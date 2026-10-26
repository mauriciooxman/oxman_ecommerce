<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function update(User $user, Category $category): bool
    {
        return $user->ownsStore() && (int) $user->store_id === (int) $category->store_id;
    }
}
