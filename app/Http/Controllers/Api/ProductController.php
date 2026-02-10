<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of products.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }


        if ($request->has('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }


        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        if ($request->has('per_page')) {
            $products = $query->with(['defaultCategory', 'images' => function($q) {
                                $q->orderBy('sort_order')->limit(1);
                            }])
                              ->paginate($request->per_page);

            $products->getCollection()->transform(function ($product) {
                $product->first_image = $product->images->first() 
                    ? Storage::url($product->images->first()->image_path)
                    : null;
                return $product;
            });
        } else {

            $products = $query->select(['id', 'name', 'sku'])
                              ->get();
        }

        return response()->json([
            'data' => $products
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "sku" => "required|string|max:100|unique:products,sku",
            "short_description" => "nullable|string|max:500",
            "description" => "nullable|string",

            "categories" => "required|array|min:1",
            "categories.*" => "exists:categories,id",
            "default_category_id" => "required|exists:categories,id",

            "compatible_products" => "nullable|array",
            "compatible_products.*" => "exists:products,id",

            "slug" => "nullable|string|max:255|unique:products,slug",
            "meta_title" => "nullable|string|max:255",
            "meta_description" => "nullable|string|max:500",

            "images" => "nullable|array",
            "images.*.file" => "required|image|mimes:jpeg,png,jpg,gif,webp|max:2048",
            "images.*.alt" => "nullable|string|max:255",
            "images.*.title" => "nullable|string|max:255",
            "images.*.caption" => "nullable|string|max:255",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation failed",
                "errors" => $validator->errors(),
            ], 422);
        }

        try {
            $slug = $request->slug ?? Str::slug($request->name);
            
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product = Product::create([
                "name" => $request->name,
                "sku" => $request->sku,
                "short_description" => $request->short_description,
                "description" => $request->description,
                "default_category_id" => $request->default_category_id,
                "slug" => $slug,
                "meta_title" => $request->meta_title ?? $request->name,
                "meta_description" => $request->meta_description ?? $request->short_description,
            ]);

            $product->categories()->sync($request->categories);

            if ($request->filled("compatible_products")) {
                $product->compatibleProducts()->sync($request->compatible_products);
            }

            if ($request->has("images")) {
                foreach ($request->images as $index => $imageData) {
                    if (isset($imageData['file'])) {
                        $path = $imageData['file']->store('products', 'public');

                        // Create image record
                        $product->images()->create([
                            "image_path" => $path,
                            "alt" => $imageData["alt"] ?? null,
                            "title" => $imageData["title"] ?? null,
                            "caption" => $imageData["caption"] ?? null,
                            "sort_order" => $index,
                        ]);
                    }
                }
            }

            $product->load(['categories', 'defaultCategory', 'compatibleProducts', 'images']);

            return response()->json([
                "message" => "Product created successfully",
                "data" => $product
            ], 201);

        } catch (Exception $e) {
            
            return response()->json([
                "message" => "Failed to create product",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified product.
     */
    public function show($uuid)
    {
        $product = Product::with([
            'categories',
            'defaultCategory',
            'compatibleProducts',
            'images' => function($query) {
                $query->orderBy('sort_order');
            }
        ])->where('uuid', $uuid)->firstOrFail();

        return response()->json([
            'data' => $product
        ]);
    }

    /**
     * Show the form for editing the specified product.
     */
    public function edit($uuid)
    {
        $product = Product::with([
            'categories',
            'defaultCategory',
            'compatibleProducts',
            'images' => function($query) {
                $query->orderBy('sort_order');
            }
        ])->where('uuid', $uuid)->firstOrFail();

        $data = [
            'id' => $product->id,
            'uuid' => $product->uuid,
            'name' => $product->name,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'short_description' => $product->short_description,
            'description' => $product->description,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'default_category_id' => $product->default_category_id,
            'categories' => $product->categories->pluck('id')->toArray(),
            'compatible_products' => $product->compatibleProducts->pluck('id')->toArray(),
            'images' => $product->images->map(function($image) {
                return [
                    'id' => $image->id,
                    'url' => Storage::url($image->image_path),
                    'alt' => $image->alt,
                    'title' => $image->title,
                    'caption' => $image->caption,
                    'sort_order' => $image->sort_order,
                ];
            }),
        ];

        return response()->json([
            'data' => $data
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(Request $request, $uuid)
    {
        $product = Product::where('uuid', $uuid)->firstOrFail();

        $validator = Validator::make($request->all(), [
            "name" => "required|string|max:255",
            "sku" => "required|string|max:100|unique:products,sku," . $product->id,
            "short_description" => "nullable|string|max:500",
            "description" => "nullable|string",

            "categories" => "required|array|min:1",
            "categories.*" => "exists:categories,id",
            "default_category_id" => "required|exists:categories,id",

            "compatible_products" => "nullable|array",
            "compatible_products.*" => "exists:products,id",

            "slug" => "nullable|string|max:255|unique:products,slug," . $product->id,
            "meta_title" => "nullable|string|max:255",
            "meta_description" => "nullable|string|max:500",

            "images" => "nullable|array",
            "images.*.file" => "nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048",
            "images.*.alt" => "nullable|string|max:255",
            "images.*.title" => "nullable|string|max:255",
            "images.*.caption" => "nullable|string|max:255",
            
            "deleted_images" => "nullable|array",
            "deleted_images.*" => "exists:product_images,id",
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation failed",
                "errors" => $validator->errors(),
            ], 422);
        }

        try {

            $slug = $request->slug ?? Str::slug($request->name);
            
            $originalSlug = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            $product->update([
                "name" => $request->name,
                "sku" => $request->sku,
                "short_description" => $request->short_description,
                "description" => $request->description,
                "default_category_id" => $request->default_category_id,
                "slug" => $slug,
                "meta_title" => $request->meta_title ?? $request->name,
                "meta_description" => $request->meta_description ?? $request->short_description,
            ]);


            $product->categories()->sync($request->categories);


            $product->compatibleProducts()->sync($request->compatible_products ?? []);


            if ($request->has('deleted_images')) {
                foreach ($request->deleted_images as $imageId) {
                    $image = $product->images()->find($imageId);
                    if ($image) {
                        Storage::disk('public')->delete($image->image_path);
                        $image->delete();
                    }
                }
            }

            if ($request->has("images")) {
                $maxSortOrder = $product->images()->max('sort_order') ?? 0;
                
                foreach ($request->images as $index => $imageData) {
                    if (isset($imageData['file'])) {
                        $path = $imageData['file']->store('products', 'public');

                        $product->images()->create([
                            "image_path" => $path,
                            "alt" => $imageData["alt"] ?? null,
                            "title" => $imageData["title"] ?? null,
                            "caption" => $imageData["caption"] ?? null,
                            "sort_order" => $maxSortOrder + $index + 1,
                        ]);
                    }
                }
            }

            $product->load(['categories', 'defaultCategory', 'compatibleProducts', 'images']);

            return response()->json([
                "message" => "Product updated successfully",
                "data" => $product
            ]);

        } catch (Exception $e) {
            
            return response()->json([
                "message" => "Failed to update product",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product.
     */
    public function destroy($uuid)
    {
        $product = Product::where('uuid', $uuid)->firstOrFail();

        try {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }

            $product->delete();

            return response()->json([
                "message" => "Product deleted successfully"
            ]);

        } catch (Exception $e) {
            
            return response()->json([
                "message" => "Failed to delete product",
                "error" => $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:active,inactive,draft',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation failed",
                "errors" => $validator->errors(),
            ], 422);
        }

        $product = Product::where('uuid', $uuid)->firstOrFail();
        $product->update(['status' => $request->status]);

        return response()->json([
            "message" => "Product status updated successfully",
            "data" => $product
        ]);
    }
}