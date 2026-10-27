# 08 — Complete Route Documentation

> **MSR BROILERS Management System** — Flockwise BizTrack  
> Routes defined in: `routes/web.php`, `routes/api.php`, `routes/console.php`

---

## 1. Web Routes (`routes/web.php`)

### 1.1 Auth Routes

```php
Route::middleware('guest:web')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'register'])->name('register');
    Route::post('register', [AuthController::class, 'registerUser']);
});

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
```

| Method | URI | Name | Middleware | Controller |
|---|---|---|---|---|
| GET | `/login` | `login` | `guest:web` | `Auth\AuthController@showLoginForm` |
| POST | `/login` | — | `guest:web` | `Auth\AuthController@login` |
| GET | `/register` | `register` | `guest:web` | `Auth\AuthController@register` |
| POST | `/register` | — | `guest:web` | `Auth\AuthController@registerUser` |
| POST | `/logout` | `logout` | `auth` | `Auth\AuthController@logout` |

---

### 1.2 Dashboard

```php
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
```

| Method | URI | Name | Middleware | Controller |
|---|---|---|---|---|
| GET | `/` | `dashboard` | `auth` | `DashboardController@index` |

---

### 1.3 Masters Module

```php
Route::prefix('masters')->name('masters.')->middleware('auth')->group(function () {
    // Customers
    Route::get('customers/search', ...)->name('customers.search');
    Route::get('customers/export-pdf', ...)->name('customers.exportPDF');
    Route::resource('customers', CustomerController::class)->except(['create', 'show']);

    // Dealers
    Route::get('dealers/search', ...)->name('dealers.search');
    Route::resource('dealers', DealerController::class)->except(['create', 'show']);

    // Vendors
    Route::get('vendors/search', ...)->name('vendors.search');
    Route::resource('vendors', VendorController::class)->except(['create', 'show']);

    // Routes
    Route::resource('routes', RouteController::class)->except(['create', 'show']);
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/masters/customers/search` | `masters.customers.search` | `Masters\CustomerController@search` |
| GET | `/masters/customers/export-pdf` | `masters.customers.exportPDF` | `Masters\CustomerController@exportPDF` |
| GET | `/masters/customers` | `masters.customers.index` | `Masters\CustomerController@index` |
| POST | `/masters/customers` | `masters.customers.store` | `Masters\CustomerController@store` |
| GET | `/masters/customers/{customer}/edit` | `masters.customers.edit` | `Masters\CustomerController@edit` |
| PUT/PATCH | `/masters/customers/{customer}` | `masters.customers.update` | `Masters\CustomerController@update` |
| DELETE | `/masters/customers/{customer}` | `masters.customers.destroy` | `Masters\CustomerController@destroy` |
| GET | `/masters/dealers/search` | `masters.dealers.search` | `Masters\DealerController@search` |
| GET | `/masters/dealers` | `masters.dealers.index` | `Masters\DealerController@index` |
| POST | `/masters/dealers` | `masters.dealers.store` | `Masters\DealerController@store` |
| GET | `/masters/dealers/{dealer}/edit` | `masters.dealers.edit` | `Masters\DealerController@edit` |
| PUT/PATCH | `/masters/dealers/{dealer}` | `masters.dealers.update` | `Masters\DealerController@update` |
| DELETE | `/masters/dealers/{dealer}` | `masters.dealers.destroy` | `Masters\DealerController@destroy` |
| GET | `/masters/vendors/search` | `masters.vendors.search` | `Masters\VendorController@search` |
| GET | `/masters/vendors` | `masters.vendors.index` | `Masters\VendorController@index` |
| POST | `/masters/vendors` | `masters.vendors.store` | `Masters\VendorController@store` |
| GET | `/masters/vendors/{vendor}/edit` | `masters.vendors.edit` | `Masters\VendorController@edit` |
| PUT/PATCH | `/masters/vendors/{vendor}` | `masters.vendors.update` | `Masters\VendorController@update` |
| DELETE | `/masters/vendors/{vendor}` | `masters.vendors.destroy` | `Masters\VendorController@destroy` |
| GET | `/masters/routes` | `masters.routes.index` | `Masters\RouteController@index` |
| POST | `/masters/routes` | `masters.routes.store` | `Masters\RouteController@store` |
| GET | `/masters/routes/{route}/edit` | `masters.routes.edit` | `Masters\RouteController@edit` |
| PUT/PATCH | `/masters/routes/{route}` | `masters.routes.update` | `Masters\RouteController@update` |
| DELETE | `/masters/routes/{route}` | `masters.routes.destroy` | `Masters\RouteController@destroy` |

---

### 1.4 Bird Batches

