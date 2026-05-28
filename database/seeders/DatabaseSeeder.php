<?php

namespace Database\Seeders;

use App\Models\PatchNote;
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
        $admin = User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        $editor = User::updateOrCreate(
            ['email' => 'editor@example.com'],
            [
                'name' => 'Editor User',
                'password' => 'password',
                'role' => 'editor',
            ]
        );

        $viewer = User::updateOrCreate(
            ['email' => 'viewer@example.com'],
            [
                'name' => 'Viewer User',
                'password' => 'password',
                'role' => 'viewer',
            ]
        );

        PatchNote::updateOrCreate(
            ['title' => 'Welcome to Patch Notes Publisher'],
            [
                'content' => 'A published demo patch note seeded for local API testing.',
                'published' => true,
                'user_id' => $editor->id,
            ]
        );

        PatchNote::updateOrCreate(
            ['title' => 'Internal Draft Release Notes'],
            [
                'content' => 'An unpublished demo draft visible only to authorized users.',
                'published' => false,
                'user_id' => $editor->id,
            ]
        );
    }
}
