<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\ProfessionalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — prefijo /api/v1 (bootstrap/app.php)
|--------------------------------------------------------------------------
| Permisos: `role:` en la ruta decide QUIÉN puede entrar (tipo de cuenta);
| las Policies deciden sobre QUÉ recurso (dueño, estado). Ver docs/arquitectura.md.
*/

// Autenticación
Route::prefix('auth')->group(function () {
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('register/client', [AuthController::class, 'registerClient']);
        Route::post('register/professional', [AuthController::class, 'registerProfessional']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me'])->middleware('not_blocked');
    });
});

// Catálogos y búsqueda pública
Route::get('communes', [CatalogController::class, 'communes']);
Route::get('categories', [CatalogController::class, 'categories']);
Route::get('professionals', [ProfessionalController::class, 'index']);
Route::get('professionals/{id}', [ProfessionalController::class, 'show'])->whereNumber('id');

// Rutas autenticadas
Route::middleware(['auth:sanctum', 'not_blocked'])->group(function () {
    // Favoritos (solo clientes)
    Route::middleware('role:client')->group(function () {
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/{professionalId}', [FavoriteController::class, 'store'])->whereNumber('professionalId');
        Route::delete('favorites/{professionalId}', [FavoriteController::class, 'destroy'])->whereNumber('professionalId');
    });

    // Reservas
    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);
});
