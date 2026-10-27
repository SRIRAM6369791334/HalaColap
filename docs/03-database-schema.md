# Database Schema Documentation

Create a comprehensive table-by-table documentation of every database table. Include:
- Table name
- Purpose
- All columns with types, nullable, default
- Primary key, foreign keys
- Indexes, unique constraints

Tables to document (from migration analysis):

### Core Framework Tables
1. **users** — id, name, email (unique), email_verified_at, password, remember_token, phone, username (unique), is_active, timestamps
2. **password_reset_tokens** — email (PK), token, created_at
3. **sessions** — id (PK string), user_id (FK), ip_address, user_agent, payload, last_activity
4. **cache** / **cache_locks** — Key-value cache
5. **jobs** / **job_batches** / **failed_jobs** — Queue tables
6. **personal_access_tokens** — Sanctum tokens
7. **notifications** — Database notifications (uuid PK, type, notifiable morph, data, read_at)

### Business Tables
8. **customers** — id, name, phone, address, gst_number, route (text), type (enum: Retail/Wholesale), balance, route_id (FK→routes), timestamps, soft_deletes. Indexes: name, route, type, balance, composite(route, type)
9. **dealers** — id, firm_name, gst_number, location, contact_person, phone, route (text), pending_amount, route_id (FK→routes), timestamps, soft_deletes. Indexes: firm_name, pending_amount
10. **vendors** — id, firm_name, is_shop, gst_number, location, contact_person, phone, route, notes, pending_amount, timestamps, soft_deletes. Indexes: firm_name
11. **vehicles** — id, vehicle_number (unique), vehicle_type, capacity, timestamps
12. **drivers** — id, driver_name, phone, license_number, timestamps
13. **routes** — id, route_name, zone, vehicle_id (FK), driver_id (FK), is_active, timestamps
14. **weekly_bills** — id, dealer_id (FK→dealers), period_start, period_end, invoice_no (unique), amount, gst_percentage, gst_amount, net_amount, discount_percentage, discount_amount, status (enum: COD/Pending/Bank/Paid), payment_mode, bank_method, previous_outstanding, payments_during_week, monday_payment_amount/status, friday_payment_amount/status, timestamps
15. **weekly_bill_items** — id, weekly_bill_id (FK), item_name, vendor_name, quantity_kg, rate_per_kg, tax_amount, total_amount, timestamps
16. **daily_bills** — id, customer_id (FK nullable), dealer_id (FK nullable), date, date_from, date_to, invoice_no (unique), amount, gst_percentage, gst_amount, net_amount, discount_percentage, discount_amount, payment_mode, bank_method, status (enum: COD/Pending/Bank/Paid), previous_outstanding, payments_during_day, timestamps
17. **daily_bill_items** — id, daily_bill_id (FK), item_name, quantity_kg, rate_per_kg, tax_amount, total_amount, timestamps
18. **day_load_batches** — id, billing_date (index), status (enum: Open/Invoiced/Locked), total_boxes, total_box_weight, total_empty_weight, total_bird_weight, total_farm_weight, total_weight, total_loss_weight, weight_loss_amount, is_weight_loss_approved, weight_loss_approved_by (FK), weight_loss_approved_at, total_dealer_income, total_vendor_cost, total_dealer_collected, total_vendor_paid, invoice_id (FK→day_load_invoices), timestamps
19. **day_load_entries** — id, batch_id (FK→day_load_batches), vendor_id (FK), dealer_id (FK), weekly_bill_id (FK nullable), daily_bill_id (FK nullable), paper_rate, billing_rate, customer_rate, amount, no_of_boxes, box_weight, empty_weight, farm_weight, total_weight, loss_weight, bird_weight, status (enum: Active/Adjusted/Split/Cancelled), parent_entry_id (FK self-referencing), version, remarks, dealer_collected, vendor_paid, dealer_payment_status, vendor_payment_status, timestamps
20. **day_load_invoices** — id, batch_id (FK), invoice_no (unique), invoice_date, total_boxes, total_box_weight, total_empty_weight, total_bird_weight, total_farm_weight, total_weight, total_loss_weight, total_amount, amount_paid, balance_due (STORED), payment_status (enum: Pending/Partial/Paid), status (enum: Draft/Final), version, timestamps
21. **entry_adjustment_logs** — id, entry_id (FK), action_type (enum: Create/Edit/Split/Cancel), old_values, new_values, resulting_entry_id (FK), reason, adjusted_by (FK→users), created_at

### Purchase Tables
22. **purchases** — id, vendor_id (FK nullable), vendor_name, invoice_no, date, due_date, gst_percentage, gst_amount, total_amount, payment_mode (string), timestamps. Indexes: date, vendor_id, invoice_no, payment_mode, composite(date, vendor_id)
23. **purchase_items** — id, purchase_id (FK), item_name, quantity, unit, rate, tax_amount, total_amount, timestamps
24. **dealer_purchases** — id, dealer_id (FK), date, invoice_no (unique), amount, gst_percentage, gst_amount, net_amount, weekly_bill_id (FK nullable), timestamps
25. **dealer_purchase_items** — id, dealer_purchase_id (FK), item_name, quantity_kg, rate_per_kg, tax_amount, total_amount, timestamps

