<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $email = env('LICENSE_ADMIN_EMAIL', 'admin@example.com');
        $password = env('LICENSE_ADMIN_PASSWORD', 'password');

        if (! User::where('email', $email)->exists()) {
            User::create([
                'name' => 'Admin',
                'email' => $email,
                'password' => Hash::make($password),
                'is_admin' => true,
            ]);
            $this->command->info("Admin user created: {$email}");
        } else {
            $this->command->info("Admin user already exists: {$email}");
        }
    }
}
