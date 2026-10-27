# API Documentation — Flockwise BizTrack

## Overview

**Base URL:** `{APP_URL}/api/v1`

All API endpoints return JSON responses. Authentication is handled via Laravel Sanctum tokens.

---

## Authentication

### POST `/api/v1/auth/login`

Authenticates a user and returns a Sanctum token.

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `login` | string | Yes | Email or username |
| `password` | string | Yes | User password |

**Response (200):**
```json
{
  "token": "sanctum-token-string",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@msr.com",
    "roles": ["admin"]
  }
}
```

---

### POST `/api/v1/auth/logout`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | Any authenticated user |

Logs out the current user by revoking the active token.

**Response (200):**
```json
{ "message": "Logged out" }
```

---

### GET `/api/v1/auth/profile`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | Any authenticated user |

Returns the authenticated user's profile including roles and permissions.

**Response (200):**
```json
{
  "id": 1,
  "name": "Admin",
  "email": "admin@msr.com",
  "roles": ["admin"],
  "permissions": ["view analytics", "manage users"]
}
```

---

## Dashboard

### GET `/api/v1/dashboard`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view analytics` |

Returns aggregated dashboard statistics (revenue, expenses, profit, stock, active batches, pending payments). Cached for 60 seconds.

**Response (200):**
```json
{
  "total_revenue": 1500000,
  "total_expenses": 900000,
  "total_profit": 600000,
  "active_batches": 5,
  "pending_dealer_payments": 200000,
  "pending_vendor_payments": 150000,
  "low_stock_items": 3
}
```

---

### GET `/api/v1/dashboard/alerts`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view analytics` |

Returns active system alerts (EMI due, low stock, pending payments).

**Response (200):**
```json
{
  "alerts": [
    {
      "type": "emi_due",
      "message": "EMI for dealer Ravi is due tomorrow",
      "severity": "warning"
    }
  ]
}
```

---

## Masters

### Customers

#### `apiResource /api/v1/masters/customers` — CRUD

Standard Laravel apiResource routes: `index`, `store`, `show`, `update`, `destroy`.

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/masters/customers` | GET | `auth:sanctum` | `view customers` |
| `/masters/customers` | POST | `auth:sanctum` | `create customers` |
| `/masters/customers/{id}` | GET | `auth:sanctum` | `view customers` |
| `/masters/customers/{id}` | PUT/PATCH | `auth:sanctum` | `edit customers` |
| `/masters/customers/{id}` | DELETE | `auth:sanctum` | `delete customers` |

**Store/Update Request Body:**
```json
{
  "name": "Customer Name",
  "phone": "9876543210",
  "address": "123 Main St",
  "route_id": 1,
  "route": "Mylapore",
  "opening_balance": 0,
  "credit_limit": 50000
}
```

---

#### GET `/api/v1/masters/customers/{id}/billing-history`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view customers` |

Returns all daily and weekly bills for the specified customer.

**Response (200):**
```json
{
  "daily_bills": [],
  "weekly_bills": []
}
```

---

#### GET `/api/v1/masters/customers/{id}/payment-history`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view customers` |

Returns all payments received from the specified customer.

**Response (200):**
```json
{
  "payments": [
    {
      "id": 1,
      "amount": 5000,
      "date": "2026-08-15",
      "mode": "cash"
    }
  ]
}
```

---

### Dealers

#### `apiResource /api/v1/masters/dealers` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/masters/dealers` | GET | `auth:sanctum` | `view dealers` |
| `/masters/dealers` | POST | `auth:sanctum` | `create dealers` |
| `/masters/dealers/{id}` | GET | `auth:sanctum` | `view dealers` |
| `/masters/dealers/{id}` | PUT/PATCH | `auth:sanctum` | `edit dealers` |
| `/masters/dealers/{id}` | DELETE | `auth:sanctum` | `delete dealers` |

**Store/Update Request Body:**
```json
{
  "name": "Dealer Name",
  "phone": "9876543210",
  "address": "456 Market Rd",
  "route_id": 2,
  "opening_balance": 0,
  "credit_limit": 100000,
  "emi_amount": 5000
}
```

---

#### GET `/api/v1/masters/dealers/{id}/purchase-history`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view dealers` |

