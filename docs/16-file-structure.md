# Complete File Structure — Flockwise BizTrack

## Summary

| Directory | File Count | Description |
|-----------|------------|-------------|
| `app/Http/Controllers` | 45 | Web and API controllers |
| `app/Models` | 43 | Eloquent models |
| `app/Services` | 23 | Business logic services (22 + 1 tax) |
| `app/Jobs` | 4 | Queued jobs |
| `app/Observers` | 2 | Model observers |
| `app/Policies` | 3 | Authorization policies |
| `app/Notifications` | 1 | Database notification class |
| `app/Repositories` | 4 | 2 repositories + 2 interfaces |
| `app/Http/Requests` | 12 | Form request validation classes |
| `app/Http/Resources` | 15 | API resource transformers |
| `app/Http/Middleware` | 3 | Custom middleware |
| `app/Console/Commands` | 3 | Artisan commands |
| `app/Providers` | 2 | Service providers |
| `database/migrations` | 88 | Database migrations |
| `database/seeders` | 12 | Database seeders |
| `database/factories` | 15 | Model factories |
| `resources/views` | 155 | Blade templates |
| `routes` | 3 | Route files (web, api, console) |
| `config` | 13 | Configuration files |

---

## Directory Tree

```
web/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── BackupDatabase.php
│   │       ├── ClearDashboardCache.php
│   │       └── SyncStockFromPurchases.php
│   │
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   └── AuthController.php
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── BatchController.php
│   │   │   │   ├── BirdBatchController.php
│   │   │   │   ├── ConsumptionController.php
│   │   │   │   ├── CustomerPaymentController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── DealerPaymentController.php
│   │   │   │   ├── ExpenseController.php
│   │   │   │   ├── InventoryController.php
│   │   │   │   ├── ItemController.php
│   │   │   │   ├── MortalityController.php
│   │   │   │   ├── ProfitController.php
│   │   │   │   ├── PurchaseController.php
│   │   │   │   ├── ReportController.php
│   │   │   │   ├── StockController.php
│   │   │   │   ├── UserController.php
│   │   │   │   ├── VendorPaymentController.php
│   │   │   │   ├── WarehouseController.php
│   │   │   │   └── masters/
│   │   │   │       ├── CustomerController.php
│   │   │   │       ├── DealerController.php
│   │   │   │       ├── RouteController.php
│   │   │   │       └── VendorController.php
│   │   │   ├── Billing/
│   │   │   │   ├── DailyInvoiceController.php
│   │   │   │   ├── DailyReportController.php
│   │   │   │   ├── WeeklyInvoiceController.php
│   │   │   │   └── WeeklyReportController.php
│   │   │   ├── Inventory/
│   │   │   │   ├── BatchController.php
│   │   │   │   ├── BirdBatchController.php
│   │   │   │   ├── ConsumptionController.php
│   │   │   │   ├── ItemController.php
│   │   │   │   ├── MortalityController.php
│   │   │   │   ├── StockController.php
│   │   │   │   └── WarehouseController.php
│   │   │   ├── Masters/
│   │   │   │   ├── CustomerController.php
│   │   │   │   ├── DealerController.php
│   │   │   │   ├── RouteController.php
│   │   │   │   └── VendorController.php
│   │   │   ├── Payments/
│   │   │   │   ├── CustomerPaymentController.php
│   │   │   │   ├── DealerPaymentController.php
│   │   │   │   └── VendorPaymentController.php
│   │   │   ├── Payments/ (top-level, legacy)
│   │   │   │   └── PaymentController.php
│   │   │   ├── Profit/
│   │   │   │   ├── BatchWiseController.php
│   │   │   │   ├── ComparisonController.php
│   │   │   │   ├── OrderWiseController.php
│   │   │   │   ├── OverviewController.php
│   │   │   │   └── PartyWiseController.php
│   │   │   ├── Reports/
│   │   │   │   ├── CustomerReportController.php
│   │   │   │   ├── DealerReportController.php
│   │   │   │   ├── PurchaseReportController.php
│   │   │   │   ├── SalesReportController.php
│   │   │   │   └── VendorReportController.php
│   │   │   ├── BirdBatchController.php (top-level, legacy)
│   │   │   ├── DashboardController.php
│   │   │   ├── ExpenseController.php
│   │   │   ├── HomeController.php
│   │   │   ├── NotificationController.php
│   │   │   ├── ProfitController.php
│   │   │   ├── PurchaseController.php
│   │   │   ├── ReportController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── StockController.php (top-level, legacy)
│   │   │   └── UserController.php
│   │   │
│   │   ├── Middleware/
│   │   │   ├── CheckPermission.php
│   │   │   ├── PreventDisabledUser.php
│   │   │   └── TrackActivity.php
│   │   │
│   │   ├── Requests/
│   │   │   ├── StoreCustomerRequest.php
│   │   │   ├── UpdateCustomerRequest.php
│   │   │   ├── StoreDealerRequest.php
│   │   │   ├── UpdateDealerRequest.php
│   │   │   ├── StoreVendorRequest.php
│   │   │   ├── UpdateVendorRequest.php
│   │   │   ├── StorePurchaseRequest.php
│   │   │   ├── UpdatePurchaseRequest.php
│   │   │   ├── StoreExpenseRequest.php
│   │   │   ├── StoreDailyBillRequest.php
│   │   │   ├── StoreWeeklyBillRequest.php
│   │   │   └── StorePaymentRequest.php
│   │   │
│   │   └── Resources/
│   │       ├── CustomerResource.php
│   │       ├── DealerResource.php
│   │       ├── VendorResource.php
│   │       ├── RouteResource.php
│   │       ├── WarehouseResource.php
│   │       ├── BatchResource.php
│   │       ├── BirdBatchResource.php
│   │       ├── ItemResource.php
│   │       ├── PurchaseResource.php
│   │       ├── ExpenseResource.php
│   │       ├── DailyBillResource.php
│   │       ├── WeeklyBillResource.php
│   │       ├── PaymentResource.php
│   │       ├── StockResource.php
│   │       └── UserResource.php
│   │
│   ├── Jobs/
│   │   ├── ShareInvoiceWhatsAppJob.php
│   │   ├── GenerateWeeklyBillsJob.php
│   │   ├── RecalculateCashBankLedgerJob.php
│   │   └── ClearDashboardCacheJob.php
│   │
│   ├── Models/
│   │   ├── User.php
│   │   ├── Customer.php
│   │   ├── Dealer.php
│   │   ├── Vendor.php
│   │   ├── Route.php
│   │   ├── Warehouse.php
│   │   ├── Batch.php
│   │   ├── BirdBatch.php
│   │   ├── BirdBatchEntry.php
│   │   ├── Item.php
│   │   ├── ItemCategory.php
│   │   ├── Purchase.php
│   │   ├── Expense.php
│   │   ├── ExpenseCategory.php
│   │   ├── Payment.php
│   │   ├── DailyBill.php
│   │   ├── DailyBillEntry.php
│   │   ├── WeeklyBill.php
│   │   ├── WeeklyBillEntry.php
│   │   ├── StockItem.php
│   │   ├── StockTransaction.php
│   │   ├── StockLedger.php
│   │   ├── Mortality.php
│   │   ├── Consumption.php
│   │   ├── CashBankLedger.php
│   │   ├── VendorAdvance.php
│   │   ├── EMI.php
│   │   ├── EMIPayment.php
│   │   ├── DealerDayLoad.php
│   │   ├── DealerDayLoadEntry.php
│   │   ├── DealerDayLoadEntryAdjustment.php
│   │   ├── DealerPaymentAllocation.php
│   │   ├── DealerPaymentAdjustmentLog.php
│   │   ├── Invoice.php
│   │   ├── InvoiceEntry.php
│   │   ├── Notification.php
│   │   ├── ActivityLog.php
│   │   ├── Setting.php
│   │   ├── Tax.php
│   │   ├── GSTRate.php
│   │   ├── OpeningBalance.php
│   │   ├── Permission.php
│   │   └── Role.php
│   │
│   ├── Notifications/
│   │   └── GeneralNotification.php
│   │
│   ├── Observers/
│   │   ├── ItemObserver.php
│   │   └── CustomerObserver.php
│   │
│   ├── Policies/
│   │   ├── UserPolicy.php
│   │   ├── ExpensePolicy.php
│   │   └── BatchPolicy.php
│   │
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   └── AuthServiceProvider.php
│   │
│   ├── Repositories/
│   │   ├── CustomerRepository.php
│   │   ├── DealerRepository.php
│   │   ├── Interfaces/
│   │   │   ├── CustomerRepositoryInterface.php
│   │   │   └── DealerRepositoryInterface.php
│   │
│   └── Services/
│       ├── BillingService.php
│       ├── CashBankLedgerService.php
│       ├── CustomerService.php
│       ├── DashboardService.php
│       ├── DealerPaymentService.php
│       ├── DealerService.php
│       ├── ExpenseService.php
│       ├── InventoryService.php
│       ├── InvoiceService.php
│       ├── ItemService.php
│       ├── MortalityService.php
│       ├── NotificationService.php
│       ├── OpeningBalanceService.php
│       ├── ProfitService.php
│       ├── PurchaseService.php
│       ├── ReportService.php
│       ├── RouteService.php
│       ├── StockService.php
│       ├── TaxService.php
│       ├── VendorAdvanceService.php
│       ├── VendorPaymentService.php
│       ├── VendorService.php
│       └── WarehouseService.php
│
├── config/
│   ├── app.php
│   ├── auth.php
│   ├── broadcasting.php
│   ├── cache.php
│   ├── cors.php
│   ├── database.php
│   ├── filesystems.php
│   ├── hashing.php
│   ├── logging.php
│   ├── mail.php
│   ├── sanctum.php
│   ├── services.php
│   └── spatie-permission.php
│
├── database/
│   ├── factories/
│   │   ├── UserFactory.php
│   │   ├── CustomerFactory.php
│   │   ├── DealerFactory.php
│   │   ├── VendorFactory.php
│   │   ├── BatchFactory.php
│   │   ├── BirdBatchFactory.php
│   │   ├── ItemFactory.php
│   │   ├── PurchaseFactory.php
│   │   ├── ExpenseFactory.php
│   │   ├── PaymentFactory.php
│   │   ├── DailyBillFactory.php
│   │   ├── WeeklyBillFactory.php
│   │   ├── MortalityFactory.php
│   │   ├── ConsumptionFactory.php
│   │   └── StockItemFactory.php
│   │
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── ... (88 total migration files)
│   │   └── (covering all tables: users, roles, permissions, customers,
│   │        dealers, vendors, routes, warehouses, batches, bird_batches,
│   │        items, purchases, expenses, payments, daily_bills, weekly_bills,
│   │        stock_items, stock_transactions, stock_ledgers, mortalities,
│   │        consumptions, cash_bank_ledgers, vendor_advances, emis,
│   │        dealer_day_loads, invoices, notifications, settings, etc.)
│   │
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleSeeder.php
│       ├── PermissionSeeder.php
│       ├── UserSeeder.php
│       ├── RouteSeeder.php
│       ├── WarehouseSeeder.php
│       ├── ItemCategorySeeder.php
│       ├── ExpenseCategorySeeder.php
│       ├── CustomerSeeder.php
│       ├── DealerSeeder.php
│       ├── VendorSeeder.php
│       └── TaxSeeder.php
│
├── docs/
│   ├── 01-system-overview.md
│   ├── 02-tech-stack.md
│   ├── 03-database-schema.md
│   ├── 04-architecture.md
│   ├── 05-routes.md
│   ├── 06-auth-and-permissions.md
│   ├── 07-billing-system.md
│   ├── 08-inventory.md
│   ├── 09-financial-system.md
│   ├── 10-reports.md
│   ├── 11-services.md
│   ├── 12-frontend.md
│   ├── 13-testing.md
│   ├── 14-api-documentation.md
│   ├── 15-integrations.md
│   ├── 16-file-structure.md
│   ├── 17-unknown-or-dead-code.md
│   └── 18-business-logic-findings.md
│
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   ├── app.blade.php
│   │   │   ├── pdf.blade.php
│   │   │   └── guest.blade.php
│   │   ├── auth/
│   │   │   ├── login.blade.php
│   │   │   └── register.blade.php
│   │   ├── dashboard/
│   │   │   └── index.blade.php
│   │   ├── masters/
│   │   │   ├── customers/
│   │   │   ├── dealers/
│   │   │   ├── vendors/
│   │   │   ├── routes/
│   │   │   └── warehouses/
│   │   ├── billing/
│   │   │   ├── daily/
│   │   │   └── weekly/
│   │   ├── inventory/
│   │   │   ├── batches/
│   │   │   ├── bird-batches/
│   │   │   ├── items/
│   │   │   ├── mortalities/
│   │   │   ├── consumptions/
│   │   │   └── stock/
│   │   ├── purchases/
│   │   │   ├── index.blade.php
│   │   │   ├── create.blade.php
│   │   │   ├── show.blade.php
│   │   │   └── invoices-pdf.blade.php
│   │   ├── expenses/
│   │   │   ├── index.blade.php
│   │   │   ├── create.blade.php
│   │   │   └── categories.blade.php
│   │   ├── payments/
│   │   │   ├── customers/
│   │   │   ├── dealers/
│   │   │   └── vendors/
│   │   ├── profit/
│   │   │   ├── overview.blade.php
│   │   │   ├── party-wise.blade.php
│   │   │   ├── batch-wise.blade.php
│   │   │   ├── order-wise.blade.php (Coming Soon)
│   │   │   ├── comparison.blade.php (Coming Soon)
│   │   │   └── batch.blade.php (Under Construction)
│   │   ├── reports/
│   │   │   ├── sales/
│   │   │   ├── purchases/
│   │   │   ├── customers/
│   │   │   ├── dealers/
│   │   │   ├── vendors/
│   │   │   └── pdf/
│   │   ├── settings/
│   │   │   └── index.blade.php
│   │   ├── users/
│   │   │   ├── index.blade.php
│   │   │   ├── create.blade.php
│   │   │   └── edit.blade.php
│   │   ├── components/
│   │   │   ├── alert.blade.php
│   │   │   ├── modal.blade.php
│   │   │   ├── table.blade.php
│   │   │   ├── stat-card.blade.php
│   │   │   └── ...
│   │   └── notifications/
│   │       └── index.blade.php
│   │
│   ├── css/
│   │   └── app.css
│   │
│   └── js/
│       └── app.js
│
├── routes/
│   ├── web.php
│   ├── api.php
│   └── console.php
│
├── public/
│   ├── index.php
│   ├── .htaccess
│   └── build/ (Vite compiled assets)
│
├── bootstrap/
│   ├── app.php
│   └── cache/
│
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
│
├── .env.example
├── artisan
├── composer.json
├── package.json
├── vite.config.js
├── phpunit.xml
├── tailwind.config.js
├── postcss.config.js
└── README.md
```

