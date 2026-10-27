# Project Architecture

## Architectural Pattern
- **MVC (Model-View-Controller)** with Service Layer and Repository Pattern

## Layer Architecture

```
Routes (web.php / api.php)
  → Controllers (Web + API)
    → Form Requests (validation)
      → Services (business logic)
        → Repositories (data access abstraction)
          → Models (Eloquent ORM)
            → Database
```

## Directory Structure

```
app/
├── Console/Commands/           # Artisan commands (3)
│   ├── AuditCashBankLedger.php
│   ├── BackfillCashBankLedger.php
│   └── FixDayLoadWeights.php
├── Exceptions/                 # Custom exceptions
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # RBAC management (3)
│   │   ├── Api/                # REST API (23)
│   │   ├── Auth/               # Authentication (1)
│   │   ├── Billing/            # Billing operations (4)
│   │   ├── Inventory/          # Inventory management (7)
│   │   ├── Masters/            # Master data (4)
│   │   ├── Payments/           # Payment operations (3)
│   │   ├── Purchases/          # Purchase management (1)
│   │   └── 10 top-level        # Dashboard, Stock, Profit, etc.
│   ├── Middleware/              # Custom middleware (3)
│   ├── Requests/               # Form request validation (12)
│   └── Resources/              # API resources (15)
├── Jobs/                       # Queue jobs (4)
├── Models/                     # Eloquent models (43)
├── Notifications/              # Database notifications (1)
├── Observers/                  # Model observers (2)
├── Policies/                   # Authorization policies (3)
├── Providers/                  # Service providers (2)
├── Repositories/               # Repository pattern (2 + 2 interfaces)
└── Services/                   # Business logic (22 + 1 tax)

resources/
├── css/app.css                 # Tailwind + custom CSS
├── js/
│   ├── app.js                  # Main JS (theme, Alpine, SweetAlert)
│   └── bootstrap.js            # Axios setup
└── views/                      # Blade templates (155 files)
    ├── layouts/                # App shells (4)
    ├── components/             # Reusable UI components (20+)
    ├── partials/               # Sidebar, topbar, etc.
    ├── dashboard/
    ├── masters/
    ├── billing/
    ├── purchases/
    ├── payments/
    ├── expenses/
    ├── inventory/
    ├── profit/
    ├── reports/
    ├── routes/
    ├── investments/
    ├── admin/
    └── vendor/

database/
├── migrations/                 # 88 migration files
├── seeders/                    # 12 seeders
└── factories/                  # 15 model factories
```

## Design Patterns Used
1. **Service Layer** — Business logic extracted from controllers into dedicated services
2. **Repository Pattern** — Data access abstraction for Stock and Purchases
3. **Form Request Validation** — Dedicated request classes for input validation
4. **API Resources** — Structured JSON responses for API endpoints
5. **Observer Pattern** — Cache invalidation for Items and Customers
6. **Policy Pattern** — Authorization logic for Users, Roles, Permissions
7. **Job/Queue Pattern** — Async operations for PDF generation, exports, WhatsApp, imports
8. **Component-Based UI** — Blade components for reusable UI elements

## Key Architectural Decisions
1. **Dual Interface**: Both web (Blade) and API (JSON) interfaces coexist
2. **Service-Oriented**: Controllers are thin; services contain business logic
3. **Cash/Bank Ledger System**: Double-entry style daily ledger tracking all cash and bank movements
4. **Day-Load Billing**: Custom billing system tracking bird weights, boxes, and dealer/vendor settlements
5. **Permission-Based UI**: Sidebar and actions are conditionally rendered using @can directives
