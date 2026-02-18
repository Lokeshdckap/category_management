<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CustomerGroupController extends Controller
{
    public function index(Request $request)
    {
        $groups = CustomerGroup::latest()->get();

        return response()->json(
            $groups
                ->map(
                    fn($group) => [
                        "id" => $group->id,
                        "uuid" => $group->uuid,
                        "name" => $group->name,
                        "status" => $group->status,
                    ]
                )
                ->values()
        );
    }

    public function store(Request $request)
    {
        $request->merge([
            "name" => strtolower($request->name),
        ]);

        $request->validate([
            "name" => "required|string|max:255|unique:customer_groups,name",
            "status" => "nullable|boolean",
        ]);

        $group = CustomerGroup::create([
            "name" => $request->name,
            "status" => $request->boolean("status", true),
        ]);

        return response()->json(
            [
                "message" => "Customer group created successfully",
                "data" => $group,
            ],
            201
        );
    }

    public function show($uuid)
    {
        $group = CustomerGroup::where("uuid", $uuid)->firstOrFail();
        return response()->json($group);
    }

    public function update(Request $request, $uuid)
    {
        $group = CustomerGroup::where("uuid", $uuid)->firstOrFail();

        $request->merge([
            "name" => strtolower(trim($request->name)),
        ]);

        $validated = $request->validate([
            "name" => [
                "required",
                "string",
                "max:255",
                Rule::unique("customer_groups", "name")->ignore($group->id),
            ],
            "status" => "nullable|boolean",
        ]);

        $group->update([
            "name" => $validated["name"],
            "status" => $request->boolean("status"),
        ]);

        return response()->json([
            "message" => "Customer group updated successfully",
            "data" => $group,
        ]);
    }

    public function destroy($uuid)
    {
        $group = CustomerGroup::where("uuid", $uuid)->firstOrFail();
        $group->delete();

        return response()->json([
            "message" => "Customer group deleted successfully",
        ]);
    }

    public function status($uuid)
    {
        $group = CustomerGroup::where("uuid", $uuid)->firstOrFail();

        $group->status = !$group->status;
        $group->save();

        return response()->json([
            "message" => "Status updated successfully",
            "status" => $group->status,
        ]);
    }
}
