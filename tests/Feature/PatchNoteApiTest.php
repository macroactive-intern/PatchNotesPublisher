<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('public users can list all patch notes', function () {
    $user = User::factory()->create();

    PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Published notes',
        'content' => 'Visible published content.',
        'published' => true,
    ]);

    PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Draft notes',
        'content' => 'Visible draft content.',
        'published' => false,
    ]);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonFragment(['title' => 'Published notes'])
        ->assertJsonFragment(['title' => 'Draft notes']);
});

test('patch note API routes support CRUD operations', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();
    $patchNote = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Initial notes',
        'content' => 'First published notes.',
        'published' => true,
    ]);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Initial notes']);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('id', $patchNote->id);

    $created = $this->actingAs($admin)->postJson('/api/patch-notes', [
        'user_id' => $user->id,
        'title' => 'Created notes',
        'content' => 'Created through the API.',
        'published' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('title', 'Created notes')
        ->json();

    $this->actingAs($admin)->putJson("/api/patch-notes/{$created['id']}", [
        'title' => 'Updated notes',
        'published' => false,
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Updated notes')
        ->assertJsonPath('published', false);

    $this->actingAs($admin)->deleteJson("/api/patch-notes/{$created['id']}")
        ->assertNoContent();

    $this->assertDatabaseMissing('patch_notes', ['id' => $created['id']]);
});

test('public users can view published patch notes', function () {
    $user = User::factory()->create();
    $patchNote = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Published notes',
        'content' => 'Public release notes.',
        'published' => true,
    ]);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('id', $patchNote->id)
        ->assertJsonPath('published', true);
});

test('public users cannot view draft patch notes', function () {
    $user = User::factory()->create();
    $patchNote = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Draft notes',
        'content' => 'Internal draft notes.',
        'published' => false,
    ]);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertUnauthorized();
});

test('owning editors can view draft patch notes', function () {
    $user = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Draft notes',
        'content' => 'Internal draft notes.',
        'published' => false,
    ]);

    $this->actingAs($user)
        ->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('id', $patchNote->id)
        ->assertJsonPath('published', false);
});

test('viewers cannot view draft patch notes', function () {
    $owner = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Internal draft notes.',
        'published' => false,
    ]);

    $this->actingAs($viewer)
        ->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertForbidden();
});

test('viewers cannot create update or delete patch notes', function () {
    $owner = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Published notes',
        'content' => 'Public notes.',
        'published' => true,
    ]);

    $this->actingAs($viewer)->postJson('/api/patch-notes', [
        'user_id' => $viewer->id,
        'title' => 'Viewer notes',
        'content' => 'Viewer content.',
    ])->assertForbidden();

    $this->actingAs($viewer)->putJson("/api/patch-notes/{$patchNote->id}", [
        'title' => 'Viewer update',
    ])->assertForbidden();

    $this->actingAs($viewer)->deleteJson("/api/patch-notes/{$patchNote->id}")
        ->assertForbidden();
});

test('editors can create patch notes', function () {
    $editor = User::factory()->editor()->create();

    $this->actingAs($editor)->postJson('/api/patch-notes', [
        'user_id' => $editor->id,
        'title' => 'Editor notes',
        'content' => 'Created by an editor.',
        'published' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('title', 'Editor notes');
});

test('public users cannot create patch notes', function () {
    $user = User::factory()->create();

    $this->postJson('/api/patch-notes', [
        'user_id' => $user->id,
        'title' => 'Public notes',
        'content' => 'Created without auth.',
    ])->assertForbidden();
});

test('editors can update their own patch notes but cannot update others or delete', function () {
    $editor = User::factory()->editor()->create();
    $otherEditor = User::factory()->editor()->create();
    $ownPatchNote = PatchNote::create([
        'user_id' => $editor->id,
        'title' => 'Own notes',
        'content' => 'Owned content.',
        'published' => false,
    ]);
    $otherPatchNote = PatchNote::create([
        'user_id' => $otherEditor->id,
        'title' => 'Other notes',
        'content' => 'Other content.',
        'published' => true,
    ]);

    $this->actingAs($editor)->putJson("/api/patch-notes/{$ownPatchNote->id}", [
        'title' => 'Updated own notes',
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Updated own notes');

    $this->actingAs($editor)->putJson("/api/patch-notes/{$otherPatchNote->id}", [
        'title' => 'Updated other notes',
    ])->assertForbidden();

    $this->actingAs($editor)->deleteJson("/api/patch-notes/{$ownPatchNote->id}")
        ->assertForbidden();
});