```php
Route::resource('bird-batches', BirdBatchController::class)->middleware('auth');
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/bird-batches` | `bird-batches.index` | `BirdBatchController@index` |
| POST | `/bird-batches` | `bird-batches.store` | `BirdBatchController@store` |
| GET | `/bird-batches/{birdBatch}` | `bird-batches.show` | `BirdBatchController@show` |
| GET | `/bird-batches/{birdBatch}/edit` | `bird-batches.edit` | `BirdBatchController@edit` |
| PUT/PATCH | `/bird-batches/{birdBatch}` | `bird-batches.update` | `BirdBatchController@update` |
| DELETE | `/bird-batches/{birdBatch}` | `bird-batches.destroy` | `BirdBatchController@destroy` |

---

### 1.5 Stock

```php
Route::resource('stock', StockController::class)->middleware(['auth', 'permission:stock']);
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/stock` | `stock.index` | `StockController@index` |
| POST | `/stock` | `stock.store` | `StockController@store` |
| GET | `/stock/{stockItem}/edit` | `stock.edit` | `StockController@edit` |
| PUT/PATCH | `/stock/{stockItem}` | `stock.update` | `StockController@update` |
| DELETE | `/stock/{stockItem}` | `stock.destroy` | `StockController@destroy` |

---

### 1.6 Expenses

```php
Route::resource('expenses', ExpenseController::class)->middleware('auth');
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/expenses` | `expenses.index` | `ExpenseController@index` |
| POST | `/expenses` | `expenses.store` | `ExpenseController@store` |
| GET | `/expenses/{expense}/edit` | `expenses.edit` | `ExpenseController@edit` |
| PUT/PATCH | `/expenses/{expense}` | `expenses.update` | `ExpenseController@update` |
| DELETE | `/expenses/{expense}` | `expenses.destroy` | `ExpenseController@destroy` |

---

### 1.7 Purchases

```php
Route::get('purchases/search', ...)->name('purchases.search')->middleware('auth');
Route::get('purchases/export-pdf', ...)->name('purchases.exportPDF')->middleware('auth');
Route::resource('purchases', PurchaseController::class)->middleware('auth');
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/purchases/search` | `purchases.search` | `Purchases\PurchaseController@search` |
| GET | `/purchases/export-pdf` | `purchases.exportPDF` | `Purchases\PurchaseController@exportPDF` |
| GET | `/purchases` | `purchases.index` | `Purchases\PurchaseController@index` |
| POST | `/purchases` | `purchases.store` | `Purchases\PurchaseController@store` |
| GET | `/purchases/{purchase}` | `purchases.show` | `Purchases\PurchaseController@show` |
| GET | `/purchases/{purchase}/edit` | `purchases.edit` | `Purchases\PurchaseController@edit` |
| PUT/PATCH | `/purchases/{purchase}` | `purchases.update` | `Purchases\PurchaseController@update` |
| DELETE | `/purchases/{purchase}` | `purchases.destroy` | `Purchases\PurchaseController@destroy` |

---

### 1.8 Capital Transactions

```php
Route::get('capital-transactions/export-pdf', ...)->name('capital-transactions.exportPDF');
Route::resource('capital-transactions', CapitalTransactionController::class)->middleware(['auth', 'permission:capital']);
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/capital-transactions/export-pdf` | `capital-transactions.exportPDF` | `CapitalTransactionController@exportPDF` |
| GET | `/capital-transactions` | `capital-transactions.index` | `CapitalTransactionController@index` |
| POST | `/capital-transactions` | `capital-transactions.store` | `CapitalTransactionController@store` |
| GET | `/capital-transactions/{capitalTransaction}/edit` | `capital-transactions.edit` | `CapitalTransactionController@edit` |
| PUT/PATCH | `/capital-transactions/{capitalTransaction}` | `capital-transactions.update` | `CapitalTransactionController@update` |
| DELETE | `/capital-transactions/{capitalTransaction}` | `capital-transactions.destroy` | `CapitalTransactionController@destroy` |

---

### 1.9 Cash/Bank Ledger

```php
Route::resource('cash-bank-ledgers', CashBankLedgerController::class)->middleware(['auth', 'permission:cashbank']);
Route::post('cash-bank-ledgers/{cashBankLedger}/approve', ...)->name('cash-bank-ledgers.approve');
Route::post('cash-bank-ledgers/{cashBankLedger}/unapprove', ...)->name('cash-bank-ledgers.unapprove');
Route::post('cash-bank-ledgers/{cashBankLedger}/approve-amount', ...)->name('cash-bank-ledgers.approveAmount');
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/cash-bank-ledgers` | `cash-bank-ledgers.index` | `CashBankLedgerController@index` |
| POST | `/cash-bank-ledgers` | `cash-bank-ledgers.store` | `CashBankLedgerController@store` |
| GET | `/cash-bank-ledgers/{cashBankLedger}/edit` | `cash-bank-ledgers.edit` | `CashBankLedgerController@edit` |
| PUT/PATCH | `/cash-bank-ledgers/{cashBankLedger}` | `cash-bank-ledgers.update` | `CashBankLedgerController@update` |
| DELETE | `/cash-bank-ledgers/{cashBankLedger}` | `cash-bank-ledgers.destroy` | `CashBankLedgerController@destroy` |
| POST | `/cash-bank-ledgers/{cashBankLedger}/approve` | `cash-bank-ledgers.approve` | `CashBankLedgerController@approve` |
| POST | `/cash-bank-ledgers/{cashBankLedger}/unapprove` | `cash-bank-ledgers.unapprove` | `CashBankLedgerController@unapprove` |
| POST | `/cash-bank-ledgers/{cashBankLedger}/approve-amount` | `cash-bank-ledgers.approveAmount` | `CashBankLedgerController@approveAmount` |

