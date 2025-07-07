<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponProductController extends Controller
{
    // List all coupon-product associations
    public function index()
    {
        $rows = DB::table('coupon_product')->get();
        return response()->json($rows);
    }

    // Show a single association
    public function show($id)
    {
        $row = DB::table('coupon_product')->where('id', $id)->first();
        if (!$row) {
            return response()->json(['message' => 'Not found'], 404);
        }
        return response()->json($row);
    }

    // Create a new association
    public function store(Request $request)
    {
        $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
            'product_id' => 'required|exists:products,id',
        ]);
        $id = DB::table('coupon_product')->insertGetId([
            'coupon_id' => $request->coupon_id,
            'product_id' => $request->product_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $row = DB::table('coupon_product')->where('id', $id)->first();
        return response()->json($row, 201);
    }

    // Update an association
    public function update(Request $request, $id)
    {
        $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
            'product_id' => 'required|exists:products,id',
        ]);
        $updated = DB::table('coupon_product')->where('id', $id)->update([
            'coupon_id' => $request->coupon_id,
            'product_id' => $request->product_id,
            'updated_at' => now(),
        ]);
        if (!$updated) {
            return response()->json(['message' => 'Not found or not updated'], 404);
        }
        $row = DB::table('coupon_product')->where('id', $id)->first();
        return response()->json($row);
    }

    // Delete an association
    public function destroy($id)
    {
        $deleted = DB::table('coupon_product')->where('id', $id)->delete();
        if (!$deleted) {
            return response()->json(['message' => 'Not found or not deleted'], 404);
        }
        return response()->json(['message' => 'Deleted successfully']);
    }
} 