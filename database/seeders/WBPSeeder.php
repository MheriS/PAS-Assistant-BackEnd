<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WBPSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\WBP::create([
            'nama' => 'Budi Sudarsono',
            'no_regs' => 'BI/2023/12345',
            'jenis_kelamin' => 'Laki-laki',
            'perkara' => 'Pencurian',
            'blok' => 'A',
            'kamar' => '05',
            'foto' => 'https://i.pravatar.cc/150?u=BI202312345',
        ]);

        \App\Models\WBP::create([
            'nama' => 'Siti Aminah',
            'no_regs' => 'BII/2024/67890',
            'jenis_kelamin' => 'Perempuan',
            'perkara' => 'Narkotika',
            'blok' => 'Wanita',
            'kamar' => '01',
            'foto' => 'https://i.pravatar.cc/150?u=BII202467890',
        ]);
        
        \App\Models\WBP::create([
            'nama' => 'Anto Wijaya',
            'no_regs' => 'BI/2024/11223',
            'jenis_kelamin' => 'Laki-laki',
            'perkara' => 'Penipuan',
            'blok' => 'B',
            'kamar' => '12',
            'foto' => 'https://i.pravatar.cc/150?u=BI202411223',
        ]);
    }
}
