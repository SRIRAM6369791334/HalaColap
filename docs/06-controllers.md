# 06 — Complete Controller Analysis

> **MSR BROILERS Management System** — Flockwise BizTrack  
> Total Controllers: **45** (Web: 35, API: 10)

---

## Controller Directory Structure

```
app/Http/Controllers/
├── Controller.php                          (Base)
├── Api/
│   └── V1/
│       ├── BaseApiController.php           (API Base)
│       ├── AuthController.php
│       ├── DashboardController.php
│       ├── PaymentController.php
│       ├── StockController.php
│       ├── InventoryAnalyticsController.php
│       ├── UserManagementController.php
│       └── WeeklyBillingController.php
├── Admin/
│   ├── PermissionController.php
│   ├── RoleController.php
│   └── UserManagementController.php
├── Auth/
│   └── AuthController.php
├── Billing/
│   ├── DailyBillingController.php
│   ├── DailyInvoiceController.php
│   ├── DayLoadBillingController.php
│   └── WeeklyBillingController.php
├── Inventory/
│   ├── AnalyticsController.php
│   ├── BatchController.php
│   ├── ConsumptionController.php
│   ├── ItemController.php
│   ├── MortalityController.php
│   ├── StockController.php
│   └── WarehouseController.php
├── Masters/
│   ├── CustomerController.php
│   ├── DealerController.php
│   ├── RouteController.php
│   └── VendorController.php
├── Payments/
│   ├── CustomerPaymentController.php
│   ├── DealerPaymentController.php
│   └── VendorPaymentController.php
├── Purchases/
│   └── PurchaseController.php
├── BirdBatchController.php
├── CapitalTransactionController.php
├── CashBankLedgerController.php
├── DashboardController.php
├── ExpenseController.php
├── GlobalSearchController.php
├── NotificationController.php
├── PaymentController.php
├── ProfitController.php
├── ReportController.php
└── StockController.php
```

---

## Base / App-Level Controllers

### 1. Controller

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Controller.php` |
| **Extends** | `Illuminate\Routing\Controller` |
| **Role** | Abstract base class for all web controllers |

**Methods:** None (empty base class).

---

### 2. DashboardController (Web)

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/DashboardController.php` |
| **Namespace** | `App\Http\Controllers` |
| **Middleware** | `auth`, `permission:dashboard` |

**Custom Methods:** None. Dashboard views are rendered by the route closure in `routes/web.php`.

---

### 3. GlobalSearchController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/GlobalSearchController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `search(Request $request)` | GET | Returns JSON results for AJAX live search across modules (routes, customers, dealers, vendors, stock, purchases, payments) |

---

### 4. NotificationController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/NotificationController.php` |
| **Middleware** | `auth`, `permission:settings` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `getUnreadNotifications()` | GET | Returns paginated unread notifications JSON for polling |
| `getLatestNotifications()` | GET | Returns latest 10 notifications JSON for modal |
| `markAllRead()` | POST | Marks all user notifications as read |
| `markSingleRead($id)` | POST | Marks a single notification as read |

---

### 5. ProfitController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/ProfitController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Profit report page with date filtering |

---

### 6. ReportController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/ReportController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Filters and lists payments (daily bills, cash/bank totals, deals) |
| `exportPDF(Request $request)` | GET | Generates PDF payment report via DomPDF |
| `search(Request $request)` | GET | AJAX search for payments, dealers, customers, vendors, stock, purchases |

---

### 7. ExpenseController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/ExpenseController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists expenses with search and category filtering |
| `store(Request $request)` | POST | Validates and creates a new expense |
| `update(Request $request, Expense $expense)` | PUT | Validates and updates an expense |
| `destroy(Expense $expense)` | DELETE | Soft-deletes an expense |

---

### 8. CapitalTransactionController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/CapitalTransactionController.php` |
| **Middleware** | `auth`, `permission:capital` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists capital transactions with current balance |
| `store(Request $request)` | POST | Validates and creates a capital transaction |
| `edit(CapitalTransaction $capitalTransaction)` | GET | Returns JSON for inline editing |
| `update(Request $request, CapitalTransaction $capitalTransaction)` | PUT | Validates and updates a transaction |
| `destroy(CapitalTransaction $capitalTransaction)` | DELETE | Deletes a capital transaction |
| `exportPDF()` | GET | Generates PDF of capital transactions |

