<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('publish gate allows admins and editors only', function () {
    expect(Gate::forUser(new User(['role' => 'admin']))->allows('publish'))->toBeTrue()
        ->and(Gate::forUser(new User(['role' => 'editor']))->allows('publish'))->toBeTrue()
        ->and(Gate::forUser(new User(['role' => 'viewer']))->allows('publish'))->toBeFalse();
});
