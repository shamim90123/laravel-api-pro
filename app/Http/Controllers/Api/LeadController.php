<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;


class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with('destination:id,flag,name,iso_3166_2');

        if ($search = $request->get('q')) {
            $query->where(function($q) use ($search) {
                $q->where('lead_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'lead_name');
        $direction = $request->get('direction', 'asc');

        $leads = $query->orderBy($sortBy, $direction)->paginate(10);

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
                'comments.user:id,name' // include commenter name
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
    public function products(Lead $lead)
    {
        // Return minimal fields needed by your UI (id, name, code/sku, etc.)
        $items = $lead->products()
            ->select('products.id', 'products.name')
            ->orderBy('products.name')
            ->get();

        return response()->json([
            'data' => $items,
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
}
