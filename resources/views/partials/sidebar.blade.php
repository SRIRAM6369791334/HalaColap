@php
    $user = auth()->user();
    $activeTenant = app(\App\Services\Tenancy\TenantContext::class)->getTenant();
    $brandName = $activeTenant ? ($activeTenant->company_name ?: $activeTenant->name) : 'MSR BROILERS';

    $isTenantAdmin = (bool) ($user && method_exists($user, 'isTenantAdmin') && $user->isTenantAdmin());

    $canAccess = function(array $item) use ($user, $isTenantAdmin) {
        if (!empty($item['super_admin_only'])) {
            return (bool) ($user && ($user->is_super_admin ?? false));
        }
        if (!isset($item['permission'])) {
            return true;
        }
        // Tenant Admin (Company MD) has 100% full operational access to all business modules
        if ($isTenantAdmin) {
            return true;
        }
        return (bool) ($user && $user->can($item['permission']));
    };
    $routeExists = fn(string $routeName) => \Illuminate\Support\Facades\Route::has($routeName);

    $navItems = [
        ['label' => 'Dashboard', 'icon' => 'dashboard', 'route' => 'dashboard'],
        ['label' => 'Dayload Billing', 'icon' => 'description', 'route' => 'billing.day-load.index', 'permission' => 'view bills'],
        

        ['header' => 'Master Records'],
        ['label' => 'Customers', 'icon' => 'group', 'route' => 'masters.customers.index', 'permission' => 'view customers'],
        ['label' => 'Dealers', 'icon' => 'local_shipping', 'route' => 'masters.dealers.index', 'permission' => 'view dealers'],
        ['label' => 'Vendors', 'icon' => 'inventory_2', 'route' => 'masters.vendors.index', 'permission' => 'view vendors'],
        
        ['header' => 'Operations'],
        ['label' => 'Daily Billing Generate', 'icon' => 'receipt', 'route' => 'billing.daily.index', 'permission' => 'view bills'],
        ['label' => 'Weekly Billing Generate', 'icon' => 'receipt_long', 'route' => 'billing.weekly.index', 'permission' => 'view bills'],
        ['label' => 'Purchases', 'icon' => 'shopping_cart', 'route' => 'purchases.entry', 'permission' => 'view purchases'],
        ['label' => 'Purchase Invoices', 'icon' => 'receipt', 'route' => 'purchases.invoices', 'permission' => 'view purchases'],

        ['header' => 'Finance & Payments'],
        ['label' => 'Dealer Payments', 'icon' => 'payments', 'route' => 'payments.dealers.index', 'permission' => 'view payments'],
        ['label' => 'Vendor Payments', 'icon' => 'credit_card', 'route' => 'payments.vendors.index', 'permission' => 'view vendor payments'],
        ['label' => 'Cash & Bank Ledger', 'icon' => 'account_balance', 'route' => 'billing.cash-bank-ledger.index'],
        ['label' => 'Capital & Investments', 'icon' => 'savings', 'route' => 'billing.investments.index'],
        ['label' => 'Expenses', 'icon' => 'account_balance_wallet', 'route' => 'expenses.index', 'permission' => 'view expenses'],
        ['label' => 'EMI Records', 'icon' => 'notifications_active', 'route' => 'expenses.emis.index', 'permission' => 'view emis'],
        
        ['header' => 'Performance'],
        ['label' => 'Profit & Loss', 'icon' => 'show_chart', 'route' => 'profit.index', 'permission' => 'view profit dashboard'],
        ['label' => 'Reports', 'icon' => 'analytics', 'route' => 'reports.index', 'permission' => 'view reports'],

        ['header' => 'Administration', 'permission' => 'manage users'],
        ['label' => 'Business Accounts (SaaS)', 'icon' => 'domain', 'route' => 'admin.tenants.index', 'super_admin_only' => true],
        ['label' => 'User Management', 'icon' => 'manage_accounts', 'route' => 'admin.users.index', 'permission' => 'manage users'],
        ['label' => 'Role Management', 'icon' => 'admin_panel_settings', 'route' => 'admin.roles.index', 'permission' => 'manage roles'],
        ['label' => 'Permissions', 'icon' => 'vpn_key', 'route' => 'admin.permissions.index', 'permission' => 'manage roles'],
    ];

    $isActive = fn(string $routeName) => request()->routeIs($routeName);

    $visibleItems = [];
    $currentHeader = null;
    $hasLinksUnderHeader = false;

    foreach ($navItems as $item) {
        if (isset($item['header'])) {
            // Check if header itself has explicit permission restrictions
            if (isset($item['permission']) && !$canAccess($item)) {
                $currentHeader = null;
                continue;
            }
            $currentHeader = $item;
            $hasLinksUnderHeader = false;
        } else {
            if ($canAccess($item) && $routeExists($item['route'])) {
                if ($currentHeader && !$hasLinksUnderHeader) {
                    $visibleItems[] = $currentHeader;
                    $hasLinksUnderHeader = true;
                }
                $visibleItems[] = $item;
            }
        }
    }
