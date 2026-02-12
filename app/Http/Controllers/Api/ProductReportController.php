<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with([
            'categories:id,name', 
            'suppliers:id,name', 
            'images', 
            'compatibleProducts:id,name,sku', 
            'bundleProducts:id,name,sku', 
            'defaultCategory:id,name'
        ]);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('sku', 'like', "%{$request->search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }

        if ($request->filled('supplier_id')) {
            $query->whereHas('suppliers', function ($q) use ($request) {
                $q->where('suppliers.id', $request->supplier_id);
            });
        }

        if ($request->filled('status')) {
             $query->where('status', $request->status);
        }

        $perPage = $request->input('per_page', 10);
        $products = $query->with([
            'primaryImage', 
            'defaultCategory', 
            'categories', 
            'suppliers', 
            'images', 
            'compatibleProducts', 
            'bundleProducts'
        ])->paginate($perPage);

        // Transform data to report format
        $data = $products->getCollection()->map(function ($product) {
            $firstImage = $product->primaryImage;
            
            return [
                'id' => $product->id,
                'uuid' => $product->uuid,
                'image' => $firstImage ? \Storage::url($firstImage->image_path) : null,
                'name' => $product->name,
                'sku' => $product->sku,
                'status' => $product->status,
                'type' => $product->type,
                'description' => $product->description,
                'short_description' => $product->short_description,
                'slug' => $product->slug,
                'meta_title' => $product->meta_title,
                'meta_description' => $product->meta_description,
                'price' => $product->price ?? 0,
                'gp_percentage' => ($product->isBundle() ? $product->bundle_gp_percentage : $product->gp_percentage) ?? 0,
                'total_price' => ($product->isBundle() ? $product->calculated_bundle_final_price : $product->total_price) ?? 0,
                'bundle_subtotal' => $product->isBundle() ? $product->calculated_bundle_subtotal : 0,
                'bundle_gp_percentage' => $product->bundle_gp_percentage ?? 0,
                'bundle_final_price' => $product->isBundle() ? $product->calculated_bundle_final_price : 0,
                'default_category' => $product->defaultCategory,
                'all_categories' => $product->categories,
                'categories' => $product->categories->pluck('name')->implode(', '),
                'suppliers' => $product->suppliers->map(function ($supplier) {
                    return [
                        'name' => $supplier->name,
                        'price' => $supplier->pivot->price
                    ];
                }),
                'images' => $product->images->map(function ($img) {
                    return [
                        'url' => \Storage::url($img->image_path),
                        'alt' => $img->alt,
                        'is_primary' => $img->is_primary
                    ];
                }),
                'compatible_products' => $product->compatibleProducts->map(function ($p) {
                    return ['name' => $p->name, 'sku' => $p->sku];
                }),
                'bundle_items' => $product->bundleProducts->map(function ($p) {
                    $price = $p->pivot->price > 0 ? $p->pivot->price : ($p->total_price ?? 0);
                    return [
                        'name' => $p->name, 
                        'sku' => $p->sku,
                        'qty' => $p->pivot->quantity,
                        'price' => $price
                    ];
                }),
                'base_price' => $product->price ?? 0,
            ];
        });

        return response()->json([
            'data' => $data,
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total()
        ]);
    }
}
