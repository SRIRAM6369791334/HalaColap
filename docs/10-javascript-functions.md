# JavaScript Analysis — Flockwise BizTrack / MSR BROILERS

This document describes the JavaScript used across the application. The front-end is a server-rendered Blade +  Alpine.js + TailwindCSS stack, with a small amount of custom JavaScript compiled via Vite (`resources/js/app.js` and `resources/js/bootstrap.js`), plus inline scripts in the main layout.

---

## 1. app.js — Application Bootstrap & UI Logic

> Source: `resources/js/app.js` (ES module, bundled by Vite, loaded via `@vite(['resources/js/app.js'])`)

### 1. `applyTheme(theme)` — Dark / Light mode switcher

```js
const applyTheme = (theme) => {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.dataset.theme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.dataset.theme = 'light';
    }
    localStorage.setItem('poultrypro-theme', theme);

    const icon = document.querySelector('#theme-toggle .material-symbols-rounded');
    if (icon) {
        icon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    }
};
```

- Adds/removes the `dark` class on `<html>` (Tailwind `darkMode: 'class'`).
- Keeps `data-theme` for backward compatibility.
- Persists the choice in `localStorage` under key `poultrypro-theme`.
- Swaps the theme toggle icon (`dark_mode` ↔ `light_mode`).

On load, the saved theme (default `light`) is applied immediately (`applyTheme(localStorage.getItem('poultrypro-theme') || 'light')`) to avoid theme flash.

### 2. Theme toggle click handler

```js
document.getElementById('theme-toggle')?.addEventListener('click', () => {
    const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    applyTheme(current === 'dark' ? 'light' : 'dark');
});
```

Located in `app.js`, this listens on `#theme-toggle` (in the topbar) and toggles between light and dark.

### 3. Form submit confirmation (SweetAlert2)

A **capturing** `submit` listener (`true` as third argument) intercepts any form whose inline `onsubmit` attribute contains a native `confirm(...)` call, replacing it with a SweetAlert2 dialog.

```js
document.addEventListener('submit', (event) => {
    const form = event.target;
    const onsubmitAttr = form.getAttribute('onsubmit');
    if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
        event.preventDefault();
        event.stopPropagation();

        let message = "Are you sure you want to perform this action?";
        const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
        if (match && match[1]) message = match[1];

        Swal.fire({
            title: 'Confirmation',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            cancelButtonColor: '#ef4444',
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        }).then((result) => {
            if (result.isConfirmed) {
                form.removeAttribute('onsubmit'); // avoid re-triggering
                form.submit();
            }
        });
        return;
    }
}, true);
```

