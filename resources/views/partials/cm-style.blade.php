<style>
/* ── Reset / Custom CSS Variables (Sleek Theme Matrix) ── */
/* Note: These variables are deprecated. Use Tailwind classes and app.css variables instead. */
:root {
    --cm-bg: #f8fafc;
    --cm-card-bg: #ffffff;
    --cm-card-border: #e2e8f0;
    --cm-text-primary: #0f172a;
    --cm-text-secondary: #475569;
    --cm-text-muted: #94a3b8;
    --cm-accent-emerald: #10b981;
    --cm-accent-emerald-hover: #059669;
    --cm-accent-emerald-light: #e6fbf2;
    --cm-shadow-sm: 0 2px 8px -2px rgba(15, 23, 42, 0.05);
    --cm-shadow-md: 0 12px 24px -4px rgba(15, 23, 42, 0.08), 0 4px 12px -2px rgba(15, 23, 42, 0.04);
    --cm-shadow-lg: 0 24px 40px -8px rgba(15, 23, 42, 0.12), 0 12px 16px -6px rgba(15, 23, 42, 0.06);
}

[data-theme='dark'], .dark {
    --cm-bg: #090d16;
    --cm-card-bg: #111827;
    --cm-card-border: #1f2937;
    --cm-text-primary: #f3f4f6;
    --cm-text-secondary: #9ca3af;
    --cm-text-muted: #6b7280;
    --cm-accent-emerald-light: rgba(16, 185, 129, 0.1);
    --cm-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
    --cm-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
    --cm-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
}

/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Layout ── */
.cm-page { padding: 1rem 0 3rem; }
.cm-hidden { display: none !important; }

/* ── Top Bar ── */
.cm-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 1rem;
    flex-wrap: wrap;
}
.cm-page-title {
    font-size: 1.375rem;
    font-weight: 600;
    color: var(--cm-text-primary);
    letter-spacing: -0.02em;
}
.cm-page-sub {
    font-size: 0.8125rem;
    color: var(--cm-text-muted);
    margin-top: 2px;
}

/* ── Buttons ── */
.cm-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    background: #0f172a;
    color: #fff;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
    text-decoration: none;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.cm-btn-primary:hover { opacity: 0.85; }
