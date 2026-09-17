<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'path',
        'ip_address',
        'created_at',
    ];
}
