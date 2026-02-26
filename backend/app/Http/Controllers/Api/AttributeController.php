<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;

class AttributeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Attribute::with('values');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $attributes = $query->orderBy('sort_order')->get();
        return response()->json($attributes);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'values' => 'required|array|min:1',
            'values.*.value' => 'required|string|max:255',
        ]);

        // Case-insensitive check for attribute name
        $exists = Attribute::whereRaw('LOWER(name) = ?', [strtolower($request->name)])->exists();
        if ($exists) {
            return response()->json([
                'message' => 'An attribute with this name already exists (case-insensitive).',
                'errors' => ['name' => ['The attribute name is already taken.']]
            ], 422);
        }

        // Case-insensitive check for duplicate values within the input
        $inputValues = collect($request->values)->map(fn($v) => strtolower($v['value']));
        if ($inputValues->unique()->count() !== $inputValues->count()) {
            return response()->json([
                'message' => 'Duplicate attribute values provided.',
                'errors' => ['values' => ['Each attribute value must be unique for this attribute.']]
            ], 422);
        }

        $attribute = Attribute::create([
            'name' => $request->name,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        foreach ($request->values as $index => $val) {
            $attribute->values()->create([
                'value' => $val['value'],
                'sort_order' => $val['sort_order'] ?? $index,
                'status' => $val['status'] ?? 'active',
            ]);
        }

        return response()->json($attribute->load('values'), 201);
    }

    public function show(Attribute $attribute)
    {
        return response()->json($attribute->load('values'));
    }

    public function update(Request $request, Attribute $attribute)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|string|in:active,inactive',
            'values' => 'required|array|min:1',
            'values.*.id' => 'nullable|exists:attribute_values,id',
            'values.*.value' => 'required|string|max:255',
            'values.*.status' => 'nullable|string|in:active,inactive',
        ]);

        // Case-insensitive check for attribute name (excluding current)
        $exists = Attribute::where('id', '!=', $attribute->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'An attribute with this name already exists (case-insensitive).',
                'errors' => ['name' => ['The attribute name is already taken.']]
            ], 422);
        }

        // Case-insensitive check for duplicate values within the input
        $inputValues = collect($request->values)->map(fn($v) => strtolower($v['value']));
        if ($inputValues->unique()->count() !== $inputValues->count()) {
            return response()->json([
                'message' => 'Duplicate attribute values provided.',
                'errors' => ['values' => ['Each attribute value must be unique for this attribute.']]
            ], 422);
        }

        $attribute->update([
            'name' => $request->name,
            'sort_order' => $request->sort_order ?? 0,
            'status' => $request->status ?? 'active',
        ]);

        $keepIds = [];
        foreach ($request->values as $index => $val) {
            if (isset($val['id'])) {
                $attributeValue = $attribute->values()->find($val['id']);
                if ($attributeValue) {
                    $attributeValue->update([
                        'value' => $val['value'],
                        'sort_order' => $val['sort_order'] ?? $index,
                        'status' => $val['status'] ?? 'active',
                    ]);
                    $keepIds[] = $attributeValue->id;
                }
            } else {
                $newVal = $attribute->values()->create([
                    'value' => $val['value'],
                    'sort_order' => $val['sort_order'] ?? $index,
                    'status' => $val['status'] ?? 'active',
                ]);
                $keepIds[] = $newVal->id;
            }
        }

        // Delete values not in the request
        $attribute->values()->whereNotIn('id', $keepIds)->delete();

        return response()->json($attribute->load('values'));
    }

    public function destroy(Attribute $attribute)
    {
        $attribute->delete();
        return response()->json(['message' => 'Attribute deleted successfully']);
    }
    
    public function updateOrder(Request $request)
    {
        $request->validate([
            'attributes' => 'required|array',
            'attributes.*.id' => 'required|exists:attributes,id',
            'attributes.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->attributes as $item) {
            Attribute::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Order updated successfully']);
    }
}
