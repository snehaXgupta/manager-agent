<?php

use App\Http\Controllers\ManagerPerformanceController;
use App\Http\Controllers\TimerController;
use App\Http\Controllers\Api\ManagerReportController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\PredictiveAnalyticsController;
use App\Http\Controllers\Api\GitWebhookController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth.developer')->group(function () {
    Route::post('/timer/start', [TimerController::class, 'start']);
    Route::post('/timer/stop', [TimerController::class, 'stop']);
    Route::get('/managers/{id}/performance', [ManagerPerformanceController::class, 'show']);

    // Performance Reports API
    Route::get('/managers/{id}/reports', [ManagerReportController::class, 'index']);
    Route::get('/managers/{id}/reports/{reportId}', [ManagerReportController::class, 'show']);
    Route::post('/managers/{id}/generate-report', [ManagerReportController::class, 'store']);

    // Employee Reports API
    Route::get('/employees/{id}/performance', [\App\Http\Controllers\Api\EmployeeReportController::class, 'showPerformance']);
    Route::get('/employees/{id}/reports', [\App\Http\Controllers\Api\EmployeeReportController::class, 'index']);
    Route::get('/employees/{id}/reports/{reportId}', [\App\Http\Controllers\Api\EmployeeReportController::class, 'show']);
    Route::post('/employees/{id}/generate-report', [\App\Http\Controllers\Api\EmployeeReportController::class, 'store']);

    // Leaderboard API
    Route::get('/leaderboard', [LeaderboardController::class, 'index']);

    // Predictive Analytics API
    Route::get('/managers/{id}/predictive-health', [PredictiveAnalyticsController::class, 'getHealth']);
    Route::get('/managers/{id}/predictive-risks', [PredictiveAnalyticsController::class, 'getRisks']);
    Route::get('/managers/{id}/workload-distribution', [PredictiveAnalyticsController::class, 'getWorkload']);
    Route::post('/managers/{id}/generate-predictive-report', [PredictiveAnalyticsController::class, 'generateReport']);
});

// Public GitLab Webhook endpoint
Route::post('/webhooks/gitlab', [\App\Http\Controllers\Api\GitlabWebhookController::class, 'handle']);

// Public Fireflies Webhook endpoint
Route::post('/webhooks/fireflies', [\App\Http\Controllers\Api\FirefliesWebhookController::class, 'handle'])->name('api.webhooks.fireflies');

// Public Git platform webhook receiver
Route::post('/webhooks/{platform}', [GitWebhookController::class, 'handle']);

