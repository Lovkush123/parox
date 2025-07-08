<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShipRocket
{

    // ================== Create Shiprocket Order ======================

public function createOrder($order)
{
    $token = $this->getShiprocketToken();

    if (!$token) {
        \Log::error('Shiprocket Authentication Failed');
        return ['success' => false, 'message' => 'Failed to authenticate with Shiprocket'];
    }

        $items = $order->products->map(function ($item) {

        $size = $item->size; // assuming `size` relation is eager loaded
        $name = $item->product->name ?? 'Product';
        $id = $item->product->id ?? rand(100, 999);

        // Step 1: Get initials
        $words = explode(' ', $name);
        $initials = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $initials .= strtoupper($word[0]);
            }
        }

        // Step 2: Combine initials with ID
        $sku = $initials . $id;


        return [
            "name" => $item->product->name ?? 'Product',
            "sku" => $sku ?? 'SKU1234',
            "units" => $item->quantity,
            "selling_price" => $item->price,
            "length" => (float) ($size->length ?? 10),
            "breadth" => (float) ($size->width ?? 10),
            "height" => (float) ($size->height ?? 10),
            "weight" => (float) ($size->weight ?? 1),
            "discount"=> $size->mrp - $item->price,
            // New fields
            "hsn_code" => '3303',
            "gst_rate" => 18,
            "tax_included" => 'Yes',
        ];
    })->toArray();

    $billingNameParts = explode(' ', $order->user->name ?? 'Customer');
    $billingFirstName = $billingNameParts[0] ?? 'Customer';
    $billingLastName = end($billingNameParts) ?: 'NA';

    $payload = [
        "order_id" => $order->unique_order_id,
        "order_date" => now()->format('Y-m-d'),
        "pickup_location" => "warehouse",
        "billing_customer_name" => $billingFirstName,
        "billing_last_name" => $billingLastName,        // Added
        "billing_address" => $order->address->address_one ?? 'N/A',
        "billing_address_2" => $order->address->address_two ?? '',
        "billing_city" => $order->address->city,
        "billing_pincode" => $order->address->pincode,
        "billing_state" => $order->address->state,
        "billing_country" => "India",
        "billing_email" => $order->user->email ?? 'no@email.com',
        "billing_phone" => $order->user->number ?? '0000000000',
        "shipping_is_billing" => true,
        "order_items" => $items,
        "payment_method" => $order->payment_type,
        "sub_total" => $order->subtotal,
        "tax"=> $order->tax,
        "total"=> $order->total,
        "discount"=> $order->discount_amount,

        "length" => max(array_column($items, 'length')) ?: 10,
        "breadth" => max(array_column($items, 'breadth')) ?: 10,
        "height" => max(array_column($items, 'height')) ?: 10,
        "weight" => array_sum(array_column($items, 'weight')) ?: 1,
    ];


    try {
        \Log::info('Creating Shiprocket order', ['payload' => $payload]);

        $res = Http::withToken($token)
            ->post('https://apiv2.shiprocket.in/v1/external/orders/create/adhoc', $payload);

        if ($res->successful()) {
            $order->update([
                'delivery_status' => "shippment created",
            ]);

            \Log::info('Shiprocket order created successfully', ['response' => $res->json()]);
            return ['success' => true, 'data' => $res->json()];
        } else {
            \Log::error('Shiprocket order creation failed', ['response' => $res->body()]);
            return [
                'success' => false,
                'message' => 'Shiprocket order creation failed',
                'error' => $res->json()
            ];
        }
    } catch (\Exception $e) {
        \Log::error('Shiprocket order creation exception', ['error' => $e->getMessage()]);
        return [
            'success' => false,
            'message' => 'Exception while creating Shiprocket order',
            'error' => $e->getMessage(),
        ];
    }
}

// ================== Get Shiprocket Token ======================

public function getShiprocketToken()
{
    try {
        $response = Http::post('https://apiv2.shiprocket.in/v1/external/auth/login', [
            'email'    => env('SHIPROCKET_EMAIL'),    // Use env keys, not hardcoded
            'password' => env('SHIPROCKET_PASSWORD'),
        ]);

        if ($response->successful()) {
            $token = $response->json()['token'] ?? null;
            \Log::info('Shiprocket Token fetched', ['token' => $token]);
            return $token;
        } else {
            \Log::error('Shiprocket Token fetch failed', ['response' => $response->body()]);
            return null;
        }
    } catch (\Exception $e) {
        \Log::error('Shiprocket Token Exception', ['error' => $e->getMessage()]);
        return null;
    }
}
   
}