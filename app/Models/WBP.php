<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WBP extends Model
{
    protected $table = 'wbps';
    
    protected $fillable = [
        'nama',
        'no_regs',
        'jenis_kelamin',
        'perkara',
        'blok',
        'kamar',
    ];
}
