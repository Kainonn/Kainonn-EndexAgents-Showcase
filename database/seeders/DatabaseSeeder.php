<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = [
            ['name' => 'Showcase Admin', 'email' => 'admin@example.test'],
            ['name' => 'Showcase Reviewer', 'email' => 'reviewer@example.test'],
            ['name' => 'Showcase Analyst', 'email' => 'analyst@example.test'],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['email' => $user['email']],
                [
                    'name' => $user['name'],
                    'password' => bcrypt('password'),
                ],
            );
        }
    }
}
