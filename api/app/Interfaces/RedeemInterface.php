<?php

namespace App\Interfaces;

interface RedeemInterface
{
    public function claimEmailReward(string $email, ?string $casinoId = null): array;
}
