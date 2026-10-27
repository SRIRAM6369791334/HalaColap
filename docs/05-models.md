# 05 — Complete Model Analysis

> **MSR BROILERS Management System** — Flockwise BizTrack  
> Total Models: **43**

---

## 1. ActivityLog

| Property | Value |
|---|---|
| **File** | `app/Models/ActivityLog.php` |
| **Table** | `activity_logs` (default) |
| **Traits** | — |
| **Hidden** | — |
| **Timestamps** | Disabled (`$timestamps = false`) |

**Fillable:** `user_id`, `action`, `module`, `record_id`, `timestamp`

**Casts:** `timestamp` → `datetime`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `user` | BelongsTo | User | `user_id` |

---

## 2. Batch

| Property | Value |
|---|---|
| **File** | `app/Models/Batch.php` |
| **Table** | `batches` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `batch_code`, `placement_date`, `initial_count`, `current_count`, `breed`, `avg_placement_weight`, `status`, `closed_at`

**Casts:** `placement_date` → `date`, `closed_at` → `date`

**Relationships:** — (referenced by Consumption, Mortality, PurchaseItem, StockLedger via foreign keys)

**Scopes:**

| Scope | Description |
|---|---|
| `scopeActive($query)` | Filters where `status = 'Active'` |

---

## 3. BirdBatch

| Property | Value |
|---|---|
| **File** | `app/Models/BirdBatch.php` |
| **Table** | `bird_batches` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `batch_name`, `date_received`, `initial_count`, `current_count`, `avg_weight`

**Casts:** `date_received` → `date`

**Relationships:** —

---

## 4. CapitalTransaction

| Property | Value |
|---|---|
| **File** | `app/Models/CapitalTransaction.php` |
| **Table** | `capital_transactions` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `type`, `date`, `amount`, `payment_mode`, `bank_transfer_type`, `person_name`, `reference_number`, `notes`, `created_by`

