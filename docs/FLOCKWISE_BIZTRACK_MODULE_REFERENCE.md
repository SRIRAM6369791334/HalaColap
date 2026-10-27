# MSR BROILERS MANAGEMENT SYSTEM
# FLOCKWISE BIZTRACK
# MODULE REFERENCE MATRIX

**Document Version:** 1.0  
**Application:** Flockwise BizTrack (MSR BROILERS Management System)  
**Target Audience:** System Administrators, Business Analysts, Department Leads, and Operations Staff  

---

## Executive Module Map

This quick reference guide summarizes all confirmed modules across Flockwise BizTrack. It outlines each module's core business purpose, primary user actions, and interrelated dependencies across the enterprise system.

---

## 1. Master Records

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Customers** | Maintains retail outlets, wholesale buyers, and direct poultry purchasers. | • Add/Edit/Delete customer profile<br>• View billing and payment history<br>• Download customer statement / ledger PDF<br>• Assign delivery route | • Delivery Routes<br>• Daily Billing<br>• Customer Payments<br>• Customer Ranking Report |
| **Dealers** | Manages commercial broiler distributors receiving daily bird dispatches. | • Add/Edit/Delete dealer firm<br>• View cumulative pending debt<br>• Inspect purchase history<br>• Download dealer ledger PDF | • Delivery Routes<br>• Dayload Billing<br>• Weekly Billing<br>• Dealer Payments |
| **Vendors** | Manages suppliers providing feed, day-old chicks, medicines, and farm supplies. | • Add/Edit/Delete supplier<br>• Toggle retail shop status<br>• View vendor purchase ledger<br>• Inspect vendor advance credits | • Purchases<br>• Purchase Invoices<br>• Dayload Billing<br>• Vendor Payments<br>• Vendor Advances |
| **Delivery Routes & Fleet** | Coordinates logistics delivery routes, transport vehicles, and commercial drivers. | • Add/Edit vehicles (capacity, type)<br>• Register commercial drivers<br>• Configure delivery zones and routes<br>• Assign drivers and trucks to routes | • Customers Master<br>• Dealers Master<br>• Dayload Billing |

---

## 2. Day-Load & Billing Operations

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Dayload Billing** | Core daily bird harvesting, crate weighing, weight shrinkage settlement, and dispatch billing. | • Create/open daily batch by date<br>• Add individual loading entries<br>• Rapid bulk entry across dealers<br>• Transfer weight between entries<br>• Split entries (box conservation)<br>• Approve transit weight loss (creates expense)<br>• Finalize day-load invoice<br>• Record direct collections/payouts<br>• Print and export daily invoices | • Vendors Master<br>• Dealers Master<br>• Cash & Bank Ledger<br>• Operational Expenses<br>• Dealer Payments<br>• Vendor Payments<br>• Profit & Loss |
| **Daily Billing Generate** | Creates single-day invoices for dealers or retail customers with optional tax (GST). | • Select dealer and date interval<br>• Calculate daily sales and payments<br>• Generate daily invoice voucher<br>• Print, PDF, and share statements | • Customers Master<br>• Dealers Master<br>• Dayload Billing<br>• Cash & Bank Ledger |
| **Weekly Billing Generate** | Consolidates 7 days of bird loads, accounts for mid-week cash collections, and applies volume discounts. | • Select dealer and weekly date range<br>• Consolidate linked Dayload entries<br>• Factor in Monday–Friday payments<br>• Apply agreed volume discounts<br>• Generate weekly bill document<br>• Batch-print and download dealer ZIP | • Dealers Master<br>• Dayload Billing<br>• Dealer Payments<br>• Cash & Bank Ledger<br>• Profit & Loss |

---

## 3. Procurement & Purchases

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Purchases** | Records inbound supplier shipments of feed, day-old chicks, medicines, and farm equipment. | • Record supplier invoice header<br>• Add item lines, quantities, and rates<br>• Auto-create newly entered items<br>• Apply input GST tax rates<br>• Select cash, bank, or credit terms<br>• Generate monthly loan EMI schedules<br>• Automatic stock-in to warehouses | • Vendors Master<br>• Inventory Items<br>• Warehouse Stock<br>• Cash & Bank Ledger<br>• Loan EMI Records<br>• Operational Expenses |
| **Purchase Invoices** | Historical archive of all recorded vendor purchases organized chronologically. | • Browse purchases grouped by date<br>• Inspect line item breakdowns<br>• Print supplier payment vouchers<br>• Export invoice archives to PDF | • Vendors Master<br>• Purchases<br>• Purchases Reports |

