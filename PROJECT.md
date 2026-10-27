# Project: Flockwise BizTrack Full-Spectrum QA Verification & Integrity Hardening

## Architecture
- **Framework**: Laravel 11 / PHP 8.2 on Windows/XAMPP
- **Multi-Tenancy**: Shared database architecture with `TenantGlobalScope` and `BelongsToTenant` trait enforcing `tenant_id` isolation across 39 business models; request context resolved via `ResolveTenantContext` middleware.
- **Authorization**: Spatie Laravel-Permission with team-based tenancy permissions (`setPermissionsTeamId($tenant->id)`). Platform Super Admin role and `is_super_admin` flag.
- **Financial Architecture**: Real-time liability ledger with dynamic balance accrual (`dayload_outstanding`), 4-tier FIFO allocation for dealer payments, continuous date-ordered Cash & Bank ledger roll-forward (`recalculateForDate` with forward cascading), and atomic deletion reversals.
- **Inventory & Crate Conservation**: DayLoad batches enforce crate conservation invariance ($\text{remaining} + \text{split} = \text{original}$) throwing `BoxConservationException`.

## Feature Inventory
| # | Feature | Description | Milestone | Source |
|---|---------|-------------|-----------|--------|
| 1 | Dealer CRUD & Validations | Positive CRUD; negative checks (blank firm name, negative opening balance -50000, string in numeric); zero cross-tenant leakage | M2 | R1 |
| 2 | Vendor CRUD & Validations | Positive CRUD; negative checks (invalid phone, invalid GST format); purchase dependency check | M2 | R1 |
| 3 | Customer CRUD & Validations | Full CRUD, positive and negative validation on contact/balance fields | M2 | R1 |
| 4 | Items & Inventory CRUD | Feed, Chick, Medicine, Vaccine items; unit selection and rate validation; stock ledger dependency protection | M2 | R1 |
| 5 | Batches & Mortality CRUD | Batch creation (initial count > 0); daily mortality updates (mortality <= current count); batch closure; fix missing relationships | M1, M2 | R1 |
| 6 | Dependency Deletion Protection | Add application-level transaction checks to Dealer & Vendor controllers before soft-delete | M1, M2 | R1 |
| 7 | Dayload Poultry Math | Net weight (Gross - Tare), Average bird weight (Net / Count), Bill Total (Net * Rate), Dealer balance increment | M3 | R2 |
| 8 | Dayload Negative Validations | Gross < Tare boundary rejection, zero bird count rejection, negative rate rejection, BoxConservationException on crate mismatch | M3 | R2 |
| 9 | Dayload Recalculation on Update | Rate change (e.g. ₹125 to ₹130) recalculates bill total and automatically accrues balance delta | M3 | R2 |
| 10 | Dayload Reversal on Delete | Deleting bill reverses dealer balance, voids linked payments, and restores crate inventory | M3 | R2 |
| 11 | Dealer Payments & 4-Tier FIFO | Positive payment creation, 4-tier FIFO allocation against oldest bills, negative amount rejection, receipt display | M3 | R3 |
| 12 | Payment Void & Reversal | Voiding payment restores dealer outstanding balance and reverses cash book entries | M3 | R3 |
| 13 | Vendor Payments & Purchases | Record purchases, track vendor balances, record payments with cheques/references | M3 | R3 |
| 14 | Cash & Bank Ledger | Daily roll-forward (yesterday closing = today opening), forward cascading, contra entries via CapitalTransaction | M3 | R3 |
| 15 | Expenses & EMIs | Create expense deducts cash book, update expense adjusts difference, delete expense restores cash book; EMI schedule tracking | M3 | R3 |
| 16 | Staff Users & Roles | Create staff user under tenant with Spatie team permissions; reject duplicate emails; password confirmation | M4 | R4 |
| 17 | Platform Super Admin Protection | Modifying, deactivating, or deleting Platform Super Admin (admin@poultry.com) strictly returns HTTP 403 Forbidden | M4 | R4 |
| 18 | SaaS Tenants CRUD & Barrier | Non-super-admins accessing /admin/tenants return HTTP 403 Forbidden across all verbs; Primary Tenant #1 deletion/deactivation blocked | M4 | R4 |
| 19 | Cross-Tenant IDOR Probes | Route model binding returns 404 across all CRUD endpoints; fix DailyBillingController::show parameter mismatch | M1, M4 | R5 |
| 20 | Canonical Test Suite Verification | Execute tests/run-all-tests.php across all test tiers with 100% pass rate and zero regressions | M5 | R6 |
| 21 | Playwright Visual Receipts | Capture browser screenshots documenting validation error states and CRUD views | M6 | R6 |
| 22 | Forensic Integrity Audit | Independent Forensic Auditor verifies authenticity, static analysis, runtime execution, and zero cheating | M6 | R6 |

