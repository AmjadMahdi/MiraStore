<?php

namespace App\Http\Controllers;

use App\Models\InteractionLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StoreProductController extends Controller
{
    public function __invoke(Request $request, User $vendor, Product $product): View
    {
        abort_unless($vendor->isVendor() && $vendor->is_active, 404);
        abort_unless($product->vendor_id === $vendor->id, 404);
        abort_unless($product->status === 'approved', 404);

        InteractionLog::create([
            'vendor_id' => $vendor->id,
            'product_id' => $product->id,
            'action_type' => 'view',
            'ip_address' => (string) $request->ip(),
        ]);

        $product->load('images');

        return view('store.product', ['vendor' => $vendor, 'product' => $product]);
    }
}
