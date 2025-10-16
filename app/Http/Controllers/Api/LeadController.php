<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::query();

        if ($search = $request->get('q')) {
            $query->where('lead_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
        }

        $sortBy = $request->get('sort_by', 'lead_name');
        $direction = $request->get('direction', 'asc');

        $leads = $query->orderBy($sortBy, $direction)->paginate(10);

        return response()->json($leads);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_id' => ['nullable', 'integer', 'exists:leads,id'],
            'lead_name' => ['required', 'string', 'max:255'],
            'destination_id' => ['required', 'integer'],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        // ✅ If lead_id exists → update, else → create
        if (!empty($data['lead_id'])) {
            $lead = Lead::findOrFail($data['lead_id']);
            $lead->update([
                'lead_name' => $data['lead_name'],
                'destination_id' => $data['destination_id'],
                'city' => $data['city'] ?? null,
            ]);

            return response()->json([
                'message' => 'Lead updated successfully',
                'lead' => $lead,
            ], 200);
        }

        // ✅ Create new lead
        $lead = Lead::create([
            'lead_name' => $data['lead_name'],
            'destination_id' => $data['destination_id'],
            'city' => $data['city'] ?? null,
        ]);

        return response()->json([
            'message' => 'Lead created successfully',
            'lead' => $lead,
        ], 201);
    }

    public function show($id)
    {
        $lead = Lead::with('contacts')->find($id);
    if (!$lead) {
        return response()->json(['message' => 'Lead not found'], 404);
    }
    return response()->json($lead);
    }


    public function destroy(Lead $lead)
    {
        // $this->authorize('delete', $lead);
        $lead->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
