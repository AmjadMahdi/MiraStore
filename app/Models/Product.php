<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'price',
        'compare_at_price',
        'image_path',
        'options',
        'status',
        'stock_status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Product $product) {
            if ($product->isDirty() && ! $product->isDirty('status') && $product->getOriginal('status') === 'approved') {
                $product->status = 'pending';
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug')
            ->doNotGenerateSlugsOnUpdate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'price', 'status', 'stock_status'])
            ->logOnlyDirty();
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function interactionLogs()
    {
        return $this->hasMany(InteractionLog::class);
    }
}
