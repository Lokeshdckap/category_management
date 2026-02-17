<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
   
    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->has('is_default')) {
            $query->where('is_default', $request->is_default);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $suppliers = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'suppliers' => $suppliers
        ], 200);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'duty_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->is_default) {
            Supplier::where('is_default', true)->update(['is_default' => false]);
        }

        $supplier = Supplier::create([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? 'active',
            'is_default' => $request->is_default ?? false,
            'duty_percentage' => $request->duty_percentage ?? 0,
            'shipping_cost' => $request->shipping_cost ?? 0
        ]);

        return response()->json([
            'message' => "Supplier created successfully",
            'supplier' => $supplier
        ], 201);
    }

    public function show($id)
    {
        $supplier = Supplier::findOrFail($id);
        return response()->json([
            'supplier' => $supplier
        ]);
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'is_default' => 'nullable|boolean',
            'duty_percentage' => 'nullable|numeric|min:0|max:100',
            'shipping_cost' => 'nullable|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if trying to inactivate a supplier with products
        if ($request->status === 'inactive' && $supplier->status === 'active') {
            if ($supplier->products()->count() > 0) {
                return response()->json([
                    'message' => "can't inactive this suplier alearey exist in associated products"
                ], 422);
            }
        }

        if ($request->is_default && !$supplier->is_default) {
            Supplier::where('is_default', true)->update(['is_default' => false]);
        }

        $supplier->update([
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ?? $supplier->status,
            'is_default' => $request->is_default ?? $supplier->is_default,
            'duty_percentage' => $request->duty_percentage ?? $supplier->duty_percentage,
            'shipping_cost' => $request->shipping_cost ?? $supplier->shipping_cost
        ]);

        return response()->json([
            'message' => "Supplier updated successfully",
            'supplier' => $supplier
        ]);
    }

    public function destroy($id)
    {
        $supplier = Supplier::findOrFail($id);

        if ($supplier->products()->count() > 0) {
            return response()->json([
                'message' => "can't delete this suplier alearey exist in associated products"
            ], 422);
        }

        $supplier->delete();

        return response()->json([
            'message' => "Supplier deleted successfully"
        ]);
    }

    public function toggleStatus(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);
        $newStatus = $supplier->status === 'active' ? 'inactive' : 'active';

        if ($newStatus === 'inactive' && $supplier->products()->count() > 0) {
            return response()->json([
                'message' => "can't inactive this suplier alearey exist in associated products"
            ], 422);
        }

        $supplier->status = $newStatus;
        $supplier->save();

        return response()->json([
            'message' => "Supplier status updated to " . $newStatus,
            'supplier' => $supplier
        ]);
    }
}