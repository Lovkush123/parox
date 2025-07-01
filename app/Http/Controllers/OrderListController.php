<?php

namespace App\Http\Controllers;

use App\Models\OrderList;
use Illuminate\Http\Request;

class OrderListController extends Controller
{
    /**
     * Display a listing of order lists.
     */
    public function index()
    {
        return OrderList::all();
    }

    /**
     * Store a newly created order list.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_product_id' => 'required|integer',
            'order_id'         => 'required|integer',
            'subtotal'         => 'required|numeric',
            'tax'              => 'required|numeric',
            'total'            => 'required|numeric',
            'response_id'      => 'nullable|string',
            'order_status'     => 'nullable|string',
            'tracking_id'      => 'nullable|string',
            'payment_type'     => 'nullable|string',
        ]);

        $orderList = OrderList::create($validated);

        return response()->json($orderList, 201);
    }

    /**
     * Display the specified order list.
     */
    public function show($id)
    {
        return OrderList::findOrFail($id);
    }

    /**
     * Update the specified order list.
     */
    public function update(Request $request, $id)
    {
        $orderList = OrderList::findOrFail($id);

        $orderList->update($request->only([
            'order_product_id',
            'order_id',
            'subtotal',
            'tax',
            'total',
            'response_id',
            'order_status',
            'tracking_id',
            'payment_type',
        ]));

        return response()->json($orderList);
    }

    /**
     * Remove the specified order list.
     */
    public function destroy($id)
    {
        $orderList = OrderList::findOrFail($id);
        $orderList->delete();

        return response()->json(['message' => 'Order list entry deleted']);
    }
}
