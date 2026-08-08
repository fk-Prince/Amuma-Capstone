<?php

use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchContractController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NominatimController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PatientAdmissionController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\XenditController;
use App\Service\AdmissionService;
use Illuminate\Support\Facades\Route;



// PUBLIC 
Route::prefix('auth')->group(function () {
    Route::middleware('auth:sanctum')->get('/me', [UserController::class, 'fetchMe']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/webhook/xendit', [XenditController::class, 'xenditWebhook']);


    Route::post('/google/url', [AuthController::class, 'google']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback']);

    Route::prefix('otp')->group(function () {
        Route::post('/send', [OtpController::class, 'send']);
        Route::post('/verify', [OtpController::class, 'verify']);
    });
});
Route::get('/branches/fetchBranch/{id}', [BranchController::class, 'fetchBranch']);


// BRNACHES
Route::prefix('branches')->group(function () {
    // PUBLIC BRANCHES
    Route::get('/featured', [BranchController::class, 'retrieveFeaturedBranch']);
    Route::get('/filtered', [BranchController::class, 'retrieveFilteredBranch']);

    // AUTHENTICATED BRACNHES ACCESS
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{uuid}/services', [ServiceController::class, 'getBranchServices']);
    });
});

// PRIVATE - CUSTOM
Route::middleware('auth:sanctum')->group(function () {

    // EMPLOYEE / STAFF
    Route::post('/services/assign-employee', [ServiceController::class, 'assignEmployee']);

    Route::post('/admissions/action', [PatientAdmissionController::class, 'action']);
    // Route::post('/admissions/admit', [PatientAdmissionController::class, 'action']);

    // CUSTOM-BOOKING
    // Route::post('/bookings/facility', [BookingController::class, 'createBooking']);
    // Route::post('/bookings/facility-admission', [BookingController::class, 'admission']);
    // Route::post('/total', [BookingController::class, 'getTotal']);


    Route::post('/bookings/action', [BookingController::class, 'action']);

    // OVERVIEW / STATS
    Route::post('/bookings/overview', [BookingController::class, 'overview']);
    Route::get('/invoices/overview', [InvoiceController::class, 'overview']);
    Route::post('/contracts/overview', [BranchContractController::class, 'overview']);
    Route::get('/rooms/overview', [RoomController::class, 'overview']);


    // SUBSCRIPTION
    Route::post('/subscription', [SubscriptionController::class, 'newSubscription']);
    Route::get('/subscription-detail',  [SubscriptionController::class, 'retrieveSubscriptionDetail']);
    Route::post('/subscription-validate',  [SubscriptionController::class, 'validateSubscription']);
    Route::get('/users/branches',  [UserController::class, 'getUserBranch']);
    Route::get('/reviews/public',  [ReviewController::class, 'publicReviews']);

    //MEDICAL ASSIGn
    Route::post('/schedules/action',  [ScheduleController::class, 'action']);
});


// PRIVATE API ROUTES
Route::middleware('auth:sanctum')->group(function () {
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
        'contracts' => BranchContractController::class,
        'reviews' => ReviewController::class,
        'patients' => PatientController::class,
        'medications' => MedicationController::class,
        'schedules' => ScheduleController::class,
        'admissions' => PatientAdmissionController::class,
        'invoices' => InvoiceController::class,
        'settings' => BranchSettingController::class
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
