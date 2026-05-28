<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users default to the viewer role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe('viewer')
        ->and($user->isViewer())->toBeTrue();
});

test('users can be created with admin or editor roles', function () {
    $admin = User::factory()->admin()->create();
    $editor = User::factory()->editor()->create();

    expect($admin->role)->toBe('admin')
        ->and($admin->isAdmin())->toBeTrue()
        ->and($editor->role)->toBe('editor')
        ->and($editor->isEditor())->toBeTrue();
});
