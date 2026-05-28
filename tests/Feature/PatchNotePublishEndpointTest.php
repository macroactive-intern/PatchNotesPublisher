<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('admins can toggle a patch note published state', function () {
    $admin = User::factory()->admin()->create();
    $owner = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Draft notes',
        'content' => 'Draft content.',
        'published' => false,
    ]);

    Sanctum::actingAs($admin);

    $this->patchJson("/api/patch-notes/{$patchNote->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.published', true);

    $this->patchJson("/api/patch-notes/{$patchNote->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.published', false);
});

test('editors can toggle a patch note published state', function () {
    $editor = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $editor->id,
        'title' => 'Editor notes',
        'content' => 'Editor content.',
        'published' => false,
    ]);

    Sanctum::actingAs($editor);

    $this->patchJson("/api/patch-notes/{$patchNote->id}/publish")
        ->assertOk()
        ->assertJsonPath('data.published', true);
});

test('editors cannot toggle another editors patch note published state', function () {
    $editor = User::factory()->editor()->create();
    $otherEditor = User::factory()->editor()->create();
    $patchNote = PatchNote::create([
        'user_id' => $otherEditor->id,
        'title' => 'Others notes',
        'content' => 'Owned by another editor.',
        'published' => false,
    ]);

    Sanctum::actingAs($editor);

    $this->patchJson("/api/patch-notes/{$patchNote->id}/publish")
        ->assertForbidden();

    $this->assertDatabaseHas('patch_notes', [
        'id' => $patchNote->id,
        'published' => false,
    ]);
});

test('guests and viewers cannot toggle published state', function () {
    $owner = User::factory()->editor()->create();
    $viewer = User::factory()->viewer()->create();
    $patchNote = PatchNote::create([
        'user_id' => $owner->id,
        'title' => 'Protected notes',
        'content' => 'Protected content.',
        'published' => false,
    ]);

    $this->patchJson("/api/patch-notes/{$patchNote->id}/publish")
        ->assertUnauthorized();

    Sanctum::actingAs($viewer);

    $this->patchJson("/api/patch-notes/{$patchNote->id}/publish")
        ->assertForbidden();

    $this->assertDatabaseHas('patch_notes', [
        'id' => $patchNote->id,
        'published' => false,
    ]);
});