Returns day-load entries and invoices associated with the dealer.

---

### Vendors

#### `apiResource /api/v1/masters/vendors` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/masters/vendors` | GET | `auth:sanctum` | `view vendors` |
| `/masters/vendors` | POST | `auth:sanctum` | `create vendors` |
| `/masters/vendors/{id}` | GET | `auth:sanctum` | `view vendors` |
| `/masters/vendors/{id}` | PUT/PATCH | `auth:sanctum` | `edit vendors` |
| `/masters/vendors/{id}` | DELETE | `auth:sanctum` | `delete vendors` |

**Store/Update Request Body:**
```json
{
  "name": "Feed Supplier",
  "phone": "9876543210",
  "address": "789 Supply Ln",
  "opening_balance": 0,
  "advance_balance": 0,
  "rate": 42.5
}
```

---

#### GET `/api/v1/masters/vendors/{id}/purchase-history`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view vendors` |

Returns all purchases and day-load entries linked to the vendor.

---

### Routes

#### `apiResource /api/v1/masters/routes` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/masters/routes` | GET | `auth:sanctum` | `view routes` |
| `/masters/routes` | POST | `auth:sanctum` | `create routes` |
| `/masters/routes/{id}` | GET | `auth:sanctum` | `view routes` |
| `/masters/routes/{id}` | PUT/PATCH | `auth:sanctum` | `edit routes` |
| `/masters/routes/{id}` | DELETE | `auth:sanctum` | `delete routes` |

**Store/Update Request Body:**
```json
{
  "name": "Mylapore Route",
  "description": "Covers Mylapore area"
}
```

---

### Warehouses

#### `apiResource /api/v1/masters/warehouses` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/masters/warehouses` | GET | `auth:sanctum` | `view warehouses` |
| `/masters/warehouses` | POST | `auth:sanctum` | `create warehouses` |
| `/masters/warehouses/{id}` | GET | `auth:sanctum` | `view warehouses` |
| `/masters/warehouses/{id}` | PUT/PATCH | `auth:sanctum` | `edit warehouses` |
| `/masters/warehouses/{id}` | DELETE | `auth:sanctum` | `delete warehouses` |

**Store/Update Request Body:**
```json
{
  "name": "Main Godown",
  "address": "100 Farm Road",
  "capacity": 5000
}
```

---

## Billing

### Daily Billing

#### `apiResource /api/v1/billing/daily` — CRUD (except update)

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/billing/daily` | GET | `auth:sanctum` | `view daily bills` |
| `/billing/daily` | POST | `auth:sanctum` | `create daily bills` |
| `/billing/daily/{id}` | GET | `auth:sanctum` | `view daily bills` |
| `/billing/daily/{id}` | DELETE | `auth:sanctum` | `delete daily bills` |

> **Note:** `PUT/PATCH /billing/daily/{id}` is not supported. Daily bills are versioned; corrections create new versions.

**Store Request Body:**
```json
{
  "date": "2026-09-01",
  "dealer_id": 1,
  "batch_id": 1,
  "entries": [
    {
      "customer_id": 10,
      "boxes": 5,
      "bird_weight": 25.0,
      "rate": 95.00,
      "box_weight": 2.5
    }
  ]
}
```

---

### Weekly Billing

#### POST `/api/v1/billing/weekly/bulk`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `create weekly bills` |

Generates weekly bills for all dealers in bulk for a given date range.

**Request Body:**
```json
{
  "start_date": "2026-08-25",
  "end_date": "2026-08-31"
}
```

**Response (200):**
```json
{
  "message": "Weekly bills generated",
  "count": 5,
  "bills": []
}
```

---

#### POST `/api/v1/billing/weekly/{id}/share-url`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view weekly bills` |

Generates a shareable URL (and triggers WhatsApp job) for the specified weekly bill.

**Request Body:**
```json
{
  "phone": "9876543210"
}
```

**Response (200):**
```json
{
  "share_url": "https://app.example.com/shared/invoice/abc123",
  "message": "Share URL generated"
}
```

---

