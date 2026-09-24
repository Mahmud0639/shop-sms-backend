<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;

use App\Http\Controllers\Api\SmsWalletController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/divisions', [LocationController::class, 'getDivisions']);
Route::get('/districts/{division_id}', [LocationController::class, 'getDistricts']);
Route::get('/upazilas/{district_id}', [LocationController::class, 'getUpazilas']);

Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserStatus::class])->group(function () {
    // এখানে প্রোটেক্টেড রুটগুলো থাকবে (Customer, SMS, Wallet)
    Route::get('/dashboard', [DashboardController::class, 'getSummary']);
    
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers/{id}/transaction', [CustomerController::class, 'addTransaction']);

    Route::post('/sms/schedule', [SmsWalletController::class, 'scheduleSms']);
    Route::post('/wallet/recharge', [SmsWalletController::class, 'rechargeWallet']);
    
});