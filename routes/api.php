<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\CategoryController;



Route::post('/login', LoginController::class);

// Route::middleware([
//     'auth:sanctum',
//     'role:admin'
// ])->prefix('admin')->group(function () {

//     Route::get('/categories/parents', [CategoryController::class, 'parents']);
//     Route::post('/categories/reorder', [CategoryController::class, 'reorder']);
//     Route::post('/categories/reorder-featured', [CategoryController::class, 'reorderFeatured']);
//     Route::patch('/categories/{uuid}/status', [CategoryController::class, 'status']);

// });



Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {

    Route::get('/categories/parents', [CategoryController::class, 'parents'])
         ->middleware('permission:edit categories');

    Route::post('/categories/reorder', [CategoryController::class, 'reorder'])
        ->middleware('permission:reorder categories');

    Route::post('/categories/reorder-featured', [CategoryController::class, 'reorderFeatured'])
        ->middleware('permission:reorder categories');

    Route::patch('/categories/{uuid}/status', [CategoryController::class, 'status'])
        ->middleware('permission:change category status');

    Route::resource('categories', CategoryController::class);

    Route::post('/logout', LogoutController::class);
});


