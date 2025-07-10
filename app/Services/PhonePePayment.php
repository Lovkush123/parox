<?php

namespace App\Services;

use App\Mail\BookingConfirmationMail;
use App\Models\Order;
use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PhonePePayment
{
    public function initiatePhonePePayment($order)
    {
        $accessToken = $this->getPhonePeAccessToken();
        if (!$accessToken) {
            return response()->json(['error' => 'Failed to get PhonePe access token'], 500);
        }
    
        $payload = [
            "merchantOrderId" => $order->unique_order_id,
            "amount" => (int) ($order->total * 100),
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Payment for order #" . $order->unique_order_id,
                "merchantUrls" => [
                    "redirectUrl" => "https://66c50ba9ea02.ngrok-free.app/api/phonepe/result"
                ]
            ]
        ];
    
        $baseUrl = $this->getPhonePeBaseUrl();

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $accessToken
        ])->post($baseUrl . '/checkout/v2/pay', $payload);
    
        if ($response->successful() && isset($response->json()['redirectUrl'])) {
            $order->update([
                'payment_status' => 'processing',
                'payment_response_id' => $response->json()['orderId'] ?? null,
            ]);
            $responseData =  $response->json();
            $responseData["merchantOrderId"] = $order->unique_order_id;
            return $responseData;
        }
    
        Log::error('PhonePe Payment Initiation Error:', $response->json());
        return response()->json(['error' => 'Failed to initiate payment'], 500);
    }

  // ================== PhonePe Response ======================

public function phonepeResponse($request)
{
    $merchantOrderId = $request->input('merchantOrderId');
    $orderId = $request->input('orderId');

    $order =  Orders::with(['user', 'address', 'products'])->where('unique_order_id', $merchantOrderId)->first();

    if (!$order) {
        Log::error('PhonePe Callback: Order not found', ['merchantOrderId' => $merchantOrderId]);
        return response()->json(['error' => 'Order not found'], 404);
    }

    // Always verify status with PhonePe API
    $accessToken = $this->getPhonePeAccessToken();
    if (!$accessToken) {
        Log::error('PhonePe Callback: Failed to get access token');
        return response()->json(['error' => 'Payment verification failed'], 500);
    }

    $baseUrl = $this->getPhonePeBaseUrl();
    $statusUrl = $baseUrl . "/checkout/v2/order/{$merchantOrderId}/status";

    $statusResponse = Http::withHeaders([
        'Content-Type'  => 'application/json',
        'Authorization' => 'O-Bearer ' . $accessToken
    ])->get($statusUrl);

    $statusData = $statusResponse->json();

    if (isset($statusData['state']) && $statusData['state'] === 'COMPLETED') {
        $order->update(['payment_status' => 'success']);
        $order->save();

        // =============== Create Shiprocket order ==============
        $shipRocket = new ShipRocket();
        $shipRocket->createOrder($order);

        // ============== Generate PDf ====================
        $url = GenratePdf::generateInvoice($order);

        // ================== Sent Order Success Mail ===============
        Mail::to($order->user->email)->send(new BookingConfirmationMail($order));

        // ============== Send Order Success Whatsapp ===============
        // OtpService::sendWhatsAppBookingConfirmation($order, $url);
        
    } else {
        $order->update(['payment_status' => 'failed']);
    }

    return response()->json(['message' => "Payment Status updated and store successfully", "data"=>$statusData]);
}

// ==================== Refund Request ===================

public function phonePeRefund($request)
{
    $orders = Order::where('unique_order_id', $request->order_id)->get();

    if (!$orders) {
        Log::error('PhonePe Callback: Order not found', ['Order Id' => $request->order_id]);
        return response()->json(['error' => 'Order not found'], 404);
    }
    
    $accessToken = $this->getPhonePeAccessToken();

    if (!$accessToken) {
        return response()->json(['error' => 'Failed to get PhonePe access token'], 500);
    }

    $baseUrl = $this->getPhonePeBaseUrl();
    $endpoint = $baseUrl . '/payments/v2/refund';
    $merchantRefundId = 'refund' . now()->format('YmdHis') . strtoupper(\Str::random(5));

    $payload = [
        'merchantRefundId' => $merchantRefundId,
        'originalMerchantOrderId' => $request->order_id,
        'amount' => (int) $request->amount,
    ];

    $response = \Http::withHeaders([
        'Content-Type' => 'application/json',
        'Authorization' => 'O-Bearer ' . $accessToken,
    ])->post($endpoint, $payload);

    return response()->json(["message"=>"Refund Request Proceed", "data"=>$response->json()]);
}

// ====================== Phone Pe status Check ===============

public function phonepeStatusCheck($request)
{
    $orderId = $request->input('order_id');

    // Always verify status with PhonePe API
    $accessToken = $this->getPhonePeAccessToken();

    if (!$accessToken) {
        Log::error('PhonePe Callback: Failed to get access token');
        return response()->json(['error' => 'Payment verification failed'], 500);
    }

    $baseUrl = $this->getPhonePeBaseUrl();
    $statusUrl = $baseUrl . "/payments/v2/refund/{$orderId}/status";

    $statusResponse = Http::withHeaders([
        'Content-Type'  => 'application/json',
        'Authorization' => 'O-Bearer ' . $accessToken
    ])->get($statusUrl);

    $statusData = $statusResponse->json();


    return response()->json(['message' => "Phone Pe Refund Status", "data"=>$statusData]);
}


// ================== Phone Pe Access Token ===============

public function getPhonePeAccessToken()
{
    $baseUrl = $this->getPhonePeBaseUrl();
    $response = Http::asForm()->post($baseUrl.'/v1/oauth/token', [
        'client_id'     => env('PHONEPE_CLIENT_ID'),
        'client_secret' => env('PHONEPE_CLIENT_SECRET'),
        'client_version'=> '1',
        'grant_type'    => 'client_credentials',
    ]);
    if ($response->successful()) {
        return $response->json()['access_token'];
    }

    \Log::error('PhonePe Token Error:', $response->json());
    return null;
}

// ================== PhonePe Base URL ======================

public function getPhonePeBaseUrl()
{
    return env('PHONEPE_ENV') === 'live'
        ? env('PHONEPE_LIVE_URL')
        : env('PHONEPE_UAT_URL');
}

}