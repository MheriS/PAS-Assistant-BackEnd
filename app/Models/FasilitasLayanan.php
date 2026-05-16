<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FasilitasLayanan extends Model
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
        $path = base_path('chatbot-ai/DataFasilitas.csv');
        $data = self::all();
        
        $handle = fopen($path, 'w');
        fputcsv($handle, ['nama_fasilitas', 'lokasi', 'keterangan']);
        
        foreach ($data as $row) {
            fputcsv($handle, [$row->nama_fasilitas, $row->lokasi, $row->keterangan]);
        }
        
        fclose($handle);
    }
}
