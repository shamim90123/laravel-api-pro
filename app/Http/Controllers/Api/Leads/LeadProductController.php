<?php
namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LeadProductController extends Controller
{
    // GET /leads/{lead}/products
    public function index(Lead $lead): JsonResponse
    {
        $items = $lead->products()
            ->withPivot(['stage_id', 'account_manager_id', 'contact_id', 'notes', 'demo_book_id', 'demo_book_date'])
            ->select('products.id', 'products.name')
            ->orderBy('products.name')
            ->get()
            ->map(fn ($p) => [
                'id'   => $p->id,
                'name' => $p->name,
                'pivot'=> [
                    'sales_stage_id'     => $p->pivot->stage_id,
                    'account_manager_id' => $p->pivot->account_manager_id,
                    'contact_id'         => $p->pivot->contact_id,
                    'notes'              => $p->pivot->notes,
                    'demo_book_id'       => $p->pivot->demo_book_id,
                    'demo_book_date'       => $p->pivot->demo_book_date,

                ],
            ]);

        return response()->json(['data' => $items]);
    }

    // PUT/POST /leads/{lead}/products  Body: { product_ids: [1,2,...] }
    public function assign(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'product_ids'   => ['required', 'array'],
            'product_ids.*' => ['integer', 'exists:products,id'],
        ]);

        DB::transaction(fn() => $lead->products()->sync(array_values(array_unique($data['product_ids']))));

        $items = $lead->products()
            ->select('products.id', 'products.name')
            ->orderBy('products.name')
            ->get();

        return response()->json(['message' => 'Products assigned successfully.', 'data' => $items]);
    }

    // PUT /leads/{lead}/products/bulk
    // { items: [{ product_id, sales_stage_id, account_manager_id }, ...] }
    public function bulkUpdate(Request $request, Lead $lead): JsonResponse
    {
        $data = $request->validate([
            'items'                      => ['required', 'array', 'min:1'],
            'items.*.product_id'         => ['required', 'integer', 'exists:products,id'],
            'items.*.sales_stage_id'     => ['nullable', 'integer', 'exists:sale_stages,id'],
            'items.*.account_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.contact_id'         => ['nullable', 'integer', 'exists:lead_contacts,id'],
            'items.*.notes'              => ['nullable'],
            'items.*.demo_book_id'       => ['nullable'],
            'items.*.demo_book_date'       => ['nullable'],


        ]);

        DB::transaction(function () use ($lead, $data) {
            foreach ($data['items'] as $it) {
                $lead->products()->syncWithoutDetaching([
                    $it['product_id'] => [
                        'stage_id'            => $it['sales_stage_id']     ?? null,
                        'account_manager_id'  => $it['account_manager_id'] ?? null,
                        'contact_id'          => $it['contact_id'] ?? null,
                        'notes'               => $it['notes'] ?? null,
                    ],
                ]);

                $lead->products()->updateExistingPivot($it['product_id'], [
                    'contact_id' => $it['contact_id'] ?? null,
                ]);

                $lead->products()->updateExistingPivot($it['product_id'], [
                    'notes' => $it['notes'] ?? null,
                ]);

                $lead->products()->updateExistingPivot($it['product_id'], [
                    'demo_book_id' => $it['demo_book_id'] ?? null,
                ]);

                $lead->products()->updateExistingPivot($it['product_id'], [
                    'demo_book_date' => $it['demo_book_date'] ?? null,
                ]);
            }
        });

        return $this->index($lead);
    }

    // PUT /leads/{lead}/products/{product}
    // { sales_stage_id, account_manager_id }
    // public function updateSingle(Request $request, Lead $lead, Product $product): JsonResponse
    // {
    //     $attrs = $request->validate([
    //         'sales_stage_id'     => ['nullable', 'integer', 'exists:sale_stages,id'],
    //         'account_manager_id' => ['nullable', 'integer', 'exists:users,id'],
    //     ]);

    //     $exists = $lead->products()->where('products.id', $product->id)->exists();

    //     $payload = [
    //         'stage_id'            => $attrs['sales_stage_id']     ?? null,
    //         'account_manager_id'  => $attrs['account_manager_id'] ?? null,
    //     ];

    //     $exists
    //         ? $lead->products()->updateExistingPivot($product->id, $payload)
    //         : $lead->products()->attach($product->id, $payload);

    //     // Return refreshed single link
    //     $ref = $lead->products()->where('products.id', $product->id)
    //         ->withPivot(['stage_id','account_manager_id'])
    //         ->firstOrFail();

    //     return response()->json([
    //         'message' => 'Product link updated',
    //         'data' => [
    //             'id'   => $ref->id,
    //             'name' => $ref->name,
    //             'pivot'=> [
    //                 'sales_stage_id'     => $ref->pivot->stage_id,
    //                 'account_manager_id' => $ref->pivot->account_manager_id,
    //             ],
    //         ],
    //     ]);
    // }
}
