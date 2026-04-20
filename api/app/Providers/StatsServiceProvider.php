<?php

	namespace App\Providers;

	use App\Repositories\Stats\Contracts\GgrReportServiceInterface;
	use App\Repositories\Stats\Contracts\TopGamesServiceInterface;
	use App\Repositories\Stats\Services\GgrReportService;
	use App\Repositories\Stats\Services\TopGamesService;
	use Illuminate\Support\ServiceProvider;
	use App\Repositories\Stats\Contracts\DashboardStatsServiceInterface;
	use App\Repositories\Stats\Services\DashboardStatsService;
	use App\Repositories\Stats\Contracts\GamesTabServiceInterface;
	use App\Repositories\Stats\Services\GamesTabService;
	use App\Repositories\Stats\Contracts\RiskReportServiceInterface;
	use App\Repositories\Stats\Services\RiskReportService;
	use App\Repositories\Stats\Contracts\ConversionFunnelServiceInterface;
	use App\Repositories\Stats\Services\ConversionFunnelService;
	use App\Repositories\Stats\Contracts\FinanceOpsDashboardServiceInterface;
	use App\Repositories\Stats\Services\FinanceOpsDashboardService;
	use App\Repositories\Stats\Contracts\CryptoOpsStatsServiceInterface;
	use App\Repositories\Stats\Services\CryptoOpsStatsService;
	use App\Repositories\Stats\Contracts\CryptoTransactionsServiceInterface;
	use App\Repositories\Stats\Services\CryptoTransactionsService;
	use App\Repositories\Stats\Contracts\CryptoOpsDashboardServiceInterface;
	use App\Repositories\Stats\Services\CryptoOpsDashboardService;

	class StatsServiceProvider extends ServiceProvider
	{
		/**
		 * Register services.
		 */
		public function register(): void
		{
			$this->app->bind(DashboardStatsServiceInterface::class, DashboardStatsService::class);
			$this->app->bind(TopGamesServiceInterface::class, TopGamesService::class);
			$this->app->bind(GgrReportServiceInterface::class, GgrReportService::class);
			$this->app->bind(GamesTabServiceInterface::class, GamesTabService::class);
			$this->app->bind(RiskReportServiceInterface::class, RiskReportService::class);
			$this->app->bind(ConversionFunnelServiceInterface::class, ConversionFunnelService::class);
			$this->app->bind(FinanceOpsDashboardServiceInterface::class, FinanceOpsDashboardService::class);
			$this->app->bind(CryptoOpsStatsServiceInterface::class, CryptoOpsStatsService::class);
			$this->app->bind(CryptoTransactionsServiceInterface::class, CryptoTransactionsService::class);
			$this->app->bind(CryptoOpsDashboardServiceInterface::class, CryptoOpsDashboardService::class);
		}

		/**
		 * Bootstrap services.
		 */
		public function boot(): void
		{
			//
		}
	}