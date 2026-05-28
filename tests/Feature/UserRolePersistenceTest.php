<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('new users default to the viewer role', function () {
    $user = User::factory()->create();

    expect($user->role)->toBe('viewer')
        ->and($user->isViewer())->toBeTrue();
});

test('users can be created with named role states', function () {
    $admin = User::factory()->admin()->create();
    $editor = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();

    expect($admin->role)->toBe('admin')
        ->and($admin->isAdmin())->toBeTrue()
        ->and($editor->role)->toBe('editor')
        ->and($editor->isEditor())->toBeTrue()
        ->and($viewer->role)->toBe('viewer')
        ->and($viewer->isViewer())->toBeTrue();
});