**Casts:** `date` → `date`, `amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `creator` | BelongsTo | User | `created_by` |

**Custom Methods:**

| Method | Return | Description |
|---|---|---|
| `getCurrentBalance()` (static) | `float` | Calculates net investment balance: invested + from-business − to-business − withdrawn − vendor-advance-outflow |

---

## 5. CashBankLedger

| Property | Value |
|---|---|
| **File** | `app/Models/CashBankLedger.php` |
| **Table** | `cash_bank_ledgers` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `ledger_date`, `opening_cash_balance`, `opening_bank_balance`, `cash_income`, `bank_income`, `cash_expense`, `bank_expense`, `closing_cash_balance`, `closing_bank_balance`, `is_approved`, `approved_amount`, `approved_by`, `approved_at`

**Casts:** `ledger_date` → `date`, all monetary fields → `decimal:2`, `is_approved` → `boolean`, `approved_at` → `datetime`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `approvedBy` | BelongsTo | User | `approved_by` |

---

## 6. Consumption

| Property | Value |
|---|---|
| **File** | `app/Models/Consumption.php` |
| **Table** | `poultry_consumptions` (explicit) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `date`, `batch_id`, `item_id`, `warehouse_id`, `quantity`, `unit`, `remarks`, `created_by`

**Casts:** `date` → `date`, `quantity` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `batch` | BelongsTo | Batch | `batch_id` |
| `item` | BelongsTo | Item | `item_id` |
| `warehouse` | BelongsTo | Warehouse | `warehouse_id` |
| `creator` | BelongsTo | User | `created_by` |

---

## 7. Customer

| Property | Value |
|---|---|
| **File** | `app/Models/Customer.php` |
| **Table** | `customers` (default) |
| **Traits** | HasFactory, SoftDeletes |
| **Hidden** | — |

**Fillable:** `name`, `phone`, `address`, `gst_number`, `route`, `route_id`, `type`, `balance`

**Casts:** `balance` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `routeRelation` | BelongsTo | Route | `route_id` |
| `weeklyBills` | HasMany | WeeklyBill | — |
| `dailyBills` | HasMany | DailyBill | — |
| `payments` | HasMany | CustomerPayment | — |
| `emis` | HasMany | Emi | `entity_id` (filtered `emi_type = 'Customer'`) |

**Scopes:**

| Scope | Description |
|---|---|
| `scopeSearch($query, $term)` | Searches name, phone, route |
| `scopeWithBalance($query)` | Filters where `balance > 0` |

**Accessors:**

| Accessor | Return | Description |
|---|---|---|
| `formatted_balance` | `string` | Formatted ₹ balance or '—' |

**Boot Events:** `created` → Sends ActivityNotification to all users

---

## 8. CustomerPayment

| Property | Value |
|---|---|
| **File** | `app/Models/CustomerPayment.php` |
| **Table** | `customer_payments` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `customer_id`, `date`, `amount`, `cod_amount`, `bank_transfer_amount`, `payment_mode`, `payment_type`, `balance_after`, `notes`

**Casts:** `date` → `date`, `amount` → `decimal:2`, `cod_amount` → `decimal:2`, `bank_transfer_amount` → `decimal:2`, `total_amount` → `decimal:2`, `balance_after` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `customer` | BelongsTo | Customer | `customer_id` |

**Scopes:** `scopeSearch($query, $term)` — Searches via related customer name

---

## 9. DailyBill

| Property | Value |
|---|---|
| **File** | `app/Models/DailyBill.php` |
| **Table** | `daily_bills` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `customer_id`, `dealer_id`, `date`, `date_from`, `date_to`, `amount`, `gst_percentage`, `gst_amount`, `net_amount`, `discount_percentage`, `discount_amount`, `payment_mode`, `bank_method`, `status`, `invoice_no`, `previous_outstanding`, `payments_during_day`

**Casts:** `date`/`date_from`/`date_to` → `date`, all monetary → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `customer` | BelongsTo | Customer | `customer_id` |
| `dealer` | BelongsTo | Dealer | `dealer_id` |
| `items` | HasMany | DailyBillItem | — |
| `dayLoadEntries` | HasMany | DayLoadEntry | `daily_bill_id` |

**Scopes:** `scopeSearch($query, $term)` — Searches via dealer firm_name, customer name, item name

**Accessors:** `invoice_number`, `items_description`, `quantity_kg`, `rate_per_kg`

**Boot Events:** `created` → Sends ActivityNotification

---

## 10. DailyBillItem

| Property | Value |
|---|---|
| **File** | `app/Models/DailyBillItem.php` |
| **Table** | `daily_bill_items` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `daily_bill_id`, `item_name`, `quantity_kg`, `rate_per_kg`, `tax_amount`, `total_amount`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `dailyBill` | BelongsTo | DailyBill | `daily_bill_id` |

---

## 11. DayLoadBatch

| Property | Value |
|---|---|
| **File** | `app/Models/DayLoadBatch.php` |
| **Table** | `day_load_batches` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `billing_date`, `status`, `total_boxes`, `total_box_weight`, `total_empty_weight`, `total_bird_weight`, `total_farm_weight`, `total_weight`, `total_loss_weight`, `weight_loss_amount`, `is_weight_loss_approved`, `weight_loss_approved_by`, `weight_loss_approved_at`, `invoice_id`

**Casts:** `billing_date` → `date`, all weight fields → `decimal:2`, `is_weight_loss_approved` → `boolean`, `weight_loss_approved_at` → `datetime`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `entries` | HasMany | DayLoadEntry | `batch_id` |
| `invoice` | BelongsTo | DayLoadInvoice | `invoice_id` |
| `weightLossApprovedBy` | BelongsTo | User | `weight_loss_approved_by` |

---

## 12. DayLoadEntry

| Property | Value |
|---|---|
| **File** | `app/Models/DayLoadEntry.php` |
| **Table** | `day_load_entries` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `batch_id`, `vendor_id`, `dealer_id`, `weekly_bill_id`, `daily_bill_id`, `paper_rate`, `billing_rate`, `customer_rate`, `amount`, `no_of_boxes`, `box_weight`, `empty_weight`, `farm_weight`, `total_weight`, `loss_weight`, `bird_weight`, `status`, `parent_entry_id`, `version`, `remarks`, `dealer_collected`, `vendor_paid`, `dealer_payment_status`, `vendor_payment_status`

**Casts:** `no_of_boxes`/`version` → `integer`, all weights/rates/amounts → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `batch` | BelongsTo | DayLoadBatch | `batch_id` |
| `vendor` | BelongsTo | Vendor | `vendor_id` |
| `dealer` | BelongsTo | Dealer | `dealer_id` |
| `weeklyBill` | BelongsTo | WeeklyBill | `weekly_bill_id` |
| `dailyBill` | BelongsTo | DailyBill | `daily_bill_id` |
| `parentEntry` | BelongsTo | DayLoadEntry | `parent_entry_id` |
| `childEntries` | HasMany | DayLoadEntry | `parent_entry_id` |
| `advanceAdjustments` | HasMany | VendorAdvanceAdjustment | `day_load_entry_id` |
| `adjustmentLogs` | HasMany | EntryAdjustmentLog | `entry_id` |
| `dealerPayments` | HasMany | DealerPayment | `day_load_entry_id` |
| `vendorPayments` | HasMany | VendorPayment | `day_load_entry_id` |

**Accessors:** `dealer_income`, `effective_farm_weight`, `vendor_cost`, `gross_margin`, `dealer_balance`, `vendor_balance`, `rate_difference`

**Boot Events:** `saving` → Auto-calculates `bird_weight`, `loss_weight`, `total_weight`, `amount`

---

## 13. DayLoadInvoice

| Property | Value |
|---|---|
| **File** | `app/Models/DayLoadInvoice.php` |
| **Table** | `day_load_invoices` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `batch_id`, `invoice_no`, `invoice_date`, `total_boxes`, `total_box_weight`, `total_empty_weight`, `total_bird_weight`, `total_farm_weight`, `total_weight`, `total_loss_weight`, `total_amount`, `amount_paid`, `payment_status`, `status`, `version`

**Casts:** `invoice_date` → `date`, all weight/amount fields → `decimal:2`, `version` → `integer`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `batch` | BelongsTo | DayLoadBatch | `batch_id` |
| `dealerPayments` | HasMany | DealerPayment | `invoice_id` |

---

## 14. Dealer

| Property | Value |
|---|---|
| **File** | `app/Models/Dealer.php` |
| **Table** | `dealers` (default) |
| **Traits** | HasFactory, SoftDeletes |
| **Hidden** | — |

**Fillable:** `firm_name`, `gst_number`, `location`, `contact_person`, `phone`, `route`, `route_id`, `pending_amount`

**Casts:** `pending_amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `routeRelation` | BelongsTo | Route | `route_id` |
| `payments` | HasMany | DealerPayment | — |
| `dayLoadEntries` | HasMany | DayLoadEntry | — |
| `purchases` | HasMany | Purchase | `vendor_name` → `firm_name` |
| `dealerPurchases` | HasMany | DealerPurchase | — |

