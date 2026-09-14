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
            $footerHasTerms = filled(\App\Models\Setting::get('terms_and_conditions'));
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
    <body class="flex min-h-screen flex-col bg-white text-ink">
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

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="relative overflow-hidden bg-primary">
            <div
                class="pointer-events-none absolute inset-0"
                aria-hidden="true"
                style="
                    background-image:
                        linear-gradient(to right, rgba(255,255,255,0.08) 1px, transparent 1px),
                        linear-gradient(to bottom, rgba(255,255,255,0.08) 1px, transparent 1px);
                    background-size: 44px 44px;
                    mask-image: radial-gradient(ellipse 75% 75% at 50% 50%, black 30%, transparent 85%);
                    -webkit-mask-image: radial-gradient(ellipse 75% 75% at 50% 50%, black 30%, transparent 85%);
                "
            ></div>

            <div class="relative mx-auto max-w-6xl px-4 py-10">
                <div class="grid gap-8 sm:grid-cols-3">
                    <div>
                        <p class="font-semibold text-white">{{ config('app.name') }}</p>
                        <p class="mt-2 text-sm leading-relaxed text-white">
                            {{ __('متجر ميرا: وسيطك المعتمد لطلبات شي إن في تعز، ومنصتك الإلكترونية المبتكرة لتسوق منتجات تجار تعز والطلب مباشرة عبر واتساب.') }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-white">{{ __('روابط سريعة') }}</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-white">
                            <li><a href="{{ route('home') }}" class="hover:underline">{{ __('الرئيسية') }}</a></li>
                            <li><a href="{{ route('shein.index') }}" class="hover:underline">{{ __('اطلب من Shein') }}</a></li>
                            <li><a href="{{ route('register') }}" class="hover:underline">{{ __('ابدأ البيع') }}</a></li>
                            @if ($footerHasTerms)
                                <li><a href="{{ route('terms') }}" class="hover:underline">{{ __('الشروط والأحكام') }}</a></li>
                            @endif
                        </ul>
                    </div>

                    <div>
                        <p class="text-sm font-semibold text-white">{{ __('تواصل معنا') }}</p>
                        <ul class="mt-2 space-y-1.5 text-sm text-white">
                            <li>
                                <a href="https://wa.me/967785698740" target="_blank" class="hover:underline" dir="ltr">
                                    +967 785698740
                                </a>
                            </li>
                            <li>
                                <a href="https://wa.me/966535271025" target="_blank" class="hover:underline" dir="ltr">
                                    +966 53 527 1025
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <p class="mt-8 border-t border-white/10 pt-6 text-center text-xs text-white">
                    &copy; {{ now()->year }} {{ config('app.name') }}. {{ __('جميع الحقوق محفوظة.') }}
                </p>
            </div>
        </footer>

        @livewireScripts
    </body>
</html>
