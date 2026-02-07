<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with("parent:id,name,slug")->withCount("children");

        if ($request->has("main_only") && $request->main_only) {
            $query->whereNull("parent_id");
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where(
                    "name",
                    "like",
                    "%" . $request->search . "%"
                )->orWhere("slug", "like", "%" . $request->search . "%");
            });
        }

        if ($request->has("status")) {
            $status = filter_var($request->status, FILTER_VALIDATE_BOOLEAN);
            $query->where("status", $status);
        }

        if ($request->has("featured")) {
            $featured = filter_var($request->featured, FILTER_VALIDATE_BOOLEAN);
            $query->where("featured", $featured);
        }

        if ($request->has("parent_id")) {
            if ($request->parent_id === "null" || $request->parent_id === "") {
                $query->whereNull("parent_id");
            } else {
                $query->where("parent_id", $request->parent_id);
            }
        }

        $categories = $query->orderBy("sort_order")->get();

        return response()->json(
            $categories->map(function ($category) {
                return [
                    "id" => $category->id,
                    "uuid" => $category->uuid,
                    "name" => $category->name,
                    "slug" => $category->slug,
                    "parent_id" => $category->parent_id,
                    "parent" => $category->parent,
                    "featured" => $category->featured,
                    "status" => $category->status,
                    "sort_order" => $category->sort_order,
                    "featured_order" => $category->featured_order,
                    "children_count" => $category->children_count,
                    "has_children" => $category->children_count > 0,
                    "created_at" => $category->created_at,
                ];
            })
        );
    }

    public function parents(Request $request)
    {
        $query = Category::where("status", true)->select(
            "id",
            "name",
            "slug",
            "parent_id"
        );

        if ($request->exclude) {
            $query->where("uuid", "!=", $request->exclude);
        }

        $categories = $query->orderBy("name")->get();

        return response()->json($categories);
    }

    public function show($uuid)
    {
        $category = Category::with("parent:id,name,slug")
            ->where("uuid", $uuid)
            ->firstOrFail();

        return response()->json($category);
    }

    public function destroy($uuid)
    {
        $category = Category::where("uuid", $uuid)->firstOrFail();

        if ($category->children()->count() > 0) {
            return response()->json(
                [
                    "message" =>
                        "Cannot delete category with subcategories. Please delete or move subcategories first.",
                ],
                422
            );
        }

        if ($category->featured_image) {
            Storage::disk("public")->delete($category->featured_image);
        }
        if ($category->banner_image) {
            Storage::disk("public")->delete($category->banner_image);
        }

        $category->delete();

        return response()->json(["message" => "Category deleted successfully"]);
    }

    public function status($uuid)
    {
        $category = Category::where("uuid", $uuid)->firstOrFail();
        $category->status = !$category->status;
        $category->save();

        return response()->json([
            "message" => "Status updated successfully",
            "status" => $category->status,
        ]);
    }

    public function reorder(Request $request)
    {
        $request->validate([
            "items" => "required|array",
            "items.*.uuid" => "required|exists:categories,uuid",
            "items.*.sort_order" => "required|integer",
        ]);

        foreach ($request->items as $item) {
            Category::where("uuid", $item["uuid"])->update([
                "sort_order" => $item["sort_order"],
            ]);
        }

        return response()->json(["message" => "Order updated successfully"]);
    }

    public function reorderFeatured(Request $request)
    {
        $request->validate([
            "items" => "required|array",
            "items.*.uuid" => "required|exists:categories,uuid",
            "items.*.featured_order" => "required|integer",
        ]);

        foreach ($request->items as $item) {
            Category::where("uuid", $item["uuid"])
                ->where("featured", true)
                ->update(["featured_order" => $item["featured_order"]]);
        }

        return response()->json([
            "message" => "Featured order updated successfully",
        ]);
    }

    public function edit($uuid)
    {
        $category = Category::where("uuid", $uuid)->firstOrFail();

        return response()->json($category);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data["uuid"] = Str::uuid();
        $data["featured"] = $request->boolean("featured", false);
        $data["status"] = $request->boolean("status", true);
        $data["slug_status"] = $request->boolean("slug_status", true);
        $data["parent_id"] = $request->parent_id;

        if ($data["slug_status"]) {
            $baseSlug = Str::slug($request->name);
            $data["slug"] = $this->generateHierarchicalSlug(
                $baseSlug,
                $data["parent_id"]
            );
        } else {
            $data["slug"] = $this->generateHierarchicalSlug(
                $request->slug,
                $data["parent_id"]
            );
        }

        $this->handleImages($request, $data);

        $data["sort_order"] = Category::max("sort_order") + 1;

        if ($data["featured"]) {
            $data["featured_order"] =
                Category::where("featured", true)->max("featured_order") + 1;
        }

        $category = Category::create($data);

        return response()->json(
            [
                "message" => "Category created successfully",
                "data" => $category,
            ],
            201
        );
    }

    public function update(Request $request, $uuid)
    {
        $category = Category::where("uuid", $uuid)->firstOrFail();

        $data = $this->validateData($request, $category->id);

        $data["featured"] = $request->boolean("featured", false);
        $data["status"] = $request->boolean("status", true);
        $data["slug_status"] = $request->boolean("slug_status", true);
        $data["parent_id"] = $request->parent_id;

        if (
            $data["parent_id"] &&
            $this->wouldCreateCircular($category->id, $data["parent_id"])
        ) {
            return response()->json(
                [
                    "message" =>
                        "Cannot set parent: would create circular reference",
                ],
                422
            );
        }

        if ($data["slug_status"]) {
            $baseSlug = Str::slug($request->name);
            $newSlug = $this->generateHierarchicalSlug(
                $baseSlug,
                $data["parent_id"],
                $category->id
            );
        } else {
            $newSlug = $this->generateHierarchicalSlug(
                $request->slug,
                $data["parent_id"],
                $category->id
            );
        }

        if ($newSlug !== $category->slug) {
            $data["slug"] = $newSlug;
        }

        $this->handleImages($request, $data, $category);

        if ($data["featured"] && !$category->featured) {
            $data["featured_order"] =
                Category::where("featured", true)->max("featured_order") + 1;
        } elseif (!$data["featured"] && $category->featured) {
            $data["featured_order"] = 0;
        }

        $category->update($data);

        return response()->json([
            "message" => "Category updated successfully",
            "data" => $category,
        ]);
    }

    private function generateHierarchicalSlug(
        $baseSlug,
        $parentId = null,
        $ignoreId = null
    ) {
        $parentPath = "";
        if ($parentId) {
            $parent = Category::find($parentId);
            if ($parent) {
                $parentPath = $parent->slug . "-";
            }
        }

        $fullSlug = $parentPath . $baseSlug;
        $originalSlug = $fullSlug;
        $count = 1;

        while (true) {
            $query = Category::where("slug", $fullSlug);

            if ($ignoreId) {
                $query->where("id", "!=", $ignoreId);
            }

            if (!$query->exists()) {
                return $fullSlug;
            }

            $fullSlug = $originalSlug . "-" . $count;
            $count++;
        }
    }

    private function validateData(Request $request, $id = null)
    {
        return $request->validate([
            "parent_id" => "nullable|exists:categories,id",
            "name" => "required|string|max:255",
            "slug" => "required|string",
            "slug_status" => "nullable|boolean",
            "description" => "nullable",
            "featured" => "nullable|boolean",
            "status" => "nullable|boolean",
            "featured_image" => "nullable|image|max:2048",
            "banner_image" => "nullable|image|max:2048",
            "meta_title" => "nullable|string|max:255",
            "meta_description" => "nullable|string",
        ]);
    }

    private function handleImages(
        Request $request,
        array &$data,
        $category = null
    ) {
        $manager = new ImageManager(new Driver());

        // FEATURED IMAGE
        if ($request->hasFile("featured_image")) {
            $this->deleteOldImage($category?->featured_image);

            $data["featured_image"] = $this->processImage(
                $manager,
                $request->file("featured_image"),
                "categories/featured",
                $request->featured_image_meta
            );
        }

        if ($request->has("featured_image_meta")) {
            $data["featured_image_meta"] = $this->parseMeta(
                $request->featured_image_meta
            );
        }

        // BANNER IMAGE
        if ($request->hasFile("banner_image")) {
            $this->deleteOldImage($category?->banner_image);

            $data["banner_image"] = $this->processImage(
                $manager,
                $request->file("banner_image"),
                "categories/banner",
                $request->banner_image_meta
            );
        }

        if ($request->has("banner_image_meta")) {
            $data["banner_image_meta"] = $this->parseMeta(
                $request->banner_image_meta
            );
        }
    }
    private function processImage(
        ImageManager $manager,
        $file,
        string $path,
        $meta = null
    ): string {
        $image = $manager->read($file->getRealPath());

        $meta = $this->parseMeta($meta);

        // Crop if meta exists
        if ($meta && isset($meta["width"], $meta["height"])) {
            $image->crop(
                $meta["width"],
                $meta["height"],
                $meta["x"] ?? 0,
                $meta["y"] ?? 0
            );
        }

        // Optional resize (max width safety)
        $image->scaleDown(width: 1600);

        // Encode (use webp if you want)
        $encoded = $image->toJpeg(quality: 85);

        $filename = $path . "/" . uniqid() . ".jpg";

        Storage::disk("public")->put($filename, $encoded);

        return $filename;
    }

    private function parseMeta($meta): ?array
    {
        if (!$meta) {
            return null;
        }

        if (is_string($meta)) {
            $meta = json_decode($meta, true);
        }

        return is_array($meta) ? $meta : null;
    }

    private function deleteOldImage(?string $path): void
    {
        if ($path && Storage::disk("public")->exists($path)) {
            Storage::disk("public")->delete($path);
        }
    }

    private function wouldCreateCircular($categoryId, $parentId)
    {
        if (!$parentId) {
            return false;
        }

        $parent = Category::find($parentId);

        while ($parent) {
            if ($parent->id === $categoryId) {
                return true;
            }
            $parent = $parent->parent;
        }

        return false;
    }
}