---

### 9. CashBankLedgerController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/CashBankLedgerController.php` |
| **Middleware** | `auth`, `permission:cashbank` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Displays ledger list with search, daily/monthly view, period filtering, export toggles |
| `store(Request $request)` | POST | Validates and creates a ledger entry |
| `update(Request $request, CashBankLedger $cashBankLedger)` | PUT | Updates a ledger entry |
| `destroy(CashBankLedger $cashBankLedger)` | DELETE | Deletes a ledger entry |
| `approve(CashBankLedger $cashBankLedger)` | POST | Approves a ledger entry (amount check) |
| `unapprove(CashBankLedger $cashBankLedger)` | POST | Removes approval from a ledger entry |
| `approveAmount(Request $request, CashBankLedger $cashBankLedger)` | POST | Sets specific approval amount on a ledger entry |

---

### 10. StockController (Web)

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/StockController.php` |
| **Namespace** | `App\Http\Controllers` |
| **Middleware** | `auth`, `permission:stock` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists stock items with search, category, and date range filtering |
| `store(Request $request)` | POST | Validates and creates a new stock item |
| `update(Request $request, StockItem $stockItem)` | PUT | Validates and updates a stock item |
| `destroy(StockItem $stockItem)` | DELETE | Deletes a stock item |

---

### 11. PaymentController (Web)

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/PaymentController.php` |
| **Namespace** | `App\Http\Controllers` |
| **Middleware** | `auth`, `permission:payments` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `storeCustomerPayment(Request $request)` | POST | Validates and creates a customer payment |
| `storeDealerPayment(Request $request)` | POST | Validates and creates a dealer payment |
| `storeVendorPayment(Request $request)` | POST | Validates and creates a vendor payment |

---

### 12. BirdBatchController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/BirdBatchController.php` |
| **Middleware** | `auth`, `permission:batches` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists bird batches with search, date range, and sorting |
| `store(Request $request)` | POST | Validates and creates a new bird batch |
| `show(BirdBatch $birdBatch)` | GET | Shows batch detail (JSON) |
| `update(Request $request, BirdBatch $birdBatch)` | PUT | Validates and updates a bird batch |
| `destroy(BirdBatch $birdBatch)` | DELETE | Deletes a bird batch |

---

## Admin Controllers

### 13. Admin\PermissionController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Admin/PermissionController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists permission groups and ungrouped permissions |

---

### 14. Admin\RoleController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Admin/RoleController.php` |
| **Middleware** | `auth`, `permission:roles` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists all roles with user counts |
| `store(Request $request)` | POST | Validates and creates a new role with permissions |
| `edit(Role $role)` | GET | Shows edit view for a role |
| `update(Request $request, Role $role)` | PUT | Validates and updates a role with permissions |
| `destroy(Role $role)` | DELETE | Deletes a role (if not system role) |

---

### 15. Admin\UserManagementController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Admin/UserManagementController.php` |
| **Middleware** | `auth`, `permission:users` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists all users with roles, permission counts, search |
| `store(Request $request)` | POST | Validates and creates a new user with roles and permissions |
| `edit($id)` | GET | Shows user edit view with form data |
| `update(Request $request, $id)` | PUT | Validates and updates a user with roles and permissions |
| `destroy($id)` | DELETE | Deletes a user |
| `updateStatus(Request $request, $id)` | POST | Toggles user active/inactive status |

---

### 16. Auth\AuthController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Auth/AuthController.php` |
| **Middleware** | `guest:web` on login/register |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `showLoginForm()` | GET | Shows login page |
| `login(Request $request)` | POST | Validates credentials, checks `is_active`, logs in |
| `register()` | GET | Shows registration page |
| `registerUser(Request $request)` | POST | Validates and creates user, logs in |
| `logout(Request $request)` | POST | Logs out user, invalidates session/token |

