import Alpine from 'alpinejs';

window.Alpine = Alpine;

// Most page visits do not show a dialog, so load SweetAlert only on demand.
const lazySwal = {
    fire: (...args) => window.loadSweetAlert().then((Swal) => Swal.fire(...args)),
    showLoading: (...args) => window.loadSweetAlert().then((Swal) => Swal.showLoading(...args)),
};
let sweetAlertPromise;

window.Swal = lazySwal;
window.loadSweetAlert = () => {
    if (window.Swal !== lazySwal) {
        return Promise.resolve(window.Swal);
    }

    if (!sweetAlertPromise) {
        sweetAlertPromise = new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
            script.async = true;
            script.onload = () => resolve(window.Swal);
            script.onerror = () => {
                sweetAlertPromise = undefined;
                reject(new Error('Unable to load the confirmation dialog.'));
            };
            document.head.appendChild(script);
        });
    }

    return sweetAlertPromise;
};

const applyTheme = (theme) => {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
        document.documentElement.dataset.theme = 'dark'; // Keep data-theme for backward compatibility if needed
    } else {
        document.documentElement.classList.remove('dark');
        document.documentElement.dataset.theme = 'light';
    }
    try { localStorage.setItem('poultrypro-theme', theme); } catch (_) {}

    const icon = document.querySelector('#theme-toggle .material-symbols-rounded');
    if (icon) {
        icon.textContent = theme === 'dark' ? 'light_mode' : 'dark_mode';
    }
};

const initialTheme = document.documentElement.dataset.theme || 'light';
applyTheme(initialTheme);

document.addEventListener('DOMContentLoaded', () => {
    Alpine.start();
    document.getElementById('theme-toggle')?.addEventListener('click', () => {
        const current = document.documentElement.classList.contains('dark') ? 'dark' : 'light';
        applyTheme(current === 'dark' ? 'light' : 'dark');
    });
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    // Intercept native confirm() handlers and replace with SweetAlert2
    const onsubmitAttr = form.getAttribute('onsubmit');
    if (onsubmitAttr && onsubmitAttr.includes('confirm(')) {
        event.preventDefault();
        event.stopPropagation();

        // Extract the message from confirm('...')
        let message = "Are you sure you want to perform this action?";
        const match = onsubmitAttr.match(/confirm\(['"](.*?)['"]\)/);
        if (match && match[1]) {
            message = match[1];
        }

        // Show SweetAlert2
        Swal.fire({
            title: 'Confirmation',
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#10b981', // emerald-600
            cancelButtonColor: '#ef4444',  // red-500
            confirmButtonText: 'Yes, proceed',
            cancelButtonText: 'Cancel',
            background: document.documentElement.classList.contains('dark') ? '#1e293b' : '#ffffff',
            color: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#0f172a',
        }).then((result) => {
            if (result.isConfirmed) {
                // Temporarily strip the attribute to avoid re-triggering and submit form
                form.removeAttribute('onsubmit');
                form.submit();
            }
        });
        return;
    }

}, true);

document.addEventListener('alpine:init', () => {
    Alpine.data('ajaxTabs', () => ({
        cache: {},
        
        prefetchTab(e) {
            let link = e.target.closest('a');
            if (!link || !link.href || link.parentElement !== this.$el.firstElementChild || this.cache[link.href]) return;
            if (link.target === '_blank' || link.hasAttribute('download') || link.href.includes('pdf')) return;
            
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    let doc = new DOMParser().parseFromString(html, 'text/html');
                    let newEl = doc.getElementById(this.$el.id);
                    if (newEl) {
                        this.cache[link.href] = newEl.innerHTML;
                    }
                }).catch(() => {});
        },

        handleTabClick(e) {
            let link = e.target.closest('a');
            if (!link || !link.href || link.parentElement !== this.$el.firstElementChild) return;
            if (e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
            
            if (link.target === '_blank' || link.hasAttribute('download') || link.href.includes('pdf')) return;
            
            e.preventDefault();
            
            if (this.cache[link.href]) {
                this.$el.innerHTML = this.cache[link.href];
                window.history.pushState({}, '', link.href);
                return;
            }
            
            this.$el.setAttribute('aria-busy', 'true');
            this.$el.style.pointerEvents = 'none';
            
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(res => res.text())
                .then(html => {
                    let doc = new DOMParser().parseFromString(html, 'text/html');
                    let newEl = doc.getElementById(this.$el.id);
                    if (newEl) {
                        this.cache[link.href] = newEl.innerHTML;
                        this.$el.innerHTML = newEl.innerHTML;
                        window.history.pushState({}, '', link.href);
                    } else {
                        window.location.href = link.href;
                    }
                })
                .catch(() => {
                    window.location.href = link.href;
                })
                .finally(() => {
                    this.$el.removeAttribute('aria-busy');
                    this.$el.style.pointerEvents = 'auto';
                });
        }
    }));
});
