# 07 — Controller Function Matrix

> **MSR BROILERS Management System** — Flockwise BizTrack  
> Every method across all 45 controllers, with HTTP verb and route path.

---

## Legend

- **Module** — Functional area
- **Controller** — Class name (short)
- **Method** — PHP method name
- **Verb** — HTTP method
- **Route** — Route path (from `routes/web.php` or `routes/api.php`)
- **Middleware** — Route-level or controller-level middleware

---

## 1. Base / App-Level Controllers

### Controller (Base)

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Base | Controller | — | — | — | — |

Empty base class. No methods.

---

### GlobalSearchController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Global | GlobalSearchController | `search` | GET | `/search` | `auth` |

---

### NotificationController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Notifications | NotificationController | `getUnreadNotifications` | GET | `/notifications/unread` | `auth`, `permission:settings` |
| Notifications | NotificationController | `getLatestNotifications` | GET | `/notifications/latest` | `auth`, `permission:settings` |
| Notifications | NotificationController | `markAllRead` | POST | `/notifications/mark-all-read` | `auth`, `permission:settings` |
| Notifications | NotificationController | `markSingleRead` | POST | `/notifications/{id}/read` | `auth`, `permission:settings` |

---

### ProfitController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Profit | ProfitController | `index` | GET | `/profit` | `auth` |

---

### ReportController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Reports | ReportController | `index` | GET | `/report` | `auth` |
| Reports | ReportController | `exportPDF` | GET | `/report/export-pdf` | `auth` |
| Reports | ReportController | `search` | GET | `/report/search` | `auth` |

---

### ExpenseController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Expenses | ExpenseController | `index` | GET | `/expenses` | `auth` |
| Expenses | ExpenseController | `store` | POST | `/expenses` | `auth` |
| Expenses | ExpenseController | `update` | PUT | `/expenses/{expense}` | `auth` |
| Expenses | ExpenseController | `destroy` | DELETE | `/expenses/{expense}` | `auth` |

---

### CapitalTransactionController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Capital | CapitalTransactionController | `index` | GET | `/capital-transactions` | `auth`, `permission:capital` |
| Capital | CapitalTransactionController | `store` | POST | `/capital-transactions` | `auth`, `permission:capital` |
| Capital | CapitalTransactionController | `edit` | GET | `/capital-transactions/{capitalTransaction}/edit` | `auth`, `permission:capital` |
| Capital | CapitalTransactionController | `update` | PUT | `/capital-transactions/{capitalTransaction}` | `auth`, `permission:capital` |
| Capital | CapitalTransactionController | `destroy` | DELETE | `/capital-transactions/{capitalTransaction}` | `auth`, `permission:capital` |
| Capital | CapitalTransactionController | `exportPDF` | GET | `/capital-transactions/export-pdf` | `auth`, `permission:capital` |

---

### CashBankLedgerController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| CashBank | CashBankLedgerController | `index` | GET | `/cash-bank-ledgers` | `auth`, `permission:cashbank` |
| CashBank | CashBankLedgerController | `store` | POST | `/cash-bank-ledgers` | `auth`, `permission:cashbank` |
| CashBank | CashBankLedgerController | `update` | PUT | `/cash-bank-ledgers/{cashBankLedger}` | `auth`, `permission:cashbank` |
| CashBank | CashBankLedgerController | `destroy` | DELETE | `/cash-bank-ledgers/{cashBankLedger}` | `auth`, `permission:cashbank` |
| CashBank | CashBankLedgerController | `approve` | POST | `/cash-bank-ledgers/{cashBankLedger}/approve` | `auth`, `permission:cashbank` |
| CashBank | CashBankLedgerController | `unapprove` | POST | `/cash-bank-ledgers/{cashBankLedger}/unapprove` | `auth`, `permission:cashbank` |
| CashBank | CashBankLedgerController | `approveAmount` | POST | `/cash-bank-ledgers/{cashBankLedger}/approve-amount` | `auth`, `permission:cashbank` |

---

### StockController (Web)

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Stock | StockController | `index` | GET | `/stock` | `auth`, `permission:stock` |
| Stock | StockController | `store` | POST | `/stock` | `auth`, `permission:stock` |
| Stock | StockController | `update` | PUT | `/stock/{stockItem}` | `auth`, `permission:stock` |
| Stock | StockController | `destroy` | DELETE | `/stock/{stockItem}` | `auth`, `permission:stock` |

