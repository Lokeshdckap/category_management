<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\ProductReportController;




use App\Http\Controllers\Api\FrontendController;

Route::post("/login", LoginController::class);

Route::prefix('shop')->group(function () {
    Route::get('/products', [FrontendController::class, 'products']);
    Route::get('/products/{slug}', [FrontendController::class, 'show']);
    Route::get('/categories', [FrontendController::class, 'categories']);
    Route::get('/featured-categories', [FrontendController::class, 'featuredCategories']);
    Route::get('/resolve/{slug}', [FrontendController::class, 'resolve'])->where('slug', '.*');
});

Route::middleware(["auth:sanctum", "role:admin"])
    ->prefix("admin")
    ->group(function () {
        Route::get("/categories", [CategoryController::class, "index"]);

        Route::get("/categories/parents", [
            CategoryController::class,
            "parents",
        ]);

        Route::post("/categories/reorder", [
            CategoryController::class,
            "reorder",
        ]);
        Route::post("/categories/reorder-featured", [
            CategoryController::class,
            "reorderFeatured",
        ]);

        Route::patch("/categories/{uuid}/status", [
            CategoryController::class,
            "status",
        ]);

        Route::get("/categories/{uuid}/edit", [
            CategoryController::class,
            "edit",
        ]);

        Route::get("/categories/{uuid}", [CategoryController::class, "show"]);

        Route::delete("/categories/{uuid}", [
            CategoryController::class,
            "destroy",
        ]);
        Route::put("/categories/{uuid}", [CategoryController::class, "update"]);

        Route::post("/categories", [CategoryController::class, "store"]);

        Route::get("/products", [ProductController::class, "index"]);

        Route::post("/products", [ProductController::class, "store"]);

        Route::get("/products/{uuid}", [ProductController::class, "show"]);


        Route::get("/products/{uuid}/edit", [
            ProductController::class,
            "edit",
        ]);



        Route::patch("/products/{uuid}/status", [ProductController::class, "updateStatus"]);

        Route::put("/products/{uuid}", [ProductController::class, "update"]);

         Route::delete("/products/{uuid}", [
            ProductController::class,
            "destroy",
        ]);

        Route::get("/suppliers",[SupplierController::class,'index']);
        Route::post("/suppliers", [SupplierController::class, 'store']);
        Route::get("/suppliers/{id}", [SupplierController::class, 'show']);
        Route::put("/suppliers/{id}", [SupplierController::class, 'update']);
        Route::delete("/suppliers/{id}", [SupplierController::class, 'destroy']);

        Route::get("/reports/products",[ProductReportController::class,'index']);


        Route::post("/logout", LogoutController::class);
    });
