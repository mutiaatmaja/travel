<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? config('app.name') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                    },
                },
            },
        };
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @livewireStyles
</head>

<body class="min-h-screen bg-white font-sans text-slate-800 antialiased">
    @props([
        'title' => $title ?? 'Dashboard',
        'section' => $section ?? 'Overview',
    ])

    <div x-data="{ sidebarOpen: false, masterOpen: true }" class="min-h-screen bg-slate-50">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden">
        </div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
            <div class="flex h-20 items-center justify-between border-b border-slate-100 px-6">
                <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-500 text-white"><svg
                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 12h18M3 12l4-4M3 12l4 4M21 12l-4-4M21 12l-4 4" />
                        </svg></span>
                    <span class="text-lg font-extrabold tracking-tight text-slate-900">Trans<span
                            class="text-brand-500">Go</span></span>
                </a>
                <button type="button" @click="sidebarOpen = false"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-50 lg:hidden" aria-label="Tutup sidebar"><svg
                        class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg></button>
            </div>

            <nav class="flex-1 overflow-y-auto px-4 py-6">
                <p class="px-3 text-[11px] font-bold uppercase tracking-widest text-slate-400">Menu utama</p>
                <div class="mt-3 space-y-1">
                    <a href="{{ route('dashboard') }}" wire:navigate
                        class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-brand-50 hover:text-brand-600"><svg
                            class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect width="7" height="7" x="3" y="3" rx="1" />
                            <rect width="7" height="7" x="14" y="3" rx="1" />
                            <rect width="7" height="7" x="14" y="14" rx="1" />
                            <rect width="7" height="7" x="3" y="14" rx="1" />
                        </svg>Dashboard</a>
                    <button type="button" @click="masterOpen = !masterOpen"
                        class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-600"><span
                            class="flex items-center gap-3"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2">
                                <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
                            </svg>Master Data</span><svg class="h-4 w-4 transition-transform"
                            :class="masterOpen ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="m6 9 6 6 6-6" />
                        </svg></button>
                    <div x-show="masterOpen" class="ml-5 border-l border-slate-200 pl-4">
                        @foreach ([['label' => 'Users', 'href' => route('users')], ['label' => 'Role & Permission', 'href' => route('roles-permissions')], ['label' => 'Wilayah', 'href' => route('cities')], ['label' => 'Outlet', 'href' => route('outlets')], ['label' => 'Armada', 'href' => route('vehicles')], ['label' => 'Supir', 'href' => route('drivers')], ['label' => 'Rute', 'href' => route('routes')], ['label' => 'Jadwal', 'href' => route('trips')]] as $menu)
                            <a href="{{ $menu['href'] }}" wire:navigate
                                class="block rounded-lg px-3 py-2 text-sm {{ $menu['label'] === $title ? 'bg-brand-50 font-bold text-brand-700' : 'text-slate-500 hover:bg-brand-50 hover:text-brand-600' }}">{{ $menu['label'] }}</a>
                        @endforeach
                    </div>
                    @foreach (['Booking', 'Laporan', 'Pengaturan'] as $menu)
                        <a href="#"
                            class="flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold text-slate-600 hover:bg-slate-50 hover:text-brand-600"><span
                                class="flex h-5 w-5 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">{{ substr($menu, 0, 1) }}</span>{{ $menu }}</a>
                    @endforeach
                </div>
            </nav>

            <div class="border-t border-slate-100 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3"><img
                        src="https://i.pravatar.cc/80?img=12" alt="Avatar {{ auth()->user()->name }}"
                        class="h-9 w-9 rounded-full object-cover">
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-slate-500">{{ auth()->user()->email }}</p>
                    </div><button type="button" wire:click="logout" wire:loading.attr="disabled"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600 disabled:opacity-50"
                        aria-label="Keluar"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M10 17l5-5-5-5M15 12H3" />
                            <path d="M21 19V5a2 2 0 0 0-2-2h-6" />
                        </svg></button>
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header
                class="sticky top-0 z-30 flex h-20 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex items-center gap-3"><button type="button" @click="sidebarOpen = true"
                        class="rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50 lg:hidden"
                        aria-label="Buka sidebar"><svg class="h-5 w-5" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg></button>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">{{ $section }}
                        </p>
                        <h1 class="text-lg font-extrabold text-slate-900 sm:text-xl">{{ $title }}</h1>
                    </div>
                </div><button type="button" wire:click="logout" wire:loading.attr="disabled"
                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600 disabled:opacity-50"><svg
                        class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 17l5-5-5-5M15 12H3" />
                        <path d="M21 19V5a2 2 0 0 0-2-2h-6" />
                    </svg><span wire:loading.remove wire:target="logout">Keluar</span><span wire:loading
                        wire:target="logout">Keluar...</span></button>
            </header>
            <main class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
        </div>
    </div>

    @livewireScripts
</body>

</html>
