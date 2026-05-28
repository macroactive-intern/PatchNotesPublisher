<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('patch note API routes support CRUD operations', function () {
    $user = User::factory()->create();
    $patchNote = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Initial notes',
        'content' => 'First published notes.',
        'published' => false,
    ]);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Initial notes']);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('id', $patchNote->id);

    $created = $this->postJson('/api/patch-notes', [
        'user_id' => $user->id,
        'title' => 'Created notes',
        'content' => 'Created through the API.',
        'published' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('title', 'Created notes')
        ->json();

    $this->putJson("/api/patch-notes/{$created['id']}", [
        'title' => 'Updated notes',
        'published' => false,
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Updated notes')
        ->assertJsonPath('published', false);

    $this->deleteJson("/api/patch-notes/{$created['id']}")
        ->assertNoContent();

    $this->assertDatabaseMissing('patch_notes', ['id' => $created['id']]);
});
