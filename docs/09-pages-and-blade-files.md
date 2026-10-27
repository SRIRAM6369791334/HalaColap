# Pages and Blade Files — Complete Page Inventory

This document inventories all **155 Blade files** in the Flockwise BizTrack / MSR BROILERS Management System. Files are grouped by module (as they appear in the navigation sidebar and file tree). For each page we record the Blade path, the layout it extends, and its purpose.

> **Layout Legend**
> - **app** — `layouts.app` — main authenticated application shell (sidebar + topbar + flash messages)
> - **auth** — `layouts.auth` — standalone auth screen (login)
> - **empty** — `layouts.empty` — minimal shell, used when a view is loaded via AJAX
> - **pdf** — `layouts.pdf` — print/PDF-only layout with no navigation
> - **—** / *standalone* — the file is a raw HTML `<DOCTYPE>` document (print/export or component partial), or a nested partial with no layout

---

## 1. Layouts (4)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| App Layout | `layouts/app.blade.php` | — | Main application shell: `<head>` (fonts, CDN scripts), topbar, sidebar, flash messages, `@yield('content')`, `@stack('styles')` / `@stack('scripts')`, dark mode, CSRF meta, JS setup (SweetAlert2, Chart.js, Alpine, Tailwind) |
| Auth Layout | `layouts/auth.blade.php` | — | Standalone shell for unauthenticated pages (auth/guest area), full-screen centered card |
| Empty Layout | `layouts/empty.blade.php` | — | Minimal shell (no sidebar/topbar) returned when views are requested via AJAX (`request()->ajax()`) |
| PDF Layout | `layouts/pdf.blade.php` | — | Print/PDF layout with no navigation, used by DOM PDF exports and print views |

---

## 2. Partials (4)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Sidebar | `partials/sidebar.blade.php` | — | Main navigation sidebar with module links, grouped by section; uses `@can` directives to filter menu items by permission |
| Topbar | `partials/topbar.blade.php` | — | Top header bar: page title/breadcrumb, theme toggle, user menu with logout |
| Flash Messages | `partials/flash-messages.blade.php` | — | Renders success/error/info flash message banners (backs the SweetAlert2 flash handling) |
| CM Style | `partials/cm-style.blade.php` | — | Tailwind/CSS styling partial (custom style block) used by child views via `@stack('styles')` |

---

## 3. Auth (1)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Login | `auth/login.blade.php` | auth | Login form: email/username + password, submit posts to `/login` |
| Welcome | `welcome.blade.php` | — | Laravel default welcome page (standalone, brand placeholder) |

---

## 4. Dashboard (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Dashboard | `dashboard/index.blade.php` | app | KPI stat cards (Today's Revenue, Total Customers, Pending Payments, Monthly Revenue/Purchases, Active Batches), revenue & purchase charts, recent transactions |
| Alerts | `dashboard/alerts.blade.php` | app | Upcoming alerts (EMI due, low stock, pending payments) |

---

## 5. Admin (10)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Users | `admin/users/index.blade.php` | app | List users, assign roles, activate/deactivate accounts, add users |
| Roles Index | `admin/roles/index.blade.php` | app | List/crud roles |
| Role Create | `admin/roles/create.blade.php` | app | Create a new role |
| Role Edit | `admin/roles/edit.blade.php` | app | Edit a role |
| Assign Permissions | `admin/roles/assignpermission.blade.php` | app | Permission matrix: assign granular permissions to a role |
| Permissions Index | `admin/permissions/index.blade.php` | app | List permissions (grouped by permission group) |
| Permission Create | `admin/permissions/create.blade.php` | app | Create a new permission |
| Permission Edit | `admin/permissions/edit.blade.php` | app | Edit a permission |

---

## 6. Masters (24)

### Customers (9 + 1 partial)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Customers List | `masters/customers/index.blade.php` | app | List/search customers with outstanding balances |
| Create Customer | `masters/customers/create.blade.php` | app | Add a new customer (name, phone, GST, route, type retail/wholesale) |
| Edit Customer | `masters/customers/edit.blade.php` | app | Edit an existing customer |
| Show Customer | `masters/customers/show.blade.php` | empty/app | Customer profile with actions (conditional: empty layout when AJAX) |
| Billing History | `masters/customers/billing-history.blade.php` | empty/app | Customer billing history (AJAX tab) |
| Payment History | `masters/customers/payment-history.blade.php` | empty/app | Customer payment history (AJAX tab) |
| EMI History | `masters/customers/emi-history.blade.php` | empty/app | Customer's EMI payment history (AJAX tab) |
| Customer PDF | `masters/customers/pdf.blade.php` | — | Customer print/statement HTML document |
| Ledger PDF | `masters/customers/ledger_pdf.blade.php` | pdf | Customer ledger export (PDF layout) |
| Profile Style Partial | `masters/customers/partials/profile-style.blade.php` | — | Shared `profile-style` CSS/HTML partial for customer profile tabs |

