<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    protected $fillable = [
        'id',
        'nik',
        'visitor_name',
        'visitor_phone',
        'visitor_address',
        'inmate_name',
        'inmate_number',
        'relationship',
        'visit_date',
        'visit_time',
        'room_block',
        'status',
        'pengikut_laki',
        'pengikut_perempuan',
        'pengikut_anak',
        'jumlah_pengikut',
    ];

    public $incrementing = false;
    protected $keyType = 'string';}
