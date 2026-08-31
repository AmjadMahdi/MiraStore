<?php

use App\Models\Product;
use Illuminate\Support\Facades\Auth;
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

    #[Validate('nullable|image|max:4096')]
    public $image;

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            abort_unless($product->vendor_id === Auth::id(), 403);

            $this->product = $product;
            $this->name = $product->name;
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
            'image' => $this->product ? 'nullable|image|max:4096' : 'required|image|max:4096',
        ];
    }

    public function save(): void
    {
        $vendor = Auth::user();

        if (! $this->product && $vendor->max_products_limit !== null
            && $vendor->products()->count() >= $vendor->max_products_limit) {
            $this->addError('name', "You've reached your {$vendor->max_products_limit}-product limit.");

            return;
        }

        $this->validate();

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'price' => $this->price,
            'compare_at_price' => $this->compare_at_price !== '' ? $this->compare_at_price : null,
            'options' => $this->options !== '' ? $this->options : null,
            'stock_status' => $this->stock_status,
        ];

        if ($this->image) {
            $data['image_path'] = $this->storeSquareImage($this->image);
        }

        if ($this->product) {
            $this->product->update($data);
        } else {
            $data['vendor_id'] = $vendor->id;
            $this->product = Product::create($data);
        }

        $this->redirect(route('vendor.products.index'), navigate: true);
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

<div class="mx-auto max-w-lg p-6">
    <h1 class="text-xl font-semibold text-gray-800">
        {{ $product ? 'Edit product' : 'Add product' }}
    </h1>

    <form wire:submit="save" class="mt-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea wire:model="description" rows="4" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700">Price</label>
                <input type="text" wire:model="price" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                @error('price') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Compare-at price</label>
                <input type="text" wire:model="compare_at_price" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Options (e.g. Sizes: S, M, L)</label>
            <input type="text" wire:model="options" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Stock status</label>
            <select wire:model="stock_status" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                <option value="in_stock">In stock</option>
                <option value="pre_order">Pre-order</option>
                <option value="out_of_stock">Out of stock</option>
            </select>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Image (square crop applied automatically)</label>
            <input type="file" wire:model="image" accept="image/*" class="mt-1 w-full text-sm">
            @error('image') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            @if ($image)
                <img src="{{ $image->temporaryUrl() }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
            @elseif ($product)
                <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image_path) }}" class="mt-2 h-24 w-24 rounded-lg object-cover">
            @endif
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="w-full rounded-lg bg-rose-600 py-2 text-sm font-semibold text-white disabled:opacity-60"
        >
            <span wire:loading.remove wire:target="save">{{ $product ? 'Save changes' : 'Submit for approval' }}</span>
            <span wire:loading wire:target="save">Saving...</span>
        </button>
    </form>
</div>
