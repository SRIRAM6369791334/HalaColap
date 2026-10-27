# Business Logic Findings and Observations — Flockwise BizTrack

## Core Business: MSR BROILERS

This is a **broiler poultry farm management system** for MSR BROILERS. The business involves:

1. **Raising broiler chickens** — batches with placement dates, breeds, mortality tracking
2. **Selling birds to dealers** — dealers sell to end customers
3. **Purchasing feed/medicine from vendors** — supplier management
4. **Managing daily bird loading** — boxes of birds transported to dealers
5. **Billing dealers for birds sold** — daily and weekly invoicing
6. **Collecting payments from dealers** — FIFO allocation system
7. **Paying vendors for supplies** — purchase payments
8. **Tracking profitability** — per batch, per dealer, per period

---

## Key Business Concepts

### Day-Load System

The "day-load" is the **core daily operation** of the farm:

```
Farm → Load birds into boxes/crates → Transport to dealer → Deliver to customers
```

**Key Data Points:**

| Field | Description |
|-------|-------------|
| `boxes` | Number of boxes loaded |
| `box_weight` | Weight of empty box (subtracted from total) |
| `bird_weight` | Weight of birds (total_weight - box_weight) |
| `farm_weight` | Weight measured at the farm |
| `paper_rate` | Vendor's rate per kg |
| `billing_rate` | Internal billing rate |
| `customer_rate` | Rate charged to dealer per kg |

**Weight Loss Calculation:**
```
farm_weight - bird_weight = weight_loss
weight_loss × rate = loss_amount
```
Weight loss beyond threshold requires approval.

**Rate Margins:**
```
margin = customer_rate - paper_rate
margin_per_kg × bird_weight = profit_per_entry
```

### Cash/Bank Ledger

A sophisticated **daily accounting system**:

```
┌─────────────────────────────────────────────────┐
│                  Daily Ledger                     │
├─────────────┬─────────────┬───────────────────────┤
│   Opening   │   Income    │   Expenses            │
│   Balance   │  Cash  Bank │   Cash   Bank         │
├─────────────┼─────────────┼───────────────────────┤
│  Cash: 5000 │  Cash: 3000 │  Cash:  1000          │
│  Bank: 20000│  Bank: 8000 │  Bank:   5000         │
├─────────────┴─────────────┴───────────────────────┤
│  Closing = Opening + Income - Expenses            │
│  Surplus cash → swept into bank (if approved)     │
└─────────────────────────────────────────────────┘
```

**Cascading Updates:**
- If a day's ledger is recalculated, **all following days** must update their opening balances
- This creates a chain: Day 1 → Day 2 → Day 3 → ... → Day N
- Implemented via `RecalculateCashBankLedgerJob`

### Dealer Payment Allocation (FIFO)

When a dealer makes a payment, allocation follows this priority:

```
Dealer Payment Received
    │
    ├── 1. Selected EMIs (if any)
    │      └── Reduces EMI outstanding
    │
    ├── 2. Pending Amount (overpayment)
    │      └── Reduces dealer's pending balance
    │
    ├── 3. Day-Load Entries (FIFO — oldest first)
    │      └── Reduces pending_amount on each entry
    │      └── Entry status: Pending → Partially Paid → Fully Paid
    │
    └── 4. Vendor Advance (excess)
           └── Creates credit for future vendor purchases
```

### Vendor Advance System

Pre-payments to vendors tracked as advances:

```
Vendor Advance Lifecycle:
    Pending → Partially Adjusted → Fully Adjusted

Flow:
    1. Dealer overpayment → creates vendor advance
    2. Vendor advance → applied against day-load entries
    3. Applied amount reduces vendor's pending balance
```

### GST Handling

```
GST = CGST + SGST (Tamil Nadu intra-state)
     └── Typically 5% total (2.5% + 2.5%)

Applied to:
    ├── Purchases (from vendors)
    ├── Daily bills (to dealers)
    └── Weekly bills (to dealers)
```

Configurable percentage per transaction via `gst_percent` field.

---

## Financial Flows

```
Capital Investment
    ↓
Cash/Bank Ledger (opening balances)
    ↓
Vendor Purchases ─── Stock In ─── Expense Tracking
    ↓
Bird Raising ─── Batches ─── Mortality/Consumption
    ↓
Day-Load Entries ─── Birds loaded ─── Dealer receives
    ↓
Daily/Weekly Bills ─── Dealer billed
    ↓
Dealer Payments ─── Cash/Bank Ledger ─── Vendor Payments
    ↓
Profit = Revenue - Vendor Costs - Expenses
```

### Revenue Streams
- Bird sales to dealers (day-load billing)
- GST collected on sales

### Cost Centers
- Feed purchases (largest cost)
- Medicine purchases
- Day-load losses (weight loss)
- Mortality (bird deaths)
- Operational expenses (transport, labor, utilities)
- EMI payments (equipment/vehicle loans)

---

## Data Integrity Patterns

### 1. Soft Deletes
| Model | Soft Delete |
|-------|-------------|
| Customer | Yes |
| Dealer | Yes |
| Vendor | Yes |
| Expense | Yes |

### 2. Entry Adjustment Logs
Day-load entry changes are tracked:
- Original values preserved
- Adjustment reason required
- Adjusted by user recorded
- Timestamp of adjustment

### 3. Payment Adjustment Logs
Dealer payment allocation changes are logged:
- Previous allocation recorded
- New allocation recorded
- Reason for adjustment
- User who made the change

### 4. Version Tracking
- Day-load entries have `version` field
- Invoices track version history
- Each correction creates a new version

### 5. Status Enums

**Batches:**
```
Open → Invoiced → Locked
```

**Day-Load Entries:**
```
Pending → Partially Paid → Fully Paid → Cancelled
         → Adjusted → Split
```

### 6. Box Conservation
When entries are split, total boxes must remain constant:
```
Original: 10 boxes
Split into: 3 boxes + 7 boxes = 10 boxes ✓
Split into: 4 boxes + 5 boxes = 9 boxes ✗ (rejected)
```

### 7. Overpayment Validation
Dealer payments cannot exceed:
```
payment_amount ≤ pending_amount + selected_emis_total
```
Excess becomes vendor advance (not rejected).

---

## Notification System

| Feature | Implementation |
|---------|----------------|
| **Channel** | Database-only (no email/SMS) |
| **Storage** | `notifications` table |
| **Trigger Events** | Customer/Dealer/Vendor creation, Daily/Weekly bill creation, Purchase creation |
| **Alert Types** | EMI due dates, Low stock warnings, Pending payment reminders |

---

## Caching Strategy

| Cache Key | TTL | Invalidation |
|-----------|-----|-------------|
| `dashboard_stats` | 60 seconds | Auto-expire + `ClearDashboardCacheJob` |
| `masters.items.all` | Until changed | `ItemObserver` clears on save/delete |
| `masters.customers.all` | Until changed | `CustomerObserver` clears on save/delete |

**Observer-based invalidation:**
- `ItemObserver` — clears item cache when items are created, updated, or deleted
- `CustomerObserver` — clears customer cache when customers are created, updated, or deleted
