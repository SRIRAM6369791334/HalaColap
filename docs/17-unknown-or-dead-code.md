# Dead Code and Unknown Code Detection — Flockwise BizTrack

## Potentially Unused / Legacy Controllers

### 1. `PaymentController.php` (top-level)

| Field | Details |
|-------|---------|
| **Location** | `app/Http/Controllers/Payments/PaymentController.php` |
| **Status** | Likely legacy |
| **Reason** | Payment routes are now handled by `Payments/CustomerPaymentController`, `Payments/DealerPaymentController`, and `Payments/VendorPaymentController`. This unified controller may have no active routes. |

### 2. `StockController.php` (top-level)

| Field | Details |
|-------|---------|
| **Location** | `app/Http/Controllers/StockController.php` |
| **Status** | Likely superseded |
| **Reason** | May be superseded by `Inventory/StockController` which handles the newer stock ledger system. |

### 3. `BirdBatchController.php` (top-level)

| Field | Details |
|-------|---------|
| **Location** | `app/Http/Controllers/BirdBatchController.php` |
| **Status** | Likely superseded |
| **Reason** | May be superseded by `Inventory/BirdBatchController` which is properly namespaced. |

### 4. `Billing/DailyInvoiceController.php`

| Field | Details |
|-------|---------|
| **Location** | `app/Http/Controllers/Billing/DailyInvoiceController.php` |
| **Status** | Empty stub |
| **Reason** | Empty class with no methods. Not registered in any route file. Placeholder that was never implemented. |

---

## Suspicious Code Patterns

### 1. Utility Routes in `web.php`

Several routes perform destructive operations via HTTP GET requests without admin-only middleware:

| Route | Operation | Risk |
|-------|-----------|------|
| `/run-updates` | Runs migrations + payment reallocation | **High** — any authenticated user could trigger |
| `/export-db-2026` | Exposes full SQL database dump | **Critical** — data exfiltration risk |
| `/import-sql-dump` | Imports SQL from file | **Critical** — arbitrary data injection |
| `/run-batch-migration` | Runs schema changes | **High** — data modification |
| `/live-deploy-sync-2026` | Runs migrations + cache clears | **High** — production disruption |

**Recommendation:** These routes should be:
- Removed entirely from production
- Protected by admin-only middleware at minimum
- Converted to Artisan commands or scheduled tasks
- Never exposed via GET (use POST with CSRF + auth)

### 2. WhatsApp Simulation

| Component | Details |
|-----------|---------|
| **Job** | `ShareInvoiceWhatsAppJob` |
| **Behavior** | Only logs the intended message payload; no actual API call |
| **Impact** | Users may believe messages were sent when they were not |

### 3. Google Maps Script

| Component | Details |
|-----------|---------|
| **Location** | `resources/views/layouts/app.blade.php` |
| **Behavior** | Loads Google Maps JS API on every page |
| **Impact** | Unnecessary network request, potential API quota usage, no visible feature uses it |

---

## Potential Missing Validation

1. **API Controllers** — Some endpoints in `Api/` controllers may not have corresponding `FormRequest` validation classes. Input validation should be verified per endpoint.

2. **Day-load bulk store** — The bulk day-load creation endpoint accepts an array of entries. Validation for:
   - Maximum payload size
   - Box count conservation
   - Weight consistency (bird_weight + box_weight matches total)
   - Rate bounds checking
   
   should be confirmed as present.

---

## Potential Security Issues

### 1. Utility Route Access Control
The utility/debug routes (`/run-updates`, `/export-db-2026`, etc.) do not appear to have admin-only middleware. Any authenticated user could access them.

### 2. Database Export Route
`/export-db-2026` exposes a complete SQL dump of the database via HTTP. This could leak:
- Customer personal data (phone numbers, addresses)
- Financial records (payments, invoices, balances)
- User credentials (hashed passwords)
- Business-critical data

### 3. CSRF Protection
API routes are protected by Sanctum token authentication. However, web routes should have CSRF protection via `VerifyCsrfToken` middleware. Verify that all state-changing web routes use POST/PUT/DELETE methods.

---

## Duplicate / Overlapping Logic

### 1. Two Inventory Stock Systems

| System | Tables | Status |
|--------|--------|--------|
| **Older** | `stock_transactions` / `stock_items` | Legacy — used by `StockService` |
| **Newer** | `stock_ledgers` / `items` | Poultry-specific — used by `InventoryService` |

Both systems coexist. The older system handles general item stock while the newer handles poultry-specific inventory with batch tracking. Migration between them may be incomplete.

### 2. Two Batch Systems

| System | Table | Purpose |
|--------|-------|---------|
| **Poultry Batches** | `batches` | Full batch lifecycle: placement, mortality, consumption, FCR tracking |
| **Bird Batches** | `bird_batches` | Simpler bird loading tracking for daily operations |

These serve different purposes but the relationship between them is not clearly defined.

### 3. Legacy `route` Field

Both `customers` and `dealers` tables have:
- `route` (string) — legacy text field
- `route_id` (foreign key) — newer FK to `routes` table

Both fields coexist, creating potential data inconsistency.

---

## Unfinished / Placeholder Pages

| Page | Status | Notes |
|------|--------|-------|
| Profit → Order-Wise | "Coming Soon" | Route exists, view shows placeholder |
| Profit → Comparison | "Coming Soon" | Route exists, view shows placeholder |
| Profit → Batch | "Under Construction" | Route exists, view shows placeholder |

---

## Needs Business Confirmation

The following business logic areas need clarification from the MSR BROILERS team:

| # | Question | Impact |
|---|----------|--------|
| 1 | What is the exact business meaning of the "Day-Load" billing system? | Core billing flow |
| 2 | What is the relationship between dealers and vendors in the day-load flow? | Payment allocation |
| 3 | When are vendor advances applied vs when are they used? | Vendor payment logic |
| 4 | Who should approve the Cash/Bank ledger and when? | Financial controls |
| 5 | What are the weight loss approval threshold rules? | Loss calculation |
| 6 | Which permission names should be seeded vs use Spatie defaults? | RBAC setup |
| 7 | Should the old `stock_transactions`/`stock_items` system be deprecated? | Code cleanup |
| 8 | Is the `route` string field still needed alongside `route_id`? | Data cleanup |