---

### PaymentController (Web)

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Payments | PaymentController | `storeCustomerPayment` | POST | `/payments/customer` | `auth`, `permission:payments` |
| Payments | PaymentController | `storeDealerPayment` | POST | `/payments/dealer` | `auth`, `permission:payments` |
| Payments | PaymentController | `storeVendorPayment` | POST | `/payments/vendor` | `auth`, `permission:payments` |

---

### BirdBatchController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| BirdBatch | BirdBatchController | `index` | GET | `/bird-batches` | `auth`, `permission:batches` |
| BirdBatch | BirdBatchController | `store` | POST | `/bird-batches` | `auth`, `permission:batches` |
| BirdBatch | BirdBatchController | `show` | GET | `/bird-batches/{birdBatch}` | `auth`, `permission:batches` |
| BirdBatch | BirdBatchController | `update` | PUT | `/bird-batches/{birdBatch}` | `auth`, `permission:batches` |
| BirdBatch | BirdBatchController | `destroy` | DELETE | `/bird-batches/{birdBatch}` | `auth`, `permission:batches` |

---

## 2. Admin Controllers

### Admin\PermissionController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Admin | PermissionController | `index` | GET | `/admin/permissions` | `auth` |

---

### Admin\RoleController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Admin | RoleController | `index` | GET | `/admin/roles` | `auth`, `permission:roles` |
| Admin | RoleController | `store` | POST | `/admin/roles` | `auth`, `permission:roles` |
| Admin | RoleController | `edit` | GET | `/admin/roles/{role}/edit` | `auth`, `permission:roles` |
| Admin | RoleController | `update` | PUT | `/admin/roles/{role}` | `auth`, `permission:roles` |
| Admin | RoleController | `destroy` | DELETE | `/admin/roles/{role}` | `auth`, `permission:roles` |

---

### Admin\UserManagementController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Admin | UserManagementController | `index` | GET | `/admin/users` | `auth`, `permission:users` |
| Admin | UserManagementController | `store` | POST | `/admin/users` | `auth`, `permission:users` |
| Admin | UserManagementController | `edit` | GET | `/admin/users/{id}/edit` | `auth`, `permission:users` |
| Admin | UserManagementController | `update` | PUT | `/admin/users/{id}` | `auth`, `permission:users` |
| Admin | UserManagementController | `destroy` | DELETE | `/admin/users/{id}` | `auth`, `permission:users` |
| Admin | UserManagementController | `updateStatus` | POST | `/admin/users/{id}/status` | `auth`, `permission:users` |

---

## 3. Auth Controllers

### Auth\AuthController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Auth | AuthController | `showLoginForm` | GET | `/login` | `guest:web` |
| Auth | AuthController | `login` | POST | `/login` | `guest:web` |
| Auth | AuthController | `register` | GET | `/register` | `guest:web` |
| Auth | AuthController | `registerUser` | POST | `/register` | `guest:web` |
| Auth | AuthController | `logout` | POST | `/logout` | `auth` |

---

## 4. Billing Controllers

### Billing\DailyBillingController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Billing | DailyBillingController | `index` | GET | `/billing/daily` | `auth` |
| Billing | DailyBillingController | `store` | POST | `/billing/daily` | `auth` |
| Billing | DailyBillingController | `show` | GET | `/billing/daily/{dailyBill}` | `auth` |
| Billing | DailyBillingController | `edit` | GET | `/billing/daily/{dailyBill}/edit` | `auth` |
| Billing | DailyBillingController | `update` | PUT | `/billing/daily/{dailyBill}` | `auth` |
| Billing | DailyBillingController | `destroy` | DELETE | `/billing/daily/{dailyBill}` | `auth` |
| Billing | DailyBillingController | `printInvoice` | GET | `/billing/daily/{dailyBill}/print` | `auth` |
| Billing | DailyBillingController | `exportInvoice` | GET | `/billing/daily/{dailyBill}/export` | `auth` |
| Billing | DailyBillingController | `getDealerSummary` | GET | `/billing/daily/dealer-summary` | `auth` |

---

