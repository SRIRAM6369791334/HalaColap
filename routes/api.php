<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DailyBillingController;
use App\Http\Controllers\Api\DealerController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfitController;
use App\Http\Controllers\Api\PurchaseController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\VendorController;
use App\Http\Controllers\Api\WeeklyBillingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\BirdBatchController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\BatchController;
use App\Http\Controllers\Api\ConsumptionController;
use App\Http\Controllers\Api\MortalityController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\InventoryAnalyticsController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\TenantInvitationController;
use Illuminate\Support\Facades\Route;

if (!function_exists('apiPermissionResource')) {
    function apiPermissionResource($name, $controller, $permission, $options = []) {
        $only = $options['only'] ?? ['index', 'store', 'show', 'update', 'destroy'];
        $except = $options['except'] ?? [];
        $methods = array_diff($only, $except);

        $view = array_intersect($methods, ['index', 'show']);
        $create = array_intersect($methods, ['store']);
        $edit = array_intersect($methods, ['update']);
        $delete = array_intersect($methods, ['destroy']);

        if (!empty($view)) {
            Route::apiResource($name, $controller)->only($view)->middleware("permission:view {$permission}");
        }
        if (!empty($create)) {
            Route::apiResource($name, $controller)->only($create)->middleware("permission:create {$permission}");
        }
        if (!empty($edit)) {
            Route::apiResource($name, $controller)->only($edit)->middleware("permission:edit {$permission}");
        }
        if (!empty($delete)) {
            Route::apiResource($name, $controller)->only($delete)->middleware("permission:delete {$permission}");
        }
    }
}

