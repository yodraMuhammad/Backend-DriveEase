<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CarController;
use App\Http\Controllers\RentalController;
use App\Http\Controllers\CarReturnController;
use App\Http\Middleware\CheckJWTToken;

Route::get('/', function () {
    return response()->json(['message' => 'Hello World'], 200);
});

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
// Route::get('/users', [UserController::class, 'index']);
// Route::get('/users/{id}', [UserController::class, 'show']);

Route::middleware(CheckJWTToken::class)->group(function () {
    Route::post('/cars', [CarController::class, 'store']);

    Route::get('/cars', [CarController::class, 'index']);
    Route::get('/cars/available-cars', [CarController::class, 'getAvailableCars']);
    Route::get('/cars/availability', [CarController::class, 'checkAvailability']);
    Route::get('/cars/{id}', [CarController::class, 'show']);

    Route::post('/cars/{id}', [CarController::class, 'update']);
    Route::delete('/cars/{id}', [CarController::class, 'destroy']);
    Route::patch('/cars/{id}/availability', [CarController::class, 'updateAvailability']);

    Route::post('/rentals', [RentalController::class, 'store']);
    Route::get('user/rentals', [RentalController::class, 'userRentals']);
    Route::get('owner/rentals', [RentalController::class, 'ownerRentals']);

    Route::post('/car-returns', [CarReturnController::class, 'store']);
    Route::post('/returns', [RentalController::class, 'returnCar']);
});

