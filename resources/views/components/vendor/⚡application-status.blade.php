<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    public function with(): array
    {
        return [
            'supportWhatsappLink' => Setting::get('support_whatsapp_link'),
        ];
    }
};
?>

<div class="mx-auto mt-8 max-w-md rounded-xl border border-line-medium p-8 text-center">
    @if (Auth::user()->isApplicationRejected())
        <h1 class="text-2xl font-bold tracking-tight text-ink">لم تتم الموافقة على طلبك</h1>
        <p class="mt-3 text-sm text-ink-soft">
            بعد مراجعة طلب انضمامك كتاجر، لم تتم الموافقة عليه في هذه المرة.
        </p>
        @if (Auth::user()->application_rejection_reason)
            <p class="mt-3 rounded-lg bg-surface p-3 text-sm text-ink-soft">
                السبب: {{ Auth::user()->application_rejection_reason }}
            </p>
        @endif
    @else
        <h1 class="text-2xl font-bold tracking-tight text-ink">شكراً لانضمامك إلى ميرا ستور</h1>
        <p class="mt-3 text-sm text-ink-soft">
            تم استلام بياناتك بنجاح، وطلبك الآن قيد المراجعة من قبل الإدارة. الانضمام والبيع على ميرا ستور مجاني بالكامل، ولا توجد أي رسوم مطلوبة منك.
        </p>
        <p class="mt-3 text-sm text-ink-soft">
            سنتواصل معك قريباً بعد مراجعة بياناتك.
        </p>
    @endif

    @if ($supportWhatsappLink)
        <a
            href="{{ $supportWhatsappLink }}"
            target="_blank"
            class="mt-6 flex items-center justify-center gap-1.5 rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 004.74 1.21h.01c5.46 0 9.9-4.45 9.9-9.91C21.96 6.45 17.5 2 12.04 2zm0 18.1a8.2 8.2 0 01-4.18-1.14l-.3-.18-3.12.82.83-3.04-.2-.31a8.18 8.18 0 01-1.26-4.37c0-4.53 3.69-8.21 8.24-8.21 2.2 0 4.27.86 5.82 2.41a8.15 8.15 0 012.41 5.81c0 4.53-3.69 8.21-8.24 8.21zm4.52-6.16c-.25-.12-1.47-.72-1.7-.81-.23-.08-.39-.12-.56.13-.17.25-.64.81-.78.97-.15.17-.29.19-.54.06-.25-.12-1.04-.38-1.99-1.22-.73-.65-1.23-1.46-1.37-1.71-.14-.25-.02-.38.11-.51.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.17.04-.31-.02-.43-.06-.13-.56-1.34-.76-1.84-.2-.48-.41-.42-.56-.42-.14-.01-.31-.01-.47-.01a.9.9 0 00-.65.31c-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.57.12.17 1.75 2.67 4.24 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.67-1.18.21-.58.21-1.07.15-1.18-.06-.11-.23-.17-.48-.29z" />
            </svg>
            تواصل معنا عبر واتساب
        </a>
    @endif

    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-muted underline hover:text-primary">تسجيل الخروج</button>
    </form>
</div>