### Dealers (8)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Dealers List | `masters/dealers/index.blade.php` | app | List/search dealers |
| Create Dealer | `masters/dealers/create.blade.php` | app | Add a new dealer |
| Edit Dealer | `masters/dealers/edit.blade.php` | app | Edit a dealer |
| Show Dealer | `masters/dealers/show.blade.php` | app | Dealer profile with details |
| Purchase History | `masters/dealers/purchase-history.blade.php` | app | Dealer purchase history |
| Outstanding Report | `masters/dealers/outstanding-report.blade.php` | app | Dealer outstanding balances report |
| Ledger PDF | `masters/dealers/ledger_pdf.blade.php` | pdf | Dealer ledger export |

### Vendors (7)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Vendors List | `masters/vendors/index.blade.php` | app | List/search vendors |
| Create Vendor | `masters/vendors/create.blade.php` | app | Add a new vendor |
| Edit Vendor | `masters/vendors/edit.blade.php` | app | Edit a vendor |
| Show Vendor | `masters/vendors/show.blade.php` | app | Vendor profile |
| Purchase History | `masters/vendors/purchase-history.blade.php` | app | Vendor purchase history |
| Ledger | `masters/vendors/ledger.blade.php` | app | Vendor ledger / balances |
| History PDF | `masters/vendors/history_pdf.blade.php` | pdf | Vendor history export |

---

## 7. Billing (15)

### Day-Load (4)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Day-Load Billing | `billing/day-load/index.blade.php` | app | Core day-load billing board: batch for a date, entries, bulk store, transfers, approvals, finalize, payments |
| Day-Load Invoice | `billing/day-load/invoice.blade.php` | — | Day-load invoice view/document |
| Day-Load PDF | `billing/day-load/pdf.blade.php` | pdf | Day-load invoice PDF export |
| Vendor Rates | `billing/day-load/vendor-rates.blade.php` | app | Vendor rates reference/manager within day-load |

### Weekly (4)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Weekly Index | `billing/weekly/index.blade.php` | app | Dealer weekly billing system: generate weekly bills for a period |
| Weekly Bulk | `billing/weekly/bulk.blade.php` | app | Bulk weekly billing for multiple dealers |
| Dealer Invoice | `billing/weekly/dealer-invoice.blade.php` | app | Weekly dealer invoice view |
| Weekly PDF | `billing/weekly/pdf.blade.php` | pdf | Weekly invoice PDF export |

### Daily (5)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Daily Index | `billing/daily/index.blade.php` | app | Dealer daily billing system |
| Create Daily Invoice | `billing/daily/create.blade.php` | app | Create a daily invoice (dealer + date range) |
| Daily Invoice | `billing/daily/invoice.blade.php` | app | Daily invoice view |
| Daily GST | `billing/daily/gst.blade.php` | app | GST billing overview |
| Daily PDF | `billing/daily/pdf.blade.php` | pdf | Daily tax invoice PDF export |

### Cash & Bank Ledger (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Ledger Index | `billing/cash-bank-ledger/index.blade.php` | app | Daily cash & bank opening/closing balances grid |
| Ledger Show | `billing/cash-bank-ledger/show.blade.php` | app | Day detail: line items, approve/sweep cash to bank |

### Invoice Base (1)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Invoice | `billing/invoice.blade.php` | — | Base invoice document (shared invoice HTML) |

---

## 8. Purchases (8)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Purchases Index | `purchases/index.blade.php` | app | Purchase entries list |
| Create Purchase | `purchases/create.blade.php` | app | Purchase entry form (vendor, invoice no, GST, items, EMI schedule) |
| Show Purchase | `purchases/show.blade.php` | app | Purchase detail |
| Edit Purchase | `purchases/edit.blade.php` | app | Edit a purchase |
| Invoices Archive | `purchases/invoices.blade.php` | app | Purchase invoice archive grouped by date |
| Invoices Day | `purchases/invoices-day.blade.php` | app | Invoices for a specific day |
| Invoices Print | `purchases/invoices-print.blade.php` | — | Invoice print document |
| Invoices PDF | `purchases/invoices-pdf.blade.php` | pdf | Invoice PDF export |
| Print | `purchases/print.blade.php` | — | Single-purchase print document |

---

