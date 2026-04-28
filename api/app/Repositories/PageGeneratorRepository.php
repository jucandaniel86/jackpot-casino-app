<?php

	namespace App\Repositories;

	use App\Enums\ContainerSection;
	use App\Enums\SectionStatus;
	use App\Http\Resources\GameResource;
	use App\Http\Resources\TagsResource;
	use App\Interfaces\PageGeneratorInterface;
	use App\Models\Categories;
	use App\Models\Game;
	use App\Models\Pages;
	use App\Models\Promotion;
	use App\Models\Providers;
	use App\Models\Tag;
	use App\Traits\ContainerGeneratorTrait;
	use Illuminate\Http\Request;
	use Illuminate\Support\Str;

	class PageGeneratorRepository implements PageGeneratorInterface
	{
		const DEFAULT_LAYOUT = "DefaultLayout";

		use ContainerGeneratorTrait;

		/**
		 * @param string $slug
		 * @return array
		 */
		public function getPage(string $slug, $casinoID = ""): array
		{
			$CurrentPage = Pages::query()->with(['sections' => function ($query) {
				$query->where('status', SectionStatus::PUBLISHED->value);
				$query->orderByRaw('page_sections.page_order ASC');
			}])
				->when($casinoID && (string)$casinoID != "", function ($query) use ($casinoID) {
					$query->where('int_casino_id', $casinoID);
				})
				->where('slug', $slug)->first();

			if (!$CurrentPage) {
				return [
					'status' => 404,
					'message' => 'Page not found'
				];
			}

			return $this->generateLayoutResponse($this->parseSections($CurrentPage->sections), $casinoID, $CurrentPage->seo);
		}

		/**
		 * @param $sections
		 * @return array
		 */
		private function parseSections($sections): array
		{
			$_return = [];
			foreach ($sections as $section) {
				switch ($section->container) {
					case ContainerSection::SEO->value:
						$_return[] = $this->parseSeoContainer($section);
						break;
					case ContainerSection::HTML->value:
						$_return[] = $this->parseSeoContainer($section);
						break;
					case ContainerSection::PROVIDER_LOGOS->value:
						$_return[] = $this->parseProvidersContainer($section);
						break;
					case ContainerSection::CATEGORY_HEADLESS->value:
						$_return[] = $this->parseCategoryContainer($section, 45);
						break;
					case ContainerSection::BET_FEED->value:
						$_return[] = $this->parseBetFeed($section);
						break;
					case ContainerSection::GAMES_CATEGORY->value:
						$_return[] = $this->parseCategoryContainer($section, 12);
						break;
					case ContainerSection::SEARCH->value:
						$_return[] = $this->parseSearchContainer($section);
						break;
					case ContainerSection::TABS->value:
						$_return[] = $this->parseTabsContainer($section);
						break;
					case ContainerSection::ACCORDION->value:
						$_return[] = $this->parseAccordionContainer($section);
						break;
					case ContainerSection::PROMOTIONS->value:
						$_return[] = $this->parsePromotionsContainer($section);
						break;
					case ContainerSection::SLIDER->value:
						$_return[] = $this->generateSliderSection($section);
						break;
					case ContainerSection::LOGOS->value:
						$_return[] = $this->generateLogosContainer($section);
						break;
					case ContainerSection::BONUS->value:
						$_return[] = $this->generateBonusContainer($section);
						break;
					case ContainerSection::OFFER->value:
						$_return[] = $this->parseOfferContainer($section);
						break;
					case ContainerSection::PLAYER_FAVORITES->value:
						$_return[] = $this->parsePlayerFavoritesContainer($section);
						break;
					case ContainerSection::TOURNAMENTS->value:
    				$_return[] = $this->parseTournamentsContainer($section);
						break;
          case ContainerSection::BUNDLES->value:
            $_return[] = $this->parseBundlesContainer($section);
            break;
				}
			}
			return $_return;
		}

		private function extractCategory($data): int
		{
			$Category = 0;
			if (isset($data['category'])) {
				if (is_array($data['category']) && count($data['category']) > 0) {
					$Category = (int)$data['category'][0];
				} else {
					$Category = (int)$data['category'];
				}
			}
			return $Category;
		}

		private function generateCategoryDetailsSection($Category)
		{
			$Limit = 36;
			$Page = 1;

			$GameData = $this->getGamesWithPagination(['category' => $Category->id], $Page, $Limit);

			return [
				'id' => Str::uuid(),
				'container' => ContainerSection::CATEGORY_DETAILS->value,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig([])
				],
				'data' => [
					'aspectRatioPercentage' => 127,
					'fetchUrl' => route('fe:category-games', $Category->slug),
					'resolutionConfig' => config('sections.categories_sections_config'),
					'title' => $Category->name,
					'slug' => $Category->slug,
					'icon' => $Category->icon,
					'initialState' => [
						'size' => $Limit,
						'page' => $GameData['page'],
						'totalItems' => $GameData['totalItems'],
						'data' => $GameData['data']
					]
				]
			];
		}

		private function parseCategoryContainer($section, $Limit = 12)
		{
			$Category = $this->extractCategory($section->data);
			$Page = 1;
			$GameData = $this->getGamesWithPagination(['category' => $Category], $Page, $Limit);

			$CurrentCategory = Categories::where('id', $Category)->first();

			if (!$CurrentCategory) {
				return [
					'id' => $section->id,
					'children' => [],
					'container' => $section->container,
					'appearance' => [
						'resolutionConfig' => $this->handleResolutionConfig($section),
					],
					'data' => [
						'resolutionConfig' => $section->data['resolutionConfig'],
						'initialState' => []
					]
				];
			}

			return [
				'id' => $section->id,
				'children' => [],
				'container' => $section->container,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				],
				'data' => [
					'aspectRatioPercentage' => $section->data['aspectRatio'],
					'fetchUrl' => route('fe:category-games', $CurrentCategory->slug),
					'resolutionConfig' => $section->data['resolutionConfig'],
					'title' => $CurrentCategory->name,
					'slug' => 'category/' . $CurrentCategory->slug,
					'icon' => $CurrentCategory->icon,
					'initialState' => [
						'page' => 1,
						'size' => $Limit,
						'totalItems' => $GameData['totalItems'],
						'data' => $GameData['data']
					]
				]
			];
		}

		private function parseTabsContainer($section)
		{
			$sectionTabs = (array)$section->data['tab'];
			$TabsQuery = Tag::query()->whereIn('id', $sectionTabs)->get();
			$FirstTab = $TabsQuery[0];

			return [
				'id' => $section->id,
				'container' => $section->container,
				'children' => $this->parseSections($FirstTab->sections()->orderBy('page_order')->get()),
//				'sections' => $FirstTab->sections()->get(),
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig($section)
				],
				'data' => [
					'fetchUrl' => '/tabs-container/',
					'tabs' => TagsResource::collection($TabsQuery)
				],
			];
		}


		/**
		 * @param string $slug
		 * @return array
		 */
		public function getCategory(string $slug, string $casinoID): array
		{
			$CurrentPage = Categories::query()->where('slug', $slug)->first();
			if (!$CurrentPage) {
				return [
					'status' => 404,
					'message' => 'Page not found'
				];
			}
			$CategorySection = $this->generateCategoryDetailsSection($CurrentPage);

			return $this->generateLayoutResponse([$CategorySection], $casinoID);
		}


		/**
		 * @param $slug
		 * @param Request $request
		 * @return array
		 */
		public function getCategoryGames($slug, Request $request)
		{
			$page = $request->get('page');
			$Category = Categories::query()->where('slug', $slug)->first();

			$GamesData = $this->getGamesWithPagination(['category' => $Category->id], $page);

			return [
				'data' => $GamesData['data'],
				'totalItems' => $GamesData['totalItems'],
				'page' => $GamesData['page']
			];
		}

		/**
		 * @param string $slug
		 * @return array
		 */
		public function getTab(string $slug)
		{
			$FirstTab = Tag::query()->where('slug', $slug)->first();
			if (!$FirstTab) return [];

			return $this->parseSections($FirstTab->sections()->orderBy('page_order')->get());
		}

		/**
		 * @param string $slug
		 * @return array|int[]
		 */
		public function getPromotion(string $slug, string $casinoID): array
		{
			$Promotion = Promotion::query()->where('slug', $slug)->first();

			if (!$Promotion) {
				return [
					'error' => 404,
				];
			}

			$sections = [];

			if ($Promotion->banner) {
				$sections[] = $this->generateEmptySection(ContainerSection::PROMO_IMAGE->value, [
					'image' => $Promotion->bannerUrl
				]);
			}
			if ($Promotion->title) {
				$sections[] = $this->generateEmptySection(ContainerSection::PROMO_TITLE->value, [
					'title' => $Promotion->title
				]);
			}

			if ($Promotion->subtitle) {
				$sections[] = $this->generateEmptySection(ContainerSection::PROMO_SUBTITLE->value, [
					'subtitle' => $Promotion->subtitle
				]);
			}

			if ($Promotion->description) {
				$sections[] = $this->generateEmptySection(ContainerSection::PROMO_DESCRIPTION->value, [
					'text' => $Promotion->description
				]);
			}


			if ($Promotion->primaryAction) {
				$sections[] = $this->generateEmptySection(ContainerSection::PROMO_BUTTON->value, [
					'button' => $Promotion->primaryAction
				]);
			}


			if ($Promotion->terms) {
				$sections[] = $this->generateEmptySection(ContainerSection::SEO->value, [
					'title' => 'Terms & Conditions',
					'description' => $Promotion->terms
				]);
			}

			return $this->generateLayoutResponse($sections, $casinoID, $Promotion->seo);
		}

		private function generateGamePlayContainer(Game $game): array
		{
			$IS_FAVORITE = false;
			if (auth('casino')->check()) {
				$IS_FAVORITE = auth('casino')->user()->favorites()->get()->whereIn('id', [$game->id])->count() > 0;
			}

			return [
				'demo' => true,
				'favorite' => $IS_FAVORITE,
				'name' => $game->name,
				'slug' => $game->slug,
				'gameID' => $game->id,
				'rgs_game_id' => $game->game_id,
			];
		}

		/**
		 * @param string $slug
		 * @return array
		 */
		public function getGame(string $slug): array
		{
			$CurrentGame = Game::query()->where('slug', $slug)
				->where('active_on_site', 1)
				->first();
			if (!$CurrentGame) {
				return [
					'status' => 404,
					'message' => 'Page not found'
				];
			}

			$sections = [];
			$sections[] = $this->generateEmptySection('GamePlayContainer', $this->generateGamePlayContainer($CurrentGame));
			$sections[] = $this->generateEmptySection('FavouriteGamesContainer', (auth('casino')->check() ? GameResource::collection(auth('casino')->user()->favorites()->get())->jsonSerialize() : [])
			);
			return $this->generateLayoutResponse($sections, $CurrentGame->name);
		}

		/**
		 * @param $Provider
		 * @return array
		 */
		private function generateProviderDetailsSection($Provider)
		{
			$Limit = 36;
			$GamesData = $this->getGamesWithPagination(['provider' => $Provider->id], 1, $Limit);

			return [
				'id' => Str::uuid(),
				'container' => ContainerSection::CATEGORY_DETAILS->value,
				'appearance' => [
					'resolutionConfig' => $this->handleResolutionConfig([])
				],
				'data' => [
					'aspectRatioPercentage' => 127,
					'fetchUrl' => route('fe.provider-games', $Provider->slug),
					'resolutionConfig' => config('sections.categories_sections_config'),
					'title' => $Provider->name,
					'slug' => $Provider->slug,
					'icon' => '',
					'initialState' => [
						'size' => $Limit,
						'page' => $GamesData['page'],
						'totalItems' => $GamesData['totalItems'],
						'data' => $GamesData['data'],
					]
				]
			];
		}

		/**
		 * @param $slug
		 * @param Request $request
		 * @return array
		 */
		public function getProviderGames($slug, Request $request)
		{
			$page = $request->get('page');
			$Provider = Providers::query()->where('slug', $slug)->first();

			$GamesData = $this->getGamesWithPagination(['provider' => $Provider->id], $page);

			return [
				'data' => $GamesData['data'],
				'totalItems' => $GamesData['totalItems'],
				'page' => $GamesData['page'],
			];
		}

		/**
		 * @param array $filter
		 * @param int $page
		 * @param int $limit
		 * @return array
		 */
		private function getGamesWithPagination(array $filter, int $page, int $limit = 36): array
		{
			$GamesQuery = Game::query()
				->when(isset($filter['provider']), function ($query) use ($filter) {
					return $query->where('provider_id', (int)$filter['provider']);
				})
				->when(isset($filter['category']), function ($query) use ($filter) {
					$query->whereHas('categories', function ($query) use ($filter) {
						$query->where('categories.id', '=', $filter['category']);
					});
				})
				->when(auth('casino')->check(), function ($query) {
					$query->leftJoin('player_games', 'player_games.game_id', 'games.id');
					$query->orWhere('player_games.player_id', auth('casino')->user()->id);
				});

			$offset = ($page > 1) ? ($page - 1) * $limit : 0;
			$Total = $GamesQuery->count();
			$Games = GameResource::collection($GamesQuery->offset($offset)->limit($limit)->get());

			return [
				'totalItems' => $Total,
				'page' => $page,
				'data' => $Games,
			];
		}

		/**
		 * @param string $slug
		 * @return array
		 */
		public function getGamesProviders(string $slug): array
		{
			$CurrentProvider = Providers::query()->where('slug', $slug)
				->first();
			if (!$CurrentProvider) {
				return [
					'success' => false,
					'msg' => 'Provider not found',
					'code' => 404,
					'data' => []
				];
			}
			$ProviderSection = $this->generateProviderDetailsSection($CurrentProvider);

			return $this->generateLayoutResponse([$ProviderSection]);
		}

		/**
		 * @param $main
		 * @return array
		 */
		private function generateLayoutResponse($main, $casinoID, $seo = [])
		{
			$Menus = new MenuRepository();
			$currentCasino = $casinoID ?? config('casino.defaultCasinoId');
			return [
				'layout' => self::DEFAULT_LAYOUT,
				'seo' => $seo,
				'children' => [
					'main' => $main,
					'leftSidebar' => $Menus->menu("SIDEBAR", $currentCasino),
					'footer' => $Menus->menu('FOOTER', $currentCasino),
				],
				'restricted' => false,
			];
		}
	}
