<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ClientDashboardController;

Route::prefix('auth')->group(function(){
    Route::post('register/client', [AuthController::class,'registerClient']);
    Route::post('register/professional', [AuthController::class,'registerProfessional']);
    Route::post('login', [AuthController::class,'login'])->middleware('throttle:10,1');
    Route::post('logout', [AuthController::class,'logout'])->middleware('auth:sanctum');
    Route::get('me', [AuthController::class,'me'])->middleware('auth:sanctum');
});

Route::get('professionals', [ProfessionalController::class,'index']);
Route::get('professionals/{id}', [ProfessionalController::class,'show']);

Route::middleware('auth:sanctum')->group(function(){
    // uploads
    Route::post('uploads', [\App\Http\Controllers\Api\UploadController::class, 'store']);
    // favorites
    Route::get('favorites', [FavoriteController::class,'index']);
    Route::post('favorites/{professional}', [FavoriteController::class,'store']);
    Route::delete('favorites/{professional}', [FavoriteController::class,'destroy']);

    // bookings
    Route::post('bookings', [BookingController::class,'store']);
    Route::get('bookings', [BookingController::class,'index']);
    Route::get('bookings/{id}', [BookingController::class,'show']);
    Route::post('bookings/{id}/cancel', [BookingController::class,'cancel']);

    // dashboard
    Route::get('client/dashboard', [ClientDashboardController::class,'index']);
});
