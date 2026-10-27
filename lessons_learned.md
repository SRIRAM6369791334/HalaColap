# Lessons Learned & Technical Memory — Flockwise BizTrack

## Categories
- `[Documentation_Integrity]`: Ensuring user guides reflect real UI terms and do not expose internal code/secrets.
- `[Business_Logic]`: Documenting domain complexities like FIFO allocations, box weight math, and ledger cascading.
- `[UI_UX_Accuracy]`: Aligning documentation with exact Blade navigation menus and Alpine.js modal behaviors.

---

### [Documentation_Integrity]
- **Date:** 2026-09-04
- **Context:** Generating Customer User Guide from pre-existing technical architecture docs.
- **Rule:** Do not expose sensitive utility maintenance endpoints (`/export-db-2026`, `/run-updates`, etc.) in end-user customer manuals. Treat them as internal admin-only technical matters requiring review.
- **Rule:** Do not invent missing thresholds (e.g. weight loss approval percentages). Mark them explicitly as "Requires Business Confirmation".

### [Tenancy_Authentication_Edge_Case]
- **Date:** 2026-10-26
- **Context:** Tenant onboarding and tenant admin authentication failure ("These credentials do not match our records").
- **Observation:** When Platform Super Admin created a new business tenant at `/admin/tenants/create` using an existing user's email, `TenantManagementController::store` invoked `TenantMembershipService::createOrInvite()`. Because the email already existed in `users`, this triggered `inviteExistingUser()`, creating a `pending` row in `tenant_user_invitations` and firing an email notification (logged to `storage/logs/laravel.log` via `MAIL_MAILER=log`). The tenant admin was never attached to `tenant_user`, and the password specified in the onboarding form was completely discarded. When trying to log in at `/login`, the credentials failed with "These credentials do not match our records". Furthermore, `/login` had no input for `business_code`, preventing users from disambiguating their target tenant.
- **Rule:** When Platform Super Admin provisions a tenant, use `TenantMembershipService::provisionAdmin()`. This ensures that whether the email is new or existing, the user is immediately attached to `tenant_user` with `role = 'admin'` and `is_active = true`, the password is updated to what Super Admin specified, `current_tenant_id` is set, and permissions are synced without requiring unreceivable email invitation loops. In `resources/views/auth/login.blade.php`, provide an optional `business_code` field to allow immediate routing to specific tenants.

- **Context:** Navigation sidebar menu nomenclature.
- **Observation:** In `partials/sidebar.blade.php`, the primary bird loading screen is labeled `Dayload Billing` (route `billing.day-load.index`). The daily invoice generator is labeled `Daily Billing Generate` and weekly invoice generator is `Weekly Billing Generate`. Always preserve these exact menu strings.

### [Business_Logic]
- **Date:** 2026-09-15
- **Context:** Vendor payment FIFO allocation causing fragmentation in UI payout history (`/payments/vendors?tab=payouts`).
- **Observation:** `VendorPaymentService::record` splits a bulk payment into individual `vendor_payments` records for each unpaid `day_load_entry` (e.g., 58 separate rows for a single ₹7,10,000 payment).
- **Rule:** Never display raw fragmented FIFO payment chunks in the end-user Payout History or Ledger tables. Group by `(vendor_id, date)` using `paginatedDailyGrouped` to show a single consolidated daily entry while preserving the underlying allocation links in the database. When deleting a payment, always clean up all related splits and revert `DayLoadEntry->vendor_paid` properly.
- **Rule:** When implementing standalone informational/retail billing tools (like `/billing/daily/create`), ensure they are decoupled from corporate dealer ledgers, inventory deductions, customer credit balances, cash/bank journals, and profit calculations (`ProfitService` must query `DailyBill::whereNotNull('dealer_id')`). Always allow multi-item entry with presets and custom free-text inputs.

### [Dealer_Payment_Granularity]
- **Date:** 2026-09-16
- **Context:** Dealer payment history displaying lumped entries instead of distinct individual transactions across ledger, web invoices, and PDF downloads.
- **Observation:** In `DealerPaymentController::ledger`, grouped payments by `payment_group_id` collapsed separate transactions (e.g. ₹1,000 Cash and ₹2,000 UPI) into a single aggregate row `Lump-Sum Payment (2 allocations): ₹3,000`. Furthermore, `billing/daily/pdf.blade.php` lacked the `Payment History` table entirely, displaying only `Payments Today: - Rs 3,000.00`.
- **Rule:** For dealer auditability, preserve discrete payment rows in Dealer Payment History (`/payments/dealers`), Dealer Ledger (`/payments/dealers/{id}/ledger`), Daily/Weekly Web Invoices (`/billing/daily/{id}/invoice`), and PDF downloads (`/billing/daily/{id}/pdf`). Each payment must show its own Date, Reference (`PAY-XXXX`), Payment Mode (Cash, UPI, etc.), Mode breakdowns, and distinct Amount. Ensure `daily/pdf.blade.php` and `weekly/pdf.blade.php` maintain identical Payment History table layouts.

