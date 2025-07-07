<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Models\ProductReviewImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductReviewController extends Controller
{
    // List all reviews for a product
    public function index(Request $request, $productId)
    {
        $query = ProductReview::where('product_id', $productId)->with(['user', 'images']);
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        $perPage = $request->input('per_page', 10);
        $page = $request->input('page', 1);
        $reviews = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
        // Attach image URLs
        $reviews->getCollection()->transform(function ($review) {
            $review->image_urls = $review->images->map(function ($img) {
                return $img->image_path ? url(Storage::url($img->image_path)) : null;
            })->filter()->values();
            return $review;
        });
        return response()->json($reviews);
    }

    // Store a new review
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'user_id'    => 'required|exists:users,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'nullable|string',
            'images.*'   => 'nullable|image|max:2048',
        ]);

        $review = ProductReview::create($request->only(['product_id', 'user_id', 'rating', 'comment']));

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('review_images', 'public');
                ProductReviewImage::create([
                    'product_review_id' => $review->id,
                    'image_path' => $path,
                ]);
            }
        }

        $review->load(['user', 'images']);
        $review->image_urls = $review->images->map(function ($img) {
            return $img->image_path ? url(Storage::url($img->image_path)) : null;
        })->filter()->values();

        return response()->json($review, 201);
    }

    // Show a single review
    public function show($id)
    {
        $review = ProductReview::with(['user', 'images'])->find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }
        $review->image_urls = $review->images->map(function ($img) {
            return $img->image_path ? url(Storage::url($img->image_path)) : null;
        })->filter()->values();
        return response()->json($review);
    }

    // Update a review
    public function update(Request $request, $id)
    {
        $review = ProductReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }
        $request->validate([
            'rating'  => 'nullable|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'images.*'   => 'nullable|image|max:2048',
        ]);
        $review->update($request->only(['rating', 'comment']));

        // Handle new image uploads (append to existing)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('review_images', 'public');
                ProductReviewImage::create([
                    'product_review_id' => $review->id,
                    'image_path' => $path,
                ]);
            }
        }

        $review->load(['user', 'images']);
        $review->image_urls = $review->images->map(function ($img) {
            return $img->image_path ? url(Storage::url($img->image_path)) : null;
        })->filter()->values();

        return response()->json($review);
    }

    // Delete a review
    public function destroy($id)
    {
        $review = ProductReview::find($id);
        if (!$review) {
            return response()->json(['message' => 'Review not found'], 404);
        }
        $review->delete();
        return response()->json(['message' => 'Review deleted successfully']);
    }
} 