---

### 1.10 Payments

```php
Route::prefix('payments')->name('payments.')->middleware(['auth', 'permission:payments'])->group(function () {
    Route::get('customer', ...)->name('customer.index');
    Route::post('customer', ...)->name('customer.store');
    Route::put('customer/{customerPayment}', ...)->name('customer.update');
    Route::delete('customer/{customerPayment}', ...)->name('customer.destroy');
    Route::post('customer/{customerPayment}/adjust', ...)->name('customer.adjust');

    Route::get('dealer', ...)->name('dealer.index');
    Route::post('dealer', ...)->name('dealer.store');
    Route::put('dealer/{dealerPayment}', ...)->name('dealer.update');
    Route::delete('dealer/{dealerPayment}', ...)->name('dealer.destroy');
    Route::post('dealer/{dealerPayment}/adjust', ...)->name('dealer.adjust');

    Route::get('vendor', ...)->name('vendor.index');
    Route::post('vendor', ...)->name('vendor.store');
    Route::put('vendor/{vendorPayment}', ...)->name('vendor.update');
    Route::delete('vendor/{vendorPayment}', ...)->name('vendor.destroy');
    Route::post('vendor/{vendorPayment}/adjust', ...)->name('vendor.adjust');
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/payments/customer` | `payments.customer.index` | `Payments\CustomerPaymentController@index` |
| POST | `/payments/customer` | `payments.customer.store` | `Payments\CustomerPaymentController@store` |
| PUT | `/payments/customer/{customerPayment}` | `payments.customer.update` | `Payments\CustomerPaymentController@update` |
| DELETE | `/payments/customer/{customerPayment}` | `payments.customer.destroy` | `Payments\CustomerPaymentController@destroy` |
| POST | `/payments/customer/{customerPayment}/adjust` | `payments.customer.adjust` | `Payments\CustomerPaymentController@adjust` |
| GET | `/payments/dealer` | `payments.dealer.index` | `Payments\DealerPaymentController@index` |
| POST | `/payments/dealer` | `payments.dealer.store` | `Payments\DealerPaymentController@store` |
| PUT | `/payments/dealer/{dealerPayment}` | `payments.dealer.update` | `Payments\DealerPaymentController@update` |
| DELETE | `/payments/dealer/{dealerPayment}` | `payments.dealer.destroy` | `Payments\DealerPaymentController@destroy` |
| POST | `/payments/dealer/{dealerPayment}/adjust` | `payments.dealer.adjust` | `Payments\DealerPaymentController@adjust` |
| GET | `/payments/vendor` | `payments.vendor.index` | `Payments\VendorPaymentController@index` |
| POST | `/payments/vendor` | `payments.vendor.store` | `Payments\VendorPaymentController@store` |
| PUT | `/payments/vendor/{vendorPayment}` | `payments.vendor.update` | `Payments\VendorPaymentController@update` |
| DELETE | `/payments/vendor/{vendorPayment}` | `payments.vendor.destroy` | `Payments\VendorPaymentController@destroy` |
| POST | `/payments/vendor/{vendorPayment}/adjust` | `payments.vendor.adjust` | `Payments\VendorPaymentController@adjust` |

---

### 1.11 Billing Module

