<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            WBPSeeder::class,
        ]);
        
        // User::factory(10)->create();

        // Admin creation is now handled via 'php artisan make:admin' for security
        /*
        User::factory()->create([
            'name' => 'Administrator',
            'email' => 'admin@pas.com',
            'username' => 'admin',
            'password' => bcrypt('admin123'),
        ]);
        */
    }
}
