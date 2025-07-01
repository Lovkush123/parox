<?php
namespace App\Http\Controllers;

use App\Models\ProductMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductMediaController extends Controller
{
    /**
     * Store a new image or video.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,mp4,mov,avi|max:20480', // Max 20MB
            'product_id' => 'required|integer',
            'size_id' => 'required|integer',
        ]);

        $file = $request->file('image');
        $path = $file->store('media_uploads', 'public');

        $media = ProductMedia::create([
            'product_id' => $request->product_id,
            'size_id' => $request->size_id,
            'file_path' => $path,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Media uploaded successfully.',
            'data' => $media,
            'file_url' => asset('storage/' . $path),
        ]);
    }

    /**
     * Optional: Fetch all media entries.
     */
    public function index()
    {
        return response()->json(ProductMedia::all());
    }

    /**
     * Optional: Delete a media entry.
     */
    public function destroy($id)
    {
        $media = ProductMedia::find($id);

        if (!$media) {
            return response()->json(['status' => 'error', 'message' => 'Media not found'], 404);
        }

        // Delete file from storage
        Storage::disk('public')->delete($media->file_path);

        // Delete record from DB
        $media->delete();

        return response()->json(['status' => 'success', 'message' => 'Media deleted successfully']);
    }
}
