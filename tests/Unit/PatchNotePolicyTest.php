<?php

use App\Models\PatchNote;
use App\Models\User;
use App\Policies\PatchNotePolicy;

test('anyone can view patch note listings', function () {
    $policy = new PatchNotePolicy();

    expect($policy->viewAny(null))->toBeTrue();
});

test('published notes are public and drafts require a user', function () {
    $policy = new PatchNotePolicy();
    $published = new PatchNote(['published' => true]);
    $draft = new PatchNote(['published' => false]);

    expect($policy->view(null, $published))->toBeTrue()
        ->and($policy->view(null, $draft))->toBeFalse()
        ->and($policy->view(new User(), $draft))->toBeTrue();
});

test('admins and editors can create or update patch notes', function () {
    $policy = new PatchNotePolicy();
    $patchNote = new PatchNote();
    $admin = new User(['role' => 'admin']);
    $editor = new User(['role' => 'editor']);
    $viewer = new User(['role' => 'viewer']);

    expect($policy->create($admin))->toBeTrue()
        ->and($policy->create($editor))->toBeTrue()
        ->and($policy->create($viewer))->toBeFalse()
        ->and($policy->update($admin, $patchNote))->toBeTrue()
        ->and($policy->update($editor, $patchNote))->toBeTrue()
        ->and($policy->update($viewer, $patchNote))->toBeFalse();
});

test('only admins can delete patch notes', function () {
    $policy = new PatchNotePolicy();
    $patchNote = new PatchNote();

    expect($policy->delete(new User(['role' => 'admin']), $patchNote))->toBeTrue()
        ->and($policy->delete(new User(['role' => 'editor']), $patchNote))->toBeFalse()
        ->and($policy->delete(new User(['role' => 'viewer']), $patchNote))->toBeFalse();
});
