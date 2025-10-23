<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Product;



class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query()
            ->with([
                'destination:id,flag,name,iso_3166_2',
                'accountManager:id,name', // optional, used by UI
            ])
            ->withCount([
                'contacts',               // -> contacts_count
                'comments as notes_count' // alias for UI "notes"
            ]);

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('lead_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Safe sorting (allow-list)
        $sortBy    = $request->get('sort_by', 'lead_name');
        $direction = strtolower($request->get('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $sortable = ['lead_name', 'city', 'created_at', 'contacts_count', 'notes_count'];
        if (! in_array($sortBy, $sortable, true)) {
            $sortBy = 'lead_name';
        }

        $perPage = (int) $request->get('per_page', 10);

        $leads = $query
            ->orderBy($sortBy, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($leads);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id'        => ['nullable', 'integer', 'exists:leads,id'],
            'lead_name'      => ['required', 'string', 'max:255'],
            'destination_id' => ['required', 'integer'],
            'city'           => ['nullable', 'string', 'max:255'],
        ]);

        if (!empty($data['lead_id'])) {
            $lead = Lead::findOrFail($data['lead_id']);
            $lead->update([
                'lead_name'      => $data['lead_name'],
                'destination_id' => $data['destination_id'],
                'city'           => $data['city'] ?? null,
            ]);

            return response()->json([
                'message' => 'Lead updated successfully',
                'lead'    => $lead,
            ], 200);
        }

        $lead = Lead::create([
            'lead_name'      => $data['lead_name'],
            'destination_id' => $data['destination_id'],
            'city'           => $data['city'] ?? null,
        ]);

        return response()->json([
            'message' => 'Lead created successfully',
            'lead'    => $lead,
        ], 201);
    }

    public function show($id)
    {
        $lead = Lead::with([
                'destination:id,name,flag,iso_3166_2',
                'contacts',
                'comments' => function($q) { $q->latest('created_at'); },
                'comments.user:id,name', // include commenter name
                'accountManager:id,name' // include account manager name
            ])->find($id);

        if (!$lead) {
            return response()->json(['message' => 'Lead not found'], 404);
        }

        return response()->json($lead);
    }

    public function destroy(Lead $lead)
    {
        $lead->leadProducts()->delete();
        $lead->contacts()->delete();
        $lead->comments()->delete();

        // Finally delete the lead itself
        $lead->delete();
        return response()->json(['message' => 'Deleted']);
    }

    // ========================
    // Comment Endpoints
    // ========================

    // List comments for a lead (paginated)
    public function comments(Lead $lead, Request $request)
    {
        $perPage = (int) ($request->get('per_page', 10));
        $comments = LeadComment::with('user:id,name')
            ->where('lead_id', $lead->id)
            ->latest('created_at')
            ->paginate($perPage);

        return response()->json($comments);
    }

    // Create a comment for a lead
    public function storeComment(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'comment' => ['required', 'string', 'max:5000'],
        ]);

        // If you use Sanctum, you likely want the authenticated user:
        $userId = auth()->id() ?? $request->get('user_id'); // fallback if you pass user_id explicitly

        if (!$userId) {
            return response()->json(['message' => 'User not identified'], 422);
        }

        $comment = LeadComment::create([
            'lead_id' => $lead->id,
            'user_id' => $userId,
            'comment' => $validated['comment'],
        ]);

        // eager load user name for response
        $comment->load('user:id,name');

        return response()->json([
            'message' => 'Comment added',
            'comment' => $comment,
        ], 201);
    }

    // Delete a specific comment
    public function destroyComment(Lead $lead, LeadComment $comment)
    {
        if ($comment->lead_id !== $lead->id) {
            return response()->json(['message' => 'Comment does not belong to this lead'], 422);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted']);
    }


    /**
     * GET: List products currently linked to the lead
     */
    // public function products(Lead $lead)
    // {
    //     // Return minimal fields needed by your UI (id, name, code/sku, etc.)
    //     $items = $lead->products()
    //         ->select('products.id', 'products.name')
    //         ->orderBy('products.name')
    //         ->get();

    //     return response()->json([
    //         'data' => $items,
    //     ]);
    // }

public function products(Lead $lead)
{
    $items = $lead->products()
        ->withPivot(['stage_id', 'account_manager_id'])
        ->select('products.id', 'products.name')
        ->orderBy('products.name')
        ->get()
        ->map(fn ($p) => [
            'id'   => $p->id,
            'name' => $p->name,
            'pivot'=> [
                'sales_stage_id'     => $p->pivot->stage_id,
                'account_manager_id' => $p->pivot->account_manager_id,
            ],
        ]);

    return response()->json(['data' => $items]);
}


/**
 * PUT /leads/{lead}/products/{product}
 * body: { sales_stage_id: nullable|int, account_manager_id: nullable|int }
 */
public function updateProductLink(Request $request, Lead $lead, Product $product)
{
    $validated = $request->validate([
        'sales_stage_id'     => ['nullable', 'integer', 'exists:lead_stages,id'],
        'account_manager_id' => ['nullable', 'integer', 'exists:users,id'],
    ]);

    // attach if missing; then update pivot
    if (! $lead->products()->where('products.id', $product->id)->exists()) {
        $lead->products()->attach($product->id, [
            'stage_id'            => $validated['sales_stage_id']     ?? null,
            'account_manager_id'  => $validated['account_manager_id'] ?? null,
        ]);
    } else {
        $lead->products()->updateExistingPivot($product->id, [
            'stage_id'            => $validated['sales_stage_id']     ?? null,
            'account_manager_id'  => $validated['account_manager_id'] ?? null,
        ]);
    }

    // return the refreshed single link
    $ref = $lead->products()->where('products.id', $product->id)
        ->withPivot(['stage_id', 'account_manager_id'])
        ->firstOrFail();

    return response()->json([
        'message' => 'Product link updated',
        'data' => [
            'id'   => $ref->id,
            'name' => $ref->name,
            'pivot'=> [
                'sales_stage_id'     => $ref->pivot->stage_id,
                'account_manager_id' => $ref->pivot->account_manager_id,
            ],
        ],
    ]);
}

/**
 * PUT /api/v1/leads/{lead}/products/bulk
 * Body:
 * {
 *   "items": [
 *     { "product_id": 11, "sales_stage_id": 3, "account_manager_id": 7 },
 *     { "product_id": 12, "sales_stage_id": null, "account_manager_id": 5 }
 *   ]
 * }
 */
public function bulkUpdateProductLinks(Request $request, Lead $lead)
{
    $validated = $request->validate([
        'items'                      => ['required', 'array', 'min:1'],
        'items.*.product_id'         => ['required', 'integer', 'exists:products,id'],
        'items.*.sales_stage_id'     => ['nullable', 'integer', 'exists:lead_stages,id'],
        'items.*.account_manager_id' => ['nullable', 'integer', 'exists:users,id'],
    ]);

    DB::transaction(function () use ($lead, $validated) {
        foreach ($validated['items'] as $it) {
            $lead->products()->syncWithoutDetaching([
                $it['product_id'] => [
                    'stage_id'            => $it['sales_stage_id']     ?? null,
                    'account_manager_id'  => $it['account_manager_id'] ?? null,
                ],
            ]);
        }
    });

    // return refreshed list
    $items = $lead->products()
        ->withPivot(['stage_id', 'account_manager_id'])
        ->select('products.id', 'products.name')
        ->orderBy('products.name')
        ->get()
        ->map(fn ($p) => [
            'id'   => $p->id,
            'name' => $p->name,
            'pivot'=> [
                'sales_stage_id'     => $p->pivot->stage_id,
                'account_manager_id' => $p->pivot->account_manager_id,
            ],
        ]);

    return response()->json([
        'message' => 'Product links updated',
        'data'    => $items,
    ]);
}

     /**
     * PUT/POST: Assign products to a lead (replaces existing set).
     * Body: { product_ids: [1,2,3] }
     */
    public function assignProducts(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'product_ids'   => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        $ids = array_values(array_unique($validated['product_ids']));

        DB::transaction(function () use ($lead, $ids) {
            // Replace the whole set; use syncWithoutDetaching($ids) if you want additive behavior
            $lead->products()->sync($ids);
        });

        // Return updated list for convenience
        $items = $lead->products()
            ->select('products.id', 'products.name',)
            ->orderBy('products.name')
            ->get();

        return response()->json([
            'message' => 'Products assigned successfully.',
            'data'    => $items,
        ], Response::HTTP_OK);
    }


    /**
     * Show a list of all of the countries.
     *
     *
     */
    public function getCountries()
    {
        $countries = DB::table('countries')->get();

        return response()->json([
            'message' => 'Countries retrieved successfully.',
            'data'    => $countries,
        ], Response::HTTP_OK);
    }

    public function assignAccountManager(Request $request, $leadId)
    {
        // ✅ Validate input
        $validated = $request->validate([
            'user_ids.user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // ✅ Find lead or fail if not found
        $lead = Lead::findOrFail($leadId);

        // ✅ Extract user_id from nested payload
        $userId = $validated['user_ids']['user_id'] ?? null;

        // ✅ Update lead with assigned account manager
        $lead->update([
            'account_manager_id' => $userId,
        ]);

        return response()->json([
            'message' => 'Account Manager updated successfully',
            'lead' => $lead->fresh(['accountManager']), // include relationship if you have one
        ], 200);
    }


    public function leadComments(\App\Models\Lead $lead)
{
    $comments = $lead->comments()->with('user:id,name')->latest()->paginate(10);
    return response()->json($comments);
}

}