#### `apiResource /api/v1/billing/weekly` — CRUD (except update)

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/billing/weekly` | GET | `auth:sanctum` | `view weekly bills` |
| `/billing/weekly` | POST | `auth:sanctum` | `create weekly bills` |
| `/billing/weekly/{id}` | GET | `auth:sanctum` | `view weekly bills` |
| `/billing/weekly/{id}` | DELETE | `auth:sanctum` | `delete weekly bills` |

---

## Purchases

#### `apiResource /api/v1/purchases` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/purchases` | GET | `auth:sanctum` | `view purchases` |
| `/purchases` | POST | `auth:sanctum` | `create purchases` |
| `/purchases/{id}` | GET | `auth:sanctum` | `view purchases` |
| `/purchases/{id}` | PUT/PATCH | `auth:sanctum` | `edit purchases` |
| `/purchases/{id}` | DELETE | `auth:sanctum` | `delete purchases` |

**Store/Update Request Body:**
```json
{
  "vendor_id": 1,
  "item_id": 5,
  "date": "2026-09-01",
  "quantity": 100,
  "rate": 42.50,
  "total": 4250.00,
  "gst_percent": 5.0,
  "gst_amount": 212.50,
  "payment_mode": "bank",
  "batch_id": 2
}
```

---

## Payments

### Customer Payments

#### GET `/api/v1/payments/customers`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view payments` |

Returns all customer payments with optional query filters.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `customer_id` | int | Filter by customer |
| `date_from` | date | Filter from date |
| `date_to` | date | Filter to date |

---

#### POST `/api/v1/payments/customers`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `create payments` |

Records a payment received from a customer.

**Request Body:**
```json
{
  "customer_id": 1,
  "amount": 5000.00,
  "date": "2026-09-01",
  "mode": "cash",
  "reference": "INV-001",
  "notes": "Partial payment"
}
```

---

### Dealer Payments

#### GET `/api/v1/payments/dealers`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view payments` |

Returns all dealer payments with optional filters.

---

#### POST `/api/v1/payments/dealers`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `create payments` |

Records a payment received from a dealer. Automatically applies FIFO allocation to day-load entries and EMI selection.

**Request Body:**
```json
{
  "dealer_id": 1,
  "amount": 25000.00,
  "date": "2026-09-01",
  "mode": "bank",
  "emi_ids": [3, 4],
  "notes": "Monthly settlement"
}
```

**Response (200):**
```json
{
  "message": "Payment recorded and allocated",
  "payment_id": 12,
  "allocation": {
    "emi_paid": 10000,
    "pending_reduced": 5000,
    "day_load_allocated": 8000,
    "vendor_advance": 2000
  }
}
```

---

#### GET `/api/v1/payments/dealers/{id}/ledger`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view payments` |

Returns the full payment ledger for a dealer, showing all transactions, allocations, and running balance.

**Response (200):**
```json
{
  "dealer_id": 1,
  "dealer_name": "Ravi Traders",
  "opening_balance": 50000,
  "total_billed": 200000,
  "total_paid": 150000,
  "closing_balance": 0,
  "ledger_entries": [
    {
      "date": "2026-08-25",
      "type": "bill",
      "reference": "WB-2026-034",
      "debit": 25000,
      "credit": 0,
      "balance": 25000
    }
  ]
}
```

---

## Expenses

#### GET `/api/v1/expenses/categories`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view expenses` |

Returns all expense categories.

---

#### POST `/api/v1/expenses/categories`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `create expenses` |

Creates a new expense category.

**Request Body:**
```json
{
  "name": "Transport",
  "description": "Bird transport expenses"
}
```

---

#### GET `/api/v1/expenses/emis`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view expenses` |

Returns all EMI records with dealer associations and payment status.

---

#### GET `/api/v1/expenses/alerts`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view expenses` |

Returns expense-related alerts (upcoming EMIs, overdue payments).

---

#### `apiResource /api/v1/expenses` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/expenses` | GET | `auth:sanctum` | `view expenses` |
| `/expenses` | POST | `auth:sanctum` | `create expenses` |
| `/expenses/{id}` | GET | `auth:sanctum` | `view expenses` |
| `/expenses/{id}` | PUT/PATCH | `auth:sanctum` | `edit expenses` |
| `/expenses/{id}` | DELETE | `auth:sanctum` | `delete expenses` |

