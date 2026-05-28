<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('patch note responses use consistent JSON structure and HTTP status codes', function () {
    $admin = User::factory()->admin()->create();
    $patchNote = PatchNote::create([
        'user_id' => $admin->id,
        'title' => 'Existing notes',
        'content' => 'Existing content.',
        'published' => true,
    ]);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonStructure(['data']);

    $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['id', 'title', 'content', 'published', 'user_id']]);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/patch-notes', [
        'title' => 'Created notes',
        'content' => 'Created content.',
    ])
        ->assertStatus(201)
        ->assertJsonStructure(['data' => ['id', 'title', 'content', 'published', 'user_id']])
        ->json('data');

    $this->putJson("/api/patch-notes/{$created['id']}", [
        'title' => 'Updated notes',
    ])
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['id', 'title', 'content', 'published', 'user_id']]);

    $this->deleteJson("/api/patch-notes/{$created['id']}")
        ->assertStatus(204)
        ->assertContent('');
});

test('index returns notes newest first with pagination metadata', function () {
    $user = User::factory()->create();

    $older = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Older note',
        'content' => 'Created first.',
        'published' => true,
    ]);
    $newer = PatchNote::create([
        'user_id' => $user->id,
        'title' => 'Newer note',
        'content' => 'Created second.',
        'published' => true,
    ]);

    $response = $this->getJson('/api/patch-notes')->assertOk();

    $ids = $response->json('data.*.id');

    expect($ids[0])->toBe($newer->id)
        ->and($ids[1])->toBe($older->id);

    $response->assertJsonStructure([
        'data',
        'links' => ['first', 'last', 'prev', 'next'],
        'meta' => ['current_page', 'last_page', 'per_page', 'total'],
    ]);
});

test('patch note responses expose only safe fields and omit sensitive user data', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Public notes',
        'content' => 'Public content.',
        'published' => true,
    ]);

    $data = $this->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->json('data');

    expect($data)->toHaveKeys(['id', 'title', 'content', 'published', 'user_id', 'user'])
        ->and($data)->not->toHaveKey('email')
        ->and($data)->not->toHaveKey('role')
        ->and($data['user'])->toHaveKeys(['id', 'name'])
        ->and($data['user'])->not->toHaveKey('email')
        ->and($data['user'])->not->toHaveKey('role')
        ->and($data['user'])->not->toHaveKey('password');
});

test('public users see only published patch notes in the listing', function () {
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
        'content' => 'Hidden draft content.',
        'published' => false,
    ]);

    $this->getJson('/api/patch-notes')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['title' => 'Published notes'])
        ->assertJsonMissing(['title' => 'Draft notes']);
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
        ->assertJsonPath('data.id', $patchNote->id);

    Sanctum::actingAs($admin);

    $created = $this->postJson('/api/patch-notes', [
        'title' => 'Created notes',
        'content' => 'Created through the API.',
        'published' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Created notes')
        ->assertJsonPath('data.user_id', $admin->id)
        ->json('data');

    $this->putJson("/api/patch-notes/{$created['id']}", [
        'title' => 'Updated notes',
        'published' => false,
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated notes')
        ->assertJsonPath('data.published', false);

    $this->deleteJson("/api/patch-notes/{$created['id']}")
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
        ->assertJsonPath('data.id', $patchNote->id)
        ->assertJsonPath('data.published', true);
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

    Sanctum::actingAs($user);

    $this
        ->getJson("/api/patch-notes/{$patchNote->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $patchNote->id)
        ->assertJsonPath('data.published', false);
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

    Sanctum::actingAs($viewer);

    $this
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

    Sanctum::actingAs($viewer);

    $this->postJson('/api/patch-notes', [
        'title' => 'Viewer notes',
        'content' => 'Viewer content.',
    ])->assertForbidden();

    $this->putJson("/api/patch-notes/{$patchNote->id}", [
        'title' => 'Viewer update',
    ])->assertForbidden();

    $this->deleteJson("/api/patch-notes/{$patchNote->id}")
        ->assertForbidden();
});

test('editors can create patch notes', function () {
    $editor = User::factory()->editor()->create();

    Sanctum::actingAs($editor);

    $this->postJson('/api/patch-notes', [
        'title' => 'Editor notes',
        'content' => 'Created by an editor.',
        'published' => false,
    ])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Editor notes')
        ->assertJsonPath('data.user_id', $editor->id);
});

test('public users cannot create patch notes', function () {
    $user = User::factory()->create();

    $this->postJson('/api/patch-notes', [
        'title' => 'Public notes',
        'content' => 'Created without auth.',
    ])->assertUnauthorized();
});

test('public users cannot update or delete patch notes', function () {
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Protected notes',
        'content' => 'Protected content.',
        'published' => true,
    ]);

    $this->putJson("/api/patch-notes/{$patchNote->id}", [
        'title' => 'Public update',
    ])->assertUnauthorized();

    $this->deleteJson("/api/patch-notes/{$patchNote->id}")
        ->assertUnauthorized();
});

test('store ignores submitted user ids and assigns the authenticated user as owner', function () {
    $editor = User::factory()->editor()->create();
    $otherUser = User::factory()->create();

    Sanctum::actingAs($editor);

    $this->postJson('/api/patch-notes', [
        'user_id' => $otherUser->id,
        'title' => 'Owned notes',
        'content' => 'Ownership comes from auth.',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $editor->id);

    $this->assertDatabaseHas('patch_notes', [
        'title' => 'Owned notes',
        'user_id' => $editor->id,
    ]);
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

    Sanctum::actingAs($editor);

    $this->putJson("/api/patch-notes/{$ownPatchNote->id}", [
        'title' => 'Updated own notes',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated own notes');

    $this->putJson("/api/patch-notes/{$otherPatchNote->id}", [
        'title' => 'Updated other notes',
    ])->assertForbidden();

    $this->deleteJson("/api/patch-notes/{$ownPatchNote->id}")
        ->assertForbidden();
});

test('update ignores submitted user ids', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->editor()->create();
    $otherUser = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Original notes',
        'content' => 'Original content.',
        'published' => true,
    ]);

    Sanctum::actingAs($admin);

    $this->putJson("/api/patch-notes/{$patchNote->id}", [
        'user_id' => $otherUser->id,
        'title' => 'Updated notes',
    ])
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated notes')
        ->assertJsonPath('data.user_id', $owner->id);

    $this->assertDatabaseHas('patch_notes', [
        'id' => $patchNote->id,
        'title' => 'Updated notes',
        'user_id' => $owner->id,
    ]);
});
