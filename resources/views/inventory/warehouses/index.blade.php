@extends('layouts.app')
@section('title', 'Warehouse Master')

@section('content')

<div class="cm-page">

    {{-- Top Bar --}}
    <div class="cm-topbar">
        <div>
            <h1 class="cm-page-title">Warehouse Master</h1>
            <p class="cm-page-sub">Define storage locations, Godowns, and Sheds</p>
        </div>
        <a href="{{ route('inventory.warehouses.create') }}" class="cm-btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
            </svg>
            Add Warehouse
        </a>
    </div>

    {{-- Stats --}}
    <div class="cm-stats">
        <div class="cm-stat-card">
            <div class="cm-stat-icon cm-icon-teal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                    <polyline points="9 22 9 12 15 12 15 22"/>
                </svg>
            </div>
            <div>
                <div class="cm-stat-label">Total Locations</div>
                <div class="cm-stat-value">{{ $warehouses->total() }}</div>
            </div>
        </div>
        <div class="cm-stat-card">
            <div class="cm-stat-icon cm-icon-blue">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M8 14s1.5 2 4 2 4-2 4-2"/>
                    <line x1="9" y1="9" x2="9.01" y2="9"/>
                    <line x1="15" y1="9" x2="15.01" y2="9"/>
                </svg>
            </div>
            <div>
                <div class="cm-stat-label">Active Units</div>
                <div class="cm-stat-value">{{ $warehouses->where('is_active', true)->count() }}</div>
            </div>
        </div>
    </div>

    {{-- Table Card --}}
    <div class="cm-table-card">
        <div class="cm-table-toolbar">
            <form action="{{ route('inventory.warehouses.index') }}" method="GET" class="cm-search-wrap" style="max-width: 320px;">
                <svg class="cm-search-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                    placeholder="Search by name or location..." class="cm-search-input">
            </form>
        </div>

        <div class="cm-table-wrap">
            <table class="cm-table">
                <thead>
                    <tr>
                        <th>Warehouse Name</th>
                        <th>Address / Location</th>
                        <th>Status</th>
                        <th class="cm-th-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warehouses as $warehouse)
                    <tr class="cm-tr">
                        <td class="cm-td">
                            <div class="cm-cust-name">{{ $warehouse->name }}</div>
                        </td>
                        <td class="cm-td">
                            <div class="cm-cust-meta">{{ $warehouse->location ?: '-' }}</div>
                        </td>
                        <td class="cm-td">
                            <span class="cm-badge {{ $warehouse->is_active ? 'cm-badge-teal' : 'cm-badge-red' }}">
                                {{ $warehouse->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="cm-td">
                            <div class="cm-actions">
                                <a href="{{ route('inventory.warehouses.edit', $warehouse) }}"
                                    class="cm-action-btn cm-action-btn--edit" title="Edit Warehouse">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                        stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </a>
                                <form action="{{ route('inventory.warehouses.destroy', $warehouse) }}" method="POST"
                                    onsubmit="return confirm('Remove this warehouse location? Ensure it has no stock first.')" style="display:inline;">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="cm-action-btn cm-action-btn--danger" title="Delete Warehouse">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="3 6 5 6 21 6"/>
                                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                            <path d="M10 11v6"/><path d="M14 11v6"/>
                                            <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="cm-empty">
                            <div class="cm-empty-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28"
                                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                </svg>
                            </div>
                            <p class="cm-empty-title">No storage locations found</p>
                            <p class="cm-empty-sub">Start by adding your first warehouse.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($warehouses->hasPages())
        <div class="cm-pagination">
            <span class="cm-pg-info">
                Showing {{ $warehouses->firstItem() }}–{{ $warehouses->lastItem() }} of {{ $warehouses->total() }} locations
            </span>
            <div class="cm-pg-links">
                {{ $warehouses->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>

</div>

@endsection

@push('styles')
<style>
/* ── Theme Variables & Dark Mode Matrix ── */
:root {
    --cm-bg: #f8fafc;
    --cm-card-bg: #ffffff;
    --cm-card-border: #e2e8f0;
    --cm-text-primary: #0f172a;
    --cm-text-secondary: #475569;
    --cm-text-muted: #94a3b8;
    --cm-accent-teal: #0d9488;
    --cm-accent-teal-hover: #0f766e;
    --cm-accent-teal-light: #f0fdfa;
    --cm-accent-blue: #2563eb;
    --cm-accent-blue-hover: #1d4ed8;
    --cm-accent-blue-light: #eff6ff;
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
    --cm-accent-teal-light: rgba(13, 148, 136, 0.1);
    --cm-accent-blue-light: rgba(37, 99, 235, 0.1);
    --cm-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.5);
    --cm-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.3);
    --cm-shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.4), 0 4px 6px -4px rgba(0, 0, 0, 0.4);
}

/* ── Reset & Base ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Layout ── */
.cm-page { padding: 2rem 0 3rem; }
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
    font-weight: 700;
    color: var(--cm-text-primary);
    letter-spacing: -0.02em;
}
.cm-page-sub {
    font-size: 0.8125rem;
    color: var(--cm-text-secondary);
    margin-top: 2px;
}

