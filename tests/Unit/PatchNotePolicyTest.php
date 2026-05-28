<?php

use App\Models\PatchNote;
use App\Models\User;
use App\Policies\PatchNotePolicy;

test('anyone can view patch note listings', function () {
    $policy = new PatchNotePolicy();

    expect($policy->viewAny(null))->toBeTrue();
});

test('published notes are public', function () {
    $policy = new PatchNotePolicy();
    $published = new PatchNote(['published' => true]);

    expect($policy->view(null, $published))->toBeTrue();
});

test('admins have full access', function () {
    $policy = new PatchNotePolicy();
    $patchNote = new PatchNote(['published' => false]);
    $admin = new User(['role' => 'admin']);

    expect($policy->create($admin))->toBeTrue()
        ->and($policy->view($admin, $patchNote))->toBeTrue()
        ->and($policy->update($admin, $patchNote))->toBeTrue()
        ->and($policy->delete($admin, $patchNote))->toBeTrue();
});

test('editors can create and update their own patch notes only', function () {
    $policy = new PatchNotePolicy();
    $editor = new User(['role' => 'editor']);
    $editor->id = 1;
    $ownPatchNote = new PatchNote(['published' => false, 'user_id' => 1]);
    $otherPatchNote = new PatchNote(['published' => false, 'user_id' => 2]);

    expect($policy->create($editor))->toBeTrue()
        ->and($policy->view($editor, $ownPatchNote))->toBeTrue()
        ->and($policy->view($editor, $otherPatchNote))->toBeFalse()
        ->and($policy->update($editor, $ownPatchNote))->toBeTrue()
        ->and($policy->update($editor, $otherPatchNote))->toBeFalse()
        ->and($policy->delete($editor, $ownPatchNote))->toBeFalse();
});

test('viewers are read only', function () {
    $policy = new PatchNotePolicy();
    $published = new PatchNote(['published' => true]);
    $draft = new PatchNote(['published' => false]);
    $viewer = new User(['role' => 'viewer']);

    expect($policy->view($viewer, $published))->toBeTrue()
        ->and($policy->view($viewer, $draft))->toBeFalse()
        ->and($policy->create($viewer))->toBeFalse()
        ->and($policy->update($viewer, $published))->toBeFalse()
        ->and($policy->delete($viewer, $published))->toBeFalse();
});
