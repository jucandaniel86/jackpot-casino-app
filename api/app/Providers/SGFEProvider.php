<?php

	namespace App\Providers;

	use App\Interfaces\BetInterface;
	use App\Interfaces\BonusAdminInterface;
	use App\Interfaces\CategoriesInterface;

	use App\Interfaces\GameInterface;
	use App\Interfaces\GameSessionsInterface;
	use App\Interfaces\IconsInterface;
	use App\Interfaces\MenuInterface;
	use App\Interfaces\PageGeneratorInterface;
	use App\Interfaces\PageInterface;
	use App\Interfaces\PlayersInterface;
	use App\Interfaces\PromotionInterface;
	use App\Interfaces\ProvidersInterface;
	use App\Interfaces\SearchInterface;
	use App\Interfaces\SectionInterface;
	use App\Interfaces\SliderInterface;
	use App\Interfaces\TagsInterface;
	use App\Interfaces\UserInterface;
	use App\Interfaces\WalletInterface;
	use App\Repositories\BetRepository;
	use App\Repositories\BonusAdminRepository;
	use App\Repositories\CategoriesRepository;

	use App\Repositories\GameRepository;
	use App\Repositories\GameSessionsRepository;
	use App\Repositories\IconsRepository;
	use App\Repositories\MenuRepository;
	use App\Repositories\PageGeneratorRepository;
	use App\Repositories\PageRepository;
	use App\Repositories\PlayersRepository;
	use App\Repositories\PromotionRepository;
	use App\Repositories\ProvidersRepository;
	use App\Repositories\SearchRepository;
	use App\Repositories\SectionRepository;
	use App\Repositories\SliderRepository;
	use App\Repositories\TagsRepository;
	use App\Repositories\UserRepository;
	use App\Repositories\WalletRepository;
	use Illuminate\Support\ServiceProvider;

	class SGFEProvider extends ServiceProvider
	{
		/**
		 * Register services.
		 */
		public function register(): void
		{
			$this->app->bind(UserInterface::class, UserRepository::class);
			$this->app->bind(CategoriesInterface::class, CategoriesRepository::class);
			$this->app->bind(ProvidersInterface::class, ProvidersRepository::class);
			$this->app->bind(IconsInterface::class, IconsRepository::class);
			$this->app->bind(SectionInterface::class, SectionRepository::class);
			$this->app->bind(PageInterface::class, PageRepository::class);
			$this->app->bind(GameInterface::class, GameRepository::class);
			$this->app->bind(PageGeneratorInterface::class, PageGeneratorRepository::class);
			$this->app->bind(SearchInterface::class, SearchRepository::class);
			$this->app->bind(TagsInterface::class, TagsRepository::class);
			$this->app->bind(PromotionInterface::class, PromotionRepository::class);
			$this->app->bind(SliderInterface::class, SliderRepository::class);
			$this->app->bind(PlayersInterface::class, PlayersRepository::class);
			$this->app->bind(MenuInterface::class, MenuRepository::class);
			$this->app->bind(WalletInterface::class, WalletRepository::class);
			$this->app->bind(GameSessionsInterface::class, GameSessionsRepository::class);
			$this->app->bind(BetInterface::class, BetRepository::class);
			$this->app->bind(BonusAdminInterface::class, BonusAdminRepository::class);
		}

		/**
		 * Bootstrap services.
		 */
		public function boot(): void
		{
			//
		}
	}
