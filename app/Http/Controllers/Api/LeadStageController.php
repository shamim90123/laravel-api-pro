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

        $product = LeadStage::create([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($product, 201);
    }

    // Show a product
    public function show($id)
    {
        $product = LeadStage::findOrFail($id);
        return response()->json($product);
    }

    // Update a product
    public function update(Request $request, $id)
    {
        $product = LeadStage::findOrFail($id);
        $product->update([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($product);
    }

    // Toggle product status
    public function toggleStatus($id)
    {
        $product = LeadStage::findOrFail($id);
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return response()->json($product);
    }
}
