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

// SSLCommerz Callbacks (No Auth Middleware Required)
Route::post('/payment/success', [SmsWalletController::class, 'handleSuccess'])->name('payment.success');
Route::post('/payment/fail', [SmsWalletController::class, 'handleFail'])->name('payment.fail');
Route::post('/payment/cancel', [SmsWalletController::class, 'handleCancel'])->name('payment.cancel');
Route::post('/payment/ipn', [SmsWalletController::class, 'handleSuccess'])->name('payment.ipn');

Route::middleware(['auth:sanctum', \App\Http\Middleware\CheckUserStatus::class])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'getSummary']);
    
    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers', [CustomerController::class, 'store']);
    Route::get('/customers/{id}', [CustomerController::class, 'show']);
    Route::post('/customers/{id}/transaction', [CustomerController::class, 'addTransaction']);

    // ম্যানুয়াল SMS তাগাদা পাঠানোর রুট
    Route::post('/customers/{id}/send-reminder-sms', [CustomerController::class, 'sendReminderSms']);

    // SMS Schedule Routes
    Route::get('/sms/schedules', [SmsWalletController::class, 'getSmsSchedules']);
    Route::post('/sms/schedule', [SmsWalletController::class, 'scheduleSms']);
    Route::delete('/sms/schedules/{id}/cancel', [SmsWalletController::class, 'cancelSmsSchedule']);

    // ওয়ালেট ডাটা, ডাইনামিক প্যাকেজ এবং রিচার্জ হিস্ট্রি রাউট
    Route::get('/wallet/info', [SmsWalletController::class, 'getWalletInfo']);
    Route::get('/wallet/packages', [SmsWalletController::class, 'getSmsPackages']);
    
    // ওয়ালেট রিচার্জ রুট (Initiate)
    Route::post('/wallet/recharge', [SmsWalletController::class, 'rechargeWallet']);

    Route::get('/wallet/all-history', [SmsWalletController::class, 'getAllRechargeHistory']);
    
    // বাল্ক SMS তাগাদা পাঠানোর রুট
    Route::post('/customers/send-bulk-reminder-sms', [CustomerController::class, 'sendBulkReminderSms']);
});