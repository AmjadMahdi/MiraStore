<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        @php
            // Private, gated, or per-visitor pages are excluded from indexing
            // by default so search engines never crawl dashboards or a
            // guest's own cart — this is centralized here (rather than on
            // every individual view) precisely so no existing page template
            // needs to be touched to get the correct behavior.
            $seoNoindex = $noindex ?? (
                request()->routeIs('admin.*')
                || request()->routeIs('vendor.dashboard')
                || request()->routeIs('vendor.products.*')
                || request()->routeIs('vendor.analytics')
                || request()->routeIs('vendor.status')
                || request()->routeIs('login')
                || request()->routeIs('shein.cart')
                || request()->routeIs('shein.public-cart')
            );

            $seoTitle = $title ?? config('app.name');
            $seoDescription = $description ?? 'ميرا ستور: وسيط طلبات شي إن في تعز، اليمن، ومنصة إلكترونية لتجّار تعز لعرض وبيع منتجاتهم مباشرة عبر واتساب.';
            $seoCanonical = $canonical ?? url()->current();
            $seoType = $ogType ?? 'website';
        @endphp

        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">

        @if ($seoNoindex)
            <meta name="robots" content="noindex, nofollow">
        @else
            <meta name="robots" content="index, follow">
        @endif

        <link rel="canonical" href="{{ $seoCanonical }}">
        <link rel="alternate" hreflang="ar-ye" href="{{ $seoCanonical }}">
        <link rel="alternate" hreflang="x-default" href="{{ $seoCanonical }}">

        {{-- Open Graph --}}
        <meta property="og:type" content="{{ $seoType }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="ar_YE">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:url" content="{{ $seoCanonical }}">
        @isset($image)
            <meta property="og:image" content="{{ $image }}">
            <meta property="og:image:alt" content="{{ $imageAlt ?? $seoTitle }}">
        @endisset

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="{{ isset($image) ? 'summary_large_image' : 'summary' }}">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        @isset($image)
            <meta name="twitter:image" content="{{ $image }}">
        @endisset

        {{ $seoHead ?? '' }}

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/nebula-shader.js'])

        @livewireStyles
    </head>
    <body class="bg-white text-ink">
        <nav class="border-b border-line-medium bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
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
                    @if (request()->routeIs('home'))
                        <livewire:shein.order-status />
                    @endif

                    @auth
                        <a href="{{ auth()->user()->isStaff() ? route('admin.dashboard') : route('vendor.dashboard') }}" class="text-muted hover:text-primary">
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
            </div>
        </nav>

        @auth
            @if (request()->routeIs('vendor.*'))
                <div class="border-b border-line-medium bg-white">
                    <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 text-sm">
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
                </div>
            @elseif (request()->routeIs('admin.*'))
                <div class="border-b border-line-medium bg-white">
                    <div class="mx-auto flex max-w-6xl gap-1 overflow-x-auto px-4 text-sm">
                        @php
                            $adminTabs = [
                                'admin.dashboard' => __('نظرة عامة'),
                                'admin.products.index' => __('المنتجات'),
                                'admin.categories.index' => __('الفئات'),
                                'admin.vendors.index' => __('التجّار'),
                                'admin.carts.index' => __('سلال Shein'),
                                'admin.activity.index' => __('سجل النشاط'),
                                'admin.settings.index' => __('الإعدادات'),
                            ];

                            if (auth()->user()->isSuperAdmin()) {
                                $adminTabs['admin.staff.index'] = __('الحسابات');
                            }
                        @endphp
                        @foreach ($adminTabs as $route => $label)
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
                </div>
            @endif
        @endauth

        {{ $slot }}

        @livewireScripts
    </body>
</html>