### Billing\DailyInvoiceController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Billing | DailyInvoiceController | `index` | GET | `/billing/daily-invoices` | `auth` |
| Billing | DailyInvoiceController | `store` | POST | `/billing/daily-invoices` | `auth` |
| Billing | DailyInvoiceController | `show` | GET | `/billing/daily-invoices/{id}` | `auth` |
| Billing | DailyInvoiceController | `update` | PUT | `/billing/daily-invoices/{id}` | `auth` |
| Billing | DailyInvoiceController | `destroy` | DELETE | `/billing/daily-invoices/{id}` | `auth` |

---

### Billing\DayLoadBillingController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Billing | DayLoadBillingController | `index` | GET | `/billing/dayload` | `auth` |
| Billing | DayLoadBillingController | `store` | POST | `/billing/dayload` | `auth` |
| Billing | DayLoadBillingController | `show` | GET | `/billing/dayload/{batch}` | `auth` |
| Billing | DayLoadBillingController | `update` | PUT | `/billing/dayload/{batch}` | `auth` |
| Billing | DayLoadBillingController | `destroy` | DELETE | `/billing/dayload/{batch}` | `auth` |
| Billing | DayLoadBillingController | `regenerateInvoice` | POST | `/billing/dayload/invoice/{invoiceId}/regenerate` | `auth` |
| Billing | DayLoadBillingController | `approveWeightLoss` | POST | `/billing/dayload/{batch}/approve-weight-loss` | `auth` |
| Billing | DayLoadBillingController | `unapproveWeightLoss` | POST | `/billing/dayload/{batch}/unapprove-weight-loss` | `auth` |
| Billing | DayLoadBillingController | `updateEntryStatus` | POST | `/billing/dayload/entry/status` | `auth` |

---

### Billing\WeeklyBillingController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Billing | WeeklyBillingController | `index` | GET | `/billing/weekly` | `auth` |
| Billing | WeeklyBillingController | `store` | POST | `/billing/weekly` | `auth` |
| Billing | WeeklyBillingController | `show` | GET | `/billing/weekly/{weeklyBill}` | `auth` |
| Billing | WeeklyBillingController | `edit` | GET | `/billing/weekly/{weeklyBill}/edit` | `auth` |
| Billing | WeeklyBillingController | `update` | PUT | `/billing/weekly/{weeklyBill}` | `auth` |
| Billing | WeeklyBillingController | `destroy` | DELETE | `/billing/weekly/{weeklyBill}` | `auth` |
| Billing | WeeklyBillingController | `exportInvoice` | GET | `/billing/weekly/{weeklyBill}/export` | `auth` |
| Billing | WeeklyBillingController | `exportDealerBillPDF` | GET | `/billing/weekly/dealer-bill` | `auth` |
| Billing | WeeklyBillingController | `exportDealerBillPDFForDealer` | GET | `/billing/weekly/dealer-bill/{dealer}` | `auth` |
| Billing | WeeklyBillingController | `printDealerBillPDF` | GET | `/billing/weekly/print-dealer-bill/{dealer}` | `auth` |
| Billing | WeeklyBillingController | `printAllDealerBillsPDF` | GET | `/billing/weekly/print-all-dealer-bills` | `auth` |
| Billing | WeeklyBillingController | `downloadDealerBillsZip` | GET | `/billing/weekly/download-all-dealer-bills-zip` | `auth` |
| Billing | WeeklyBillingController | `downloadDealerBillPDF` | GET | `/billing/weekly/download-dealer-bill/{dealerId}` | `auth` |

---

## 5. Inventory Controllers

### Inventory\AnalyticsController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | AnalyticsController | `index` | GET | `/inventory/analytics` | `auth`, `permission:inventory` |
| Inventory | AnalyticsController | `search` | GET | `/inventory/analytics/search` | `auth`, `permission:inventory` |
| Inventory | AnalyticsController | `recentActivity` | GET | `/inventory/analytics/recent-activity` | `auth`, `permission:inventory` |
| Inventory | AnalyticsController | `stockTrends` | GET | `/inventory/analytics/stock-trends` | `auth`, `permission:inventory` |

---

