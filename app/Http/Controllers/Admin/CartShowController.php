<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SheinCart;
use Illuminate\View\View;

class CartShowController extends Controller
{
    public function __invoke(SheinCart $cart): View
    {
        return view('admin.carts-show', ['cart' => $cart]);
    }
}