```php
Route::prefix('billing')->name('billing.')->middleware('auth')->group(function () {
    // Daily Billing
    Route::get('daily', ...)->name('daily.index');
    Route::post('daily', ...)->name('daily.store');
    Route::get('daily/{dailyBill}', ...)->name('daily.show');
    Route::get('daily/{dailyBill}/edit', ...)->name('daily.edit');
    Route::put('daily/{dailyBill}', ...)->name('daily.update');
    Route::delete('daily/{dailyBill}', ...)->name('daily.destroy');
    Route::get('daily/{dailyBill}/print', ...)->name('daily.printInvoice');
    Route::get('daily/{dailyBill}/export', ...)->name('daily.exportInvoice');
    Route::get('daily/dealer-summary', ...)->name('daily.getDealerSummary');

    // Daily Invoices
    Route::get('daily-invoices', ...)->name('dailyInvoices.index');
    Route::post('daily-invoices', ...)->name('dailyInvoices.store');
    Route::get('daily-invoices/{id}', ...)->name('dailyInvoices.show');
    Route::put('daily-invoices/{id}', ...)->name('dailyInvoices.update');
    Route::delete('daily-invoices/{id}', ...)->name('dailyInvoices.destroy');

    // Day Load Billing
    Route::get('dayload', ...)->name('dayload.index');
    Route::post('dayload', ...)->name('dayload.store');
    Route::get('dayload/{batch}', ...)->name('dayload.show');
    Route::put('dayload/{batch}', ...)->name('dayload.update');
    Route::delete('dayload/{batch}', ...)->name('dayload.destroy');
    Route::post('dayload/invoice/{invoiceId}/regenerate', ...)->name('dayload.regenerateInvoice');
    Route::post('dayload/{batch}/approve-weight-loss', ...)->name('dayload.approveWeightLoss');
    Route::post('dayload/{batch}/unapprove-weight-loss', ...)->name('dayload.unapproveWeightLoss');
    Route::post('dayload/entry/status', ...)->name('dayload.updateEntryStatus');

    // Weekly Billing
    Route::get('weekly', ...)->name('weekly.index');
    Route::post('weekly', ...)->name('weekly.store');
    Route::get('weekly/{weeklyBill}', ...)->name('weekly.show');
    Route::get('weekly/{weeklyBill}/edit', ...)->name('weekly.edit');
    Route::put('weekly/{weeklyBill}', ...)->name('weekly.update');
    Route::delete('weekly/{weeklyBill}', ...)->name('weekly.destroy');
    Route::get('weekly/{weeklyBill}/export', ...)->name('weekly.exportInvoice');
    Route::get('weekly/dealer-bill', ...)->name('weekly.exportDealerBillPDF');
    Route::get('weekly/dealer-bill/{dealer}', ...)->name('weekly.exportDealerBillPDFForDealer');
    Route::get('weekly/print-dealer-bill/{dealer}', ...)->name('weekly.printDealerBillPDF');
    Route::get('weekly/print-all-dealer-bills', ...)->name('weekly.printAllDealerBillsPDF');
    Route::get('weekly/download-all-dealer-bills-zip', ...)->name('weekly.downloadDealerBillsZip');
    Route::get('weekly/download-dealer-bill/{dealerId}', ...)->name('weekly.downloadDealerBillPDF');
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/billing/daily` | `billing.daily.index` | `Billing\DailyBillingController@index` |
| POST | `/billing/daily` | `billing.daily.store` | `Billing\DailyBillingController@store` |
| GET | `/billing/daily/{dailyBill}` | `billing.daily.show` | `Billing\DailyBillingController@show` |
| GET | `/billing/daily/{dailyBill}/edit` | `billing.daily.edit` | `Billing\DailyBillingController@edit` |
| PUT | `/billing/daily/{dailyBill}` | `billing.daily.update` | `Billing\DailyBillingController@update` |
| DELETE | `/billing/daily/{dailyBill}` | `billing.daily.destroy` | `Billing\DailyBillingController@destroy` |
| GET | `/billing/daily/{dailyBill}/print` | `billing.daily.printInvoice` | `Billing\DailyBillingController@printInvoice` |
| GET | `/billing/daily/{dailyBill}/export` | `billing.daily.exportInvoice` | `Billing\DailyBillingController@exportInvoice` |
| GET | `/billing/daily/dealer-summary` | `billing.daily.getDealerSummary` | `Billing\DailyBillingController@getDealerSummary` |
| GET | `/billing/daily-invoices` | `billing.dailyInvoices.index` | `Billing\DailyInvoiceController@index` |
| POST | `/billing/daily-invoices` | `billing.dailyInvoices.store` | `Billing\DailyInvoiceController@store` |
| GET | `/billing/daily-invoices/{id}` | `billing.dailyInvoices.show` | `Billing\DailyInvoiceController@show` |
| PUT | `/billing/daily-invoices/{id}` | `billing.dailyInvoices.update` | `Billing\DailyInvoiceController@update` |
| DELETE | `/billing/daily-invoices/{id}` | `billing.dailyInvoices.destroy` | `Billing\DailyInvoiceController@destroy` |
| GET | `/billing/dayload` | `billing.dayload.index` | `Billing\DayLoadBillingController@index` |
| POST | `/billing/dayload` | `billing.dayload.store` | `Billing\DayLoadBillingController@store` |
| GET | `/billing/dayload/{batch}` | `billing.dayload.show` | `Billing\DayLoadBillingController@show` |
| PUT | `/billing/dayload/{batch}` | `billing.dayload.update` | `Billing\DayLoadBillingController@update` |
| DELETE | `/billing/dayload/{batch}` | `billing.dayload.destroy` | `Billing\DayLoadBillingController@destroy` |
| POST | `/billing/dayload/invoice/{invoiceId}/regenerate` | `billing.dayload.regenerateInvoice` | `Billing\DayLoadBillingController@regenerateInvoice` |
| POST | `/billing/dayload/{batch}/approve-weight-loss` | `billing.dayload.approveWeightLoss` | `Billing\DayLoadBillingController@approveWeightLoss` |
| POST | `/billing/dayload/{batch}/unapprove-weight-loss` | `billing.dayload.unapproveWeightLoss` | `Billing\DayLoadBillingController@unapproveWeightLoss` |
| POST | `/billing/dayload/entry/status` | `billing.dayload.updateEntryStatus` | `Billing\DayLoadBillingController@updateEntryStatus` |
| GET | `/billing/weekly` | `billing.weekly.index` | `Billing\WeeklyBillingController@index` |
| POST | `/billing/weekly` | `billing.weekly.store` | `Billing\WeeklyBillingController@store` |
| GET | `/billing/weekly/{weeklyBill}` | `billing.weekly.show` | `Billing\WeeklyBillingController@show` |
| GET | `/billing/weekly/{weeklyBill}/edit` | `billing.weekly.edit` | `Billing\WeeklyBillingController@edit` |
| PUT | `/billing/weekly/{weeklyBill}` | `billing.weekly.update` | `Billing\WeeklyBillingController@update` |
| DELETE | `/billing/weekly/{weeklyBill}` | `billing.weekly.destroy` | `Billing\WeeklyBillingController@destroy` |
| GET | `/billing/weekly/{weeklyBill}/export` | `billing.weekly.exportInvoice` | `Billing\WeeklyBillingController@exportInvoice` |
| GET | `/billing/weekly/dealer-bill` | `billing.weekly.exportDealerBillPDF` | `Billing\WeeklyBillingController@exportDealerBillPDF` |
| GET | `/billing/weekly/dealer-bill/{dealer}` | `billing.weekly.exportDealerBillPDFForDealer` | `Billing\WeeklyBillingController@exportDealerBillPDFForDealer` |
| GET | `/billing/weekly/print-dealer-bill/{dealer}` | `billing.weekly.printDealerBillPDF` | `Billing\WeeklyBillingController@printDealerBillPDF` |
| GET | `/billing/weekly/print-all-dealer-bills` | `billing.weekly.printAllDealerBillsPDF` | `Billing\WeeklyBillingController@printAllDealerBillsPDF` |
| GET | `/billing/weekly/download-all-dealer-bills-zip` | `billing.weekly.downloadDealerBillsZip` | `Billing\WeeklyBillingController@downloadDealerBillsZip` |
| GET | `/billing/weekly/download-dealer-bill/{dealerId}` | `billing.weekly.downloadDealerBillPDF` | `Billing\WeeklyBillingController@downloadDealerBillPDF` |

