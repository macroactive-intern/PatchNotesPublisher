<?php

use App\Models\User;

test('users can identify their assigned role', function () {
    $admin = new User(['role' => 'admin']);
    $editor = new User(['role' => 'editor']);
    $viewer = new User(['role' => 'viewer']);

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isEditor())->toBeFalse()
        ->and($admin->isViewer())->toBeFalse()
        ->and($editor->isEditor())->toBeTrue()
        ->and($editor->isAdmin())->toBeFalse()
        ->and($editor->isViewer())->toBeFalse()
        ->and($viewer->isViewer())->toBeTrue()
        ->and($viewer->isAdmin())->toBeFalse()
        ->and($viewer->isEditor())->toBeFalse();
});