**Scopes:** `scopeSearch($query, $term)` — Searches firm_name, contact_person, phone

**Accessors:** `formatted_pending`, `dayload_outstanding`, `emi_outstanding`, `displayed_outstanding`

**Boot Events:** `created` → Sends ActivityNotification

---

## 15. DealerPayment

| Property | Value |
|---|---|
| **File** | `app/Models/DealerPayment.php` |
| **Table** | `dealer_payments` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `dealer_id`, `invoice_id`, `day_load_entry_id`, `payment_group_id`, `date`, `amount`, `payment_mode`, `cash_amount`, `bank_amount`, `bank_transfer_type`, `reference_number`, `notes`, `pending_balance_after`, `discount_amount`

**Casts:** `date` → `date`, `amount`/`cash_amount`/`bank_amount`/`discount_amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `dealer` | BelongsTo | Dealer | `dealer_id` |
| `dayLoadInvoice` | BelongsTo | DayLoadInvoice | `invoice_id` |
| `dayLoadEntry` | BelongsTo | DayLoadEntry | `day_load_entry_id` |

**Scopes:** `scopeSearch($query, $term)` — Searches via dealer firm_name

---

## 16. DealerPurchase

| Property | Value |
|---|---|
| **File** | `app/Models/DealerPurchase.php` |
| **Table** | `dealer_purchases` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `dealer_id`, `date`, `invoice_no`, `amount`, `gst_percentage`, `gst_amount`, `net_amount`, `weekly_bill_id`

**Casts:** `date` → `date`, `amount`/`gst_amount`/`net_amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `dealer` | BelongsTo | Dealer | `dealer_id` |
| `weeklyBill` | BelongsTo | WeeklyBill | `weekly_bill_id` |
| `items` | HasMany | DealerPurchaseItem | — |

