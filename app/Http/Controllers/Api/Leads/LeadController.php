<?php
namespace App\Http\Controllers\Api\Leads;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;


use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\LeadContact;
use App\Models\Product;
use App\Models\SaleStage;
use App\Models\LeadProduct;
use Carbon\Carbon;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $q = Lead::query()
            ->with(['destination:id,flag,name,iso_3166_2', 'accountManager:id,name', 'leadProducts'])
            ->withCount(['contacts', 'comments as notes_count']);

        // ---- Existing 'q' (global search) ----
        if ($search = $request->get('q')) {
            $q->where(function ($s) use ($search) {
                $s->where('lead_name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // ---- NEW: discrete filters ----
        if ($leadName = $request->get('lead_name')) {
            $q->where('lead_name', 'like', "%{$leadName}%");
        }
        if ($city = $request->get('city')) {
            $q->where('city', 'like', "%{$city}%");
        }
        // status can be 0,1,2 — ensure we accept "0"
        if ($request->has('status')) {
            $status = $request->get('status');
            if ($status !== '' && $status !== null) {
                $q->where('status', (int) $status);
            }
        }

        // destination can be id or text; prefer id if numeric
        if ($dest = $request->get('destination')) {
            if (is_numeric($dest)) {
                $q->where('destination_id', (int) $dest);
            } else {
                // optional: allow filtering by destination name
                $q->whereHas('destination', function ($dq) use ($dest) {
                    $dq->where('name', 'like', "%{$dest}%")
                    ->orWhere('iso_3166_2', 'like', "%{$dest}%");
                });
            }
        }

        // ---- Sorting (keep your safe list) ----
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

    public function bulkImporter(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'leads'                                 => ['required', 'array'],
            'leads.*.Name'                          => ['required', 'string', 'max:255'],
            'leads.*.City'                          => ['nullable', 'string', 'max:255'],
            'leads.*.Destination'                   => ['nullable'],
            'leads.*.First Name'                    => ['nullable', 'string', 'max:255'],
            'leads.*.Last Name'                     => ['nullable', 'string', 'max:255'],
            'leads.*.Email'                         => ['nullable', 'string', 'email', 'max:255'],
            'leads.*.Job Title'                     => ['nullable', 'string', 'max:255'],
            'leads.*.Phone'                         => ['nullable', 'string', 'max:50'],
            'leads.*.Item ID (auto generated)'      => ['nullable'],
            'leads.*.Link'                          => ['nullable'],             // <- fixed
            'leads.*.Comments'                      => ['nullable', 'string'],   // <- NEW

            // Each product column contains a Stage name (string). If empty -> we record as "skipped".
            'leads.*.SAMS Manage'             => ['nullable', 'string', 'max:255'],
            'leads.*.SAMS Pay'                => ['nullable', 'string', 'max:255'],
            'leads.*.SAMS Pay Client Management' => ['nullable', 'string', 'max:255'],
            'leads.*.SAMS Perform'            => ['nullable', 'string', 'max:255'],
        ]);

        $productColumns = [
            'SAMS Manage'                 => 'SAMS Manage',
            'SAMS Pay'                    => 'SAMS Pay',
            'SAMS Pay Client Management'  => 'SAMS Pay Client Management',
            'SAMS Perform'                => 'SAMS Perform',
            'Booked Demo'                 => 'Booked Demo',
        ];


        $created    = 0;
        $updated    = 0;
        $contactsUp = 0;
        $lpUpserts  = 0;
        $commentsCreated = 0;

        // Collect diagnostics of not-inserted / skipped items
        $skips = [
            'leads_skipped_for_blank_name' => [],         // [ {row_index, reason} ]
            'contacts_skipped_no_identifier' => [],       // [ {row_index, lead_id, reason} ]
            'lead_products_skipped_no_stage' => [],       // [ {row_index, lead_id, product} ]
            'lead_products_conflicts' => [],              // [ {row_index, lead_id, product, reason} ]
            'leads_existing' => [],                       // [ {row_index, lead_id, lead_name} ]
            'row_errors' => [],                           // [ {row_index, reason, row_payload} ]
            'comments_skipped' => [],  // [{row_index, lead_id, reason}]
        ];

        DB::transaction(function () use (
            &$created, &$updated, &$contactsUp, &$lpUpserts, $commentsCreated,
            $validated, $productColumns, &$skips
        ) {
            foreach ($validated['leads'] as $idx => $row) {
                try {
                    $leadName = trim((string) \Illuminate\Support\Arr::get($row, 'Name', ''));
                    $city     = self::n(\Illuminate\Support\Arr::get($row, 'City'));
                    $destRaw  = \Illuminate\Support\Arr::get($row, 'Destination');

                    $destinationId = self::resolveDestinationId($destRaw) ?? null;

                    if ($leadName === '') {
                        $skips['leads_skipped_for_blank_name'][] = [
                            'row_index' => $idx,
                            'reason'    => 'Blank Name after trimming.',
                        ];
                        continue;
                    }

                    // Lead upsert by name (case-insensitive)
                    $lead = \App\Models\Lead::whereRaw('LOWER(lead_name) = ?', [\Illuminate\Support\Str::lower($leadName)])->first();

                    if (!$lead) {
                        $lead = \App\Models\Lead::create([
                            'lead_name'      => $leadName,
                            'city'           => $city,
                            'destination_id' => $destinationId,
                        ]);
                        $created++;
                    } else {
                        // Record that it already existed (not a failure, just not "inserted")
                        $skips['leads_existing'][] = [
                            'row_index' => $idx,
                            'lead_id'   => $lead->id,
                            'lead_name' => $lead->lead_name,
                        ];

                        $dirty = false;
                        if ($city && $city !== $lead->city) {
                            $lead->city = $city;
                            $dirty = true;
                        }
                        if ($destinationId && $destinationId !== (int) $lead->destination_id) {
                            $lead->destination_id = $destinationId;
                            $dirty = true;
                        }
                        if ($dirty) {
                            $lead->save();
                            $updated++;
                        }
                    }

                    // Contact upsert
                    $first     = self::n(\Illuminate\Support\Arr::get($row, 'First Name'));
                    $last      = self::n(\Illuminate\Support\Arr::get($row, 'Last Name'));
                    $email     = self::n(\Illuminate\Support\Arr::get($row, 'Email'));
                    $jobTitle  = self::n(\Illuminate\Support\Arr::get($row, 'Job Title'));
                    $phone     = self::n(\Illuminate\Support\Arr::get($row, 'Phone'));
                    $itemId    = self::n(\Illuminate\Support\Arr::get($row, 'Item ID (auto generated)'));
                    $link      = self::n(\Illuminate\Support\Arr::get($row, 'Link'));
                    $fullName  = trim(implode(' ', array_filter([$first, $last])));

                    $contactQuery = \App\Models\LeadContact::query()->where('lead_id', $lead->id);
                    if ($email) {
                        $contactQuery->where('email', $email);
                    } elseif ($phone) {
                        $contactQuery->where('phone', $phone);
                    } else {
                        if (!$first && !$last && !$jobTitle && !$phone) {
                            $skips['contacts_skipped_no_identifier'][] = [
                                'row_index' => $idx,
                                'lead_id'   => $lead->id,
                                'reason'    => 'No email/phone and no other meaningful contact fields.',
                            ];
                            // continue into products — contact is optional
                            goto PRODUCTS;
                        }
                    }

                    $contact = $contactQuery->first();

                    if (!$contact) {
                        $contact = new \App\Models\LeadContact();
                        $contact->lead_id    = $lead->id;
                        $contact->first_name = $first;
                        $contact->last_name  = $last;
                        $contact->name       = $fullName ?: null;
                        $contact->email      = $email;
                        $contact->phone      = $phone;
                        $contact->job_title  = $jobTitle;
                        $contact->item_id    = $itemId;
                        $contact->link       = $link;
                        $contact->save();
                        $contactsUp++;
                    } else {
                        $dirty = false;
                        foreach ([
                            'first_name' => $first,
                            'last_name'  => $last,
                            'name'       => ($fullName ?: null),
                            'email'      => $email,
                            'phone'      => $phone,
                            'job_title'  => $jobTitle,
                            'item_id'    => $itemId,
                            'link'       => $link,
                        ] as $field => $val) {
                            if ($val && $val !== $contact->{$field}) {
                                $contact->{$field} = $val;
                                $dirty = true;
                            }
                        }
                        if ($dirty) {
                            $contact->save();
                            $contactsUp++;
                        }
                    }

                    // log contact info if needed later
                    // \Log::info("Processed contact ID {$contact->id} for lead ID {$lead->id}");

                    // Products + Stages
                    PRODUCTS:
                    foreach ($productColumns as $column => $productName) {
                        try {
                            $stageName = self::n(\Illuminate\Support\Arr::get($row, $column));
                            if (!$stageName) {
                                // The product column exists for this template, but no stage value provided in this row
                                $skips['lead_products_skipped_no_stage'][] = [
                                    'row_index' => $idx,
                                    'lead_id'   => $lead->id,
                                    'product'   => $productName,
                                ];
                                continue;
                            }

                            // Ensure Product (by name)
                            $product = \App\Models\Product::whereRaw('LOWER(name) = ?', [\Illuminate\Support\Str::lower($productName)])->first();
                            if (!$product) {
                                $product = \App\Models\Product::create(['name' => $productName]);
                            }

                            // Ensure Stage (global, not product-scoped in your latest code)
                            $stage = \App\Models\SaleStage::whereRaw('LOWER(name) = ?', [\Illuminate\Support\Str::lower($stageName)])->first();
                            if (!$stage) {
                                $stage = \App\Models\SaleStage::create(['name' => $stageName]);
                            }

                            // Upsert lead_products with stage_id
                            $lp = \App\Models\LeadProduct::where('lead_id', $lead->id)
                                ->where('product_id', $product->id)
                                ->first();

                            if (!$lp) {
                                \App\Models\LeadProduct::create([
                                    'lead_id'    => $lead->id,
                                    'product_id' => $product->id,
                                    'stage_id'   => $stage->id,
                                ]);
                                $lpUpserts++;
                            } else {
                                if ($lp->stage_id !== $stage->id) {
                                    $lp->stage_id = $stage->id;
                                    $lp->save();
                                    $lpUpserts++;
                                }
                            }
                        } catch (\Throwable $e) {
                            $skips['lead_products_conflicts'][] = [
                                'row_index' => $idx,
                                'lead_id'   => $lead->id ?? null,
                                'product'   => $productName,
                                'reason'    => $e->getMessage(),
                            ];
                            // keep processing other products/rows
                        }
                    }


                    // --- Comments import (optional per row)
                    $commentText = self::n(\Illuminate\Support\Arr::get($row, 'Comments'));
                    if ($commentText) {
                        try {
                            // Attach to the lead; prefer linking to the contact we just upserted (if any)
                            // contact may be null if no identifier was given and we "goto PRODUCTS"
                            $contactId = isset($contact) && $contact ? $contact->id : null;

                            \App\Models\LeadComment::create([
                                'lead_id'    => $lead->id,
                                'user_id' => $contactId,
                                'user_type' => 2, // 2 = LeadContact
                                'comment'    => $commentText,
                            ]);
                            $commentsCreated++;
                        } catch (\Throwable $e) {
                            $skips['comments_skipped'][] = [
                                'row_index' => $idx,
                                'lead_id'   => $lead->id ?? null,
                                'reason'    => $e->getMessage(),
                            ];
                        }
                    }


                } catch (\Throwable $e) {
                    $skips['row_errors'][] = [
                        'row_index'  => $idx,
                        'reason'     => $e->getMessage(),
                        'row_payload'=> $row, // echo back the offending row for quick debugging
                    ];
                    // continue with next row
                }
            }
        });

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

    /** helpers */
    private static function n($v): ?string
    {
        if ($v === null) return null;
        $s = trim((string) $v);
        return $s === '' ? null : $s;
    }

    private static function resolveDestinationId($raw): ?int
    {
        if ($raw === null) return null;
        if (is_numeric($raw)) return (int) $raw;
        return null;
    }


    public function bulkCommentImporter(Request $request): JsonResponse
    {
        // 1) Validate basic structure
        $validated = $request->validate([
            'comments'                         => ['required', 'array'],
            'comments.*.Item ID'               => ['nullable'],          // string or number
            'comments.*.Item Name'             => ['nullable', 'string'],
            'comments.*.User'                  => ['required', 'string'],
            'comments.*.Update Content'        => ['required', 'string'],
            'comments.*.Created At'            => ['required', 'string'],
            // Other fields in the payload are ignored
        ]);

        $inserted = 0;

        // Diagnostics
        $skips = [
            'lead_not_found'    => [], // [{row_index, item_id, item_name, reason}]
            'user_not_found'    => [], // [{row_index, user}]
            'date_parse_failed' => [], // [{row_index, created_at_raw}]
            'row_errors'        => [], // [{row_index, reason}]
        ];

        DB::transaction(function () use (&$inserted, &$skips, $validated) {
            foreach ($validated['comments'] as $idx => $row) {
                try {
                    // --- Extract fields (tolerant of missing keys) ---
                    $itemId        = self::sv($row, 'Item ID');        // may be numeric or string
                    $itemName      = self::sv($row, 'Item Name');
                    $userName      = self::sv($row, 'User');           // required by validation
                    $commentText   = self::sv($row, 'Update Content'); // required by validation
                    $createdAtRaw  = self::sv($row, 'Created At');     // required by validation

                    // --- Resolve lead_id ---
                    $leadId = null;

                    // Priority 1: by lead_contacts.item_id
                    if ($itemId !== null && $itemId !== '') {
                        $contact = \App\Models\LeadContact::query()
                            ->where('item_id', (string)$itemId)
                            ->first();
                        if ($contact) {
                            $leadId = (int)$contact->lead_id;
                        }
                    }

                    // Fallback: by leads.lead_name = Item Name (case-insensitive)
                    if (!$leadId && $itemName) {
                        $lead = \App\Models\Lead::query()
                            ->whereRaw('LOWER(lead_name) = ?', [Str::lower($itemName)])
                            ->first();
                        if ($lead) {
                            $leadId = (int)$lead->id;
                        }
                    }

                    if (!$leadId) {
                        $skips['lead_not_found'][] = [
                            'row_index' => $idx,
                            'item_id'   => $itemId,
                            'item_name' => $itemName,
                            'reason'    => 'Could not resolve lead via LeadContact.item_id or Lead.lead_name',
                        ];
                        continue; // cannot insert (lead_id is NOT NULL)
                    }

                    // --- Resolve user_id by users.name (case-insensitive) ---
                    $user = \App\Models\User::query()
                        ->whereRaw('LOWER(name) = ?', [Str::lower($userName)])
                        ->first();

                    if (!$user) {
                        $skips['user_not_found'][] = [
                            'row_index' => $idx,
                            'user'      => $userName,
                        ];
                        continue; // cannot insert (user_id is NOT NULL)
                    }

                    // --- Parse created_at ---
                    $createdAt = self::parseHumanDate($createdAtRaw);
                    if (!$createdAt) {
                        $skips['date_parse_failed'][] = [
                            'row_index'      => $idx,
                            'created_at_raw' => $createdAtRaw,
                        ];
                        continue;
                    }

                    // --- (Optional) de-duplication guard ---
                    // If you want to avoid duplicates, uncomment the check below:
                    /*
                    $exists = \App\Models\LeadComment::query()
                        ->where('lead_id', $leadId)
                        ->where('user_id', $user->id)
                        ->where('comment', $commentText)
                        ->where('created_at', $createdAt)
                        ->exists();
                    if ($exists) {
                        // Already imported; skip silently or record a reason if you prefer
                        continue;
                    }
                    */

                    // --- Insert row ---
                    \App\Models\LeadComment::insert([
                        'lead_id'    => $leadId,
                        'user_id'    => (int)$user->id,
                        'comment'    => $commentText,
                        'created_at' => $createdAt,    // honour source timestamp
                        'updated_at' => $createdAt,    // keep same as created_at
                        'user_type' => 2,    // 2 = LeadContact
                    ]);

                    $inserted++;
                } catch (\Throwable $e) {
                    $skips['row_errors'][] = [
                        'row_index' => $idx,
                        'reason'    => $e->getMessage(),
                    ];
                    // continue to next row
                }
            }
        });

        return response()->json([
            'message' => 'Bulk comment import completed',
            'summary' => [
                'comments_inserted' => $inserted,
            ],
            'not_inserted' => $skips,
        ], 201);
    }

    /**
     * Safe value getter & normalizer: trims strings; returns null for empty strings.
     */
    private static function sv(array $row, string $key): ?string
    {
        if (!array_key_exists($key, $row)) return null;
        $v = is_string($row[$key]) ? trim($row[$key]) : $row[$key];
        if ($v === '' || $v === null) return null;
        return is_string($v) ? $v : (string)$v;
    }

    /**
     * Parse dates like "28/January/2025  09:09:47 PM" (extra spaces, month name).
     * Falls back to Carbon::parse().
     */
    private static function parseHumanDate(?string $raw): ?string
    {
        if (!$raw) return null;

        // Collapse multiple spaces
        $norm = preg_replace('/\s+/', ' ', trim($raw));

        // Try explicit formats (day/MonthName/Year hour:minute:second AM|PM)
        $formats = [
            'd/F/Y h:i:s A',     // 28/January/2025 09:09:47 PM
            'd/F/Y h:i A',       // 28/January/2025 09:09 PM
            'd-M-Y h:i:s A',     // 28-Jan-2025 09:09:47 PM
            'd-m-Y h:i:s A',     // 28-01-2025 09:09:47 PM
            'Y-m-d H:i:s',       // 2025-01-28 21:09:47
            'Y-m-d\TH:i:s',      // ISO-ish without TZ
        ];

        foreach ($formats as $fmt) {
            try {
                $c = Carbon::createFromFormat($fmt, $norm, config('app.timezone') ?? 'Asia/Dhaka');
                if ($c !== false) {
                    return $c->toDateTimeString(); // "Y-m-d H:i:s"
                }
            } catch (\Throwable $e) {
                // try next
            }
        }

        // Last resort: free parse
        try {
            $c = Carbon::parse($raw, config('app.timezone') ?? 'Asia/Dhaka');
            return $c->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }


    /**
     * PATCH /api/v1/leads/{lead}/status
     * Body: { "status": 0|1|2 }
     */
    public function updateStatus(Request $request, Lead $lead): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'integer', 'in:0,1,2'],
        ]);

        $lead->status = (int) $validated['status'];
        $lead->save();

        $lead->loadMissing([
            'destination:id,flag,name,iso_3166_2',
            'accountManager:id,name',
        ])->loadCount([
            'contacts',
            'comments as notes_count',
        ]);

        return response()->json([
            'message' => 'Lead status updated',
            'data'    => $lead,
        ]);
    }
}
