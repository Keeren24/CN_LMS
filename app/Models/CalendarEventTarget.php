<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEventTarget extends Model
{
    use HasFactory;

    protected $table = 'calendar_event_targets';

    protected $fillable = ['calendar_event_id', 'target_type', 'target_id'];

    public const TYPE_CLASS = 'class';
    public const TYPE_STUDENT = 'student';
}
