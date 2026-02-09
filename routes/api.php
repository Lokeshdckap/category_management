<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\CategoryController;

Route::post("/login", LoginController::class);

// Route::middleware([
//     'auth:sanctum',
//     'role:admin'
// ])->prefix('admin')->group(function () {

//     Route::get('/categories/parents', [CategoryController::class, 'parents']);
//     Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
//     Route::post('/categories/reorder-featured', [CategoryController::class, 'reorderFeatured']);
//     Route::patch('/categories/{uuid}/status', [CategoryController::class, 'status']);

// });

Route::middleware(["auth:sanctum", "role:admin"])
    ->prefix("admin")
    ->group(function () {
        Route::get('/categories', [CategoryController::class, 'index']);

Route::get('/categories/parents', [CategoryController::class, 'parents']);

Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
Route::post('/categories/reorder-featured', [CategoryController::class, 'reorderFeatured']);

Route::patch('/categories/{uuid}/status', [CategoryController::class, 'status']);

Route::get('/categories/{uuid}/edit', [CategoryController::class, 'edit']);

Route::get('/categories/{uuid}', [CategoryController::class, 'show']);

Route::delete('/categories/{uuid}', [CategoryController::class, 'destroy']);
Route::put('/categories/{uuid}', [CategoryController::class, 'update']);

Route::post('/categories', [CategoryController::class, 'store']);


        Route::post("/logout", LogoutController::class);

        
    });