---

### 1.12 Inventory Module

```php
Route::prefix('inventory')->name('inventory.')->middleware(['auth', 'permission:inventory'])->group(function () {
    // Items
    Route::get('items/analytics', ...)->name('items.analytics');
    Route::resource('items', ItemController::class)->except(['create', 'show']);

    // Batches
    Route::get('batches/analytics', ...)->name('batches.analytics');
    Route::post('batches/analytics/store', ...)->name('batches.analyticsStore');
    Route::resource('batches', BatchController::class)->except(['create', 'show']);

    // Warehouses
    Route::get('warehouses/analytics', ...)->name('warehouses.analytics');
    Route::resource('warehouses', WarehouseController::class)->except(['create', 'show']);

    // Stock Ledger
    Route::get('stock/analytics', ...)->name('stock.analytics');
    Route::get('stock/low-stock', ...)->name('stock.lowStock');
    Route::get('stock/search', ...)->name('stock.search');
    Route::resource('stock', StockController::class)->except(['create']);

    // Consumption
    Route::get('consumption/analytics', ...)->name('consumption.analytics');
    Route::resource('consumption', ConsumptionController::class)->except(['create', 'show']);

    // Mortality
    Route::get('mortality/analytics', ...)->name('mortality.analytics');
    Route::resource('mortality', MortalityController::class)->except(['create', 'show']);

    // Analytics
    Route::get('analytics', ...)->name('analytics.index');
    Route::get('analytics/search', ...)->name('analytics.search');
    Route::get('analytics/recent-activity', ...)->name('analytics.recentActivity');
    Route::get('analytics/stock-trends', ...)->name('analytics.stockTrends');
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/inventory/items/analytics` | `inventory.items.analytics` | `Inventory\ItemController@analytics` |
| GET | `/inventory/items` | `inventory.items.index` | `Inventory\ItemController@index` |
| POST | `/inventory/items` | `inventory.items.store` | `Inventory\ItemController@store` |
| GET | `/inventory/items/{item}/edit` | `inventory.items.edit` | `Inventory\ItemController@edit` |
| PUT/PATCH | `/inventory/items/{item}` | `inventory.items.update` | `Inventory\ItemController@update` |
| DELETE | `/inventory/items/{item}` | `inventory.items.destroy` | `Inventory\ItemController@destroy` |
| GET | `/inventory/batches/analytics` | `inventory.batches.analytics` | `Inventory\BatchController@analytics` |
| POST | `/inventory/batches/analytics/store` | `inventory.batches.analyticsStore` | `Inventory\BatchController@analyticsStore` |
| GET | `/inventory/batches` | `inventory.batches.index` | `Inventory\BatchController@index` |
| POST | `/inventory/batches` | `inventory.batches.store` | `Inventory\BatchController@store` |
| GET | `/inventory/batches/{batch}/edit` | `inventory.batches.edit` | `Inventory\BatchController@edit` |
| PUT/PATCH | `/inventory/batches/{batch}` | `inventory.batches.update` | `Inventory\BatchController@update` |
| DELETE | `/inventory/batches/{batch}` | `inventory.batches.destroy` | `Inventory\BatchController@destroy` |
| GET | `/inventory/warehouses/analytics` | `inventory.warehouses.analytics` | `Inventory\WarehouseController@analytics` |
| GET | `/inventory/warehouses` | `inventory.warehouses.index` | `Inventory\WarehouseController@index` |
| POST | `/inventory/warehouses` | `inventory.warehouses.store` | `Inventory\WarehouseController@store` |
| GET | `/inventory/warehouses/{warehouse}/edit` | `inventory.warehouses.edit` | `Inventory\WarehouseController@edit` |
| PUT/PATCH | `/inventory/warehouses/{warehouse}` | `inventory.warehouses.update` | `Inventory\WarehouseController@update` |
| DELETE | `/inventory/warehouses/{warehouse}` | `inventory.warehouses.destroy` | `Inventory\WarehouseController@destroy` |
| GET | `/inventory/stock/analytics` | `inventory.stock.analytics` | `Inventory\StockController@analytics` |
| GET | `/inventory/stock/low-stock` | `inventory.stock.lowStock` | `Inventory\StockController@lowStock` |
| GET | `/inventory/stock/search` | `inventory.stock.search` | `Inventory\StockController@search` |
| GET | `/inventory/stock` | `inventory.stock.index` | `Inventory\StockController@index` |
| POST | `/inventory/stock` | `inventory.stock.store` | `Inventory\StockController@store` |
| GET | `/inventory/stock/{entry}` | `inventory.stock.show` | `Inventory\StockController@show` |
| GET | `/inventory/stock/{entry}/edit` | `inventory.stock.edit` | `Inventory\StockController@edit` |
| PUT/PATCH | `/inventory/stock/{entry}` | `inventory.stock.update` | `Inventory\StockController@update` |
| DELETE | `/inventory/stock/{entry}` | `inventory.stock.destroy` | `Inventory\StockController@destroy` |
| GET | `/inventory/consumption/analytics` | `inventory.consumption.analytics` | `Inventory\ConsumptionController@analytics` |
| GET | `/inventory/consumption` | `inventory.consumption.index` | `Inventory\ConsumptionController@index` |
| POST | `/inventory/consumption` | `inventory.consumption.store` | `Inventory\ConsumptionController@store` |
| GET | `/inventory/consumption/{consumption}/edit` | `inventory.consumption.edit` | `Inventory\ConsumptionController@edit` |
| PUT/PATCH | `/inventory/consumption/{consumption}` | `inventory.consumption.update` | `Inventory\ConsumptionController@update` |
| DELETE | `/inventory/consumption/{consumption}` | `inventory.consumption.destroy` | `Inventory\ConsumptionController@destroy` |
| GET | `/inventory/mortality/analytics` | `inventory.mortality.analytics` | `Inventory\MortalityController@analytics` |
| GET | `/inventory/mortality` | `inventory.mortality.index` | `Inventory\MortalityController@index` |
| POST | `/inventory/mortality` | `inventory.mortality.store` | `Inventory\MortalityController@store` |
| GET | `/inventory/mortality/{mortality}/edit` | `inventory.mortality.edit` | `Inventory\MortalityController@edit` |
| PUT/PATCH | `/inventory/mortality/{mortality}` | `inventory.mortality.update` | `Inventory\MortalityController@update` |
| DELETE | `/inventory/mortality/{mortality}` | `inventory.mortality.destroy` | `Inventory\MortalityController@destroy` |
| GET | `/inventory/analytics` | `inventory.analytics.index` | `Inventory\AnalyticsController@index` |
| GET | `/inventory/analytics/search` | `inventory.analytics.search` | `Inventory\AnalyticsController@search` |
| GET | `/inventory/analytics/recent-activity` | `inventory.analytics.recentActivity` | `Inventory\AnalyticsController@recentActivity` |
| GET | `/inventory/analytics/stock-trends` | `inventory.analytics.stockTrends` | `Inventory\AnalyticsController@stockTrends` |

