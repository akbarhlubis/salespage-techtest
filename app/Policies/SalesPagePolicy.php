<?php

namespace App\Policies;

use App\Models\SalesPage;
use App\Models\User;

class SalesPagePolicy
{
    public function view(User $user, SalesPage $salesPage): bool
    {
        return $user->id === $salesPage->user_id;
    }

    public function delete(User $user, SalesPage $salesPage): bool
    {
        return $user->id === $salesPage->user_id;
    }
}
