<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-2 border-b border-zinc-200 bg-white/95 px-3 backdrop-blur-xl transition-colors dark:border-zinc-800 dark:bg-zinc-950/95 sm:h-20 sm:px-6">
    <div class="flex min-w-0 items-center gap-4 w-full md:w-auto">
        <button class="rounded-xl p-2 min-h-[44px] min-w-[44px] flex items-center justify-center text-zinc-500 dark:text-zinc-400 transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-zinc-900 dark:hover:text-white lg:hidden focus:outline-none focus:ring-2 focus:ring-emerald-500/20"
                @click="sidebarOpen = true"
                aria-label="Open Sidebar">
            <span class="material-symbols-rounded">menu</span>
        </button>

        {{-- Global Search --}}
        <div class="hidden md:block w-72 lg:w-96">
            <x-search placeholder="Search records, invoices..." />
        </div>
    </div>

    <div class="flex items-center gap-2 sm:gap-4 shrink-0">
        <div class="flex items-center gap-1">
            {{-- Dark Mode Toggle --}}
            <button id="theme-toggle"
                type="button"
                class="flex min-h-11 min-w-11 items-center justify-center rounded-xl text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white"
                title="Toggle dark mode"
                aria-label="Toggle dark mode">
                <span class="material-symbols-rounded text-[22px]" aria-hidden="true">dark_mode</span>
            </button>

            {{-- Notification Bell Dropdown --}}
            <div 
                x-data="{ 
                    open: false,
                    count: 0,
                    notifications: [],
                    loading: false,
                    fetchNotifications() {
                        this.loading = true;
                        fetch('{{ route('notifications.index') }}')
                            .then(res => res.json())
                            .then(data => {
                                this.count = data.count || 0;
                                this.notifications = data.notifications || [];
                                this.loading = false;
                            });
                    },
                    markAsRead(id, url) {
                        fetch(`/notifications/${id}/read`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        }).then(() => {
                            window.location.href = url;
                        });
                    },
                    markAllAsRead() {
                        fetch('{{ route('notifications.readAll') }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        }).then(() => {
                            this.count = 0;
                            this.notifications = [];
                            this.open = false;
                        });
                    }
                }"
                x-init="fetchNotifications(); setInterval(() => fetchNotifications(), 60000)"
                class="relative"
            >
                <button type="button" @click="open = !open" @click.away="open = false" :aria-expanded="open.toString()" aria-label="Notifications" class="relative flex min-h-11 min-w-11 items-center justify-center rounded-xl text-zinc-600 transition-colors hover:bg-zinc-100 hover:text-zinc-950 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-white" title="Notifications">
                    <span class="material-symbols-rounded text-[22px]">notifications</span>
                    <span x-show="count > 0" class="absolute right-2 top-2 h-4 w-4 rounded-full border-2 border-white dark:border-zinc-950 bg-rose-500 text-[9px] font-bold text-white flex items-center justify-center" x-text="count > 9 ? '9+' : count" x-cloak></span>
                </button>

                {{-- Dropdown Panel --}}
                <div x-show="open"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95 -translate-y-2"
                     x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                     x-transition:leave-end="opacity-0 scale-95 -translate-y-2"
                     class="absolute right-0 mt-2 w-80 rounded-2xl bg-white/90 dark:bg-zinc-900/90 backdrop-blur-xl border border-zinc-200/60 dark:border-zinc-800/60 shadow-[0_8px_32px_rgba(0,0,0,0.08)] overflow-hidden z-50"
                     x-cloak>
                    
                    <div class="flex items-center justify-between p-4 border-b border-zinc-100 dark:border-zinc-800">
                        <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 tracking-tight">Recent Activity</h3>
                        <button x-show="count > 0" @click="markAllAsRead" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700 dark:text-emerald-500 transition-colors">Mark all read</button>
                    </div>

                    <div class="max-h-80 overflow-y-auto">
                        <div x-show="loading" class="p-8 text-center text-zinc-400">
                            <span class="material-symbols-rounded animate-spin">refresh</span>
                        </div>
                        
                        <div x-show="!loading && notifications.length === 0" class="p-8 text-center flex flex-col items-center justify-center">
                            <div class="h-12 w-12 rounded-full bg-zinc-50 dark:bg-zinc-800/50 flex items-center justify-center text-zinc-400 mb-3">
                                <span class="material-symbols-rounded">notifications_paused</span>
                            </div>
                            <p class="text-sm text-zinc-500 font-medium tracking-tight">You're all caught up!</p>
                            <p class="text-xs text-zinc-400 mt-1">No new activities to show.</p>
                        </div>

                        <template x-for="notif in notifications" :key="notif.id">
                            <button @click="markAsRead(notif.id, notif.data.url)" class="w-full text-left p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition-colors border-b border-zinc-50 dark:border-zinc-800/50 last:border-0 flex items-start gap-3">
                                <div class="mt-0.5 shrink-0 h-8 w-8 rounded-full flex items-center justify-center text-white shadow-sm"
                                     :class="{
                                         'bg-emerald-500': notif.data.color === 'emerald',
                                         'bg-blue-500': notif.data.color === 'blue',
                                         'bg-amber-500': notif.data.color === 'amber',
                                         'bg-orange-500': notif.data.color === 'orange',
                                         'bg-violet-500': notif.data.color === 'violet',
                                         'bg-purple-500': notif.data.color === 'purple'
                                     }">
                                    <span class="material-symbols-rounded text-[16px]" x-text="notif.data.icon"></span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100 truncate" x-text="notif.data.title"></p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-2" x-text="notif.data.message"></p>
                                    <p class="text-[10px] text-zinc-400 font-medium tracking-wide uppercase mt-1.5" x-text="notif.created_at"></p>
                                </div>
                            </button>
                        </template>
                    </div>
                    
                    <div class="p-2 border-t border-zinc-100 dark:border-zinc-800 bg-zinc-50/50 dark:bg-zinc-900/50 text-center">
                        <a href="{{ route('dashboard.alerts') }}" class="text-xs font-semibold text-zinc-500 hover:text-zinc-900 dark:hover:text-zinc-300 transition-colors">View Alert Center</a>
                    </div>
                </div>
            </div>
            
         
        </div>

        {{-- SaaS Multi-Tenant Switcher --}}
        @php
            $currentTenant = app(\App\Services\Tenancy\TenantContext::class)->getTenant();
            $authUser = Auth::user();
            $accessibleTenants = ($authUser && ($authUser->is_super_admin ?? false))
                ? \App\Models\Tenant::where('is_active', true)->orderBy('name')->get()
                : ($authUser ? $authUser->tenants()->where('tenants.is_active', true)->orderBy('name')->get() : collect());
        @endphp

        @if($currentTenant)
            <div x-data="{ openTenantDropdown: false }" class="relative">
                @if($authUser && (($authUser->is_super_admin ?? false) || $accessibleTenants->count() > 1))
                    <button @click="openTenantDropdown = !openTenantDropdown" @click.away="openTenantDropdown = false"
                            class="flex min-h-11 max-w-[170px] items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-900 transition-colors hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-100 dark:hover:bg-emerald-900/50 sm:max-w-none">
                        <span class="material-symbols-rounded text-sm text-emerald-600">domain</span>
                        <span class="max-w-[130px] sm:max-w-[180px] truncate">{{ $currentTenant->name }}</span>
                        @if($authUser->is_super_admin ?? false)
                            <span class="px-1.5 py-0.5 text-[9px] bg-emerald-600 text-white rounded font-bold uppercase tracking-wider">Super</span>
                        @endif
                        <span class="material-symbols-rounded text-xs text-zinc-400">expand_more</span>
                    </button>

                    <div x-show="openTenantDropdown"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 -translate-y-1"
                         class="absolute right-0 mt-2 w-64 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-xl overflow-hidden z-50 py-1"
                         x-cloak>
                        <div class="px-3 py-2 border-b border-zinc-100 dark:border-zinc-800 text-[11px] font-bold text-zinc-400 uppercase tracking-wider flex items-center justify-between">
                            <span>Switch Business</span>
                            @if($authUser->is_super_admin ?? false)
                                <a href="{{ route('admin.tenants.index') }}" class="text-emerald-600 hover:underline">Manage All</a>
                            @endif
                        </div>
                        <div class="max-h-60 overflow-y-auto py-1">
                            @foreach($accessibleTenants as $t)
                                <form action="{{ route('tenant.switch') }}" method="POST" class="m-0">
                                    @csrf
                                    <input type="hidden" name="tenant_id" value="{{ $t->id }}">
                                    <button type="submit"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors {{ $currentTenant->id === $t->id ? 'font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50/40 dark:bg-emerald-950/20' : 'text-zinc-700 dark:text-zinc-300' }}">
                                        <span class="truncate">{{ $t->name }}</span>
                                        @if($currentTenant->id === $t->id)
                                            <span class="material-symbols-rounded text-sm">check</span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="flex min-h-11 max-w-[150px] items-center gap-1.5 rounded-xl border border-zinc-300 bg-zinc-50 px-3 py-1.5 text-xs font-semibold text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300">
                        <span class="material-symbols-rounded text-sm text-zinc-500">domain</span>
                        <span class="max-w-[150px] truncate">{{ $currentTenant->name }}</span>
                    </div>
                @endif
            </div>
        @endif

        <div class="hidden h-6 w-px bg-zinc-200 dark:bg-zinc-800 sm:block"></div>

        {{-- User Profile --}}
        <div class="group hidden min-h-11 cursor-default items-center gap-3 rounded-xl p-1 pl-2 pr-3 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/50 sm:flex">
            <div class="hidden text-right sm:block font-outfit">
                <p class="text-sm font-bold leading-none tracking-tight text-zinc-900 dark:text-white">{{ Auth::user()->name ?? 'Admin' }}</p>
                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-zinc-500 dark:text-zinc-400">
                    {{ (Auth::user()?->is_super_admin ?? false) ? 'Platform Super Admin' : 'Business Admin' }}
                </p>
            </div>
            <x-avatar name="{{ Auth::user()->name ?? 'Admin' }}" size="md" class="group-hover:scale-105 transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)]" />
        </div>
    </div>
</header>
