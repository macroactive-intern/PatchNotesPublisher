<?php

use App\Models\PatchNote;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('publish policy allows admins on any note and editors only on their own notes', function () {
    $admin = new User(['role' => 'admin']);

    $editor = new User(['role' => 'editor']);
    $editor->id = 1;

    $viewer = new User(['role' => 'viewer']);

    $ownNote = new PatchNote();
    $ownNote->user_id = 1;

    $othersNote = new PatchNote();
    $othersNote->user_id = 99;

    expect(Gate::forUser($admin)->allows('publish', $ownNote))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('publish', $othersNote))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('publish', $ownNote))->toBeTrue()
        ->and(Gate::forUser($editor)->allows('publish', $othersNote))->toBeFalse()
        ->and(Gate::forUser($viewer)->allows('publish', $ownNote))->toBeFalse();
});
