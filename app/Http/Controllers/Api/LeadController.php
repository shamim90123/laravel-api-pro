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
            'name'   => ['required','string','max:255'],
            'email'  => ['required','email','max:255'],
            'firstname' => ['nullable','string','max:255'],
            'lastname'  => ['nullable','string','max:255'],
            'job_title' => ['nullable','string','max:255'],
            'phone'  => ['nullable','string','max:255'],
            'city'   => ['nullable','string','max:255'],
            'link'   => ['nullable','string','max:2048'],
            'item_id'=> ['nullable','string','max:255'],

            'sams_pay' => ['nullable','string','max:255'],
            'sams_manage' => ['nullable','string','max:255'],
            'sams_platform' => ['nullable','string','max:255'],
            'sams_pay_client_management' => ['nullable','string','max:255'],
            'booked_demo' => ['nullable','string','max:255'],
            'comments' => ['nullable','string'],
        ]);

        // If you link to the authenticated user:
        // $data['user_id'] = $request->user()->id;
        // For now (no auth needed), set to null-safe or a default:
        // $data['user_id'] = $request->user()->id ?? auth()->id() ?? 1;

        $lead = Lead::create($data);
        return response()->json($lead, 201);
    }

    public function show(Lead $lead)
    {
        // $this->authorize('view', $lead);
        return response()->json($lead->load('user:id,name'));
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