**Scopes:** `scopeSearch($query, $term)` — Searches dealer name, invoice_no, item names

**Accessors:** `items_description`, `quantity_kg`

---

## 17. DealerPurchaseItem

| Property | Value |
|---|---|
| **File** | `app/Models/DealerPurchaseItem.php` |
| **Table** | `dealer_purchase_items` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `dealer_purchase_id`, `item_name`, `quantity_kg`, `rate_per_kg`, `tax_amount`, `total_amount`

**Casts:** All numeric fields → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `dealerPurchase` | BelongsTo | DealerPurchase | `dealer_purchase_id` |

---

## 18. Driver

| Property | Value |
|---|---|
| **File** | `app/Models/Driver.php` |
| **Table** | `drivers` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `driver_name`, `phone`, `license_number`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `routes` | HasMany | Route | — |

---

## 19. Emi

| Property | Value |
|---|---|
| **File** | `app/Models/Emi.php` |
| **Table** | `emis` (explicit) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `loan_name`, `bank_name`, `amount`, `paid_amount`, `due_date`, `status`, `emi_type`, `entity_id`

**Casts:** `due_date` → `date`, `amount`/`paid_amount` → `decimal:2`

**Appends:** `remaining_amount`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `customer` | BelongsTo | Customer | `entity_id` |
| `dealer` | BelongsTo | Dealer | `entity_id` |
| `vendor` | BelongsTo | Vendor | `entity_id` |

**Accessors:** `remaining_amount` — Computed: `max(0, amount - paid_amount)`

---

## 20. EntryAdjustmentLog

| Property | Value |
|---|---|
| **File** | `app/Models/EntryAdjustmentLog.php` |
| **Table** | `entry_adjustment_logs` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |
| **UPDATED_AT** | Disabled (`null`) |

**Fillable:** `entry_id`, `action_type`, `old_values`, `new_values`, `resulting_entry_id`, `reason`, `adjusted_by`

**Casts:** `old_values`/`new_values` → `array`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `entry` | BelongsTo | DayLoadEntry | `entry_id` |
| `resultingEntry` | BelongsTo | DayLoadEntry | `resulting_entry_id` |
| `adjustedBy` | BelongsTo | User | `adjusted_by` |

---

## 21. Expense

| Property | Value |
|---|---|
| **File** | `app/Models/Expense.php` |
| **Table** | `expenses` (default) |
| **Traits** | HasFactory, SoftDeletes |
| **Hidden** | — |

**Fillable:** `date`, `category`, `category_id`, `description`, `amount`, `payment_method`

