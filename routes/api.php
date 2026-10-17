<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\FavoriteController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PortfolioController;
use App\Http\Controllers\Api\ProfessionalController;
use App\Http\Controllers\Api\ProfessionalProfileController;
use App\Http\Controllers\Api\ProfessionalServiceController;
use App\Http\Controllers\Api\ProfessionalSlotController;
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
Route::get('professionals/{id}/slots', [ProfessionalSlotController::class, 'index'])->whereNumber('id');

// Rutas autenticadas
Route::middleware(['auth:sanctum', 'not_blocked'])->group(function () {
    // Favoritos (solo clientes)
    Route::middleware('role:client')->group(function () {
        Route::get('favorites', [FavoriteController::class, 'index']);
        Route::post('favorites/{professionalId}', [FavoriteController::class, 'store'])->whereNumber('professionalId');
        Route::delete('favorites/{professionalId}', [FavoriteController::class, 'destroy'])->whereNumber('professionalId');
    });

    // Perfil, servicios y portafolio del profesional (HU009, HU047)
    Route::middleware('role:professional')->prefix('professional')->group(function () {
        Route::get('profile', [ProfessionalProfileController::class, 'show']);
        Route::patch('profile', [ProfessionalProfileController::class, 'update']);
        Route::put('categories', [ProfessionalProfileController::class, 'categories']);

        Route::get('services', [ProfessionalServiceController::class, 'index']);
        Route::post('services', [ProfessionalServiceController::class, 'store']);
        Route::patch('services/{service}', [ProfessionalServiceController::class, 'update']);
        Route::delete('services/{service}', [ProfessionalServiceController::class, 'destroy']);

        Route::get('portfolio', [PortfolioController::class, 'index']);
        Route::post('portfolio', [PortfolioController::class, 'store']);
        Route::delete('portfolio/{item}', [PortfolioController::class, 'destroy']);
    });

    // Agenda del profesional (HU003)
    Route::middleware('role:professional')->prefix('professional/availability')->group(function () {
        Route::get('/', [AvailabilityController::class, 'show']);
        Route::put('rules', [AvailabilityController::class, 'updateRules']);
        Route::post('blocks', [AvailabilityController::class, 'storeBlock']);
        Route::delete('blocks/{block}', [AvailabilityController::class, 'destroyBlock']);
    });

    // Reservas
    Route::get('bookings', [BookingController::class, 'index']);
    Route::get('bookings/summary', [BookingController::class, 'summary']);
    Route::post('bookings', [BookingController::class, 'store']);
    Route::get('bookings/{booking}', [BookingController::class, 'show']);
    Route::get('bookings/{booking}/history', [BookingController::class, 'history']);
    Route::post('bookings/{booking}/accept', [BookingController::class, 'accept']);
    Route::post('bookings/{booking}/reject', [BookingController::class, 'reject']);
    Route::post('bookings/{booking}/confirm', [BookingController::class, 'confirm']);
    Route::post('bookings/{booking}/start', [BookingController::class, 'start']);
    Route::post('bookings/{booking}/complete', [BookingController::class, 'complete']);
    Route::post('bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
    Route::post('bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Notificaciones (HU018)
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'readAll']);
    Route::post('notifications/{id}/read', [NotificationController::class, 'read'])->whereUuid('id');

    // Chat de la reserva (HU011, HU042, HU043)
    Route::get('bookings/{booking}/messages', [MessageController::class, 'index']);
    Route::post('bookings/{booking}/messages', [MessageController::class, 'store']);
    Route::post('bookings/{booking}/messages/read', [MessageController::class, 'read']);
    Route::delete('messages/{message}', [MessageController::class, 'destroy']);
});
