<?php

// app/Http/Controllers/ProductController.php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    // Get all products
    public function index()
    {
        $products = Product::orderBy('name', 'asc')->get();
        return response()->json($products);
    }

    // Store a new product
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:active,inactive',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($product, 201);
    }

    // Show a product
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    // Update a product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json($product);
    }

    // Toggle product status
    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return response()->json($product);
    }

    public function destroy($id)
    {
        // Find the product by ID or fail if not found
        $product = Product::findOrFail($id);

        // Delete the product
        $product->delete();

        // Return a response indicating the product was deleted
        return response()->json(null, 204); // No content status
    }
}
