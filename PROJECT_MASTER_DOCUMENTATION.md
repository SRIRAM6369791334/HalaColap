# MSR BROILERS Management System — Master Project Documentation

> **Project Name**: Flockwise BizTrack (branded as MSR BROILERS Management System)
> **Framework**: Laravel 12.x | PHP 8.2+
> **Database**: SQLite (default) / MySQL
> **Frontend**: Blade + Alpine.js + TailwindCSS + Vite
> **Total Models**: 43 | **Total Controllers**: 45 | **Total Migrations**: 88 | **Total Blade Views**: 155
> **Generated**: September 4, 2026

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Project Purpose](#2-project-purpose)
3. [Technology Stack](#3-technology-stack)
4. [Complete Module List](#4-complete-module-list)
5. [Database Overview](#5-database-overview)
6. [Complete Table List](#6-complete-table-list)
7. [Complete Model List](#7-complete-model-list)
8. [Complete Controller List](#8-complete-controller-list)
9. [Complete Route List](#9-complete-route-list)
10. [Complete Page List](#10-complete-page-list)
11. [User Roles](#11-user-roles)
12. [Permissions](#12-permissions)
13. [Complete Application Flow](#13-complete-application-flow)
14. [Module-by-Module Workflow](#14-module-by-module-workflow)
15. [Customer/User Guide](#15-customer-user-guide)
16. [Technical Architecture](#16-technical-architecture)
17. [API Summary](#17-api-summary)
18. [External Integrations](#18-external-integrations)
19. [Unknown Business Logic](#19-unknown-business-logic)
20. [Dead/Unused Code](#20-deadunused-code)
21. [Security Observations](#21-security-observations)
22. [Recommended Areas for Business Confirmation](#22-recommended-areas-for-business-confirmation)
23. [Detailed Documentation Index](#23-detailed-documentation-index)

---

## 1. Executive Summary

**MSR BROILERS Management System** (Flockwise BizTrack) is a comprehensive ERP system built for a broiler poultry farming operation. The system manages the complete lifecycle of broiler poultry operations including master data management, day-load billing (daily bird loading and delivery tracking), weekly and daily dealer billing, purchase management, payment tracking for customers/dealers/vendors, expense and EMI management, inventory tracking (items, batches, stock, consumptions, mortalities), profit analysis, reporting, cash/bank ledger, capital management, and role-based access control.

The system serves as the central business management platform for MSR BROILERS, handling financial transactions worth crores (implied by the decimal(12,2) column types and business context).

### Key Statistics
- **43 Eloquent Models** covering all business entities
- **45 Controllers** (12 top-level + 33 in subdirectories including 23 API controllers)
- **88 Database Migrations** showing active schema evolution
- **155 Blade Template Files** with a glassmorphism design system
- **22 Service Classes** containing core business logic
- **150+ Web Routes** + **25+ API Routes**
- **12 Form Request Validation Classes**
- **15 API Resource Classes**
- **4 Background Jobs** (PDF generation, CSV export, WhatsApp sharing, bulk import)
- **3 Console Commands** (ledger audit, backfill, weight fix)
- **15 Model Factories** for testing

---

## 2. Project Purpose

The system is designed to manage a broiler poultry farm business with these core operations:

1. **Bird Lifecycle Management**: Track batches from placement to sale, including mortality and feed consumption
2. **Dealer Network Management**: Manage dealers who purchase birds and sell to end customers
3. **Vendor Management**: Track feed, medicine, and equipment suppliers
4. **Day-Load Billing**: Daily tracking of bird loading, box weights, farm weights, and dealer/vendor settlements
5. **Weekly/Daily Billing**: Periodic invoicing for dealers with GST
6. **Purchase Management**: Record vendor purchases with items, GST, and stock tracking
7. **Payment Processing**: Complex payment allocation with FIFO settlement across day-load entries
8. **Financial Tracking**: Cash/bank ledger, capital transactions, vendor advances
9. **Profitability Analysis**: Per-dealer, per-batch, per-period P&L
10. **Inventory Control**: Items, stock tracking, batch management, consumptions, mortalities

---

## 3. Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend Framework | Laravel | 12.x |
| PHP | PHP | ^8.2 |
| Database | SQLite (default) / MySQL | — |
| ORM | Eloquent | — |
| Authentication | Laravel Session + Sanctum | 4.x |
| Authorization | Spatie Laravel Permission | 6.25 |
| PDF Generation | barryvdh/laravel-dompdf | 3.1 |
| CSS Framework | TailwindCSS | 3.4 |
| JS Framework | Alpine.js | 3.x (CDN) |
| Build Tool | Vite | 5.x |
| Charts | Chart.js (CDN) | — |
| Alerts | SweetAlert2 (CDN) | 11.x |
| Icons | Material Symbols + Phosphor Icons | — |
| Fonts | Outfit, Cabinet Grotesk, JetBrains Mono | — |
| Queue | Database driver | — |
| Session | Database driver | — |
| Cache | Database store | — |

---

## 4. Complete Module List

| # | Module | Description | Key Models |
|---|--------|-------------|------------|
| 1 | Dashboard | KPIs, charts, alerts | — |
| 2 | Masters — Customers | Customer master data | Customer |
| 3 | Masters — Dealers | Dealer master data | Dealer |
| 4 | Masters — Vendors | Vendor master data | Vendor |
| 5 | Masters — Routes | Delivery routes, vehicles, drivers | Route, Vehicle, Driver |
| 6 | Billing — Day-Load | Daily bird loading and settlement | DayLoadBatch, DayLoadEntry, DayLoadInvoice |
| 7 | Billing — Weekly | Weekly dealer invoices | WeeklyBill, WeeklyBillItem |
| 8 | Billing — Daily | Daily dealer invoices | DailyBill, DailyBillItem |
| 9 | Billing — Cash/Bank Ledger | Daily accounting ledger | CashBankLedger |
| 10 | Purchases | Vendor purchase management | Purchase, PurchaseItem |
| 11 | Payments — Customer | Customer payment collection | CustomerPayment |
| 12 | Payments — Dealer | Dealer payment collection | DealerPayment |
| 13 | Payments — Vendor | Vendor payment processing | VendorPayment |
| 14 | Expenses | Business expense tracking | Expense, ExpenseCategory |
| 15 | EMIs | Loan EMI scheduling | Emi |
| 16 | Inventory — Items | Item master (Feed, Chick, etc.) | Item |
| 17 | Inventory — Batches | Bird batch lifecycle | Batch |
| 18 | Inventory — Stock | Real-time stock tracking | StockItem, StockTransaction, StockLedger |
| 19 | Inventory — Consumptions | Feed/medicine usage logs | Consumption |
| 20 | Inventory — Mortalities | Bird death tracking | Mortality |
| 21 | Inventory — Analytics | Batch performance dashboard | — |
| 22 | Profit | P&L analysis | — |
| 23 | Reports | Sales/purchase/customer reports | — |
| 24 | Routes (Fleet) | Vehicle and driver management | Vehicle, Driver, Route |
| 25 | Capital | Investment tracking | CapitalTransaction |
| 26 | Vendor Advances | Pre-payment to vendors | VendorAdvance, VendorAdvanceAdjustment |
| 27 | Admin — Users | User management | User |
| 28 | Admin — Roles | Role management | Role |
| 29 | Admin — Permissions | Permission management | Permission, PermissionGroup |
| 30 | Admin — Activity Logs | Audit trail | ActivityLog |

---

## 5. Database Overview

- **Engine**: SQLite (primary/development) with MySQL support configured
- **Total Tables**: ~50 business tables + framework tables
- **Total Migrations**: 88 files showing active schema evolution from March 2026 to August 2026
- **Key Patterns**: Soft deletes on master data, computed stored columns, composite indexes, foreign key constraints, enum status fields

---

## 6. Complete Table List

| # | Table Name | Purpose |
|---|-----------|---------|
| 1 | users | System users with roles |
| 2 | password_reset_tokens | Password reset |
| 3 | sessions | User sessions |
| 4 | cache / cache_locks | Application cache |
| 5 | jobs / job_batches / failed_jobs | Queue system |
| 6 | personal_access_tokens | Sanctum API tokens |
| 7 | notifications | Database notifications |
| 8 | customers | Customer master |
| 9 | dealers | Dealer master |
| 10 | vendors | Vendor master |
| 11 | vehicles | Delivery vehicles |
| 12 | drivers | Delivery drivers |
| 13 | routes | Delivery routes |
| 14 | weekly_bills | Weekly dealer invoices |
| 15 | weekly_bill_items | Weekly bill line items |
| 16 | daily_bills | Daily dealer invoices |
| 17 | daily_bill_items | Daily bill line items |
| 18 | day_load_batches | Daily bird loading batches |
| 19 | day_load_entries | Individual dealer/vendor loading entries |
| 20 | day_load_invoices | Finalized day-load invoices |
| 21 | entry_adjustment_logs | Day-load change audit trail |
| 22 | purchases | Vendor purchase records |
| 23 | purchase_items | Purchase line items |
| 24 | dealer_purchases | Dealer purchase records |
| 25 | dealer_purchase_items | Dealer purchase line items |
| 26 | customer_payments | Payments from customers |
| 27 | dealer_payments | Payments from dealers |
| 28 | vendor_payments | Payments to vendors |
| 29 | expenses | Business expenses |
| 30 | expense_categories | Expense categories |
| 31 | emis | Loan EMI schedules |
| 32 | items | Poultry inventory items |
| 33 | batches | Bird batches |
| 34 | warehouses | Storage locations |
| 35 | stock_ledgers | Poultry stock ledger |
| 36 | poultry_consumptions | Feed/medicine usage |
| 37 | poultry_mortalities | Bird death records |
| 38 | stock_items | Stock summary |
| 39 | stock_transactions | Stock movement history |
| 40 | bird_batches | Simpler bird batch tracking |
| 41 | capital_transactions | Capital/investment records |
| 42 | vendor_advances | Vendor pre-payments |
| 43 | vendor_advance_adjustments | Advance allocation records |
| 44 | cash_bank_ledgers | Daily cash/bank accounting |
| 45 | activity_logs | User action audit trail |
| 46 | payment_adjustment_logs | Payment change audit trail |
| 47 | permission_groups | Permission grouping |
| 48 | permissions | RBAC permissions |
| 49 | roles | RBAC roles |
| 50 | model_has_permissions | User-permission pivot |
| 51 | model_has_roles | User-role pivot |
| 52 | role_has_permissions | Role-permission pivot |

---

## 7. Complete Model List

| # | Model | Table | Key Relationships |
|---|-------|-------|-------------------|
| 1 | User | users | hasMany ActivityLog; HasRoles (Spatie); HasApiTokens |
| 2 | Customer | customers | hasMany WeeklyBill, DailyBill, CustomerPayment; belongsTo Route |
| 3 | Dealer | dealers | hasMany DealerPayment, DayLoadEntry, DealerPurchase; belongsTo Route |
| 4 | Vendor | vendors | hasMany DayLoadEntry, Purchase, VendorPayment, VendorAdvance |
| 5 | Route | routes | belongsTo Vehicle, Driver; hasMany Customer, Dealer |
| 6 | Vehicle | vehicles | hasMany Route |
| 7 | Driver | drivers | hasMany Route |
| 8 | WeeklyBill | weekly_bills | belongsTo Dealer; hasMany WeeklyBillItem, DealerPurchase, DayLoadEntry |
| 9 | WeeklyBillItem | weekly_bill_items | belongsTo WeeklyBill |
| 10 | DailyBill | daily_bills | belongsTo Customer, Dealer; hasMany DailyBillItem, DayLoadEntry |
| 11 | DailyBillItem | daily_bill_items | belongsTo DailyBill |
| 12 | DayLoadBatch | day_load_batches | hasMany DayLoadEntry; belongsTo DayLoadInvoice |
| 13 | DayLoadEntry | day_load_entries | belongsTo DayLoadBatch, Vendor, Dealer, WeeklyBill, DailyBill; hasMany DealerPayment, VendorPayment, VendorAdvanceAdjustment, EntryAdjustmentLog |
| 14 | DayLoadInvoice | day_load_invoices | belongsTo DayLoadBatch; hasMany DealerPayment |
| 15 | EntryAdjustmentLog | entry_adjustment_logs | belongsTo DayLoadEntry, User |
| 16 | Purchase | purchases | belongsTo Vendor; hasMany PurchaseItem |
| 17 | PurchaseItem | purchase_items | belongsTo Purchase, Item, Batch, Warehouse |
| 18 | DealerPurchase | dealer_purchases | belongsTo Dealer, WeeklyBill; hasMany DealerPurchaseItem |
| 19 | DealerPurchaseItem | dealer_purchase_items | belongsTo DealerPurchase |
| 20 | CustomerPayment | customer_payments | belongsTo Customer |
| 21 | DealerPayment | dealer_payments | belongsTo Dealer, DayLoadInvoice, DayLoadEntry |
| 22 | VendorPayment | vendor_payments | belongsTo Vendor, DayLoadEntry |
| 23 | Expense | expenses | belongsTo ExpenseCategory |
| 24 | ExpenseCategory | expense_categories | hasMany Expense |
| 25 | Emi | emis | belongsTo Customer/Dealer/Vendor (via entity_id) |
| 26 | Item | items | hasMany StockLedger |
| 27 | Batch | batches | — (referenced by Consumption, Mortality, PurchaseItem, StockLedger) |
| 28 | Warehouse | warehouses | — |
| 29 | StockLedger | stock_ledgers | belongsTo Item, Batch, Warehouse |
| 30 | Consumption | poultry_consumptions | belongsTo Batch, Item, Warehouse, User |
| 31 | Mortality | poultry_mortalities | belongsTo Batch, User |
| 32 | StockItem | stock_items | hasMany StockTransaction (by item_name) |
| 33 | StockTransaction | stock_transactions | morphTo (reference) |
| 34 | BirdBatch | bird_batches | — |
| 35 | CapitalTransaction | capital_transactions | belongsTo User |
| 36 | VendorAdvance | vendor_advances | belongsTo Vendor, User; hasMany VendorAdvanceAdjustment |
| 37 | VendorAdvanceAdjustment | vendor_advance_adjustments | belongsTo VendorAdvance, DayLoadEntry |
| 38 | CashBankLedger | cash_bank_ledgers | belongsTo User |
| 39 | ActivityLog | activity_logs | belongsTo User |
| 40 | PaymentAdjustmentLog | payment_adjustment_logs | belongsTo DealerPayment, User |
| 41 | Role | roles | extends Spatie Role |
| 42 | Permission | permissions | extends Spatie Permission; belongsTo PermissionGroup |
| 43 | PermissionGroup | permission_groups | hasMany Permission |

---

## 8. Complete Controller List

| # | Controller | Path | Purpose |
|---|-----------|------|---------|
| 1 | Controller | Http/Controllers/ | Abstract base controller |
| 2 | DashboardController | Http/Controllers/ | Dashboard stats, recent sales, alerts |
| 3 | BirdBatchController | Http/Controllers/ | Bird batch CRUD (legacy) |
| 4 | CapitalTransactionController | Http/Controllers/ | Capital/investment management |
| 5 | CashBankLedgerController | Http/Controllers/ | Ledger index, show, approve |
| 6 | ExpenseController | Http/Controllers/ | Expense + category + EMI management |
| 7 | GlobalSearchController | Http/Controllers/ | Cross-entity search |
| 8 | NotificationController | Http/Controllers/ | Mark notifications read |
| 9 | PaymentController | Http/Controllers/ | Unified payment (possibly legacy) |
| 10 | ProfitController | Http/Controllers/ | P&L views, export |
| 11 | ReportController | Http/Controllers/ | Sales/purchase/customer reports |
| 12 | StockController | Http/Controllers/ | Stock management (possibly legacy) |
| 13 | AuthController | Http/Controllers/Auth/ | Login/logout |
| 14 | DailyBillingController | Http/Controllers/Billing/ | Daily bill CRUD + GST |
| 15 | DailyInvoiceController | Http/Controllers/Billing/ | Empty stub |
| 16 | DayLoadBillingController | Http/Controllers/Billing/ | Day-load entries, bulk, transfer |
| 17 | WeeklyBillingController | Http/Controllers/Billing/ | Weekly bill CRUD + bulk |
| 18 | AnalyticsController | Http/Controllers/Inventory/ | Batch performance analytics |
| 19 | BatchController | Http/Controllers/Inventory/ | Batch CRUD |
| 20 | ConsumptionController | Http/Controllers/Inventory/ | Feed/medicine consumption |
| 21 | ItemController | Http/Controllers/Inventory/ | Item master CRUD |
| 22 | MortalityController | Http/Controllers/Inventory/ | Mortality logs |
| 23 | StockController | Http/Controllers/Inventory/ | Stock dashboard + movements |
| 24 | WarehouseController | Http/Controllers/Inventory/ | Warehouse CRUD |
| 25 | CustomerController | Http/Controllers/Masters/ | Customer CRUD + history |
| 26 | DealerController | Http/Controllers/Masters/ | Dealer CRUD + history |
| 27 | RouteController | Http/Controllers/Masters/ | Route CRUD + vehicles/drivers |
| 28 | VendorController | Http/Controllers/Masters/ | Vendor CRUD + history |
| 29 | CustomerPaymentController | Http/Controllers/Payments/ | Customer payment CRUD |
| 30 | DealerPaymentController | Http/Controllers/Payments/ | Dealer payment CRUD |
| 31 | VendorPaymentController | Http/Controllers/Payments/ | Vendor payment CRUD |
| 32 | PurchaseController | Http/Controllers/Purchases/ | Purchase CRUD + invoices |
| 33 | PermissionController | Http/Controllers/Admin/ | Permission CRUD |
| 34 | RoleController | Http/Controllers/Admin/ | Role CRUD + permissions |
| 35 | UserManagementController | Http/Controllers/Admin/ | User CRUD + toggle status |
| 36-58 | 23 Api/* Controllers | Http/Controllers/Api/ | REST API equivalents |

---

## 9. Complete Route List

### Web Routes (~150 routes)
- **Auth**: GET/POST /login, POST /logout
- **Dashboard**: GET /, GET /dashboard/alerts
- **Global Search**: GET /global-search
- **Notifications**: GET/PUT/POST /notifications/*
- **Masters**: CRUD for customers, dealers, vendors + sub-routes (history, PDF, etc.)
- **Purchases**: entry, invoices, export, print, PDF
- **Inventory**: analytics, stock, warehouses, items, batches, consumptions, mortalities
- **Billing**: day-load (index, store, bulk, transfer, approve, payments), weekly (index, bulk, generate, PDF, export, WhatsApp), daily (index, generate, PDF, export, GST, WhatsApp)
- **Payments**: customer/dealer/vendor CRUD + export + ledger
- **Expenses**: CRUD + categories + EMI management
- **Profit**: index, weekly-detail, monthly, expense-vs-income, export
- **Reports**: sales/purchases daily/weekly/monthly, customer ranking, vendor analytics, PDF exports
- **Routes (Fleet)**: CRUD for routes, vehicles, drivers
- **Admin**: users, roles, permissions, activity-logs
- **Utility**: /run-updates, /export-db-2026, /import-sql-dump (SECURITY CONCERN)

### API Routes (~25 routes)
- All protected by auth:sanctum
- Resource controllers for most modules
- Swagger documentation at /api/v1/docs

---

## 10. Complete Page List

**155 Blade template files** organized across:
- 4 layouts (app, auth, empty, pdf)
- 4 partials (sidebar, topbar, flash-messages, cm-style)
- 20+ reusable components (data-table, stat-card, modal, badge, button, pagination, search, form elements, etc.)
- Dashboard: 2 pages
- Admin: 5+ pages
- Masters: 15+ pages (customers, dealers, vendors)
- Billing: 15+ pages (day-load, weekly, daily, cash-bank-ledger)
- Purchases: 8+ pages
- Payments: 7 pages
- Expenses: 7+ pages (including EMIs)
- Inventory: 17 pages
- Profit: 8 pages
- Reports: 12+ pages
- Routes: 1 page
- Investments: 1 page

---

## 11. User Roles

| Role | Level | Description |
|------|-------|-------------|
| Admin | 4 | Full system access, bypasses all permission checks |
| Manager | 3 | Broad access to operations, can manage billing/payments/purchases |
| Accountant | 3 | Financial operations focus (billing, payments, expenses, reports) |
| Staff | 2 | Basic data entry and operations |
| Data Entry | 2 | Focused on data input tasks |
| Delivery Staff | 2 | Delivery-related operations only |
| Viewer | 1 | Read-only access to all modules |

---

## 12. Permissions

Permissions follow the pattern `{action} {module}`:
- **Actions**: view, create, edit, delete, manage
- **Modules**: customers, dealers, vendors, bills, purchases, payments, expenses, emis, stock, batches, items, consumptions, mortalities, routes, analytics, reports, users, roles, permissions

The `manage` permission acts as a super-permission for each module, granting all CRUD operations.

---

## 13. Complete Application Flow

### Authentication Flow
```
User → Login Page → POST /login → AuthController@login → Validates credentials → Creates session → Redirect to Dashboard
```

### Core Business Flow: Day-Load Billing
```
1. Create/Open Batch (billing_date)
2. Add Day-Load Entries (vendor, dealer, boxes, weights, rates)
3. System auto-computes: bird_weight, loss_weight, total_weight, amount
4. Optional: Transfer weight, Split entries, Apply vendor advances
5. Approve weight loss (creates expense)
6. Finalize invoice
7. Record dealer/vendor payments
```

### Payment Flow (Dealer — FIFO Allocation)
```
Dealer Payment → Allocate to EMIs → Reduce pending_amount → Allocate to day-load entries (FIFO) → Excess as advance
```

### Cash/Bank Ledger Flow
```
Daily: Get/Create ledger → Calculate income from all sources → Calculate expenses → Compute closing balances → Cascade to next day
```

### Purchase Flow
```
Purchase Entry → Create Purchase + Items → Auto-create Items → Record Stock In → Create Expense → Recalculate Ledger → Optionally create EMI
```

---

## 14. Module-by-Module Workflow

See [docs/11-application-flows.md](docs/11-application-flows.md) for detailed Mermaid flow diagrams of all 14 major workflows.

---

## 15. Customer/User Guide

See [docs/12-user-guide.md](docs/12-user-guide.md) for the complete non-technical user guide.

---

## 16. Technical Architecture

See [docs/02-project-architecture.md](docs/02-project-architecture.md) for the complete architecture documentation.

**Key Design Patterns**:
- Service Layer (22 services)
- Repository Pattern (Stock, Purchase)
- Form Request Validation (12 request classes)
- API Resources (15 resource classes)
- Observer Pattern (Customer, Item cache invalidation)
- Policy Pattern (User, Role, Permission)
- Job/Queue Pattern (4 background jobs)
- Component-Based UI (20+ Blade components)

---

## 17. API Summary

- **Base URL**: `/api/v1`
- **Authentication**: Laravel Sanctum tokens
- **Documentation**: Swagger UI at `/api/v1/docs`
- **25+ endpoints** covering auth, dashboard, masters, billing, purchases, payments, expenses, inventory, profit, reports, and users
- See [docs/14-api-documentation.md](docs/14-api-documentation.md) for complete documentation.

---

## 18. External Integrations

1. **PDF Generation** — barryvdh/laravel-dompdf (active)
2. **WhatsApp Sharing** — Simulated/placeholder only
3. **Google Maps** — CDN loaded but not actively used
4. **CDN Libraries** — Tailwind, Alpine.js, Chart.js, SweetAlert2, Material Symbols, Phosphor Icons

---

## 19. Unknown Business Logic

1. **Day-Load system**: The exact business process for daily bird loading needs confirmation
2. **Weight loss approval**: Threshold rules for when approval is needed
3. **Cash/Bank ledger approval**: Who should approve and when
4. **Vendor advance system**: When advances are applied vs when they're created
5. **Dealer vs Customer billing**: The transition from customer-based to dealer-based weekly billing
6. **Dual stock systems**: Why both stock_transactions/stock_items AND stock_ledgers/items exist

---

## 20. Dead/Unused Code

1. **PaymentController** (top-level) — Possibly legacy, superseded by Payments/ controllers
2. **StockController** (top-level) — Possibly superseded by Inventory/StockController
3. **BirdBatchController** (top-level) — Possibly superseded by Inventory/BatchController
4. **DailyInvoiceController** — Empty stub class
5. **Profit pages**: order-wise, comparison, batch — "Coming Soon" placeholders
6. **Google Maps** — Script loaded but not used
7. **WhatsApp jobs** — Simulated, no real API

---

## 21. Security Observations

### HIGH RISK
1. **`/export-db-2026`** — Exposes full database dump via HTTP GET to any authenticated user
2. **`/import-sql-dump`** — Allows SQL import from a file via HTTP GET
3. **`/run-updates`** — Runs migrations + payment reallocation on HTTP GET
4. **`/live-deploy-sync-2026`** — Runs migrations + cache clears on HTTP GET
5. **`/run-batch-migration`** — Modifies schema on HTTP GET

### MEDIUM RISK
6. Utility routes lack admin-only middleware
7. No rate limiting on some sensitive operations
8. Database session/cache in production without encryption verification

### LOW RISK
9. CSRF protection present via Laravel middleware
10. Security headers added via SecurityHeadersMiddleware
11. API rate limiting via ApiRateLimitMiddleware

---

## 22. Recommended Areas for Business Confirmation

1. **Day-Load billing process**: Confirm the exact daily workflow
2. **Weight loss thresholds**: What triggers approval requirements?
3. **Ledger approval workflow**: Who approves and what are the rules?
4. **Permission matrix**: Verify the exact permissions needed per role
5. **Vendor advance business rules**: When are advances created vs applied?
6. **GST rates**: Confirm default 18% and any category-specific rates
7. **EMI entity types**: Customer vs Dealer vs Vendor EMI processes
8. **Dealer pending_amount**: How it's calculated and maintained
9. **Report requirements**: Which reports are actively used?
10. **WhatsApp integration**: Is real integration planned?

---

## 23. Detailed Documentation Index

| # | Document | Path | Content |
|---|----------|------|---------|
| 01 | Project Overview | docs/01-project-overview.md | Project name, purpose, tech stack |
| 02 | Architecture | docs/02-project-architecture.md | Layer architecture, design patterns |
| 03 | Database Schema | docs/03-database-schema.md | All 52 tables with columns |
| 04 | Relationships | docs/04-database-relationships.md | All foreign keys and relationships |
| 05 | Models | docs/05-models.md | 43 models with details |
| 06 | Controllers | docs/06-controllers.md | 45 controllers with methods |
| 07 | Controller Functions | docs/07-controller-functions.md | Every method documented |
| 08 | Routes | docs/08-routes.md | All 175+ routes |
| 09 | Pages & Blade | docs/09-pages-and-blade-files.md | 155 Blade files |
| 10 | JavaScript | docs/10-javascript-functions.md | JS functions and Alpine patterns |
| 11 | Application Flows | docs/11-application-flows.md | 14 workflows with Mermaid |
| 12 | User Guide | docs/12-user-guide.md | Non-technical user manual |
| 13 | Roles & Permissions | docs/13-roles-and-permissions.md | RBAC matrix |
| 14 | API Documentation | docs/14-api-documentation.md | All API endpoints |
| 15 | Integrations | docs/15-integrations.md | External services |
| 16 | File Structure | docs/16-file-structure.md | Complete directory tree |
| 17 | Dead Code | docs/17-unknown-or-dead-code.md | Unused code detection |
| 18 | Business Logic | docs/18-business-logic-findings.md | Business process findings |

---

*This document was generated by automated codebase analysis. All findings are traceable to actual source code. Items marked as "Needs Business Confirmation" require validation from business stakeholders.*
