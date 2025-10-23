<?php
namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Lead::query()
            ->with(['destination:id,flag,name,iso_3166_2', 'accountManager:id,name'])
            ->withCount(['contacts', 'comments as notes_count']);

        if ($search = $request->get('q')) {
            $q->where(function ($s) use ($search) {
                $s->where('lead_name', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        $sortBy    = $request->get('sort_by', 'lead_name');
        $direction = strtolower($request->get('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortable  = ['lead_name', 'city', 'created_at', 'contacts_count', 'notes_count'];
        if (!in_array($sortBy, $sortable, true)) $sortBy = 'lead_name';

        $perPage = (int) $request->get('per_page', 10);

        $leads = $q->orderBy($sortBy, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json($leads);
    }

    public function store(Request $request): JsonResponse
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
            return response()->json(['message' => 'Lead updated successfully', 'lead' => $lead], 200);
        }

        $lead = Lead::create([
            'lead_name'      => $data['lead_name'],
            'destination_id' => $data['destination_id'],
            'city'           => $data['city'] ?? null,
        ]);

        return response()->json(['message' => 'Lead created successfully', 'lead' => $lead], 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load([
            'destination:id,name,flag,iso_3166_2',
            'contacts',
            'comments' => fn($q) => $q->latest('created_at'),
            'comments.user:id,name',
            'accountManager:id,name',
        ]);

        return response()->json($lead);
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->leadProducts()->delete();
        $lead->contacts()->delete();
        $lead->comments()->delete();
        $lead->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function assignAccountManager(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'user_ids.user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        $lead->update(['account_manager_id' => $validated['user_ids']['user_id'] ?? null]);

        return response()->json([
            'message' => 'Account Manager updated successfully',
            'lead'    => $lead->fresh(['accountManager']),
        ], 200);
    }
}
