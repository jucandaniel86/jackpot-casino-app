<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponseClass;
use App\Interfaces\BonusAdminInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BonusAdminController extends Controller
{
	public function __construct(private BonusAdminInterface $service)
	{
	}

	public function rules(Request $request): JsonResponse
	{
		return ApiResponseClass::sendResponse(
			$this->service->listRules($request->all() + ['int_casino_id' => $this->forcedCasinoId($request)]),
			''
		);
	}

	public function rule(Request $request): JsonResponse
	{
		$request->validate(['id' => 'required|integer']);
		return ApiResponseClass::sendResponse(
			$this->service->getRule((int)$request->id, $this->forcedCasinoId($request)),
			''
		);
	}

	public function saveRule(Request $request): JsonResponse
	{
		$request->validate([
			'id' => 'nullable|integer',
			'int_casino_id' => 'nullable|string',
			'name' => 'required|string|max:255',
			'trigger_type' => 'required|string|max:40',
			'campaign_type' => 'nullable|in:register_bonus,deposit_bonus,custom',
			'reward_type' => 'required|in:fixed_amount,percentage',
			'reward_value' => 'required|numeric|min:0',
			'currency_id' => 'nullable|string|max:32',
			'currency_code' => 'nullable|string|max:16',
			'max_reward_amount' => 'nullable|numeric|min:0',
			'deposit_bonus_multiplier' => 'nullable|numeric|min:0',
			'wagering_multiplier' => 'nullable|integer|min:0',
			'real_wager_multiplier' => 'nullable|integer|min:0',
			'bonus_wager_multiplier' => 'nullable|integer|min:0',
			'consume_priority' => 'nullable|in:real_first,bonus_first',
			'win_destination' => 'nullable|in:bonus_wallet,real_wallet',
			'max_convert_to_real_ui' => 'nullable|numeric|min:0',
			'expire_after_days' => 'nullable|integer|min:0',
			'valid_from' => 'nullable|date',
			'valid_until' => 'nullable|date',
			'priority' => 'nullable|integer|min:0',
			'stacking_policy' => 'nullable|in:stackable,exclusive',
			'is_active' => 'nullable|boolean',
		]);

		DB::beginTransaction();
		try {
			$item = $this->service->saveRule(
				params: $request->all(),
				adminId: (int)$request->user()->id,
				forcedCasinoId: $this->forcedCasinoId($request)
			);
			DB::commit();
			return ApiResponseClass::sendResponse($item, 'Rule saved', 201);
		} catch (\Throwable $e) {
			ApiResponseClass::rollback($e);
			return ApiResponseClass::sendError(['error' => $e->getMessage()], 'Error');
		}
	}

	public function removeRule(Request $request): JsonResponse
	{
		$request->validate(['id' => 'required|integer']);
		return ApiResponseClass::sendResponse(
			$this->service->removeRule((int)$request->id, $this->forcedCasinoId($request)),
			'Rule deleted'
		);
	}

	public function toggleRule(Request $request): JsonResponse
	{
		$request->validate([
			'id' => 'required|integer',
			'is_active' => 'required|boolean',
		]);

		return ApiResponseClass::sendResponse(
			$this->service->toggleRule(
				id: (int)$request->id,
				isActive: (bool)$request->is_active,
				adminId: (int)$request->user()->id,
				forcedCasinoId: $this->forcedCasinoId($request)
			),
			'Rule updated'
		);
	}

	public function previewManual(Request $request): JsonResponse
	{
		$request->validate([
			'int_casino_id' => 'nullable|string',
			'amount_ui' => 'nullable|numeric|min:0',
			'filters' => 'nullable|array',
		]);
		return ApiResponseClass::sendResponse(
			$this->service->previewManual($request->all(), $this->forcedCasinoId($request)),
			''
		);
	}

	public function grantManual(Request $request): JsonResponse
	{
		$request->validate([
			'int_casino_id' => 'nullable|string',
			'name' => 'nullable|string|max:255',
			'mode' => 'required|in:rule,amount',
			'rule_id' => 'nullable|integer',
			'currency_id' => 'nullable|string|max:32',
			'amount_ui' => 'nullable|numeric|min:0',
			'wagering_multiplier' => 'nullable|integer|min:0',
			'expires_at' => 'nullable|date',
			'source_ref' => 'nullable|string|max:191',
			'filters' => 'nullable|array',
			'limit' => 'nullable|integer|min:1|max:5000',
		]);

		DB::beginTransaction();
		try {
			$res = $this->service->grantManual(
				params: $request->all(),
				adminId: (int)$request->user()->id,
				forcedCasinoId: $this->forcedCasinoId($request)
			);
			DB::commit();
			return ApiResponseClass::sendResponse($res, 'Manual grants completed', 201);
		} catch (\Throwable $e) {
			ApiResponseClass::rollback($e);
			return ApiResponseClass::sendError(['error' => $e->getMessage()], 'Error');
		}
	}

	public function grants(Request $request): JsonResponse
	{
		return ApiResponseClass::sendResponse(
			$this->service->listGrants($request->all(), $this->forcedCasinoId($request)),
			''
		);
	}

	public function grantEvents(Request $request): JsonResponse
	{
		$request->validate(['grant_id' => 'required|integer']);
		return ApiResponseClass::sendResponse(
			$this->service->grantEvents((int)$request->grant_id, $this->forcedCasinoId($request)),
			''
		);
	}

	public function stats(Request $request): JsonResponse
	{
		return ApiResponseClass::sendResponse(
			$this->service->stats($request->all(), $this->forcedCasinoId($request)),
			''
		);
	}

	public function runTest(Request $request): JsonResponse
	{
		$request->validate([
			'scenario' => 'required|in:register_flow_readiness,deposit_flow_readiness,withdraw_lock_consistency,wallet_consumption_simulation,all',
			'params' => 'nullable|array',
		]);

		DB::beginTransaction();
		try {
			$item = $this->service->runTestScenario(
				params: $request->all(),
				adminId: (int)$request->user()->id,
				forcedCasinoId: $this->forcedCasinoId($request)
			);
			DB::commit();
			return ApiResponseClass::sendResponse($item, 'Test scenario completed', 201);
		} catch (\Throwable $e) {
			ApiResponseClass::rollback($e);
			return ApiResponseClass::sendError(['error' => $e->getMessage()], 'Error');
		}
	}

	public function testRuns(Request $request): JsonResponse
	{
		return ApiResponseClass::sendResponse(
			$this->service->listTestRuns($request->all(), $this->forcedCasinoId($request)),
			''
		);
	}

	public function testRun(Request $request): JsonResponse
	{
		$request->validate(['id' => 'required|integer']);
		return ApiResponseClass::sendResponse(
			$this->service->getTestRun((int)$request->id, $this->forcedCasinoId($request)),
			''
		);
	}

	public function testRunLogs(Request $request): JsonResponse
	{
		$request->validate(['run_id' => 'required|integer']);
		return ApiResponseClass::sendResponse(
			$this->service->listTestRunLogs((int)$request->run_id, $this->forcedCasinoId($request)),
			''
		);
	}

	private function forcedCasinoId(Request $request): ?string
	{
		return (string)($request->user()?->int_casino_id ?? '') ?: null;
	}
}
