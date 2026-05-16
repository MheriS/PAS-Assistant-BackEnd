<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MedicineRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\MedicineRule::insert([
            [
                'title' => 'Kemasan Asli',
                'description' => 'Obat harus dalam kemasan asli yang belum terbuka/segel utuh.',
                'is_prohibited' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Tanpa Label',
                'description' => 'Dilarang membawa obat tanpa label/identitas yang jelas.',
                'is_prohibited' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Obat Cair Terbuka',
                'description' => 'Obat dalam bentuk cairan yang segelnya sudah terbuka dilarang masuk.',
                'is_prohibited' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Psikotropika Tanpa Resep',
                'description' => 'Dilarang keras membawa obat-obatan golongan psikotropika/narkotika tanpa resep dokter resmi.',
                'is_prohibited' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Obat Luar/Salep',
                'description' => 'Obat luar atau salep harus diperiksa keasliannya dan hanya yang bersumber dari apotek resmi.',
                'is_prohibited' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
