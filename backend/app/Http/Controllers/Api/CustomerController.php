<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = User::role('customer')->with('customerGroup')->latest()->get();

        return response()->json($customers->map(fn($customer) => [
            'id' => $customer->id,
            'uuid' => $customer->uuid,
            'name' => $customer->name,
            'email' => $customer->email,
            'customer_group_id' => $customer->customer_group_id,
            'customer_group' => $customer->customerGroup ? $customer->customerGroup->name : null,
            'status' => $customer->is_active,
        ])->values());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'customer_group_id' => 'required|exists:customer_groups,id',
            'status' => 'boolean'
        ]);

        $customer = User::create([
            'uuid' => Str::uuid(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'customer_group_id' => $request->customer_group_id,
            'is_active' => $request->boolean('status', true),
        ]);

        $customer->assignRole('customer');

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    public function show($uuid)
    {
        $customer = User::role('customer')->with('customerGroup')->where('uuid', $uuid)->firstOrFail();
        return response()->json($customer);
    }

    public function update(Request $request, $uuid)
    {
        $customer = User::role('customer')->where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $customer->id,
            'password' => 'nullable|string|min:8',
            'customer_group_id' => 'required|exists:customer_groups,id',
            'status' => 'boolean'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'customer_group_id' => $request->customer_group_id,
            'is_active' => $request->boolean('status', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $customer->update($data);

        return response()->json([
            'message' => 'Customer updated successfully',
            'data' => $customer
        ]);
    }

    public function destroy($uuid)
    {
        $customer = User::role('customer')->where('uuid', $uuid)->firstOrFail();
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function status($uuid)
    {
        $customer = User::role('customer')->where('uuid', $uuid)->firstOrFail();
        
        $customer->is_active = !$customer->is_active;
        $customer->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'status' => $customer->is_active
        ]);
    }
}
