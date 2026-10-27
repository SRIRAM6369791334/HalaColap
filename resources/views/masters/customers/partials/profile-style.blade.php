<style>
/* ── Reset / Custom CSS Variables (Sleek Theme Matrix) ── */
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
    --cm-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --cm-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
    --cm-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
}

[data-theme='dark'] {
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

/* ── Container & Layout ── */
.cm-page { padding: 1rem 0 3rem; }
.cm-hidden { display: none !important; }

/* ── Back Link ── */
.cm-back-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--cm-accent-emerald);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    text-decoration: none;
    margin-bottom: 1.25rem;
    transition: transform 0.2s ease, color 0.2s ease;
}
.cm-back-btn:hover {
    color: var(--cm-accent-emerald-hover);
    transform: translateX(-4px);
}

/* ── Top Bar ── */
.cm-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    gap: 1.5rem;
    flex-wrap: wrap;
}
.cm-profile-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}
.cm-avatar-lg {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.375rem;
    font-weight: 700;
    box-shadow: var(--cm-shadow-md);
    background: linear-gradient(135deg, #10b981, #06b6d4);
    color: #ffffff;
}

/* Dynamic avatar colors by starting letter */
.cm-avatar-lg--a, .cm-avatar-lg--e, .cm-avatar-lg--i, .cm-avatar-lg--m, .cm-avatar-lg--q, .cm-avatar-lg--u, .cm-avatar-lg--y {
    background: linear-gradient(135deg, #10b981, #3b82f6);
}
.cm-avatar-lg--b, .cm-avatar-lg--f, .cm-avatar-lg--j, .cm-avatar-lg--n, .cm-avatar-lg--r, .cm-avatar-lg--v, .cm-avatar-lg--z {
    background: linear-gradient(135deg, #6366f1, #a855f7);
}
.cm-avatar-lg--c, .cm-avatar-lg--g, .cm-avatar-lg--k, .cm-avatar-lg--o, .cm-avatar-lg--s, .cm-avatar-lg--w {
    background: linear-gradient(135deg, #f59e0b, #ec4899);
}
.cm-avatar-lg--d, .cm-avatar-lg--h, .cm-avatar-lg--l, .cm-avatar-lg--p, .cm-avatar-lg--t, .cm-avatar-lg--x {
    background: linear-gradient(135deg, #ef4444, #f97316);
}

.cm-page-title {
    font-size: 1.625rem;
    font-weight: 800;
    color: var(--cm-text-primary);
    letter-spacing: -0.025em;
    margin: 0;
    line-height: 1.2;
}
.cm-page-sub {
    font-size: 0.8125rem;
    color: var(--cm-text-secondary);
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* ── Actions Group ── */
.cm-actions-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* ── Buttons ── */
.cm-btn-outline {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    background: var(--cm-card-bg);
    color: var(--cm-text-secondary);
    border: 1px solid var(--cm-card-border);
    border-radius: 12px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
    text-decoration: none;
    box-shadow: var(--cm-shadow-sm);
}
.cm-btn-outline:hover {
    background: var(--cm-bg);
    color: var(--cm-text-primary);
    border-color: var(--cm-text-secondary);
}
.cm-btn-outline--sm {
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 8px;
}

.cm-btn-danger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 10px 18px;
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fee2e2;
    border-radius: 12px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.2s ease;
    text-decoration: none;
}
.cm-btn-danger:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
}
[data-theme='dark'] .cm-btn-danger {
    background: rgba(220, 38, 38, 0.1);
    border-color: rgba(220, 38, 38, 0.2);
}
[data-theme='dark'] .cm-btn-danger:hover {
    background: #dc2626;
    color: #ffffff;
    border-color: #dc2626;
}

/* ── Content Grid ── */
.cm-detail-layout {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 2rem;
}
@media (max-width: 1024px) {
    .cm-detail-layout {
        grid-template-columns: 1fr;
    }
}

/* ── Column 1: Info & Balance ── */
.cm-side-col {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.cm-card {
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--cm-shadow-sm);
}

.cm-card-title {
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    border-bottom: 1px solid var(--cm-card-border);
    padding-bottom: 0.75rem;
    margin-bottom: 1.25rem;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cm-info-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}
.cm-info-item {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.cm-info-icon {
    color: var(--cm-text-muted);
    margin-top: 2px;
}
.cm-info-label {
    font-size: 0.6875rem;
    font-weight: 700;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 2px;
}
.cm-info-val {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--cm-text-primary);
    word-break: break-word;
}
.cm-info-val--mono {
    font-family: monospace;
    letter-spacing: -0.02em;
}

/* Balance Card */
.cm-balance-card {
    background: linear-gradient(135deg, #059669 0%, #10b981 50%, #06b6d4 100%);
    border-radius: 18px;
    padding: 2.25rem 1.75rem;
    color: #ffffff;
    text-align: center;
    box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.3);
    position: relative;
    overflow: hidden;
}
.cm-balance-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 60%);
    pointer-events: none;
}
.cm-balance-label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    opacity: 0.85;
    margin-bottom: 0.5rem;
}
.cm-balance-amount {
    font-size: 2.375rem;
    font-weight: 800;
    letter-spacing: -0.03em;
}
.cm-balance-actions {
    margin-top: 1.75rem;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.cm-balance-btn-pay {
    background: #ffffff;
    color: #047857;
    border: none;
    border-radius: 12px;
    padding: 12px 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}
.cm-balance-btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
    background: #f8fafc;
}
.cm-balance-btn-dl {
    background: rgba(4, 120, 87, 0.4);
    color: #ffffff;
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 12px;
    padding: 12px 20px;
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}
.cm-balance-btn-dl:hover {
    transform: translateY(-2px);
    background: rgba(4, 120, 87, 0.6);
    border-color: rgba(255, 255, 255, 0.5);
}

/* ── Column 2: Activity Tabs ── */
.cm-main-col {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.cm-tabs-card {
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: var(--cm-shadow-sm);
}

.cm-tabs-header {
    display: flex;
    border-bottom: 1px solid var(--cm-card-border);
    background: var(--cm-bg);
}
.cm-tab-link {
    flex: 1;
    text-align: center;
    padding: 1.125rem 1rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--cm-text-secondary);
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
}
.cm-tab-link:hover {
    color: var(--cm-text-primary);
    background: var(--cm-card-bg);
}
.cm-tab-link--active {
    color: var(--cm-accent-emerald);
    border-bottom-color: var(--cm-accent-emerald);
    background: var(--cm-card-bg);
    font-weight: 700;
}

.cm-tab-content {
    padding: 2rem;
}

.cm-tab-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    gap: 10px;
}
.cm-tab-title {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--cm-text-primary);
    margin-bottom: 0;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Summary Cards */
