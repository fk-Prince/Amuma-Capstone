<?php

use App\Events\NotificationEvent;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NominatimController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\sample;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Models\Room;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    Route::middleware('auth:sanctum')->get('/me', [UserController::class, 'fetchMe']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/subscription/success', [SubscriptionController::class, 'subscriptionWebhook']); // SUBSCRIPTION GCASH WEBHOOK { #TODO CHNAGE URL }
    Route::post('/google/url', [AuthController::class, 'google']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);

    Route::prefix('otp')->group(function () {
        Route::post('/send', [OtpController::class, 'send']);
        Route::post('/verify', [OtpController::class, 'verify']);
    });
});

Route::prefix('branches')->group(function () {
    // PUBLIC BRANCHES
    Route::get('/featured', [BranchController::class, 'retrieveFeaturedBranch']);
    Route::get('/filtered', [BranchController::class, 'retrieveFilteredBranch']);

    // AUTHENTICATED BRACNHES ACCESS
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{uuid}/services', [ServiceController::class, 'getBranchServices']);
    });
});

Route::prefix('reviews')->group(function () {
    // PUBLIC REVIEW
    Route::get('/',  [ReviewController::class, 'list']);


    // AUTHENTICATED REVIEW ACCESS
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/',  [ReviewController::class, 'store']);
    });
});



Route::middleware('auth:sanctum')->group(function () {
    // SUBSCRIPTION
    Route::post('/subscription', [SubscriptionController::class, 'newSubscription']);
    Route::get('/subscription-detail',  [SubscriptionController::class, 'retrieveSubscriptionDetail']);
    Route::post('/subscription-validate',  [SubscriptionController::class, 'validateSubscription']);

    Route::get('/users/branches',  [UserController::class, 'getUserBranch']);



    Route::apiResources([
        'employees' => EmployeeController::class,
        'agencies' => AgencyController::class,
        'services' => ServiceController::class,
        'branches' => BranchController::class,
        'users' => UserController::class,
        'bookings' => BookingController::class,
        'notifications' => NotificationController::class,
        'rooms' => RoomController::class,
        'categories' => CategoryController::class,
        'beds' => BedController::class,
        'modules' => ModuleController::class,
    ]);



    //VALIDATE INPUTS
    Route::prefix('validate')->group(function () {
        Route::post('/agencies', [AgencyController::class, 'validate']);
        Route::post('/branches', [BranchController::class, 'validate']);
    });
});

// PLANS
Route::get('/plans', [PlanController::class, 'index']);


// LOCATIONS
Route::get('/geocode', [NominatimController::class, 'geocode']);
Route::get('/reverse-geocode', [NominatimController::class, 'reverse']);
Route::get('/nereast-street', [NominatimController::class, 'nearest']);
