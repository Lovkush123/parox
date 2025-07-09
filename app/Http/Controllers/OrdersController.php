<?php

namespace App\Http\Controllers;

use App\Models\OrderProducts;
use App\Models\Orders;
use App\Services\GenratePdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmationMail;
use App\Services\OtpService;
use App\Services\PhonePePayment;
use App\Services\ShipRocket;
use Illuminate\Support\Facades\Log;
use App\Models\Coupon;

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
        'coupon_id'             => 'nullable|exists:coupons,id',
        'discount'              => 'nullable|numeric',
    ]);

    $discount = $request->discount ?? 0;
    $coupon_id = $request->coupon_id ?? null;

    // Optionally, validate coupon_id and products here for extra safety
    if ($coupon_id) {
        $coupon = Coupon::find($coupon_id);
        if (!$coupon || !$coupon->is_active) {
            return response()->json(['message' => 'Invalid or inactive coupon.'], 422);
        }
        // If coupon is product-specific, check if it applies to any product in the order
        if ($coupon->products()->exists()) {
            $productsInOrder = collect($request->products)->pluck('product_id')->toArray();
            $applicable = $coupon->products()->whereIn('products.id', $productsInOrder)->exists();
            if (!$applicable) {
                return response()->json(['message' => 'Coupon does not apply to selected products.'], 422);
            }
        }
    }

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
            'total'              => $request->total - $discount,
            'coupon_id'          => $coupon_id,
            'discount_amount'    => $request->discount_amount,
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
        $orders = Orders::with(['user', 'address', 'products'])->find($order->id);

        // Handle PhonePe payment
        if ($request->payment_type === 'prepaid') {
            // =============== Initiate PhonePe Payment ==============
            $phonePePayment = new PhonePePayment();
            $response =  $phonePePayment->initiatePhonePePayment($orders);

           return $response;
        }


        // =============== Create Shiprocket order ==============
        $shipRocket = new ShipRocket();
        $shipRocket->createOrder($orders);

        // ============== Generate PDf ====================
        $url = GenratePdf::generateInvoice($orders);

        // ================== Sent Order Success Mail ===============
        Mail::to($orders->user->email)->send(new BookingConfirmationMail($orders));

        // ============== Send Order Success Whatsapp ===============
        OtpService::sendWhatsAppBookingConfirmation($orders, $url);
        

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

// ================== Get All Orders ======================

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

    $orders->getCollection()->transform(function ($order) {
        foreach ($order->products as $productItem) {
            $product = $productItem->product;

            // Generate image URLs from image_path
            if ($product && !empty($product->images)) {
                $product->image_urls = collect($product->images)->map(function ($img) {
                    return asset('storage/' . $img['image_path']);
                })->toArray();
            } else {
                $product->image_urls = [];
            }
        }

        return $order;
    });

        return response()->json($orders);
    }

    // ================== Get single order ======================
    public function show($id)
    {
        $order = Orders::with(['user', 'address', 'products'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    // ================== Update order status ======================
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

    public function phonepeResponse(Request $request)
    {
        
        $phonePePayment = new PhonePePayment();
        return $phonePePayment->phonepeResponse($request);
    }


    public function shipRocketOrderCreate(Request $request)
    {
        $orderId = $request->input("order_id");


        $order = Orders::where("unique_order_id", $orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        
        $phonePePayment = new ShipRocket();
        return $phonePePayment->createOrder($order);
    }










}
