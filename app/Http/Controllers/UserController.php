<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\SendOtpMail;

class UserController extends Controller
{
    /**
     * ✅ Login or Register with OTP (no expectsJson check)
     */
    public function loginOrRegisterWithOTP(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $otp = rand(100000, 999999);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $user->otp = $otp;
            $user->save();
        } else {
            $user = User::create([
                'email' => $request->email,
                'otp' => $otp,
                'username' => null,
                'name' => null,
                'password' => null,
            ]);
        }

        try {
            Mail::to($request->email)->send(new SendOtpMail($otp));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send OTP',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'OTP sent to your email',
            'email' => $request->email
        ], 200);
    }

    /**
     * ✅ Verify the OTP (no expectsJson check)
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->otp === $request->otp) {
            $user->otp = null;
            $user->save();

            return response()->json([
                'message' => 'OTP verified successfully',
                'user' => $user
            ], 200);
        }

        return response()->json(['message' => 'Invalid OTP'], 401);
    }

    /**
     * ✅ Get all users
     */
    public function index()
    {
        $users = User::all()->map(function ($user) {
            $user->profile_url = $user->profile ? asset('storage/' . $user->profile) : null;
            return $user;
        });

        return response()->json($users);
    }

    /**
     * ✅ Create new user (non-OTP)
     */
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username',
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'number' => 'nullable',
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'password' => 'required|min:6',
        ]);

        $profilePath = null;

        if ($request->hasFile('profile')) {
            $profilePath = $request->file('profile')->store('profiles', 'public');
        }

        $user = User::create([
            'username' => $request->username,
            'name' => $request->name,
            'email' => $request->email,
            'number' => $request->number,
            'profile' => $profilePath,
            'password' => Hash::make($request->password),
        ]);

        $user->profile_url = $profilePath ? asset('storage/' . $profilePath) : null;

        return response()->json($user, 201);
    }

    /**
     * ✅ Show a specific user
     */
    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->profile_url = $user->profile ? asset('storage/' . $user->profile) : null;

        return response()->json($user);
    }

    /**
     * ✅ Update user data
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate([
            'username' => 'sometimes|unique:users,username,' . $id,
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'profile' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('profile')) {
            if ($user->profile && Storage::disk('public')->exists($user->profile)) {
                Storage::disk('public')->delete($user->profile);
            }

            $profilePath = $request->file('profile')->store('profiles', 'public');
            $user->profile = $profilePath;
        }

        $user->username = $request->username ?? $user->username;
        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->number = $request->number ?? $user->number;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $user->profile_url = $user->profile ? asset('storage/' . $user->profile) : null;

        return response()->json($user);
    }

    /**
     * ✅ Delete user
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->profile && Storage::disk('public')->exists($user->profile)) {
            Storage::disk('public')->delete($user->profile);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