/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (V1)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->as('api.v1.')->group(function () {

    // Public Routes (no auth required)
    Route::get('docs', function () {
        return response('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>MSR BROILERS Management V1 API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css" />
    <style>
        body { margin: 0; background: #0f172a; }
        .swagger-ui { filter: invert(88%) hue-rotate(180deg); }
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js" charset="UTF-8"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js" charset="UTF-8"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: "/docs/openapi.yaml",
                dom_id: "#swagger-ui",
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout",
                deepLinking: true,
                showExtensions: true,
                filter: true
            });
        };
    </script>
</body>
</html>', 200, ['Content-Type' => 'text/html']);
    });

    Route::post('auth/login', [AuthController::class, 'login']);

    // Protected Routes (Sanctum + Permission Middleware)
    Route::middleware('auth:sanctum')->group(function () {

        // Session/Auth — no extra permission needed
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/profile', [AuthController::class, 'profile']);
        Route::post('tenant-invitations/{token}/accept', [TenantInvitationController::class, 'accept']);

        // Dashboard & Alerts — view dashboard
        Route::middleware('permission:view analytics')->group(function () {
            Route::get('dashboard', [DashboardController::class, 'index']);
            Route::get('dashboard/alerts', [DashboardController::class, 'alerts']);
        });

        // Customers — granular permissions
        apiPermissionResource('masters/customers', CustomerController::class, 'customers');
        Route::get('masters/customers/{customer}/billing-history', [CustomerController::class, 'billingHistory'])->middleware('permission:view customers');
        Route::get('masters/customers/{customer}/payment-history', [CustomerController::class, 'paymentHistory'])->middleware('permission:view customers');

        // Dealers — granular permissions
        apiPermissionResource('masters/dealers', DealerController::class, 'dealers');
        Route::get('masters/dealers/{dealer}/purchase-history', [DealerController::class, 'purchaseHistory'])->middleware('permission:view dealers');

        // Vendors — granular permissions
        apiPermissionResource('masters/vendors', VendorController::class, 'vendors');
        Route::get('masters/vendors/{vendor}/purchase-history', [VendorController::class, 'purchaseHistory'])->middleware('permission:view vendors');

        // Routes & Warehouses — granular permissions
        apiPermissionResource('masters/routes', RouteController::class, 'routes', [
            'only' => ['index', 'store', 'update', 'destroy'],
        ]);
        apiPermissionResource('masters/warehouses', WarehouseController::class, 'warehouses');

        // Billing — Daily & Weekly
        Route::apiResource('billing/daily', DailyBillingController::class)->only(['index', 'show'])->middleware('permission:view bills');
        Route::apiResource('billing/daily', DailyBillingController::class)->only(['store'])->middleware('permission:create bills');
        Route::apiResource('billing/daily', DailyBillingController::class)->only(['destroy'])->middleware('permission:delete bills');

        Route::apiResource('billing/weekly', WeeklyBillingController::class)->only(['index', 'show'])->middleware('permission:view bills');
        Route::apiResource('billing/weekly', WeeklyBillingController::class)->only(['store'])->middleware('permission:create bills');
        Route::apiResource('billing/weekly', WeeklyBillingController::class)->only(['destroy'])->middleware('permission:delete bills');
        Route::post('billing/weekly/bulk', [WeeklyBillingController::class, 'bulkStore'])->middleware('permission:create bills');
        Route::get('billing/weekly/{weekly_bill}/share-url', [WeeklyBillingController::class, 'shareUrl'])->middleware('permission:view bills');

        // Purchases — granular permissions
        apiPermissionResource('purchases', PurchaseController::class, 'purchases');

        // Payments — granular permissions
        Route::get('payments/customers', [PaymentController::class, 'indexCustomers'])->middleware('permission:view payments');
        Route::post('payments/customers', [PaymentController::class, 'storeCustomerPayment'])->middleware('permission:create payments');
        Route::get('payments/dealers', [PaymentController::class, 'indexDealers'])->middleware('permission:view payments');
        Route::post('payments/dealers', [PaymentController::class, 'storeDealerPayment'])->middleware('permission:create payments');
        Route::get('payments/dealers/{dealer}/ledger', [PaymentController::class, 'dealerLedger'])->middleware('permission:view payments');

        // Expenses & EMIs — granular permissions
        Route::get('expenses/categories', [ExpenseController::class, 'categories'])->middleware('permission:view expenses');
        Route::get('expenses/emis', [ExpenseController::class, 'emisIndex'])->middleware('permission:view expenses');
        Route::post('expenses/emis', [ExpenseController::class, 'storeEmi'])->middleware('permission:create expenses');
        Route::delete('expenses/emis/{emi}', [ExpenseController::class, 'destroyEmi'])->middleware('permission:delete expenses');
        Route::get('expenses/alerts', [ExpenseController::class, 'emisAlerts'])->middleware('permission:view expenses');
        apiPermissionResource('expenses', ExpenseController::class, 'expenses', [
            'only' => ['index', 'store', 'destroy'],
        ]);

        // Livestock Cycle & Mortality — granular permissions
        apiPermissionResource('batches', BatchController::class, 'stock');
        Route::post('bird-batches/{batch}/mortality', [BirdBatchController::class, 'recordMortality'])->middleware('permission:create stock');
        apiPermissionResource('bird-batches', BirdBatchController::class, 'stock', [
            'only' => ['index', 'store'],
        ]);
        apiPermissionResource('mortalities', MortalityController::class, 'stock', [
            'only' => ['index', 'store', 'destroy'],
        ]);

        // Inventory — Items & Stock — granular permissions
        apiPermissionResource('items', ItemController::class, 'stock');
        apiPermissionResource('consumptions', ConsumptionController::class, 'stock', [
            'only' => ['index', 'store', 'destroy'],
        ]);
        Route::get('stock', [StockController::class, 'index'])->middleware('permission:view stock');
        Route::get('stock/movements', [StockController::class, 'movements'])->middleware('permission:view stock');
        Route::post('stock/adjust', [StockController::class, 'adjust'])->middleware('permission:edit stock');
        Route::get('inventory/analytics', [InventoryAnalyticsController::class, 'index'])->middleware('permission:view stock');

        // Financial / Profit
        Route::middleware('permission:view reports|view analytics')->group(function () {
            Route::get('profit', [ProfitController::class, 'index']);
            Route::get('profit/monthly', [ProfitController::class, 'monthly']);
            Route::get('profit/expense-vs-income', [ProfitController::class, 'expenseVsIncome']);
        });

        // Reports
        Route::middleware('permission:view reports')->group(function () {
            Route::get('reports', [ReportController::class, 'index']);
            Route::get('reports/sales/daily', [ReportController::class, 'salesDaily']);
            Route::get('reports/sales/weekly', [ReportController::class, 'salesWeekly']);
            Route::get('reports/sales/monthly', [ReportController::class, 'salesMonthly']);
            Route::get('reports/purchases/daily', [ReportController::class, 'purchasesDaily']);
            Route::get('reports/purchases/weekly', [ReportController::class, 'purchasesWeekly']);
            Route::get('reports/purchases/monthly', [ReportController::class, 'purchasesMonthly']);
            Route::get('reports/purchases/vendor-analytics', [ReportController::class, 'vendorAnalytics']);
            Route::get('reports/customers/ranking', [ReportController::class, 'customerRanking']);
            Route::get('reports/purchases/analytics', [ReportController::class, 'purchaseAnalytics']);
        });

        // User Management (Admin only)
        Route::middleware('permission:manage users')->group(function () {
            Route::get('users/identity-status', [UserManagementController::class, 'identityStatus']);
            Route::post('users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus']);
            Route::apiResource('users', UserManagementController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

    });
});
