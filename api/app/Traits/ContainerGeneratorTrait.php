<?php

	namespace App\Traits;

	use App\Enums\ContainerSection;
	use App\Enums\SectionStatus;
	use App\Http\Resources\GameResource;
	use App\Http\Resources\PromotionCardResource;
	use App\Http\Resources\ProviderResource;
	use App\Http\Resources\SliderResource;
	use App\Models\Promotion;
	use App\Models\Providers;
	use App\Models\Sliders;
	use App\Repositories\MenuRepository;
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
	}