---

## Billing Controllers

### 17. Billing\DailyBillingController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Billing/DailyBillingController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists daily bills with search, date range, and AJAX search |
| `store(Request $request)` | POST | Creates a DailyBill with items and DayLoadEntry links |
| `show(DailyBill $dailyBill)` | GET | Returns JSON for AJAX bill detail |
| `edit(DailyBill $dailyBill)` | GET | Returns JSON for AJAX edit |
| `update(Request $request, DailyBill $dailyBill)` | PUT | Updates bill with items and DayLoadEntry links |
| `destroy(DailyBill $dailyBill)` | DELETE | Deletes bill with nullification of linked DayLoadEntries |
| `printInvoice(DailyBill $dailyBill)` | GET | Returns print-ready invoice view |
| `exportInvoice(DailyBill $dailyBill)` | GET | Generates downloadable PDF invoice |
| `getDealerSummary(Request $request)` | GET | Returns dealer daily summary for AJAX |

---

### 18. Billing\DailyInvoiceController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Billing/DailyInvoiceController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists daily invoices with filtering |
| `store(Request $request)` | POST | Creates a DailyInvoice |
| `show($id)` | GET | Shows invoice detail |
| `update(Request $request, $id)` | PUT | Updates an invoice |
| `destroy($id)` | DELETE | Deletes an invoice |

---

### 19. Billing\DayLoadBillingController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Billing/DayLoadBillingController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists day-load batches with filtering, totals, exports |
| `store(Request $request)` | POST | Creates a new day-load batch |
| `show(DayLoadBatch $batch)` | GET | Shows batch with entries |
| `update(Request $request, DayLoadBatch $batch)` | PUT | Updates batch with entries and links |
| `destroy(DayLoadBatch $batch)` | DELETE | Deletes batch and its entries |
| `regenerateInvoice($invoiceId)` | POST | Regenerates an existing day-load invoice |
| `approveWeightLoss(Request $request, DayLoadBatch $batch)` | POST | Approves weight loss for a batch |
| `unapproveWeightLoss(DayLoadBatch $batch)` | POST | Unapproves weight loss for a batch |
| `updateEntryStatus(Request $request)` | POST | Updates entry status (vendor_paid, dealer_collected) |

---

### 20. Billing\WeeklyBillingController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Billing/WeeklyBillingController.php` |
| **Middleware** | `auth` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists weekly bills with search and filtering |
| `store(Request $request)` | POST | Creates a weekly bill with items and dealer purchases |
| `show(WeeklyBill $weeklyBill)` | GET | Shows weekly bill with items and purchases |
| `edit(WeeklyBill $weeklyBill)` | GET | Returns JSON for AJAX edit |
| `update(Request $request, WeeklyBill $weeklyBill)` | PUT | Updates weekly bill with items and purchases |
| `destroy(WeeklyBill $weeklyBill)` | DELETE | Deletes weekly bill and adjusts dealer balances |
| `exportInvoice(WeeklyBill $weeklyBill)` | GET | Generates PDF invoice |
| `exportDealerBillPDF(Request $request)` | GET | Exports dealer-specific weekly bill PDF |
| `exportDealerBillPDFForDealer(Request $request, Dealer $dealer)` | GET | PDF for a specific dealer |
| `printDealerBillPDF(Request $request, Dealer $dealer)` | GET | Prints dealer bill PDF |
| `printAllDealerBillsPDF(Request $request)` | GET | Batch prints all dealer bills |
| `downloadDealerBillsZip(Request $request)` | GET | Downloads all dealer bills as ZIP |
| `downloadDealerBillPDF($dealerId)` | GET | Downloads single dealer bill PDF |

---

## Inventory Controllers

### 21. Inventory\AnalyticsController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/AnalyticsController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Returns analytics view with stock levels, category distribution, trends, top items |
| `search(Request $request)` | GET | AJAX search across items, batches, warehouses, categories |
| `recentActivity(Request $request)` | GET | Returns recent stock ledger entries (JSON) |
| `stockTrends(Request $request)` | GET | Returns stock movement trends (JSON) |

