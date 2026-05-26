<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class UserOwnedPolicy
{
    public function view(User $user, Model $model): bool { return (int) $model->user_id === (int) $user->id; }
    public function update(User $user, Model $model): bool { return (int) $model->user_id === (int) $user->id; }
    public function delete(User $user, Model $model): bool { return (int) $model->user_id === (int) $user->id; }
}
