<?php

use App\Http\Controllers\AdditionalChargeController;
use App\Http\Controllers\AgencyController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BedController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\BranchContractController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\BranchSettingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DiagnosisCaseController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MedicationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NominatimController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdlAttendanceController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\PatientAccessController;
use App\Http\Controllers\CaregiverShiftController;
use App\Http\Controllers\PatientAdmissionController;
use App\Http\Controllers\PatientActivityController;
use App\Http\Controllers\EmployeeNoteController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SandboxController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VitalController;
use App\Http\Controllers\XenditController;
use App\Http\Middleware\BlockInSandbox;
use Illuminate\Support\Facades\Route;



// PUBLIC 
Route::prefix('auth')->group(function () {
    Route::get('/me', [UserController::class, 'fetchMe']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/webhook/xendit', [XenditController::class, 'xenditWebhook']);
    Route::get('/payments/status/{reference}', [XenditController::class, 'checkStatus']);


    Route::post('/google/url', [AuthController::class, 'google']);
    Route::get('/google/callback', [AuthController::class, 'googleCallback'])->middleware('web');

    Route::prefix('otp')->group(function () {
        Route::post('/send', [OtpController::class, 'send']);
        Route::post('/verify', [OtpController::class, 'verify']);
    });

    Route::post('/forgot-password', [PasswordResetController::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/reset-password/check', [PasswordResetController::class, 'check'])->middleware('throttle:20,1');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
});
Route::get('/branches/fetchBranch/{id}', [BranchController::class, 'fetchBranch']);
if (config('app.sandbox')) {
    Route::post('/sandbox/provision', [SandboxController::class, 'provision'])->middleware('throttle:30,1');
    Route::post('/sandbox/lock', [SandboxController::class, 'lock'])->middleware('throttle:120,1');
}


// BRNACHES
Route::prefix('branches')->group(function () {
    // PUBLIC BRANCHES
    Route::get('/featured', [BranchController::class, 'retrieveFeaturedBranch']);
    Route::get('/filtered', [BranchController::class, 'retrieveFilteredBranch']);
    Route::get('/{uuid}/health-providers', [BranchController::class, 'healthProviders']);
    Route::post('/{uuid}/inquiries', [InquiryController::class, 'store'])->middleware('throttle:5,1');

    // AUTHENTICATED BRACNHES ACCESS
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/{uuid}/services', [ServiceController::class, 'getBranchServices']);
    });
});


Route::middleware('auth:sanctum')->post('/qr/verify', [AdlAttendanceController::class, 'verifyQr']);
Route::middleware('auth:sanctum')->post('/qr/demo-verify', [AdlAttendanceController::class, 'demoVerifyQr']);

// PRIVATE - CUSTOM
Route::middleware('auth:sanctum')->group(function () {

    // INQUIRIES
    Route::get('/inquiries', [InquiryController::class, 'index']);
    Route::patch('/inquiries/{uuid}/status', [InquiryController::class, 'updateStatus']);
    Route::post('/inquiries/{uuid}/reply', [InquiryController::class, 'reply']);

    // CUSTOMER SUPPORT
    Route::get('/support-tickets', [SupportTicketController::class, 'index']);
    Route::post('/support-tickets', [SupportTicketController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/support-tickets/{uuid}', [SupportTicketController::class, 'show'])->whereUuid('uuid');
    Route::post('/support-tickets/{uuid}/replies', [SupportTicketController::class, 'reply'])->whereUuid('uuid');
    Route::patch('/support-tickets/{uuid}/status', [SupportTicketController::class, 'updateStatus'])->whereUuid('uuid');

    // AUDIT LOGS
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{uuid}', [AuditLogController::class, 'show']);

    // REPORTS
    Route::get('/reports/sales-vat', [ReportController::class, 'salesVat']);
    Route::get('/reports/sales-vat/summary', [ReportController::class, 'salesVatSummary']);
    Route::get('/reports/sales-vat/export', [ReportController::class, 'salesVatExport']);

    // EMPLOYEE / STAFF
    Route::post('/services/assign-employee', [ServiceController::class, 'assignEmployee']);

    //SCHEDULES
    Route::get('/adl-attendances/qr', [AdlAttendanceController::class, 'generateQr']);
    Route::post('/schedules/action',  [ScheduleController::class, 'action']);

    // MEDICATION DOSAGE TRACKING
    Route::post('/medications/dosage', [MedicationController::class, 'dosage']);

    Route::post('/bookings/action', [BookingController::class, 'action']);
    Route::post('/invoices/action', [InvoiceController::class, 'action']);
    Route::get('/additional-charges', [AdditionalChargeController::class, 'index']);
    Route::post('/additional-charges', [AdditionalChargeController::class, 'store']);
    Route::get('/diagnosis-cases', [DiagnosisCaseController::class, 'index']);
    Route::get('/diagnosis-cases/patient-diagnoses', [DiagnosisCaseController::class, 'patientDiagnoses']);
    Route::post('/diagnosis-cases', [DiagnosisCaseController::class, 'store']);
    Route::put('/diagnosis-cases/{uuid}', [DiagnosisCaseController::class, 'update']);
    Route::post('/admissions/action', [PatientAdmissionController::class, 'action']);
    Route::get('/admissions/guardian-email-exists', [PatientAdmissionController::class, 'guardianEmailExists']);
    Route::get('/caregiver-shifts/board', [CaregiverShiftController::class, 'board']);
    Route::get('/caregiver-shifts', [CaregiverShiftController::class, 'index']);
    Route::post('/caregiver-shifts', [CaregiverShiftController::class, 'store']);
    Route::put('/caregiver-shifts/{id}', [CaregiverShiftController::class, 'update']);
    Route::post('/subscriptions/action', [SubscriptionController::class, 'action'])->middleware(BlockInSandbox::class);
    Route::apiResource('plans', PlanController::class)->only(['update']);
    Route::apiResource('employee-notes', EmployeeNoteController::class)->only(['index', 'store', 'update', 'destroy']);

    Route::get('/patient-access/action', [PatientAccessController::class, 'retrieveAction']);
    Route::post('/patient-access/action', [PatientAccessController::class, 'executeAction']);

    // WITHDRAWALS
    Route::post('/withdrawals/issue', [RefundController::class, 'issue']);

    // PORTAL (family/client-facing)
    Route::middleware('portal')->group(function () {
        Route::post('/withdrawals/action', [RefundController::class, 'store']);
        Route::post('/withdrawals/requests', [RefundController::class, 'index']);
        Route::post('/payments/action', [PaymentController::class, 'store']);
        Route::post('/payments/receipt', [PaymentController::class, 'receipt']);
        Route::get('/messages/conversations', [MessageController::class, 'clientIndex']);
        Route::get('/messages/contacts', [MessageController::class, 'clientContacts']);
        Route::post('/messages/open-contact', [MessageController::class, 'openContact']);
    });

    // OVERVIEW / STATS
    Route::post('/bookings/overview', [BookingController::class, 'overview']);
    Route::get('/invoices/overview', [InvoiceController::class, 'overview']);
    Route::get('/invoices/receipts', [InvoiceController::class, 'receipts']);
    Route::get('/invoices/bookings', [InvoiceController::class, 'bookings']);
    Route::get('/invoices/transactions', [InvoiceController::class, 'transactions']);
    Route::post('/contracts/overview', [BranchContractController::class, 'overview']);
    Route::get('/rooms/overview', [RoomController::class, 'overview']);
    Route::get('/branches/dashboard', [BranchController::class, 'dashboard']);

    // SUBSCRIPTION
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    Route::get('/subscriptions-detail',  [SubscriptionController::class, 'retrieveSubscriptionDetail']);
    Route::get('/subscriptions/payments/{reference}/invoice', [SubscriptionController::class, 'paymentInvoice']);

    Route::middleware(BlockInSandbox::class)->group(function () {
        Route::post('/subscriptions', [SubscriptionController::class, 'newSubscription']);
        Route::post('/subscriptions-trial', [SubscriptionController::class, 'startTrial']);
        Route::post('/subscriptions-validate',  [SubscriptionController::class, 'validateSubscription']);
        Route::post('/subscriptions-check-unique',  [SubscriptionController::class, 'checkUnique']);
        Route::post('/subscriptions-renew',  [SubscriptionController::class, 'renew']);
        Route::post('/subscriptions-branch', [SubscriptionController::class, 'newBranchFromCapacity']);
        Route::post('/subscriptions-branch-additional', [SubscriptionController::class, 'newAdditionalBranch']);
        Route::post('/subscriptions-branch-resubmit', [SubscriptionController::class, 'resubmitBranch']);
        Route::post('/subscriptions-branch-resubmit-purchase', [SubscriptionController::class, 'resubmitBranchWithPurchase']);
        Route::post('/subscriptions-apply-upgrade', [SubscriptionController::class, 'applyUpgrade']);
        Route::post('/subscriptions-cancel-test', [SubscriptionController::class, 'cancelTest']);
        Route::post('/subscriptions-end-test', [SubscriptionController::class, 'endTest']);
        Route::post('/subscriptions-card', [SubscriptionController::class, 'updateCard']);
        Route::post('/subscriptions-auto-renew', [SubscriptionController::class, 'setAutoRenew']);
        Route::post('/subscriptions-cancel-pending', [SubscriptionController::class, 'cancelPendingPlan']);
    });

    Route::post('/notifications/read', [NotificationController::class, 'markRead']);

    // MESSAGING (family portal <-> branch staff)
    Route::get('/messages/branch-conversations', [MessageController::class, 'branchIndex']);
    Route::get('/messages/staff-conversations', [MessageController::class, 'staffIndex']);
    Route::get('/messages/colleagues', [MessageController::class, 'colleagues']);
    Route::post('/messages/open-staff', [MessageController::class, 'openWithStaff']);
    Route::get('/messages/recipients', [MessageController::class, 'recipients']);
    Route::post('/messages/open', [MessageController::class, 'openWith']);
    Route::get('/messages/thread', [MessageController::class, 'thread']);
    Route::get('/messages/details', [MessageController::class, 'details']);
    Route::post('/messages/pin', [MessageController::class, 'pin']);
    Route::post('/messages', [MessageController::class, 'send']);

    Route::get('/users/branches',  [UserController::class, 'getUserBranch']);
    Route::get('/profile',  [UserController::class, 'profile']);
    Route::post('/profile',  [UserController::class, 'updateProfile']);
    Route::get('/profile/transactions',  [UserController::class, 'transactions']);
    Route::post('/onboarding/{area}',  [UserController::class, 'completeOnboarding'])->whereIn('area', ['portal']);
});

Route::get('/reviews/public',  [ReviewController::class, 'publicReviews']);


// PRIVATE API ROUTES
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/patients/{uuid}/report', [PatientController::class, 'report']);
    Route::post('/patients/{uuid}/diagnoses', [PatientController::class, 'storeDiagnosis']);
    Route::put('/patients/{uuid}/diagnoses/{diagnosis_uuid}', [PatientController::class, 'updateDiagnosis'])
        ->whereUuid(['uuid', 'diagnosis_uuid']);
    Route::put('/patients/{uuid}/assessments/{assessment_uuid}', [PatientController::class, 'updateAssessment'])
        ->whereUuid(['uuid', 'assessment_uuid']);

    Route::patch('/employees/{uuid}/status', [EmployeeController::class, 'updateStatus']);

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
        'adl-attendances' => AdlAttendanceController::class,
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
Route::get('/ip-locate', [NominatimController::class, 'ipLocate']);
Route::get('/geocode', [NominatimController::class, 'geocode']);
Route::get('/reverse-geocode', [NominatimController::class, 'reverse']);
Route::get('/nereast-street', [NominatimController::class, 'nearest']);
Route::get('/locations/search', [NominatimController::class, 'searchLocation']);