### Inventory\BatchController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | BatchController | `index` | GET | `/inventory/batches` | `auth`, `permission:inventory` |
| Inventory | BatchController | `store` | POST | `/inventory/batches` | `auth`, `permission:inventory` |
| Inventory | BatchController | `update` | PUT | `/inventory/batches/{batch}` | `auth`, `permission:inventory` |
| Inventory | BatchController | `destroy` | DELETE | `/inventory/batches/{batch}` | `auth`, `permission:inventory` |
| Inventory | BatchController | `analytics` | GET | `/inventory/batches/analytics` | `auth`, `permission:inventory` |
| Inventory | BatchController | `analyticsStore` | POST | `/inventory/batches/analytics/store` | `auth`, `permission:inventory` |

---

### Inventory\ConsumptionController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | ConsumptionController | `index` | GET | `/inventory/consumption` | `auth`, `permission:inventory` |
| Inventory | ConsumptionController | `store` | POST | `/inventory/consumption` | `auth`, `permission:inventory` |
| Inventory | ConsumptionController | `update` | PUT | `/inventory/consumption/{consumption}` | `auth`, `permission:inventory` |
| Inventory | ConsumptionController | `destroy` | DELETE | `/inventory/consumption/{consumption}` | `auth`, `permission:inventory` |
| Inventory | ConsumptionController | `analytics` | GET | `/inventory/consumption/analytics` | `auth`, `permission:inventory` |

---

### Inventory\ItemController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | ItemController | `index` | GET | `/inventory/items` | `auth`, `permission:inventory` |
| Inventory | ItemController | `store` | POST | `/inventory/items` | `auth`, `permission:inventory` |
| Inventory | ItemController | `update` | PUT | `/inventory/items/{item}` | `auth`, `permission:inventory` |
| Inventory | ItemController | `destroy` | DELETE | `/inventory/items/{item}` | `auth`, `permission:inventory` |
| Inventory | ItemController | `analytics` | GET | `/inventory/items/analytics` | `auth`, `permission:inventory` |

---

### Inventory\MortalityController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | MortalityController | `index` | GET | `/inventory/mortality` | `auth`, `permission:inventory` |
| Inventory | MortalityController | `store` | POST | `/inventory/mortality` | `auth`, `permission:inventory` |
| Inventory | MortalityController | `update` | PUT | `/inventory/mortality/{mortality}` | `auth`, `permission:inventory` |
| Inventory | MortalityController | `destroy` | DELETE | `/inventory/mortality/{mortality}` | `auth`, `permission:inventory` |
| Inventory | MortalityController | `analytics` | GET | `/inventory/mortality/analytics` | `auth`, `permission:inventory` |

---

### Inventory\StockController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | StockController | `index` | GET | `/inventory/stock` | `auth`, `permission:inventory` |
| Inventory | StockController | `store` | POST | `/inventory/stock` | `auth`, `permission:inventory` |
| Inventory | StockController | `show` | GET | `/inventory/stock/{entry}` | `auth`, `permission:inventory` |
| Inventory | StockController | `update` | PUT | `/inventory/stock/{entry}` | `auth`, `permission:inventory` |
| Inventory | StockController | `destroy` | DELETE | `/inventory/stock/{entry}` | `auth`, `permission:inventory` |
| Inventory | StockController | `analytics` | GET | `/inventory/stock/analytics` | `auth`, `permission:inventory` |
| Inventory | StockController | `lowStock` | GET | `/inventory/stock/low-stock` | `auth`, `permission:inventory` |
| Inventory | StockController | `search` | GET | `/inventory/stock/search` | `auth`, `permission:inventory` |

---

### Inventory\WarehouseController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Inventory | WarehouseController | `index` | GET | `/inventory/warehouses` | `auth`, `permission:inventory` |
| Inventory | WarehouseController | `store` | POST | `/inventory/warehouses` | `auth`, `permission:inventory` |
| Inventory | WarehouseController | `update` | PUT | `/inventory/warehouses/{warehouse}` | `auth`, `permission:inventory` |
| Inventory | WarehouseController | `destroy` | DELETE | `/inventory/warehouses/{warehouse}` | `auth`, `permission:inventory` |
| Inventory | WarehouseController | `analytics` | GET | `/inventory/warehouses/analytics` | `auth`, `permission:inventory` |

---

## 6. Masters Controllers

