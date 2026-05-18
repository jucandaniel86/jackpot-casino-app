<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Interfaces\RedeemInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedeemController extends Controller
{
    public function __construct(private readonly RedeemInterface $redeem)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'casino_id' => ['nullable', 'string', 'max:128'],
        ]);

        $result = $this->redeem->claimEmailReward(
            email: $data['email'],
            casinoId: $data['casino_id'] ?? null,
        );

        $httpStatus = match ($result['status'] ?? null) {
            'not_found' => 404,
            'already_redeemed' => 409,
            'missing_wallet' => 422,
            default => 200,
        };

        return response()->json($result, $httpStatus);
    }
}