**Casts:** `date` → `date`, `amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `category` | BelongsTo | ExpenseCategory | `category_id` |

---

## 22. ExpenseCategory

| Property | Value |
|---|---|
| **File** | `app/Models/ExpenseCategory.php` |
| **Table** | `expense_categories` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `name`, `color`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `expenses` | HasMany | Expense | `category_id` |

---

## 23. Item

| Property | Value |
|---|---|
| **File** | `app/Models/Item.php` |
| **Table** | `items` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `name`, `code`, `type`, `category`, `brand`, `base_unit`, `conversion_rate`, `is_active`, `status`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `stockLedgers` | HasMany | StockLedger | — |

**Scopes:** `scopeActive($query)` — Filters where `is_active = true`

**Accessors/Mutators:**

| Name | Type | Description |
|---|---|---|
| `current_stock` | Accessor | Calculated: IN − OUT from stock_ledgers |
| `status` | Accessor | Returns 'Active'/'Inactive' based on `is_active` |
| `status` | Mutator | Sets `is_active` from 'Active'/'Inactive' string |

---

## 24. Mortality

| Property | Value |
|---|---|
| **File** | `app/Models/Mortality.php` |
| **Table** | `poultry_mortalities` (explicit) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `date`, `batch_id`, `count`, `reason`, `remarks`, `created_by`

**Casts:** `date` → `date`, `count` → `integer`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `batch` | BelongsTo | Batch | `batch_id` |
| `creator` | BelongsTo | User | `created_by` |

---

## 25. PaymentAdjustmentLog

| Property | Value |
|---|---|
| **File** | `app/Models/PaymentAdjustmentLog.php` |
| **Table** | `payment_adjustment_logs` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |
| **UPDATED_AT** | Disabled (`null`) |

**Fillable:** `payment_id`, `action_type`, `old_values`, `new_values`, `reason`, `adjusted_by`

**Casts:** `old_values`/`new_values` → `array`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `payment` | BelongsTo | DealerPayment | `payment_id` |
| `adjustedBy` | BelongsTo | User | `adjusted_by` |

---

## 26. Permission

| Property | Value |
|---|---|
| **File** | `app/Models/Permission.php` |
| **Table** | `permissions` (via Spatie) |
| **Extends** | `Spatie\Permission\Models\Permission` |

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `permissionGroup` | BelongsTo | PermissionGroup | `permission_group_id` |

---

## 27. PermissionGroup

| Property | Value |
|---|---|
| **File** | `app/Models/PermissionGroup.php` |
| **Table** | `permission_groups` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `name`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `permissions` | HasMany | Spatie\Permission\Models\Permission | — |

---

## 28. Purchase

| Property | Value |
|---|---|
| **File** | `app/Models/Purchase.php` |
| **Table** | `purchases` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `vendor_id`, `vendor_name`, `invoice_no`, `date`, `due_date`, `gst_percentage`, `gst_amount`, `total_amount`, `payment_mode`

**Casts:** `date`/`due_date` → `date`, `gst_percentage`/`gst_amount`/`total_amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `vendor` | BelongsTo | Vendor | `vendor_id` |
| `items` | HasMany | PurchaseItem | — |

**Scopes:** `scopeSearch($query, $term)` — Searches vendor_name, item names

**Accessors:** `quantity`, `unit`, `rate`, `item` — All backward-compatibility accessors from items relation

**Boot Events:** `created` → Sends ActivityNotification

---

## 29. PurchaseItem

| Property | Value |
|---|---|
| **File** | `app/Models/PurchaseItem.php` |
| **Table** | `purchase_items` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `purchase_id`, `item_id`, `batch_id`, `warehouse_id`, `item_name`, `quantity`, `unit`, `rate`, `tax_amount`, `total_amount`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `purchase` | BelongsTo | Purchase | `purchase_id` |
| `item` | BelongsTo | Item | `item_id` |
| `batch` | BelongsTo | Batch | `batch_id` |
| `warehouse` | BelongsTo | Warehouse | `warehouse_id` |

---

## 30. Role

| Property | Value |
|---|---|
| **File** | `app/Models/Role.php` |
| **Table** | `roles` (via Spatie) |
| **Extends** | `Spatie\Permission\Models\Role` |

**Fillable:** `name`, `guard_name`, `description`, `is_system`, `created_by`

**Casts:** `is_system` → `boolean`

---

## 31. Route

