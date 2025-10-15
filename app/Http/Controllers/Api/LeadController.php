<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        // $this->authorize('viewAny', Lead::class);
        $leads = Lead::latest()->paginate(10);
        return response()->json($leads);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'lead_name' => ['required', 'string', 'max:255'],
            'destination_id' => ['required', 'integer'],
            'city' => ['nullable', 'string', 'max:255'],
        ]);

        $lead = Lead::create($data);
        return response()->json($lead, 201);
    }
    public function show($id)
    {
        $lead = Lead::with('contacts')->find($id);
    if (!$lead) {
        return response()->json(['message' => 'Lead not found'], 404);
    }
    return response()->json($lead);
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'name'   => ['sometimes','required','string','max:255'],
            'email'  => ['sometimes','required','email','max:255'],
            'firstname' => ['nullable','string','max:255'],
            'lastname'  => ['nullable','string','max:255'],
            'job_title' => ['nullable','string','max:255'],
            'phone'  => ['nullable','string','max:255'],
            'city'   => ['nullable','string','max:255'],
            'link'   => ['nullable','string','max:2048'],
            'item_id'=> ['nullable','string','max:255'],

            'sams_pay' => ['boolean'],
            'sams_manage' => ['boolean'],
            'sams_platform' => ['boolean'],
            'sams_pay_client_management' => ['boolean'],
            'booked_demo' => ['boolean'],
            'comments' => ['nullable','string'],
        ]);

        $lead->update($data);
        return $lead;
    }


    public function destroy(Lead $lead)
    {
        // $this->authorize('delete', $lead);
        $lead->delete();
        return response()->json(['message'=>'Deleted']);
    }
}
