<?php

namespace App\Http\Controllers;

use App\Models\OrderProduct;
use Illuminate\Http\Request;

class OrderProductController extends Controller
{
    /**
     * Display a listing of the order products.
     */
    public function index()
    {
        return OrderProduct::all();
    }

    /**
     * Store a newly created order product in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'     => 'required|integer',
            'address_id'  => 'required|integer',
            'product_id'  => 'required|integer',
            'size_id'     => 'required|integer',
            'subtotal'    => 'required|numeric',
            'tax'         => 'required|numeric',
            'total'       => 'required|numeric',
        ]);

        $order = OrderProduct::create($validated);

        return response()->json($order, 201);
    }

    /**
     * Display the specified order product.
     */
    public function show($id)
    {
        return OrderProduct::findOrFail($id);
    }

    /**
     * Update the specified order product in storage.
     */
    public function update(Request $request, $id)
    {
        $order = OrderProduct::findOrFail($id);

        $order->update($request->only([
            'user_id',
            'address_id',
            'product_id',
            'size_id',
            'subtotal',
            'tax',
            'total',
        ]));

        return response()->json($order);
    }

    /**
     * Remove the specified order product from storage.
     */
    public function destroy($id)
    {
        $order = OrderProduct::findOrFail($id);
        $order->delete();

        return response()->json(['message' => 'Order product deleted']);
    }
}
