<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?Product $product = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|exists:categories,id')]
    public string $category_id = '';

    #[Validate('required|string|max:2000')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public string $price = '';

    #[Validate('nullable|numeric|min:0')]
    public string $compare_at_price = '';

    #[Validate('nullable|string|max:255')]
    public string $options = '';

    #[Validate('required|in:in_stock,pre_order,out_of_stock')]
    public string $stock_status = 'in_stock';

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $newImages = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            abort_unless($product->vendor_id === Auth::id() || Auth::user()->isSuperAdmin(), 403);

            $this->product = $product;
            $this->name = $product->name;
            $this->category_id = (string) $product->category_id;
            $this->description = $product->description;
            $this->price = (string) $product->price;
            $this->compare_at_price = (string) $product->compare_at_price;
            $this->options = (string) $product->options;
            $this->stock_status = $product->stock_status;
        }
    }

    public function rules(): array
    {
        return [
            'newImages' => $this->product ? 'nullable|array' : 'required|array|min:1',
            'newImages.*' => 'image|max:4096',
        ];
    }

    public function removeExistingImage(int $imageId): void
    {
        if ($this->product->images()->count() <= 1) {
            $this->addError('newImages', 'يجب أن يحتوي المنتج على صورة واحدة على الأقل.');

            return;
        }

        $image = $this->product->images()->findOrFail($imageId);
        Storage::disk('public')->delete($image->path);
        $image->delete();

        $this->syncCoverImage($this->product);
        $this->revertToPendingIfNeeded($this->product);
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'category_id' => $this->category_id,
            'description' => $this->description,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price !== '' ? $this->compare_at_price : null,
            'options' => $this->options !== '' ? $this->options : null,
            'stock_status' => $this->stock_status,
        ];

        if ($this->product) {
            $this->product->update($data);

            if (! empty($this->newImages)) {
                $this->appendImages($this->product, $this->newImages);
                $this->revertToPendingIfNeeded($this->product);
            }

            $redirectRoute = Auth::user()->isSuperAdmin() ? 'admin.products.index' : 'vendor.products.index';
            $this->redirect(route($redirectRoute), navigate: true);

            return;
        }

        $vendorId = Auth::id();
        $product = null;

        $firstUpload = array_shift($this->newImages);
        $data['image_path'] = $this->storeSquareImage($firstUpload);

        $limitReached = DB::transaction(function () use ($vendorId, $data, &$product) {
            $vendor = User::whereKey($vendorId)->lockForUpdate()->first();

            if ($vendor->max_products_limit !== null
                && $vendor->products()->count() >= $vendor->max_products_limit) {
                return true;
            }

            $data['vendor_id'] = $vendorId;
            $product = Product::create($data);

            return false;
        });

        if ($limitReached) {
            $limit = Auth::user()->max_products_limit;
            $this->addError('name', "لقد وصلت إلى الحد الأقصى المسموح به وهو {$limit} منتجات.");

            return;
        }

        $product->images()->create(['path' => $data['image_path'], 'sort_order' => 0]);
        $this->appendImages($product, $this->newImages);

        $this->product = $product;

        $this->redirect(route('vendor.products.index'), navigate: true);
    }

    public function with(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'existingImages' => $this->product ? $this->product->images()->get() : collect(),
        ];
    }

    protected function appendImages(Product $product, array $uploads): void
    {
        $nextOrder = ((int) $product->images()->max('sort_order')) + 1;

        foreach ($uploads as $upload) {
            $product->images()->create([
                'path' => $this->storeSquareImage($upload),
                'sort_order' => $nextOrder++,
            ]);
        }

        $this->syncCoverImage($product);
        $this->newImages = [];
    }

    protected function syncCoverImage(Product $product): void
    {
        $cover = $product->images()->orderBy('sort_order')->first();

        if ($cover) {
            $product->update(['image_path' => $cover->path]);
        }
    }

    protected function revertToPendingIfNeeded(Product $product): void
    {
        if (Auth::user()->isSuperAdmin()) {
            return;
        }

        if (in_array($product->status, ['approved', 'rejected'], true)) {
            $product->update(['status' => 'pending', 'rejection_reason' => null]);
        }
    }

    protected function storeSquareImage($upload): string
    {
        $image = ImageManager::gd()->read($upload->getRealPath())->cover(800, 800);

        $path = 'products/'.uniqid().'.jpg';

        Storage::disk('public')->put($path, (string) $image->toJpeg(85));

        return $path;
    }
};
?>

