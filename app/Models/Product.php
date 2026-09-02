<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Product extends Model
{
    use HasFactory, HasSlug, LogsActivity, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'description',
        'price',
        'compare_at_price',
        'image_path',
        'options',
        'status',
        'stock_status',
        'rejection_reason',
        'display_order',
        'is_pinned',
    ];

    /**
     * Mirrors the migration's DB-level defaults so a freshly-created
     * in-memory instance reflects them immediately (Eloquent does not
     * re-sync DB defaults after insert).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'stock_status' => 'in_stock',
        'display_order' => 0,
        'is_pinned' => false,
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'is_pinned' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Product $product) {
            $contentDirty = array_diff(array_keys($product->getDirty()), ['display_order', 'is_pinned']) !== [];

            if (
                $contentDirty
                && ! $product->isDirty('status')
                && in_array($product->getOriginal('status'), ['approved', 'rejected'], true)
                && ! (Auth::check() && Auth::user()->isSuperAdmin())
            ) {
                $product->status = 'pending';
                $product->rejection_reason = null;
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function interactionLogs()
    {
        return $this->hasMany(InteractionLog::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }
}