**Store/Update Request Body:**
```json
{
  "category_id": 2,
  "amount": 1500.00,
  "date": "2026-09-01",
  "description": "Diesel for generator",
  "payment_mode": "cash",
  "vendor_id": null
}
```

---

## Inventory

### Batches (Poultry)

#### `apiResource /api/v1/batches` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/batches` | GET | `auth:sanctum` | `view batches` |
| `/batches` | POST | `auth:sanctum` | `create batches` |
| `/batches/{id}` | GET | `auth:sanctum` | `view batches` |
| `/batches/{id}` | PUT/PATCH | `auth:sanctum` | `edit batches` |
| `/batches/{id}` | DELETE | `auth:sanctum` | `delete batches` |

**Store/Update Request Body:**
```json
{
  "batch_number": "B-2026-001",
  "breed": "Cobb 500",
  "placement_date": "2026-08-01",
  "initial_count": 5000,
  "vendor_id": 1,
  "status": "open"
}
```

---

### Bird Batches

#### `apiResource /api/v1/bird-batches` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/bird-batches` | GET | `auth:sanctum` | `view batches` |
| `/bird-batches` | POST | `auth:sanctum` | `create batches` |
| `/bird-batches/{id}` | GET | `auth:sanctum` | `view batches` |
| `/bird-batches/{id}` | PUT/PATCH | `auth:sanctum` | `edit batches` |
| `/bird-batches/{id}` | DELETE | `auth:sanctum` | `delete batches` |

---

#### POST `/api/v1/bird-batches/{batch}/mortality`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `edit batches` |

Records mortality (bird deaths) for a specific bird batch.

**Request Body:**
```json
{
  "count": 5,
  "date": "2026-09-01",
  "reason": "Disease",
  "notes": "Respiratory issue"
}
```

---

### Mortalities

#### `apiResource /api/v1/mortalities` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/mortalities` | GET | `auth:sanctum` | `view batches` |
| `/mortalities` | POST | `auth:sanctum` | `create batches` |
| `/mortalities/{id}` | GET | `auth:sanctum` | `view batches` |
| `/mortalities/{id}` | PUT/PATCH | `auth:sanctum` | `edit batches` |
| `/mortalities/{id}` | DELETE | `auth:sanctum` | `delete batches` |

---

### Items

#### `apiResource /api/v1/items` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/items` | GET | `auth:sanctum` | `view items` |
| `/items` | POST | `auth:sanctum` | `create items` |
| `/items/{id}` | GET | `auth:sanctum` | `view items` |
| `/items/{id}` | PUT/PATCH | `auth:sanctum` | `edit items` |
| `/items/{id}` | DELETE | `auth:sanctum` | `delete items` |

**Store/Update Request Body:**
```json
{
  "name": "Broiler Feed",
  "category_id": 1,
  "unit": "kg",
  "current_stock": 5000,
  "reorder_level": 1000,
  "rate": 42.50
}
```

---

### Consumptions

#### `apiResource /api/v1/consumptions` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/consumptions` | GET | `auth:sanctum` | `view batches` |
| `/consumptions` | POST | `auth:sanctum` | `create batches` |
| `/consumptions/{id}` | GET | `auth:sanctum` | `view batches` |
| `/consumptions/{id}` | PUT/PATCH | `auth:sanctum` | `edit batches` |
| `/consumptions/{id}` | DELETE | `auth:sanctum` | `delete batches` |

**Store Request Body:**
```json
{
  "batch_id": 1,
  "item_id": 5,
  "quantity": 200,
  "date": "2026-09-01",
  "notes": "Daily feed consumption"
}
```

---

### Stock

#### GET `/api/v1/stock`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view items` |

Returns current stock levels for all items.

---

#### GET `/api/v1/stock/movements`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view items` |

Returns stock movement history with optional filters (`?item_id=`, `?date_from=`, `?date_to=`).

---

#### POST `/api/v1/stock/adjust`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `edit items` |

Manually adjusts stock levels for an item.

**Request Body:**
```json
{
  "item_id": 5,
  "adjustment": -50,
  "reason": "Damaged bags discarded",
  "date": "2026-09-01"
}
```

---

#### GET `/api/v1/inventory/analytics`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view analytics` |

Returns inventory analytics: stock value, consumption trends, low stock alerts, turnover rates.

---

## Profit

