<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceRedemption extends Model
{
    use HasFactory, BelongsToWorkgroup;

    protected $table = 'marketplace_redemptions';

    protected $fillable = [
        'student_id',
        'marketplace_item_id',
        'item_name',
        'cost_paid',
        'status',
        'redeemed_at',
        'fulfilled_at',
        'fulfilled_by',
    ];

    protected function casts(): array
    {
        return [
            'redeemed_at'  => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function item()
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }
}
