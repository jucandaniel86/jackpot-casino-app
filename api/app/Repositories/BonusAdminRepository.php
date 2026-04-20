<?php

namespace App\Repositories;

use App\Repositories\Bonus\Services\BonusGrantService;
use App\Repositories\Crypto\Support\CurrencyDecimals;
use App\Repositories\Crypto\Support\Money;
use App\Interfaces\BonusAdminInterface;
use App\Models\BonusGrant;
use App\Models\BonusGrantEvent;
use App\Models\BonusRule;
use App\Models\ManualBonusBatch;
use App\Models\Player;
use App\Models\WalletLedgerEntry;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BonusAdminRepository implements BonusAdminInterface
{
	public function __construct(
		private BonusGrantService $grantService
	)
	{
	}

	public function listRules(array $params = []): array
	{
		$q = BonusRule::query();
		$casinoId = $this->resolveCasinoId($params, null);
		if ($casinoId) {
			$q->where('int_casino_id', $casinoId);
		}

		if (isset($params['trigger_type']) && $params['trigger_type'] !== '') {
			$q->where('trigger_type', $params['trigger_type']);
		}
		if (isset($params['is_active']) && $params['is_active'] !== '') {
			$q->where('is_active', (int)$params['is_active']);
		}
		if (isset($params['search']) && strlen((string)$params['search']) > 1) {
			$s = (string)$params['search'];
			$q->where(function ($qq) use ($s) {
				$qq->where('name', 'like', "%{$s}%")
					->orWhere('trigger_type', 'like', "%{$s}%");
			});
		}

		return $q->orderBy('priority')->orderByDesc('id')->get()->toArray();
	}

	public function getRule(int $id, ?string $forcedCasinoId = null): array
	{
		$q = BonusRule::query()->where('id', $id);
		if ($forcedCasinoId) {
			$q->where('int_casino_id', $forcedCasinoId);
		}
		return (array)($q->first()?->toArray() ?? []);
	}

	public function saveRule(array $params = [], ?int $adminId = null, ?string $forcedCasinoId = null): array
	{
		$id = (int)($params['id'] ?? 0);
		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);

		$data = [
			'int_casino_id' => $casinoId,
			'name' => (string)$params['name'],
			'trigger_type' => (string)$params['trigger_type'],
			'campaign_type' => $params['campaign_type'] ?? null,
			'condition_json' => $this->normalizeJsonInput($params['condition_json'] ?? null),
			'reward_type' => (string)$params['reward_type'],
			'reward_value' => (string)$params['reward_value'],
			'currency_id' => $params['currency_id'] ?? null,
			'currency_code' => $params['currency_code'] ?? null,
			'max_reward_amount' => $params['max_reward_amount'] ?? null,
			'deposit_bonus_multiplier' => $params['deposit_bonus_multiplier'] ?? null,
			'wagering_multiplier' => (int)($params['wagering_multiplier'] ?? 0),
			'real_wager_multiplier' => isset($params['real_wager_multiplier'])
				? (int)$params['real_wager_multiplier']
				: null,
			'bonus_wager_multiplier' => isset($params['bonus_wager_multiplier'])
				? (int)$params['bonus_wager_multiplier']
				: null,
			'consume_priority' => $params['consume_priority'] ?? null,
			'win_destination' => $params['win_destination'] ?? null,
			'max_convert_to_real_ui' => $params['max_convert_to_real_ui'] ?? null,
			'expire_after_days' => isset($params['expire_after_days'])
				? (int)$params['expire_after_days']
				: null,
			'valid_from' => $params['valid_from'] ?? null,
			'valid_until' => $params['valid_until'] ?? null,
			'priority' => (int)($params['priority'] ?? 100),
			'stacking_policy' => (string)($params['stacking_policy'] ?? 'stackable'),
			'is_active' => (int)($params['is_active'] ?? 0),
		];

		if ($id <= 0) {
			$data['created_by'] = $adminId;
			$rule = BonusRule::query()->create($data);
			return $rule->toArray();
		}

		$q = BonusRule::query()->where('id', $id);
		if ($forcedCasinoId) {
			$q->where('int_casino_id', $forcedCasinoId);
		}
		$rule = $q->firstOrFail();
		$data['updated_by'] = $adminId;
		$rule->update($data);
		return $rule->fresh()->toArray();
	}

	public function removeRule(int $id, ?string $forcedCasinoId = null): array
	{
		$q = BonusRule::query()->where('id', $id);
		if ($forcedCasinoId) {
			$q->where('int_casino_id', $forcedCasinoId);
		}
		$rule = $q->firstOrFail();
		$rule->delete();
		return ['deleted' => true, 'id' => $id];
	}

	public function toggleRule(int $id, bool $isActive, ?int $adminId = null, ?string $forcedCasinoId = null): array
	{
		$q = BonusRule::query()->where('id', $id);
		if ($forcedCasinoId) {
			$q->where('int_casino_id', $forcedCasinoId);
		}
		$rule = $q->firstOrFail();
		$rule->is_active = $isActive;
		$rule->updated_by = $adminId;
		$rule->save();
		return $rule->toArray();
	}

	public function previewManual(array $params = [], ?string $forcedCasinoId = null): array
	{
		$playersQ = $this->playersQueryFromFilters($params, $forcedCasinoId);
		$count = (clone $playersQ)->count();
		$sample = (clone $playersQ)->select(['id', 'username', 'email'])->limit(20)->get()->toArray();

		$amountUi = (string)($params['amount_ui'] ?? '0');
		$estimatedTotalUi = bcmul((string)$count, $amountUi, 8);

		return [
			'estimated_players' => $count,
			'estimated_total_ui' => $estimatedTotalUi,
			'sample' => $sample,
		];
	}

	public function grantManual(array $params = [], ?int $adminId = null, ?string $forcedCasinoId = null): array
	{
		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);
		$players = $this->playersQueryFromFilters($params, $forcedCasinoId)
			->select(['id', 'username', 'email', 'int_casino_id'])
			->get();

		$mode = (string)($params['mode'] ?? 'rule');
		$sourceRef = (string)($params['source_ref'] ?? ('manual:' . Str::uuid()));
		$batch = ManualBonusBatch::query()->create([
			'int_casino_id' => $casinoId,
			'name' => (string)($params['name'] ?? ('Manual bonus ' . now()->toDateTimeString())),
			'segment_filter_json' => $this->normalizeJsonInput($params['filters'] ?? $params),
			'status' => 'processing',
			'estimated_players' => $players->count(),
			'created_by' => $adminId,
		]);

		$granted = 0;
		$skipped = 0;
		$errors = [];
		$skipReasons = [];

		if ($mode === 'rule') {
			$ruleId = (int)($params['rule_id'] ?? 0);
			$ruleQ = BonusRule::query()->where('id', $ruleId);
			if ($forcedCasinoId) {
				$ruleQ->where('int_casino_id', $forcedCasinoId);
			}
			$rule = $ruleQ->firstOrFail();

				foreach ($players as $player) {
					try {
						$res = $this->grantService->tryGrantRuleToPlayer(
							player: $player,
							rule: $rule,
							sourceType: 'manual_segment',
							sourceRef: $sourceRef,
							extraMeta: ['batch_id' => $batch->id, 'admin_id' => $adminId]
						);
						if ($res['grant']) {
							$granted++;
						} else {
							$skipped++;
							$skipReasons[] = [
								'player_id' => $player->id,
								'username' => $player->username,
								'reason' => $res['skip_reason'] ?? 'unknown_skip_reason',
							];
						}
					} catch (\Throwable $e) {
						$skipped++;
						$errors[] = ['player_id' => $player->id, 'error' => $e->getMessage()];
					}
				}
			} else {
			$currencyId = (string)($params['currency_id'] ?? config('crypto.defaultCurrency'));
			$amountUi = (string)($params['amount_ui'] ?? '0');
			$expiresAt = $params['expires_at'] ?? null;
			$wageringMultiplier = (int)($params['wagering_multiplier'] ?? 0);

				foreach ($players as $player) {
					try {
						$res = $this->grantService->tryGrantManualToPlayer(
							player: $player,
							currencyId: $currencyId,
							amountUi: $amountUi,
							wageringMultiplier: $wageringMultiplier,
							expiresAt: $expiresAt,
							sourceRef: $sourceRef,
							meta: ['batch_id' => $batch->id, 'admin_id' => $adminId]
						);
						if ($res['grant']) {
							$granted++;
						} else {
							$skipped++;
							$skipReasons[] = [
								'player_id' => $player->id,
								'username' => $player->username,
								'reason' => $res['skip_reason'] ?? 'unknown_skip_reason',
							];
						}
					} catch (\Throwable $e) {
						$skipped++;
						$errors[] = ['player_id' => $player->id, 'error' => $e->getMessage()];
					}
				}
		}

		$batch->status = count($errors) > 0 ? 'completed' : 'completed';
		$batch->save();

		return [
			'batch_id' => $batch->id,
			'source_ref' => $sourceRef,
			'estimated_players' => $players->count(),
			'granted' => $granted,
			'skipped' => $skipped,
			'skip_reasons' => array_slice($skipReasons, 0, 200),
			'errors' => array_slice($errors, 0, 50),
		];
	}

	public function listGrants(array $params = [], ?string $forcedCasinoId = null): array
	{
		$q = BonusGrant::query()
			->leftJoin('players', 'players.id', '=', 'bonus_grants.player_id')
			->select('bonus_grants.*', 'players.username as player_username');
		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);
		if ($casinoId) {
			$q->where('bonus_grants.int_casino_id', $casinoId);
		}
		if (!empty($params['status'])) {
			$q->where('bonus_grants.status', $params['status']);
		}
		if (!empty($params['source_type'])) {
			$q->where('bonus_grants.source_type', $params['source_type']);
		}
		if (!empty($params['player_id'])) {
			$q->where('bonus_grants.player_id', (int)$params['player_id']);
		}
		if (!empty($params['rule_id'])) {
			$q->where('bonus_grants.bonus_rule_id', (int)$params['rule_id']);
		}
		if (!empty($params['from'])) {
			$q->where('bonus_grants.created_at', '>=', $params['from']);
		}
		if (!empty($params['to'])) {
			$q->where('bonus_grants.created_at', '<=', $params['to']);
		}

		$limit = min(200, max(1, (int)($params['length'] ?? 50)));
		$page = max(1, (int)($params['page'] ?? 1));
		$offset = ($page - 1) * $limit;

		$total = (clone $q)->count();
		$items = $q->orderByDesc('bonus_grants.id')->offset($offset)->limit($limit)->get()->toArray();
		$items = array_map(function (array $item) {
			$internalDecimals = $this->resolveCurrencyInternalDecimals(
				(string)($item['currency_id'] ?? ''),
				(string)($item['currency_code'] ?? '')
			);
			$displayDecimals = $this->resolveCurrencyUiDecimals(
				(string)($item['currency_id'] ?? ''),
				(string)($item['currency_code'] ?? '')
			);

			$item['display_decimals'] = $displayDecimals;
			$item['amount_granted_ui'] = Money::baseToUi((string)($item['amount_granted_base'] ?? '0'), $internalDecimals, $displayDecimals);
			$item['amount_remaining_ui'] = Money::baseToUi((string)($item['amount_remaining_base'] ?? '0'), $internalDecimals, $displayDecimals);
			$item['wagering_required_ui'] = Money::baseToUi((string)($item['wagering_required_base'] ?? '0'), $internalDecimals, $displayDecimals);
			$item['wagering_progress_ui'] = Money::baseToUi((string)($item['wagering_progress_base'] ?? '0'), $internalDecimals, $displayDecimals);

			return $item;
		}, $items);

		return [
			'total' => $total,
			'page' => $page,
			'length' => $limit,
			'items' => $items,
		];
	}

	public function grantEvents(int $grantId, ?string $forcedCasinoId = null): array
	{
		$grantQ = BonusGrant::query()->where('id', $grantId);
		if ($forcedCasinoId) {
			$grantQ->where('int_casino_id', $forcedCasinoId);
		}
		$grant = $grantQ->firstOrFail();
		$internalDecimals = CurrencyDecimals::internalForCurrency((string)$grant->currency_id);
		$displayDecimals = CurrencyDecimals::uiForCurrency((string)$grant->currency_id);

		$events = BonusGrantEvent::query()
			->where('bonus_grant_id', $grant->id)
			->orderByDesc('id')
			->get();

		if ($events->isEmpty()) {
			return [];
		}

		$ledgerContext = $this->collectGrantEventLedgerContext($events);
		$wallets = $this->collectWalletsForLedgerEntries((array)($ledgerContext['wallet_ids'] ?? []));

		return $events->map(function (BonusGrantEvent $event) use (
			$grant,
			$internalDecimals,
			$displayDecimals,
			$ledgerContext,
			$wallets
		) {
			$eventArray = $event->toArray();

			$human = $this->humanizeGrantEventType((string)($eventArray['event_type'] ?? ''));
			$amountBase = (string)($eventArray['amount_base'] ?? '0');

			$eventType = (string)($eventArray['event_type'] ?? '');
			$debugByRef = $this->resolveLedgerEntriesForEvent($eventArray, $ledgerContext['entries_by_reference'] ?? []);
			$walletDebug = $this->buildGrantEventWalletDebug($eventType, $debugByRef, $wallets, $internalDecimals, $displayDecimals);
			$eventArray['wallet_balance_debug'] = $walletDebug;
			$eventArray['event_label'] = $human['label'];
			$eventArray['event_description'] = $human['description'];
			$eventArray['event_effect'] = $human['effect'];
			$eventArray['amount_ui'] = Money::baseToUi($amountBase, $internalDecimals, $displayDecimals);
			$eventArray['currency_code'] = (string)($grant->currency_code ?? '');

			$totalDebugBase = '0';
			foreach ($walletDebug as $wallet) {
				$totalDebugBase = bcadd($totalDebugBase, (string)($wallet['delta_base'] ?? '0'), 0);
			}
			$eventArray['wallet_split_total_base'] = $totalDebugBase;
			$eventArray['wallet_split_total_ui'] = Money::baseToUi($totalDebugBase, $internalDecimals, $displayDecimals);

			return $eventArray;
		})->toArray();
	}

	public function stats(array $params = [], ?string $forcedCasinoId = null): array
	{
		$q = BonusGrant::query();
		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);
		if ($casinoId) {
			$q->where('bonus_grants.int_casino_id', $casinoId);
		}
		if (!empty($params['from'])) {
			$q->where('bonus_grants.created_at', '>=', $params['from']);
		}
		if (!empty($params['to'])) {
			$q->where('bonus_grants.created_at', '<=', $params['to']);
		}

		$totalGrants = (clone $q)->count();
		$totalGrantedBase = (string)((clone $q)->sum('amount_granted_base') ?: '0');
		$totalRemainingBase = (string)((clone $q)->sum('amount_remaining_base') ?: '0');
		$totalConsumedBase = bcsub($totalGrantedBase, $totalRemainingBase, 0);
		$consumptionRatePct = bccomp($totalGrantedBase, '0', 0) > 0
			? bcmul(bcdiv($totalConsumedBase, $totalGrantedBase, 8), '100', 2)
			: '0.00';

		$byStatus = (clone $q)
			->select('status', DB::raw('COUNT(*) as c'))
			->groupBy('status')
			->pluck('c', 'status')
			->toArray();

		$bySource = (clone $q)
			->select('source_type', DB::raw('COUNT(*) as c'))
			->groupBy('source_type')
			->pluck('c', 'source_type')
			->toArray();

		$currencyBreakdown = (clone $q)
			->select(
				'currency_id',
				'currency_code',
				DB::raw('COUNT(*) as grants_count'),
				DB::raw('COALESCE(SUM(amount_granted_base), 0) as granted_base'),
				DB::raw('COALESCE(SUM(amount_remaining_base), 0) as remaining_base')
			)
			->groupBy('currency_id', 'currency_code')
			->orderByDesc('grants_count')
			->get()
			->map(function ($row) {
				$grantedBase = (string)($row->granted_base ?? '0');
				$remainingBase = (string)($row->remaining_base ?? '0');
				return [
					'currency_id' => (string)($row->currency_id ?? ''),
					'currency_code' => (string)($row->currency_code ?? ''),
					'grants_count' => (int)($row->grants_count ?? 0),
					'granted_base' => $grantedBase,
					'remaining_base' => $remainingBase,
					'consumed_base' => bcsub($grantedBase, $remainingBase, 0),
				];
			})
			->toArray();

		$dailyTrend = (clone $q)
			->selectRaw('DATE(created_at) as day')
			->selectRaw('COUNT(*) as grants_count')
			->selectRaw('COALESCE(SUM(amount_granted_base), 0) as granted_base')
			->selectRaw('COALESCE(SUM(amount_remaining_base), 0) as remaining_base')
			->groupBy(DB::raw('DATE(created_at)'))
			->orderBy('day')
			->get()
			->map(function ($row) {
				$grantedBase = (string)($row->granted_base ?? '0');
				$remainingBase = (string)($row->remaining_base ?? '0');
				return [
					'day' => (string)$row->day,
					'grants_count' => (int)($row->grants_count ?? 0),
					'granted_base' => $grantedBase,
					'remaining_base' => $remainingBase,
					'consumed_base' => bcsub($grantedBase, $remainingBase, 0),
				];
			})
			->toArray();

		$topRules = (clone $q)
			->leftJoin('bonus_rules', 'bonus_rules.id', '=', 'bonus_grants.bonus_rule_id')
			->select(
				'bonus_grants.bonus_rule_id',
				'bonus_rules.name as rule_name',
				DB::raw('COUNT(*) as grants_count'),
				DB::raw('COALESCE(SUM(bonus_grants.amount_granted_base), 0) as granted_base'),
				DB::raw('COALESCE(SUM(bonus_grants.amount_remaining_base), 0) as remaining_base')
			)
			->groupBy('bonus_grants.bonus_rule_id', 'bonus_rules.name')
			->orderByDesc('grants_count')
			->limit(10)
			->get()
			->map(function ($row) {
				$grantedBase = (string)($row->granted_base ?? '0');
				$remainingBase = (string)($row->remaining_base ?? '0');
				return [
					'rule_id' => $row->bonus_rule_id ? (int)$row->bonus_rule_id : null,
					'rule_name' => (string)($row->rule_name ?? 'Manual / No rule'),
					'grants_count' => (int)($row->grants_count ?? 0),
					'granted_base' => $grantedBase,
					'remaining_base' => $remainingBase,
					'consumed_base' => bcsub($grantedBase, $remainingBase, 0),
				];
			})
			->toArray();

		$aggregateCurrencyCode = null;
		$aggregateInternalDecimals = 9;
		$aggregateDisplayDecimals = 2;
		if (count($currencyBreakdown) === 1) {
			$aggregateCurrencyCode = (string)($currencyBreakdown[0]['currency_code'] ?? '');
			$aggregateInternalDecimals = $this->resolveCurrencyInternalDecimals(
				(string)($currencyBreakdown[0]['currency_id'] ?? ''),
				$aggregateCurrencyCode
			);
			$aggregateDisplayDecimals = $this->resolveCurrencyUiDecimals(
				(string)($currencyBreakdown[0]['currency_id'] ?? ''),
				$aggregateCurrencyCode
			);
		}

		$currencyBreakdown = array_map(function (array $row) {
			$internalDecimals = $this->resolveCurrencyInternalDecimals(
				(string)($row['currency_id'] ?? ''),
				(string)($row['currency_code'] ?? '')
			);
			$displayDecimals = $this->resolveCurrencyUiDecimals(
				(string)($row['currency_id'] ?? ''),
				(string)($row['currency_code'] ?? '')
			);
			$row['granted_ui'] = Money::baseToUi((string)($row['granted_base'] ?? '0'), $internalDecimals, $displayDecimals);
			$row['remaining_ui'] = Money::baseToUi((string)($row['remaining_base'] ?? '0'), $internalDecimals, $displayDecimals);
			$row['consumed_ui'] = Money::baseToUi((string)($row['consumed_base'] ?? '0'), $internalDecimals, $displayDecimals);
			return $row;
		}, $currencyBreakdown);

		if ($aggregateCurrencyCode) {
			$dailyTrend = array_map(function (array $row) use ($aggregateInternalDecimals, $aggregateDisplayDecimals) {
				$row['granted_ui'] = Money::baseToUi((string)($row['granted_base'] ?? '0'), $aggregateInternalDecimals, $aggregateDisplayDecimals);
				$row['remaining_ui'] = Money::baseToUi((string)($row['remaining_base'] ?? '0'), $aggregateInternalDecimals, $aggregateDisplayDecimals);
				$row['consumed_ui'] = Money::baseToUi((string)($row['consumed_base'] ?? '0'), $aggregateInternalDecimals, $aggregateDisplayDecimals);
				return $row;
			}, $dailyTrend);

			$topRules = array_map(function (array $row) use ($aggregateInternalDecimals, $aggregateDisplayDecimals) {
				$row['granted_ui'] = Money::baseToUi((string)($row['granted_base'] ?? '0'), $aggregateInternalDecimals, $aggregateDisplayDecimals);
				$row['remaining_ui'] = Money::baseToUi((string)($row['remaining_base'] ?? '0'), $aggregateInternalDecimals, $aggregateDisplayDecimals);
				$row['consumed_ui'] = Money::baseToUi((string)($row['consumed_base'] ?? '0'), $aggregateInternalDecimals, $aggregateDisplayDecimals);
				return $row;
			}, $topRules);
		}

		return [
			'total_grants' => $totalGrants,
			'total_granted_base' => $totalGrantedBase,
			'total_remaining_base' => $totalRemainingBase,
			'total_consumed_base' => $totalConsumedBase,
			'total_granted_ui' => $aggregateCurrencyCode ? Money::baseToUi($totalGrantedBase, $aggregateInternalDecimals, $aggregateDisplayDecimals) : null,
			'total_remaining_ui' => $aggregateCurrencyCode ? Money::baseToUi($totalRemainingBase, $aggregateInternalDecimals, $aggregateDisplayDecimals) : null,
			'total_consumed_ui' => $aggregateCurrencyCode ? Money::baseToUi($totalConsumedBase, $aggregateInternalDecimals, $aggregateDisplayDecimals) : null,
			'aggregate_currency_code' => $aggregateCurrencyCode,
			'consumption_rate_pct' => $consumptionRatePct,
			'active_grants' => (int)($byStatus['active'] ?? 0),
			'consumed_grants' => (int)($byStatus['consumed'] ?? 0),
			'expired_grants' => (int)($byStatus['expired'] ?? 0),
			'revoked_grants' => (int)($byStatus['revoked'] ?? 0),
			'status_breakdown' => $byStatus,
			'source_breakdown' => $bySource,
			'currency_breakdown' => $currencyBreakdown,
			'daily_trend' => $dailyTrend,
			'top_rules' => $topRules,
		];
	}

	public function runTestScenario(array $params = [], ?int $adminId = null, ?string $forcedCasinoId = null): array
	{
		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);
		$scenario = (string)($params['scenario'] ?? 'all');
		$scenarioList = $scenario === 'all'
			? ['register_flow_readiness', 'deposit_flow_readiness', 'withdraw_lock_consistency', 'wallet_consumption_simulation']
			: [$scenario];

		$uuid = (string)Str::uuid();
		$runId = (int)DB::table('bonus_test_runs')->insertGetId([
			'uuid' => $uuid,
			'int_casino_id' => $casinoId,
			'scenario' => $scenario,
			'status' => 'running',
			'requested_by' => $adminId,
			'started_at' => now(),
			'meta' => json_encode(['params' => $params]),
			'created_at' => now(),
			'updated_at' => now(),
		]);

		$this->logTestRun($runId, 'info', 'run_started', 'Test run started', [
			'scenario' => $scenario,
			'casino_id' => $casinoId,
		]);

		$results = [];
		$hasFailure = false;
		foreach ($scenarioList as $item) {
			$ok = match ($item) {
				'register_flow_readiness' => $this->testRegisterFlowReadiness($runId, $casinoId),
				'deposit_flow_readiness' => $this->testDepositFlowReadiness($runId, $casinoId),
				'withdraw_lock_consistency' => $this->testWithdrawLockConsistency($runId, $casinoId),
				'wallet_consumption_simulation' => $this->testWalletConsumptionSimulation($runId, $casinoId, (array)($params['params'] ?? [])),
				default => false,
			};

			$results[$item] = $ok ? 'passed' : 'failed';
			if (!$ok) {
				$hasFailure = true;
			}
		}

		$status = $hasFailure ? 'failed' : 'passed';
		DB::table('bonus_test_runs')->where('id', $runId)->update([
			'status' => $status,
			'summary_json' => json_encode(['results' => $results]),
			'finished_at' => now(),
			'updated_at' => now(),
		]);

		$this->logTestRun($runId, $hasFailure ? 'error' : 'success', 'run_finished', 'Test run finished', [
			'status' => $status,
			'results' => $results,
		]);

		return $this->getTestRun($runId, $forcedCasinoId);
	}

	public function listTestRuns(array $params = [], ?string $forcedCasinoId = null): array
	{
		$q = DB::table('bonus_test_runs as r')
			->leftJoin('users as u', 'u.id', '=', 'r.requested_by')
			->select([
				'r.*',
				'u.name as requested_by_name',
			])
			->orderByDesc('r.id');

		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);
		if ($casinoId) {
			$q->where('r.int_casino_id', $casinoId);
		}

		if (!empty($params['status'])) {
			$q->where('r.status', (string)$params['status']);
		}
		if (!empty($params['scenario'])) {
			$q->where('r.scenario', (string)$params['scenario']);
		}

		$limit = min(100, max(1, (int)($params['length'] ?? 20)));
		$page = max(1, (int)($params['page'] ?? 1));
		$offset = ($page - 1) * $limit;

		$total = (clone $q)->count();
		$items = $q->offset($offset)->limit($limit)->get()->map(function ($row) {
			$item = (array)$row;
			$item['summary_json'] = $this->normalizeJsonInput($item['summary_json'] ?? null);
			$item['meta'] = $this->normalizeJsonInput($item['meta'] ?? null);
			return $item;
		})->toArray();

		return [
			'total' => $total,
			'page' => $page,
			'length' => $limit,
			'items' => $items,
		];
	}

	public function getTestRun(int $id, ?string $forcedCasinoId = null): array
	{
		$q = DB::table('bonus_test_runs as r')
			->leftJoin('users as u', 'u.id', '=', 'r.requested_by')
			->select([
				'r.*',
				'u.name as requested_by_name',
			])
			->where('r.id', $id);

		if ($forcedCasinoId) {
			$q->where('r.int_casino_id', $forcedCasinoId);
		}

		$item = (array)($q->first() ?? []);
		if ($item === []) {
			return [];
		}

		$item['summary_json'] = $this->normalizeJsonInput($item['summary_json'] ?? null);
		$item['meta'] = $this->normalizeJsonInput($item['meta'] ?? null);
		return $item;
	}

	public function listTestRunLogs(int $runId, ?string $forcedCasinoId = null): array
	{
		$runQ = DB::table('bonus_test_runs')->where('id', $runId);
		if ($forcedCasinoId) {
			$runQ->where('int_casino_id', $forcedCasinoId);
		}
		$run = $runQ->first();
		if (!$run) {
			return [];
		}

		return DB::table('bonus_test_run_logs')
			->where('run_id', $runId)
			->orderBy('id')
			->get()
			->map(function ($row) {
				$item = (array)$row;
				$item['context_json'] = $this->normalizeJsonInput($item['context_json'] ?? null);
				return $item;
			})
			->toArray();
	}

	private function testRegisterFlowReadiness(int $runId, ?string $casinoId): bool
	{
		$this->logTestRun($runId, 'info', 'register_scan_start', 'Scanning register bonus rules...');

		$q = DB::table('bonus_rules')
			->where('is_active', 1)
			->where('trigger_type', 'register');
		if ($casinoId) {
			$q->where('int_casino_id', $casinoId);
		}
		$rules = $q->get();

		if ($rules->isEmpty()) {
			$this->logTestRun($runId, 'error', 'register_rules_missing', 'No active register rules found.');
			return false;
		}

		$ok = true;
		foreach ($rules as $rule) {
			$errors = [];
			if ((string)$rule->reward_type !== 'fixed_amount') {
				$errors[] = 'reward_type should be fixed_amount';
			}
			if ((float)$rule->reward_value <= 0) {
				$errors[] = 'reward_value must be > 0';
			}

			$bonusWager = isset($rule->bonus_wager_multiplier)
				? (int)$rule->bonus_wager_multiplier
				: (int)$rule->wagering_multiplier;
			if ($bonusWager <= 0) {
				$errors[] = 'bonus_wager_multiplier/wagering_multiplier must be > 0';
			}

			if (!in_array((string)$rule->consume_priority, ['real_first', 'bonus_first'], true)) {
				$errors[] = 'consume_priority should be real_first or bonus_first';
			}
			if (!in_array((string)$rule->win_destination, ['bonus_wallet', 'real_wallet'], true)) {
				$errors[] = 'win_destination should be bonus_wallet or real_wallet';
			}

			if ($errors !== []) {
				$ok = false;
				$this->logTestRun($runId, 'error', 'register_rule_invalid', 'Register rule validation failed', [
					'rule_id' => $rule->id,
					'name' => $rule->name,
					'errors' => $errors,
				]);
			} else {
				$this->logTestRun($runId, 'success', 'register_rule_ok', 'Register rule is valid', [
					'rule_id' => $rule->id,
					'name' => $rule->name,
				]);
			}
		}

		return $ok;
	}

	private function testDepositFlowReadiness(int $runId, ?string $casinoId): bool
	{
		$this->logTestRun($runId, 'info', 'deposit_scan_start', 'Scanning deposit bonus rules...');

		$q = DB::table('bonus_rules')
			->where('is_active', 1)
			->where('trigger_type', 'deposit');
		if ($casinoId) {
			$q->where('int_casino_id', $casinoId);
		}
		$rules = $q->get();

		if ($rules->isEmpty()) {
			$this->logTestRun($runId, 'error', 'deposit_rules_missing', 'No active deposit rules found.');
			return false;
		}

		$ok = true;
		foreach ($rules as $rule) {
			$errors = [];
			if ((float)$rule->reward_value <= 0) {
				$errors[] = 'reward_value must be > 0';
			}
			if ((int)$rule->real_wager_multiplier < 0) {
				$errors[] = 'real_wager_multiplier must be >= 0';
			}
			$bonusWager = isset($rule->bonus_wager_multiplier)
				? (int)$rule->bonus_wager_multiplier
				: (int)$rule->wagering_multiplier;
			if ($bonusWager <= 0) {
				$errors[] = 'bonus_wager_multiplier/wagering_multiplier must be > 0';
			}
			if (!in_array((string)$rule->consume_priority, ['real_first', 'bonus_first'], true)) {
				$errors[] = 'consume_priority should be real_first or bonus_first';
			}
			if (!in_array((string)$rule->win_destination, ['bonus_wallet', 'real_wallet'], true)) {
				$errors[] = 'win_destination should be bonus_wallet or real_wallet';
			}

			if ($errors !== []) {
				$ok = false;
				$this->logTestRun($runId, 'error', 'deposit_rule_invalid', 'Deposit rule validation failed', [
					'rule_id' => $rule->id,
					'name' => $rule->name,
					'errors' => $errors,
				]);
			} else {
				$this->logTestRun($runId, 'success', 'deposit_rule_ok', 'Deposit rule is valid', [
					'rule_id' => $rule->id,
					'name' => $rule->name,
				]);
			}
		}

		return $ok;
	}

	private function testWithdrawLockConsistency(int $runId, ?string $casinoId): bool
	{
		$this->logTestRun($runId, 'info', 'withdraw_lock_scan_start', 'Checking withdraw lock consistency...');

		$q = DB::table('bonus_grants')->whereIn('status', ['active', 'granted', 'consumed']);
		if ($casinoId) {
			$q->where('int_casino_id', $casinoId);
		}
		$grants = $q->get();

		if ($grants->isEmpty()) {
			$this->logTestRun($runId, 'warn', 'withdraw_lock_no_grants', 'No eligible bonus grants found for consistency check.');
			return true;
		}

		$ok = true;
		foreach ($grants as $grant) {
			$bonusDone = bccomp((string)$grant->wagering_progress_base, (string)$grant->wagering_required_base, 0) >= 0;
			$realDone = bccomp((string)$grant->real_wager_progress_base, (string)$grant->real_wager_required_base, 0) >= 0;
			$shouldBeLocked = !($bonusDone && $realDone);
			$isLocked = (int)$grant->withdraw_lock === 1;

			if ($shouldBeLocked !== $isLocked) {
				$ok = false;
				$this->logTestRun($runId, 'error', 'withdraw_lock_mismatch', 'Withdraw lock mismatch for grant.', [
					'grant_id' => $grant->id,
					'is_locked' => $isLocked,
					'should_be_locked' => $shouldBeLocked,
					'bonus_progress' => (string)$grant->wagering_progress_base,
					'bonus_required' => (string)$grant->wagering_required_base,
					'real_progress' => (string)$grant->real_wager_progress_base,
					'real_required' => (string)$grant->real_wager_required_base,
				]);
			}
		}

		if ($ok) {
			$this->logTestRun($runId, 'success', 'withdraw_lock_consistent', 'Withdraw lock state is consistent for scanned grants.');
		}
		return $ok;
	}

	private function testWalletConsumptionSimulation(int $runId, ?string $casinoId, array $params = []): bool
	{
		$this->logTestRun($runId, 'info', 'wallet_sim_start', 'Starting wallet consumption simulation...');

		$depositUi = isset($params['deposit_ui']) ? (string)$params['deposit_ui'] : '210';
		$betUi = isset($params['bet_ui']) ? (string)$params['bet_ui'] : '120';
		$winUi = isset($params['win_ui']) ? (string)$params['win_ui'] : '180';
		$realBalanceUi = isset($params['real_balance_ui']) ? (string)$params['real_balance_ui'] : '300';

		$q = DB::table('bonus_rules')
			->where('is_active', 1)
			->whereIn('trigger_type', ['register', 'deposit']);
		if ($casinoId) {
			$q->where('int_casino_id', $casinoId);
		}

		$rules = $q->orderBy('priority')->orderBy('id')->get();
		if ($rules->isEmpty()) {
			$this->logTestRun($runId, 'warn', 'wallet_sim_no_rules', 'No active register/deposit rules to simulate.');
			return true;
		}

		$scale = 8;
		foreach ($rules as $rule) {
			$rewardType = (string)$rule->reward_type;
			$rewardValue = (string)$rule->reward_value;
			$maxReward = $rule->max_reward_amount !== null ? (string)$rule->max_reward_amount : null;

			$bonusGrantUi = '0';
			if ($rewardType === 'fixed_amount') {
				$bonusGrantUi = $rewardValue;
			} else {
				$bonusGrantUi = bcdiv(bcmul($depositUi, $rewardValue, $scale), '100', $scale);
			}
			if ($maxReward !== null && bccomp($bonusGrantUi, $maxReward, $scale) === 1) {
				$bonusGrantUi = $maxReward;
			}

			if ((string)$rule->trigger_type === 'deposit' && $rule->deposit_bonus_multiplier !== null) {
				$bonusGrantUi = bcmul($depositUi, (string)$rule->deposit_bonus_multiplier, $scale);
			}

			$consumePriority = in_array((string)$rule->consume_priority, ['real_first', 'bonus_first'], true)
				? (string)$rule->consume_priority
				: 'bonus_first';
			$winDestination = in_array((string)$rule->win_destination, ['bonus_wallet', 'real_wallet'], true)
				? (string)$rule->win_destination
				: 'real_wallet';

			$remainingBet = $betUi;
			$realUsedUi = '0';
			$bonusUsedUi = '0';

			if ($consumePriority === 'real_first') {
				$realUsedUi = $this->minUiValue($remainingBet, $realBalanceUi, $scale);
				$remainingBet = bcsub($remainingBet, $realUsedUi, $scale);
				$bonusUsedUi = $this->minUiValue($remainingBet, $bonusGrantUi, $scale);
				$remainingBet = bcsub($remainingBet, $bonusUsedUi, $scale);
			} else {
				$bonusUsedUi = $this->minUiValue($remainingBet, $bonusGrantUi, $scale);
				$remainingBet = bcsub($remainingBet, $bonusUsedUi, $scale);
				$realUsedUi = $this->minUiValue($remainingBet, $realBalanceUi, $scale);
				$remainingBet = bcsub($remainingBet, $realUsedUi, $scale);
			}

			$realWalletWinUi = $winDestination === 'real_wallet' ? $winUi : '0';
			$bonusWalletWinUi = $winDestination === 'bonus_wallet' ? $winUi : '0';
			$winTargetWallet = $winDestination === 'real_wallet' ? 'real_wallet' : 'bonus_wallet';

			$bonusWagerMultiplier = $rule->bonus_wager_multiplier !== null
				? (int)$rule->bonus_wager_multiplier
				: (int)$rule->wagering_multiplier;
			$realWagerMultiplier = (int)($rule->real_wager_multiplier ?? 0);

			$bonusRequiredUi = bcmul($bonusGrantUi, (string)max(0, $bonusWagerMultiplier), $scale);
			$realRequiredUi = bcmul($depositUi, (string)max(0, $realWagerMultiplier), $scale);

			$bonusProgressUi = $bonusUsedUi;
			$realProgressUi = $realUsedUi;

			$maxConvertUi = $rule->max_convert_to_real_ui !== null ? (string)$rule->max_convert_to_real_ui : '0';
			$remainingBonusUi = bcadd(bcsub($bonusGrantUi, $bonusUsedUi, $scale), $bonusWalletWinUi, $scale);
			$bonusDone = bccomp($bonusProgressUi, $bonusRequiredUi, $scale) >= 0 || bccomp($bonusRequiredUi, '0', $scale) <= 0;
			$realDone = bccomp($realProgressUi, $realRequiredUi, $scale) >= 0 || bccomp($realRequiredUi, '0', $scale) <= 0;
			$conversionActivated = $bonusDone && $realDone;

			$convertibleUi = '0';
			$maxConvertApplied = false;
			if ($conversionActivated) {
				$capUi = bccomp($maxConvertUi, '0', $scale) === 1 ? $maxConvertUi : $remainingBonusUi;
				$convertibleUi = $this->minUiValue($remainingBonusUi, $capUi, $scale);
				$maxConvertApplied = bccomp($maxConvertUi, '0', $scale) === 1 && bccomp($remainingBonusUi, $maxConvertUi, $scale) === 1;
			}

			$this->logTestRun($runId, 'info', 'wallet_sim_consumption', 'Wallet consumption split', [
				'rule_id' => $rule->id,
				'consume_priority' => $consumePriority,
				'bet_ui' => $betUi,
				'real_wallet_consumed_ui' => $realUsedUi,
				'bonus_wallet_consumed_ui' => $bonusUsedUi,
				'uncovered_bet_ui' => $remainingBet,
			]);

			$this->logTestRun($runId, 'info', 'wallet_sim_win_route', 'Win routed to wallet', [
				'rule_id' => $rule->id,
				'win_ui' => $winUi,
				'win_target_wallet' => $winTargetWallet,
				'win_to_real_wallet_ui' => $realWalletWinUi,
				'win_to_bonus_wallet_ui' => $bonusWalletWinUi,
			]);

			$this->logTestRun($runId, 'info', 'wallet_sim_rule', 'Wallet simulation for rule', [
				'rule_id' => $rule->id,
				'name' => $rule->name,
				'trigger_type' => $rule->trigger_type,
				'consume_priority' => $consumePriority,
				'win_destination' => $winDestination,
				'bet_ui' => $betUi,
				'real_used_ui' => $realUsedUi,
				'bonus_used_ui' => $bonusUsedUi,
				'win_to_real_wallet_ui' => $realWalletWinUi,
				'win_to_bonus_wallet_ui' => $bonusWalletWinUi,
				'bonus_required_ui' => $bonusRequiredUi,
				'bonus_progress_ui' => $bonusProgressUi,
				'real_required_ui' => $realRequiredUi,
				'real_progress_ui' => $realProgressUi,
				'max_convert_to_real_ui' => $maxConvertUi,
				'conversion_activated' => $conversionActivated,
				'convertible_to_real_ui' => $convertibleUi,
			]);

			if ($conversionActivated) {
				$this->logTestRun($runId, 'success', 'wallet_sim_conversion', 'max_convert_to_real eligible', [
					'rule_id' => $rule->id,
					'remaining_bonus_ui' => $remainingBonusUi,
					'max_convert_to_real_ui' => $maxConvertUi,
					'max_convert_cap_applied' => $maxConvertApplied,
					'convertible_to_real_ui' => $convertibleUi,
				]);
			} else {
				$this->logTestRun($runId, 'warn', 'wallet_sim_not_completed', 'Wagering not completed yet for conversion', [
					'rule_id' => $rule->id,
					'bonus_left_ui' => bccomp($bonusRequiredUi, $bonusProgressUi, $scale) === 1 ? bcsub($bonusRequiredUi, $bonusProgressUi, $scale) : '0',
					'real_left_ui' => bccomp($realRequiredUi, $realProgressUi, $scale) === 1 ? bcsub($realRequiredUi, $realProgressUi, $scale) : '0',
				]);
			}
		}

		return true;
	}

	private function minUiValue(string $a, string $b, int $scale = 8): string
	{
		return bccomp($a, $b, $scale) <= 0 ? $a : $b;
	}

	private function logTestRun(
		int $runId,
		string $level,
		string $stepCode,
		string $message,
		array $context = []
	): void
	{
		DB::table('bonus_test_run_logs')->insert([
			'run_id' => $runId,
			'level' => $level,
			'step_code' => $stepCode,
			'message' => $message,
			'context_json' => $context ? json_encode($context) : null,
			'created_at' => now(),
			'updated_at' => now(),
		]);
	}

	private function playersQueryFromFilters(array $params, ?string $forcedCasinoId = null)
	{
		$casinoId = $this->resolveCasinoId($params, $forcedCasinoId);
		$q = Player::query();
		if ($casinoId) {
			$q->where('int_casino_id', $casinoId);
		}

		$filters = $params['filters'] ?? $params;
		if (!is_array($filters)) {
			$filters = [];
		}

		if (!empty($filters['player_ids']) && is_array($filters['player_ids'])) {
			$q->whereIn('id', array_map('intval', $filters['player_ids']));
		}
		if (!empty($filters['usernames']) && is_array($filters['usernames'])) {
			$q->whereIn('username', $filters['usernames']);
		}
		if (!empty($filters['emails']) && is_array($filters['emails'])) {
			$q->whereIn('email', $filters['emails']);
		}
		if (isset($filters['active']) && $filters['active'] !== '') {
			$q->where('active', (int)$filters['active']);
		}
		if (!empty($filters['registered_from'])) {
			$q->where('created_at', '>=', $filters['registered_from']);
		}
		if (!empty($filters['registered_to'])) {
			$q->where('created_at', '<=', $filters['registered_to']);
		}
		if (!empty($filters['min_total_deposit_ui'])) {
			$minUi = (string)$filters['min_total_deposit_ui'];
			$q->whereIn('id', function ($sub) use ($minUi) {
				$sub->from('transaction as t')
					->join('wallets as w', 'w.id', '=', 't.wallet_id')
					->where('t.type', 'deposit')
					->where('t.status', 'confirmed')
					->groupBy('w.holder_id')
					->havingRaw('SUM(t.amount) >= ?', [$minUi])
					->selectRaw('w.holder_id');
			});
		}

		$limit = min(5000, max(1, (int)($params['limit'] ?? 1000)));
		$q->limit($limit);

		return $q;
	}

	private function resolveCasinoId(array $params = [], ?string $forcedCasinoId = null): ?string
	{
		return $forcedCasinoId ?: ($params['int_casino_id'] ?? null);
	}

	private function resolveCurrencyInternalDecimals(string $currencyId, string $currencyCode = ''): int
	{
		$currencyId = trim($currencyId);
		$currencyCode = trim($currencyCode);

		if ($currencyId !== '') {
			return CurrencyDecimals::internalForCurrency($currencyId);
		}
		if ($currencyCode !== '') {
			return CurrencyDecimals::internalForCurrency($currencyCode);
		}
		if ($currencyId === '' && $currencyCode === '') {
			return 9;
		}
		return 9;
	}

	private function resolveCurrencyUiDecimals(string $currencyId, string $currencyCode = ''): int
	{
		$currencyId = trim($currencyId);
		$currencyCode = trim($currencyCode);

		if ($currencyId !== '') {
			return CurrencyDecimals::uiForCurrency($currencyId);
		}
		if ($currencyCode !== '') {
			return CurrencyDecimals::uiForCurrency($currencyCode);
		}
		return 2;
	}

	private function humanizeGrantEventType(string $eventType): array
	{
		return match ($eventType) {
			'issued' => [
				'label' => 'Bonus issued',
				'description' => 'The bonus was issued and credited to the bonus wallet.',
				'effect' => 'credit',
			],
			'bet_debit' => [
				'label' => 'Bonus bet placed',
				'description' => 'A portion of the bonus balance was used for a bet.',
				'effect' => 'debit',
			],
			'win_credit' => [
				'label' => 'Bonus win credited',
				'description' => 'A win amount was credited to the bonus wallet.',
				'effect' => 'credit',
			],
			'expired' => [
				'label' => 'Bonus expired',
				'description' => 'The remaining bonus has expired and can no longer be used.',
				'effect' => 'debit',
			],
			'revoked' => [
				'label' => 'Bonus revoked',
				'description' => 'The bonus was revoked according to the configured rules.',
				'effect' => 'debit',
			],
			'wagering_completed' => [
				'label' => 'Wagering completed',
				'description' => 'The wagering requirements were completed.',
				'effect' => 'info',
			],
			default => [
				'label' => ucfirst(str_replace('_', ' ', $eventType ?: 'unknown')),
				'description' => 'A bonus event was recorded in the system.',
				'effect' => 'info',
			],
		};
	}

	private function collectGrantEventLedgerContext(\Illuminate\Support\Collection $events): array
	{
		$referenceByType = [];
		$walletIds = [];

		foreach ($events as $event) {
			$refType = (string)($event->reference_type ?? '');
			$refId = (string)($event->reference_id ?? '');
			if ($refType === '' || $refId === '') {
				continue;
			}

			$referenceByType[$refType][] = $refId;
		}

		foreach ($referenceByType as $type => $ids) {
			$referenceByType[$type] = array_values(array_unique(array_filter($ids, static fn($id) => $id !== '')));
		}

		if (empty($referenceByType)) {
			return [
				'entries_by_reference' => [],
				'wallet_ids' => [],
			];
		}

		$entries = WalletLedgerEntry::query()
			->where(function ($q) use ($referenceByType) {
				foreach ($referenceByType as $type => $ids) {
					if (count($ids) === 0) {
						continue;
					}
					$q->orWhere(function ($qq) use ($type, $ids) {
						$qq->where('reference_type', $type)->whereIn('reference_id', $ids);
					});
				}
			})
			->get();

		$entriesByReference = [];
		foreach ($entries as $entry) {
			$refType = (string)($entry->reference_type ?? '');
			$refId = (string)($entry->reference_id ?? '');
			if ($refType === '' || $refId === '') {
				continue;
			}

			$entriesByReference[$refType][$refId][] = $entry->toArray();
			$walletId = (string)($entry->wallet_id ?? '');
			if ($walletId !== '') {
				$walletIds[] = $walletId;
			}
		}

		return [
			'entries_by_reference' => $entriesByReference,
			'wallet_ids' => array_values(array_unique(array_filter($walletIds))),
		];
	}

	private function collectWalletsForLedgerEntries(array $walletIds): array
	{
		if (empty($walletIds)) {
			return [];
		}

		return Wallet::query()
			->whereIn('id', $walletIds)
			->with('type:id,purpose')
			->get()
			->keyBy('id')
			->toArray();
	}

	private function resolveLedgerEntriesForEvent(array $event, array $entriesByReference): array
	{
		$refType = (string)($event['reference_type'] ?? '');
		$refId = (string)($event['reference_id'] ?? '');
		if ($refType === '' || $refId === '') {
			return [];
		}

		return $entriesByReference[$refType][$refId] ?? [];
	}

	private function buildGrantEventWalletDebug(
		string $eventType,
		array $entries,
		array $wallets,
		int $internalDecimals,
		int $displayDecimals
	): array
	{
		if (empty($entries)) {
			return [];
		}

		$relevantTypes = $this->walletEntryTypesForEvent((string)$eventType);
		$walletSums = [];
		$totalBase = '0';

		foreach ($entries as $entry) {
			$entryMeta = (array)($entry['meta'] ?? []);
			$deltaBase = (string)($entryMeta['delta_available_base'] ?? '0');
			if (bccomp($deltaBase, '0', 0) === 0) {
				continue;
			}

			$entryType = (string)($entry['type'] ?? '');
			if (is_array($relevantTypes) && !in_array($entryType, $relevantTypes, true)) {
				continue;
			}

			$walletId = (string)($entry['wallet_id'] ?? '');
			if ($walletId === '') {
				continue;
			}

			$usedBase = $this->absBase((string)$deltaBase);
			$walletData = $walletSums[$walletId] ?? [];

			$walletSums[$walletId]['wallet_id'] = (int)$walletId;
			$walletSums[$walletId]['wallet_purpose'] = $walletData['wallet_purpose'] ?? $this->resolveWalletPurpose($walletId, (array)($wallets[(int)$walletId] ?? []), $entryMeta);
			$walletSums[$walletId]['entry_type'] = $entryType;
			$walletSums[$walletId]['delta_base'] = bcadd((string)($walletData['delta_base'] ?? '0'), $usedBase, 0);

			$walletSums[$walletId]['delta_pct'] = '0';
			$before = $entryMeta['available_before'] ?? null;
			$after = $entryMeta['available_after'] ?? null;
			if ($before !== null && $before !== '') {
				$walletSums[$walletId]['available_before_base'] = (string)$before;
				$walletSums[$walletId]['available_before_ui'] = Money::baseToUi((string)$before, $internalDecimals, $displayDecimals);
			}
			if ($after !== null && $after !== '') {
				$walletSums[$walletId]['available_after_base'] = (string)$after;
				$walletSums[$walletId]['available_after_ui'] = Money::baseToUi((string)$after, $internalDecimals, $displayDecimals);
			}

			$totalBase = bcadd($totalBase, $usedBase, 0);
		}

		if ($totalBase === '0') {
			return [];
		}

		foreach ($walletSums as $walletId => $row) {
			$deltaBase = (string)($row['delta_base'] ?? '0');
			$walletSums[$walletId]['delta_ui'] = Money::baseToUi($deltaBase, $internalDecimals, $displayDecimals);
			$walletSums[$walletId]['delta_pct'] = bccomp($totalBase, '0', 0) > 0
				? bcmul(bcdiv($deltaBase, $totalBase, 8), '100', 2)
				: '0';
		}

		return array_values($walletSums);
	}

	private function walletEntryTypesForEvent(string $eventType): ?array
	{
		return match ($eventType) {
			'bet_debit',
			'real_wager_progress' => ['bet', 'bet_bonus'],
			'win_credit' => ['win', 'win_bonus'],
			'conversion_to_real' => ['bonus_convert_out', 'bonus_convert_in'],
			default => null,
		};
	}

	private function resolveWalletPurpose(string $walletId, array $wallet, array $meta): string
	{
		if (!empty($wallet['type']['purpose'])) {
			return (string)$wallet['type']['purpose'];
		}

		if (($meta['bet_source'] ?? '') === 'bonus') {
			return 'bonus';
		}
		if (($meta['bet_source'] ?? '') === 'real') {
			return 'real';
		}
		if (($meta['bonus_wallet_id'] ?? null) && (string)$meta['bonus_wallet_id'] === $walletId) {
			return 'bonus';
		}
		if (($meta['wallet_id_real'] ?? null) && (string)$meta['wallet_id_real'] === $walletId) {
			return 'real';
		}
		if (($meta['linked_real_wallet_id'] ?? null) && (string)$meta['linked_real_wallet_id'] === $walletId) {
			return 'real';
		}

		return 'unknown';
	}

	private function absBase(string $value): string
	{
		$value = trim($value);
		if ($value === '') {
			return '0';
		}
		return ltrim($value, '+-');
	}

	private function normalizeJsonInput(mixed $value): mixed
	{
		if (is_string($value) && $value !== '') {
			$decoded = json_decode($value, true);
			if (json_last_error() === JSON_ERROR_NONE) {
				return $decoded;
			}
		}
		return $value;
	}
}
