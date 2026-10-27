# Database Relationship Map

## Entity-Relationship Diagram (Text)

Create a comprehensive relationship map showing all foreign keys and model relationships.

## Relationship Matrix

| Parent Model | Child Model | Relationship | FK Column | Cascade |
|---|---|---|---|---|
| Customer | WeeklyBill | hasMany | weekly_bills.customer_id | CASCADE (now dealer_id) |
| Customer | DailyBill | hasMany | daily_bills.customer_id | CASCADE |
| Customer | CustomerPayment | hasMany | customer_payments.customer_id | CASCADE |
| Dealer | DealerPayment | hasMany | dealer_payments.dealer_id | CASCADE |
| Dealer | DayLoadEntry | hasMany | day_load_entries.dealer_id | RESTRICT |
| Dealer | WeeklyBill | hasMany | weekly_bills.dealer_id | CASCADE |
| Dealer | DealerPurchase | hasMany | dealer_purchases.dealer_id | CASCADE |
| Vendor | VendorPayment | hasMany | vendor_payments.vendor_id | CASCADE |
| Vendor | VendorAdvance | hasMany | vendor_advances.vendor_id | CASCADE |
| Vendor | DayLoadEntry | hasMany | day_load_entries.vendor_id | RESTRICT |
| Vendor | Purchase | hasMany | purchases.vendor_id | NULL |
| Route | Customer | hasMany | customers.route_id | NULL |
| Route | Dealer | hasMany | dealers.route_id | NULL |
| Route | Vehicle | belongsTo | routes.vehicle_id | NULL |
| Route | Driver | belongsTo | routes.driver_id | NULL |
| DayLoadBatch | DayLoadEntry | hasMany | day_load_entries.batch_id | CASCADE |
| DayLoadBatch | DayLoadInvoice | belongsTo | day_load_batches.invoice_id | NULL |
| DayLoadInvoice | DayLoadBatch | hasOne | day_load_batches.invoice_id | NULL |
| DayLoadInvoice | DealerPayment | hasMany | dealer_payments.invoice_id | NULL |
| DayLoadEntry | DayLoadBatch | belongsTo | day_load_entries.batch_id | CASCADE |
| DayLoadEntry | Vendor | belongsTo | day_load_entries.vendor_id | RESTRICT |
| DayLoadEntry | Dealer | belongsTo | day_load_entries.dealer_id | RESTRICT |
| DayLoadEntry | WeeklyBill | belongsTo | day_load_entries.weekly_bill_id | NULL |
| DayLoadEntry | DailyBill | belongsTo | day_load_entries.daily_bill_id | NULL |
| DayLoadEntry | DayLoadEntry (parent) | belongsTo | day_load_entries.parent_entry_id | NULL |
| DayLoadEntry | DayLoadEntry (children) | hasMany | day_load_entries.parent_entry_id | NULL |
| DayLoadEntry | DealerPayment | hasMany | dealer_payments.day_load_entry_id | NULL |
| DayLoadEntry | VendorPayment | hasMany | vendor_payments.day_load_entry_id | NULL |
| DayLoadEntry | VendorAdvanceAdjustment | hasMany | vendor_advance_adjustments.day_load_entry_id | CASCADE |
| DayLoadEntry | EntryAdjustmentLog | hasMany | entry_adjustment_logs.entry_id | CASCADE |
| WeeklyBill | Dealer | belongsTo | weekly_bills.dealer_id | CASCADE |
| WeeklyBill | WeeklyBillItem | hasMany | weekly_bill_items.weekly_bill_id | CASCADE |
| WeeklyBill | DealerPurchase | hasMany | dealer_purchases.weekly_bill_id | NULL |
| WeeklyBill | DayLoadEntry | hasMany | day_load_entries.weekly_bill_id | NULL |
| DailyBill | Customer | belongsTo | daily_bills.customer_id | CASCADE |
| DailyBill | Dealer | belongsTo | daily_bills.dealer_id | CASCADE |
| DailyBill | DailyBillItem | hasMany | daily_bill_items.daily_bill_id | CASCADE |
| DailyBill | DayLoadEntry | hasMany | day_load_entries.daily_bill_id | NULL |
| Purchase | Vendor | belongsTo | purchases.vendor_id | NULL |
| Purchase | PurchaseItem | hasMany | purchase_items.purchase_id | CASCADE |
| DealerPurchase | Dealer | belongsTo | dealer_purchases.dealer_id | CASCADE |
| DealerPurchase | DealerPurchaseItem | hasMany | dealer_purchase_items.dealer_purchase_id | CASCADE |
| DealerPurchase | WeeklyBill | belongsTo | dealer_purchases.weekly_bill_id | NULL |
| CustomerPayment | Customer | belongsTo | customer_payments.customer_id | CASCADE |
| DealerPayment | Dealer | belongsTo | dealer_payments.dealer_id | CASCADE |
| DealerPayment | DayLoadInvoice | belongsTo | dealer_payments.invoice_id | NULL |
| DealerPayment | DayLoadEntry | belongsTo | dealer_payments.day_load_entry_id | NULL |
| VendorPayment | Vendor | belongsTo | vendor_payments.vendor_id | CASCADE |
| VendorPayment | DayLoadEntry | belongsTo | vendor_payments.day_load_entry_id | NULL |
| VendorAdvance | Vendor | belongsTo | vendor_advances.vendor_id | CASCADE |
| VendorAdvance | VendorAdvanceAdjustment | hasMany | vendor_advance_adjustments.vendor_advance_id | CASCADE |
| Expense | ExpenseCategory | belongsTo | expenses.category_id | NULL |
| ExpenseCategory | Expense | hasMany | expenses.category_id | NULL |
| StockLedger | Item | belongsTo | stock_ledgers.item_id | FK |
| StockLedger | Batch | belongsTo | stock_ledgers.batch_id | NULL FK |
| StockLedger | Warehouse | belongsTo | stock_ledgers.warehouse_id | NULL FK |
| Consumption | Batch | belongsTo | poultry_consumptions.batch_id | CASCADE |
| Consumption | Item | belongsTo | poultry_consumptions.item_id | CASCADE |
| Consumption | Warehouse | belongsTo | poultry_consumptions.warehouse_id | CASCADE |
| Consumption | User (creator) | belongsTo | poultry_consumptions.created_by | NULL |
| Mortality | Batch | belongsTo | poultry_mortalities.batch_id | CASCADE |
| Mortality | User (creator) | belongsTo | poultry_mortalities.created_by | NULL |
| Item | StockLedger | hasMany | stock_ledgers.item_id | FK |
| Warehouse | (none) | hasMany (from others) | — | — |
| Batch | StockLedger | hasMany | stock_ledgers.batch_id | NULL FK |
| Emi | Customer | belongsTo (polymorphic-style) | emis.entity_id | — |
| Emi | Dealer | belongsTo (polymorphic-style) | emis.entity_id | — |
| Emi | Vendor | belongsTo (polymorphic-style) | emis.entity_id | — |
| User | ActivityLog | hasMany | activity_logs.user_id | NULL |
| User | Role | belongsToMany (Spatie) | model_has_roles | — |
| Role | Permission | belongsToMany (Spatie) | role_has_permissions | — |
| Permission | PermissionGroup | belongsTo | permissions.permission_group_id | NULL |
| StockTransaction | reference | morphTo | reference_type + reference_id | — |
| CashBankLedger | User (approvedBy) | belongsTo | cash_bank_ledgers.approved_by | NULL |
| CapitalTransaction | User (creator) | belongsTo | capital_transactions.created_by | NULL |
| VendorAdvance | User (creator) | belongsTo | vendor_advances.created_by | NULL |
