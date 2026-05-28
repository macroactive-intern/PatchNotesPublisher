<?php

namespace App\Policies;

use App\Models\PatchNote;
use App\Models\User;

class PatchNotePolicy
{
    /**
     * Determine whether patch notes can be listed.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the patch note can be viewed.
     */
    public function view(?User $user, PatchNote $patchNote): bool
    {
        return $patchNote->published || $user !== null;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PatchNote $patchNote): bool
    {
        return $user->isAdmin() || $user->isEditor();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PatchNote $patchNote): bool
    {
        return $user->isAdmin();
    }
}
