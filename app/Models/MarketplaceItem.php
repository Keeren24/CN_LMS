<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceItem extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkgroup;

    public const CATEGORIES = [
        'merchandise'       => 'Merchandise',
        'workshop_discount' => 'Workshop Discount',
        'free_class'        => 'Free Class',
        'voucher'           => 'Voucher',
        'sticker'           => 'Sticker',
        'tshirt'            => 'T-Shirt',
        'badge'             => 'Badge',
        'other'             => 'Other',
    ];

    protected $table = 'marketplace_items';

    protected $fillable = [
        'name',
        'description',
        'image_path',
        'cost',
        'stock',
        'category',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function redemptions()
    {
        return $this->hasMany(MarketplaceRedemption::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function isInStock(): bool
    {
        return $this->stock === null || $this->stock > 0;
    }
}
