<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    // List all coupons with related products
    public function index(Request $request)
    {
        $code = $request->get("code");
    
        $coupons = Coupon::with('products')->where('is_active', 1);
    
        if ($code) {
            $coupons = $coupons->where('code', $code);
        }

        $data = $coupons->get();
    
        if ($code && $data->isEmpty()) {
            return response()->json(["message" => "Coupon Code invalid or Expired"], 404);
        }

        $perPage = (int) ($request->get('per_page', 10));
        $page = (int) ($request->get('page_number', 1));
        $coupons = $coupons->paginate($perPage, ['*'], 'page', $page);
    
        return response()->json(["message" => "success", "data" => $coupons]);
    }

    // Store a new coupon
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:coupons',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric',
            'min_purchase' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $coupon = Coupon::create($validated);

        return response()->json($coupon, 201);
    }

    // Show a specific coupon
    public function show($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        return response()->json($coupon, 200);
    }

    // Update a coupon
    public function update(Request $request, $id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $validated = $request->validate([
            'code' => 'string|unique:coupons,code,' . $id,
            'type' => 'in:percentage,fixed',
            'value' => 'numeric',
            'min_purchase' => 'nullable|numeric',
            'max_discount' => 'nullable|numeric',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'is_active' => 'boolean',
            'description' => 'nullable|string',
        ]);

        $coupon->update($validated);

        return response()->json($coupon, 200);
    }

    // Delete a coupon
    public function destroy($id)
    {
        $coupon = Coupon::find($id);
        if (!$coupon) {
            return response()->json(['message' => 'Coupon not found'], 404);
        }

        $coupon->delete();

        return response()->json(['message' => 'Coupon deleted'], 200);
    }

    // Validate coupon code for given products
    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'integer|exists:products,id',
            'total' => 'nullable|numeric',
        ]);

        $coupon = Coupon::where('code', $request->code)->where('is_active', true)->first();
        if (!$coupon) {
            return response()->json(['message' => 'Invalid or inactive coupon code.'], 422);
        }

        $productsInOrder = $request->product_ids ?? [];
        $applicable = true;
        if ($coupon->products()->exists()) {
            $applicable = $coupon->products()->whereIn('products.id', $productsInOrder)->exists();
            if (!$applicable) {
                return response()->json(['message' => 'Coupon does not apply to selected products.'], 422);
            }
        }

        // Calculate discount
        $discount = 0;
        if ($coupon->type === 'percent') {
            $discount = ($request->total * $coupon->value) / 100;
        } else {
            $discount = $coupon->value;
        }

        return response()->json([
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => $coupon->value,
            'discount' => $discount,
            'applicable' => $applicable,
            'products' => $coupon->products()->pluck('products.id'),
        ]);
    }
}
