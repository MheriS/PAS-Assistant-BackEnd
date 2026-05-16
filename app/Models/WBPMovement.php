<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WBPMovement extends Model
{
    protected $table = 'wbp_movements';

    protected $fillable = [
        'wbp_id',
        'type',
        'tanggal',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
    ];

    public function wbp()
    {
        return $this->belongsTo(WBP::class, 'wbp_id');
    }
}
