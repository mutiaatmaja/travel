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

    <link rel="stylesheet" href="{{ asset('css/admin-sidebar.css') }}">

    <style>
        [x-cloak] {
            display: none !important;
        }

        .admin-sidebar-scroll {
            scrollbar-color: #cbd5e1 transparent;
            scrollbar-width: auto;
        }

        .admin-sidebar-scroll::-webkit-scrollbar {
            width: 8px;
        }

        .admin-sidebar-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }

        .admin-sidebar-scroll::-webkit-scrollbar-track {
            background: #f8fafc;
        }
    </style>

    @livewireStyles
</head>

<body class="h-screen overflow-hidden bg-white font-sans text-slate-800 antialiased">
    @props([
        'title' => $title ?? 'Dashboard',
        'section' => $section ?? 'Overview',
    ])

    {{-- sidebar.open & desktopCollapsed murni state UI (bukan status "aktif"), jadi tetap Alpine --}}
    <div x-data="{ sidebar: { open: false, desktopCollapsed: false } }" class="h-screen bg-slate-50">

        <!-- Mobile overlay -->
        <div x-show="sidebar.open" x-cloak @click="sidebar.open = false"
            class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden">
        </div>

        <aside
            class="admin-sidebar fixed inset-y-0 left-0 z-50 flex h-screen flex-col overflow-hidden border-r border-slate-200 bg-white transition-all duration-300"
            :class="[
                sidebar.open ? 'translate-x-0' : '-translate-x-full',
                'lg:translate-x-0',
                sidebar.desktopCollapsed ? 'lg:w-24' : 'lg:w-72',
                'w-72'
            ]">

            <!-- Logo -->
            <div class="flex h-20 items-center justify-between border-b border-slate-100 px-4 lg:px-3">
                <a wire:navigate href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <img src="{{ asset('asetgambar/logo.png') }}" alt="TransGo"
                        class="h-10 w-10 shrink-0 rounded-xl object-cover">

                    <span class="text-lg font-extrabold tracking-tight text-slate-900"
                        x-show="!sidebar.desktopCollapsed" x-cloak>
                        Trans<span class="text-brand-500">Go</span>
                    </span>
                </a>

                <!-- Mobile Close Button -->
                <button type="button" @click="sidebar.open = false"
                    class="rounded-lg p-2 text-slate-400 hover:bg-slate-50 lg:hidden" aria-label="Tutup sidebar">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 6l12 12M18 6L6 18" />
                    </svg>
                </button>
            </div>

            <!-- Navigation -->
            <nav
                class="admin-sidebar-scroll h-[calc(100vh-8rem)] min-h-0 flex-none overflow-y-auto overscroll-contain px-3 py-6">

                <!-- Section Title -->
                <p class="px-3 text-[11px] font-bold uppercase tracking-widest text-slate-400"
                    x-show="!sidebar.desktopCollapsed" x-cloak>
                    Menu utama
                </p>

                <div class="mt-3 space-y-1">

                    {{-- =====================================================
                        DASHBOARD
                    ===================================================== --}}
                    <a wire:navigate href="{{ route('dashboard') }}"
                        class="menu-item {{ request()->routeIs('dashboard') ? 'menu-active' : '' }}">
                        <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <rect width="7" height="7" x="3" y="3" rx="1" />
                            <rect width="7" height="7" x="14" y="3" rx="1" />
                            <rect width="7" height="7" x="14" y="14" rx="1" />
                            <rect width="7" height="7" x="3" y="14" rx="1" />
                        </svg>
                        <span x-show="!sidebar.desktopCollapsed" x-cloak>Dashboard</span>
                    </a>

                    {{-- =====================================================
                        MASTER DATA
                    ===================================================== --}}
                    <div x-data="{
                        open: {{ request()->routeIs([
                            'users',
                            'roles-permissions',
                            'permissions',
                            'cities',
                            'outlets',
                            'vehicles',
                            'drivers',
                            'routes',
                            'trips',
                        ])
                            ? 'true'
                            : 'false' }}
                    }" :class="open ? 'menu-open' : 'menu-closed'">
                        <button type="button" class="menu-parent" @click="open = !open">
                            <span class="flex items-center gap-3">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                    stroke-width="2">
                                    <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />
                                </svg>
                                <span x-show="!sidebar.desktopCollapsed" x-cloak>Master Data</span>
                            </span>
                            <svg class="menu-arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" x-show="!sidebar.desktopCollapsed" x-cloak>
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div class="submenu" x-show="!sidebar.desktopCollapsed" x-cloak>
                            <a wire:navigate href="{{ route('users') }}"
                                class="submenu-item {{ request()->routeIs('users') ? 'menu-active' : '' }}">
                                Users
                            </a>
                            <a wire:navigate href="{{ route('roles-permissions') }}"
                                class="submenu-item {{ request()->routeIs('roles-permissions') ? 'menu-active' : '' }}">
                                Role & Permission
                            </a>
                            <a wire:navigate href="{{ route('cities') }}"
                                class="submenu-item {{ request()->routeIs('cities') ? 'menu-active' : '' }}">
                                Wilayah
                            </a>
                            <a wire:navigate href="{{ route('outlets') }}"
                                class="submenu-item {{ request()->routeIs('outlets') ? 'menu-active' : '' }}">
                                Outlet
                            </a>
                            <a wire:navigate href="{{ route('vehicles') }}"
                                class="submenu-item {{ request()->routeIs('vehicles') ? 'menu-active' : '' }}">
                                Armada
                            </a>
                            <a wire:navigate href="{{ route('drivers') }}"
                                class="submenu-item {{ request()->routeIs('drivers') ? 'menu-active' : '' }}">
                                Supir
                            </a>
                            <a wire:navigate href="{{ route('routes') }}"
                                class="submenu-item {{ request()->routeIs('routes') ? 'menu-active' : '' }}">
                                Rute
                            </a>
                            <a wire:navigate href="{{ route('trips') }}"
                                class="submenu-item {{ request()->routeIs('trips') ? 'menu-active' : '' }}">
                                Jadwal
                            </a>
                        </div>
                    </div>

                    {{-- =====================================================
                        PAKET
                    ===================================================== --}}
                    <div x-data="{
                        open: {{ request()->routeIs(['packages.statistics', 'packages.settings', 'packages', 'packages.tracing'])
                            ? 'true'
                            : 'false' }}
                    }" :class="open ? 'menu-open' : 'menu-closed'">
                        <button type="button" class="menu-parent" @click="open = !open">
                            <span class="flex items-center gap-3">
                                <svg class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none"
                                    stroke="currentColor" stroke-width="2">
                                    <path d="M21 8l-9-5-9 5 9 5 9-5Zm0 0v8l-9 5m0-8v8m0-8L3 8m9 5-9-5" />
                                </svg>
                                <span x-show="!sidebar.desktopCollapsed" x-cloak>Paket</span>
                            </span>
                            <svg class="menu-arrow h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2" x-show="!sidebar.desktopCollapsed" x-cloak>
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <div class="submenu" x-show="!sidebar.desktopCollapsed" x-cloak>
                            <a wire:navigate href="{{ route('packages.statistics') }}"
                                class="submenu-item {{ request()->routeIs('packages.statistics') ? 'menu-active' : '' }}">
                                Statistik
                            </a>
                            <a wire:navigate href="{{ route('packages.settings') }}"
                                class="submenu-item {{ request()->routeIs('packages.settings') ? 'menu-active' : '' }}">
                                Pengaturan
                            </a>
                            <a wire:navigate href="{{ route('packages') }}"
                                class="submenu-item {{ request()->routeIs('packages') ? 'menu-active' : '' }}">
                                Semua Paket
                            </a>
                            <a wire:navigate href="{{ route('packages.tracing') }}"
                                class="submenu-item {{ request()->routeIs('packages.tracing') ? 'menu-active' : '' }}">
                                Tracing
                            </a>
                        </div>
                    </div>

                    {{-- =====================================================
                        BOOKING / LAPORAN / PENGATURAN
                    ===================================================== --}}
                    <a wire:navigate href="{{ route('dashboard') }}"
                        class="menu-item {{ request()->routeIs('booking.*') ? 'menu-active' : '' }}">
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">B</span>
                        <span x-show="!sidebar.desktopCollapsed" x-cloak>Booking</span>
                    </a>

                    <a wire:navigate href="{{ route('dashboard') }}"
                        class="menu-item {{ request()->routeIs('laporan.*') ? 'menu-active' : '' }}">
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">L</span>
                        <span x-show="!sidebar.desktopCollapsed" x-cloak>Laporan</span>
                    </a>

                    <a wire:navigate href="{{ route('dashboard') }}"
                        class="menu-item {{ request()->routeIs('pengaturan.*') ? 'menu-active' : '' }}">
                        <span
                            class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-slate-100 text-[10px] font-extrabold text-slate-500">P</span>
                        <span x-show="!sidebar.desktopCollapsed" x-cloak>Pengaturan</span>
                    </a>

                </div>
            </nav>

            <!-- User Profile -->
            <div class="border-t border-slate-100 p-4">
                <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3">

                    <img src="https://i.pravatar.cc/80?img=12" alt="Avatar Admin"
                        class="h-9 w-9 shrink-0 rounded-full object-cover">

                    <div class="min-w-0 flex-1" x-show="!sidebar.desktopCollapsed" x-cloak>
                        <p class="truncate text-sm font-bold text-slate-800">
                            {{ auth()->user()->name ?? 'Admin TransGo' }}
                        </p>

                        <p class="truncate text-xs text-slate-500">
                            {{ auth()->user()->email ?? 'admin@transgo.com' }}
                        </p>
                    </div>

                    <!-- Logout -->
                    <button type="button" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600"
                        aria-label="Keluar">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M10 17l5-5-5-5M15 12H3" />
                            <path d="M21 19V5a2 2 0 0 0-2-2h-6" />
                        </svg>
                    </button>

                </div>
            </div>

        </aside>


        <div class="flex h-screen flex-col transition-all duration-300 lg:pl-72"
            :class="sidebar.desktopCollapsed ? 'lg:pl-24' : 'lg:pl-72'">
            <header
                class="sticky top-0 z-30 flex h-20 shrink-0 items-center justify-between border-b border-slate-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" @click="sidebar.open = true"
                        class="rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50 lg:hidden"
                        aria-label="Buka sidebar">
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <button type="button" @click="sidebar.desktopCollapsed = !sidebar.desktopCollapsed"
                        class="hidden rounded-xl border border-slate-200 p-2.5 text-slate-600 hover:bg-slate-50 lg:inline-flex"
                        aria-label="Collapse sidebar">
                        <svg class="h-5 w-5 transition-transform duration-300" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2"
                            :class="sidebar.desktopCollapsed ? 'rotate-180' : ''">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wider text-brand-600">{{ $section }}
                        </p>
                        <h1 class="text-lg font-extrabold text-slate-900 sm:text-xl">{{ $title }}</h1>
                    </div>
                </div>
                <button type="button" wire:click="logout" wire:loading.attr="disabled"
                    class="flex items-center gap-2 rounded-xl px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-red-50 hover:text-red-600 disabled:opacity-50">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 17l5-5-5-5M15 12H3" />
                        <path d="M21 19V5a2 2 0 0 0-2-2h-6" />
                    </svg>
                    <span wire:loading.remove wire:target="logout">Keluar</span>
                    <span wire:loading wire:target="logout">Keluar...</span>
                </button>
            </header>
            <main class="mx-auto min-h-0 w-full max-w-7xl flex-1 overflow-y-auto overscroll-contain p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>

</html>