---

### 1.13 Reports & Search

```php
Route::middleware('auth')->group(function () {
    Route::get('/report', [ReportController::class, 'index'])->name('report.index');
    Route::get('/report/export-pdf', [ReportController::class, 'exportPDF'])->name('report.exportPDF');
    Route::get('/report/search', [ReportController::class, 'search'])->name('report.search');
    Route::get('/profit', [ProfitController::class, 'index'])->name('profit.index');
    Route::get('/search', [GlobalSearchController::class, 'search'])->name('search');
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/report` | `report.index` | `ReportController@index` |
| GET | `/report/export-pdf` | `report.exportPDF` | `ReportController@exportPDF` |
| GET | `/report/search` | `report.search` | `ReportController@search` |
| GET | `/profit` | `profit.index` | `ProfitController@index` |
| GET | `/search` | `search` | `GlobalSearchController@search` |

---

### 1.14 Notifications

```php
Route::middleware(['auth', 'permission:settings'])->group(function () {
    Route::get('/notifications/unread', ...)->name('notifications.unread');
    Route::get('/notifications/latest', ...)->name('notifications.latest');
    Route::post('/notifications/mark-all-read', ...)->name('notifications.markAllRead');
    Route::post('/notifications/{id}/read', ...)->name('notifications.markSingleRead');
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/notifications/unread` | `notifications.unread` | `NotificationController@getUnreadNotifications` |
| GET | `/notifications/latest` | `notifications.latest` | `NotificationController@getLatestNotifications` |
| POST | `/notifications/mark-all-read` | `notifications.markAllRead` | `NotificationController@markAllRead` |
| POST | `/notifications/{id}/read` | `notifications.markSingleRead` | `NotificationController@markSingleRead` |

