<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DriverQueueController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SeatController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::prefix('trips')->group(function () {
    Route::get('/', [TripController::class, 'index']);
    Route::post('/', [TripController::class, 'store']);
});

Route::prefix('reservations')->group(function () {
    Route::post('/', [ReservationController::class, 'store']);
    Route::post('/{reservation}/pay', [PaymentController::class, 'pay']);
    Route::delete('/{reservation}', [ReservationController::class, 'destroy']);
});

Route::prefix('company')->group(function () {
    Route::get('/routes', [CompanyController::class, 'routes']);
    Route::post('/routes', [CompanyController::class, 'storeRoute']);
    Route::get('/passengers', [CompanyController::class, 'passengers']);
});

Route::prefix('seats')->group(function () {
    Route::get('/trips/{trip}', [SeatController::class, 'available']);
    Route::post('/assign', [SeatController::class, 'assign']);
});

Route::prefix('users')->group(function () {
    Route::get('/{user}/reservations', [UserController::class, 'reservations']);
});

Route::prefix('drivers')->group(function () {
    Route::post('/queue', [DriverQueueController::class, 'store']);
});