---

### 22. Inventory\BatchController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/BatchController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists batches with search and filtering |
| `store(Request $request)` | POST | Validates and creates a new batch |
| `update(Request $request, Batch $batch)` | PUT | Validates and updates a batch |
| `destroy(Batch $batch)` | DELETE | Deletes a batch |
| `analytics()` | GET | Returns batch analytics JSON (total batches, active count, age distribution, recent batches) |
| `analyticsStore(Request $request)` | POST | Creates a batch from analytics page |

---

### 23. Inventory\ConsumptionController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/ConsumptionController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists consumption records with search, batch/item filters |
| `store(Request $request)` | POST | Validates and creates a consumption record |
| `update(Request $request, Consumption $consumption)` | PUT | Validates and updates a consumption record |
| `destroy(Consumption $consumption)` | DELETE | Deletes a consumption record |
| `analytics()` | GET | Returns consumption analytics JSON (totals by batch, top consumed items, daily trends) |

---

### 24. Inventory\ItemController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/ItemController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists items with search, status, type, category filters |
| `store(Request $request)` | POST | Validates and creates a new item |
| `update(Request $request, Item $item)` | PUT | Validates and updates an item |
| `destroy(Item $item)` | DELETE | Deletes an item |
| `analytics()` | GET | Returns item analytics JSON (total, active/inactive, category distribution, type distribution, low stock) |

---

### 25. Inventory\MortalityController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/MortalityController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists mortality records with search, batch filter, date range |
| `store(Request $request)` | POST | Validates and creates a mortality record |
| `update(Request $request, Mortality $mortality)` | PUT | Validates and updates a mortality record |
| `destroy(Mortality $mortality)` | DELETE | Deletes a mortality record |
| `analytics()` | GET | Returns mortality analytics JSON (total, recent mortality, top batches, daily trends) |

---

### 26. Inventory\StockController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/StockController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists stock ledger entries with search, date range, item/batch/warehouse/type filters |
| `store(Request $request)` | POST | Validates and creates a stock entry (IN/OUT/ADJUSTMENT) |
| `show(StockLedger $entry)` | GET | Returns JSON for editing a stock entry |
| `update(Request $request, StockLedger $entry)` | PUT | Validates and updates a stock entry |
| `destroy(StockLedger $entry)` | DELETE | Deletes a stock entry |
| `analytics()` | GET | Returns stock analytics JSON (summary, items, warehouses, movements, low stock, out of stock) |
| `lowStock()` | GET | Returns low stock items JSON |
| `search(Request $request)` | GET | AJAX search across items, batches, warehouses, stock entries |

---

### 27. Inventory\WarehouseController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Inventory/WarehouseController.php` |
| **Middleware** | `auth`, `permission:inventory` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists warehouses with search and stock summaries |
| `store(Request $request)` | POST | Validates and creates a new warehouse |
| `update(Request $request, Warehouse $warehouse)` | PUT | Validates and updates a warehouse |
| `destroy(Warehouse $warehouse)` | DELETE | Deletes a warehouse (only if no stock entries) |
| `analytics()` | GET | Returns warehouse analytics JSON (total, active, total stock, top warehouses, items per warehouse) |

---

## Masters Controllers

### 28. Masters\CustomerController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Masters/CustomerController.php` |
| **Middleware** | `auth`, `permission:customers` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists customers with search, route filter, and AJAX search |
| `store(Request $request)` | POST | Validates and creates a customer |
| `update(Request $request, Customer $customer)` | PUT | Validates and updates a customer |
| `destroy(Customer $customer)` | DELETE | Validates and deletes a customer |
| `search(Request $request)` | GET | AJAX search across customers, dealers, vendors, purchases, payments, stock |
| `exportPDF(Request $request)` | GET | Generates filtered PDF of customers |

---

