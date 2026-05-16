<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicineDelivery extends Model
{
    protected $fillable = [
        'registration_id',
        'wbp_id',
        'medicine_name',
        'quantity',
        'dosage',
        'approval_status',
        'rejection_reason',
        'delivery_status',
        'officer_id',
        'medical_officer_id',
        'delivery_officer_id',
        'notes',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function wbp()
    {
        return $this->belongsTo(WBP::class);
    }
}
