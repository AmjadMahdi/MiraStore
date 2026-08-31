<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-gray-50 text-gray-800">
        <nav class="flex items-center justify-between border-b border-gray-100 bg-white px-4 py-3">
            <a href="{{ route('home') }}" class="font-semibold text-rose-600">MiraStore</a>

            <div class="flex items-center gap-3 text-sm sm:gap-4">
                @auth
                    <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.dashboard') : route('vendor.dashboard') }}" class="text-gray-600">
                        Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600">Sign in</a>
                    <a href="{{ route('register') }}" class="font-semibold text-rose-600">Sell</a>
                @endauth
            </div>
        </nav>

        @auth
            @if (request()->routeIs('vendor.*'))
                <div class="flex gap-1 overflow-x-auto border-b border-gray-100 bg-white px-4 text-sm">
                    @foreach ([
                        'vendor.dashboard' => 'Overview',
                        'vendor.products.index' => 'Products',
                        'vendor.analytics' => 'Analytics',
                    ] as $route => $label)
                        <a
                            href="{{ route($route) }}"
                            @class([
                                'whitespace-nowrap border-b-2 px-3 py-2.5 font-medium',
                                'border-rose-600 text-rose-600' => request()->routeIs($route),
                                'border-transparent text-gray-500' => ! request()->routeIs($route),
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @elseif (request()->routeIs('admin.*'))
                <div class="flex gap-1 overflow-x-auto border-b border-gray-100 bg-white px-4 text-sm">
                    @foreach ([
                        'admin.dashboard' => 'Overview',
                        'admin.products.index' => 'Products',
                        'admin.vendors.index' => 'Vendors',
                        'admin.carts.index' => 'SHEIN carts',
                        'admin.activity.index' => 'Activity',
                    ] as $route => $label)
                        <a
                            href="{{ route($route) }}"
                            @class([
                                'whitespace-nowrap border-b-2 px-3 py-2.5 font-medium',
                                'border-rose-600 text-rose-600' => request()->routeIs($route),
                                'border-transparent text-gray-500' => ! request()->routeIs($route),
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @endif
        @endauth

        {{ $slot }}

        @livewireScripts
    </body>
</html>
