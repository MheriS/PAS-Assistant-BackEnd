<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('make:admin')]
#[Description('Create a new admin user interactively')]
class MakeAdmin extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = text('Full Name', 'Administrator', required: true);
        $username = text('Username', 'admin', required: true);
        $password = password('Password', required: true);
        $confirmPassword = password('Confirm Password', required: true);

        if ($password !== $confirmPassword) {
            $this->error('Passwords do not match!');
            return 1;
        }

        $user = User::updateOrCreate(
            ['username' => $username],
            [
                'name' => $name,
                'email' => $username . '@pas.com',
                'password' => Hash::make($password),
            ]
        );

        $this->info("Admin '{$username}' successfully created/updated!");
        return 0;
    }
}