| Property | Value |
|---|---|
| **File** | `app/Models/Route.php` |
| **Table** | `routes` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `route_name`, `zone`, `vehicle_id`, `driver_id`, `is_active`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `vehicle` | BelongsTo | Vehicle | `vehicle_id` |
| `driver` | BelongsTo | Driver | `driver_id` |
| `customers` | HasMany | Customer | — |
| `dealers` | HasMany | Dealer | — |

---

## 32. StockItem

| Property | Value |
|---|---|
| **File** | `app/Models/StockItem.php` |
| **Table** | `stock_items` (explicit) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `item_name`, `category`, `unit`, `current_stock`, `reorder_level`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `transactions` | HasMany | StockTransaction | `item_name` (non-standard FK) |

---

## 33. StockLedger

| Property | Value |
|---|---|
| **File** | `app/Models/StockLedger.php` |
| **Table** | `stock_ledgers` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `item_id`, `batch_id`, `warehouse_id`, `quantity`, `type`, `source_type`, `source_id`, `unit`, `transaction_date`, `remarks`

**Casts:** `transaction_date` → `date`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `item` | BelongsTo | Item | `item_id` |
| `batch` | BelongsTo | Batch | `batch_id` |
| `warehouse` | BelongsTo | Warehouse | `warehouse_id` |

---

## 34. StockTransaction

| Property | Value |
|---|---|
| **File** | `app/Models/StockTransaction.php` |
| **Table** | `stock_transactions` (explicit) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `type`, `txn_type`, `item_name`, `quantity`, `unit`, `rate`, `reference_type`, `reference_id`, `notes`, `date`, `created_by`

**Casts:** `date` → `date`, `quantity` → `decimal:3`, `rate` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `reference` | MorphTo | (any) | `reference_type` + `reference_id` |

---

## 35. User

| Property | Value |
|---|---|
| **File** | `app/Models/User.php` |
| **Table** | `users` (default) |
| **Traits** | HasFactory, Notifiable, HasRoles (Spatie), HasApiTokens (Sanctum) |
| **Hidden** | `password`, `remember_token` |

**Fillable:** `name`, `email`, `password`, `phone`, `username`, `is_active`

**Casts:** `email_verified_at` → `datetime`, `password` → `hashed`, `is_active` → `boolean`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `activityLogs` | HasMany | ActivityLog | — |

**Custom Methods:**

| Method | Return | Description |
|---|---|---|
| `getRoleLevel()` | `int` | Returns highest role hierarchy level (1–4) |

---

## 36. Vehicle

| Property | Value |
|---|---|
| **File** | `app/Models/Vehicle.php` |
| **Table** | `vehicles` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `vehicle_number`, `vehicle_type`, `capacity`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `routes` | HasMany | Route | — |

---

## 37. Vendor

| Property | Value |
|---|---|
| **File** | `app/Models/Vendor.php` |
| **Table** | `vendors` (default) |
| **Traits** | HasFactory, SoftDeletes |
| **Hidden** | — |

**Fillable:** `firm_name`, `is_shop`, `gst_number`, `location`, `contact_person`, `phone`, `route`, `notes`, `pending_amount`

