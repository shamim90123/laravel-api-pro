<?php

namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadContactController extends Controller
{
    public function index(Lead $lead, Request $request)
    {
        // Optional: support ?q=search and pagination
        $query = $lead->contacts()->orderByDesc('is_primary')->orderByDesc('id');

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('job_title', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->get('per_page', 10);

        $contacts = $query->paginate($perPage)->through(function ($contact) {
            return [
                'id'             => $contact->id,
                'name'           => $contact->name,
                'email'          => $contact->email,
                'phone'          => $contact->phone,
                'job_title'      => $contact->job_title,
                'department'     => $contact->department,
                'primary_status' => (bool) $contact->is_primary,
                'created_at'     => $contact->created_at,
            ];
        });

        return response()->json($contacts);
    }

    // store and update
    public function store_update(Lead $lead, Request $request)
    {
        $payload = $request->all();
        $items = isset($payload[0]) ? $payload : [$payload];

        $data = validator($items, [
            '*.id'              => ['nullable', 'integer', 'exists:lead_contacts,id'],
            '*.first_name'      => ['required', 'string', 'max:255'],
            '*.last_name'       => ['required', 'string', 'max:255'],
            '*.email'           => ['nullable', 'email', 'max:255'],
            '*.phone'           => ['nullable', 'string', 'max:255'],
            '*.job_title'       => ['nullable', 'string', 'max:255'],
            '*.department'      => ['nullable', 'string', 'max:255'],
            '*.is_primary'      => ['nullable', 'boolean'],
        ])->validate();

        // 🔁 Normalize: build `name` from first/last, remove split fields
        $data = array_map(static function (array $item) {
            $first = trim((string) ($item['first_name'] ?? ''));
            $last  = trim((string) ($item['last_name'] ?? ''));
            $full  = trim(preg_replace('/\s+/u', ' ', $first.' '.$last));

            $item['name'] = $full;

            return $item;
        }, $data);

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
