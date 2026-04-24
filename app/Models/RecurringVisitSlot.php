<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringVisitSlot extends Model
{
    protected $fillable = [
        'day_of_week',
        'session_name',
        'start_time',
        'end_time',
        'max_visitors',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
