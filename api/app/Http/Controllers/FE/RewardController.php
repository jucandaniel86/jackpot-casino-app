<?php

namespace App\Http\Controllers\FE;

use App\Http\Controllers\Controller;
use App\Interfaces\RewardInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    public function __construct(private readonly RewardInterface $rewards)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->rewards->publicList($request->all()),
        ]);
    }
}
