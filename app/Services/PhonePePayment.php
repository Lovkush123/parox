<?php

namespace App\Services;

use App\Models\Orders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PhonePePayment
{
    public function initiatePhonePePayment($order)
    {
        $accessToken = $this->getPhonePeAccessToken();
    
        if (!$accessToken) {
            return response()->json(['error' => 'Failed to get PhonePe access token'], 500);
        }
    
        $payload = [
            "merchantId" => env('PHONEPE_MERCHANT_ID'),
            "merchantTransactionId" => $order->unique_order_id,
            "merchantUserId" => $order->user_id,
            "amount" => (int) ($order->total * 100),
            "redirectUrl" => route('phonepe.response'),
            "redirectMode" => "POST",
            "callbackUrl" => route('phonepe.response'),
            "paymentInstrument" => [
                "type" => "PAY_PAGE"
            ]
        ];
    
        $baseUrl = $this->getPhonePeBaseUrl();

        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $accessToken
        ])->post($baseUrl . '/checkout/v2/pay', $payload);

    
        if ($response->successful()) {
    
            $order->update([
                'payment_status' => 'processing',
                'payment_response_id' => $response->json()['orderId'],
            ]);
    
            return response()->json([
                'message' => 'Redirect to PhonePe payment page',
                'data' => $response->json(),
            ]);
        }
    
        Log::error('PhonePe Payment Initiation Error:', $response->json());
        return response()->json(['error' => 'Failed to initiate payment'], 500);
    }

  // ================== PhonePe Response ======================

public function phonepeResponse($request)
{

    $merchantTransactionId = $request['merchantOrderId']; // your order ID

    // Use unique_order_id for lookup (as you control this field)
    $order = Orders::where('unique_order_id', $merchantTransactionId)->first();

    if (!$order) {
        Log::error('PhonePe Response: Order not found', ['merchantTransactionId' => $merchantTransactionId]);
        return redirect(env('PHONEPE_FAILURE_URL'))->with('error', 'Order not found');
    }

    // Get access token for PhonePe API
    $accessToken = $this->getPhonePeAccessToken();
    if (!$accessToken) {
        Log::error('PhonePe Status: Failed to get access token');
        return redirect(env('PHONEPE_FAILURE_URL'))->with('error', 'Payment verification failed');
    }

    // Fetch payment status from PhonePe API
    $baseUrl = $this->getPhonePeBaseUrl();

    // Create checksum for verification as per PhonePe docs
    $apiPath = $baseUrl."checkout/v2/order/{$order->unique_order_id}/status";

    try {
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => 'O-Bearer ' . $accessToken
        ])->get($apiPath);

        $statusResponse = $response->json();
        Log::info('PhonePe Status Response', ['response' => $statusResponse, 'http_status' => $response->status()]);
    } catch (\Exception $e) {
        Log::error('PhonePe Status API Exception', ['error' => $e->getMessage()]);
        return redirect(env('PHONEPE_FAILURE_URL'))->with('error', 'Payment verification failed');
    }

    if (isset($statusResponse['code']) && $statusResponse['code'] === 'PAYMENT_SUCCESS') {
        $order->update([
            'payment_status' => 'success',
        ]);

        return redirect(env('PHONEPE_REDIRECT_URL'))->with('message', 'Payment successful');
    }

}

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