## 9. Payments (8)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Customer Payments | `payments/customers.blade.php` | app | List customer payments |
| Customer Payment Create | `payments/customers/create.blade.php` | app | Record customer payment (COD/bank, mode, type) |
| Dealer Payments | `payments/dealers.blade.php` | app | List dealer payments |
| Dealer Payment Create | `payments/dealers/create.blade.php` | app | Record dealer payment with complex allocation (EMIs, entries, FIFO) |
| Dealer Ledger | `payments/dealers/ledger.blade.php` | app | Dealer payment ledger |
| Vendor Payments | `payments/vendors.blade.php` | app | Vendor payments & advances list |
| Vendor Payment Create | `payments/vendors/create.blade.php` | app | Record vendor payout |
| Vendor Advance | (*see* `payments/vendors.blade.php`) | — | Vendor advances shown within vendor payments page |

---

## 10. Expenses & EMIs (7)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Expenses Index | `expenses/index.blade.php` | app | Expenses & EMI overview |
| Create Expense | `expenses/create.blade.php` | app | Add expense (category, description, amount, date, method) |
| Categories | `expenses/categories/index.blade.php` | app | Expense categories management |
| EMI Index | `expenses/emis/index.blade.php` | app | EMI tracking list |
| EMI Create | `expenses/emis/create.blade.php` | app | Create EMI schedule with due dates/installments |
| EMI Edit | `expenses/emis/edit.blade.php` | app | Edit an EMI |
| EMI Alerts | `expenses/emis/alerts.blade.php` | app | Upcoming & overdue EMI alerts |

---

## 11. Inventory (17)

### Warehouses (3)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Warehouses Index | `inventory/warehouses/index.blade.php` | app | Warehouse master list |
| Create Warehouse | `inventory/warehouses/create.blade.php` | app | Add warehouse |
| Edit Warehouse | `inventory/warehouses/edit.blade.php` | app | Edit warehouse |

### Items (3)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Items Index | `inventory/items/index.blade.php` | app | Item master (Feed, Chick, Medicine, Vaccine, Equipment) |
| Create Item | `inventory/items/create.blade.php` | app | Add item |
| Edit Item | `inventory/items/edit.blade.php` | app | Edit item |

### Stock (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Stock Index | `inventory/stock/index.blade.php` | app | Real-time stock levels, low-stock alerts |
| Stock Movements | `inventory/stock/movements.blade.php` | app | Stock ledger / movement history |

### Batches (4)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Batches Index | `inventory/batches/index.blade.php` | app | Bird batches list |
| Create Batch | `inventory/batches/create.blade.php` | app | Create a batch (code, placement date, breed, initial count) |
| Edit Batch | `inventory/batches/edit.blade.php` | app | Edit a batch |
| Show Batch | `inventory/batches/show.blade.php` | app | Batch detail / flock lifecycle |

### Consumptions (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Consumptions Index | `inventory/consumptions/index.blade.php` | app | List consumption logs |
| Create Consumption | `inventory/consumptions/create.blade.php` | app | Log daily feed/medicine usage per batch |

### Mortalities (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Mortalities Index | `inventory/mortalities/index.blade.php` | app | List mortality logs |
| Create Mortality | `inventory/mortalities/create.blade.php` | app | Log bird deaths (count, reason, remarks) |

### Analytics (1)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Inventory Analytics | `inventory/analytics/index.blade.php` | app | Inventory analytics dashboards |

---

## 12. Profit (8)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Profit Index | `profit/index.blade.php` | app | Weekly P&L breakdown |
| Weekly Detail | `profit/weekly-detail.blade.php` | app | Detailed weekly profit |
| Monthly | `profit/monthly.blade.php` | app | Monthly profit trend |
| Expense vs Income | `profit/expense-vs-income.blade.php` | app | Expense vs income comparison |
| Order Wise | `profit/order-wise.blade.php` | app | Order-wise profit breakdown |
| Comparison | `profit/comparison.blade.php` | app | Period/entity comparison |
| Batch | `profit/batch.blade.php` | app | Batch-wise profit |
| Profit PDF | `profit/pdf.blade.php` | pdf | Profit export PDF |

---

## 13. Reports (14)

### Index & Ranking (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Reports Index | `reports/index.blade.php` | app | Reports landing with summary |
| Customer Ranking | `reports/customers/ranking.blade.php` | app | Customer ranking report |

### Sales (3)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Daily Sales | `reports/sales/daily.blade.php` | app | Daily sales report |
| Weekly Sales | `reports/sales/weekly.blade.php` | app | Weekly sales report |
| Monthly Sales | `reports/sales/monthly.blade.php` | app | Monthly sales report |

### Purchases (4)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Daily Purchases | `reports/purchases/daily.blade.php` | app | Daily purchases report |
| Weekly Purchases | `reports/purchases/weekly.blade.php` | app | Weekly purchases report |
| Monthly Purchases | `reports/purchases/monthly.blade.php` | app | Monthly purchases report |
| Purchase Analytics | `reports/purchases/analytics.blade.php` | app | Purchase analytics |
| Vendor Analytics | `reports/purchases/vendor-analytics.blade.php` | app | Vendor analytics |

