<?php

namespace App\Http\Controllers;

use App\Models\OrderProducts;
use App\Models\Orders;
use App\Services\GenratePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use App\Services\OtpService;

class OrdersController extends Controller
{
 public function placeOrder(Request $request)
{
    $request->validate([
        'user_id'               => 'required|exists:users,id',
        'address_id'            => 'required|exists:addresses,id',
        'payment_type'          => 'required|in:prepaid,cod',
        'subtotal'              => 'required|numeric',
        'tax'                   => 'required|numeric',
        'total'                 => 'required|numeric',
        'products'              => 'required|array',
        'products.*.product_id' => 'required|exists:products,id',
        'products.*.size_id'    => 'nullable|exists:sizes,id',
        'products.*.quantity'   => 'required|integer|min:1',
        'products.*.price'      => 'required|numeric',
    ]);

    try {
        DB::beginTransaction();

        // Create order
        $order = Orders::create([
            'user_id'            => $request->user_id,
            'address_id'         => $request->address_id,
            'unique_order_id'    => 'ORD' . strtoupper(Str::random(10)),
            'order_status'       => 'confirmed',
            'delivery_status'    => 'processing',
            'payment_status'     => 'pending',
            'payment_type'       => $request->payment_type,
            'subtotal'           => $request->subtotal,
            'tax'                => $request->tax,
            'total'              => $request->total,
        ]);

        // Save order products
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

        // Handle PhonePe payment
        if ($request->payment_type === 'prepaid') {
            $payload = [
                'merchantId'            => env('PHONEPE_MERCHANT_ID'),
                'merchantTransactionId' => $order->unique_order_id,
                'merchantUserId'        => $order->user_id,
                'amount'                => (int) ($order->total * 100), // in paise
                'redirectUrl'           => route('phonepe.response'),
                'redirectMode'          => 'POST',
                'paymentInstrument'     => [
                    'type' => 'PAY_PAGE',
                ],
            ];

            $jsonPayload     = json_encode($payload);
            $base64Payload   = base64_encode($jsonPayload);
            $saltKey         = env('PHONEPE_SALT_KEY');
            $saltIndex       = env('PHONEPE_SALT_INDEX');
            $stringToHash    = $base64Payload . "/pg/v1/pay" . $saltKey;
            $xVerify         = hash('sha256', $stringToHash) . "###" . $saltIndex;
            $baseUrl         = $this->getPhonePeBaseUrl();

            $response = Http::withHeaders([
                'Content-Type'   => 'application/json',
                'X-VERIFY'       => $xVerify,
                'X-MERCHANT-ID'  => env('PHONEPE_MERCHANT_ID'),
            ])->post("$baseUrl/pg/v1/pay", [
                'request' => $base64Payload,
            ]);

            $responseData = $response->json();

            if (!empty($responseData['success']) && $responseData['success'] === true) {
                $paymentUrl = $responseData['data']['instrumentResponse']['redirectInfo']['url'];

                return response()->json([
                    'message'     => 'Order placed. Redirect to PhonePe payment.',
                    'order'       => $order,
                    'payment_url' => $paymentUrl,
                ], 201);
            }

            return response()->json([
                'message' => 'Failed to initiate payment',
                'error'   => $responseData,
            ], 500);
        }

        $orders = Orders::with(['user', 'address', 'products'])->find($order->id);

        // =============== Create Shiprocket order ==============
        $this->createOrder($orders);

        // ============== Generate PDf ====================
        $url = GenratePdf::generateInvoice($orders);

        // ================== Sent Order Success Mail ===============
        Mail::to($orders->user->email)->send(new BookingConfirmationMail($orders));

        // ============== Send Order Success Whatsapp ===============
        // OtpService::sendWhatsAppBookingConfirmation($orders, $url);
        

        // COD fallback
        return response()->json([
            'message' => 'Order placed with Cash on Delivery.',
            'order'   => $order,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();

        return response()->json([
            'message' => 'Order failed',
            'error'   => $e->getMessage(),
        ], 500);
    }
}



public function phonepeResponse(Request $request)
{
    $merchantTransactionId = $request->input('merchantTransactionId'); // your order ID
    $transactionId = $request->input('transactionId'); // PhonePe txn ID

    $order = Orders::where('unique_order_id', $merchantTransactionId)->first();

    if (!$order) {
        return redirect(env('PHONEPE_FAILURE_URL'))->with('error', 'Order not found');
    }

    // Fetch payment status from PhonePe API
    $baseUrl = env('PHONEPE_BASE_URL');
    $saltKey = env('PHONEPE_SALT_KEY');
    $saltIndex = env('PHONEPE_SALT_INDEX');

    // Create checksum for verification as per PhonePe docs
    $checksum = hash('sha256', "/pg/v1/status/$order->unique_order_id/$order->user_id" . $saltKey) . "###" . $saltIndex;

    $response = Http::withHeaders([
        'X-VERIFY' => $checksum,
        'X-MERCHANT-ID' => env('PHONEPE_MERCHANT_ID'),
    ])->get("$baseUrl/pg/v1/status/$order->unique_order_id/$order->user_id");

    $statusResponse = $response->json();

    if (isset($statusResponse['code']) && $statusResponse['code'] === 'PAYMENT_SUCCESS') {
        $order->update([
            'payment_status' => 'success',
            'payment_response_id' => $transactionId,
            'order_status' => 'confirmed',
            'delivery_status' => 'processing',
        ]);
        return redirect(env('PHONEPE_REDIRECT_URL'))->with('message', 'Payment successful');
    }

    $order->update([
        'payment_status' => 'failed',
        'payment_response_id' => $transactionId,
        'order_status' => 'canceled',
        'delivery_status' => 'pending',
    ]);

    return redirect(env('PHONEPE_FAILURE_URL'))->with('error', 'Payment failed');
}


public function index(Request $request)
    {
        $query = Orders::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('order_status')) {
            $query->where('order_status', $request->order_status);
        }

        if ($request->has('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->has('delivery_status')) {
            $query->where('delivery_status', $request->delivery_status);
        }

        $perPage = $request->input('per_page', 10); // Default 10
        $page = $request->input('page_number', 1);  // Default 1

        // Eager load relations if needed
        $orders = $query->with(['user', 'address', 'products'])
                        ->orderBy('created_at', 'desc')
                        ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($orders);
    }

    // 🟡 SHOW: Get single order
    public function show($id)
    {
        $order = Orders::with(['user', 'address', 'products'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    // 🟠 UPDATE: Update order status
    public function update(Request $request, $id)
    {
        $order = Orders::find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $request->validate([
            'order_status'    => 'nullable|string|in:confirmed,canceled,returned',
            'payment_status'  => 'nullable|string|in:pending,success,failed',
            'delivery_status' => 'nullable|string|in:processing,packed,shipped,delivered',
            'payment_response_id' => 'nullable|string',
        ]);

        // Update only if present in request
        $order->order_status       = $request->order_status ?? $order->order_status;
        $order->payment_status     = $request->payment_status ?? $order->payment_status;
        $order->delivery_status    = $request->delivery_status ?? $order->delivery_status;
        $order->payment_response_id = $request->payment_response_id ?? $order->payment_response_id;

        $order->save();

        return response()->json(['message' => 'Order updated successfully', 'order' => $order]);
    }

    public function getPhonePeBaseUrl()
    {
        return env('PHONEPE_ENV') === 'live'
            ? env('PHONEPE_LIVE_URL')
            : env('PHONEPE_UAT_URL');
    }

// ================== Shiprocket Token Genrate ============
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

        return [
            "name" => $item->product->name ?? 'Product',
            "sku" => $item->product->id ?? 'SKU',
            "units" => $item->quantity,
            "selling_price" => $item->price,
            "length" => (float) ($size->length ?? 10),
            "breadth" => (float) ($size->width ?? 10),
            "height" => (float) ($size->height ?? 10),
            "weight" => (float) ($size->weight ?? 1),
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
    "payment_method" => "COD",
    "sub_total" => $order->subtotal,

    // Provide valid numeric values for order dimensions (use max or sum of items or fixed defaults)
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


}