### Payment Tables
26. **customer_payments** — id, customer_id (FK), date, amount, cod_amount, bank_transfer_amount, total_amount (STORED: cod+bank), payment_mode (string: Cash/Bank Transfer), payment_type (enum: Full/Part/Advance/Regular/Adjustment/Opening), balance_after, notes, timestamps
27. **dealer_payments** — id, dealer_id (FK), invoice_id (FK→day_load_invoices nullable), day_load_entry_id (FK nullable), payment_group_id (string nullable), date, amount, discount_amount, cash_amount, bank_amount, total_amount (STORED: cash+bank), payment_mode (string), bank_transfer_type (enum: UPI/Bank Transfer/NEFT/RTGS/IMPS/Cheque/Other), reference_number, notes, pending_balance_after, timestamps
28. **vendor_payments** — id, vendor_id (FK), day_load_entry_id (FK nullable), date, amount, cash_amount, bank_amount, total_amount (STORED: cash+bank), payment_mode (string), bank_transfer_type (enum), reference_number, notes, pending_balance_after, timestamps

### Expense Tables
29. **expenses** — id, date, category (legacy string), category_id (FK→expense_categories nullable), description, amount, payment_method (enum: Cash/Bank Transfer), timestamps, soft_deletes
30. **expense_categories** — id, name (unique), color (default #6B7280), timestamps
31. **emis** — id, emi_type (string: Bank/Finance Company/Bank Loan), entity_id (nullable), loan_name (nullable), bank_name, amount, paid_amount, due_date, status (string: Upcoming/Pending/Paid), timestamps

### Inventory Tables
32. **items** — id, name, code (unique nullable), type (enum: Feed/Chick/Medicine/Vaccine/Equipment/Other), category, brand, base_unit (default kg), conversion_rate, is_active, timestamps
33. **batches** — id, batch_code (unique), placement_date, initial_count, current_count, breed, avg_placement_weight, status (enum: Active/Closed), closed_at, timestamps
34. **warehouses** — id, name, location, is_active, timestamps
35. **stock_ledgers** — id, item_id (FK), batch_id (FK nullable), warehouse_id (FK nullable), quantity, type (enum: IN/OUT), source_type, source_id, unit, transaction_date, remarks, timestamps
36. **poultry_consumptions** — id, date, batch_id (FK), item_id (FK), warehouse_id (FK), quantity, unit, remarks, created_by (FK→users nullable), timestamps
37. **poultry_mortalities** — id, date, batch_id (FK), count, reason, remarks, created_by (FK→users nullable), timestamps
38. **stock_items** (was stock_summary) — id, item_name (unique), category, unit, current_stock, reorder_level, last_updated, timestamps
39. **stock_transactions** (was stock_movements) — id, type (enum: purchase_in/sale_out/adjustment), txn_type (enum: IN/OUT/ADJUST nullable), item_name, quantity, unit, rate, reference_type, reference_id (morph), notes, date, created_by (FK→users nullable), timestamps
40. **bird_batches** — id, batch_name, date_received, initial_count, current_count, avg_weight, timestamps

### Financial Tables
41. **capital_transactions** — id, type (enum: Investment/Transfer to Cash/Transfer to Bank/Transfer from Cash/Transfer from Bank/Withdrawal/Vendor Advance Outflow), date, amount, payment_mode, bank_transfer_type, person_name, reference_number, notes, created_by (FK→users nullable), timestamps
42. **vendor_advances** — id, vendor_id (FK), date, total_amount, cash_amount, bank_amount, investment_amount, adjusted_amount, payment_mode, bank_transfer_type, status (enum: Pending/Partially Adjusted/Fully Adjusted), reference_number, notes, created_by (FK→users nullable), timestamps
43. **vendor_advance_adjustments** — id, vendor_advance_id (FK), day_load_entry_id (FK), amount, date, notes, timestamps
44. **cash_bank_ledgers** — id, ledger_date (unique), opening_cash_balance, opening_bank_balance, cash_income, bank_income, cash_expense, bank_expense, closing_cash_balance, closing_bank_balance, is_approved, approved_amount, approved_by (FK→users nullable), approved_at, timestamps

### Audit/Logging Tables
45. **activity_logs** — id, user_id (FK nullable), action, module, record_id, timestamp
46. **payment_adjustment_logs** — id, payment_id (FK→dealer_payments), action_type, old_values (json), new_values (json), reason, adjusted_by (FK→users nullable), created_at
47. **permission_groups** — id, name (unique), timestamps

### RBAC Tables (Spatie)
48. **permissions** — id, name, guard_name, permission_group_id (FK nullable), timestamps
49. **roles** — id, name, guard_name, description, is_system, created_by (FK→users nullable), timestamps
50. **model_has_permissions** — permission_id (FK), model_type, model_id
51. **model_has_roles** — role_id (FK), model_type, model_id
52. **role_has_permissions** — permission_id (FK), role_id (FK)

For each table, document columns in a table format with Column | Type | Nullable | Default | Description.
