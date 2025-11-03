<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Helpers\UploadHelper;

class ProductController extends Controller
{

    public function __construct()
    {
        // Spatie permission gates per action
        $this->middleware('permission:products.view')->only(['index', 'show']);
        $this->middleware('permission:products.create')->only(['store']);
        $this->middleware('permission:products.update')->only(['update']);
        $this->middleware('permission:products.delete')->only(['destroy']);
        $this->middleware('permission:products.toggle-status')->only(['toggleStatus']);
    }


    // Get all products for product list and leads
    public function index()
    {
        $products = Product::orderBy('name', 'asc')->get();
        return response()->json($products);
    }


    // Store a new product
    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'status' => 'in:active,inactive',
    //     ]);

    //     $product = Product::create([
    //         'name' => $request->name,
    //         'status' => $request->status ?? 'active',
    //     ]);

    //     return response()->json($product, 201);
    // }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120', // 5MB max
            'status' => 'in:active,inactive',
        ]);

        $imageData = null;

        // Handle image upload
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $uploadResult = UploadHelper::uploadImageToS3($request->file('image'), 'products');

            if (!$uploadResult['success']) {
                return response()->json([
                    'error' => 'Image upload failed: ' . $uploadResult['error']
                ], 422);
            }

            $imageData = [
                'image_url' => $uploadResult['url'],
                'image_path' => $uploadResult['file_path']
            ];
        }

        $product = Product::create(array_merge([
            'name' => $request->name,
            'status' => $request->status ?? 'active',
        ], $imageData ?? []));

        return response()->json($product, 201);
    }

    // Show a product
    public function show($id)
    {
        $product = Product::findOrFail($id);
        return response()->json($product);
    }

    // Update a product
    // public function update(Request $request, $id)
    // {
    //     $product = Product::findOrFail($id);
    //     $product->update([
    //         'name' => $request->name,
    //         'status' => $request->status ?? 'active',
    //     ]);

    //     return response()->json($product);
    // }


     // Update product - handle both PUT and POST
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:5120',
            'status' => 'sometimes|in:active,inactive',
        ]);

        $updateData = [
            'name' => $request->name ?? $product->name,
            'status' => $request->status ?? $product->status,
        ];

        // Handle image upload if new image provided
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            // Delete old image if exists
            if ($product->image_path) {
                UploadHelper::deleteImageFromS3($product->image_path);
            }

            $uploadResult = UploadHelper::uploadImageToS3($request->file('image'), 'products');

            if (!$uploadResult['success']) {
                return response()->json([
                    'error' => 'Image upload failed: ' . $uploadResult['error']
                ], 422);
            }

            $updateData['image_url'] = $uploadResult['url'];
            $updateData['image_path'] = $uploadResult['file_path'];
        }
        // Handle image removal if image field is present but empty
        else if ($request->has('image') && $request->image === null) {
            // Delete old image if exists
            if ($product->image_path) {
                UploadHelper::deleteImageFromS3($product->image_path);
            }
            $updateData['image_url'] = null;
            $updateData['image_path'] = null;
        }

        $product->update($updateData);

        return response()->json($product);
    }

     // Delete product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        // Delete associated image from S3
        if ($product->image_path) {
            UploadHelper::deleteImageFromS3($product->image_path);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }

    // Toggle product status
    public function toggleStatus($id)
    {
        $product = Product::findOrFail($id);
        $product->status = $product->status === 'active' ? 'inactive' : 'active';
        $product->save();

        return response()->json($product);
    }

    public function destroybkp($id)
    {
        // Find the product by ID or fail if not found
        $product = Product::findOrFail($id);

        // Delete the product
        $product->delete();

        // Return a response indicating the product was deleted
        return response()->json(null, 204); // No content status
    }
}