---

### 1.15 Admin Module

```php
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    // Permissions
    Route::get('permissions', ...)->name('permissions.index');

    // Roles
    Route::resource('roles', RoleController::class)->except(['show', 'create']);

    // Users
    Route::post('users/{id}/status', ...)->name('users.updateStatus');
    Route::resource('users', UserManagementController::class)->except(['show', 'create']);
});
```

| Method | URI | Name | Controller |
|---|---|---|---|
| GET | `/admin/permissions` | `admin.permissions.index` | `Admin\PermissionController@index` |
| GET | `/admin/roles` | `admin.roles.index` | `Admin\RoleController@index` |
| POST | `/admin/roles` | `admin.roles.store` | `Admin\RoleController@store` |
| GET | `/admin/roles/{role}/edit` | `admin.roles.edit` | `Admin\RoleController@edit` |
| PUT/PATCH | `/admin/roles/{role}` | `admin.roles.update` | `Admin\RoleController@update` |
| DELETE | `/admin/roles/{role}` | `admin.roles.destroy` | `Admin\RoleController@destroy` |
| POST | `/admin/users/{id}/status` | `admin.users.updateStatus` | `Admin\UserManagementController@updateStatus` |
| GET | `/admin/users` | `admin.users.index` | `Admin\UserManagementController@index` |
| POST | `/admin/users` | `admin.users.store` | `Admin\UserManagementController@store` |
| GET | `/admin/users/{id}/edit` | `admin.users.edit` | `Admin\UserManagementController@edit` |
| PUT/PATCH | `/admin/users/{id}` | `admin.users.update` | `Admin\UserManagementController@update` |
| DELETE | `/admin/users/{id}` | `admin.users.destroy` | `Admin\UserManagementController@destroy` |

---

## 2. API Routes (`routes/api.php`)

All routes are prefixed with `/api` and grouped under version `v1`.

### 2.1 Public API Routes (No Auth)

| Method | URI | Controller | Description |
|---|---|---|---|
| POST | `/api/v1/login` | `Api\V1\AuthController@login` | Authenticate user, return Sanctum token |

---

### 2.2 Authenticated API Routes (`auth:sanctum`)

```php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [AuthController::class, 'profile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('dashboard', [DashboardController::class, 'index']);
    Route::get('users', [UserManagementController::class, 'index']);
    Route::get('users/{id}', [UserManagementController::class, 'show']);
    Route::get('permissions', [UserManagementController::class, 'getPermissions']);
    Route::get('roles', [UserManagementController::class, 'getRoles']);
    Route::get('inventory/analytics', [InventoryAnalyticsController::class, 'index']);
    Route::get('stock', [StockController::class, 'index']);
    Route::post('stock/in', [StockController::class, 'storeStockIn']);
    Route::post('stock/out', [StockController::class, 'storeStockOut']);
    Route::post('stock/adjust', [StockController::class, 'storeAdjustment']);
    Route::get('billing/dealer-summary', [WeeklyBillingController::class, 'getDealerSummary']);
    Route::post('billing/weekly-bill', [WeeklyBillingController::class, 'createDealerWeeklyBill']);
    Route::post('billing/vendor-dayload', [WeeklyBillingController::class, 'createVendorDayLoad']);
    Route::post('payments/dealer', [PaymentController::class, 'storeDealerPayment']);
    Route::post('payments/vendor', [PaymentController::class, 'storeVendorPayment']);
    Route::get('payments/dealer/{dealer}', [PaymentController::class, 'getDealerPayments']);
    Route::get('payments/vendor/{vendor}', [PaymentController::class, 'getVendorPayments']);
    Route::post('advances/dealer', [PaymentController::class, 'storeDealerAdvance']);
    Route::post('advances/vendor', [PaymentController::class, 'storeVendorAdvance']);
});
```

