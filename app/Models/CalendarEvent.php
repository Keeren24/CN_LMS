<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory, BelongsToWorkgroup;

    protected $table = 'calendar_events';

    protected $fillable = [
        'workgroup_id',
        'title',
        'description',
        'start_date',
        'end_date',
        'all_day',
        'color',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'all_day' => 'boolean',
    ];

    public function targets()
    {
        return $this->hasMany(CalendarEventTarget::class);
    }

    public function isForEveryone(): bool
    {
        return $this->targets->isEmpty();
    }
}