**Casts:** `is_shop` → `boolean`, `pending_amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `dayLoadEntries` | HasMany | DayLoadEntry | — |
| `purchases` | HasMany | Purchase | — |
| `vendorPayments` | HasMany | VendorPayment | — |
| `advances` | HasMany | VendorAdvance | — |

**Scopes:** `scopeSearch($query, $term)` — Searches firm_name, contact_person, phone; `scopeShops($query)` — Filters `is_shop = true`

**Accessors:** `active_advance_balance`, `emi_outstanding`, `outstanding_balance`

**Boot Events:** `created` → Sends ActivityNotification

---

## 38. VendorAdvance

| Property | Value |
|---|---|
| **File** | `app/Models/VendorAdvance.php` |
| **Table** | `vendor_advances` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `vendor_id`, `date`, `total_amount`, `cash_amount`, `bank_amount`, `investment_amount`, `adjusted_amount`, `payment_mode`, `bank_transfer_type`, `status`, `reference_number`, `notes`, `created_by`

**Casts:** `date` → `date`, all monetary fields → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `vendor` | BelongsTo | Vendor | `vendor_id` |
| `adjustments` | HasMany | VendorAdvanceAdjustment | — |
| `creator` | BelongsTo | User | `created_by` |

**Accessors:** `remaining_amount`, `balance_amount`

---

## 39. VendorAdvanceAdjustment

| Property | Value |
|---|---|
| **File** | `app/Models/VendorAdvanceAdjustment.php` |
| **Table** | `vendor_advance_adjustments` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `vendor_advance_id`, `day_load_entry_id`, `amount`, `date`, `notes`

**Casts:** `date` → `date`, `amount` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `advance` | BelongsTo | VendorAdvance | `vendor_advance_id` |
| `dayLoadEntry` | BelongsTo | DayLoadEntry | `day_load_entry_id` |

---

## 40. VendorPayment

| Property | Value |
|---|---|
| **File** | `app/Models/VendorPayment.php` |
| **Table** | `vendor_payments` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `vendor_id`, `day_load_entry_id`, `date`, `amount`, `payment_mode`, `cash_amount`, `bank_amount`, `bank_transfer_type`, `reference_number`, `notes`, `pending_balance_after`

**Casts:** `date` → `date`, `amount`/`cash_amount`/`bank_amount`/`pending_balance_after` → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `vendor` | BelongsTo | Vendor | `vendor_id` |
| `dayLoadEntry` | BelongsTo | DayLoadEntry | `day_load_entry_id` |

---

## 41. Warehouse

| Property | Value |
|---|---|
| **File** | `app/Models/Warehouse.php` |
| **Table** | `warehouses` (default) |
| **Traits** | — |
| **Hidden** | — |

**Fillable:** `name`, `location`, `is_active`

**Scopes:** `scopeActive($query)` — Filters where `is_active = true`

---

## 42. WeeklyBill

| Property | Value |
|---|---|
| **File** | `app/Models/WeeklyBill.php` |
| **Table** | `weekly_bills` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `dealer_id`, `period_start`, `period_end`, `invoice_no`, `amount`, `gst_percentage`, `gst_amount`, `net_amount`, `discount_percentage`, `discount_amount`, `status`, `payment_mode`, `bank_method`, `previous_outstanding`, `payments_during_week`

**Casts:** `period_start`/`period_end` → `date`, all monetary fields → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `dealer` | BelongsTo | Dealer | `dealer_id` |
| `items` | HasMany | WeeklyBillItem | — |
| `dealerPurchases` | HasMany | DealerPurchase | — |
| `dayLoadEntries` | HasMany | DayLoadEntry | — |

**Scopes:** `scopeSearch($query, $term)` — Searches dealer name, item names

**Accessors:** `invoice_number`, `items_description`, `quantity_kg`

**Boot Events:** `created` → Sends ActivityNotification

---

## 43. WeeklyBillItem

| Property | Value |
|---|---|
| **File** | `app/Models/WeeklyBillItem.php` |
| **Table** | `weekly_bill_items` (default) |
| **Traits** | HasFactory |
| **Hidden** | — |

**Fillable:** `weekly_bill_id`, `item_name`, `vendor_name`, `quantity_kg`, `rate_per_kg`, `tax_amount`, `total_amount`

**Casts:** All numeric fields → `decimal:2`

**Relationships:**

| Method | Type | Related Model | FK |
|---|---|---|---|
| `weeklyBill` | BelongsTo | WeeklyBill | `weekly_bill_id` |

---

## Model Relationship Matrix

| Model | BelongsTo (FK → Model) | HasMany (→ Model) |
|---|---|---|
| **ActivityLog** | `user_id` → User | — |
| **Batch** | — | Consumption, Mortality, PurchaseItem, StockLedger |
| **BirdBatch** | — | — |
| **CapitalTransaction** | `created_by` → User | — |
| **CashBankLedger** | `approved_by` → User | — |
| **Consumption** | `batch_id` → Batch, `item_id` → Item, `warehouse_id` → Warehouse, `created_by` → User | — |
| **Customer** | `route_id` → Route | WeeklyBill, DailyBill, CustomerPayment, Emi |
| **CustomerPayment** | `customer_id` → Customer | — |
| **DailyBill** | `customer_id` → Customer, `dealer_id` → Dealer | DailyBillItem, DayLoadEntry |
| **DailyBillItem** | `daily_bill_id` → DailyBill | — |
| **DayLoadBatch** | `invoice_id` → DayLoadInvoice, `weight_loss_approved_by` → User | DayLoadEntry |
| **DayLoadEntry** | `batch_id` → DayLoadBatch, `vendor_id` → Vendor, `dealer_id` → Dealer, `weekly_bill_id` → WeeklyBill, `daily_bill_id` → DailyBill, `parent_entry_id` → DayLoadEntry | DayLoadEntry (children), VendorAdvanceAdjustment, EntryAdjustmentLog, DealerPayment, VendorPayment |
| **DayLoadInvoice** | `batch_id` → DayLoadBatch | DealerPayment |
| **Dealer** | `route_id` → Route | DealerPayment, DayLoadEntry, Purchase, DealerPurchase |
| **DealerPayment** | `dealer_id` → Dealer, `invoice_id` → DayLoadInvoice, `day_load_entry_id` → DayLoadEntry | — |
| **DealerPurchase** | `dealer_id` → Dealer, `weekly_bill_id` → WeeklyBill | DealerPurchaseItem |
| **DealerPurchaseItem** | `dealer_purchase_id` → DealerPurchase | — |
| **Driver** | — | Route |
| **Emi** | `entity_id` → Customer/Dealer/Vendor (polymorphic-like) | — |
| **EntryAdjustmentLog** | `entry_id` → DayLoadEntry, `resulting_entry_id` → DayLoadEntry, `adjusted_by` → User | — |
| **Expense** | `category_id` → ExpenseCategory | — |
| **ExpenseCategory** | — | Expense |
| **Item** | — | StockLedger |
| **Mortality** | `batch_id` → Batch, `created_by` → User | — |
| **PaymentAdjustmentLog** | `payment_id` → DealerPayment, `adjusted_by` → User | — |
| **Permission** | `permission_group_id` → PermissionGroup | — |
| **PermissionGroup** | — | Permission |
| **Purchase** | `vendor_id` → Vendor | PurchaseItem |
| **PurchaseItem** | `purchase_id` → Purchase, `item_id` → Item, `batch_id` → Batch, `warehouse_id` → Warehouse | — |
| **Role** | — | — (Spatie manages pivot) |
| **Route** | `vehicle_id` → Vehicle, `driver_id` → Driver | Customer, Dealer |
| **StockItem** | — | StockTransaction |
| **StockLedger** | `item_id` → Item, `batch_id` → Batch, `warehouse_id` → Warehouse | — |
| **StockTransaction** | `reference` → MorphTo (any) | — |
| **User** | — | ActivityLog |
| **Vehicle** | — | Route |
| **Vendor** | — | DayLoadEntry, Purchase, VendorPayment, VendorAdvance |
| **VendorAdvance** | `vendor_id` → Vendor, `created_by` → User | VendorAdvanceAdjustment |
| **VendorAdvanceAdjustment** | `vendor_advance_id` → VendorAdvance, `day_load_entry_id` → DayLoadEntry | — |
| **VendorPayment** | `vendor_id` → Vendor, `day_load_entry_id` → DayLoadEntry | — |
| **Warehouse** | — | — |
| **WeeklyBill** | `dealer_id` → Dealer | WeeklyBillItem, DealerPurchase, DayLoadEntry |
| **WeeklyBillItem** | `weekly_bill_id` → WeeklyBill | — |