.cm-billing-summary-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 2rem;
}
@media (max-width: 768px) {
    .cm-billing-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 480px) {
    .cm-billing-summary-grid {
        grid-template-columns: 1fr;
    }
}

.cm-mini-stat-card {
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    box-shadow: var(--cm-shadow-sm);
}
.cm-mini-stat-label {
    font-size: 0.625rem;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 4px;
}
.cm-mini-stat-val {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--cm-text-primary);
}
.cm-mini-stat-val--green { color: var(--cm-accent-emerald); }
.cm-mini-stat-val--red { color: #dc2626; }
.cm-mini-stat-val--blue { color: #2563eb; }

/* ── Table ── */
.cm-table-wrap { margin-top: 1rem; }
.cm-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.cm-table thead tr { border-bottom: 1px solid var(--cm-card-border); }
.cm-table th {
    padding: 12px 16px;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    text-align: left;
    background: var(--cm-bg);
    white-space: nowrap;
}
.cm-th-right { text-align: right; }
.cm-th-center { text-align: center; }

.cm-tr { transition: background 0.15s ease; border-bottom: 1px solid var(--cm-card-border); }
.cm-tr:hover { background: var(--cm-bg); }
.cm-td {
    padding: 14px 16px;
    vertical-align: middle;
    color: var(--cm-text-primary);
}
.cm-table tbody tr:last-child .cm-td { border-bottom: none; }
.cm-td-right { text-align: right; }
.cm-td-center { text-align: center; }

.cm-mono-val {
    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--cm-text-muted);
}
.cm-bold-val {
    font-weight: 700;
    color: var(--cm-text-primary);
}
.cm-meta-sub {
    font-size: 0.6875rem;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-top: 2px;
}
.cm-desc-val {
    font-size: 0.8125rem;
    color: var(--cm-text-secondary);
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

/* Status Pill */
.cm-status-pill {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.cm-status-pill--paid, .cm-status-pill--cash { background: rgba(16, 185, 129, 0.12); color: #059669; }
.cm-status-pill--pending { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.cm-status-pill--generated, .cm-status-pill--bank { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
.cm-status-pill--upi { background: rgba(139, 92, 246, 0.12); color: #7c3aed; }

/* Table Action Button */
.cm-table-action-btn {
    width: 28px;
    height: 28px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--cm-card-border);
    border-radius: 8px;
    background: var(--cm-card-bg);
    cursor: pointer;
    color: var(--cm-text-secondary);
    text-decoration: none;
    transition: all 0.2s ease;
}
.cm-table-action-btn:hover {
    border-color: var(--cm-accent-emerald);
    color: var(--cm-accent-emerald);
    background: var(--cm-accent-emerald-light);
}

/* Empty Cell styling */
.cm-empty-cell {
    padding: 3rem 1rem;
    text-align: center;
}
.cm-empty-icon-sub {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: var(--cm-bg);
    color: var(--cm-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.75rem;
}
.cm-empty-text {
    font-size: 0.8125rem;
    color: var(--cm-text-muted);
}

/* Pagination Info and Links */
.cm-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1.5rem;
    margin-top: 1rem;
    border-top: 1px solid var(--cm-card-border);
    flex-wrap: wrap;
    gap: 8px;
}
.cm-pg-info { font-size: 0.75rem; color: var(--cm-text-muted); }
.cm-pg-links { display: flex; gap: 4px; }
.cm-pg-links nav p { display: none; }
.cm-pg-links svg { width: 16px; height: 16px; }
.cm-pg-links span[aria-current="page"] > span {
    background: var(--cm-accent-emerald) !important;
    color: white !important;
    border-color: var(--cm-accent-emerald) !important;
}

/* ── Badges ── */
.cm-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 0.6875rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.cm-badge--wholesale { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
.cm-badge--retail    { background: rgba(16, 185, 129, 0.12); color: #059669; }
.cm-badge--route     { background: rgba(107, 114, 128, 0.06); color: var(--cm-text-secondary); border: 1px solid var(--cm-card-border); }
[data-theme='dark'] .cm-badge--wholesale { background: rgba(59, 130, 246, 0.2); color: #60a5fa; }
[data-theme='dark'] .cm-badge--retail    { background: rgba(16, 185, 129, 0.2); color: #34d399; }

/* ── Item Chips ── */
.cm-item-chip {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    border: 1.5px solid rgba(16, 185, 129, 0.05);
    font-size: 0.725rem;
    font-weight: 700;
    border-radius: 20px;
    transition: all 0.2s ease;
}
.cm-item-chip:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}
[data-theme='dark'] .cm-item-chip {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
}

/* Sub Tab Active Styling */
.cm-sub-tab.active {
    background: var(--cm-accent-emerald-light) !important;
    color: var(--cm-accent-emerald) !important;
    box-shadow: var(--cm-shadow-sm);
}

/* Overview specific styling from show */
.cm-overview-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}
@media (max-width: 1024px) {
    .cm-overview-grid {
        grid-template-columns: 1fr;
    }
}
.cm-stat-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: var(--cm-card-bg);
    border: 1px solid var(--cm-card-border);
    border-radius: 12px;
}
.cm-stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.cm-stat-card--blue .cm-stat-icon { background: rgba(59, 130, 246, 0.1); color: #2563eb; }
.cm-stat-card--purple .cm-stat-icon { background: rgba(168, 85, 247, 0.1); color: #a855f7; }
.cm-stat-card--emerald .cm-stat-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.cm-stat-label { font-size: 0.75rem; color: var(--cm-text-muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; }
.cm-stat-value { font-size: 1.5rem; font-weight: 800; color: var(--cm-text-primary); display: flex; align-items: baseline; gap: 8px; }

/* Method / Mode Tag styling */
.cm-method-tag {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    border: 1px solid var(--cm-card-border);
    background: var(--cm-bg);
    color: var(--cm-text-secondary);
}
.cm-method-tag--cash { background: rgba(16, 185, 129, 0.08); color: #059669; border-color: rgba(16, 185, 129, 0.2); }
.cm-method-tag--bank { background: rgba(59, 130, 246, 0.08); color: #2563eb; border-color: rgba(59, 130, 246, 0.2); }
.cm-method-tag--upi  { background: rgba(139, 92, 246, 0.08); color: #7c3aed; border-color: rgba(139, 92, 246, 0.2); }
.cm-method-tag--check, .cm-method-tag--cheque { background: rgba(245, 158, 11, 0.08); color: #d97706; border-color: rgba(245, 158, 11, 0.2); }
</style>
