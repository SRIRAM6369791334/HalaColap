# Roles and Permissions — Analysis

This document describes the role-based access control (RBAC) system of the Flockwise BizTrack / MSR BROILERS Management System. Its purpose is to document the roles, permission structure, access matrix, middleware, and policies so teams can reason about authorization behavior and confirm or adjust business rules.

---

## 1. RBAC System

- Powered by the **Spatie Laravel Permission** package (`spatie/laravel-permission ^6.25`).
- Permissions are organized into **Permission Groups** for clean, grouped management in the UI (see `admin/permissions/index.blade.php`).
- The **`admin` role bypasses all checks** via `Gate::before` registered in `AppServiceProvider`:

  ```php
  Gate::before(function ($user, $ability) {
      return $user->hasRole('admin') ? true : null;
  });
  ```

  Because it returns `true` for admins, an admin is authorized for **any** ability regardless of explicit permission records.

- **Role hierarchy** (from `User::getRoleLevel()`):

  ```php
  $hierarchy = [
      'viewer'        => 1,
      'staff'         => 2,
      'delivery_staff'=> 2,
      'data_entry'    => 2,
      'accountant'    => 3,
      'manager'       => 3,
      'admin'         => 4,
  ];
  return $this->roles->map(fn($role) => $hierarchy[$role->name] ?? 0)->max() ?? 0;
  ```

  Ordered ascending: **Viewer (1) < Staff / DeliveryStaff / DataEntry (2) < Accountant / Manager (3) < Admin (4)**. A user's effective level is the highest level across all their roles (`->max()`); users with no recognized role default to `0`.

---

## 2. Known Roles (from seeders and code)

| Role | Level | Notes |
|------|:-----:|-------|
| **Admin** | 4 | Full access; bypasses all permission checks via `Gate::before` |
| **Manager** | 3 | Broad access; can manage most operations |
| **Accountant** | 3 | Financial operations (billing, payments, expenses, profit, reports) |
| **Staff** | 2 | Basic day-to-day operations |
| **Delivery Staff** | 2 | Delivery-related operations |
| **Data Entry** | 2 | Data input tasks |
| **Viewer** | 1 | Read-only access |

Additional custom roles may exist; the system supports arbitrary role names via the Spatie package.

---

## 3. Permission Categories

Permission names follow the pattern **`{action} {module}`**:

- **Modules**: `customers`, `dealers`, `vendors`, `bills`, `purchases`, `payments`, `expenses`, `emis`, `stock`, `batches`, `items`, `consumptions`, `mortalities`, `routes`, `analytics`, `reports`, `users`, `roles`, `permissions`
- **Actions**: `view`, `create`, `edit`, `delete`, `manage`

Examples of full permission strings:
- `view customers`, `create customers`, `edit customers`, `delete customers`
- `view purchases`, `create purchases`, `edit purchases`, `delete purchases`
- `manage users`, `manage roles`, `manage permissions`

> The exact set of seeded permissions for a given install may vary from the defaults; permission names are typically prepended to modules consistently, and the sidebar (`partials/sidebar.blade.php`) filters menu items with `@can('...')` directives based on these strings.

---

## 4. Role Access Matrix

| Module | Admin | Manager | Accountant | Staff | Viewer |
|--------|:-----:|:-------:|:----------:|:-----:|:------:|
| Dashboard | Yes | Yes | Yes | Yes | Yes |
| Masters (Customers) | Yes | Yes | Yes | Yes | View |
| Masters (Dealers) | Yes | Yes | Yes | Yes | View |
| Masters (Vendors) | Yes | Yes | Yes | Yes | View |
| Billing (Day-Load) | Yes | Yes | Yes | Yes | View |
| Billing (Weekly) | Yes | Yes | Yes | Yes | View |
| Billing (Daily) | Yes | Yes | Yes | Yes | View |
| Cash/Bank Ledger | Yes | Yes | Yes | No | No |
| Purchases | Yes | Yes | Yes | Yes | View |
| Payments (Customer) | Yes | Yes | Yes | Yes | View |
| Payments (Dealer) | Yes | Yes | Yes | Yes | View |
| Payments (Vendor) | Yes | Yes | Yes | Yes | View |
| Expenses | Yes | Yes | Yes | Yes | View |
| EMIs | Yes | Yes | Yes | Yes | View |
| Inventory (Items) | Yes | Yes | No | Yes | View |
| Inventory (Batches) | Yes | Yes | No | Yes | View |
| Inventory (Stock) | Yes | Yes | No | Yes | View |
| Profit | Yes | Yes | Yes | No | View |
| Reports | Yes | Yes | Yes | No | View |
| Routes (Fleet) | Yes | Yes | No | No | View |
| Admin (Users) | Yes | No | No | No | No |
| Admin (Roles) | Yes | No | No | No | No |
| Admin (Permissions) | Yes | No | No | No | No |
| Admin (Activity Logs) | Yes | Yes | No | No | No |

