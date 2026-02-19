<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CustomerAuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'uuid'              => Str::uuid(),
            'name'              => $request->name,
            'email'             => $request->email,
            'password'          => Hash::make($request->password),
            'customer_group_id' => null,
            'is_active'         => false, 
        ]);

        $user->assignRole('customer');

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Your account is under verification.',
            'data'    => $user
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = User::with('customerGroup')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        if (!$user->hasRole('customer')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        if (!$user->is_active || is_null($user->customer_group_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Account under verification once verified we will notify you',
            ], 403);
        }

        $group = $user->customerGroup;

        if (!$user->is_active || !$group || !$group->status) {
            return response()->json([
                'success' => false,
                'message' => "Account is inactive. Please contact admin.",
            ], 403);
        }

        $token = $user->createToken('customer_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'token' => $token,
                'customer' => [
                    'id'    => $user->id,
                    'uuid'  => $user->uuid,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'customer_group_id' => $user->customer_group_id,
                ],
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $customer = $request->user();
        
        return response()->json([
            'success' => true,
            'data' => [
                'id'    => $customer->id,
                'uuid'  => $customer->uuid,
                'name'  => $customer->name,
                'email' => $customer->email,
                'customer_group_id' => $customer->customer_group_id,
            ],
        ]);
    }
}
