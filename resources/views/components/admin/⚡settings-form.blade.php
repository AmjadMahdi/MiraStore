<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Validate('nullable|url|max:255')]
    public string $support_whatsapp_link = '';

    /** @var array<int, string> */
    public array $hero_titles = [];

    #[Validate('required|string|max:500')]
    public string $hero_subtitle = '';

    #[Validate('required|string|max:100')]
    public string $hero_button_text = '';

    /** @var array<int, string> */
    public array $hero_background_images = [];

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newHeroBackgroundImages = [];

    public bool $justSaved = false;

    public function mount(): void
    {
        $this->support_whatsapp_link = Setting::get('support_whatsapp_link', '') ?? '';

        $this->hero_titles = Setting::getArray('hero_titles', [
            'طلباتك من Shein لمدينة تعز.. أوفر وأسرع!',
        ]);

        $this->hero_subtitle = Setting::get(
            'hero_subtitle',
            'خدمة طلب مجانية بالكامل. أدخلي رابط المنتج اللي عجبك، وخدمة العملاء بتتواصل معاكي مباشرة عشان تأكد طلبك.'
        );

        $this->hero_button_text = Setting::get('hero_button_text', '🔗 هاتي رابط المنتج هنا');

        $this->hero_background_images = Setting::getArray('hero_background_images', []);
    }

    public function addTitle(): void
    {
        $this->hero_titles[] = '';
    }

    public function removeTitle(int $index): void
    {
        unset($this->hero_titles[$index]);
        $this->hero_titles = array_values($this->hero_titles);
    }

    public function uploadHeroBackgroundImages(): void
    {
        $this->validate([
            'newHeroBackgroundImages' => ['required', 'array', 'min:1'],
            'newHeroBackgroundImages.*' => ['image', 'max:8192'],
        ]);

        foreach ($this->newHeroBackgroundImages as $upload) {
            $image = ImageManager::gd()->read($upload->getRealPath())->cover(1920, 1080);

            $path = 'hero/'.uniqid().'.jpg';

            Storage::disk('public')->put($path, (string) $image->toJpeg(85));

            $this->hero_background_images[] = $path;
        }

        Setting::setArray('hero_background_images', $this->hero_background_images);

        $this->newHeroBackgroundImages = [];
    }

    public function removeHeroBackgroundImage(int $index): void
    {
        $path = $this->hero_background_images[$index] ?? null;

        if ($path === null) {
            return;
        }

        Storage::disk('public')->delete($path);

        unset($this->hero_background_images[$index]);
        $this->hero_background_images = array_values($this->hero_background_images);

        Setting::setArray('hero_background_images', $this->hero_background_images);
    }

    protected function rules(): array
    {
        return [
            'hero_titles' => ['required', 'array', 'min:1'],
            'hero_titles.*' => ['required', 'string', 'max:255'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        Setting::set('support_whatsapp_link', $this->support_whatsapp_link !== '' ? $this->support_whatsapp_link : null);
        Setting::setArray('hero_titles', $this->hero_titles);
        Setting::set('hero_subtitle', $this->hero_subtitle);
        Setting::set('hero_button_text', $this->hero_button_text);

        $this->justSaved = true;
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">الإعدادات</h1>

    @if ($justSaved)
        <div class="mt-4 rounded-lg border border-green-300 bg-green-50 p-3" wire:poll.4s="$set('justSaved', false)">
            <p class="text-sm font-semibold text-green-700">تم حفظ الإعدادات بنجاح ✓</p>
        </div>
    @endif

    <form wire:submit="save" class="mt-6 space-y-6">
        <div>
            <label class="block text-sm font-medium text-ink-soft">رابط واتساب الإدارة</label>
            <p class="mt-0.5 text-xs text-muted">يظهر هذا الرابط للتجّار الجدد ليتواصلوا معكم في حال وجود استفسار.</p>
            <input
                type="text"
                wire:model="support_whatsapp_link"
                dir="ltr"
                placeholder="https://wa.me/967xxxxxxxxx"
                class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
            >
            @error('support_whatsapp_link') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div class="border-t border-line-medium pt-6">
            <p class="text-sm font-semibold text-ink">محتوى الواجهة الرئيسية (Hero)</p>

            <div class="mt-3">
                <label class="block text-sm font-medium text-ink-soft">العنوان الرئيسي</label>
                <p class="mt-0.5 text-xs text-muted">يمكنك إضافة أكثر من عنوان، وسيتم عرضها بالتناوب مع تأثير حركي.</p>

                <div class="mt-1.5 space-y-2">
                    @foreach ($hero_titles as $index => $title)
                        <div class="flex items-center gap-2">
                            <input
                                type="text"
                                wire:model="hero_titles.{{ $index }}"
                                class="w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                            >
                            @if (count($hero_titles) > 1)
                                <button
                                    type="button"
                                    wire:click="removeTitle({{ $index }})"
                                    class="flex-shrink-0 rounded-lg border border-line-medium px-2.5 py-2.5 text-xs font-medium text-discount"
                                    aria-label="حذف هذا العنوان"
                                >
                                    حذف
                                </button>
                            @endif
                        </div>
                        @error("hero_titles.{$index}") <p class="text-sm text-discount">{{ $message }}</p> @enderror
                    @endforeach
                </div>
                @error('hero_titles') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror

                <button
                    type="button"
                    wire:click="addTitle"
                    class="mt-2 rounded-lg border border-line-medium px-3 py-1.5 text-xs font-medium text-ink-soft"
                >
                    + إضافة عنوان آخر
                </button>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-ink-soft">صور الخلفية</label>
                <p class="mt-0.5 text-xs text-muted">
                    ارفع صورة أو أكثر لتظهر كخلفية متغيرة بدل التأثير المتحرك الافتراضي — عند رفع أكثر من صورة، تتبدل الخلفية بينها تلقائياً. اترك القائمة فارغة لاستخدام التأثير الافتراضي.
                </p>

                @if (count($hero_background_images) > 0)
                    <div class="mt-2 grid grid-cols-3 gap-2">
                        @foreach ($hero_background_images as $index => $path)
                            <div class="group relative aspect-video overflow-hidden rounded-lg border border-line-medium">
                                <img src="{{ Storage::url($path) }}" class="h-full w-full object-cover">
                                <button
                                    type="button"
                                    wire:click="removeHeroBackgroundImage({{ $index }})"
                                    wire:confirm="حذف هذه الصورة من خلفية الواجهة الرئيسية؟"
                                    class="absolute end-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-black/60 text-white transition hover:bg-discount"
                                    aria-label="حذف الصورة"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-2 flex items-start gap-2">
                    <div class="flex-1">
                        <input
                            type="file"
                            wire:model="newHeroBackgroundImages"
                            multiple
                            accept="image/*"
                            class="block w-full text-sm text-ink-soft"
                        >
                        @error('newHeroBackgroundImages') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                        @error('newHeroBackgroundImages.*') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
                    </div>
                    <button
                        type="button"
                        wire:click="uploadHeroBackgroundImages"
                        wire:loading.attr="disabled"
                        wire:target="uploadHeroBackgroundImages,newHeroBackgroundImages"
                        class="flex-shrink-0 rounded-lg border border-line-medium px-3 py-2 text-xs font-medium text-ink-soft disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="uploadHeroBackgroundImages">رفع</span>
                        <span wire:loading wire:target="uploadHeroBackgroundImages">جارٍ الرفع...</span>
                    </button>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-ink-soft">النص الفرعي</label>
                <textarea
                    wire:model="hero_subtitle"
                    rows="3"
                    class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                ></textarea>
                @error('hero_subtitle') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium text-ink-soft">نص الزر</label>
                <input
                    type="text"
                    wire:model="hero_button_text"
                    class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"
                >
                @error('hero_button_text') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">حفظ</span>
            <span wire:loading wire:target="save">جارٍ الحفظ...</span>
        </button>
    </form>
</div>
