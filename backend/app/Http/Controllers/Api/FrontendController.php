<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FrontendController extends Controller
{
    public function products(Request $request)
    {
        $query = Product::with(['defaultCategory', 'primaryImage', 'customerGroupPrices'])
            ->where('status', 'active');

        if ($request->has('category_id')) {
            $categoryIds = $this->getAllCategoryIds($request->category_id);
            $query->whereIn('default_category_id', $categoryIds);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        // Handle frontend specific sort keys if needed, e.g., 'price-asc'
        switch ($sortBy) {
            case 'price-asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price-desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name-asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name-desc':
                $query->orderBy('name', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $products = $query->paginate($request->get('per_page', 50));

        $products->getCollection()->transform(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float)$product->price,
                'image' => $product->primaryImage 
                    ? Storage::url($product->primaryImage->image_path) 
                    : null, // Fallback or placeholder logic can be frontend side
                'categoryId' => $product->default_category_id,
                'category_slug_url' => $product->defaultCategory ? $product->defaultCategory->slug_url : '',
                'description' => $product->short_description ?? $product->description,
                'slug' => $product->slug,
                'customer_group_pricing' => $product->customerGroupPrices,
                'override_rrp_cost' => (float)$product->override_rrp_cost,
                'rrp_cost' => (float)$product->rrp_cost,
            ];
        });

        return response()->json($products);
    }

    private function getAllCategoryIds($categoryId)
    {
        $ids = [$categoryId];
        $children = Category::where('parent_id', $categoryId)->pluck('id');
        
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getAllCategoryIds($childId));
        }
        
        return array_unique($ids);
    }

    public function categories()
    {
        $categories = Category::with('children')
            ->where('status', true)
            ->whereNull('parent_id')
            ->orderBy('sort_order')
            ->get();

        $transform = function ($cats) use (&$transform) {
            return $cats->map(function ($category) use ($transform) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'slug_url' => $category->slug_url,
                    'parentId' => $category->parent_id,
                    'image' => $category->featured_image 
                        ? Storage::url($category->featured_image) 
                        : null,
                    'children' => $category->children ? $transform($category->children) : []
                ];
            });
        };

        return response()->json($transform($categories));
    }

    public function featuredCategories()
    {
        $categories = Category::where('featured', true)
            ->where('status', true)
            ->orderBy('featured_order')
            ->get();

        return response()->json($categories->map(fn($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'slug' => $cat->slug,
            'slug_url' => $cat->slug_url,
            'image' => $cat->featured_image ? Storage::url($cat->featured_image) : null,
        ]));
    }

    public function resolve(string $slug)
    {
        // 1. Try to find a category by slug_url
        $category = Category::with(['children'])
            ->where('slug_url', $slug)
            ->where('status', true)
            ->first();

        if ($category) {
            return response()->json([
                'type' => 'category',
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'slug_url' => $category->slug_url,
                    'children' => $category->children->map(fn($child) => [
                        'id' => $child->id,
                        'name' => $child->name,
                        'slug' => $child->slug,
                        'slug_url' => $child->slug_url,
                    ])
                ]
            ]);
        }

        // 2. If not a category, try to find a product
        // We assume the URL is [category-path]/[product-slug]
        $parts = explode('/', $slug);
        $productSlug = array_pop($parts);
        $categoryPath = implode('/', $parts);

        // Try direct slug match first if no category path
        $productQuery = Product::with(['defaultCategory', 'primaryImage', 'images', 'customerGroupPrices'])
            ->where('slug', $productSlug)
            ->where('status', 'active');

        if ($categoryPath) {
            $productQuery->whereHas('defaultCategory', function($q) use ($categoryPath) {
                $q->where('slug_url', $categoryPath);
            });
        }

        $product = $productQuery->first();

        if ($product) {
            $relatedProducts = Product::with(['defaultCategory', 'primaryImage'])
                ->where('default_category_id', $product->default_category_id)
                ->where('id', '!=', $product->id)
                ->where('status', 'active')
                ->limit(4)
                ->get()
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'price' => (float)$p->price,
                    'image' => $p->primaryImage ? Storage::url($p->primaryImage->image_path) : null,
                    'slug' => $p->slug,
                    'category_slug_url' => $p->defaultCategory ? $p->defaultCategory->slug_url : ''
                ]);

            return response()->json([
                'type' => 'product',
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => (float)$product->price,
                    'image' => $product->primaryImage 
                        ? Storage::url($product->primaryImage->image_path) 
                        : null,
                    'images' => $product->images->map(fn($img) => Storage::url($img->image_path)),
                    'category' => $product->defaultCategory ? [
                        'id' => $product->defaultCategory->id,
                        'name' => $product->defaultCategory->name,
                        'slug' => $product->defaultCategory->slug,
                        'slug_url' => $product->defaultCategory->slug_url
                    ] : null,
                    'description' => $product->description,
                    'short_description' => $product->short_description,
                    'sku' => $product->sku,
                    'slug' => $product->slug,
                    'total_price' => (float)$product->total_price,
                    'type' => $product->type,
                    'customer_group_pricing' => $product->customerGroupPrices,
                    'override_rrp_cost' => (float)$product->override_rrp_cost,
                    'rrp_cost' => (float)$product->rrp_cost,
                    'related_products' => $relatedProducts
                ]
            ]);
        }

        return response()->json(['message' => 'Not found'], 404);
    }

    public function show(string $slug)
    {
        $product = Product::with(['defaultCategory', 'primaryImage', 'images', 'customerGroupPrices'])
            ->where('slug', $slug)
            ->where('status', 'active')
            ->firstOrFail();

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float)$product->price,
            'image' => $product->primaryImage 
                ? Storage::url($product->primaryImage->image_path) 
                : null,
            'images' => $product->images->map(fn($img) => Storage::url($img->image_path)),
            'category' => $product->defaultCategory ? [
                'id' => $product->defaultCategory->id,
                'name' => $product->defaultCategory->name,
                'slug' => $product->defaultCategory->slug
            ] : null,
            'description' => $product->description,
            'short_description' => $product->short_description,
            'sku' => $product->sku,
            'slug' => $product->slug,
            'total_price' => (float)$product->total_price,
            'type' => $product->type,
            'customer_group_pricing' => $product->customerGroupPrices,
            'override_rrp_cost' => (float)$product->override_rrp_cost,
            'rrp_cost' => (float)$product->rrp_cost,
        ]);
    }
}
