<?php

use App\Models\PatchNote;
use App\Policies\PatchNotePolicy;
use Illuminate\Support\Facades\Gate;

test('patch note policy is registered', function () {
    expect(Gate::getPolicyFor(PatchNote::class))->toBeInstanceOf(PatchNotePolicy::class);
});
