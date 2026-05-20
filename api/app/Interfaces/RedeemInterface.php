<?php

namespace App\Interfaces;

use App\Models\Player;

interface RedeemInterface
{
    public function claimEmailReward(string $email, ?string $casinoId = null, ?string $rewardUid = null): array;

    public function claimPlayerReward(string $rewardUid, Player $player, ?string $casinoId = null): array;
}