@endphp

<aside id="sidebar" 
    x-cloak
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-zinc-200 bg-white/95 shadow-xl backdrop-blur-xl transition-transform duration-300 ease-out dark:border-zinc-800 dark:bg-zinc-950/95 lg:static lg:translate-x-0 lg:shadow-none">
    
    {{-- Branding Header --}}
    <div class="flex h-20 items-center gap-3 px-6 shrink-0">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-white shadow-sm dark:bg-emerald-500 dark:text-emerald-950">
            <span class="material-symbols-rounded text-[24px]">egg</span>
        </div>
        <div class="flex min-w-0 flex-col justify-center">
            <span class="text-xl font-cabinet font-bold tracking-tight text-zinc-900 dark:text-white leading-none truncate max-w-[150px]" title="{{ $brandName }}">{{ $brandName }}</span>
            <span class="mt-0.5 text-[10px] font-bold uppercase tracking-widest text-emerald-600 dark:text-emerald-400">Management</span>
        </div>
        <button class="ml-auto rounded-xl p-2 min-h-[44px] min-w-[44px] flex items-center justify-center text-zinc-400 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white lg:hidden focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                @click="sidebarOpen = false"
                aria-label="Close Sidebar">
            <span class="material-symbols-rounded text-xl">close</span>
        </button>
    </div>

    {{-- Navigation Links --}}
    <nav class="custom-scrollbar flex-1 space-y-1 overflow-y-auto px-4 py-6 font-outfit">
        @foreach($visibleItems as $item)
            @if(isset($item['header']))
                <h3 class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[0.15em] text-zinc-400 dark:text-zinc-500 first:mt-0 font-cabinet">
                    {{ $item['header'] }}
                </h3>
            @else
                <a href="{{ route($item['route']) }}"
                   class="group relative flex min-h-11 items-center gap-3 rounded-xl border px-3 py-2.5 transition-colors duration-200 {{ $isActive($item['route']) ? 'border-emerald-200 bg-emerald-50 text-zinc-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-white' : 'border-transparent text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-400 dark:hover:bg-zinc-900 dark:hover:text-white' }}">
                    <span class="material-symbols-rounded text-[20px] transition-colors duration-300 {{ $isActive($item['route']) ? 'text-emerald-500' : 'text-zinc-400 dark:text-zinc-500 group-hover:text-emerald-500' }}">
                        {{ $item['icon'] }}
                    </span>
                    <span class="truncate text-sm font-semibold tracking-tight">{{ $item['label'] }}</span>
                    @if($isActive($item['route']))
                        <span class="absolute left-0 top-1/2 -translate-y-1/2 h-6 w-1 rounded-r-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></span>
                    @endif
                </a>
            @endif
        @endforeach
    </nav>

    {{-- User Profile Footer --}}
    <div class="border-t border-zinc-200/60 dark:border-zinc-800/60 p-4 shrink-0 bg-zinc-50/50 dark:bg-zinc-900/30">
        <div class="group flex items-center gap-3 rounded-2xl p-2 transition-colors hover:bg-white dark:hover:bg-zinc-800 border border-transparent hover:border-zinc-200 dark:hover:border-zinc-700 hover:shadow-sm">
            <x-avatar name="{{ $user?->name ?? 'Admin' }}" size="md" color="bg-emerald-500" />
            
            <div class="flex min-w-0 flex-col font-outfit">
                <span class="truncate text-sm font-bold tracking-tight text-zinc-900 dark:text-white">{{ $user?->name ?? 'Admin' }}</span>
                <span class="truncate text-[10px] font-medium text-zinc-500 dark:text-zinc-400">{{ $user?->email ?? '' }}</span>
            </div>
            
            <form method="POST" action="{{ route('logout') }}" class="ml-auto shrink-0">
                @csrf
                <button type="submit" class="rounded-xl p-2 min-h-[44px] min-w-[44px] flex items-center justify-center text-zinc-400 dark:text-zinc-500 transition-colors hover:bg-rose-50 dark:hover:bg-rose-500/10 hover:text-rose-600 dark:hover:text-rose-400 focus:outline-none focus:ring-2 focus:ring-rose-500/20" title="Logout" aria-label="Logout">
                    <span class="material-symbols-rounded text-[20px]">logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>
