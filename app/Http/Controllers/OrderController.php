<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    // Display all orders
    public function index()
    {
        return response()->json(Order::all());
    }

    // Store a new order
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|integer',
            'product_id' => 'required|integer',
            'size_id' => 'nullable|integer',
            'total_amount' => 'required|numeric',
            'discount' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'sub_total' => 'required|numeric',
        ]);

        $order = Order::create($validated);

        return response()->json(['message' => 'Order created successfully', 'order' => $order], 201);
    }

    // Show a single order
    public function show($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    // Update an order
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $validated = $request->validate([
            'user_id' => 'sometimes|integer',
            'product_id' => 'sometimes|integer',
            'size_id' => 'nullable|integer',
            'total_amount' => 'sometimes|numeric',
            'discount' => 'nullable|numeric',
            'gst' => 'nullable|numeric',
            'sub_total' => 'sometimes|numeric',
        ]);

        $order->update($validated);

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    // Delete an order
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->delete();

        return response()->json(['message' => 'Order deleted successfully']);
    }
}
