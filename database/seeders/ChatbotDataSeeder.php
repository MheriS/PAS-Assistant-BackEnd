<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\PusatInformasi;
use App\Models\BarangLayanan;
use App\Models\FasilitasLayanan;

class ChatbotDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Pusat Informasi
        $path = base_path('chatbot-ai/DataResponse.csv');
        if (file_exists($path)) {
            $data = array_map('str_getcsv', file($path));
            $header = array_shift($data);
            foreach ($data as $row) {
                if (count($row) >= 3) {
                    // Pakai Eloquent agar masuk, tapi jangan panggil static array-nya karena belum semua seeded, 
                    // atau biar aman insert biasa karena ngga papa over-write CSV yang isinya sama.
                    PusatInformasi::create([
                        'intent' => $row[0],
                        'keyword' => $row[1] ?: null,
                        'jawaban' => $row[2]
                    ]);
                }
            }
        }

        // 2. Seed Barang
        $path = base_path('chatbot-ai/DataBarang.csv');
        if (file_exists($path)) {
            $data = array_map('str_getcsv', file($path));
            $header = array_shift($data);
            foreach ($data as $row) {
                if (count($row) >= 3) {
                    BarangLayanan::create([
                        'nama_barang' => $row[0],
                        'status' => $row[1],
                        'keterangan' => $row[2]
                    ]);
                }
            }
        }


        // 4. Seed Fasilitas
        $path = base_path('chatbot-ai/DataFasilitas.csv');
        if (file_exists($path)) {
            $data = array_map('str_getcsv', file($path));
            $header = array_shift($data);
            foreach ($data as $row) {
                if (count($row) >= 3) {
                    FasilitasLayanan::create([
                        'nama_fasilitas' => $row[0],
                        'lokasi' => $row[1],
                        'keterangan' => $row[2]
                    ]);
                }
            }
        }
    }
}
