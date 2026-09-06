<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class VendorEditController extends Controller
{
    public function __invoke(User $vendor): View
    {
        abort_unless($vendor->isVendor(), 404);

        return view('admin.vendors-edit', ['vendor' => $vendor]);
    }
}