### 29. Masters\DealerController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Masters/DealerController.php` |
| **Middleware** | `auth`, `permission:dealers` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists dealers with search, route filter, AJAX search, payments, and totals |
| `store(Request $request)` | POST | Validates and creates a dealer |
| `update(Request $request, Dealer $dealer)` | PUT | Validates and updates a dealer |
| `destroy(Dealer $dealer)` | DELETE | Validates and deletes a dealer |
| `search(Request $request)` | GET | AJAX search across customers, dealers, vendors, purchases, payments, stock |

---

### 30. Masters\RouteController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Masters/RouteController.php` |
| **Middleware** | `auth`, `permission:routes` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Lists routes with customer/dealer counts, search |
| `store(Request $request)` | POST | Validates and creates a route |
| `update(Request $request, Route $route)` | PUT | Validates and updates a route |
| `destroy(Route $route)` | DELETE | Validates and deletes a route |

---

### 31. Masters\VendorController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Masters/VendorController.php` |
| **Middleware** | `auth`, `permission:vendors` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists vendors with search, filtering, AJAX search |
| `store(Request $request)` | POST | Validates and creates a vendor |
| `update(Request $request, Vendor $vendor)` | PUT | Validates and updates a vendor |
| `destroy(Vendor $vendor)` | DELETE | Validates and deletes a vendor |
| `search(Request $request)` | GET | AJAX search across customers, dealers, vendors, purchases, payments, stock |

---

## Payments Controllers

### 32. Payments\CustomerPaymentController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Payments/CustomerPaymentController.php` |
| **Middleware** | `auth`, `permission:payments` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists customer payments with search, date range, customer filter |
| `store(Request $request)` | POST | Validates and creates a customer payment |
| `update(Request $request, CustomerPayment $customerPayment)` | PUT | Validates and updates a customer payment |
| `destroy(CustomerPayment $customerPayment)` | DELETE | Validates and deletes a customer payment |
| `adjust(Request $request, CustomerPayment $customerPayment)` | POST | Adjusts a customer payment |

---

### 33. Payments\DealerPaymentController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Payments/DealerPaymentController.php` |
| **Middleware** | `auth`, `permission:payments` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists dealer payments with search, date range, dealer filter |
| `store(Request $request)` | POST | Validates and creates a dealer payment |
| `update(Request $request, DealerPayment $dealerPayment)` | PUT | Validates and updates a dealer payment |
| `destroy(DealerPayment $dealerPayment)` | DELETE | Validates and deletes a dealer payment |
| `adjust(Request $request, DealerPayment $dealerPayment)` | POST | Adjusts a dealer payment |

---

### 34. Payments\VendorPaymentController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Payments/VendorPaymentController.php` |
| **Middleware** | `auth`, `permission:payments` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists vendor payments with search, date range, vendor filter |
| `store(Request $request)` | POST | Validates and creates a vendor payment |
| `update(Request $request, VendorPayment $vendorPayment)` | PUT | Validates and updates a vendor payment |
| `destroy(VendorPayment $vendorPayment)` | DELETE | Validates and deletes a vendor payment |
| `adjust(Request $request, VendorPayment $vendorPayment)` | POST | Adjusts a vendor payment |

---

## Purchases Controllers

### 35. Purchases\PurchaseController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Purchases/PurchaseController.php` |
| **Middleware** | `auth`, `permission:purchases` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index(Request $request)` | GET | Lists purchases with search, date range, vendor filter |
| `store(Request $request)` | POST | Validates and creates a purchase with items |
| `update(Request $request, Purchase $purchase)` | PUT | Validates and updates a purchase with items |
| `destroy(Purchase $purchase)` | DELETE | Validates and deletes a purchase with items |
| `search(Request $request)` | GET | AJAX search across purchases, customers, dealers, vendors, payments, stock |
| `exportPDF(Request $request)` | GET | Generates filtered PDF of purchases |

---

## API Controllers (V1)

### 36. Api\V1\BaseApiController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/BaseApiController.php` |
| **Namespace** | `App\Http\Controllers\Api\V1` |

**Traits Used:** `ApiResponse`

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `sendResponse($result, $message, $code)` | — | Wraps successful API response |
| `sendError($message, $code)` | — | Wraps error API response |
| `sendPaginatedResponse($result, $message, $code)` | — | Wraps paginated API response |

