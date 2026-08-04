<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\MercadoPagoWebhookController;
use App\Http\Controllers\Api\V1\MonthlySummaryController;
use App\Http\Controllers\Api\V1\PasswordResetController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\TransactionController;
use App\Http\Controllers\Api\V1\TutorialController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
            ->middleware('signed')
            ->name('verification.verify');
        Route::post('/forgot-password', [PasswordResetController::class, 'forgotPassword'])->middleware('throttle:5,1');
        Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
    });

    Route::post('/webhooks/mercadopago', [MercadoPagoWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend']);
        Route::patch('/users/me', [AuthController::class, 'updateProfile']);
        Route::patch('/users/me/preferences', [AuthController::class, 'updatePreferences']);
        Route::put('/users/me/tutorial', [TutorialController::class, 'update']);

        Route::middleware('verified')->group(function (): void {
            Route::apiResource('categories', CategoryController::class);
            Route::apiResource('transactions', TransactionController::class);

            Route::get('/plans', [BillingController::class, 'plans']);
            Route::get('/subscription', [BillingController::class, 'subscription']);

            Route::get('/dashboard/summary', [DashboardController::class, 'summary']);
            Route::get('/dashboard/chart', [DashboardController::class, 'chart']);
            Route::get('/dashboard/recent', [DashboardController::class, 'recent']);
            Route::get('/dashboard/by-category', [DashboardController::class, 'byCategory']);
            Route::get('/dashboard/by-day', [DashboardController::class, 'byDay']);
            Route::get('/dashboard/installments-total', [DashboardController::class, 'installmentsTotal']);
            Route::get('/dashboard/monthly-comparison', [DashboardController::class, 'monthlyComparison']);
            Route::get('/dashboard/expenses-mtd-comparison', [DashboardController::class, 'mtdComparison']);
            Route::get('/dashboard/by-payment-method', [DashboardController::class, 'byPaymentMethod']);
            Route::get('/dashboard/weekly-expenses', [DashboardController::class, 'weeklyExpenses']);

            Route::get('/budgets', [BudgetController::class, 'index']);
            Route::post('/budgets', [BudgetController::class, 'store']);

            Route::get('/summaries/monthly', [MonthlySummaryController::class, 'show']);

            Route::get('/reports', [ReportController::class, 'index']);
            Route::post('/reports', [ReportController::class, 'store']);
            Route::get('/reports/{id}', [ReportController::class, 'show']);
            Route::get('/reports/{id}/download', [ReportController::class, 'download']);
        });
    });
});