.cm-btn-primary--blue { background: #1d4ed8; }
.cm-btn-primary--blue:hover { background: #1e40af; opacity: 1; }

.cm-btn-ghost {
    display: inline-flex;
    align-items: center;
    padding: 9px 16px;
    background: transparent;
    border: 1px solid transparent;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--cm-text-secondary);
    cursor: pointer;
    transition: all 0.2s ease;
}
.cm-btn-ghost:hover { background: #f1f5f9; color: var(--cm-text-primary); border-color: #e2e8f0; }

/* ── Stats ── */
.cm-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .cm-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .cm-stats { grid-template-columns: 1fr; } }

.cm-stat-card {
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--cm-shadow-sm);
}
.cm-stat-card--danger { border-color: #fecaca; }

.cm-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cm-icon-teal  { background: #d1fae5; color: #065f46; }
.cm-icon-blue  { background: #dbeafe; color: #1e40af; }
.cm-icon-amber { background: #fef3c7; color: #92400e; }
.cm-icon-red   { background: #fee2e2; color: #991b1b; }

.cm-stat-label {
    font-size: 0.6875rem;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 2px;
}
.cm-stat-value {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--cm-text-primary);
}

/* ── Table Card ── */
.cm-table-card {
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--cm-shadow-sm);
}

.cm-table-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.875rem 1.25rem;
    border-bottom: 0.5px solid var(--cm-card-border);
    gap: 10px;
    flex-wrap: wrap;
}
.cm-search-wrap {
    position: relative;
    flex: 1;
    max-width: 320px;
}
.cm-search-icon {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--cm-text-muted);
    pointer-events: none;
}
.cm-search-input {
    width: 100%;
    padding: 7px 12px 7px 34px;
    border: 0.5px solid var(--cm-card-border);
    border-radius: 8px;
    font-size: 0.8125rem;
    background: var(--cm-bg);
    color: var(--cm-text-primary);
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.cm-search-input:focus {
    border-color: #6366f1;
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
}

/* ── Table ── */
.cm-table-wrap { overflow-x: auto; padding-bottom: 2px; }
.cm-table-wrap::-webkit-scrollbar { height: 6px; }
.cm-table-wrap::-webkit-scrollbar-track { background: transparent; }
.cm-table-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.cm-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.cm-table thead tr { border-bottom: 0.5px solid var(--cm-card-border); }
.cm-table th {
    padding: 10px 16px;
    font-size: 0.6875rem;
    font-weight: 500;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    text-align: left;
    background: var(--cm-bg);
    white-space: nowrap;
}
.cm-th-right { text-align: right; }
.cm-th-center { text-align: center; }

.cm-tr { transition: background 0.1s; }
.cm-tr:hover { background: var(--cm-bg); }
.cm-td {
    padding: 12px 16px;
    border-bottom: 0.5px solid var(--cm-card-border);
    vertical-align: middle;
    color: var(--cm-text-primary);
}
.cm-table tbody tr:last-child .cm-td { border-bottom: none; }
.cm-td-right { text-align: right; }

/* ── Identity Cell ── */
.cm-identity { display: flex; align-items: center; gap: 10px; }
.cm-avatar {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #dbeafe;
    color: #1e40af;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    flex-shrink: 0;
}

/* Dynamic avatar colors by starting letter */
.cm-avatar--a, .cm-avatar--e, .cm-avatar--i, .cm-avatar--m, .cm-avatar--q, .cm-avatar--u, .cm-avatar--y {
    background: #d1fae5; color: #065f46;
}
.cm-avatar--b, .cm-avatar--f, .cm-avatar--j, .cm-avatar--n, .cm-avatar--r, .cm-avatar--v, .cm-avatar--z {
    background: #dbeafe; color: #1e40af;
}
.cm-avatar--c, .cm-avatar--g, .cm-avatar--k, .cm-avatar--o, .cm-avatar--s, .cm-avatar--w {
    background: #fef3c7; color: #92400e;
}
.cm-avatar--d, .cm-avatar--h, .cm-avatar--l, .cm-avatar--p, .cm-avatar--t, .cm-avatar--x {
    background: #fee2e2; color: #991b1b;
}

.cm-cust-name {
    font-weight: 500;
    color: var(--cm-text-primary);
    text-decoration: none;
    transition: color 0.15s;
    display: block;
}
a.cm-cust-name:hover { color: var(--cm-accent-emerald); }
.cm-cust-meta {
    font-size: 0.75rem;
    color: var(--cm-text-muted);
    margin-top: 1px;
}
.cm-route { font-size: 0.8125rem; color: var(--cm-text-secondary); }

/* ── Balance ── */
.cm-balance { font-weight: 500; }
.cm-balance--due   { color: #dc2626; }
.cm-balance--clear { color: var(--cm-text-muted); }

/* ── Action Buttons ── */
.cm-actions { display: flex; align-items: center; justify-content: center; gap: 6px; }
.cm-action-btn {
    width: 30px;
    height: 30px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 0.5px solid var(--cm-card-border);
    border-radius: 7px;
    background: transparent;
    cursor: pointer;
    color: var(--cm-text-muted);
    text-decoration: none;
    transition: border-color 0.15s, color 0.15s, background 0.15s;
}
.cm-action-btn:hover        { border-color: var(--cm-text-muted); color: var(--cm-text-primary); background: var(--cm-bg); }
.cm-action-btn--edit:hover  { border-color: #93c5fd; color: #1d4ed8; background: #eff6ff; }
.cm-action-btn--danger:hover{ border-color: #fca5a5; color: #dc2626; background: #fef2f2; }

/* ── Empty State ── */
.cm-empty { padding: 3rem 1rem; text-align: center; }
.cm-empty-icon {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: var(--cm-bg);
    color: var(--cm-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
}
.cm-empty-title { font-size: 0.9375rem; font-weight: 600; color: var(--cm-text-primary); margin-bottom: 4px; }
.cm-empty-sub   { font-size: 0.8125rem; color: var(--cm-text-muted); }

/* ── Pagination ── */
.cm-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1.25rem;
    border-top: 0.5px solid var(--cm-card-border);
    flex-wrap: wrap;
    gap: 8px;
}
.cm-pg-info { font-size: 0.75rem; color: var(--cm-text-muted); }
.cm-pg-links { display: flex; gap: 4px; }
.cm-pg-links nav { display: flex; align-items: center; justify-content: center; width: 100%; }
.pagination { display: flex; gap: 4px; list-style: none; padding: 0; margin: 0; }
.page-item .page-link { display: flex; align-items: center; justify-content: center; padding: 4px 10px; min-width: 28px; height: 28px; font-size: 0.75rem; font-weight: 500; color: var(--cm-text-secondary); background: transparent; border: 1px solid transparent; border-radius: 6px; text-decoration: none; transition: all 0.2s; }
.page-item .page-link:hover { background: #f1f5f9; color: var(--cm-text-primary); border-color: #e2e8f0; }
.page-item.active .page-link { background: #0f172a; color: #ffffff; border-color: #0f172a; }
.page-item.disabled .page-link { opacity: 0.5; cursor: not-allowed; background: transparent; color: var(--cm-text-muted); }
[data-theme='dark'] .page-item .page-link:hover { background: #1e293b; border-color: #334155; }
[data-theme='dark'] .page-item.active .page-link { background: #f8fafc; color: #0f172a; border-color: #f8fafc; }

/* ── Modal Overlay ── */
.cm-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 200;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 5vh 1rem 2rem;
    overflow-y: auto;
}
.cm-modal {
    background: var(--cm-card-bg);
    border-radius: 16px;
    border: 0.5px solid var(--cm-card-border);
    width: 100%;
    max-width: 520px;
    overflow: hidden;
    box-shadow: 0 20px 40px -8px rgba(15,23,42,0.15);
}

/* ── Modal Header ── */
.cm-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.125rem 1.5rem;
    border-bottom: 0.5px solid var(--cm-card-border);
    background: var(--cm-bg);
}
.cm-modal-title-row { display: flex; align-items: center; gap: 10px; }
.cm-modal-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cm-modal-icon--green { background: #d1fae5; color: #065f46; }
.cm-modal-icon--blue  { background: #dbeafe; color: #1e40af; }
.cm-modal-title { font-size: 0.9375rem; font-weight: 600; color: var(--cm-text-primary); }
.cm-modal-sub   { font-size: 0.75rem; color: var(--cm-text-muted); margin-top: 1px; }

.cm-close-btn {
    width: 28px;
    height: 28px;
    border: 0.5px solid var(--cm-card-border);
    border-radius: 7px;
    background: transparent;
    cursor: pointer;
    color: var(--cm-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s, color 0.15s, border-color 0.15s;
}
.cm-close-btn:hover { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }

/* ── Modal Body / Form ── */
.cm-modal-body { padding: 1.25rem 1.5rem; }
.cm-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-bottom: 12px;
}
@media (max-width: 480px) { .cm-form-grid { grid-template-columns: 1fr; } }

.cm-form-group { margin-bottom: 12px; }
.cm-form-group:last-of-type { margin-bottom: 0; }

.cm-form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--cm-text-primary);
    margin-bottom: 6px;
}
.cm-required { color: #ef4444; margin-left: 2px; }

.cm-form-input {
    display: block;
    width: 100%;
    padding: 9px 12px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.875rem;
    background: #ffffff;
    color: var(--cm-text-primary);
    outline: none;
    transition: all 0.2s ease;
    font-family: inherit;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.cm-form-input::placeholder { color: #9ca3af; }
.cm-form-input:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15);
}
.cm-uppercase     { text-transform: uppercase; }

/* ── Modal Footer ── */
.cm-modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 0.5px solid var(--cm-card-border);
}
/* ── Billing & Actor Form Specialized Styles ── */
/* Form Card */
.cm-card-form-large { background: var(--cm-card-bg); border: 1px solid var(--cm-card-border); border-radius: 16px; padding: 1.75rem; box-shadow: var(--cm-shadow-md); }
.cm-form-section-title { display: flex; align-items: center; gap: 8px; }
.cm-form-section-title h2 { font-size: 1rem; font-weight: 700; color: var(--cm-text-primary); margin: 0; }
.cm-form-section-title .material-symbols-rounded { font-size: 20px; }

/* Grid headers */
.cm-form-grid-header { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.25rem; margin-bottom: 1.5rem; padding-bottom: 1.5rem; border-bottom: 1px solid var(--cm-card-border); }
@media (max-width: 900px) { .cm-form-grid-header { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 520px) { .cm-form-grid-header { grid-template-columns: 1fr; } }

.cm-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 12px center; background-size: 14px; padding-right: 32px; }

/* Table Row Elements */
.cm-table-header-sub { display: flex; align-items: center; gap: 6px; font-size: 0.8125rem; font-weight: 700; color: var(--cm-text-primary); }
.cm-table-header-sub .material-symbols-rounded { font-size: 18px; }

.cm-table-select { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; background: #ffffff; color: var(--cm-text-primary); font-weight: 500; outline: none; transition: all 0.2s ease; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
.cm-table-select:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }

.cm-table-input { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem; background: #ffffff; color: var(--cm-text-primary); outline: none; transition: all 0.2s ease; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05); }
.cm-table-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.15); }
.cm-readonly { background: var(--cm-card-border); opacity: 0.7; cursor: not-allowed; }

/* Billing Summary Block */
.cm-billing-summary-grid { display: grid; grid-template-columns: 4fr 5fr; gap: 2rem; margin-top: 1rem; }
@media (max-width: 768px) { .cm-billing-summary-grid { grid-template-columns: 1fr; gap: 1.25rem; } }

.cm-summary-info-box { background: var(--cm-bg); border: 1px solid var(--cm-card-border); border-radius: 12px; padding: 1.25rem; }
.cm-tax-fields { display: flex; align-items: center; gap: 1rem; margin-top: 0.75rem; }
.cm-tax-percentage-input { width: 90px; }
.cm-small-label { font-size: 10px; font-weight: 600; color: var(--cm-text-secondary); text-transform: uppercase; margin-bottom: 4px; display: block; }

.cm-glowing-grand-total { background: linear-gradient(135deg, #059669, #10b981); border-radius: 16px; padding: 1.25rem 1.5rem; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.25); color: #ffffff; }
@media (max-width: 520px) { .cm-glowing-grand-total { flex-direction: column; align-items: stretch; text-align: center; } }

.cm-total-details { display: flex; flex-direction: column; }
.cm-total-label { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; opacity: 0.85; letter-spacing: 0.08em; }
.cm-total-value { font-size: 1.75rem; font-weight: 900; font-family: monospace; line-height: 1; margin-top: 4px; }

.cm-submit-total-btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 12px 24px; background: #ffffff; color: #059669; border: none; border-radius: 10px; font-size: 0.8125rem; font-weight: 800; cursor: pointer; transition: transform 0.15s, opacity 0.15s; }
.cm-submit-total-btn:hover { transform: translateY(-1px); opacity: 0.95; }
.cm-submit-total-btn:active { transform: translateY(1px); }
.cm-submit-total-btn .material-symbols-rounded { font-size: 18px; }

/* Bento Actor Portal Grid */
.cm-actor-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
@media (max-width: 960px) { .cm-actor-grid { grid-template-columns: 1fr; gap: 1rem; } }

.cm-actor-card { background: rgba(255, 255, 255, 0.45); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); border: 1px solid rgba(226, 232, 240, 0.8); border-radius: 20px; padding: 1.5rem; display: flex; flex-direction: column; justify-content: space-between; min-height: 190px; cursor: pointer; position: relative; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.02); overflow: hidden; }
[data-theme='dark'] .cm-actor-card { background: rgba(17, 24, 39, 0.45); border: 1px solid rgba(31, 41, 55, 0.7); box-shadow: 0 4px 20px -2px rgba(0,0,0,0.4); }
.cm-actor-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px; background: transparent; transition: all 0.3s ease; }

.cm-actor-card:hover { transform: translateY(-4px) scale(1.01); box-shadow: 0 12px 25px -5px rgba(0,0,0,0.06), 0 8px 10px -6px rgba(0,0,0,0.06); border-color: rgba(16, 185, 129, 0.3); }
[data-theme='dark'] .cm-actor-card:hover { box-shadow: 0 12px 25px -5px rgba(0,0,0,0.5), 0 8px 10px -6px rgba(0,0,0,0.5); border-color: rgba(16, 185, 129, 0.4); }

.cm-actor-card--customer::before { background: linear-gradient(90deg, #10b981, #3b82f6); }
.cm-actor-card--dealer::before { background: linear-gradient(90deg, #6366f1, #a855f7); }
.cm-actor-card--vendor::before { background: linear-gradient(90deg, #0d9488, #0f766e); }

.cm-actor-card--customer.cm-active { background: rgba(16, 185, 129, 0.04); border-color: rgba(16, 185, 129, 0.35); box-shadow: 0 8px 30px rgba(16, 185, 129, 0.08); }
[data-theme='dark'] .cm-actor-card--customer.cm-active { background: rgba(16, 185, 129, 0.06); border-color: rgba(16, 185, 129, 0.5); }
.cm-actor-card--dealer.cm-active { background: rgba(99, 102, 241, 0.04); border-color: rgba(99, 102, 241, 0.35); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.08); }
[data-theme='dark'] .cm-actor-card--dealer.cm-active { background: rgba(99, 102, 241, 0.06); border-color: rgba(99, 102, 241, 0.5); }

.cm-actor-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; background: #f1f5f9; color: #475569; border-radius: 30px; font-size: 0.65rem; font-weight: 800; letter-spacing: 0.05em; width: fit-content; margin-bottom: 1rem; text-transform: uppercase; }
[data-theme='dark'] .cm-actor-badge { background: #1f2937; color: #9ca3af; }
.cm-actor-card--customer .cm-actor-badge { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.cm-actor-card--dealer .cm-actor-badge { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
.cm-actor-card--vendor .cm-actor-badge { background: rgba(13, 148, 136, 0.1); color: #0d9488; }
.cm-actor-badge .material-symbols-rounded { font-size: 14px; }

.cm-active-dot { width: 6px; height: 6px; background-color: #10b981; border-radius: 50%; margin-left: 2px; display: inline-block; box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); animation: cm-pulse-dot 1.6s infinite cubic-bezier(0.66, 0, 0, 1); }
@keyframes cm-pulse-dot { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); } 70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }

.cm-actor-content { margin-bottom: 1.25rem; flex-grow: 1; }
.cm-actor-title { font-size: 1.05rem; font-weight: 800; color: var(--cm-text-primary); margin-bottom: 6px; }
.cm-actor-desc { font-size: 0.75rem; color: var(--cm-text-secondary); line-height: 1.5; margin: 0; }

.cm-actor-actions { display: flex; align-items: center; gap: 8px; width: 100%; }
.cm-actor-btn-primary { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 8px 14px; background: linear-gradient(135deg, #10b981, #059669); color: #ffffff !important; border-radius: 10px; font-size: 0.75rem; font-weight: 700; text-decoration: none; transition: all 0.2s ease; box-shadow: 0 4px 12px -2px rgba(16, 185, 129, 0.2); flex-grow: 1; text-align: center; cursor: pointer; border: none; }
.cm-actor-btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 15px -2px rgba(16, 185, 129, 0.3); opacity: 0.95; }
.cm-actor-btn-primary .material-symbols-rounded { font-size: 16px; }

.cm-actor-card--dealer .cm-actor-btn-primary { background: linear-gradient(135deg, #6366f1, #4f46e5); box-shadow: 0 4px 12px -2px rgba(99, 102, 241, 0.2); }
.cm-actor-card--dealer .cm-actor-btn-primary:hover { box-shadow: 0 6px 15px -2px rgba(99, 102, 241, 0.3); }
.cm-actor-card--vendor .cm-actor-btn-primary { background: linear-gradient(135deg, #0d9488, #0f766e); box-shadow: 0 4px 12px -2px rgba(13, 148, 136, 0.2); }
.cm-actor-card--vendor .cm-actor-btn-primary:hover { box-shadow: 0 6px 15px -2px rgba(13, 148, 136, 0.3); }

/* Missing item chip */
.cm-item-chips-flex { display: flex; flex-wrap: wrap; gap: 4px; }
.cm-item-chip { display: inline-flex; align-items: center; padding: 3px 8px; background: rgba(16, 185, 129, 0.1); color: #059669; border: 1.5px solid rgba(16, 185, 129, 0.08); font-size: 0.6875rem; font-weight: 700; border-radius: 20px; }

/* Enhanced Export Button */
.cm-export-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.4rem 1rem;
    height: 38px;
    background: linear-gradient(145deg, #fff1f2, #ffe4e6);
    color: #e11d48;
    border: 1px solid #fecdd3;
    border-radius: 8px;
    font-size: 0.875rem;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 2px 4px rgba(225, 29, 72, 0.05);
    transition: all 0.2s ease;
}

.cm-export-btn:hover {
    background: linear-gradient(145deg, #ffe4e6, #fecdd3);
    border-color: #fda4af;
    color: #be123c;
    transform: translateY(-1px);
    box-shadow: 0 4px 6px rgba(225, 29, 72, 0.15);
}

.cm-export-btn:active {
    transform: translateY(0);
}

.cm-export-btn svg {
    transition: transform 0.2s ease;
}

.cm-export-btn:hover svg {
    transform: translateY(2px);
}

/* Premium Accordion Form Overrides */
.cm-premium-form-inner .cm-card-form-large {
    border: none;
    box-shadow: none;
    padding: 0;
    background: transparent;
}
.cm-premium-form-inner .cm-form-section-title {
    display: none; /* Hide redundant inner title */
}

/* ── Invoice Show Page UI ── */
.cm-details-card {
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: var(--cm-shadow-md);
    margin-bottom: 2rem;
}

.cm-invoice-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 1px dashed var(--cm-card-border);
    flex-wrap: wrap;
    gap: 1.5rem;
}

.cm-brand-block { display: flex; flex-direction: column; gap: 4px; }
.cm-brand-logo { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    font-size: 1.25rem; 
    font-weight: 800; 
    color: var(--cm-accent-emerald); 
}
.cm-brand-logo .material-symbols-rounded { font-size: 28px; }
.cm-brand-sub { font-size: 0.8125rem; color: var(--cm-text-secondary); margin: 0; }

.cm-invoice-meta-block {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    text-align: right;
}
.cm-invoice-meta-title { font-size: 0.75rem; font-weight: 700; color: var(--cm-text-muted); letter-spacing: 0.1em; margin-bottom: 4px; }
.cm-invoice-meta-id { font-size: 1.5rem; font-weight: 800; font-family: monospace; color: var(--cm-text-primary); line-height: 1; }

.cm-details-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    border-bottom: 1px solid var(--cm-card-border);
}
@media (max-width: 768px) { .cm-details-grid { grid-template-columns: 1fr; gap: 1.25rem; } }

.cm-details-column { display: flex; flex-direction: column; gap: 6px; }
.cm-details-label { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; color: var(--cm-text-muted); letter-spacing: 0.05em; }
.cm-details-value { font-size: 0.9375rem; font-weight: 600; color: var(--cm-text-primary); }
.cm-details-value-firm { font-size: 1.125rem; font-weight: 800; color: var(--cm-accent-emerald); }
.cm-details-sub-text { font-size: 0.75rem; color: var(--cm-text-secondary); margin: 0; }

.cm-badge-mode { 
    display: inline-block; 
    padding: 4px 10px; 
    border-radius: 6px; 
    font-size: 0.75rem; 
    font-weight: 700; 
    background: #f1f5f9; 
    color: #475569; 
    text-transform: uppercase;
}
.cm-badge-mode--cash { background: #d1fae5; color: #065f46; }
.cm-badge-mode--upi { background: #dbeafe; color: #1e40af; }
.cm-badge-mode--bank { background: #fee2e2; color: #991b1b; }

.cm-invoice-footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 2rem;
}

.cm-financial-summary-box {
    width: 100%;
    max-width: 400px;
    background: var(--cm-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 12px;
    padding: 1.5rem;
}

.cm-summary-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.875rem;
    color: var(--cm-text-secondary);
}
.cm-summary-val { font-weight: 600; color: var(--cm-text-primary); }

.cm-grand-net-row {
    margin-top: 12px;
    padding-top: 16px;
    border-top: 1px dashed var(--cm-card-border);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.cm-grand-label { font-size: 1rem; font-weight: 800; color: var(--cm-text-primary); text-transform: uppercase; }
.cm-grand-val { font-size: 1.5rem; font-weight: 900; font-family: monospace; color: var(--cm-accent-emerald); }

</style>
