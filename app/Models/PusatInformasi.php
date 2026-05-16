<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class PusatInformasi extends Model
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
        $path = base_path('chatbot-ai/DataResponse.csv');
        $data = self::all();
        
        $handle = fopen($path, 'w');
        fputcsv($handle, ['intent', 'keyword', 'jawaban']);
        
        foreach ($data as $row) {
            fputcsv($handle, [$row->intent, $row->keyword, $row->jawaban]);
        }
        
        fclose($handle);
    }
}
