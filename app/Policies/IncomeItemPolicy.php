<?php

namespace App\Policies;

use App\Models\IncomeItem;
use App\Models\User;

class IncomeItemPolicy
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
    public function view(User $user, IncomeItem $incomeItem): bool
    {
        return $user->id === $incomeItem->user_id;
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
    public function update(User $user, IncomeItem $incomeItem): bool
    {
        return $user->id === $incomeItem->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, IncomeItem $incomeItem): bool
    {
        return $user->id === $incomeItem->user_id;
    }
}
