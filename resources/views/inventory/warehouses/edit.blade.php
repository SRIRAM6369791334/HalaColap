@extends('layouts.app')
@section('title', 'Edit Warehouse')

@section('content')

<div class="cm-page">

    {{-- Top Bar --}}
    <div class="cm-topbar">
        <div>
            <a href="{{ route('inventory.warehouses.index') }}" class="cm-route" style="display:inline-block; margin-bottom: 5px;">← Back to Warehouses</a>
            <h1 class="cm-page-title">Edit Warehouse Details</h1>
            <p class="cm-page-sub">Update credentials for {{ $warehouse->name }}</p>
        </div>
    </div>

    <div class="cm-table-card" style="max-width: 600px;">
        <form action="{{ route('inventory.warehouses.update', $warehouse->id) }}" method="POST" style="padding: 1.5rem;">
            @csrf
            @method('PUT')
            
            <div class="cm-form-group">
                <label class="cm-form-label">Warehouse Name <span class="cm-required">*</span></label>
                <input type="text" name="name" required value="{{ old('name', $warehouse->name) }}"
                       class="cm-form-input">
                @error('name') <p style="color: #dc2626; font-size: 0.75rem; margin-top: 4px;">{{ $message }}</p> @enderror
            </div>

            <div class="cm-form-group">
                <label class="cm-form-label">Address / Location</label>
                <textarea name="location" rows="3"
                          class="cm-form-input" style="resize: vertical; min-height: 64px;">{{ old('location', $warehouse->location) }}</textarea>
            </div>

            <div style="margin-top: 1.5rem; padding: 1rem; background: var(--cm-bg); border-radius: 8px; border: 0.5px dashed var(--cm-card-border);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.8125rem; font-weight: 600; color: var(--cm-text-primary);">Is Location Active?</span>
                    <input type="checkbox" name="is_active" value="1" {{ $warehouse->is_active ? 'checked' : '' }} style="width: 18px; height: 18px;">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 2rem; padding-top: 1rem; border-top: 0.5px solid var(--cm-card-border);">
                <a href="{{ route('inventory.warehouses.index') }}" class="cm-btn-ghost">Cancel</a>
                <button type="submit" class="cm-btn-primary cm-btn-primary--blue">Save Changes</button>
            </div>
        </form>
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
    --cm-accent-blue: #2563eb;
    --cm-shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

[data-theme='dark'] {
    --cm-bg: #090d16;
    --cm-card-bg: #111827;
    --cm-card-border: #1f2937;
    --cm-text-primary: #f3f4f6;
    --cm-text-secondary: #9ca3af;
    --cm-text-muted: #6b7280;
}

*, *::before, *::after { box-sizing: border-box; }

.cm-page { padding: 2rem 0 3rem; }
.cm-topbar { margin-bottom: 1.5rem; }
.cm-page-title { font-size: 1.375rem; font-weight: 700; color: var(--cm-text-primary); letter-spacing: -0.02em; }
.cm-page-sub { font-size: 0.8125rem; color: var(--cm-text-secondary); margin-top: 2px; }
.cm-route { font-size: 0.75rem; color: var(--cm-accent-blue); text-decoration: none; font-weight: 600; text-transform: uppercase; }

.cm-table-card {
    background: var(--cm-card-bg);
    border: 0.5px solid var(--cm-card-border);
    border-radius: 12px;
    box-shadow: var(--cm-shadow-sm);
}

.cm-form-group { margin-bottom: 12px; }
.cm-form-label {
    display: block; font-size: 0.6875rem; font-weight: 600; color: var(--cm-text-secondary);
    text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 5px;
}
.cm-required { color: #dc2626; }
.cm-form-input {
    width: 100%; padding: 8px 10px; border: 0.5px solid var(--cm-card-border);
    border-radius: 8px; font-size: 0.8125rem; background: var(--cm-bg); color: var(--cm-text-primary);
    outline: none; transition: border-color 0.15s; font-family: inherit;
}
.cm-form-input:focus { border-color: var(--cm-text-muted); }

.cm-btn-primary {
    display: inline-flex; align-items: center; padding: 8px 16px; background: var(--cm-text-primary);
    color: var(--cm-card-bg); border: none; border-radius: 8px; font-size: 0.8125rem; font-weight: 600;
    cursor: pointer; transition: opacity 0.15s; text-decoration: none;
}
.cm-btn-primary:hover { opacity: 0.85; }
.cm-btn-primary--blue { background: var(--cm-accent-blue); }
.cm-btn-ghost {
    padding: 8px 14px; background: transparent; border: none; border-radius: 8px; font-size: 0.8125rem;
    color: var(--cm-text-secondary); cursor: pointer; text-decoration: none;
}
.cm-btn-ghost:hover { background: var(--cm-bg); color: var(--cm-text-primary); }
</style>
@endpush
