# Project Overview

## 1. Project Name
**Flockwise BizTrack** — also branded as **MSR BROILERS Management System** in the UI title and company headers.

## 2. Purpose
A comprehensive ERP/business management system for a broiler poultry farming operation. The system manages the complete lifecycle of broiler poultry operations including: master data (customers, dealers, vendors, routes), billing (day-load, weekly, daily), purchases, payments (customer, dealer, vendor), expenses/EMI tracking, inventory (items, batches, warehouses, stock, consumptions, mortalities), profit analysis, reports, cash/bank ledger, capital management, and RBAC-based admin.

## 3. Framework and Version
- **Laravel 12.x** (laravel/framework ^12.0)
- **PHP ^8.2**

## 4. Programming Languages
- Backend: PHP 8.2+
- Frontend: Blade templates, Alpine.js, TailwindCSS, JavaScript (ES6 modules via Vite)

## 5. Database
- Default: **SQLite** (database/database.sqlite)
- Also supports: **MySQL/MariaDB** (configured in .env)
- Session driver: database
- Cache store: database
- Queue: database

## 6. Authentication System
- Laravel built-in session-based authentication (web guard)
- **Laravel Sanctum** for API token authentication (auth:sanctum)
- Login via email or username (username field on users table)
- Users can be deactivated (is_active flag)

## 7. Authorization / Roles / Permissions
- **Spatie Laravel Permission** package (spatie/laravel-permission ^6.25)
- Role-based access control with granular permissions
- Permission groups for organized permission management
- Admin role bypasses all permission checks (Gate::before in AppServiceProvider)
- Roles: Admin, Manager, Accountant, Staff, Delivery Staff, Data Entry, Viewer (and custom)

## 8. Frontend Technology
- **Blade** templates with component-based architecture (x- components)
- **Alpine.js 3** for client-side interactivity (loaded via CDN)
- **TailwindCSS 3** (both CDN in layouts and Vite-compiled)
- **Chart.js** for dashboard and analytics charts
- **SweetAlert2** for confirmation dialogs and flash messages
- **Google Fonts**: Outfit (body), Cabinet Grotesk (headings), JetBrains Mono (mono)
- **Material Symbols Rounded** + **Phosphor Icons** for iconography

## 9. Build System
- **Vite 5** with laravel-vite-plugin
- PostCSS + Autoprefixer + tailwindcss-animate

## 10. External Services / APIs
- **WhatsApp sharing** (simulated/placeholder in ShareInvoiceWhatsAppJob)
- **Google Maps** (loaded via CDN in layout)
- No real third-party payment gateway integration

## 11. Key Dependencies (composer.json)
| Package | Version | Purpose |
|---------|---------|---------|
| laravel/framework | ^12.0 | Core framework |
| laravel/sanctum | ^4.0 | API token auth |
| spatie/laravel-permission | ^6.25 | RBAC |
| barryvdh/laravel-dompdf | ^3.1 | PDF generation |
| laravel/tinker | ^2.10.1 | REPL |
| fakerphp/faker | ^1.23 | Test data |
| phpunit/phpunit | ^11.5.3 | Testing |

## 12. Main Modules
1. **Dashboard** — KPIs, charts, alerts
2. **Masters** — Customers, Dealers, Vendors (with routes)
3. **Billing** — Day-Load, Weekly Bills, Daily Bills, Cash/Bank Ledger
4. **Purchases** — Purchase entry, invoice archive, print/PDF
5. **Payments** — Customer, Dealer, Vendor payment management
6. **Expenses & EMIs** — Expense categories, EMI scheduling and tracking
7. **Inventory** — Items, Batches, Stock, Warehouses, Consumptions, Mortalities, Analytics
8. **Profit** — P&L analysis, weekly/monthly breakdowns, PDF export
9. **Reports** — Sales reports, Purchase reports, Customer ranking, Vendor analytics
10. **Routes** — Delivery route, vehicle, driver management
11. **Capital** — Investment tracking, vendor advances
12. **Admin** — Users, Roles, Permissions, Activity Logs

## 13. Branding
- Company Name: **MSR BROILERS**
- Referenced throughout in invoice headers, PDF templates, and page titles
- Favicon: egg emoji SVG
