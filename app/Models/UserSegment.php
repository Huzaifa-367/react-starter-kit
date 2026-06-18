<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSegment extends Model
{
    use HasFactory;

    protected $table = 'user_segments';

    protected $fillable = [
        'name',
        'description',
        'filters',
        'user_count',
        'last_evaluated_at',
    ];

    protected $casts = [
        'filters' => 'json',
        'last_evaluated_at' => 'datetime',
        'user_count' => 'integer',
    ];
}