### Masters\CustomerController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Masters | CustomerController | `index` | GET | `/masters/customers` | `auth`, `permission:customers` |
| Masters | CustomerController | `store` | POST | `/masters/customers` | `auth`, `permission:customers` |
| Masters | CustomerController | `update` | PUT | `/masters/customers/{customer}` | `auth`, `permission:customers` |
| Masters | CustomerController | `destroy` | DELETE | `/masters/customers/{customer}` | `auth`, `permission:customers` |
| Masters | CustomerController | `search` | GET | `/masters/customers/search` | `auth`, `permission:customers` |
| Masters | CustomerController | `exportPDF` | GET | `/masters/customers/export-pdf` | `auth`, `permission:customers` |

---

### Masters\DealerController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Masters | DealerController | `index` | GET | `/masters/dealers` | `auth`, `permission:dealers` |
| Masters | DealerController | `store` | POST | `/masters/dealers` | `auth`, `permission:dealers` |
| Masters | DealerController | `update` | PUT | `/masters/dealers/{dealer}` | `auth`, `permission:dealers` |
| Masters | DealerController | `destroy` | DELETE | `/masters/dealers/{dealer}` | `auth`, `permission:dealers` |
| Masters | DealerController | `search` | GET | `/masters/dealers/search` | `auth`, `permission:dealers` |

---

### Masters\RouteController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Masters | RouteController | `index` | GET | `/masters/routes` | `auth`, `permission:routes` |
| Masters | RouteController | `store` | POST | `/masters/routes` | `auth`, `permission:routes` |
| Masters | RouteController | `update` | PUT | `/masters/routes/{route}` | `auth`, `permission:routes` |
| Masters | RouteController | `destroy` | DELETE | `/masters/routes/{route}` | `auth`, `permission:routes` |

---

### Masters\VendorController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Masters | VendorController | `index` | GET | `/masters/vendors` | `auth`, `permission:vendors` |
| Masters | VendorController | `store` | POST | `/masters/vendors` | `auth`, `permission:vendors` |
| Masters | VendorController | `update` | PUT | `/masters/vendors/{vendor}` | `auth`, `permission:vendors` |
| Masters | VendorController | `destroy` | DELETE | `/masters/vendors/{vendor}` | `auth`, `permission:vendors` |
| Masters | VendorController | `search` | GET | `/masters/vendors/search` | `auth`, `permission:vendors` |

---

## 7. Payments Controllers

### Payments\CustomerPaymentController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Payments | CustomerPaymentController | `index` | GET | `/payments/customer` | `auth`, `permission:payments` |
| Payments | CustomerPaymentController | `store` | POST | `/payments/customer` | `auth`, `permission:payments` |
| Payments | CustomerPaymentController | `update` | PUT | `/payments/customer/{customerPayment}` | `auth`, `permission:payments` |
| Payments | CustomerPaymentController | `destroy` | DELETE | `/payments/customer/{customerPayment}` | `auth`, `permission:payments` |
| Payments | CustomerPaymentController | `adjust` | POST | `/payments/customer/{customerPayment}/adjust` | `auth`, `permission:payments` |

---

### Payments\DealerPaymentController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Payments | DealerPaymentController | `index` | GET | `/payments/dealer` | `auth`, `permission:payments` |
| Payments | DealerPaymentController | `store` | POST | `/payments/dealer` | `auth`, `permission:payments` |
| Payments | DealerPaymentController | `update` | PUT | `/payments/dealer/{dealerPayment}` | `auth`, `permission:payments` |
| Payments | DealerPaymentController | `destroy` | DELETE | `/payments/dealer/{dealerPayment}` | `auth`, `permission:payments` |
| Payments | DealerPaymentController | `adjust` | POST | `/payments/dealer/{dealerPayment}/adjust` | `auth`, `permission:payments` |

---

### Payments\VendorPaymentController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Payments | VendorPaymentController | `index` | GET | `/payments/vendor` | `auth`, `permission:payments` |
| Payments | VendorPaymentController | `store` | POST | `/payments/vendor` | `auth`, `permission:payments` |
| Payments | VendorPaymentController | `update` | PUT | `/payments/vendor/{vendorPayment}` | `auth`, `permission:payments` |
| Payments | VendorPaymentController | `destroy` | DELETE | `/payments/vendor/{vendorPayment}` | `auth`, `permission:payments` |
| Payments | VendorPaymentController | `adjust` | POST | `/payments/vendor/{vendorPayment}/adjust` | `auth`, `permission:payments` |

---

## 8. Purchases Controllers

