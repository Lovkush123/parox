<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\ProductReview;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */

public function index(Request $request)
{
    $query = Product::query();

    // Eager load relationships
    $query->with(['category', 'sizes', 'images', 'reviews', 'coupons']);

    // 🔍 Filters
    if ($request->filled('category_id')) {
        $query->where('category_id', $request->category_id);
    }

    if ($request->filled('name')) {
        $query->where('name', 'like', '%' . $request->name . '%');
    }

    if ($request->filled('status')) {
        $query->where('status', $request->status);
    }

    if ($request->filled('size')) {
        $query->whereHas('sizes', function ($q) use ($request) {
            $q->where('size', $request->size);
        });
    }

    if ($request->filled('stock') && $request->stock === 'in') {
        $query->whereHas('sizes', function ($q) {
            $q->where('total_stock', '>', 0);
        });
    }

    // 🔢 Pagination (custom page & per_page support)
    $perPage = (int) ($request->get('per_page', 10));
    $page = (int) ($request->get('page_number', 1));
    $products = $query->paginate($perPage, ['*'], 'page', $page);

    // 🚫 Conditionally hide fields
    $hideTagline = $request->get('hide_tagline') === '1';
    $hideFeatures = $request->get('hide_features') === '1';

    $products->getCollection()->transform(function ($product) use ($hideTagline, $hideFeatures) {
        // Add full image URLs
        $product->images->transform(function ($image) {
        $image->image_path = asset('storage/' . $image->image_path);
            return $image;
        });
    if ($product->category && $product->category->image) {
        $product->category->image = asset('storage/' . $product->category->image);
    }

        // Compute stock status (aggregate from sizes)
        $totalStock = $product->sizes->sum('total_stock');
        $product->stock_status = $totalStock > 0 ? 'in_stock' : 'out_of_stock';

        if ($hideTagline) {
            unset($product->tagline);
        }

        if ($hideFeatures) {
            unset($product->heart_notes, $product->top_notes, $product->base_notes);
        }

        // Attach image URLs to each review
        if ($product->reviews) {
            $product->reviews->each(function ($review) {
                $review->image_urls = $review->images->map(function ($img) {
                    return $img->image_path ? \Storage::url($img->image_path) : null;
                })->filter()->values();
            });
        }

        // Only include coupons related to this product
        $product->coupons = $product->coupons;

        return $product;
    });

    return response()->json($products);
}


    /**
     * Store a newly created product in storage.
     */
  
   public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'note'        => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'category_id' => 'required|exists:categories,id',
            'brand'       => 'nullable|string',
            'tagline'     => 'nullable|string',
            'heart_notes' => 'nullable|string',
            'top_notes'   => 'nullable|string',
            'base_notes'  => 'nullable|string',
            'features'    => 'nullable|string',
            'gender'      => 'nullable|string',
        ]); 

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $originalSlug = $validated['slug'];
        $counter = 1;
        while (\App\Models\Product::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $counter++;
        }

         if ($request->hasFile('image')) {

            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return response()->json(['message' => 'Product created successfully', 'product' => $product], 201);
    }
    /**
     * Display the specified product.
     */
    public function show($id)
    {
        $product = Product::with(['reviews.user', 'reviews.images', 'coupons'])->find($id);
        if (!$product) {
            return response()->json(['message' => 'Product not found'], 404);
        }
        // Attach image URLs to each review
        if ($product->reviews) {
            $product->reviews->each(function ($review) {
                $review->image_urls = $review->images->map(function ($img) {
                    return $img->image_path ? \Storage::url($img->image_path) : null;
                })->filter()->values();
            });
        }
        // Only include coupons related to this product
        $product->coupons = $product->coupons;
        return response()->json($product);
    }

    /**
     * Update the specified product in storage.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'note'        => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'category_id' => 'required|exists:categories,id',
            'brand'       => 'nullable|string',
            'tagline'     => 'nullable|string',
            'heart_notes' => 'nullable|string',
            'top_notes'   => 'nullable|string',
            'base_notes'  => 'nullable|string',
            'features'    => 'nullable|string',
            'gender'      => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Product deleted successfully']);
    }
}