#### GET `/api/v1/profit`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view analytics` |

Returns overall profit summary with optional date range filters.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `date_from` | date | Start date |
| `date_to` | date | End date |
| `batch_id` | int | Filter by batch |
| `dealer_id` | int | Filter by dealer |

**Response (200):**
```json
{
  "total_revenue": 1500000,
  "total_expenses": 900000,
  "total_profit": 600000,
  "profit_margin": 40.0,
  "breakdown": {
    "bird_sales": 1200000,
    "vendor_costs": 700000,
    "operational_expenses": 200000
  }
}
```

---

#### GET `/api/v1/profit/monthly`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view analytics` |

Returns monthly profit trend data for chart visualization.

---

#### GET `/api/v1/profit/expense-vs-income`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view analytics` |

Returns expense vs income comparison data for charts.

---

## Reports

#### GET `/api/v1/reports`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns available report types and metadata.

---

#### GET `/api/v1/reports/sales/daily`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns daily sales report data.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `date` | date | Specific date (defaults to today) |
| `format` | string | `json` or `pdf` |

---

#### GET `/api/v1/reports/sales/weekly`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns weekly sales report data.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `start_date` | date | Week start date |
| `format` | string | `json` or `pdf` |

---

#### GET `/api/v1/reports/sales/monthly`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns monthly sales report data.

**Query Parameters:**
| Parameter | Type | Description |
|-----------|------|-------------|
| `month` | int | Month (1-12) |
| `year` | int | Year |
| `format` | string | `json` or `pdf` |

---

#### GET `/api/v1/reports/purchases/daily`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns daily purchase report data.

---

#### GET `/api/v1/reports/purchases/weekly`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns weekly purchase report data.

---

#### GET `/api/v1/reports/purchases/monthly`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns monthly purchase report data.

---

#### GET `/api/v1/reports/purchases/vendor-analytics`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns vendor analytics: purchase volumes, rates, trends per vendor.

---

#### GET `/api/v1/reports/customers/ranking`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns customer ranking by purchase volume, payment history, or outstanding balance.

---

#### GET `/api/v1/reports/purchases/analytics`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `view reports` |

Returns purchase analytics: trends, category breakdown, vendor comparison.

---

## Users (Admin)

#### POST `/api/v1/users/{id}/toggle-status`

| Auth | Permissions |
|------|-------------|
| `auth:sanctum` | `manage users` |

Toggles a user's active/disabled status.

**Response (200):**
```json
{
  "message": "User disabled",
  "user_id": 3,
  "is_active": false
}
```

---

#### `apiResource /api/v1/users` — CRUD

| Route | Method | Auth | Permissions |
|-------|--------|------|-------------|
| `/users` | GET | `auth:sanctum` | `manage users` |
| `/users` | POST | `auth:sanctum` | `manage users` |
| `/users/{id}` | GET | `auth:sanctum` | `manage users` |
| `/users/{id}` | PUT/PATCH | `auth:sanctum` | `manage users` |
| `/users/{id}` | DELETE | `auth:sanctum` | `manage users` |

**Store/Update Request Body:**
```json
{
  "name": "New User",
  "email": "user@msr.com",
  "password": "secure-password",
  "password_confirmation": "secure-password",
  "roles": ["operator"]
}
```

---

## Swagger Documentation

#### GET `/api/v1/docs`

| Auth | Permissions |
|------|-------------|
| None | Public |

Returns the Swagger UI HTML page for interactive API documentation and testing.

Access at: `{APP_URL}/api/v1/docs`

---

## Common Response Formats

### Success (200)
```json
{
  "data": { ... },
  "message": "Operation completed successfully"
}
```

### Created (201)
```json
{
  "data": { ... },
  "message": "Resource created successfully"
}
```

### Validation Error (422)
```json
{
  "message": "Validation failed",
  "errors": {
    "field_name": ["The field name is required."]
  }
}
```

### Unauthorized (401)
```json
{
  "message": "Unauthenticated"
}
```

### Forbidden (403)
```json
{
  "message": "Unauthorized. You do not have the required permission."
}
```

### Not Found (404)
```json
{
  "message": "Resource not found"
}
```

### Server Error (500)
```json
{
  "message": "Internal server error"
}
```