### Purchases\PurchaseController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| Purchases | PurchaseController | `index` | GET | `/purchases` | `auth`, `permission:purchases` |
| Purchases | PurchaseController | `store` | POST | `/purchases` | `auth`, `permission:purchases` |
| Purchases | PurchaseController | `update` | PUT | `/purchases/{purchase}` | `auth`, `permission:purchases` |
| Purchases | PurchaseController | `destroy` | DELETE | `/purchases/{purchase}` | `auth`, `permission:purchases` |
| Purchases | PurchaseController | `search` | GET | `/purchases/search` | `auth`, `permission:purchases` |
| Purchases | PurchaseController | `exportPDF` | GET | `/purchases/export-pdf` | `auth`, `permission:purchases` |

---

## 9. API Controllers (V1)

### Api\V1\AuthController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | AuthController | `login` | POST | `/api/v1/login` | `guest:sanctum` |
| API | AuthController | `logout` | POST | `/api/v1/logout` | `auth:sanctum` |
| API | AuthController | `profile` | GET | `/api/v1/profile` | `auth:sanctum` |

---

### Api\V1\DashboardController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | DashboardController | `index` | GET | `/api/v1/dashboard` | `auth:sanctum` |

---

### Api\V1\PaymentController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | PaymentController | `storeDealerPayment` | POST | `/api/v1/payments/dealer` | `auth:sanctum` |
| API | PaymentController | `storeVendorPayment` | POST | `/api/v1/payments/vendor` | `auth:sanctum` |
| API | PaymentController | `getDealerPayments` | GET | `/api/v1/payments/dealer/{dealer}` | `auth:sanctum` |
| API | PaymentController | `getVendorPayments` | GET | `/api/v1/payments/vendor/{vendor}` | `auth:sanctum` |
| API | PaymentController | `storeDealerAdvance` | POST | `/api/v1/advances/dealer` | `auth:sanctum` |
| API | PaymentController | `storeVendorAdvance` | POST | `/api/v1/advances/vendor` | `auth:sanctum` |

---

### Api\V1\StockController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | StockController | `index` | GET | `/api/v1/stock` | `auth:sanctum` |
| API | StockController | `storeStockIn` | POST | `/api/v1/stock/in` | `auth:sanctum` |
| API | StockController | `storeStockOut` | POST | `/api/v1/stock/out` | `auth:sanctum` |
| API | StockController | `storeAdjustment` | POST | `/api/v1/stock/adjust` | `auth:sanctum` |

---

### Api\V1\InventoryAnalyticsController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | InventoryAnalyticsController | `index` | GET | `/api/v1/inventory/analytics` | `auth:sanctum` |

---

### Api\V1\UserManagementController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | UserManagementController | `index` | GET | `/api/v1/users` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `store` | POST | `/api/v1/users` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `show` | GET | `/api/v1/users/{id}` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `update` | PUT | `/api/v1/users/{id}` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `destroy` | DELETE | `/api/v1/users/{id}` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `updateStatus` | POST | `/api/v1/users/{id}/status` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `getPermissions` | GET | `/api/v1/permissions` | `auth:sanctum`, `permission:users` |
| API | UserManagementController | `getRoles` | GET | `/api/v1/roles` | `auth:sanctum`, `permission:users` |

---

### Api\V1\WeeklyBillingController

| Module | Controller | Method | Verb | Route | Middleware |
|---|---|---|---|---|---|
| API | WeeklyBillingController | `getDealerSummary` | GET | `/api/v1/billing/dealer-summary` | `auth:sanctum` |
| API | WeeklyBillingController | `createDealerWeeklyBill` | POST | `/api/v1/billing/weekly-bill` | `auth:sanctum` |
| API | WeeklyBillingController | `createVendorDayLoad` | POST | `/api/v1/billing/vendor-dayload` | `auth:sanctum` |

---

## Summary Statistics

| Category | Count |
|---|---|
| **Web Routes** | ~150+ |
| **API Routes** | ~30 |
| **Total Controller Methods** | ~175 |
| **CRUD Controllers** | 25 |
| **Dashboard/Analytics Controllers** | 5 |
| **API-Only Controllers** | 7 |
| **PDF Export Methods** | 12 |
| **AJAX Search Methods** | 8 |
| **Status Toggle Methods** | 3 |
