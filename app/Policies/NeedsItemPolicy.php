<?php

namespace App\Policies;

use App\Models\NeedsItem;
use App\Models\User;

class NeedsItemPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, NeedsItem $needsItem): bool
    {
        return $user->id === $needsItem->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, NeedsItem $needsItem): bool
    {
        return $user->id === $needsItem->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, NeedsItem $needsItem): bool
    {
        return $user->id === $needsItem->user_id;
    }
}
