<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('authorization failures return exact status codes', function () {
    $owner = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();
    $draft = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Private draft notes.',
        'published' => false,
    ]);

    $this->getJson("/api/patch-notes/{$draft->id}")
        ->assertStatus(401);

    Sanctum::actingAs($viewer);

    $this->getJson("/api/patch-notes/{$draft->id}")
        ->assertStatus(403);

    $this->getJson('/api/patch-notes/999999')
        ->assertStatus(404);
});

test('guests can list patch notes and view published patch notes', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Published notes',
        'content' => 'Visible release notes.',
        'published' => true,
    ]);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Published notes']);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('id', $patchNote->id);
});

test('guests cannot create update delete or view draft patch notes', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Private draft notes.',
        'published' => false,
    ]);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertUnauthorized();

    $this->postJson('/api/patch-notes', [
        'title' => 'Guest notes',
        'content' => 'No token.',
    ])->assertUnauthorized();

    $this->putJson("/api/patch-notes/{$patchNote->id}", [
        'title' => 'Guest update',
    ])->assertUnauthorized();

    $this->deleteJson("/api/patch-notes/{$patchNote->id}")
        ->assertUnauthorized();
});

test('admins can create update any delete any and view draft patch notes', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Private draft notes.',
        'published' => false,
    ]);

    Sanctum::actingAs($admin);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk();

    $created = $this->postJson('/api/patch-notes', [
        'title' => 'Admin notes',
        'content' => 'Created by admin.',
    ])
        ->assertCreated()
        ->assertJsonPath('user_id', $admin->id)
        ->json();

    $this->putJson("/api/patch-notes/{$patchNote->id}", [
        'title' => 'Admin update',
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Admin update');

    $this->deleteJson("/api/patch-notes/{$patchNote->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('patch_notes', ['id' => $patchNote->id]);
    $this->assertDatabaseHas('patch_notes', ['id' => $created['id']]);
});

test('editors can create and update their own notes only', function () {
    $editor = User::factory()->editor()->create();
    $otherEditor = User::factory()->editor()->create();
    $ownPatchNote = PatchNote::create([
        'user_id' => $editor->id,
        'title' => 'Own draft',
        'content' => 'Owned draft.',
        'published' => false,
    ]);
    $otherPatchNote = PatchNote::create([
        'user_id' => $otherEditor->id,
        'title' => 'Other draft',
        'content' => 'Someone else owns this.',
        'published' => false,
    ]);

    Sanctum::actingAs($editor);

    $this->postJson('/api/patch-notes', [
        'title' => 'Editor notes',
        'content' => 'Created by editor.',
    ])
        ->assertCreated()
        ->assertJsonPath('user_id', $editor->id);

    $this->getJson("/api/patch-notes/{$ownPatchNote->id}")
        ->assertOk();

    $this->putJson("/api/patch-notes/{$ownPatchNote->id}", [
        'title' => 'Updated own draft',
    ])
        ->assertOk()
        ->assertJsonPath('title', 'Updated own draft');

    $this->getJson("/api/patch-notes/{$otherPatchNote->id}")
        ->assertForbidden();

    $this->putJson("/api/patch-notes/{$otherPatchNote->id}", [
        'title' => 'Updated other draft',
    ])->assertForbidden();

    $this->deleteJson("/api/patch-notes/{$ownPatchNote->id}")
        ->assertForbidden();
});

test('viewers are read only', function () {
    $owner = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();
    $published = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Published notes',
        'content' => 'Visible release notes.',
        'published' => true,
    ]);
    $draft = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Private draft notes.',
        'published' => false,
    ]);

    Sanctum::actingAs($viewer);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonFragment(['title' => 'Published notes']);

    $this->getJson("/api/patch-notes/{$published->id}")
        ->assertOk();

    $this->getJson("/api/patch-notes/{$draft->id}")
        ->assertForbidden();

    $this->postJson('/api/patch-notes', [
        'title' => 'Viewer notes',
        'content' => 'Viewer content.',
    ])->assertForbidden();

    $this->putJson("/api/patch-notes/{$published->id}", [
        'title' => 'Viewer update',
    ])->assertForbidden();

    $this->deleteJson("/api/patch-notes/{$published->id}")
        ->assertForbidden();
});
