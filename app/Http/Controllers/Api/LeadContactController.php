<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadContactController extends Controller
{
    public function store(Lead $lead, Request $request)
    {
        $payload = $request->all();
        $items = isset($payload[0]) ? $payload : [$payload];

        $data = validator($items, [
            '*.id'         => ['nullable', 'integer', 'exists:lead_contacts,id'],
            '*.name'       => ['required', 'string', 'max:255'],
            '*.email'      => ['nullable', 'email', 'max:255'],
            '*.phone'      => ['nullable', 'string', 'max:255'],
            '*.job_title'  => ['nullable', 'string', 'max:255'],
            '*.department' => ['nullable', 'string', 'max:255'],
            '*.is_primary' => ['nullable', 'boolean'],
        ])->validate();

        $out = [];

        DB::transaction(function () use ($data, $lead, &$out) {
            foreach ($data as $contactData) {
                if (!empty($contactData['id'])) {
                    $contact = LeadContact::where('id', $contactData['id'])
                        ->where('lead_id', $lead->id)
                        ->firstOrFail();

                    $contact->update(collect($contactData)->except(['id', 'is_primary'])->toArray());
                } else {
                    $contact = LeadContact::create(array_merge(
                        collect($contactData)->except(['is_primary'])->toArray(),
                        ['lead_id' => $lead->id]
                    ));
                }

                if (!empty($contactData['is_primary'])) {
                    LeadContact::where('lead_id', $lead->id)
                        ->where('id', '!=', $contact->id)
                        ->update(['is_primary' => false]);

                    $contact->update(['is_primary' => true]);
                }

                $out[] = $contact->fresh();
            }
        });

        return response()->json($out, 201);
    }

    public function setPrimary(LeadContact $contact)
    {
        DB::transaction(function () use ($contact) {
            LeadContact::where('lead_id', $contact->lead_id)->update(['is_primary' => false]);
            $contact->update(['is_primary' => true]);
        });

        return response()->json([
            'message' => 'Primary contact updated.',
            'contact' => $contact->fresh(),
        ]);
    }

    // ✅ keep ONLY THIS delete method
    public function destroy(LeadContact $contact)
    {
        $leadId = $contact->lead_id;
        $wasPrimary = (bool) $contact->is_primary;

        DB::transaction(function () use ($contact, $leadId, $wasPrimary) {
            $contact->delete();

            if ($wasPrimary) {
                $fallback = LeadContact::where('lead_id', $leadId)->latest()->first();
                if ($fallback) {
                    $fallback->update(['is_primary' => true]);
                }
            }
        });

        return response()->noContent();
    }
}
