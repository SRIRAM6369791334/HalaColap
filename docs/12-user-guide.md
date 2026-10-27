# MSR BROILERS Management System — User Guide

## Getting Started

### Logging In
1. Open the system URL in your web browser
2. Enter your email/username and password
3. Click "Sign In"
4. You will be redirected to the Dashboard

### Dashboard
The dashboard shows key business metrics:
- Today's Revenue
- Total Customers
- Pending Payments
- Monthly Revenue/Purchases
- Active Batches
- Revenue & Purchase charts
- Recent Transactions
- Alerts (EMI due, low stock, pending payments)

### Dark Mode
Click the theme toggle button in the top-right to switch between light and dark mode.

---

## Modules

## 1. Masters — Managing Business Partners

### Customers

- **Purpose**: Track retail and wholesale poultry customers
- **Who Can Access**: Admin, Manager, Staff (with permissions)
- **How to Use**:
  1. Navigate to Masters → Customers
  2. Click "Add Customer" to create new
  3. Fill: Name, Phone, Address, GST Number, Route, Type (Retail/Wholesale)
  4. Submit to save

- **Fields**:

  | Field | Description | Required |
  |-------|-------------|----------|
  | Name | Customer's name | Yes |
  | Phone | 10-digit mobile number (unique) | Yes |
  | Address | Delivery address | No |
  | GST Number | GST registration number | No |
  | Route | Text-based route label | No |
  | Route | Dropdown route (from Routes module) | No |
  | Type | Retail or Wholesale | Yes |
  | Balance | Current outstanding balance | Auto-calculated |

- **Additional Actions**: View billing history, payment history, EMI history, print ledger PDF

### Dealers

- **Purpose**: Track poultry product dealers who sell to end customers
- **Who Can Access**: Admin, Manager
- **How to Use**: Similar to Customers
- **Fields**: Firm Name, GST Number, Location, Contact Person, Phone, Route, Route, Pending Amount
- **Additional Actions**: Outstanding report, purchase history, dealer ledger PDF

### Vendors

- **Purpose**: Track feed/equipment suppliers
- **Who Can Access**: Admin, Manager
- **How to Use**: Similar to Customers
- **Fields**: Firm Name, Is Shop, GST Number, Location, Contact Person, Phone, Route, Notes, Pending Amount
- **Additional Actions**: Purchase history, history PDF

---

## 2. Billing

### Day-Load Billing (Core Module)

- **Purpose**: Daily bird loading and delivery tracking with dealer/vendor settlements
- **Who Can Access**: Admin, Manager, Accountant
- **How to Use**:
  1. Navigate to Billing → Day Load
  2. Select a date (creates/opens a batch for that date)
  3. Add entries: select Vendor, Dealer, enter number of boxes, box weight, empty weight, farm weight, customer rate
  4. The system auto-calculates: bird weight, loss weight, total weight, amount
  5. Can add multiple entries via "Bulk Store"
  6. Transfer weight between entries if needed
  7. When done, finalize the invoice
  8. Record dealer payments and vendor payments from the same page

- **Key Fields**:

  | Field | Description |
  |-------|-------------|
  | No. of Boxes | Number of crates/boxes loaded |
  | Box Weight | Total weight of boxes with birds |
  | Empty Weight | Weight of empty boxes |
  | Farm Weight | Weight as measured at farm |
  | Customer Rate | Rate per kg charged to dealer |
  | Bird Weight | Auto-calculated: box_weight - empty_weight |
  | Loss Weight | Auto-calculated: farm_weight - bird_weight |
  | Amount | Auto-calculated: bird_weight × customer_rate |

### Weekly Billing

- **Purpose**: Generate weekly invoices for dealers
- **Who Can Access**: Admin, Manager, Accountant
- **How to Use**:
  1. Navigate to Billing → Weekly
  2. Select dealer, date range
  3. System calculates totals (previous outstanding + purchases - payments)
  4. Generate the weekly bill
  5. Can print, export PDF, or share via WhatsApp

### Daily Billing

- **Purpose**: Generate daily invoices for dealers/customers
- **Who Can Access**: Admin, Manager, Accountant
- **How to Use**:
  1. Navigate to Billing → Daily
  2. Select dealer, date range
  3. Preview calculated totals
  4. Generate the daily bill
  5. Can print, export, or share via WhatsApp

### Cash & Bank Ledger

- **Purpose**: Daily cash and bank balance tracking
- **Who Can Access**: Admin, Manager
- **How to Use**:
  1. Navigate to Billing → Cash & Bank Ledger
  2. View daily opening/closing balances
  3. Approve a day's ledger to sweep cash into bank

---

## 3. Purchases

