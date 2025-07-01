<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    // List addresses for a specific user
    public function index(Request $request)
    {
        $userId = $request->query('user_id');

        if (!$userId) {
            return response()->json(['message' => 'user_id is required'], 400);
        }

        $addresses = Address::where('user_id', $userId)->get();

        return response()->json($addresses);
    }

    // Store new address
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'address_one'  => 'nullable|string',
            'address_two'  => 'nullable|string',
            'city'         => 'nullable|string',
            'state'        => 'nullable|string',
            'pincode'      => 'nullable|string',
            'address_type' => 'nullable|string',
        ]);

        $address = Address::create($data);

        return response()->json(['message' => 'Address created', 'address' => $address], 201);
    }

    // Show single address
    public function show($id)
    {
        $address = Address::find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        return response()->json($address);
    }

    // Update address
    public function update(Request $request, $id)
    {
        $address = Address::find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $data = $request->validate([
            'address_one'  => 'nullable|string',
            'address_two'  => 'nullable|string',
            'city'         => 'nullable|string',
            'state'        => 'nullable|string',
            'pincode'      => 'nullable|string',
            'address_type' => 'nullable|string',
        ]);

        $address->update($data);

        return response()->json(['message' => 'Address updated', 'address' => $address]);
    }

    // Delete address
    public function destroy($id)
    {
        $address = Address::find($id);

        if (!$address) {
            return response()->json(['message' => 'Address not found'], 404);
        }

        $address->delete();

        return response()->json(['message' => 'Address deleted']);
    }
}
