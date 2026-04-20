<?php

	namespace App\Http\Controllers\FE;

	use App\Repositories\Crypto\Support\Money;
	use App\Enums\TransactionTypes;
	use App\Http\Controllers\Controller;
	use App\Models\Bet;
	use Illuminate\Http\Request;

	class WinStreamController extends Controller
	{
		public function __invoke(Request $request)
		{
			$lastId = $request->header('Last-Event-ID');
			$cursorId = $lastId ? (int)$lastId : 0;

			return response()->stream(function () use ($cursorId) {
				$cursor = $cursorId;

				if ($cursor === 0) {
					$recentBets = Bet::query()
						->join('games', 'bets.game_id', 'games.game_id')
						->join('players', 'players.id', 'bets.user_id')
						->leftJoin('player_profiles', 'player_profiles.player_id', 'players.id')
						->whereNotNull('bets.operator_round_id')
						->groupBy(
							'bets.operator_round_id',
							'games.name',
							'games.slug',
							'players.username',
							'bets.currency',
							'player_profiles.hide_username'
						)
						->orderByDesc('id')
						->selectRaw('MAX(bets.id) as id, MAX(when_placed) as when_placed, games.name, games.slug as game_slug, players.username, bets.currency, SUM(stake) as stake, SUM(payout) as payout, COALESCE(player_profiles.hide_username, 0) as hide_username')
						->limit(10)
						->get()
						->reverse();

					foreach ($recentBets as $bet) {
						$cursor = (int)$bet->id;
						$this->emitWinEvent($bet, $cursor);
					}
				}

				while (true) {
					$items = Bet::query()
						->join('games', 'bets.game_id', 'games.game_id')
						->join('players', 'players.id', 'bets.user_id')
						->leftJoin('player_profiles', 'player_profiles.player_id', 'players.id')
						->whereNotNull('bets.operator_round_id')
						->groupBy(
							'bets.operator_round_id',
							'games.name',
							'games.slug',
							'players.username',
							'bets.currency',
							'player_profiles.hide_username'
						)
						->having('id', '>', $cursor)
						->orderBy('id', 'asc')
						->selectRaw('MAX(bets.id) as id, MAX(when_placed) as when_placed, games.name, games.slug as game_slug, players.username, bets.currency, SUM(stake) as stake, SUM(payout) as payout, COALESCE(player_profiles.hide_username, 0) as hide_username')
						->limit(50)
						->get();

					foreach ($items as $bet) {
						$cursor = (int)$bet->id;
						$this->emitWinEvent($bet, $cursor);
					}

					echo "event: ping\n";
					echo "data: {}\n\n";
					@ob_flush();
					@flush();

					sleep(3);
				}
			}, 200, [
				'Content-Type' => 'text/event-stream',
				'Cache-Control' => 'no-cache',
				'Connection' => 'keep-alive',
				'X-Accel-Buffering' => 'no',
			]);
		}

		private function calcMultiplier(string $stake, string $payout, int $scale = 8): string
		{
			if (bccomp($stake, '0', $scale) !== 1) {
				return '0';
			}

			return bcdiv($payout, $stake, $scale);
		}

		private function formatDecimal(string $value, int $scale = 8): string
		{
			if (!str_contains($value, '.')) {
				return $scale > 0 ? ($value . '.' . str_repeat('0', $scale)) : $value;
			}

			[$int, $frac] = explode('.', $value, 2);
			$frac = preg_replace('/[^0-9]/', '', $frac);
			$frac = str_pad($frac, $scale, '0');
			if (strlen($frac) > $scale) {
				$frac = substr($frac, 0, $scale);
			}

			return $scale > 0 ? ($int . '.' . $frac) : $int;
		}

		private function emitWinEvent(Bet $bet, int $cursor): void
		{
			$whenPlaced = $bet->when_placed?->toISOString() ?? $bet->when_placed;
			$stake = $this->formatDecimal((string)$bet->stake, 2);
			$payout = $this->formatDecimal((string)$bet->payout, 2);

			// Show only positive wins (payout > stake).
			if (bccomp($payout, $stake, 2) !== 1) {
				return;
			}

			$username = ((int)$bet->hide_username === 1)
				? $this->maskUsername((string)$bet->username)
				: $bet->username;

			echo "id: {$cursor}\n";
			echo "event: win\n";
			echo "data: " . json_encode([
					'username' => $username,
					'game' => $bet->name,
					'currency' => $bet->currency,
					'game_slug' => $bet->game_slug,
					'payout' => $payout,
					'stake' => $stake,
					'multiplier' => $this->calcMultiplier($stake, $payout, 2),
					'when_placed' => $whenPlaced,
				]) . "\n\n";

			@ob_flush();
			@flush();
		}

		private function maskUsername(string $username): string
		{
			$length = mb_strlen($username);
			$visibleLength = $length <= 6 ? 3 : 4;
			$visiblePart = mb_substr($username, 0, $visibleLength);
			$maskedLength = max(0, $length - $visibleLength);

			return $visiblePart . str_repeat('*', $maskedLength);
		}
	}
