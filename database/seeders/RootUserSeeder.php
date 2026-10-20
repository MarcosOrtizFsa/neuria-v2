<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class RootUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = env('ROOT_EMAIL');
        $password = env('ROOT_PASSWORD');
        if (! $email || ! $password) {
            throw new RuntimeException('Configure ROOT_EMAIL y ROOT_PASSWORD en .env antes de crear el usuario ROOT.');
        }
        User::updateOrCreate(['email' => $email], ['role_id' => Role::where('code', 'ROOT')->value('id'), 'name' => env('ROOT_NAME', 'Root'), 'last_name' => env('ROOT_LAST_NAME', 'NEURIA'), 'password' => Hash::make($password), 'is_active' => true]);
    }
}
