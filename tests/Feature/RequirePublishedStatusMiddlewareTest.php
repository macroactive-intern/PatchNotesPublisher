<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('guests can view published patch notes through the middleware', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Published notes',
        'content' => 'Visible release notes.',
        'published' => true,
    ]);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $patchNote->id);
});

test('guests are blocked from draft patch notes through the middleware', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Private draft notes.',
        'published' => false,
    ]);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertUnauthorized();
});

test('authenticated owners can access draft patch notes through the middleware', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Private draft notes.',
        'published' => false,
    ]);

    Sanctum::actingAs($owner);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $patchNote->id);
});