## Milestones
| # | Name | Scope | Dependencies | Status |
|---|------|-------|-------------|--------|
| 1 | Core Model & Protection Fixes | Fix Batch model relationships (`consumptions`, `mortalities`), add Dealer/Vendor deletion dependency guards in controllers, and fix `DailyBillingController::show` route parameter mismatch | none | PLANNED |
| 2 | Master Records CRUD & Validation Tests | Author and execute automated feature tests for Dealers, Vendors, Customers, Items, and Batches verifying full CRUD, negative validations, dependency protection, and tenant isolation | M1 | PLANNED |
| 3 | Financial, Billing & Ledger Verification Tests | Author and execute automated feature tests for Dayload billing math, crate conservation, rate recalculations, bill reversals, FIFO payments, payment voids, cash/bank ledger roll-forward, and expense reversals | M1 | PLANNED |
| 4 | Security, Tenancy & IDOR Verification Tests | Author and execute automated feature tests for Users, Roles, Super Admin 403 protection, Tenant barrier 403 checks, and cross-tenant IDOR probes across all CRUD routes | M1 | PLANNED |
| 5 | Canonical Test Suite Execution (100% Pass) | Run canonical test runner `tests/run-all-tests.php` and verify 100% pass rate with zero regressions | M2, M3, M4 | PLANNED |
| 6 | Visual Receipts & Forensic Audit | Capture Playwright screenshots of validation states and CRUD views; execute independent Forensic Auditor verification | M5 | PLANNED |

## Interface Contracts
### Dealer & Vendor Controllers ↔ Transaction Dependency Guard
- Signature: `hasActiveTransactions(): bool`
- Precondition: When user requests DELETE on `/masters/dealers/{id}` or `/masters/vendors/{id}`.
- Contract: Check for existing `dayLoadEntries()`, `dealerPayments()`, `dailyBills()`, `purchases()`, or `vendorPayments()`. If exists, abort with `back()->with('error', 'Cannot delete ... with active transactions.')` or HTTP 422/400. If unreferenced, proceed to soft delete.

### DailyBillingController ↔ Route Model Binding
- Parameter Contract: Method signature `show(DailyBill $daily): View` must match route parameter `{daily}` in `routes/web.php`.
- Contract: Cross-tenant lookup returns HTTP 404 (via `TenantGlobalScope`), not HTTP 500.

### Batch Model ↔ Analytics
- Contract: `Batch::consumptions()` defines `hasMany(DailyConsumption::class)` and `Batch::mortalities()` defines `hasMany(Mortality::class)`.
- Precondition: `AnalyticsController::index` calls `Batch::with(['consumptions.item', 'mortalities'])->findOrFail($id)`.

## Code Layout
- Controllers: `app/Http/Controllers/`
  - Masters: `app/Http/Controllers/Masters/` (Dealer, Vendor, Customer)
  - Inventory: `app/Http/Controllers/Inventory/` (Item, Batch, Mortality, Analytics)
  - Billing: `app/Http/Controllers/Billing/` (DayLoadBilling, DailyBilling)
  - Payments: `app/Http/Controllers/Payments/` (DealerPayment, VendorPayment)
  - Finance: `app/Http/Controllers/Finance/` (CashBankLedger, Expense, Emi)
  - Admin: `app/Http/Controllers/Admin/` (UserManagement, Role, TenantManagement)
- Models: `app/Models/` (Dealer, Vendor, Customer, Item, Batch, DayLoadEntry, DealerPayment, CashBankLedger, Tenant, User)
- Tests: `tests/`
  - Canonical Runner: `tests/run-all-tests.php`
  - Feature Tests: `tests/Feature/`
  - Security Tests: `tests/Feature/Security/`
  - Visual Receipts: `screenshots/` or `tests/Visual/`
