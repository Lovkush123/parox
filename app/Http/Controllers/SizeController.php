<?php

namespace App\Http\Controllers;

use App\Models\Size;
use Illuminate\Http\Request;

class SizeController extends Controller
{
    // Get all sizes
    public function index()
    {
        return response()->json(Size::all(), 200);
    }

    // Create new size
    public function store(Request $request)
    {
        $request->validate([
            'size' => 'required|string',
            'price' => 'required|numeric',
            'product_id' => 'required|integer',
        ]);

        $size = Size::create($request->all());

        return response()->json(['message' => 'Size created successfully', 'data' => $size], 201);
    }

    // Show specific size
    public function show($id)
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json(['message' => 'Size not found'], 404);
        }

        return response()->json($size, 200);
    }

    // Update size
    public function update(Request $request, $id)
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json(['message' => 'Size not found'], 404);
        }

        $size->update($request->all());

        return response()->json(['message' => 'Size updated successfully', 'data' => $size], 200);
    }

    // Delete size
    public function destroy($id)
    {
        $size = Size::find($id);

        if (!$size) {
            return response()->json(['message' => 'Size not found'], 404);
        }

        $size->delete();

        return response()->json(['message' => 'Size deleted successfully'], 200);
    }
}
