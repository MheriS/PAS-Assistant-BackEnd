<?php

use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ChatbotController;
use App\Http\Controllers\Api\VisitorController;
use App\Http\Controllers\Api\MedicineDeliveryController;
use App\Http\Controllers\MoneyDepositController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/wbps', [\App\Http\Controllers\Api\WBPController::class, 'index']);
Route::get('/wbps/generate-no-reg', [\App\Http\Controllers\Api\WBPController::class, 'generateNoRegs']);
Route::post('/wbps', [\App\Http\Controllers\Api\WBPController::class, 'store']);
Route::patch('/wbps/{id}', [\App\Http\Controllers\Api\WBPController::class, 'update']);
Route::get('/wbps/search', [\App\Http\Controllers\Api\WBPController::class, 'search']);
Route::post('/wbps/{id}/movement', [\App\Http\Controllers\Api\WBPController::class, 'recordMovement']);
Route::post('/chatbot', [ChatbotController::class, 'chat']);

Route::get('/registrations', [RegistrationController::class, 'index']);
Route::post('/registrations', [RegistrationController::class, 'store']);
Route::patch('/registrations/{id}', [RegistrationController::class, 'update']);
Route::get('/registrations/status/{nik}', [RegistrationController::class, 'checkStatus']);
Route::get('/registrations/schedule/upcoming', [RegistrationController::class, 'getUpcomingSchedule']);
Route::get('/registrations/schedule/{date}', [RegistrationController::class, 'getSchedule']);

Route::get('/visitors/{nik}', [VisitorController::class, 'show']);
Route::post('/visitors', [VisitorController::class, 'store']);

Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);

// Visit Slots Routes
Route::prefix('visit-slots')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\VisitSlotController::class, 'index']);
    Route::get('/available-dates', [\App\Http\Controllers\Api\VisitSlotController::class, 'getAvailableDates']);
    Route::get('/available-times/{date}', [\App\Http\Controllers\Api\VisitSlotController::class, 'getAvailableTimes']);
    Route::post('/', [\App\Http\Controllers\Api\VisitSlotController::class, 'store']);
    Route::delete('/{id}', [\App\Http\Controllers\Api\VisitSlotController::class, 'destroy']);
    Route::patch('/{id}/toggle', [\App\Http\Controllers\Api\VisitSlotController::class, 'toggleAvailability']);
});

// Medicine Delivery Routes
Route::prefix('medicine-deliveries')->group(function () {
    Route::get('/', [MedicineDeliveryController::class, 'index']);
    Route::get('/rules', [MedicineDeliveryController::class, 'getRules']);
    Route::post('/', [MedicineDeliveryController::class, 'store']);
    Route::patch('/{id}/approval', [MedicineDeliveryController::class, 'updateApproval']);
    Route::patch('/{id}/delivery', [MedicineDeliveryController::class, 'updateDelivery']);
});

Route::prefix('money-deposits')->group(function () {
    Route::get('/', [MoneyDepositController::class, 'index']);
    Route::post('/', [MoneyDepositController::class, 'store']);
    Route::patch('/{id}/status', [MoneyDepositController::class, 'updateStatus']);
    Route::delete('/{id}', [MoneyDepositController::class, 'destroy']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
