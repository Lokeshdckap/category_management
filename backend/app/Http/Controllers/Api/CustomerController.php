<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::with('customerGroup')->latest()->get();

        return response()->json($customers->map(fn($customer) => [
            'id' => $customer->id,
            'uuid' => $customer->uuid,
            'name' => $customer->name,
            'email' => $customer->email,
            'customer_group_id' => $customer->customer_group_id,
            'customer_group' => $customer->customerGroup ? $customer->customerGroup->name : null,
            'status' => $customer->status,
        ])->values());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:8',
            'customer_group_id' => 'required|exists:customer_groups,id',
            'status' => 'boolean'
        ]);

        $customer = Customer::create([
            // 'uuid' => Str::uuid(),
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'customer_group_id' => $request->customer_group_id,
            'status' => $request->boolean('status', true),
        ]);

        return response()->json([
            'message' => 'Customer created successfully',
            'data' => $customer
        ], 201);
    }

    public function show($uuid)
    {
        $customer = Customer::with('customerGroup')->where('uuid', $uuid)->firstOrFail();
        return response()->json($customer);
    }

    public function update(Request $request, $uuid)
    {
        $customer = Customer::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email,' . $customer->id,
            'password' => 'nullable|string|min:8',
            'customer_group_id' => 'required|exists:customer_groups,id',
            'status' => 'boolean'
        ]);

        $data = [
            'name' => $request->name,
            'email' => $request->email,
            'customer_group_id' => $request->customer_group_id,
            'status' => $request->boolean('status', true),
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
        $customer = Customer::where('uuid', $uuid)->firstOrFail();
        $customer->delete();

        return response()->json(['message' => 'Customer deleted successfully']);
    }

    public function status($uuid)
    {
        $customer = Customer::where('uuid', $uuid)->firstOrFail();
        
        $customer->status = !$customer->status;
        $customer->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'status' => $customer->status
        ]);
    }
}
