<?php

	namespace App\Repositories;

	use App\Repositories\Bonus\Services\BonusGrantService;
	use App\Repositories\Crypto\Support\CurrencyDecimals;
	use App\Repositories\Crypto\Support\Money;
	use App\Enums\PlayerActivityEnums;
	use App\Http\Resources\BetResource;
	use App\Http\Resources\GameResource;
	use App\Http\Resources\PlayerResource;
	use App\Http\Resources\WalletResource;
	use App\Models\Bet;
	use App\Models\PlayerProfile;
	use App\Models\Session;
	use App\Events\PlayerRegistered;
	use Auth;
	use App\Interfaces\PlayersInterface;
	use App\Models\Player;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Hash;
	use Illuminate\Support\Facades\Log;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;

	class PlayersRepository implements PlayersInterface
	{
		const ACTIVE_USER = 1;
		const INACTIVE_USER = 0;

		const DEFAULT_LANG = "en";
		const DEFAULT_CURRENCY = "EUR";
		protected $fixedIDPrefix = "CC-";

		/**
		 * @return string
		 * @throws \Exception
		 */
		protected function generateFixedID(): string
		{
			do {
				$fixedID = $this->fixedIDPrefix . random_int(1000000, 9999999);
			} while (Player::query()->where('fixed_id', '=', $fixedID)->first());
			return $fixedID;
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function registration(Request $request): array
		{
			//save user to database
			$Player = Player::create([
				"username" => $request->get('username'),
				"password" => Hash::make($request->get('password')),
				"email" => $request->get('email'),
				"active" => self::ACTIVE_USER,
				"casino_id" => Str::uuid(),
				"wallet_id" => $request->get('wallet') ? $request->get('wallet') : '',
				"fixed_id" => $this->generateFixedID(),
				'int_casino_id' => $request->get('casino_id')
			]);
			Log::info('on registration');
			//save profile settings
			$PlayerProfile = new PlayerProfile();
			$PlayerProfile->player_id = $Player->id;
			$PlayerProfile->language = self::DEFAULT_LANG;
			$PlayerProfile->display_fiat_currency = self::DEFAULT_CURRENCY;
			$PlayerProfile->hide_username = 1;
			$PlayerProfile->two_factor_enabled = 0;
			$PlayerProfile->save();

			event(new PlayerRegistered($Player->id));
			try {
				$granted = app(BonusGrantService::class)->grantRegisterBonuses($Player);
				Log::info('registration bonuses processed', [
					'player_id' => $Player->id,
					'granted_count' => $granted,
				]);
			} catch (\Throwable $e) {
				// bonus grant must not block successful registration
				Log::error('registration bonus grant failed', [
					'player_id' => $Player->id,
					'error' => $e->getMessage(),
				]);
			}

			//send email

			$credentials = [
				'email' => $request->get('email'),
				'password' => $request->get('password'),
				'int_casino_id' => $request->get('casino_id'),
			];
			$token = Auth::guard('casino')->attempt($credentials);

			//logs
			$Logs = new PlayerActivity($request);
			$Logs->log(PlayerActivityEnums::REGISTRATION);

			return [
				'user' => PlayerResource::make($Player),
				'authorization' => [
					'token' => $token,
					'type' => 'bearer',
				]
			];
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function savePlayerProfile(Request $request): array
		{
			$PlayerID = $request->user('casino')->id;
			$Params = $request->validated() + [
					'address' => $request->get('address'),
					'player_id' => $PlayerID
				];

			$Profile = PlayerProfile::query()->where('player_id', '=', $PlayerID)->first();
			if (!$Profile) {
				$Profile = new PlayerProfile();
				$Profile->create($Params);
			} else {
				$Profile->update($Params);
			}
			return [
				'success' => true,
				'profile' => $Profile,
				'message' => 'Your infos was saved successfully'
			];
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function savePlayerSettings(Request $request): array
		{
			$PlayerID = $request->user('casino')->id;
			$Params = $request->only(['display_fiat_currency', 'language', 'marketing_emails', 'hide_username', 'two_factor_enabled']);

			$Profile = PlayerProfile::query()->where('player_id', '=', $PlayerID)->first();
			if (!$Profile) {
				$Profile = new PlayerProfile();
				$Profile->create($Params);
			} else {
				$Profile->update($Params);
			}
			return [
				'success' => true,
				'profile' => $Profile,
				'message' => 'Your infos was saved successfully'
			];
		}

		/**
		 * @param Request $request
		 * @return array
		 */
		public function profile(Request $request)
		{
			$UserId = $request->user('casino')->id;
			return [
				'user' => PlayerResource::make($request->user('casino')),
				'profile' => PlayerProfile::query()->where('player_id', $UserId)->first()
			];
		}

		/**
		 * @param array $params
		 * @return array
		 */
		public function list(array $params = []): array
		{
			$start = (isset($params['start'])) ? $params['start'] : 0;
			$length = (isset($params['length']) && $params['length'] > 0) ? $params['length'] : 50;
			$offset = ($start - 1) * $length;
			$casinoID = $params['int_casino_id'] ?? null;

			$MainQuery = Player::
			when(isset($params['search']) && strlen($params['search']) > 1, function ($query) use ($params) {
				$query->whereRaw("`email` LIKE '%{$params['search']}%' OR username LIKE '%{$params['search']}%' OR  fixed_id LIKE '%{$params['search']}%'");
			})
				->when($casinoID, fn($qq) => $qq->where('int_casino_id', '=', $casinoID))
				->select('players.*')
				->with(['wallets.walletBalance'])
				->addSelect([
					'last_ip' => DB::table('player_logins')
						->select('ip')
						->whereColumn('player_logins.authenticatable_id', 'players.id')
						->orderByDesc('created_at')
						->limit(1),
					'last_login_at' => DB::table('player_logins')
						->select('created_at')
						->whereColumn('player_logins.authenticatable_id', 'players.id')
						->orderByDesc('created_at')
						->limit(1),
				]);

			$total = $MainQuery->count();

			$items = $MainQuery->limit($length)
				->offset($offset)
				->get()
				->map(function (Player $player) {
					$summary = $this->walletBalanceSummary($player);
					$player->player_balance = $summary['total'];
					$player->player_balance_available = $summary['available'];
					$player->unsetRelation('wallets');
					return $player;
				});

			return [
				'total' => $total,
				'items' => $items
			];
		}

		private function walletBalanceSummary(Player $player): array
		{
			$byCurrency = [];

			foreach ($player->wallets as $wallet) {
				$currency = (string)($wallet->currency_code ?? $wallet->currency ?? '');
				if ($currency === '') {
					$currency = 'N/A';
				}

				$availableBase = (string)($wallet->walletBalance?->available_base ?? '0');
				$reservedBase = (string)($wallet->walletBalance?->reserved_base ?? '0');

				if (!isset($byCurrency[$currency])) {
					$byCurrency[$currency] = ['available_base' => '0', 'reserved_base' => '0'];
				}

				$byCurrency[$currency]['available_base'] = bcadd($byCurrency[$currency]['available_base'], $availableBase, 0);
				$byCurrency[$currency]['reserved_base'] = bcadd($byCurrency[$currency]['reserved_base'], $reservedBase, 0);
			}

			if (empty($byCurrency)) {
				return ['total' => '0', 'available' => '0'];
			}

			$total = [];
			$available = [];

			foreach ($byCurrency as $currency => $amounts) {
				$decimals = CurrencyDecimals::internalForCurrency($currency);
				$totalBase = bcadd($amounts['available_base'], $amounts['reserved_base'], 0);

				$total[] = Money::baseToUi($totalBase, $decimals, 2) . ' ' . $currency;
				$available[] = Money::baseToUi($amounts['available_base'], $decimals, 2) . ' ' . $currency;
			}

			return [
				'total' => implode(' / ', $total),
				'available' => implode(' / ', $available),
			];
		}

		public function overview(array $params = []): array
		{
			$casinoID = $params['int_casino_id'] ?? null;

			$playerIds = Player::query()
				->when($casinoID, fn($qq) => $qq->where('int_casino_id', '=', $casinoID))
				->pluck('id');

			$countryRows = collect();
			if ($playerIds->isNotEmpty()) {
				$latestLoginIds = DB::table('player_logins')
					->selectRaw('MAX(id) as id')
					->whereIn('authenticatable_id', $playerIds)
					->groupBy('authenticatable_id');

				$countryRows = DB::table('player_logins as pl')
					->joinSub($latestLoginIds, 'latest_logins', fn($join) => $join->on('pl.id', '=', 'latest_logins.id'))
					->selectRaw("COALESCE(NULLIF(pl.country, ''), 'Unknown') as country, COUNT(*) as players_count")
					->groupByRaw("COALESCE(NULLIF(pl.country, ''), 'Unknown')")
					->orderByDesc('players_count')
					->limit(10)
					->get();
			}

			return [
				'player_countries' => $countryRows->map(fn($row) => [
					'country' => (string)$row->country,
					'players_count' => (int)$row->players_count,
				])->values(),
				'deposit_balance' => $this->depositBalanceSummary($casinoID),
				'total_player_balance' => $this->totalPlayerBalanceSummary($casinoID),
			];
		}

		private function depositBalanceSummary(?string $casinoID = null): array
		{
			$rows = DB::table('deposits as d')
				->join('wallets as w', 'w.id', '=', 'd.wallet_id')
				->join('players as p', 'p.id', '=', 'w.holder_id')
				->where('w.holder_type', Player::class)
				->whereIn('d.status', ['confirmed', 'finalized'])
				->when($casinoID, fn($qq) => $qq->where('p.int_casino_id', '=', $casinoID))
				->groupBy('d.currency')
				->selectRaw('d.currency, COALESCE(SUM(d.amount_base), 0) as amount_base')
				->get();

			return $this->formatCurrencyRows($rows, 'amount_base');
		}

		private function totalPlayerBalanceSummary(?string $casinoID = null): array
		{
			$rows = DB::table('wallet_balances as wb')
				->join('wallets as w', 'w.id', '=', 'wb.wallet_id')
				->join('players as p', 'p.id', '=', 'w.holder_id')
				->where('w.holder_type', Player::class)
				->when($casinoID, fn($qq) => $qq->where('p.int_casino_id', '=', $casinoID))
				->groupBy('wb.currency')
				->selectRaw('wb.currency, COALESCE(SUM(wb.available_base + wb.reserved_base), 0) as amount_base')
				->get();

			return $this->formatCurrencyRows($rows, 'amount_base', 2);
		}

		private function formatCurrencyRows($rows, string $amountKey, ?int $fixedDisplayDecimals = null): array
		{
			$items = collect($rows)->map(function ($row) use ($amountKey, $fixedDisplayDecimals) {
				$currency = (string)($row->currency ?? 'N/A');
				$amountBase = (string)($row->{$amountKey} ?? '0');
				$decimals = CurrencyDecimals::internalForCurrency($currency);
				$uiDecimals = CurrencyDecimals::uiForCurrency($currency);
				$displayDecimals = $fixedDisplayDecimals ?? ($uiDecimals > 0 ? $uiDecimals : $decimals);

				return [
					'currency' => $currency,
					'amount_base' => $amountBase,
					'amount_ui' => Money::baseToUi($amountBase, $decimals, $displayDecimals),
				];
			})->values();

			return [
				'items' => $items,
				'display' => $items->isEmpty()
					? '0'
					: $items->map(fn($item) => "{$item['amount_ui']} {$item['currency']}")->implode(' / '),
			];
		}

		/**
		 * @param int $playerID
		 * @return array
		 */
		public function playerSessions(int $playerID): array
		{
			$start = (isset($params['start'])) ? $params['start'] : 0;
			$length = (isset($params['length']) && $params['length'] > 0) ? $params['length'] : 50;
			$offset = ($start - 1) * $length;

			$CurrentUser = Player::query()->where('id', $playerID)->first();

			if (!$CurrentUser) {
				return [
					'success' => false,
					'msg' => 'User not found'
				];
			}

			$MainQuery = Session::where('user_id', $playerID)->with(['game', 'wallet']);

			return [
				'total' => $MainQuery->count(),
				'items' => $MainQuery->limit($length)
					->offset($offset)->get()
			];
		}

		public function toggleGameFavorite($gameID): array
		{
			if (!auth('casino')->check()) {
				return [
					'success' => false,
					'message' => 'User is not authenticated'
				];
			}

			if (auth('casino')->user()->favorites()->toggle($gameID)) {
				return [
					'success' => true
				];
			}

			return [
				'success' => false,
				'message' => 'Something went wrong'
			];
		}

		/**
		 * @return array
		 */
		public function getFavGames(): array
		{
			return [
				'success' => true,
				'games' => GameResource::collection(auth('casino')->user()->favorites()->get())
			];
		}

		public function playerBet(Request $request): array
		{
			$page = $request->has('page') ? $request->get('page') : 1;
			$length = $request->has('length') ? $request->get('length') : 50;
			$offset = ($page - 1) * $length;

			$ResultsData = Bet::query()
				->where('user_id', auth('casino')->user()->id)
				->with('game')
				->when($request->has('currency') && $request->get('currency'), function ($query) use ($request) {
					$query->where('currency', $request->get('currency'));
				})
				->when($request->has('from') && $request->has('from'), function ($query) use ($request) {
					$query->whereRaw('when_placed >= "' . $request->get('from') . ' 00:00:00"');
				})
				->when($request->has('to') && $request->has('to'), function ($query) use ($request) {
					$query->whereRaw('when_placed <= "' . $request->get('to') . ' 23:59:59"');
				})
				->when($request->has('game') && $request->get('game'), function ($query) use ($request) {
					$query->where('game_id', $request->get('game'));
				})
				->orderBy('id', 'desc')
				->paginate((int)$length);

			$ResultsData->setCollection(
				$ResultsData->getCollection()->map(
					fn(Bet $bet) => BetResource::make($bet->loadMissing('game'))->resolve()
				)
			);

			return [
				'data' => $ResultsData,
				'success' => true
			];
		}
	}
