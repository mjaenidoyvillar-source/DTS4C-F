<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash; // ✅ Must be here (outside the class)
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Database\Seeders\DepartmentSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $hasUsername = Schema::hasColumn('users', 'username');

        $baseUsers = [
            // Existing accounts (ensure present)
            [
                'name' => 'Alice',
                'username' => 'alice',
                'email' => 'alice@example.com',
                'password' => Hash::make('password123'),
                'role' => 'owner',
            ],
            [
                'name' => 'Bob',
                'username' => 'bob',
                'email' => 'bob@example.com',
                'password' => Hash::make('secret456'),
                'role' => 'handler',
            ],
            // New accounts per roles
            [
                'name' => 'Admin User',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('adminpass'),
                'role' => 'admin',
            ],
            [
                'name' => 'Document Owner',
                'username' => 'owner',
                'email' => 'owner@example.com',
                'password' => Hash::make('ownerpass'),
                'role' => 'owner',
            ],
            [
                'name' => 'Document Handler',
                'username' => 'handler',
                'email' => 'handler@example.com',
                'password' => Hash::make('handlerpass'),
                'role' => 'handler',
            ],
            [
                'name' => 'Auditor User',
                'username' => 'auditor',
                'email' => 'auditor@example.com',
                'password' => Hash::make('auditorpass'),
                'role' => 'auditor',
            ],
            // Additional role accounts per department
            [
                'name' => 'IT Handler',
                'username' => 'it.handler',
                'email' => 'it.handler@example.com',
                'password' => Hash::make('handlerpass'),
                'role' => 'handler',
            ],
            [
                'name' => 'EDUC Handler',
                'username' => 'educ.handler',
                'email' => 'educ.handler@example.com',
                'password' => Hash::make('handlerpass'),
                'role' => 'handler',
            ],
            [
                'name' => 'IT Owner',
                'username' => 'it.owner',
                'email' => 'it.owner@example.com',
                'password' => Hash::make('ownerpass'),
                'role' => 'owner',
            ],
            [
                'name' => 'EDUC Owner',
                'username' => 'educ.owner',
                'email' => 'educ.owner@example.com',
                'password' => Hash::make('ownerpass'),
                'role' => 'owner',
            ],
        ];

        foreach ($baseUsers as $u) {
            $attributes = [];
            if ($hasUsername) {
                $attributes['username'] = $u['username'];
                $attributes['email'] = $u['email'] ?? null;
            } else {
                $attributes['name'] = $u['name'];
                $attributes['email'] = $u['email'];
            }
            $attributes['password'] = $u['password'];
            $attributes['role'] = $u['role'];

            // Upsert by unique identifier available
            if ($hasUsername) {
                User::updateOrCreate(['username' => $u['username']], $attributes);
            } else {
                User::updateOrCreate(['email' => $u['email']], $attributes);
            }
        }

        // Seed departments and assign user departments
        $this->call(DepartmentSeeder::class);
    }
}
