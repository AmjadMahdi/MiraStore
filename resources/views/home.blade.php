<x-layouts.app
    title="وسيط شي إن في تعز - طلب من شي إن اليمن ومتجر تجار تعز | ميرا ستور"
    description="ميرا ستور: اطلبي أي منتج من شي إن ليصلك في تعز بدون عمولة إضافية، أو تسوقي مباشرة من منتجات تجار تعز المحليين. توصيل سريع وتواصل مباشر عبر واتساب."
>
    <x-slot:seoHead>
        <script type="application/ld+json">
            {!! json_encode([
                '@@context' => 'https://schema.org',
                '@@type' => 'LocalBusiness',
                'name' => config('app.name'),
                'description' => 'وسيط طلبات شي إن ومنصة تجار إلكترونية في مدينة تعز، اليمن.',
                'areaServed' => [
                    '@@type' => 'City',
                    'name' => 'تعز',
                ],
                'address' => [
                    '@@type' => 'PostalAddress',
                    'addressLocality' => 'تعز',
                    'addressCountry' => 'YE',
                ],
                'url' => url('/'),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:seoHead>

    <livewire:shein.hero />
    <livewire:product-grid />
</x-layouts.app>
