<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class StaffEditController extends Controller
{
    public function __invoke(User $staff): View
    {
        abort_unless($staff->isStaff(), 404);

        return view('admin.staff-edit', ['staff' => $staff]);
    }
}