---

## 4. Payments & Financial Settlements

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Customer Payments** | Records cash on delivery (COD) and bank collections from direct customers. | • Record cash / bank collection<br>• Select payment type (Advance/Part/Full)<br>• Update customer outstanding balance<br>• Update daily cash drawer inflows | • Customers Master<br>• Cash & Bank Ledger |
| **Dealer Payments** | Records dealer collections and executes multi-tier priority allocation (FIFO). | • Record cash and bank payment splits<br>• Execute automated FIFO debt allocation<br>• Settle selected loan EMIs first<br>• Clear oldest historical debt next<br>• Settle day-load deliveries chronologically<br>• Convert excess into vendor advances<br>• Maintain adjustment audit logs | • Dealers Master<br>• Dayload Billing<br>• Loan EMI Records<br>• Vendor Advances<br>• Cash & Bank Ledger |
| **Vendor Payments** | Records payments and advance drawdowns made to feed mills, hatcheries, and vendors. | • Record cash and bank payouts<br>• Settle vendor EMIs and old deliveries<br>• Apply against general ledger balance<br>• Recalculate cash outflow ledgers | • Vendors Master<br>• Purchases<br>• Vendor Advances<br>• Cash & Bank Ledger |
| **Vendor Advances** | Tracks lump-sum deposits or pre-payments issued to suppliers before goods arrive. | • Create advance payment record<br>• Track status (Pending / Adjusted)<br>• Offset against open day-load deliveries<br>• Reduce fresh cash expenditure | • Vendors Master<br>• Dayload Billing<br>• Purchases<br>• Cash & Bank Ledger |

---

## 5. Inventory & Flock Management

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Items Catalog** | Master register of all farm consumables, feed types, medicines, vaccines, and equipment. | • Add/Edit/Delete catalog items<br>• Define base measurement units<br>• Configure reorder alert thresholds | • Purchases<br>• Warehouses<br>• Stock Ledger<br>• Consumptions |
| **Bird Batches** | Tracks flock biological lifecycle from shed placement to harvesting and sale. | • Create batch (code, breed, count)<br>• Monitor live count and FCR<br>• Track cumulative feed and mortalities<br>• Close batch upon complete harvest | • Dayload Billing<br>• Consumptions<br>• Mortalities<br>• Flock Analytics<br>• Profit & Loss |
| **Warehouses** | Tracks physical farm godowns, shed stores, and cold storage depots. | • Register storage warehouses<br>• View localized warehouse stock | • Purchases<br>• Items Catalog<br>• Stock Ledger<br>• Consumptions |
| **Stock Management** | Real-time tracking of item quantities on hand and stock ledger transaction history. | • View current quantities in stock<br>• Monitor low stock alert tags<br>• View IN / OUT / ADJUST movements | • Purchases<br>• Consumptions<br>• Items Catalog<br>• Warehouses |
| **Consumptions** | Daily logs of feed bags and medicine doses consumed by specific flock batches. | • Log daily bags/units consumed<br>• Select batch and source warehouse<br>• Auto-deduct warehouse stock<br>• Update batch feed conversion (FCR) | • Bird Batches<br>• Items Catalog<br>• Warehouses<br>• Stock Management |
| **Mortalities** | Daily records of dead birds found in farm sheds to maintain precise live counts. | • Log dead bird counts per shed batch<br>• Tag primary reason (heat, disease)<br>• Auto-reduce batch live bird count<br>• Feed mortality curves in analytics | • Bird Batches<br>• Flock Analytics |
| **Inventory Analytics** | Analytical visualization of flock weight gain, mortality curves, and feed efficiency. | • Review batch growth charts<br>• Compare mortality rates across sheds<br>• Inspect feed conversion trends | • Bird Batches<br>• Consumptions<br>• Mortalities |

