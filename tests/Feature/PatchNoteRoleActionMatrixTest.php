<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('role action permissions are enforced', function (string $role, string $action, string $target, int $status) {
    $actor = match ($role) {
        'admin' => User::factory()->admin()->create(),
        'editor' => User::factory()->editor()->create(),
        'viewer' => User::factory()->viewer()->create(),
        default => null,
    };

    $owner = User::factory()->editor()->create();
    $published = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Published notes',
        'content' => 'Published content.',
        'published' => true,
    ]);
    $draft = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Draft content.',
        'published' => false,
    ]);
    $own = PatchNote::create([
        'user_id' => $actor?->id ?? $owner->id,
        'title' => 'Own notes',
        'content' => 'Own content.',
        'published' => false,
    ]);

    if ($actor) {
        Sanctum::actingAs($actor);
    }

    $patchNote = match ($target) {
        'own' => $own,
        'published' => $published,
        default => $draft,
    };

    $response = match ($action) {
        'list' => $this->getJson('/api/patch-notes'),
        'view' => $this->getJson("/api/patch-notes/{$patchNote->id}"),
        'create' => $this->postJson('/api/patch-notes', [
            'title' => 'Created notes',
            'content' => 'Created content.',
        ]),
        'update' => $this->putJson("/api/patch-notes/{$patchNote->id}", [
            'title' => 'Updated notes',
        ]),
        'delete' => $this->deleteJson("/api/patch-notes/{$patchNote->id}"),
        'publish' => $this->patchJson("/api/patch-notes/{$patchNote->id}/publish"),
    };

    $response->assertStatus($status);
})->with([
    'guest can view list' => ['guest', 'list', 'published', 200],
    'guest can view published note' => ['guest', 'view', 'published', 200],
    'guest viewing drafts gets unauthorized' => ['guest', 'view', 'draft', 401],
    'guest cannot create' => ['guest', 'create', 'published', 401],
    'guest cannot update' => ['guest', 'update', 'published', 401],
    'guest cannot delete' => ['guest', 'delete', 'published', 401],
    'guest cannot publish' => ['guest', 'publish', 'published', 401],
    'admin can create' => ['admin', 'create', 'published', 201],
    'admin can update any note' => ['admin', 'update', 'draft', 200],
    'admin can delete any note' => ['admin', 'delete', 'draft', 204],
    'admin can publish any note' => ['admin', 'publish', 'draft', 200],
    'editor can create' => ['editor', 'create', 'published', 201],
    'editor can update own note' => ['editor', 'update', 'own', 200],
    'editor editing others notes gets forbidden' => ['editor', 'update', 'draft', 403],
    'editor delete attempt returns forbidden' => ['editor', 'delete', 'own', 403],
    'editor can publish own notes' => ['editor', 'publish', 'own', 200],
    'editor cannot publish others notes' => ['editor', 'publish', 'draft', 403],
    'viewer can view list' => ['viewer', 'list', 'published', 200],
    'viewer can view published note' => ['viewer', 'view', 'published', 200],
    'viewer cannot view draft' => ['viewer', 'view', 'draft', 403],
    'viewer cannot create' => ['viewer', 'create', 'published', 403],
    'viewer cannot update' => ['viewer', 'update', 'published', 403],
    'viewer cannot delete' => ['viewer', 'delete', 'published', 403],
    'viewer cannot publish' => ['viewer', 'publish', 'published', 403],
]);
