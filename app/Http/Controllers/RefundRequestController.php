<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\RefundRequest;
use App\Services\PhonePePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RefundRequestController extends Controller
{
    // List refund requests with pagination and optional order_id filter
    public function index(Request $request)
    {
        $query = RefundRequest::query();
    
        $query->with(["images"]);
    
        if ($request->has('order_id')) {
            $query->where('order_id', $request->order_id);
        }
    
        $perPage =  (int) $request->input('per_page', 10);
        $page = (int) ($request->get('page_number', 1));
        $refundRequests = $query->with('images')->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);
    
        foreach ($refundRequests as $refund) {
                $refund->images->map(function ($img) {
                $img->image_path =  $img->image_path ? asset('storage/' . $img->image_path) : null;
                return ;
            })->filter()->values();
        }
    
        return response()->json($refundRequests);
    }

    // Store a new refund request
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,unique_order_id',
            'comment' => 'nullable|string',
            'reason' => 'nullable|string',
            'type' => 'nullable|string',
            'status' => 'nullable|in:processing,rejected,approved',
            'email' => 'nullable|email',
            'images' => 'nullable|array',
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        
        $orders = Order::where('unique_order_id', $request->order_id)->get();

        if (!$orders) {
            Log::error('PhonePe Callback: Order not found', ['Order Id' => $request->order_id]);
            return response()->json(['error' => 'Order not found'], 404);
        }

        $refundRequest = RefundRequest::create($validated);

        // Handle image uploads if present
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $imageFile) {
                $imagePath = $imageFile->store('refund_request_images', 'public');
                $refundRequest->images()->create([
                    'image_path' => $imagePath,
                ]);
            }
        }

        return response()->json(['message' => 'Refund request created', 'data' => $refundRequest->load('images')], 201);
    }

    // Show a single refund request
    public function show($id)
    {
        $refundRequest = RefundRequest::with('images')->find($id);
        if (!$refundRequest) {
            return response()->json(['message' => 'Refund request not found'], 404);
        }
        return response()->json($refundRequest);
    }

    // Update a refund request
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'nullable|in:processing,rejected,approved',
        ]);

        
        $refundRequest = RefundRequest::find($id);
        if (!$refundRequest) {
            return response()->json(['message' => 'Refund request not found'], 404);
        }

       
        $refundRequest->update($validated);

        return response()->json(['message' => 'Refund request updated', 'data' => $refundRequest]);
    }

    // Soft delete a refund request
    public function destroy($id)
    {
        $refundRequest = RefundRequest::find($id);
        if (!$refundRequest) {
            return response()->json(['message' => 'Refund request not found'], 404);
        }
        $refundRequest->delete();
        return response()->json(['message' => 'Refund request deleted']);
    }

    public function phonePeRfundRequestCreate(Request $request){

        $phonePePayment = new PhonePePayment();
        $response =  $phonePePayment->phonePeRefund($request);

        return $response;
    }

    public function phonePeStatus(Request $request){

        $phonePePayment = new PhonePePayment();
        $response =  $phonePePayment->phonepeStatusCheck($request);

        return $response;
    }
} 