<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('patch notes belong to users', function () {
    $user = User::factory()->create();
    $patchNote = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Launch notes',
        'content' => 'Initial release notes.',
        'published' => true,
    ]);

    expect($patchNote->user)->toBeInstanceOf(User::class)
        ->and($patchNote->user->is($user))->toBeTrue()
        ->and($patchNote->published)->toBeTrue();
});

test('users have many patch notes', function () {
    $user = User::factory()->create();

    PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Draft notes',
        'content' => 'Unpublished draft.',
    ]);

    expect($user->patchNotes)->toHaveCount(1)
        ->and($user->patchNotes->first())->toBeInstanceOf(PatchNote::class);
});
