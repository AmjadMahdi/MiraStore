<x-layouts.app title="الشروط والأحكام">
    <div class="mx-auto max-w-2xl p-6 sm:p-8">
        <h1 class="text-2xl font-bold tracking-tight text-ink">الشروط والأحكام</h1>

        @if ($terms = \App\Models\Setting::get('terms_and_conditions'))
            <p class="mt-4 whitespace-pre-line text-sm leading-relaxed text-ink-soft">{{ $terms }}</p>
        @else
            <p class="mt-4 text-sm text-disabled">لم تتم إضافة الشروط والأحكام بعد.</p>
        @endif
    </div>
</x-layouts.app>
