<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class StoreShowController extends Controller
{
    public function __invoke(User $vendor): View
    {
        abort_unless($vendor->isVendor() && $vendor->is_active, 404);

        return view('store.show', ['vendor' => $vendor]);
    }
}
