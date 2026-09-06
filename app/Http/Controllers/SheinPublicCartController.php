<?php

namespace App\Http\Controllers;

use App\Models\SheinCart;
use Illuminate\View\View;

class SheinPublicCartController extends Controller
{
    public function __invoke(string $token): View
    {
        $cart = SheinCart::with('items')->where('public_token', $token)->firstOrFail();

        return view('shein.public-cart', ['cart' => $cart]);
    }
}
