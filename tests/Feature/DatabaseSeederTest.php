<?php

use App\Models\PatchNote;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('database seeder creates role users and demo patch notes', function () {
    $this->seed(DatabaseSeeder::class);

    expect(User::where('email', 'admin@example.com')->first())->role->toBe('admin')
        ->and(User::where('email', 'editor@example.com')->first())->role->toBe('editor')
        ->and(User::where('email', 'viewer@example.com')->first())->role->toBe('viewer')
        ->and(PatchNote::where('published', true)->count())->toBeGreaterThanOrEqual(1)
        ->and(PatchNote::where('published', false)->count())->toBeGreaterThanOrEqual(1);
});
