<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\SendOtpMail;
use App\Services\OtpService;

class UserController extends Controller
{
    /**
     * ✅ Login or Register with OTP (no expectsJson check)
     */
   public function loginOrRegisterWithOTP(Request $request)
    {
        $request->validate([
            'email'     => 'nullable|email',
            'number' => 'nullable|digits:10',
        ]);

        if (!$request->filled('email') && !$request->filled('number')) {
            return response()->json([
                'message' => 'Either email or number is required.'
            ], 422);
        }

        $otp = rand(100000, 999999);

        // 🔍 Try finding user by email or mobile
        $user = null;

        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->filled('number')) {
            $user = User::where('number', $request->number)->first();
        }

        if ($user) {
            $user->otp = $otp;
            $user->save();
        } else {
            // 👤 Create user with whatever field is available
            $user = User::create([
                'email'     => $request->email,
                'number' => $request->number,
                'otp'       => $otp,
                'username'  => null,
                'name'      => null,
                'password'  => null,
            ]);
        }

        // 📧 Send OTP via email if email exists
         if ($user->number) {
            OtpService::sendOTPPhone($otp, $user->number, 'account_verify_peraux');
        }
        
        if ($user->email) {
            Mail::to($user->email)->send(new SendOtpMail($otp));
        }
       

        return response()->json([
            'message' => 'OTP sent successfully.',
        ], 200);
    }


    /**
     * ✅ Verify the OTP (no expectsJson check)
     */
    public function verifyOtp(Request $request)
        {
            $request->validate([
                'email'     => 'required_without:number|nullable|email',
                'number' => 'required_without:email|nullable|digits:10',
                'otp'       => 'required|digits:6',
            ]);

            // Make sure at least one identifier is present
            if (!$request->filled('email') && !$request->filled('number')) {
                return response()->json([
                    'message' => 'Either email or number is required.'
                ], 422);
            }

            // Dynamically search by email or mobile
            $user = null;
            if ($request->filled('email')) {
                $user = User::where('email', $request->email)->first();
            } elseif ($request->filled('number')) {
                $user = User::where('number', $request->number)->first();
            }

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            // Match OTP
            if ($user->otp === $request->otp) {
                $user->otp = null;
                $user->save();

                $token = $user->createToken('user-token')->plainTextToken;

                return response()->json([
                    'message' => 'OTP verified successfully',
                    'user'    => $user,
                    'token'   => $token,

                ], 200);
            }

            return response()->json(['message' => 'Invalid OTP'], 401);
        }


    /**
     * ✅ Get all users
     */
   public function index(Request $request)
{
    $query = User::query();

    // 🔍 Search by name, email or number
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('number', 'like', "%{$search}%");
        });
    }

    // 📄 Pagination
    $perPage = (int) $request->get('per_page', 10);
    $page = (int) $request->get('page_number', 1);

    $users = $query->paginate($perPage, ['*'], 'page', $page);

    // 🌐 Add full profile URL
    $users->getCollection()->transform(function ($user) {
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
            'username' => 'sometimes|string|max:255',
            'name'     => 'sometimes|string|max:255',
            'email'    => 'sometimes|email|unique:users,email,' . $user->id,
            'number'   => 'sometimes|digits:10|unique:users,number,' . $user->id,
            'profile'  => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Handle profile image
        if ($request->hasFile('profile')) {
            if ($user->profile && Storage::disk('public')->exists($user->profile)) {
                Storage::disk('public')->delete($user->profile);
            }

            $profilePath = $request->file('profile')->store('profiles', 'public');
            $user->profile = $profilePath;
        }

        // Update fields
        $user->name = $request->name ?? $user->name;
        $user->email = $request->email ?? $user->email;
        $user->number = $request->number ?? $user->number;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        // Add full profile URL
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

   public function adminlogin(Request $request)
    {
        $request->validate([
            'username' => 'required|exists:users,username',
            'password' => 'required',
        ]);

        // Find user by username
        $user = User::where('username', $request->username)->first();

        // Check if user exists and password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid username or password'
            ], 401);
        }

        // Create token
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user'    => $user,
            'token'   => $token
        ]);
    }


    public function createAdmin(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:users,username',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed', // expects password_confirmation field as well
        ]);

        $admin = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $token = $admin->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Admin account created successfully',
            'admin'   => $admin,
            'token' => $token
        ], 201);
    }
}