> **Note:** This matrix is **INFERRED** from code analysis (sidebar `@can` directives and controller behavior). It may not exactly match every installation's seeded permissions; some cells should be confirmed with business owners before relying on them. Admin always overrides all cells to **Yes** due to the `Gate::before` bypass.

Legend: **Yes** = full access (view + create + edit + delete as applicable); **View** = read-only access; **No** = no access.

---

## 5. Middleware

Middleware aliases registered in `bootstrap/app.php`:

| Alias | Implementation | Purpose |
|-------|----------------|---------|
| `role` | `\App\Http\Middleware\RoleMiddleware` | Role-based guard, e.g. `role:admin`, `role:admin,manager` |
| `permission` | `\Spatie\Permission\Middleware\PermissionMiddleware` | Permission-based guard, e.g. `permission:view customers` |
| `role_or_permission` | `\Spatie\Permission\Middleware\RoleOrPermissionMiddleware` | Accepts either a role or a permission |

Common guard patterns used on routes:

| Pattern | Levels Allowed |
|---------|----------------|
| `auth` | Any authenticated user |
| `guest` | Unauthenticated users only (e.g. login) |
| `throttle:10,1` | Rate limiting on login attempts |
| `role:admin` | Admin only |
| `role:admin,manager` | Admin or Manager |
| `permission:view {module}` | Requires specific view permission |
| `permission:create {module}` | Requires create permission |
| `permission:edit {module}` | Requires edit permission |
| `permission:delete {module}` | Requires delete permission |
| `api` | API group middleware |
| `SecurityHeadersMiddleware` | Adds security headers (CSP, X-Frame-Options, HSTS, etc.) |
| `ApiRateLimitMiddleware` | Per-IP / per-user API rate limiting |

> Note: Default Spatie's middleware quotes permission names. The custom `RoleMiddleware` wraps the standard `role` handling.

---

## 6. Policies

Policies provide object-level authorization for the admin-related models. All checks apply **unless the user has the `admin` role** (which bypasses via `Gate::before`).

### UserPolicy
Methods: `viewAny`, `view`, `create`, `update`, `delete`, `toggleStatus`
- `delete` — prevents **self-delete** and **admin-delete** (an admin account cannot be deleted).
- `toggleStatus` — prevents **self-toggle** (a user cannot deactivate their own account).

### RolePolicy
Methods: `viewAny`, `view`, `create`, `update`, `delete`, `assignPermissions`
- `assignPermissions` — authorizes assigning granular permissions to a role.
- Prevents modification of the **`admin` role** (the built-in super-admin role is guarded from changes).

### PermissionPolicy
Methods: `viewAny`, `view`, `create`, `update`, `delete`
- Standard CRUD authorization, with a `manage` permission fallback for administrative permission management.

---

## 7. Key Takeaways

1. Admin is effectively **super-user** and bypasses all permission, policy, and role-matrix checks.
2. Access is enforced at **three layers**: route middleware (`role`/`permission`), object authorization (policies), and UI visibility (sidebar `@can` directives).
3. Role `level` (from `getRoleLevel`) is used to gate hierarchical operations (e.g. admin-only management actions).
4. The access matrix above is **inferred**; confirm seeded permissions and business intent before relying on it in auditing or compliance.
