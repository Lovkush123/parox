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
        'images' => 'required|array',
        'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        'product_id' => 'nullable|exists:products,id',
        'alt_texts' => 'nullable|array',
        'alt_texts.*' => 'nullable|string',
    ]);

    $storedImages = [];

    foreach ($request->file('images') as $index => $imageFile) {
        $imagePath = $imageFile->store('images', 'public');

        $image = Image::create([
            'image_path' => $imagePath,
            'product_id' => $request->product_id,
            'alt_text' => $request->alt_texts[$index] ?? null,
        ]);

        $storedImages[] = $image;
    }

    return response()->json($storedImages, 201);
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
            'product_id' => 'required|exists:products,id',
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
