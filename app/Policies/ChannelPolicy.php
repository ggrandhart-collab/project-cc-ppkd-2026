<?php

namespace App\Policies;

use App\Models\Channel;
use App\Models\User;

class ChannelPolicy
{
    public function delete(User $user, Channel $channel): bool
    {
        return $user->isAdmin() || $user->id === $channel->user_id;
    }
}
