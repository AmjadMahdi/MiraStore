<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class ProductEditController extends Controller
{
    public function __invoke(Product $product): View
    {
        return view('admin.products-edit', ['product' => $product]);
    }
}