---

## 6. Finance & Treasury

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Cash & Bank Ledger** | Daily two-column accounting reconciling physical drawer cash against bank balances. | • Track daily opening cash & bank<br>• Aggregate daily inflows and outflows<br>• Compute closing balances<br>• Cascade balances to future days<br>• Approve day and sweep cash into bank | • Dayload Billing<br>• Purchases<br>• All Payment Modules<br>• Expenses<br>• Capital Transactions |
| **Capital & Investments** | Tracks equity injections, partner drawings, and internal bank-cash fund transfers. | • Record capital equity deposits<br>• Record owner drawings/withdrawals<br>• Transfer cash drawer funds to bank<br>• Transfer bank funds to drawer cash | • Cash & Bank Ledger |
| **Operational Expenses** | Tracks non-procurement farm overhead costs (diesel, labor, electricity, repairs). | • Add categorized farm expense<br>• Assign payment method (Cash/Bank)<br>• Inspect expenses by category<br>• Soft-delete erroneous entries | • Expense Categories<br>• Cash & Bank Ledger<br>• Profit & Loss |
| **Expense Categories** | Configurable expense classification heads for farm accounting. | • Add/Edit expense category heads<br>• Assign visual badge colors | • Operational Expenses |
| **EMI Records** | Tracks long-term equipment/vehicle loans, monthly installment schedules, and closures. | • Create loan schedule with financier<br>• Track monthly installment due dates<br>• Record installment payments<br>• Execute full loan foreclosures<br>• Display overdue and upcoming alerts | • Purchases<br>• Cash & Bank Ledger<br>• Operational Expenses<br>• Dashboard Alerts |
| **Profit & Loss** | Executive reporting comparing gross bird sales turnover against procurement and overhead. | • View weekly gross margin breakdowns<br>• Compare monthly revenue vs. costs<br>• Analyze expense vs. income ratios<br>• Export P&L statements to PDF/CSV | • Dayload Billing<br>• Purchases<br>• Expenses<br>• Cash & Bank Ledger |

---

## 7. Reports & Business Intelligence

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **Sales Reports** | Analyzes sales turnover, bird kg dispatched, and cash collections over daily, weekly, or monthly periods. | • Generate daily/weekly/monthly views<br>• Filter by date and customer route<br>• Export formatted PDF and CSV files | • Dayload Billing<br>• Daily Billing<br>• Weekly Billing |
| **Purchases Reports** | Analyzes supplier procurement volumes, feed expenditure, and input GST tax credits. | • Generate daily/weekly/monthly views<br>• Filter by supplier and item type<br>• Export formatted PDF and CSV files | • Purchases<br>• Purchase Invoices<br>• Vendors Master |
| **Customer Ranking** | Evaluates customer performance by sales volume (kg) and revenue generated. | • View top poultry buyers<br>• Identify slow-moving or bad debt<br>• Filter by date range | • Customers Master<br>• Dayload Billing |
| **Vendor Analytics** | Tracks pricing trends, unit feed costs, and delivery performance per supplier over time. | • Compare supplier rates per kg<br>• Review historical price trends | • Vendors Master<br>• Purchases |

---

## 8. Administration & System Governance

| Module Name | Business Purpose | Main User Actions | Related Modules |
|---|---|---|---|
| **User Management** | Manages staff logins, security credentials, and system activation status. | • Add new employee accounts<br>• Assign operational user roles<br>• Toggle user status (Active/Inactive) | • Role Management<br>• Activity Logs |
| **Role Management** | Defines authorization security levels and operational responsibility groups. | • View roles (Admin, Manager, etc.)<br>• Define custom roles and levels<br>• Assign granular permission matrices | • User Management<br>• Permissions |
| **Permissions** | Granular feature-level privileges controlling access across all screens and actions. | • Inspect permission groups<br>• Manage `{action} {module}` rules | • Role Management |
| **Activity Logs** | Immutable audit trail recording user actions across the system. | • Review user activity logs<br>• Filter by user, module, and date<br>• Audit sensitive data changes | • All System Modules |

---

*This module reference is maintained as an operational companion to the Flockwise BizTrack Complete User Guide.*