### [DayLoad_Entry_Granularity]
- **Date:** 2026-09-16
- **Context:** Same dealer and vendor entries in the same batch auto-merging into a single row instead of preserving separate load entries.
- **Observation:** In `DayLoadBillingService::createEntry` and `createBulkEntries`, code checked for an existing active entry matching `(batch_id, vendor_id, dealer_id)`. If found, it added the boxes and weight together, logged an `Auto-merged load` edit, and updated the single entry. This caused successive loads on the same date/batch (e.g. 10:00 AM load of 10 boxes, 11:00 AM load of 5 boxes) to be merged into one 15-box entry.
- **Rule:** Always allow multiple distinct entries for the same dealer and vendor within the same batch. Avoid software-level auto-merging so operations can audit and bill discrete truckloads / trip loads with exact timestamps, remarks, and line items across the Day-load table, billing invoices, and PDFs.

### [DayLoad_Custom_Sequence]
- **Date:** 2026-09-21
- **Context:** Customizable dealer load sequence numbers (Order #) for Day-Load Billing.
- **Observation:** `day_load_entries` previously relied solely on default `latest('id')` query sorting. When adding custom sort orders, unsequenced historical entries had `NULL` values which could unpredictably shift unless queried with `CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END, sort_order ASC, latest('id')`.
- **Rule:** When rendering custom-sequenced batch items, always use `CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END, sort_order ASC, latest('id')` across the index page, print invoices, and PDF generation to guarantee custom-ordered entries (e.g. 1, 2, 3...) appear first in exact order while cleanly preserving reverse-chronological order for legacy records. In Blade form components (`components/form/input.blade.php`), always use `isset($errors) && $errors->has($name)` to prevent standalone rendering exceptions.

### [System_Wide_Sequence_Synchronization]
- **Date:** 2026-09-21
- **Context:** Synchronizing custom load sequence (`sort_order`) across all dependent views, reports, and controllers.
- **Observation:** Adding a custom sequence field to a core transactional model will not automatically affect downstream views if the parent model's Eloquent relationship (e.g., `DayLoadBatch::entries()`) does not include the order specification. Controllers relying on `$batch->entries` or direct `DayLoadEntry::whereHas('batch', ...)->get()` queries defaulted to database insertion order (`id ASC`), causing new items to appear at the bottom.
- **Rule:** When introducing custom sorting, enforce the sort ordering directly on the root Eloquent relationship (`DayLoadBatch->entries()`) so all eager-loads (`with('entries')`) inherit the correct sequence automatically. For controllers performing direct entity queries (such as `PurchaseController`, `DailyBillingController`, `WeeklyBillingController`), consistently append `->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')->orderBy('sort_order', 'asc')->latest('id')` and display sequence badges (`#{{ $e->sort_order }}`) alongside entity names in Blade templates.

### [Aggregate_Query_Reorder_With_Strict_MySQL]
- **Date:** 2026-09-21
- **Context:** Recalculating batch totals in `DayLoadBillingService::refreshBatchTotals` after default ordering was added to Eloquent relationship `DayLoadBatch::entries()`.
- **Observation:** When an Eloquent relationship has default `orderByRaw(...)` or `orderBy(...)`, executing aggregate queries using `->selectRaw('SUM(...)')->first()` carries the `ORDER BY` clause into the final SQL. In MySQL strict mode (`ONLY_FULL_GROUP_BY`), executing `SELECT SUM(...) FROM table WHERE ... ORDER BY sort_order` without a `GROUP BY` triggers SQL error 1140: `Mixing of GROUP columns with no GROUP columns is illegal if there is no GROUP BY clause`.
- **Rule:** Whenever executing aggregate calculations (`selectRaw`, `SUM`, `COUNT`) on an Eloquent relationship that has default order clauses, always invoke `->reorder()` before `->selectRaw()` to explicitly remove any `ORDER BY` clauses from aggregate SQL statements.

### [Dynamic_Multi_Load_Forms]
- **Date:** 2026-09-21
- **Context:** Allowing multiple distinct loads for the same dealer and vendor within a single form submission.
- **Observation:** Traditional web forms keyed strictly by `dealers[id]` cause subsequent loads for the same dealer to overwrite prior rows during HTTP POST array parsing.
- **Rule:** When supporting multiple dynamic rows for the same entity in Alpine/Blade, key rows using unique row IDs (`dealers[uid]` where `uid = 'd_' . id . '_' . timestamp`) or indexed arrays (`dealers[index]`). Ensure hidden inputs submit `dealer_id` explicitly and initialize new rows with auto-incremented `sort_order` sequence numbers. Always filter out untouched extra rows client-side before form submission.

### [Vendor_Advance_Pre_Farm_Weight_Allocation]
- **Date:** 2026-09-21
- **Context:** Allocating vendor advance deposits across newly created load entries before farm weight is entered.
- **Observation:** In `DayLoadEntry`, `getVendorCostAttribute()` defaults to `0.0` when `farm_weight` is `null`. If advance allocation logic evaluates `$entry->vendor_cost` without a fallback to `bird_weight`, the entry's cost resolves to zero, causing the allocation algorithm to treat the entire remaining advance as applicable to the first entry and over-allocating on Entry #1.
- **Rule:** When computing applicable vendor cost for advance deductions prior to farm weight entry, always compute effective cost using `(float)($entry->effective_farm_weight ?? $entry->bird_weight) * $vendorRate` so advance amounts are proportionally and accurately distributed across all batch loads without exceeding any entry's actual cost.

### [Ledger_Date_Resolution_Resilience]
- **Date:** 2026-09-21
- **Context:** Inspecting cash and bank ledger details for arbitrary dates via `/billing/cash-bank-ledger/{date}/details`.
- **Observation:** Using `CashBankLedger::whereDate(...)->firstOrFail()` threw ModelNotFoundException (HTTP 404) if a user inspected a date where transactions existed but the main ledger index had not been navigated to on that day.
- **Rule:** In `CashBankLedgerController::showDay()`, always fall back to `getOrCreateForDate(Carbon::parse($date))` if no record exists so opening cash and bank balances are carried forward cleanly from the previous day without throwing 404 errors.

### [Codebase_Language_Standardization]
- **Date:** 2026-09-21
- **Context:** Maintaining clean standard English across codebase UI elements, Blade templates, and JavaScript logic.
- **Observation:** Explanatory phrases in native languages (such as `இன்னொரு லோடு சேர்க்க` inside button spans) can inadvertently creep into Blade templates while explaining features to users in conversation.
- **Rule:** Keep all source code, Blade templates, HTML attributes, button labels, and code comments strictly in clean, standard English (e.g., `+ Add Another Load`) while reserving vernacular explanations exclusively for chat interactions with the user.

### [Dealer_Master_Preset_Sequence]
- **Date:** 2026-10-08
- **Context:** Storing fixed delivery route / stop order numbers at the Dealer master level (`dealers.sort_order`) and auto-propagating into Day Load Entry sheets.
- **Observation:** Daily data entry operators found it tedious to manually type `1, 2, 3...` into the `ORDER #` input box across 20+ dealer rows every single day.
- **Rule:** Store default delivery sequence numbers on the master model (`dealers.sort_order`). Query dealers using `orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')->orderBy('sort_order', 'asc')->orderBy('firm_name', 'asc')` so dealers with preset sequence numbers appear first in order, followed by unassigned dealers alphabetically. Auto-prefill this sequence into both the Multi-Dealer sheet and single/multi-load repeater, while allowing operators to override the sequence for that specific day without mutating the dealer's master profile.

### [Tenant_Scoped_User_Email_Slugging]
- **Date:** 2026-10-27
- **Context:** Independent business tenants creating staff accounts with identical raw email addresses (e.g., `arun@gmail.com`).
- **Observation:** In a multi-tenant application where `users.email` has a unique database constraint, two separate business tenants creating a staff member with the same email collided. When the email was treated as a single global account, adding the user triggered an invitation flow requiring token acceptance via email, which stalled when users only used local credentials and tenant admins managed passwords directly.
- **Rule:** When creating staff within a tenant context, automatically scope the stored email with the tenant's slug (e.g. `arun.tenant-slug@gmail.com`). In `TenantIdentityService::resolveForCredentials`, support login via Username, Raw Email (matching password against candidate scoped emails), or Scoped Email. In `UpdateTenantMembershipRequest` and `TenantMembershipService::updateMembership`, allow Tenant Admins to directly update staff passwords without requiring public email reset flows.




