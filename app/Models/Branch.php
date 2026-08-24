<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkgroup;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes, BelongsToWorkgroup;

    protected $table = 'branch';

    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'address',
        'state',
        'color',
        'status',
    ];

    public function colorTag(): string
    {
        return $this->color ?: '#16a34a';
    }

    public function classes()
    {
        return $this->hasMany(StudentClass::class, 'branch_id');
    }
}
