<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImageController extends Controller
{
    // Fetch all images
    public function index()
    {
        return response()->json(Image::all(), 200);
    }

    // Store a new image with file upload
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'product_id' => 'nullable|exists:products,id',
            'alt_text' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
        } else {
            return response()->json(['error' => 'Image file not found'], 400);
        }

        $image = Image::create([
            'image_path' => $imagePath,
            'product_id' => $request->product_id,
            'alt_text' => $request->alt_text,
        ]);

        return response()->json($image, 201);
    }

    // Show a specific image
    public function show($id)
    {
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        return response()->json($image, 200);
    }

    // Update an existing image
    public function update(Request $request, $id)
    {
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        $request->validate([
            'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'product_id' => 'nullable|exists:products,id',
            'alt_text' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            // Delete old image
            if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
                Storage::disk('public')->delete($image->image_path);
            }

            $imagePath = $request->file('image')->store('images', 'public');
            $image->image_path = $imagePath;
        }

        $image->product_id = $request->product_id ?? $image->product_id;
        $image->alt_text = $request->alt_text ?? $image->alt_text;
        $image->save();

        return response()->json($image, 200);
    }

    // Delete an image
    public function destroy($id)
    {
        $image = Image::find($id);
        if (!$image) {
            return response()->json(['error' => 'Image not found'], 404);
        }

        // Delete file from storage
        if ($image->image_path && Storage::disk('public')->exists($image->image_path)) {
            Storage::disk('public')->delete($image->image_path);
        }

        $image->delete();

        return response()->json(['message' => 'Image deleted successfully'], 200);
    }
}
