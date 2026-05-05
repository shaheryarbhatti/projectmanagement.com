<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ProjectManagementUserSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('SEED_USER_EMAIL', 'admin@projectmanagement.test')],
            [
                'name' => env('SEED_USER_NAME', 'Project Management Admin'),
                'password' => Hash::make(env('SEED_USER_PASSWORD', 'Admin@12345')),
            ]
        );
    }
}
