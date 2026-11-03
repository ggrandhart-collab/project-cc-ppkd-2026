<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Pipeline Konten') · Creator Pipeline</title>
    {{-- Tailwind & Alpine via CDN agar tidak perlu npm build (butuh koneksi internet) --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style type="text/tailwindcss">
        @layer components {
            .btn { @apply inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50; }
            .btn-primary { @apply inline-flex items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700; }
            .btn-danger { @apply inline-flex items-center rounded-md bg-rose-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-rose-700; }
            .input { @apply block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500; }
            .label { @apply mb-1 block text-sm font-medium text-slate-700; }
            .card { @apply rounded-lg border border-slate-200 bg-white p-4 shadow-sm; }
        }
    </style>
    <style>[x-cloak]{display:none!important}</style>
    <link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>


<body class="min-h-screen bg-slate-50 text-slate-800">

@auth
<div x-data="{ 
    sidebarOpen: false,
    sidebarCollapsed: false
}" class="min-h-screen">

    {{-- Overlay Mobile --}}
    <div x-show="sidebarOpen"
         x-transition.opacity
         @click="sidebarOpen = false"
         class="fixed inset-0 z-40 bg-slate-900/50 lg:hidden"
         x-cloak>
    </div>

    {{-- SIDEBAR --}}
    <aside
        class="fixed inset-y-0 left-0 z-50 flex flex-col border-r border-slate-200 bg-white transition-all duration-300"
        :class="[
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
            sidebarCollapsed ? 'w-20' : 'w-64'
        ]">

        {{-- LOGO --}}
        <div class="flex h-16 items-center border-b border-slate-200 px-5"
             :class="sidebarCollapsed ? 'justify-center' : 'justify-between'">

            <a href="{{ route('contents.index') }}"
               class="flex items-center gap-3 text-indigo-600">

                <i class="bi bi-grid-1x2-fill text-2xl shrink-0"></i>

                <span x-show="!sidebarCollapsed"
                      x-transition
                      class="whitespace-nowrap font-bold text-lg">
                    Creator Pipeline
                </span>
            </a>

            <button @click="sidebarOpen = false"
                    class="text-slate-500 lg:hidden">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        {{-- MENU --}}
        <nav class="flex-1 space-y-2 overflow-y-auto px-3 py-6">

            <p x-show="!sidebarCollapsed"
               class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                Workspace
            </p>

            {{-- Pipeline --}}
            <a href="{{ route('contents.index') }}"
               @click="sidebarOpen = false"
               title="Pipeline Konten"
               class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition
               {{ request()->routeIs('contents.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}"
               :class="sidebarCollapsed ? 'justify-center' : ''">

                <i class="bi bi-kanban text-xl shrink-0"></i>

                <span x-show="!sidebarCollapsed" x-transition
                      class="whitespace-nowrap">
                    Pipeline Konten
                </span>
            </a>

            {{-- Channel --}}
            <a href="{{ route('channels.index') }}"
               @click="sidebarOpen = false"
               title="Channel"
               class="flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition
               {{ request()->routeIs('channels.*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-100' }}"
               :class="sidebarCollapsed ? 'justify-center' : ''">

                <i class="bi bi-play-btn text-xl shrink-0"></i>

                <span x-show="!sidebarCollapsed" x-transition
                      class="whitespace-nowrap">
                    Channel
                </span>
            </a>

            {{-- Account --}}
            <div class="pt-6">
                <p x-show="!sidebarCollapsed"
                   class="mb-3 px-3 text-xs font-semibold uppercase tracking-wider text-slate-400">
                    Account
                </p>

                <div class="flex items-center gap-3 rounded-lg bg-slate-50 p-3"
                     :class="sidebarCollapsed ? 'justify-center' : ''">

                    <i class="bi bi-person-circle text-xl text-slate-500 shrink-0"></i>

                    <div x-show="!sidebarCollapsed" x-transition class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-800">
                            {{ auth()->user()->name }}
                        </p>
                        <p class="text-xs capitalize text-slate-500">
                            {{ auth()->user()->role }}
                        </p>
                    </div>
                </div>
            </div>
        </nav>

        {{-- LOGOUT --}}
        <div class="border-t border-slate-200 p-3">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        title="Keluar"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium text-rose-600 hover:bg-rose-50"
                        :class="sidebarCollapsed ? 'justify-center' : ''">

                    <i class="bi bi-box-arrow-left text-xl shrink-0"></i>

                    <span x-show="!sidebarCollapsed" x-transition>
                        Keluar
                    </span>
                </button>
            </form>
        </div>
    </aside>

    {{-- MAIN CONTENT --}}
    <div class="min-h-screen transition-all duration-300"
         :class="sidebarCollapsed ? 'lg:ml-20' : 'lg:ml-64'">

        {{-- TOPBAR --}}
        <header class="sticky top-0 z-30 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">

            <div class="flex items-center gap-3">

                {{-- Toggle Desktop --}}
                <button @click="sidebarCollapsed = !sidebarCollapsed"
                        class="hidden rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:block"
                        title="Toggle Sidebar">
                    <i class="bi bi-layout-sidebar-inset text-xl"></i>
                </button>

                {{-- Toggle Mobile --}}
                <button @click="sidebarOpen = true"
                        class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden">
                    <i class="bi bi-list text-2xl"></i>
                </button>

                <h1 class="text-lg font-semibold text-slate-800">
                    @yield('page-title', 'Dashboard')
                </h1>
            </div>

            <div class="flex items-center gap-2 text-sm text-slate-500">
                <i class="bi bi-person-circle text-lg"></i>
                <span class="hidden sm:block">{{ auth()->user()->name }}</span>
            </div>
        </header>

        {{-- CONTENT --}}
        <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">

            @if (session('success'))
                <div x-data="{ show: true }"
                     x-init="setTimeout(() => show = false, 4000)"
                     x-show="show"
                     x-transition
                     x-cloak
                     class="fixed right-4 top-20 z-50 max-w-sm rounded-md bg-emerald-600 px-4 py-3 text-sm text-white shadow-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                    @if (session('error'))
                        <p>{{ session('error') }}</p>
                    @endif

                    @if ($errors->any())
                        <ul class="list-disc pl-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
@endauth

@guest
    <main class="min-h-screen px-4 py-6">
        @yield('content')
    </main>
@endguest

@stack('scripts')

</body>
</html>