<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoneyDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'registration_id',
        'amount',
        'notes',
        'status',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }
}