<div class="mx-auto max-w-lg p-6 sm:p-8">
    <h1 class="text-2xl font-bold tracking-tight text-ink">
        {{ $product ? 'تعديل المنتج' : 'إضافة منتج' }}
    </h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-ink-soft">الاسم</label>
            <input type="text" wire:model="name" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            @error('name') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">الفئة</label>
            <select wire:model="category_id" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                <option value="">اختر الفئة...</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
            @error('category_id') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">الوصف</label>
            <textarea wire:model="description" rows="4" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black"></textarea>
            @error('description') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-ink-soft">السعر</label>
                <input type="text" wire:model="price" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                @error('price') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-ink-soft">السعر قبل الخصم</label>
                <input type="text" wire:model="compare_at_price" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">الخيارات (مثال: المقاسات: S, M, L)</label>
            <input type="text" wire:model="options" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">حالة المخزون</label>
            <select wire:model="stock_status" class="mt-1.5 w-full rounded-lg border border-line-medium px-3.5 py-2.5 text-base focus:border-black focus:ring-1 focus:ring-black">
                <option value="in_stock">متوفر</option>
                <option value="pre_order">طلب مسبق</option>
                <option value="out_of_stock">نفدت الكمية</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-ink-soft">الصور (يتم قصها تلقائياً بشكل مربّع، يمكنك اختيار أكثر من صورة)</label>
            <input type="file" wire:model="newImages" accept="image/*" multiple class="mt-1 w-full text-sm">
            @error('newImages') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror
            @error('newImages.*') <p class="mt-1 text-sm text-discount">{{ $message }}</p> @enderror

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($existingImages as $existingImage)
                    <div x-data="{ confirming: false }" class="group relative h-20 w-20 flex-shrink-0">
                        <img src="{{ Storage::url($existingImage->path) }}" class="h-full w-full rounded-lg object-cover">
                        <button
                            type="button"
                            x-on:click="confirming = true"
                            class="absolute -top-1.5 -end-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-discount text-xs font-bold text-white"
                            aria-label="إزالة الصورة"
                        >
                            &times;
                        </button>

                        <div
                            x-show="confirming"
                            x-cloak
                            x-on:click.self="confirming = false"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
                        >
                            <div class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl">
                                <p class="text-base font-medium text-ink">إزالة هذه الصورة؟</p>
                                <div class="mt-4 flex gap-2">
                                    <button
                                        type="button"
                                        x-on:click="confirming = false; $wire.removeExistingImage({{ $existingImage->id }})"
                                        class="flex-1 rounded-lg bg-discount py-2 text-sm font-semibold text-white transition hover:opacity-90"
                                    >
                                        إزالة
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="confirming = false"
                                        class="flex-1 rounded-lg border border-line-medium py-2 text-sm font-semibold text-ink"
                                    >
                                        إلغاء
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                @foreach ($newImages as $newImage)
                    <img src="{{ $newImage->temporaryUrl() }}" class="h-20 w-20 flex-shrink-0 rounded-lg object-cover">
                @endforeach
            </div>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-primary py-3 text-base font-semibold text-white transition hover:bg-primary-hover disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">{{ $product ? 'حفظ التغييرات' : 'إرسال للمراجعة' }}</span>
            <span wire:loading wire:target="save">جارٍ الحفظ...</span>
        </button>
    </form>
</div>
