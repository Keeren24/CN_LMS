<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublicHoliday extends Model
{
    use HasFactory;

    protected $table = 'public_holidays';

    protected $fillable = [
        'date',
        'name',
        'description',
        'states',
        'type',
        'is_substitute',
        'source',
        'year',
    ];

    protected $casts = [
        'date' => 'date',
        'states' => 'array',
        'is_substitute' => 'boolean',
        'year' => 'integer',
    ];

    public function scopeApplicableTo(Builder $query, ?string $state): Builder
    {
        return $query->where(function (Builder $q) use ($state) {
            $q->whereNull('states');
            if ($state !== null) {
                $q->orWhereJsonContains('states', $state);
            }
        });
    }

    public function getIsNationalAttribute(): bool
    {
        return empty($this->states);
    }

    public static function dateNameMap(?string $state = null)
    {
        return static::query()
            ->when($state !== null, fn ($q) => $q->applicableTo($state))
            ->orderBy('date', 'asc')
            ->get(['date', 'name'])
            ->mapWithKeys(fn ($h) => [$h->date->format('Y-m-d') => $h->name]);
    }
}