| Method | URI | Controller | Description |
|---|---|---|---|
| GET | `/api/v1/profile` | `Api\V1\AuthController@profile` | Get authenticated user profile |
| POST | `/api/v1/logout` | `Api\V1\AuthController@logout` | Revoke current token |
| GET | `/api/v1/dashboard` | `Api\V1\DashboardController@index` | Get dashboard summary |
| GET | `/api/v1/users` | `Api\V1\UserManagementController@index` | List users (paginated) |
| POST | `/api/v1/users` | `Api\V1\UserManagementController@store` | Create a user |
| GET | `/api/v1/users/{id}` | `Api\V1\UserManagementController@show` | Get a user |
| PUT | `/api/v1/users/{id}` | `Api\V1\UserManagementController@update` | Update a user |
| DELETE | `/api/v1/users/{id}` | `Api\V1\UserManagementController@destroy` | Delete a user |
| POST | `/api/v1/users/{id}/status` | `Api\V1\UserManagementController@updateStatus` | Toggle user status |
| GET | `/api/v1/permissions` | `Api\V1\UserManagementController@getPermissions` | List all permissions |
| GET | `/api/v1/roles` | `Api\V1\UserManagementController@getRoles` | List all roles |
| GET | `/api/v1/inventory/analytics` | `Api\V1\InventoryAnalyticsController@index` | Get inventory analytics |
| GET | `/api/v1/stock` | `Api\V1\StockController@index` | List stock levels |
| POST | `/api/v1/stock/in` | `Api\V1\StockController@storeStockIn` | Record stock IN |
| POST | `/api/v1/stock/out` | `Api\V1\StockController@storeStockOut` | Record stock OUT |
| POST | `/api/v1/stock/adjust` | `Api\V1\StockController@storeAdjustment` | Record stock adjustment |
| GET | `/api/v1/billing/dealer-summary` | `Api\V1\WeeklyBillingController@getDealerSummary` | Get dealer weekly summary |
| POST | `/api/v1/billing/weekly-bill` | `Api\V1\WeeklyBillingController@createDealerWeeklyBill` | Create weekly bill |
| POST | `/api/v1/billing/vendor-dayload` | `Api\V1\WeeklyBillingController@createVendorDayLoad` | Create vendor day-load |
| POST | `/api/v1/payments/dealer` | `Api\V1\PaymentController@storeDealerPayment` | Create dealer payment |
| POST | `/api/v1/payments/vendor` | `Api\V1\PaymentController@storeVendorPayment` | Create vendor payment |
| GET | `/api/v1/payments/dealer/{dealer}` | `Api\V1\PaymentController@getDealerPayments` | Get dealer payments |
| GET | `/api/v1/payments/vendor/{vendor}` | `Api\V1\PaymentController@getVendorPayments` | Get vendor payments |
| POST | `/api/v1/advances/dealer` | `Api\V1\PaymentController@storeDealerAdvance` | Create dealer advance |
| POST | `/api/v1/advances/vendor` | `Api\V1\PaymentController@storeVendorAdvance` | Create vendor advance |

---

## 3. Console Routes (`routes/console.php`)

```php
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
```

| Command | Description |
|---|---|
| `php artisan inspire` | Displays an inspiring quote |

---

## 4. Route Summary Statistics

| Category | Count |
|---|---|
| **Web Routes (unique)** | ~150 |
| **API Routes** | ~25 |
| **Console Commands** | 1 |
| **Resource Controllers** | 12 |
| **Custom Named Routes** | ~35 |
| **Middleware-Protected Routes** | ~145 (Web) + ~24 (API) |
| **PDF Export Routes** | 10 |
| **AJAX/JSON API Routes (Web)** | 12 |
| **Status Toggle Routes** | 3 |

---

## 5. Route-Controller Mapping

| Route Prefix | Controller | Middleware |
|---|---|---|
| `/` | DashboardController | auth |
| `/masters/*` | Masters\*Controller | auth, permission:* |
| `/bird-batches` | BirdBatchController | auth, permission:batches |
| `/stock` | StockController (Web) | auth, permission:stock |
| `/expenses` | ExpenseController | auth |
| `/purchases` | PurchaseController | auth, permission:purchases |
| `/capital-transactions` | CapitalTransactionController | auth, permission:capital |
| `/cash-bank-ledgers` | CashBankLedgerController | auth, permission:cashbank |
| `/payments/*` | Payments\*Controller | auth, permission:payments |
| `/billing/*` | Billing\*Controller | auth |
| `/inventory/*` | Inventory\*Controller | auth, permission:inventory |
| `/report` | ReportController | auth |
| `/profit` | ProfitController | auth |
| `/search` | GlobalSearchController | auth |
| `/notifications/*` | NotificationController | auth, permission:settings |
| `/admin/*` | Admin\*Controller | auth, permission:roles/users |
| `/login`, `/register` | Auth\AuthController | guest:web |
| `/logout` | Auth\AuthController | auth |
| `/api/v1/*` | Api\V1\*Controller | auth:sanctum |