- **Purpose**: Record vendor purchases with GST
- **Who Can Access**: Admin, Manager, Accountant
- **How to Use**:
  1. Navigate to Purchases → Entry
  2. Select vendor, enter invoice number, date, payment mode
  3. Add line items (item name, quantity, rate, unit)
  4. System auto-calculates GST and total
  5. Optionally create EMI schedule for "Pay later" payments
  6. Submit to save
- **Additional**: View invoice archive grouped by date, print individual invoices, export to PDF

---

## 4. Payments

### Customer Payments

- **Purpose**: Record payments received from customers
- **How to Use**:
  1. Navigate to Payments → Customers
  2. Click "Record Payment"
  3. Select customer, enter COD amount and/or bank transfer amount
  4. Choose payment mode (Cash or Bank Transfer)
  5. Select type: Full, Part, Advance, Regular, Adjustment, or Opening
  6. Submit

### Dealer Payments

- **Purpose**: Record payments from dealers (complex allocation)
- **How to Use**:
  1. Navigate to Payments → Dealers
  2. Click "Record Payment"
  3. Select dealer, enter amounts (cash/bank split)
  4. Optionally select specific EMIs to pay
  5. Optionally select specific day-load entries to settle
  6. System allocates payment FIFO across entries
  7. Submit

### Vendor Payments

- **Purpose**: Record payments to vendors
- **How to Use**:
  1. Navigate to Payments → Vendors
  2. Click "Record Payment"
  3. Select vendor, enter amounts
  4. Optionally pay specific EMIs
  5. Submit
- **Additional**: View vendor advances, ledger

---

## 5. Expenses & EMIs

- **Purpose**: Track business expenses and loan EMI payments
- **How to Use**:
  1. Navigate to Expenses
  2. Click "Add Expense"
  3. Select category (Fuel, Salary, Transport, Utilities, Miscellaneous), description, amount, date, payment method
  4. Submit

- **EMI Tracking**:
  1. Navigate to Expenses → EMIs
  2. Create EMI schedule with loan name, bank, total amount, due dates, monthly installments
  3. Pay installments as they come due
  4. System shows alerts for upcoming and overdue EMIs

---

## 6. Inventory

### Items

- **Purpose**: Manage poultry inventory items (Feed, Chick, Medicine, Vaccine, Equipment)
- **How to Use**: CRUD operations at Inventory → Items

### Batches

- **Purpose**: Track bird batches (flock lifecycle)
- **How to Use**: Create batch with code, placement date, breed, initial count. Close when batch completes.

### Stock

- **Purpose**: Real-time stock tracking with movements
- **How to Use**: View current stock levels, low stock alerts, stock movement history

### Consumptions

- **Purpose**: Log daily feed/medicine usage per batch
- **How to Use**: Select batch, item, warehouse, quantity

### Mortalities

- **Purpose**: Log bird deaths per batch
- **How to Use**: Select batch, enter count, reason (Disease, Heat, Cold, etc.), remarks

---

## 7. Profit Analysis

- **Purpose**: Business P&L analysis
- **How to Use**: View weekly breakdown, monthly trends, expense vs income comparison. Export to CSV/PDF.

---

## 8. Reports

- **Purpose**: Comprehensive business reports
- **Available Reports**: Daily/Weekly/Monthly Sales, Daily/Weekly/Monthly Purchases, Customer Ranking, Vendor Analytics, Purchase Analytics
- **How to Use**: Select report type and date range, view results, export to PDF

---

## 9. Routes (Fleet Management)

- **Purpose**: Manage delivery routes, vehicles, drivers
- **How to Use**: Create vehicles, assign drivers, create routes with zone and vehicle/driver assignment

---

## 10. Admin

### Users

- **Purpose**: Manage system users
- **How to Access**: Admin → Users
- **How to Use**: Create users, assign roles, activate/deactivate accounts

### Roles & Permissions

- **Purpose**: Define roles and granular permissions
- **How to Access**: Admin → Roles
- **How to Use**: Create roles, assign permissions via matrix, assign roles to users

### Activity Logs

- **Purpose**: Track all user actions
- **How to Access**: Admin → Activity Logs

---

## Common Tasks

### How to Generate an Invoice

1. Navigate to the billing module (Day-Load/Weekly/Daily)
2. Select the date/period and dealer
3. System auto-calculates amounts
4. Click "Generate" or "Create Invoice"
5. Print or export as needed

### How to Record a Payment

1. Navigate to Payments (Customer/Dealer/Vendor)
2. Click "Record Payment"
3. Fill in the form
4. Submit

### How to Check Profit

1. Navigate to Profit
2. Select time period
3. View revenue, costs, expenses, net profit

### How to Export Data

1. Navigate to the relevant module
2. Click "Export" button
3. Choose format (CSV/PDF)
4. Download the file

---

## Keyboard Shortcuts

- ⌘K / Ctrl+K: Open global search
