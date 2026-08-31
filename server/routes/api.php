<?php

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
use App\Http\Controllers\OnlineScheduleController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PatientAccessController;
use App\Http\Controllers\PatientAdmissionController;
use App\Http\Controllers\PatientActivityController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VitalController;
use App\Http\Controllers\XenditController;
use Illuminate\Support\Facades\Route;



// PUBLIC 
Route::prefix('auth')->group(function () {
    Route::get('/me', [UserController::class, 'fetchMe']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/signup', [AuthController::class, 'signup']);
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


Route::middleware('auth:sanctum')->post('/qr/verify', [OnlineScheduleController::class, 'verifyQr']);

// PRIVATE - CUSTOM
Route::middleware('auth:sanctum')->group(function () {

    // EMPLOYEE / STAFF
    Route::post('/services/assign-employee', [ServiceController::class, 'assignEmployee']);

    //SCHEDULES
    Route::get('/online-schedules/qr', [OnlineScheduleController::class, 'generateQr']);
    Route::post('/schedules/action',  [ScheduleController::class, 'action']);

    // MEDICATION DOSAGE TRACKING
    Route::post('/medications/dosage', [MedicationController::class, 'dosage']);

    Route::post('/bookings/action', [BookingController::class, 'action']);
    Route::post('/invoices/action', [InvoiceController::class, 'action']);
    Route::post('/admissions/action', [PatientAdmissionController::class, 'action']);
    Route::post('/subscriptions/action', [SubscriptionController::class, 'action']);
    Route::apiResource('plans', PlanController::class)->only(['update']);

    Route::get('/patient-access/action', [PatientAccessController::class, 'retrieveAction']);
    Route::post('/patient-access/action', [PatientAccessController::class, 'executeAction']);

    // PORTAL BILLING (family/client-facing)
    Route::post('/refunds/action', [RefundController::class, 'store']);
    Route::post('/payments/action', [PaymentController::class, 'store']);
    Route::post('/payments/receipt', [PaymentController::class, 'receipt']);

    // OVERVIEW / STATS
    Route::post('/bookings/overview', [BookingController::class, 'overview']);
    Route::get('/invoices/overview', [InvoiceController::class, 'overview']);
    Route::get('/invoices/receipts', [InvoiceController::class, 'receipts']);
    Route::post('/contracts/overview', [BranchContractController::class, 'overview']);
    Route::get('/rooms/overview', [RoomController::class, 'overview']);

    // SUBSCRIPTION
    Route::post('/subscriptions', [SubscriptionController::class, 'newSubscription']);
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/subscriptions-detail',  [SubscriptionController::class, 'retrieveSubscriptionDetail']);
    Route::post('/subscriptions-validate',  [SubscriptionController::class, 'validateSubscription']);
    Route::post('/subscriptions-renew',  [SubscriptionController::class, 'renew']);

    Route::post('/notifications/read', [NotificationController::class, 'markRead']);

    // MESSAGING (family portal <-> branch staff)
    Route::get('/messages/conversations', [MessageController::class, 'clientIndex']);
    Route::get('/messages/branch-conversations', [MessageController::class, 'branchIndex']);
    Route::get('/messages/staff-conversations', [MessageController::class, 'staffIndex']);
    Route::get('/messages/colleagues', [MessageController::class, 'colleagues']);
    Route::post('/messages/open-staff', [MessageController::class, 'openWithStaff']);
    Route::get('/messages/recipients', [MessageController::class, 'recipients']);
    Route::post('/messages/open', [MessageController::class, 'openWith']);
    Route::get('/messages/thread', [MessageController::class, 'thread']);
    Route::post('/messages', [MessageController::class, 'send']);

    Route::get('/users/branches',  [UserController::class, 'getUserBranch']);
    Route::get('/profile',  [UserController::class, 'profile']);
    Route::post('/profile',  [UserController::class, 'updateProfile']);
    Route::get('/reviews/public',  [ReviewController::class, 'publicReviews']);
});


// PRIVATE API ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/patients/{uuid}/report', [PatientController::class, 'report']);

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
        'vitals' => VitalController::class,
        'patient-activities' => PatientActivityController::class,
        'schedules' => ScheduleController::class,
        'admissions' => PatientAdmissionController::class,
        'invoices' => InvoiceController::class,
        'settings' => BranchSettingController::class,
        'online-schedules' => OnlineScheduleController::class,
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
Route::get('/locations/search', [NominatimController::class, 'searchLocation']);
