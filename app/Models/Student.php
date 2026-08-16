<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use App\Models\Concerns\UppercasesName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkgroup, UppercasesName;

    protected $table = 'student';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'age',
        'parent_id',
        'cn_class_id',
        'school_name',
        'birth_date',
        'email',
        'phone_no',
        'cn_duration',
        'level',
        'status',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function class_info()
    {
        return $this->belongsTo(StudentClass::class, 'cn_class_id', 'id');
    }
}
