<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangLayanan extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function ($model) {
            self::syncToCsv();
        });

        static::deleted(function ($model) {
            self::syncToCsv();
        });
    }

    public static function syncToCsv()
    {
        $path = base_path('chatbot-ai/DataBarang.csv');
        $data = self::all();
        
        $handle = fopen($path, 'w');
        fputcsv($handle, ['nama_barang', 'status', 'keterangan']);
        
        foreach ($data as $row) {
            fputcsv($handle, [$row->nama_barang, $row->status, $row->keterangan]);
        }
        
        fclose($handle);
    }
}
