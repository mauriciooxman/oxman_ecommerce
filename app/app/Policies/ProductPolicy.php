<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function view(User $user, Product $product): bool
    {
        return $user->ownsStore() && (int) $user->store_id === (int) $product->store_id;
    }

    public function update(User $user, Product $product): bool
    {
        return $this->view($user, $product);
    }
}
