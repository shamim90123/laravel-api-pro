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

    public function bulkImporter(Request $request)
    {
        // Accept any array of rows; we’ll map keys inside
        $data = $request->validate([
            'leads' => ['required', 'array'],
            'leads.*' => ['array'],
        ]);

        $created = [];

        $toBool = function ($v) {
            $s = strtolower(trim((string)$v));
            return in_array($s, ['1','true','yes','y','✓','✔','booked','done'], true);
        };

        $clean = function ($v) {
            $t = is_string($v) ? trim($v) : $v;
            return ($t === '' ? null : $t);
        };

        $findProductId = function (string $label) {
            // match by exact name (case-insensitive). Adjust if you have slugs/codes.
            return optional(
                Product::query()
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($label)])
                    ->first(['id'])
            )->id;
        };

        // Map UI/product column labels to catalog names you actually store:
        $productColumns = [
            'SAMS Manage'               => 'SAMS Manage',
            'SAMS Pay'                  => 'SAMS Pay',
            'SAMS Pay Client Management'=> 'SAMS Pay Client Management',
            'SAMS Perform'              => 'SAMS Perform',
        ];
        // --------------------------------------------------------------------

        DB::transaction(function () use ($data, &$created, $clean, $toBool, $findProductId, $productColumns, $fallbackDestinationId) {

            foreach ($data['leads'] as $row) {
                // 1) Lead
                $leadName = $clean($row['Name'] ?? null);
                if (!$leadName) {
                    // skip blank rows safely; or throw if you want strict mode
                    continue;
                }

                $city = $clean($row['City'] ?? null);

                $lead = Lead::create([
                    'lead_name'      => $leadName,
                    'destination_id' => null, // <- required by schema; no mapping requested
                    'city'           => $city,
                ]);

                // 2) Primary Contact (optional)
                $firstName  = $clean($row['First Name'] ?? null);
                $lastName   = $clean($row['Last Name'] ?? null);
                $email      = $clean($row['Email'] ?? null);
                $phone      = $clean($row['Phone'] ?? null);
                $jobTitle   = $clean($row['Job Title'] ?? null);
                $itemId     = $clean($row['Item ID (auto generated)'] ?? null);
                $bookedDemo = $toBool($row['Booked Demo'] ?? '');

                $contact = null;
                // create a contact if there’s at least some contact info
                if ($firstName || $lastName || $email || $phone || $jobTitle || $itemId) {
                    $contact = LeadContact::create([
                        'lead_id'     => $lead->id,
                        'first_name'  => $firstName,
                        'last_name'   => $lastName,
                        'email'       => $email,
                        'phone'       => $phone,
                        'job_title'   => $jobTitle,
                        'item_id'     => $itemId,     // <-- ensure these columns exist
                        'booked_demo' => $bookedDemo, // <-- ensure boolean column exists
                        'is_primary'  => true,
                    ]);
                }

                // 3) Comment (optional)
                $commentText = $clean($row['Comments'] ?? null);
                if ($commentText) {
                    LeadComment::create([
                        'lead_id'    => $lead->id,
                        'contact_id' => optional($contact)->id, // can be null if your schema allows
                        'comment'    => $commentText,
                    ]);
                }

                // 4) Products (optional)
                foreach ($productColumns as $incomingCol => $catalogName) {
                    $val = $row[$incomingCol] ?? null;
                    if ($clean($val) === null) {
                        continue; // only link when not empty
                    }
                    $pid = $findProductId($catalogName);
                    if ($pid) {
                        // Use your relation or pivot model
                        // Example with a standard many-to-many:
                        if (method_exists($lead, 'products')) {
                            $lead->products()->syncWithoutDetaching([$pid]);
                        } else {
                            // or insert directly to `lead_products` pivot:
                            DB::table('lead_products')->updateOrInsert(
                                ['lead_id' => $lead->id, 'product_id' => $pid],
                                ['lead_id' => $lead->id, 'product_id' => $pid]
                            );
                        }
                    }
                }

                $created[] = $lead;
            }
        });

        return response()->json([
            'message' => 'Leads imported successfully',
            'leads'   => $created,
        ], 201);
    }
}
