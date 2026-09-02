<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-white text-ink">
        <nav class="flex items-center justify-between border-b border-line-medium bg-white px-4 py-3">
            <div
                x-data="{ titles: ['ميرا ستور', 'Mira Store'], active: 0 }"
                x-init="setInterval(() => active = (active + 1) % titles.length, 3000)"
                class="grid"
            >
                <template x-for="(title, i) in titles" :key="i">
                    <a
                        href="{{ route('home') }}"
                        :dir="i === 1 ? 'ltr' : 'rtl'"
                        :class="active === i ? 'opacity-100 translate-y-0' : 'opacity-0 -translate-y-1'"
                        x-text="title"
                        class="col-start-1 row-start-1 font-semibold text-primary transition-all duration-700 ease-in-out"
                    ></a>
                </template>
            </div>

            <div class="flex items-center gap-3 text-sm sm:gap-4">
                <livewire:shein.cart-badge />

                @auth
                    <a href="{{ auth()->user()->isSuperAdmin() ? route('admin.dashboard') : route('vendor.dashboard') }}" class="text-muted hover:text-primary">
                        {{ __('لوحة التحكم') }}
                    </a>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-muted hover:text-primary">
                            {{ __('تسجيل الخروج') }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-muted hover:text-primary">{{ __('تسجيل الدخول') }}</a>
                    <a href="{{ route('register') }}" class="font-semibold text-primary">{{ __('ابدأ البيع') }}</a>
                @endauth
            </div>
        </nav>

        @auth
            @if (request()->routeIs('vendor.*'))
                <div class="flex gap-1 overflow-x-auto border-b border-line-medium bg-white px-4 text-sm">
                    @foreach ([
                        'vendor.dashboard' => __('نظرة عامة'),
                        'vendor.products.index' => __('المنتجات'),
                        'vendor.analytics' => __('التحليلات'),
                    ] as $route => $label)
                        <a
                            href="{{ route($route) }}"
                            @class([
                                'whitespace-nowrap border-b-2 px-3 py-2.5 font-medium',
                                'border-primary text-primary' => request()->routeIs($route),
                                'border-transparent text-muted' => ! request()->routeIs($route),
                            ])
                        >
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            @elseif (request()->routeIs('admin.*'))
                <div class="flex gap-1 overflow-x-auto border-b border-line-medium bg-white px-4 text-sm">
                    @foreach ([
                        'admin.dashboard' => __('نظرة عامة'),
                        'admin.products.index' => __('المنتجات'),
                        'admin.vendors.index' => __('التجّار'),
                        'admin.carts.index' => __('سلال شي إن'),
                        'admin.activity.index' => __('سجل النشاط'),
                    ] as $route => $label)
                        <a
                            href="{{ route($route) }}"
                            @class([
                                'whitespace-nowrap border-b-2 px-3 py-2.5 font-medium',
                                'border-primary text-primary' => request()->routeIs($route),
                                'border-transparent text-muted' => ! request()->routeIs($route),
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
