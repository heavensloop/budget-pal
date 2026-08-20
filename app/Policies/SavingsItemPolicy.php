<?php

namespace App\Policies;

use App\Models\SavingsItem;
use App\Models\User;

class SavingsItemPolicy
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
    public function view(User $user, SavingsItem $savingsItem): bool
    {
        return $user->id === $savingsItem->user_id;
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
    public function update(User $user, SavingsItem $savingsItem): bool
    {
        return $user->id === $savingsItem->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SavingsItem $savingsItem): bool
    {
        return $user->id === $savingsItem->user_id;
    }
}
