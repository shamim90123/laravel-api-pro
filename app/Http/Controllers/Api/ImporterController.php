<?php
namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImporterController extends Controller
{

    public function bulkImporter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leads'                                 => ['required', 'array'],
            'leads.*.Name'                          => ['required', 'string', 'max:255'],
        ]);

       

        return response()->json([
            'message'            => 'Bulk import completed',
            'summary' => [
                'leads_created'     => $created,
                'leads_updated'     => $updated,
                'contacts_upserted' => $contactsUp,
                'lead_products_set' => $lpUpserts,
                'lead_comments_created' => $commentsCreated
            ],
            // Everything that wasn’t inserted / was skipped with reasons:
            'not_inserted' => $skips,
        ], 201);
    }

}
