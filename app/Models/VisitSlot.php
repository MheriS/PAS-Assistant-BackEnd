<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitSlot extends Model
{
    protected $fillable = [
        'date',
        'session_name',
        'start_time',
        'end_time',
        'max_visitors',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'date' => 'date',
    ];
}
