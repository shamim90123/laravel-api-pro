<?php

// app/Http/Controllers/LeadStageController.php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LeadStage;
use Illuminate\Http\Request;

class LeadStageController extends Controller
{
    // Get all lead_stages
    public function index()
    {
        $lead_stages = LeadStage::orderBy('name', 'asc')->get();
        return response()->json($lead_stages);
    }

    // Store a new product
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:active,inactive',
        ]);

        $leadStage = LeadStage::create([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($leadStage, 201);
    }

    // Show a product
    public function show($id)
    {
        $leadStage = LeadStage::findOrFail($id);
        return response()->json($leadStage);
    }

    // Update a product
    public function update(Request $request, $id)
    {
        $leadStage = LeadStage::findOrFail($id);
        $leadStage->update([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($leadStage);
    }

    // Toggle product status
    public function toggleStatus($id)
    {
        $leadStage = LeadStage::findOrFail($id);
        $leadStage->status = $leadStage->status === 'active' ? 'inactive' : 'active';
        $leadStage->save();

        return response()->json($leadStage);
    }

    public function destroy($id)
    {
        // Find the product by ID or fail if not found
        $leadStage = LeadStage::findOrFail($id);

        // Delete the product
        $leadStage->delete();

        // Return a response indicating the product was deleted
        return response()->json(null, 204); // No content status
    }
}
