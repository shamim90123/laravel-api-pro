<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadContact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function store(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'primary_status' => ['nullable', 'string', 'max:255'],
        ]);

        $contact = $lead->contacts()->create($data); // Assuming `contacts()` is the relationship method on Lead model
        return response()->json($contact, 201);
    }

    public function destroy(Lead $lead, LeadContact $contact)
    {
        $contact->delete();
        return response()->json(['message' => 'Contact deleted']);
    }
}