### PDF (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Sales PDF | `reports/pdf/sales.blade.php` | — | Sales report print/PDF HTML document |
| Purchases PDF | `reports/pdf/purchases.blade.php` | — | Purchases report print/PDF HTML document |

---

## 14. Routes / Fleet (1)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Routes Index | `routes/index.blade.php` | app | Delivery routes, vehicles, drivers management |

---

## 15. Investments / Capital (1)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Investments | `investments/index.blade.php` | app | Investment/capital tracking, vendor advances |

---

## 16. Legacy / Stock (2)

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Stock Index | `stock/index.blade.php` | app | Legacy stock view (older module) |
| Stock Batches | `stock/batches/index.blade.php` | app | Legacy batches view |

---

## 17. Components (21)

> Blade **x-components** — reusable UI building blocks for the component-based architecture. **Note:** a total of 21 component files are present (including 3 under `components/form/`).

| Component | Blade File Path | Layout | Purpose |
|-----------|-----------------|--------|---------|
| Avatar | `components/avatar.blade.php` | component | User/entity avatar display |
| Badge | `components/badge.blade.php` | component | Status/label badge |
| Breadcrumb | `components/breadcrumb.blade.php` | component | Page breadcrumb navigation |
| Button | `components/button.blade.php` | component | Reusable button |
| Card | `components/card.blade.php` | component | Card container |
| Confirm Dialog | `components/confirm-dialog.blade.php` | component | Confirmation dialog wrapper |
| Currency | `components/currency.blade.php` | component | Currency/amount formatting |
| Data Table | `components/data-table.blade.php` | component | Data table wrapper with columns |
| Dropdown | `components/dropdown.blade.php` | component | Dropdown menu |
| EMI Schedule Generator | `components/emi-schedule-generator.blade.php` | component | Generates EMI schedule inputs |
| Empty State | `components/empty-state.blade.php` | component | Empty-state placeholder |
| Modal | `components/modal.blade.php` | component | Modal overlay (used with `$dispatch('open-modal', ...)`) |
| Page Header | `components/page-header.blade.php` | component | Page title + actions header |
| Pagination | `components/pagination.blade.php` | component | Pagination links wrapper |
| Search | `components/search.blade.php` | component | Search input |
| Stat Card | `components/stat-card.blade.php` | component | KPI statistic card |
| Tabs | `components/tabs.blade.php` | component | Tab navigation |
| Toast | `components/toast.blade.php` | component | Toast notification |
| Trend | `components/trend.blade.php` | component | Uptrend/downtrend display |
| Form Input | `components/form/input.blade.php` | component | Form text/number input |
| Form Select | `components/form/select.blade.php` | component | Form select drop-down |
| Form Textarea | `components/form/textarea.blade.php` | component | Form textarea |

---

## 18. Vendor Pagination (9)

> Standard Laravel pagination templates supplied by the framework (`vendor:publish`) — not custom application views.

| Page | Blade File Path | Layout | Purpose |
|------|-----------------|--------|---------|
| Bootstrap 4 | `vendor/pagination/bootstrap-4.blade.php` | vendor | Bootstrap 4 pagination |
| Bootstrap 5 | `vendor/pagination/bootstrap-5.blade.php` | vendor | Bootstrap 5 pagination |
| Default | `vendor/pagination/default.blade.php` | vendor | Default pagination |
| Semantic UI | `vendor/pagination/semantic-ui.blade.php` | vendor | Semantic UI pagination |
| Simple Bootstrap 4 | `vendor/pagination/simple-bootstrap-4.blade.php` | vendor | Simple Bootstrap 4 pagination |
| Simple Bootstrap 5 | `vendor/pagination/simple-bootstrap-5.blade.php` | vendor | Simple Bootstrap 5 pagination |
| Simple Default | `vendor/pagination/simple-default.blade.php` | vendor | Simple default pagination |
| Simple Tailwind | `vendor/pagination/simple-tailwind.blade.php` | vendor | Simple Tailwind pagination |
| Tailwind | `vendor/pagination/tailwind.blade.php` | vendor | Tailwind pagination |

---

## Summary Counts

| Group | Blade Files |
|-------|-------------|
| Layouts | 4 |
| Partials | 4 |
| Auth | 2 |
| Dashboard | 2 |
| Admin | 8 |
| Masters | 24 (customers 9+1, dealers 8, vendors 7) |
| Billing | 15 |
| Purchases | 9 |
| Payments | 8 |
| Expenses & EMIs | 7 |
| Inventory | 17 |
| Profit | 8 |
| Reports | 14 |
| Routes | 1 |
| Investments | 1 |
| Legacy Stock | 2 |
| Components | 21 |
| Vendor Pagination | 9 |
| **Total** | **155** |