---

### 37. Api\V1\AuthController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/AuthController.php` |
| **Middleware** | `guest:sanctum` on login |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `login(Request $request)` | POST | API login with email/password, returns Sanctum token |
| `logout(Request $request)` | POST | Revokes current token |
| `profile(Request $request)` | GET | Returns authenticated user profile with roles/permissions |

---

### 38. Api\V1\DashboardController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/DashboardController.php` |
| **Middleware** | `auth:sanctum` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Returns dashboard summary: sales (today/week/month), expenses, capital, stock alerts, recent activities |

---

### 39. Api\V1\PaymentController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/PaymentController.php` |
| **Middleware** | `auth:sanctum` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `storeDealerPayment(Request $request)` | POST | Creates a dealer payment via API |
| `storeVendorPayment(Request $request)` | POST | Creates a vendor payment via API |
| `getDealerPayments(Request $request, Dealer $dealer)` | GET | Returns dealer payments with optional date filtering |
| `getVendorPayments(Request $request, Vendor $vendor)` | GET | Returns vendor payments with optional date filtering |
| `storeDealerAdvance(Request $request)` | POST | Creates a dealer advance via API |
| `storeVendorAdvance(Request $request)` | POST | Creates a vendor advance via API |

---

### 40. Api\V1\StockController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/StockController.php` |
| **Middleware** | `auth:sanctum` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Returns stock levels for all items |
| `storeStockIn(Request $request)` | POST | Records stock IN entry |
| `storeStockOut(Request $request)` | POST | Records stock OUT entry |
| `storeAdjustment(Request $request)` | POST | Records stock adjustment entry |

---

### 41. Api\V1\InventoryAnalyticsController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/InventoryAnalyticsController.php` |
| **Middleware** | `auth:sanctum` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Returns inventory analytics: summary (total items, stock value, low stock), category/type distribution, low/out-of-stock alerts |

---

### 42. Api\V1\UserManagementController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/UserManagementController.php` |
| **Middleware** | `auth:sanctum`, `permission:users` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `index()` | GET | Returns paginated users with roles |
| `store(Request $request)` | POST | Creates a user via API |
| `show($id)` | GET | Returns a single user |
| `update(Request $request, $id)` | PUT | Updates a user via API |
| `destroy($id)` | DELETE | Deletes a user via API |
| `updateStatus(Request $request, $id)` | POST | Toggles user status via API |
| `getPermissions()` | GET | Returns all permissions and permission groups |
| `getRoles()` | GET | Returns all roles |

---

### 43. Api\V1\WeeklyBillingController

| Property | Value |
|---|---|
| **File** | `app/Http/Controllers/Api/V1/WeeklyBillingController.php` |
| **Middleware** | `auth:sanctum` |

**Methods:**

| Method | HTTP | Description |
|---|---|---|
| `getDealerSummary(Request $request)` | GET | Returns dealer weekly summary with items and purchases |
| `createDealerWeeklyBill(Request $request)` | POST | Creates weekly bill via API |
| `createVendorDayLoad(Request $request)` | POST | Creates vendor day-load entry via API |

---

## Middleware Summary

| Middleware | Used By |
|---|---|
| `auth` | All web controllers |
| `auth:sanctum` | All API controllers |
| `guest:web` | Auth login/register |
| `guest:sanctum` | API login |
| `permission:dashboard` | DashboardController |
| `permission:settings` | NotificationController |
| `permission:capital` | CapitalTransactionController |
| `permission:cashbank` | CashBankLedgerController |
| `permission:stock` | StockController (Web) |
| `permission:payments` | PaymentController (Web), all Payments/* controllers |
| `permission:batches` | BirdBatchController |
| `permission:roles` | RoleController |
| `permission:users` | UserManagementController (Admin + API) |
| `permission:customers` | CustomerController |
| `permission:dealers` | DealerController |
| `permission:routes` | RouteController |
| `permission:vendors` | VendorController |
| `permission:purchases` | PurchaseController |
| `permission:inventory` | All Inventory/* controllers |