/* ── Buttons ── */
.cm-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    background: var(--cm-text-primary);
    color: var(--cm-card-bg);
    border: none;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
    transition: opacity 0.15s;
    text-decoration: none;
}
.cm-btn-primary:hover { opacity: 0.85; }

/* ── Stats ── */
.cm-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 1.5rem;
}
@media (max-width: 900px) { .cm-stats { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 500px) { .cm-stats { grid-template-columns: 1fr; } }

.cm-stat-card {
    background: var(--cm-card-bg);
    border: 0.5px solid var(--cm-card-border);
    border-radius: 12px;
    padding: 1rem 1.125rem;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: var(--cm-shadow-sm);
}

.cm-stat-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.cm-icon-teal  { background: var(--cm-accent-teal-light); color: var(--cm-accent-teal); }
.cm-icon-blue  { background: var(--cm-accent-blue-light); color: var(--cm-accent-blue); }

.cm-stat-label {
    font-size: 0.6875rem;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    margin-bottom: 2px;
}
.cm-stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--cm-text-primary);
}

/* ── Badges ── */
.cm-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.625rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.cm-badge-teal { background: #ccfbf1; color: #0f766e; }
.cm-badge-red { background: #fee2e2; color: #b91c1c; }

/* ── Table Card ── */
.cm-table-card {
    background: var(--cm-card-bg);
    border: 0.5px solid var(--cm-card-border);
    border-radius: 12px;
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
    font-family: inherit;
}
.cm-search-input:focus {
    border-color: var(--cm-text-muted);
    box-shadow: 0 0 0 3px rgba(148,163,184,0.15);
}

/* ── Table ── */

.cm-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
.cm-table thead tr { border-bottom: 0.5px solid var(--cm-card-border); }
.cm-table th {
    padding: 10px 16px;
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--cm-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.07em;
    text-align: left;
    background: var(--cm-bg);
    white-space: nowrap;
}
.cm-th-center { text-align: center; }

.cm-tr { transition: background 0.1s; border-bottom: 0.5px solid var(--cm-card-border); }
.cm-tr:hover { background: var(--cm-bg); }
.cm-td {
    padding: 12px 16px;
    vertical-align: middle;
    color: var(--cm-text-primary);
}
.cm-table tbody tr:last-child .cm-td { border-bottom: none; }

.cm-cust-name {
    font-weight: 600;
    color: var(--cm-text-primary);
    text-decoration: none;
    transition: color 0.15s;
    display: block;
}
.cm-cust-meta {
    font-size: 0.75rem;
    color: var(--cm-text-muted);
    margin-top: 1px;
}

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
.cm-action-btn:hover        { border-color: var(--cm-text-secondary); color: var(--cm-text-primary); background: var(--cm-bg); }
.cm-action-btn--edit:hover  { border-color: #93c5fd; color: var(--cm-accent-blue); background: var(--cm-accent-blue-light); }
.cm-action-btn--danger:hover{ border-color: #fca5a5; color: #dc2626; background: rgba(220, 38, 38, 0.05); }

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
.cm-empty-title { font-size: 0.9375rem; font-weight: 700; color: var(--cm-text-primary); margin-bottom: 4px; }
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
</style>
@endpush
