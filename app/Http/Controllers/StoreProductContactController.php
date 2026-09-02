<?php

namespace App\Http\Controllers;

use App\Models\InteractionLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StoreProductContactController extends Controller
{
    public function __invoke(Request $request, User $vendor, Product $product): RedirectResponse
    {
        abort_unless($vendor->isVendor() && $vendor->is_active, 404);
        abort_unless($product->vendor_id === $vendor->id, 404);
        abort_unless($product->status === 'approved', 404);
        abort_unless($vendor->whatsapp_number, 404);

        InteractionLog::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'action_type' => 'whatsapp_click',
            'ip_address' => (string) $request->ip(),
        ]);

        $phone = preg_replace('/[^0-9]/', '', $vendor->whatsapp_number);
        $message = rawurlencode("مرحباً! أنا مهتم بمنتج \"{$product->name}\" من متجر {$vendor->store_name}.");

        return redirect()->away("https://wa.me/{$phone}?text={$message}");
    }
}