---

## File Count Breakdown by Category

### Controllers (45 total)
- **Auth**: 2 (web + API)
- **API**: 17 + 4 masters sub-namespace
- **Billing**: 4
- **Inventory**: 7
- **Masters**: 4
- **Payments**: 3 + 1 legacy
- **Profit**: 5
- **Reports**: 5
- **Top-level**: 8 (Dashboard, Expense, Home, Notification, Profit, Purchase, Report, Settings, Stock, User)

### Models (43 total)
- Core entities: User, Customer, Dealer, Vendor, Route, Warehouse
- Inventory: Batch, BirdBatch, BirdBatchEntry, Item, ItemCategory, StockItem, StockTransaction, StockLedger, Mortality, Consumption
- Billing: DailyBill, DailyBillEntry, WeeklyBill, WeeklyBillEntry, Invoice, InvoiceEntry
- Financial: Payment, Expense, ExpenseCategory, CashBankLedger, VendorAdvance, EMI, EMIPayment, DealerDayLoad, DealerDayLoadEntry, DealerDayLoadEntryAdjustment, DealerPaymentAllocation, DealerPaymentAdjustmentLog
- System: Notification, ActivityLog, Setting, Tax, GSTRate, OpeningBalance, Permission, Role

### Services (23 total)
- Business domain services: Billing, CashBankLedger, Customer, Dashboard, DealerPayment, Dealer, Expense, Inventory, Invoice, Item, Mortality, Notification, OpeningBalance, Profit, Purchase, Report, Route, Stock, Tax, VendorAdvance, VendorPayment, Vendor, Warehouse

### Migrations (88 total)
Covering all database tables including: users, roles, permissions, model_has_roles, model_has_permissions, role_has_permissions, customers, dealers, vendors, routes, warehouses, batches, bird_batches, bird_batch_entries, items, item_categories, purchases, expenses, expense_categories, payments, daily_bills, daily_bill_entries, weekly_bills, weekly_bill_entries, stock_items, stock_transactions, stock_ledgers, mortalities, consumptions, cash_bank_ledgers, vendor_advances, emis, emi_payments, dealer_day_loads, dealer_day_load_entries, dealer_day_load_entry_adjustments, dealer_payment_allocations, dealer_payment_adjustment_logs, invoices, invoice_entries, notifications, activity_logs, settings, taxes, gst_rates, opening_balances, and more.
