<?php

	namespace App\Traits;

	use App\Enums\ContainerSection;
	use App\Enums\SectionStatus;
	use App\Http\Resources\GameResource;
	use App\Http\Resources\PromotionCardResource;
	use App\Http\Resources\ProviderResource;
	use App\Http\Resources\SliderResource;
	use App\Models\Bundle;
	use App\Models\Promotion;
	use App\Models\Providers;
	use App\Models\Sliders;
	use App\Models\Tournament;
	use App\Models\TournamentPrize;
	use App\Repositories\MenuRepository;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Carbon;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;
	use Illuminate\Support\Str;

	trait ContainerGeneratorTrait
	{
		/**
		 * handleResolutionConfig - generate resolution config
		 * @param $section
		 * @return array
		 */
		private function handleResolutionConfig($section)
		{
			if (!isset($section->resolution_config) || is_null($section->resolution_config)) {
				return config('sections.resolution_config');
			}
			return $section->resolution_config;
		}

		/**
		 * parseSeoContainer {ContainerSection::SEO}
		 * generate seo container response
		 * @param $section
		 * @return array
		 */
		private function parseSeoContainer($section)
		{
			return [
				'id' => $section->id,
				'children' => [],
				'data' => $section->data,
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * generateLogosContainer {ContainerSection::LOGOS}
		 * generate logo container response
		 * @param $section
		 * @return array
		 */
		private function generateLogosContainer($section)
		{
			$Logos = [];
			for ($i = 1; $i <= 6; $i++) {
				$Logos[] = url('/uploads/logos/logo' . $i . '.svg');
			}
			return [
				'id' => $section->id,
				'children' => [],
				'data' => [
					'logos' => $Logos,
					'title' => 'Trusted By',
					'gap' => [
						'XS' => '5px',
						'SM' => '5px',
						'MD' => '5px',
						'LG' => '10px',
						'XL' => '10px'
					]
				],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * parseAccordionContainer {ContainerSection::ACCORDION}
		 * returns accordion container response
		 * @param $section
		 * @return array
		 */
		private function parseAccordionContainer($section)
		{
			return [
				'id' => $section->id,
				'children' => [],
				'data' => $section->data,
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * parseProvidersContainer {ContainerSection::PROVIDER_LOGOS}
		 * generate providers container response
		 * @param $section
		 * @return array
		 */
		private function parseProvidersContainer($section)
		{
			$Providers = Providers::query()
				->whereIn('id', $section->data['providers'])->get();

			return [
				'id' => $section->id,
				'children' => [],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				],
				'data' => [
					'width' => $section->data['width'] . "px",
					'height' => $section->data['height'] . "px",
					'providerLogos' => (count($Providers)) ? ProviderResource::collection($Providers) : [],
				]
			];
		}

		/**
		 * parseBetFeed {ContainerSection::BET_FEED}
		 * return bet feed component response
		 * @param $section
		 * @return array
		 */
		private function parseBetFeed($section)
		{
			return [
				'id' => $section->id,
				'children' => [],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				],
				'data' => [
					'highStakesFeed' => config('sections.highStakesFeed'),
					'feed' => config('sections.feed'),
				]
			];
		}

		/**
		 * parseSearchContainer {ContainerSection::SEARCH}
		 * returns search section response
		 * @param $section
		 * @return array
		 */
		private function parseSearchContainer($section)
		{
			return [
				'id' => $section->id,
				'children' => [],
				'data' => $section->data,
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * generateBonusContainer {ContainerSection::BONUS}
		 * returns bonus container response
		 * @param $section
		 * @return array
		 */
		private function generateBonusContainer($section): array
		{
			$currenciesArray = ['btc.svg', 'eth.svg', 'usdt.svg', 's.svg', 't.svg', 'dollar.svg', 'x.svg', 'd.svg', 'm.svg', 'k.svg'];
			$payment = ['ipay.svg', 'picpay.svg', 'gpay.svg', 'visa.svg', 'visa-2.svg'];

			$currencies = collect($currenciesArray)->map(function ($item) {
				return url('/uploads/currencies/' . $item);
			});
			$payments = collect($payment)->map(function ($item) {
				return url('/uploads/currencies/' . $item);
			});
			return [
				'id' => $section->id,
				'children' => [],
				'data' => [
					'currencies' => $currencies,
					'payment' => $payments,
					'title' => '<span style="color: #00ef8b;">200%</span> Welcome Bonus',
					'bonusIcon' => url('/uploads/currencies/bonus.svg')
				],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * generateSliderSection {ContainerSection::SLIDER}
		 * generate slider response
		 * @param $section
		 * @return array
		 */
		public function generateSliderSection($section): array
		{
			$Banners = Sliders::query()->whereIn('id', $section->data['sliders'])->get();

			return [
				'id' => $section->id,
				'children' => [],
				'data' => [
					'banners' => SliderResource::collection($Banners)
				],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * parsePlayerFavoritesContainer {ContainerSection::PLAYER_FAVORITES}
		 * generates player favorites container response
		 * @param $section
		 * @return array
		 */
		public function parsePlayerFavoritesContainer($section): array
		{
			return [
				'id' => $section->id,
				'children' => [],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				],
				'data' => [
					'resolutionConfig' => $this->handleResolutionConfig($section),
					'games' => auth('casino')->check() ? GameResource::collection(auth('casino')->user()->favorites()->get())->jsonSerialize() : []
				]
			];
		}

		/**
		 * parseOfferContainer {ContainerSection::OFFER}
		 * returns offer container response
		 * @param $section
		 * @return array
		 */
		private function parseOfferContainer($section)
		{
			$offers = collect(config('casino.homebox'))->filter(function ($item) use ($section) {
				if (in_array($item['id'], $section->data['offers'])) {
					return $item;
				}
			})->values();

			$Menus = new MenuRepository();

			return [
				'id' => $section->id,
				'children' => [
					'leftSidebar' => $Menus->menu("SIDEBAR"),
					'footer' => $Menus->menu('FOOTER'),
				],
				'data' => [
					'offers' => $offers
				],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * parsePromotionsContainer {ContainerSection::PROMOTIONS}
		 * generate promotions response
		 * @param $section
		 * @return array
		 */
		private function parsePromotionsContainer($section): array
		{
			$PromoQuery = Promotion::query()->where('status', SectionStatus::PUBLISHED->value)->where('active', 1)->get();

			return [
				'id' => $section->id,
				'children' => [],
				'data' => [
					'promotions' => PromotionCardResource::collection($PromoQuery),
				],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				]
			];
		}

		/**
		 * parseTournamentsContainer {ContainerSection::TOURNAMENTS}
		 * returns tournaments in the format expected by the casino frontend tournaments-config.ts
		 * @param $section
		 * @return array
		 */
		private function parseTournamentsContainer($section): array
		{
			$data = is_array($section->data) ? $section->data : [];
			$statuses = $data['statuses'] ?? ['active', 'upcoming', 'finished'];
			$limit = (int)($data['limit'] ?? 20);

			if (!is_array($statuses) || count($statuses) === 0) {
				$statuses = ['active', 'upcoming', 'finished'];
			}

			if ($limit <= 0) {
				$limit = 20;
			}

			$Tournaments = Tournament::query()
				->with(['tournamentGames.game', 'prizes'])
				->whereIn('status', $statuses)
				->orderByRaw("FIELD(status, 'active', 'upcoming', 'finished', 'scheduled', 'draft', 'cancelled')")
				->orderBy('started_at')
				->limit($limit)
				->get()
				->map(fn (Tournament $tournament) => $this->formatTournamentForFrontend($tournament))
				->values();

			return [
				'id' => $section->id,
				'children' => [],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				],
				'data' => [
					'tournaments' => $Tournaments,
				],
			];
		}

		private function formatTournamentForFrontend(Tournament $tournament): array
		{
			$startedAt = $tournament->started_at ? Carbon::parse($tournament->started_at) : null;
			$endedAt = $tournament->ended_at ? Carbon::parse($tournament->ended_at) : null;
			$now = now();
			$tournamentGames = $tournament->tournamentGames
				->filter(fn ($tournamentGame) => $tournamentGame->game !== null)
				->values();

			return [
				'id' => (string)$tournament->id,
				'name' => (string)$tournament->name,
				'thumbnail' => $tournament->thumbnail_url ?? ($tournament->thumbnail ? url(config('casino.uploads.tournaments') . $tournament->thumbnail) : null),
				'started_at' => $this->formatTournamentDate($startedAt),
				'ended_at' => $this->formatTournamentDate($endedAt),
				'status' => (string)$tournament->status,
				'point_rate' => (int)$tournament->point_rate,
				'games' => $tournamentGames->map(fn ($tournamentGame) => [
					'id' => (string)$tournamentGame->id,
					'tournament_id' => (string)$tournamentGame->tournament_id,
					'game_id' => (string)$tournamentGame->game_id,
					'name' => $tournamentGame->game->name,
					'slug' => $tournamentGame->game->slug,
					'thumbnail' => $tournamentGame->game->thumbnail,
					'thumbnail_url' => $tournamentGame->game->thumbnail_url,
					'created_at' => $this->formatTournamentDate($tournamentGame->created_at ? Carbon::parse($tournamentGame->created_at) : null),
					'updated_at' => $this->formatTournamentDate($tournamentGame->updated_at ? Carbon::parse($tournamentGame->updated_at) : null),
				])->values(),
				'prizes' => $tournament->prizes->map(fn ($prize) => [
					'id' => (string)$prize->id,
					'tournament_id' => (string)$prize->tournament_id,
					'prize_name' => (string)$prize->prize_name,
					'prize_type' => (string)$prize->prize_type,
					'rank_from' => $prize->rank_from !== null ? (int)$prize->rank_from : null,
					'rank_to' => $prize->rank_to !== null ? (int)$prize->rank_to : null,
					'min_points' => $prize->min_points !== null ? (int)$prize->min_points : null,
					'prize_currency' => $prize->prize_currency,
					'prize_amount' => $prize->prize_amount,
					'metadata' => $prize->metadata,
					'created_at' => $this->formatTournamentDate($prize->created_at ? Carbon::parse($prize->created_at) : null),
					'updated_at' => $this->formatTournamentDate($prize->updated_at ? Carbon::parse($prize->updated_at) : null),
				])->values(),
					'ui' => [
						'subtitle' => $this->buildTournamentSubtitle($tournament, $startedAt, $endedAt),
						'is_live' => $tournament->status === 'active',
						'players_count' => $this->buildTournamentPlayersCount($tournament),
						'ends_in_label' => $endedAt ? $this->buildTournamentTimeLabel($endedAt, $now) : null,
						'prize_pool_label' => $this->buildTournamentPrizePoolLabel($tournament),
						'progress_percent' => $this->buildTournamentProgressPercent($startedAt, $endedAt, $now),
						'protocols' => $this->buildTournamentProtocols($tournament, $startedAt, $endedAt),
						'description' => 'Play eligible games and earn points to climb the tournament leaderboard.',
						'rules_note' => 'Leaderboard and standing data are shown when scoring data is available.',
						'leaderboard' => $this->buildTournamentLeaderboard($tournament, 10),
						'user_standing' => null,
					],
					'created_at' => $this->formatTournamentDate($tournament->created_at ? Carbon::parse($tournament->created_at) : null),
					'updated_at' => $this->formatTournamentDate($tournament->updated_at ? Carbon::parse($tournament->updated_at) : null),
				];
			}

		private function formatTournamentDate(?Carbon $date): ?string
		{
			return $date?->toIso8601String();
		}

		private function buildTournamentSubtitle(Tournament $tournament, ?Carbon $startedAt, ?Carbon $endedAt): ?string
		{
			if ($tournament->status === 'active' && $endedAt) {
				return 'Live until ' . $endedAt->format('M j, Y H:i');
			}

			if (in_array($tournament->status, ['upcoming', 'scheduled'], true) && $startedAt) {
				return 'Starts ' . $startedAt->format('M j, Y H:i');
			}

			if ($tournament->status === 'finished' && $endedAt) {
				return 'Finished ' . $endedAt->format('M j, Y H:i');
			}

			return null;
		}

		private function buildTournamentTimeLabel(Carbon $endedAt, Carbon $now): string
		{
			if ($endedAt->lessThanOrEqualTo($now)) {
				return 'Ended';
			}

			return $endedAt->diffForHumans($now, [
				'parts' => 2,
				'short' => true,
				'syntax' => Carbon::DIFF_ABSOLUTE,
			]);
		}

		private function buildTournamentPrizePoolLabel(Tournament $tournament): ?string
		{
			if ($tournament->prizes->isEmpty()) {
				return null;
			}

			$currency = (string)($tournament->prizes->first()->prize_currency ?? 'GC');
			$total = $tournament->prizes->sum(fn ($prize) => (float)$prize->prize_amount);

			return trim($currency . ' ' . number_format($total, 2, '.', ','));
		}

		private function buildTournamentProgressPercent(?Carbon $startedAt, ?Carbon $endedAt, Carbon $now): int
		{
			if (!$startedAt || !$endedAt) {
				return 0;
			}

			if ($now->lessThanOrEqualTo($startedAt)) {
				return 0;
			}

			if ($now->greaterThanOrEqualTo($endedAt)) {
				return 100;
			}

			$totalSeconds = max(1, $startedAt->diffInSeconds($endedAt));
			$elapsedSeconds = $startedAt->diffInSeconds($now);

			return (int)round(min(100, max(0, ($elapsedSeconds / $totalSeconds) * 100)));
		}

			private function buildTournamentProtocols(Tournament $tournament, ?Carbon $startedAt, ?Carbon $endedAt): array
			{
				return [
				[
					'label' => 'Start',
					'value' => $startedAt ? $startedAt->format('M j, Y H:i') : '-',
				],
				[
					'label' => 'End',
					'value' => $endedAt ? $endedAt->format('M j, Y H:i') : '-',
				],
				[
					'label' => 'Point Rate',
					'value' => (int)$tournament->point_rate . ' points',
				],
				[
					'label' => 'Games',
					'value' => (string)$tournament->games->count(),
				],
				[
					'label' => 'Prizes',
					'value' => (string)$tournament->prizes->count(),
				],
				[
					'label' => 'Status',
					'value' => strtoupper((string)$tournament->status),
				],
				];
			}

			private function buildTournamentPlayersCount(Tournament $tournament): ?int
			{
				if (!Schema::hasTable('tournament_scores')) {
					return null;
				}

				return (int)DB::table('tournament_scores')
					->where('tournament_id', $tournament->id)
					->count();
			}

			private function buildTournamentLeaderboard(Tournament $tournament, int $limit = 10): array
			{
				if (!Schema::hasTable('tournament_scores')) {
					return [];
				}

				$limit = max(1, min(50, $limit));

				$rows = DB::table('tournament_scores as ts')
					->join('players as p', 'p.id', '=', 'ts.user_id')
					->where('ts.tournament_id', $tournament->id)
					->orderByDesc('ts.points')
					->orderBy('ts.updated_at')
					->orderBy('ts.user_id')
					->limit($limit)
					->get([
						'ts.points as points',
						'p.username as username',
					]);

				$leaderboard = [];
				$position = 1;
				foreach ($rows as $row) {
					$score = (int)$row->points;
					$leaderboard[] = [
						'position' => $position,
						'player' => (string)$row->username,
						'score' => $score,
						'prize_label' => $this->buildTournamentPrizeLabelFor($tournament->prizes, $position, $score),
					];
					$position++;
				}

				return $leaderboard;
			}

			private function buildTournamentPrizeLabelFor(Collection $prizes, int $position, int $score): string
			{
				$rankPrize = $prizes
					->where('prize_type', 'rank')
					->first(function (TournamentPrize $prize) use ($position) {
						$from = $prize->rank_from !== null ? (int)$prize->rank_from : null;
						$to = $prize->rank_to !== null ? (int)$prize->rank_to : null;

						if ($from === null || $to === null) {
							return false;
						}
						return $position >= $from && $position <= $to;
					});

				if ($rankPrize) {
					return (string)$rankPrize->prize_name;
				}

				$thresholdPrize = $prizes
					->where('prize_type', 'threshold')
					->filter(fn (TournamentPrize $prize) => $prize->min_points !== null && $score >= (int)$prize->min_points)
					->sortByDesc(fn (TournamentPrize $prize) => (int)$prize->min_points)
					->first();

				if ($thresholdPrize) {
					return (string)$thresholdPrize->prize_name;
				}

				return '-';
			}

		/**
		 * @param string $container
		 * @param array $data
		 * @return array
		 */
		private function generateEmptySection(string $container, array $data)
		{
			return [
				'id' => Str::uuid(),
				'children' => [],
				'data' => $data,
				'container' => $container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig([])
				]
			];
		}

    private function parseBundlesContainer($section): array
    {
      $now = now();
      $limit = (int)($section->data['limit'] ?? 24);

      if ($limit <= 0) {
        $limit = 24;
      }

      $bundles = Bundle::query()
        ->where('is_active', true)
        ->where(function ($query) use ($now) {
          $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
        })
        ->where(function ($query) use ($now) {
          $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
        })
        ->orderByDesc('is_featured')
        ->orderBy('sort_order')
        ->orderByDesc('created_at')
        ->limit($limit)
        ->get()
        ->map(fn (Bundle $bundle) => $this->formatBundleForFrontend($bundle))
        ->values();

      return [
        'id' => $section->id,
        'children' => [],
        'data' => [
          'bundles' => $bundles,
          'featuredBundles' => $bundles->where('tier', 'featured')->values(),
          'standardBundles' => $bundles->where('tier', 'standard')->values(),
        ],
        'container' => $section->container,
        'appearance' => [
          'resolutionConfig' => $this->handleResolutionConfig($section)
        ]
      ];
    }

		private function formatBundleForFrontend(Bundle $bundle): array
		{
			$priceAmount = (float)$bundle->price_amount;
			$coins = (int)round((float)$bundle->coin_amount > 0 ? (float)$bundle->coin_amount : (float)$bundle->gc_amount);
			$priceCurrency = strtoupper((string)($bundle->price_currency ?: 'EUR'));
			$priceLabel = trim($priceCurrency . ' ' . number_format($priceAmount, 2, '.', ','));

			return [
				'id' => (string)$bundle->id,
				'name' => (string)$bundle->name,
				'coins' => $coins,
				'priceLabel' => $priceLabel,
				'icon' => $bundle->icon ?: 'mdi-coins',
				'tier' => $bundle->is_featured ? 'featured' : 'standard',
				'badge' => $bundle->badge_text ?: $bundle->label,
				'bonusLabel' => $bundle->ribbon_text ?: $bundle->subtitle,
				'featured' => (bool)$bundle->is_featured,
				'popular' => (bool)$bundle->is_popular,
				'thumbnail' => $bundle->thumbnail,
				'image_url' => $bundle->image_url,
				'slug' => $bundle->slug,
				'short_description' => $bundle->short_description,
				'description' => $bundle->description,
				'price_amount' => $bundle->price_amount,
				'price_currency' => $bundle->price_currency,
				'gc_amount' => $bundle->gc_amount,
				'coin_amount' => $bundle->coin_amount,
				'cta_text' => $bundle->cta_text,
				'metadata' => $bundle->metadata,
			];
		}
	}
