<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderProducts;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrdersController extends Controller
{
    public function placeOrder(Request $request)
{
    $request->validate([
        'user_id'     => 'required|exists:users,id',
        'address_id'  => 'required|exists:addresses,id',
        'payment_type'=> 'required|in:prepaid,cod',
        'subtotal'    => 'required|numeric',
        'tax'         => 'required|numeric',
        'total'       => 'required|numeric',
        'products'    => 'required|array',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.size_id'    => 'nullable|exists:sizes,id',
        'products.*.quantity'   => 'required|integer|min:1',
        'products.*.price'      => 'required|numeric',
    ]);

    try {
         DB::beginTransaction();
      

        // Step 2: Create order
        $order = Order::create([
            'user_id'            => $request->user_id,
            'address_id'         => $request->address_id,
            'unique_order_id'    => "ORD".Str::upper(Str::random(10)),
            'order_status'       => 'confirmed',
            'delivery_status'    => 'processing',  // could be: processing, packed, shipped
            'payment_status'     => 'pending',
            'payment_type'       => $request->payment_type,
            'subtotal'           => $request->subtotal,
            'tax'                => $request->tax,
            'total'              => $request->total,
        ]);

        // Step 3: Insert products into order_products
        foreach ($request->products as $item) {
            OrderProducts::create([
                'order_id'   => $order->id,
                'product_id' => $item['product_id'],
                'size_id'    => $item['size_id'] ?? null,
                'quantity'   => $item['quantity'],
                'price'      => $item['price'],
            ]);
        }

        DB::commit();

        // Step 4: Handle payment gateway redirection (only if prepaid)
        if ($request->payment_type === 'prepaid') {
            $phonePeRedirectUrl = route('phonepe.payment', ['order_id' => $order->id]);

            return response()->json([   
                'message' => 'Order placed. Redirect to PhonePe for payment.',
                'order'   => $order,
                'redirect_url' => $phonePeRedirectUrl
            ], 201);
        }

        // COD response
        return response()->json([
            'message' => 'Order placed with COD.',
            'order'   => $order
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Order failed', 'error' => $e->getMessage()], 500);
    }
}

}
