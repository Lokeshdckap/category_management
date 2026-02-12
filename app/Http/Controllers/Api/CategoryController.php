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
    $query = Category::with('parent:id,name,slug')
        ->withCount('children');

    if ($request->boolean('main_only')) {
        $query->whereNull('parent_id');
    }

    if ($request->filled('search')) {
        $query->where(function ($q) use ($request) {
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('slug', 'like', "%{$request->search}%");
        });
    }

    if ($request->has('status')) {
        $query->where('status', $request->boolean('status'));
    }

    if ($request->has('featured')) {
        $query->where('featured', $request->boolean('featured'));
    }

    if ($request->has('parent_id')) {
        $request->parent_id === 'null' || $request->parent_id === ''
            ? $query->whereNull('parent_id')
            : $query->where('parent_id', $request->parent_id);
    }

    $categories = $query->orderBy('sort_order')->get();

    return response()->json(
        $categories->map(fn ($category) => [
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'parent_id' => $category->parent_id,
            'parent' => $category->parent,
            'featured' => $category->featured,
            'status' => $category->status,
            'sort_order' => $category->sort_order,
            'featured_order' => $category->featured_order,
            'children_count' => $category->children_count, 
            'has_children' => $category->children_count > 0, 
            'created_at' => $category->created_at,
        ])
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

        if ($category->products()->exists()) {
            return response()->json(
                [
                    "message" =>
                        "Cannot delete category with associated products. Please reassign or delete products first.",
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
        
        // If currently active and trying to deactivate, check for products
        if ($category->status && $category->products()->exists()) {
            return response()->json(
                [
                    "message" =>
                        "Cannot deactivate category with associated products.",
                ],
                422
            );
        }

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
        $category = Category::where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'id' => $category->id,
            'uuid' => $category->uuid,
            'name' => $category->name,
            'slug' => $category->slug,
            'slug_url' => $category->slug_url,
            'description' => $category->description,
            'parent_id' => $category->parent_id,
            'featured' => $category->featured,
            'status' => $category->status,
            'slug_status' => $category->slug_status,
            'featured_image' => $category->featured_image,
            'featured_image_url' => $category->featured_image 
                ? Storage::url($category->featured_image) 
                : null,
            'featured_image_meta' => $category->featured_image_meta ?? [
                'alt' => '',
                'title' => '',
                'caption' => ''
            ],
            'banner_image' => $category->banner_image,
            'banner_image_url' => $category->banner_image 
                ? Storage::url($category->banner_image) 
                : null,
            'banner_image_meta' => $category->banner_image_meta ?? [
                'alt' => '',
                'title' => '',
                'caption' => ''
            ],
            'meta_title' => $category->meta_title,
            'meta_description' => $category->meta_description,
            'sort_order' => $category->sort_order,
            'featured_order' => $category->featured_order,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        $data['uuid'] = Str::uuid();
        $data['featured'] = $request->boolean('featured', false);
        $data['status'] = $request->boolean('status', true);
        $data['slug_status'] = $request->boolean('slug_status', true);
        $data['parent_id'] = $request->parent_id;

        if ($data['slug_status']) {
            $baseSlug = Str::slug($request->name);
        } else {
            $baseSlug = $request->slug;
        }

        $data['slug'] = $this->generateUniqueSlug($baseSlug, $data['parent_id']);

        $data['slug_url'] = $this->generateSlugUrl($data['slug'], $data['parent_id']);

        $this->handleImages($request, $data);

        $data['sort_order'] = Category::max('sort_order') + 1;

        if ($data['featured']) {
            $data['featured_order'] = Category::where('featured', true)->max('featured_order') + 1;
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

     public function update(Request $request, $uuid)
    {
        $category = Category::where('uuid', $uuid)->firstOrFail();

        $data = $this->validateData($request, $category->id);

        $data['featured'] = $request->boolean('featured', false);
        $data['status'] = $request->boolean('status', true);
        $data['slug_status'] = $request->boolean('slug_status', true);
        $data['parent_id'] = $request->parent_id;

        if ($data['parent_id'] && $this->wouldCreateCircular($category->id, $data['parent_id'])) {
            return response()->json([
                'message' => 'Cannot set parent: would create circular reference'
            ], 422);
        }

        $slugChanged = false;
        if ($data['slug_status']) {
            $newBaseSlug = Str::slug($request->name);
        } else {
            $newBaseSlug = $request->slug;
        }

        if ($newBaseSlug !== $category->slug || $data['parent_id'] !== $category->parent_id) {
            $data['slug'] = $this->generateUniqueSlug($newBaseSlug, $data['parent_id'], $category->id);
            $data['slug_url'] = $this->generateSlugUrl($data['slug'], $data['parent_id']);
            $slugChanged = true;
        }

        $this->handleImages($request, $data, $category);

        if ($data['featured'] && !$category->featured) {
            $data['featured_order'] = Category::where('featured', true)->max('featured_order') + 1;
        } elseif (!$data['featured'] && $category->featured) {
            $data['featured_order'] = 0;
        }

        $category->update($data);

        if ($slugChanged && $category->children()->count() > 0) {
            $this->updateChildrenSlugUrls($category);
        }

        return response()->json([
            'message' => 'Category updated successfully',
            'data' => $category
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

    private function generateUniqueSlug($baseSlug, $parentId = null, $ignoreId = null)
    {
        $originalSlug = $baseSlug;
        $count = 1;

        while (true) {
            $query = Category::where('slug', $baseSlug);
            
            if ($parentId) {
                $query->where('parent_id', $parentId);
            } else {
                $query->whereNull('parent_id');
            }
            
            if ($ignoreId) {
                $query->where('id', '!=', $ignoreId);
            }

            if (!$query->exists()) {
                return $baseSlug;
            }

            $baseSlug = $originalSlug . '-' . $count;
            $count++;
        }
    }

    private function generateSlugUrl($slug, $parentId = null)
    {
        if (!$parentId) {
            return $slug;
        }


        $parent = Category::find($parentId);
        if (!$parent) {
            return $slug;
        }
        return $parent->slug_url . '/' . $slug;
    }

    private function updateChildrenSlugUrls($category)
    {
        $children = $category->children;

        foreach ($children as $child) {
            $newSlugUrl = $category->slug_url . '/' . $child->slug;
            $child->update(['slug_url' => $newSlugUrl]);


            if ($child->children()->count() > 0) {
                $this->updateChildrenSlugUrls($child);
            }
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

        if ($meta && isset($meta["width"], $meta["height"])) {
            $image->crop(
                $meta["width"],
                $meta["height"],
                $meta["x"] ?? 0,
                $meta["y"] ?? 0
            );
        }


        $image->scaleDown(width: 1600);

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
