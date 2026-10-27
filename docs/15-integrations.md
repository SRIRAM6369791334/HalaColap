# External Integrations — Flockwise BizTrack

## 1. WhatsApp Integration (Simulated)

| Component | Details |
|-----------|---------|
| **Job** | `ShareInvoiceWhatsAppJob` |
| **Status** | Simulated — no real API call is made |
| **Purpose** | Prepares payload for WhatsApp message sharing of invoices |

**Trigger Points:**
- Weekly billing `share-url` endpoint — triggers job when share URL is generated
- Daily billing — WhatsApp share option in UI

**Current Behavior:**
The job logs the intended WhatsApp message payload (phone number, message body, media URL) but does not make any external HTTP request. It is a placeholder ready to be wired to the WhatsApp Business API or a provider like Twilio/Msg91.

**Future Integration Options:**
- WhatsApp Business API (direct)
- Twilio WhatsApp
- WATI / Interakt / AiSensy (India-specific providers)

---

## 2. PDF Generation

| Package | `barryvdh/laravel-dompdf` |
|---------|--------------------------|
| **Engine** | DOMPDF (HTML to PDF rendering) |
| **Purpose** | Generate downloadable/printable PDF documents |

**Usage Locations:**

| Document Type | View Template |
|---------------|---------------|
| Sales Reports (daily/weekly/monthly) | `views/reports/pdf/sales-*.blade.php` |
| Purchase Reports (daily/weekly/monthly) | `views/reports/pdf/purchases-*.blade.php` |
| Customer Ledger | `views/reports/pdf/customer-ledger.blade.php` |
| Dealer Ledger | `views/reports/pdf/dealer-ledger.blade.php` |
| Vendor Ledger | `views/reports/pdf/vendor-ledger.blade.php` |
| Profit Reports | `views/reports/pdf/profit-*.blade.php` |
| Purchase Invoices | `views/purchases/invoices-pdf.blade.php` |
| Weekly/Daily Invoice Printouts | `views/layouts/pdf.blade.php` (base layout) |

**PDF Layout Base:**
All PDFs use `views/layouts/pdf.blade.php` as the base layout, which provides:
- A4 page sizing
- Company header with MSR BROILERS branding
- Print-optimized CSS
- Footer with page numbers

---

## 3. Google Maps

| Component | Details |
|-----------|---------|
| **Loading** | CDN script tag in `resources/views/layouts/app.blade.php` |
| **Status** | Script loaded but not visibly used in any page |

The Google Maps JavaScript API script is included in the main layout but no map components or geolocation features are currently implemented in the visible codebase. This may be reserved for future route visualization or delivery tracking features.

---

## 4. External CDN Libraries

The application loads several frontend libraries via CDN for instant rendering alongside the Vite-compiled assets.

| Library | Version | CDN | Purpose |
|---------|---------|-----|---------|
| **Tailwind CSS** | 3.x | CDN | Utility-first CSS framework for instant styling before Vite compiles |
| **Alpine.js** | 3.x | CDN | Lightweight reactive JavaScript for UI interactions |
| **Chart.js** | 4.x | CDN | Dashboard charts, analytics visualizations |
| **SweetAlert2** | Latest | CDN | Beautiful alert/confirm dialogs, toast notifications |
| **Material Symbols** | Latest | Google Fonts CDN | Icon font for UI icons throughout the application |
| **Phosphor Icons** | Latest | unpkg CDN | Alternative icon set used in select components |

**CDN Loading Strategy:**
- Tailwind CDN is loaded in the `<head>` of `app.blade.php` for immediate rendering
- Alpine.js is loaded with `defer` attribute for non-blocking initialization
- Chart.js is loaded on dashboard/analytics pages
- SweetAlert2 is loaded globally for consistent alert UX
- Icon fonts are loaded via Google Fonts `link` tags

**Note:** Vite compiles the application's custom CSS/JS. CDN libraries provide baseline functionality before Vite-bundled assets finish loading.
