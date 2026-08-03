<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Dashboard Madame Sophie</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="icon" type="image/jpeg" href="{{ asset('favicon.jpg') }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-50">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-900 text-white flex-shrink-0">
            <div class="p-6">
                <h1 class="text-2xl font-bold flex items-center gap-3">
                    <img src="{{ asset('favicon.jpg') }}" alt="Logo" class="w-8 h-8 rounded-lg object-cover">
                    <span class="text-white">Bisou Bisou</span>
                </h1>
                <p class="text-xs text-slate-400 mt-1 uppercase tracking-wider font-semibold">Dashboard Chatbot</p>
            </div>

            <nav class="mt-6 px-4 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-chart-pie w-5"></i>
                    <span>Vue d'ensemble</span>
                </a>
                @canany(['conversations.view_all', 'conversations.view_assigned'])
                <a href="{{ route('admin.conversations') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.conversations*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-comments w-5"></i>
                    <span>Conversations</span>
                </a>
                @endcanany
                @can('products.manage')
                <a href="{{ route('admin.accounts') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.accounts*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-university w-5"></i>
                    <span>Comptes & Épargne</span>
                </a>
                <a href="{{ route('admin.credits') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.credits*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-hand-holding-usd w-5"></i>
                    <span>Crédits & Prêts</span>
                </a>
                @endcan
                @canany(['tickets.view_all', 'tickets.view_assigned'])
                <a href="{{ route('admin.tickets.index') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.tickets*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-ticket-alt w-5"></i>
                    <span>Tickets & Plaintes</span>
                </a>
                @endcanany
                @can('logs.view')
                <a href="{{ route('admin.logs') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.logs*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-list-ul w-5"></i>
                    <span>Logs Webhook</span>
                </a>
                @endcan
                @can('users.view')
                <a href="{{ route('admin.users') }}"
                    class="flex items-center gap-3 px-4 py-3 rounded-lg {{ request()->routeIs('admin.users*') ? 'bg-blue-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                    <i class="fas fa-users w-5"></i>
                    <span>Utilisateurs</span>
                </a>
                @endcan

                {{-- ═══ CRM Natif ═══ --}}
                @canany(['crm.contacts.view','crm.campaigns.manage','crm.reports.view','crm.alerts.view'])
                <div class="pt-3 mt-1">
                    <p class="px-4 text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1">CRM</p>

                    @can('crm.contacts.view')
                    <a href="{{ route('admin.crm.contacts.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.crm.contacts*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fas fa-address-book w-5"></i>
                        <span>Contacts</span>
                    </a>
                    @endcan

                    @can('crm.campaigns.manage')
                    <a href="{{ route('admin.crm.campaigns.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.crm.campaigns*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fas fa-paper-plane w-5"></i>
                        <span>Campagnes</span>
                    </a>
                    @endcan

                    @can('crm.reports.view')
                    <a href="{{ route('admin.crm.reports.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.crm.reports*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fas fa-chart-pie w-5"></i>
                        <span>Rapports</span>
                    </a>
                    @endcan

                    @can('crm.alerts.view')
                    <a href="{{ route('admin.crm.alerts.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.crm.alerts*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fas fa-bell w-5"></i>
                        <span>Alertes</span>
                        <span id="crm-alert-badge"
                              class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full hidden">0</span>
                    </a>
                    @endcan

                    @can('crm.tags.manage')
                    <a href="{{ route('admin.crm.tags.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.crm.tags*') ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800' }}">
                        <i class="fas fa-tags w-5"></i>
                        <span>Tags</span>
                    </a>
                    @endcan
                </div>
                @endcanany
            </nav>

            <div class="absolute bottom-0 w-64 p-6 border-t border-slate-800 bg-slate-900">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center font-bold text-white">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-slate-400">Connecté</p>
                        </div>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-red-400 transition-colors"
                            title="Déconnexion">
                            <i class="fas fa-power-off"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
            <!-- Top Header -->
            <header class="bg-white border-b border-gray-200 h-16 flex items-center justify-between px-8">
                <h2 class="text-lg font-semibold text-gray-800">@yield('page_title')</h2>
                <div class="flex items-center gap-4">
                    @can('crm.alerts.view')
                    <a href="{{ route('admin.crm.alerts.index') }}" class="relative p-2 text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-bell"></i>
                        <span id="header-alert-badge"
                              class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full hidden"></span>
                    </a>
                    @else
                    <button class="p-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-bell"></i>
                    </button>
                    @endcan
                    <div class="h-8 w-px bg-gray-200"></div>
                    <a href="/" class="text-sm text-blue-600 hover:underline flex items-center gap-1">
                        <i class="fas fa-external-link-alt text-xs"></i>
                        Voir le site
                    </a>
                </div>
            </header>

            <!-- Content Area -->
            <div class="flex-1 overflow-y-auto p-8">
                @yield('content')
            </div>
        </main>
    </div>

@can('crm.alerts.view')
<script>
(function pollAlerts() {
    const badge     = document.getElementById('crm-alert-badge');
    const hdrBadge  = document.getElementById('header-alert-badge');
    async function refresh() {
        try {
            const r = await fetch('{{ route('admin.crm.alerts.unread-count') }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            });
            const { count } = await r.json();
            [badge, hdrBadge].forEach(el => {
                if (!el) return;
                el.textContent = count;
                el.classList.toggle('hidden', count === 0);
            });
        } catch(e) {}
    }
    refresh();
    setInterval(refresh, 60000); // Toutes les 60 secondes
})();
</script>
@endcan

@stack('scripts')
</body>

</html>