- Extracts the human-readable message from `confirm('...')`.
- On confirmation, strips the `onsubmit` attribute (so the native handler doesn't also fire) and submits.
- Dialog colors adapt to the current light/dark theme.

### 4. `Alpine.data('ajaxTabs')` — AJAX tab navigation

Registered in an `alpine:init` listener. Provides tab-style AJAX navigation with prefetching and client-side caching.

```js
Alpine.data('ajaxTabs', () => ({
    cache: {},
    prefetchTab(e) { ... },
    handleTabClick(e) { ... },
}));
```

#### `prefetchTab(e)` — Pre-fetch on hover

Fired on hover over a tab link (`x-on:mouseover`). If the link isn't already cached and isn't a `_blank` / `download` / `pdf` link:
1. `fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })`.
2. Parses the response into a DOM.
3. Finds the element with the same `id` as the current tab container (`this.$el.id`).
4. Stores `newEl.innerHTML` in `this.cache[link.href]`.

#### `handleTabClick(e)` — Load on click

- Skips `_blank` / `download` / `pdf` links (let the browser handle them).
- Prevents default navigation.
- **Cached:** injects cached HTML and `history.pushState` to the new URL — instant, no request.
- **Not cached:** dims the tab container (`opacity: 0.5`, `pointer-events: none`), fetches, replaces `innerHTML`, caches, and `pushState` the URL.
- On parse failure or network error, falls back to full `window.location.href = link.href`.
- Restores opacity/pointer-events in `finally`.

Backed by the layout pattern `@extends(request()->ajax() ? 'layouts.empty' : 'layouts.app')` so AJAX calls get the minimal (empty) layout, not the full app shell.

### 5. Double-submit prevention (in layout)

> Defined in `resources/views/layouts/app.blade.php` inline script, not in `app.js`.

On `DOMContentLoaded`, every non-GET form gets a `submit` listener that:
- Skips if `e.defaultPrevented` (e.g. a SweetAlert confirmation cancelled it) or the form fails HTML5 validation.
- Disables all submit buttons (`btn.disabled = true`, adds `opacity-75` / `cursor-not-allowed`) after a short `setTimeout` so form data is captured first.
- Replaces button content with a spinning "Processing..." indicator (unless `data-custom-loading` is set), storing the original content in `data-original-content`.

```js
window.resetSubmitButtons = function(form) {
    if (!form) return;
    const submitBtns = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    submitBtns.forEach(btn => {
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        if (btn.hasAttribute('data-original-content')) {
            btn.innerHTML = btn.getAttribute('data-original-content');
            btn.removeAttribute('data-original-content');
        }
    });
};
```

`window.resetSubmitButtons(form)` is a global helper that re-enables submit buttons and restores original content — used when a submission is cancelled by a dialog.

### 6. Mouse wheel blur

Prevents the browser default behaviour of changing `<input type="number">` values when the user scrolls over them while focused. On `wheel`, if the focused element is a number input, it is blurred.

```js
document.addEventListener('wheel', function(e) {
    if (document.activeElement && document.activeElement.type === 'number') {
        document.activeElement.blur();
    }
}, { passive: true });
```

---

## 2. bootstrap.js — HTTP client setup

> Source: `resources/js/bootstrap.js`

```js
import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
```

- Imports Axios and exposes it globally as `window.axios`.
- Sets the `X-Requested-With: XMLHttpRequest` header on every request by default (used by the app and by Laravel to detect AJAX requests — e.g. the empty-layout branch for tabs).

---

## 3. Inline JavaScript in `layouts/app.blade.php`

Beyond the double-submit handler and mouse-wheel blur described above, the layout also contains:

### Flash messages via SweetAlert2

```js
@if(session()->has('success'))
    Swal.fire({ icon: 'success', title: 'Success!', text: @json(session('success')), ... timer: 4000, timerProgressBar: true, ... });
@endif

@if(session()->has('error'))
    Swal.fire({ icon: 'error', title: 'Error!', text: @json(session('error')), ... });
@endif
```

- `session('success')` → green success dialog (auto-closes after 4s with a progress bar).
- `session('error')` → red error dialog.
- Both use `@json(...)` to safely inject PHP session values into JS, and adapt background/color to the active theme.

---

## 4. Alpine.js Patterns Used Across Views

Alpine.js is loaded from CDN (`alpinejs@3.x.x`) and used for most client-side interactivity. Recurring patterns:

### Modal open / close (event dispatch)

The `<x-modal>` component (a Blade component) listens for window events and shows/hides itself:

```html
<div x-data="{ show: false }"
     x-on:open-modal.window="$event.detail == 'NAME' ? show = true : null"
     x-on:close-modal.window="$event.detail == 'NAME' ? show = false : null">
```

- **Open:** `$dispatch('open-modal', 'NAME')` or `window.dispatchEvent(new CustomEvent('open-modal', { detail: 'NAME' }))`.
- **Close:** `$dispatch('close-modal', 'NAME')`.
- Component elements (open buttons) wrap this in `x-data` + `x-on:click="$dispatch('open-modal', 'NAME')"`.

### Two-way data binding

```html
<input x-model="field" ...>
```

`x-model` binds form inputs to Alpine component state.

### Conditional rendering

- `x-show="condition"` — toggles `display` (element stays in DOM).
- `x-if="condition"` — conditionally creates/destroys the element (requires `<template>`).

### List iteration

```html
<template x-for="item in items" :key="item.id">
    ...
</template>
```

`x-for` renders collections; combined with `:key` for stable re-rendering.

### Component state

```html
<div x-data="{ count: 0, items: [], tx: null }">
```

`x-data` declares a component's reactive state object (commonly inlined on the root element of each widget/table).

### Event handling

- `x-on:change="..."` — fires when an input/select changes (often used to recompute totals).
- `x-on:submit="..."` — intercepts form submission (e.g. for AJAX-less client-side sends).
- `x-on:click`, `x-on:mouseover` — interactions (used by `ajaxTabs` prefetch/click handlers).

---

## Quick Reference

| Concern | Where | Mechanism |
|---------|-------|-----------|
| Theme switching | `app.js` | `applyTheme()` + `localStorage` + `#theme-toggle` click |
| Native `confirm()` upgrade | `app.js` | Capturing `submit` listener → SweetAlert2 |
| AJAX tab navigation | `app.js` | `Alpine.data('ajaxTabs')` (`prefetchTab`, `handleTabClick`) + cache |
| Double-submit guard | `layout` | Disable submit buttons on POST/PUT/DELETE submit |
| Submit reset helper | `layout` | `window.resetSubmitButtons(form)` |
| Wheel on number inputs | `layout` | Blur focused number input on `wheel` |
| Flash messages | `layout` | SweetAlert2 on `session('success')` / `session('error')` |
| HTTP client | `bootstrap.js` | Axios + `X-Requested-With` header |
| Modals | views + `<x-modal>` | `$dispatch('open-modal', ...)` / `close-modal` |
