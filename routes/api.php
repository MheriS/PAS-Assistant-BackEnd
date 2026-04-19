<?php

use App\Http\Controllers\Api\RegistrationController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ChatbotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::get('/wbps/search', [\App\Http\Controllers\Api\WBPController::class, 'search']);
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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
