<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=datawidth, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="h-screen overflow-hidden font-sans antialiased bg-base-200">

    <div class="drawer lg:drawer-open h-screen overflow-hidden">

        {{-- DRAWER TOGGLE --}}
        <input id="main-drawer" type="checkbox" class="drawer-toggle" />

        {{-- CONTENT AREA --}}
        <div class="drawer-content flex flex-col h-screen overflow-hidden">

            {{-- NAVBAR MOBILE ONLY --}}
            <x-nav sticky class="lg:hidden shrink-0 bg-base-100 border-b border-base-300">
                <x-slot:brand>
                    <x-app-brand />
                </x-slot:brand>

                <x-slot:actions>
                    <label for="main-drawer" class="me-3">
                        <x-icon name="o-bars-3" class="cursor-pointer" />
                    </label>
                </x-slot:actions>
            </x-nav>

            {{-- DASHBOARD / PAGE CONTENT - SCROLL SENDIRI --}}
            <main class="flex-1 min-h-0 overflow-y-auto overflow-x-hidden bg-base-200">
                <div class="w-full min-h-full p-4 sm:p-5 lg:p-6">
                    {{ $slot }}
                </div>
            </main>

        </div>

        {{-- SIDEBAR AREA --}}
        <div class="drawer-side z-50 h-screen">

            {{-- OVERLAY MOBILE --}}
            <label for="main-drawer" aria-label="close sidebar" class="drawer-overlay"></label>

            {{-- SIDEBAR - NEMPEL KIRI + SCROLL SENDIRI --}}
            <aside class="w-72 h-screen bg-base-100 border-r border-base-300 flex flex-col overflow-hidden">

                {{-- BRAND DESKTOP --}}
                <div class="shrink-0 px-5 py-4 border-b border-base-300">
                    <x-app-brand />
                </div>

                {{-- MENU SCROLL SENDIRI --}}
                <div class="flex-1 min-h-0 overflow-y-auto px-4 py-4">

                    <x-menu activate-by-route class="w-full">

                        {{-- USER --}}
                        @if($user = auth()->user())
                            <x-menu-separator />

                            <x-list-item
                                :item="$user"
                                value="name"
                                sub-value="email"
                                no-separator
                                no-hover
                                class="-mx-2 !-my-2 rounded"
                            >
                                <x-slot:actions>
                                    <x-button
                                        icon="o-power"
                                        class="btn-circle btn-ghost btn-xs"
                                        tooltip-left="logoff"
                                        no-wire-navigate
                                        link="/logout"
                                    />
                                </x-slot:actions>
                            </x-list-item>

                        <x-menu-separator />
                        @endif

                        {{-- MENU UTAMA --}}
                        <x-menu-item 
                            title="Dashboard" 
                            icon="o-home" 
                            link="/dashboard" 
                        />

                        @role('admin')
                            <x-menu-separator />

                            {{-- DATABASE --}}
                            <x-menu-sub 
                                title="Database" 
                                icon="o-circle-stack"
                            >
                                <x-menu-item 
                                    title="Data ESP" 
                                    icon="o-cpu-chip" 
                                    link="/dataEsp" 
                                />

                                <x-menu-item 
                                    title="Data Sensor" 
                                    icon="o-chart-bar" 
                                    link="/dataSensor" 
                                />

                                <x-menu-item 
                                    title="Data Actuator" 
                                    icon="o-power" 
                                    link="/dataActuator" 
                                />

                                <x-menu-item 
                                    title="Log Data" 
                                    icon="o-document-text" 
                                    link="/logData" 
                                />

                                <x-menu-item 
                                    title="Export & Import" 
                                    icon="o-arrow-up-tray" 
                                    link="/exportimport" 
                                />
                            </x-menu-sub>

                            <x-menu-separator />

                            {{-- HARDWARE SYSTEM --}}
                            <x-menu-sub 
                                title="Hardware System" 
                                icon="o-server-stack"
                            >
                                <x-menu-item 
                                    title="Add Hardware" 
                                    icon="o-plus-circle" 
                                    link="/addHardware" 
                                />

                                <x-menu-item 
                                    title="List Hardware" 
                                    icon="o-list-bullet" 
                                    link="/admin/hardware" 
                                />

                                <x-menu-item 
                                    title="Status Hardware" 
                                    icon="o-signal" 
                                    link="/admin/hardware/status" 
                                />

                                <x-menu-item 
                                    title="Monitoring Sensor" 
                                    icon="o-presentation-chart-line" 
                                    link="/admin/hardware/monitoring" 
                                />
                            </x-menu-sub>

                            <x-menu-separator />

                            {{-- CLIENT --}}
                            <x-menu-sub 
                                title="Client" 
                                icon="o-users"
                            >
                                <x-menu-item 
                                    title="Data Client" 
                                    icon="o-user-group" 
                                    link="/admin/client" 
                                />

                                <x-menu-item 
                                    title="Add Client" 
                                    icon="o-user-plus" 
                                    link="/admin/client/create" 
                                />
                            </x-menu-sub>

                        @endrole
                    </x-menu>

                </div>

            </aside>
        </div>

    </div>

    {{-- TOAST AREA --}}
    <x-toast />

</body>
</html>