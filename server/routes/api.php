<?php

use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\NominatimController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->get('/me', fn(Request $request) => $request->user()->load('roles'));
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/subscription/success', [SubscriptionController::class, 'subscriptionWebhook']); // SUBSCRIPTION GCASH WEBHOOK
    Route::post('/google/url', [AuthController::class, 'google']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);

    Route::prefix('otp')->group(function () {
        Route::post('/send', [OtpController::class, 'send']);
        Route::post('/verify', [OtpController::class, 'verify']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/subscription', [SubscriptionController::class, 'newSubscription']);
    Route::get('/subscription-detail', [SubscriptionController::class, 'retrieveSubscriptionDetail']);
    Route::post('/subscription-validate', [SubscriptionController::class, 'validateSubscription']);

    Route::apiResources([
        'agencies' => AgencyController::class,
        'services' => ServiceController::class,
        'reviews' => ReviewController::class,
    ]);
});

Route::prefix('branches')->group(function () {
    Route::get('/featured', [BranchController::class, 'retrieveFeaturedBranch']);
    Route::get('/filtered', [BranchController::class, 'retrieveFilteredBranch']);
});


Route::get('/plans', [PlanController::class, 'index']);

Route::get('/geocode', [NominatimController::class, 'geocode']);
Route::get('/reverse-geocode', [NominatimController::class, 'reverse']);
Route::get('/nereast-street', [NominatimController::class, 'nearest']);
