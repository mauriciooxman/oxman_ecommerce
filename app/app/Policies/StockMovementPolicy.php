<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function view(User $user, StockMovement $movement): bool
    {
        return $user->ownsStore() && (int) $user->store_id === (int) $movement->store_id;
    }
}
