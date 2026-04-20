<?php

	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Route;
	use App\Http\Controllers\Api\{UsersController,
		ProvidersController,
		BetController,
		BonusAdminController,
		CategoriesController,
		IconsController,
		PageController,
		SectionController,
		GameController,
		TagsController,
		PromotionController,
		SliderController,
		MenuController,
		WalletsController,
		PlayersController as ApiPlayersController,
		TreasuryDistributionController,
		StatsController,
		RiskController,
		FinanceOpsController,
		CryptoOpsController,
		CryptoTransactionsController,
		AdminWithdrawRequestController,
		CryptoOpsStatsController,
		JobRunsController,
		MarketingController,
		CasinosController
	};
	use App\Http\Controllers\Auth\{LoginController, LogoutController};
	use App\Http\Controllers\Api\Maintenance\RebaseCurrencyDataController;
	use App\Http\Controllers\Api\Admin\TournamentController as AdminTournamentController;
	use App\Http\Controllers\Api\Admin\BundleController as AdminBundleController;
	use App\Http\Controllers\Integrations\{BalanceController,
		BetController as IntegrationsBetController,
		RefundController,
		StartController
	};

	/*
	|--------------------------------------------------------------------------
	| API Routes
	|--------------------------------------------------------------------------
	|
	| Here is where you can register API routes for your application. These
	| routes are loaded by the RouteServiceProvider and all of them will
	| be assigned to the "api" middleware group. Make something great!
	|
	*/

	Route::middleware(['auth:api'])->get('/user', function (Request $request) {
		return ['user' => $request->user()];
	});
	Route::post('/login', LoginController::class);


	Route::middleware(['auth:api'])->group(function () {
		#logout
		Route::post('/logout', LogoutController::class);

		#users
		Route::prefix('users')->group(function () {
			Route::get('/list', [UsersController::class, 'getUsersList']);
			Route::post('/save', [UsersController::class, 'createUser']);
			Route::post('/update', [UsersController::class, 'updateUser']);
			Route::delete('/delete', [UsersController::class, 'deleteUser']);
			Route::post('/change-password', [UsersController::class, 'changePassword']);
			Route::get('/change-env', [UsersController::class, 'changeUserEnvironment']);
			Route::get('/change-casino', [UsersController::class, 'updateIntCasinoId']);
		});

		#bets
		Route::prefix('bets')->group(function () {
			Route::get('/search', [BetController::class, 'search']);
		});

		#categories
		Route::prefix('categories')->group(function () {
			Route::get('/list', [CategoriesController::class, 'list']);
			Route::post('/save', [CategoriesController::class, 'save']);
			Route::delete('/delete', [CategoriesController::class, 'remove']);
		});

		#providers
		Route::prefix('providers')->group(function () {
			Route::get('/list', [ProvidersController::class, 'list']);
			Route::post('/save', [ProvidersController::class, 'save']);
			Route::delete('/delete', [ProvidersController::class, 'remove']);
		});

		#icons
		Route::prefix('icons')->group(function () {
			Route::get('/list', [IconsController::class, 'list']);
			Route::post('/save', [IconsController::class, 'save']);
			Route::delete('/delete', [IconsController::class, 'remove']);
			Route::get('/import', [IconsController::class, 'import']);
		});

		#sections
		Route::prefix('sections')->group(function () {
			Route::post('/save-draft', [SectionController::class, 'saveDraftSection']);
			Route::post('/save-data', [SectionController::class, 'saveSectionData']);
			Route::delete('/delete', [SectionController::class, 'remove']);
			Route::post('/change-order', [SectionController::class, 'changeSectionOrder']);
			Route::get('/home-boxes', [SectionController::class, 'homeBoxes']);
		});

		#pages
		Route::prefix('pages')->group(function () {
			Route::get('/list', [PageController::class, 'list']);
			Route::post('/save', [PageController::class, 'save']);
			Route::delete('/delete', [PageController::class, 'remove']);
			Route::get('/get', [PageController::class, 'get']);
		});

		#games
		Route::prefix('games')->group(function () {
			Route::get('/list', [GameController::class, 'list']);
			Route::post('/save', [GameController::class, 'save']);
			Route::delete('/delete', [GameController::class, 'remove']);
			Route::get('/get', [GameController::class, 'get']);
			Route::get('/array', [GameController::class, 'getArray']);
			Route::get('/search', [GameController::class, 'lightSearch']);
			Route::get('/games-by-categories', [GameController::class, 'getGamesByCategories']);
			Route::get('/games-by-ids', [GameController::class, 'getByIds']);
			Route::get('/games-by-section', [GameController::class, 'getGamesBySection']);
		});

		#tags
		Route::prefix('tags')->group(function () {
			Route::get('/list', [TagsController::class, 'list']);
			Route::post('/save', [TagsController::class, 'save']);
			Route::delete('/delete', [TagsController::class, 'remove']);
			Route::get('/get', [TagsController::class, 'get']);
		});

		#promotions
		Route::prefix('promotions')->group(function () {
			Route::get('/list', [PromotionController::class, 'list']);
			Route::post('/save', [PromotionController::class, 'save']);
			Route::post('/save-draft', [PromotionController::class, 'saveDraft']);
			Route::delete('/delete', [PromotionController::class, 'remove']);
			Route::get('/get', [PromotionController::class, 'get']);
		});

		#sliders
		Route::prefix('sliders')->group(function () {
			Route::get('/list', [SliderController::class, 'list']);
			Route::post('/save', [SliderController::class, 'save']);
			Route::delete('/delete', [SliderController::class, 'remove']);
		});

		#menu
		Route::prefix('menu')->group(function () {
			Route::get('/list', [MenuController::class, 'list']);
			Route::post('/save', [MenuController::class, 'save']);
			Route::delete('/delete', [MenuController::class, 'remove']);
		});

		#wallet
		Route::prefix('wallet')->group(function () {
			Route::get('/list', [WalletsController::class, 'list']);
			Route::get('/currencies', [WalletsController::class, 'currencies']);
			Route::post('/save', [WalletsController::class, 'save']);
			Route::delete('/delete', [WalletsController::class, 'remove']);
			Route::get('/create-user-wallets', [WalletsController::class, 'createUserWallets']);
		});

		#players
		Route::prefix('players')->group(function () {
			Route::get('/list', [ApiPlayersController::class, 'getList']);
			Route::get('/activity', [ApiPlayersController::class, 'userActivityList']);
			Route::get('/wallets', [ApiPlayersController::class, 'userWallets']);
			Route::get('/sessions', [ApiPlayersController::class, 'userSessions']);
		});

		#statistics
		Route::prefix('stats')->group(function () {
			Route::get('/treasury-distribution', TreasuryDistributionController::class);
			Route::get('/today', [StatsController::class, 'today']);
			Route::get('/ggr', [StatsController::class, 'ggr']);
			Route::get('/top-games', [StatsController::class, 'topGames']);
			Route::get('/games', [StatsController::class, 'games']);
			Route::get('/risk/players', [RiskController::class, 'players']);
			Route::get('/risk/duplicates', [RiskController::class, 'duplicates']);
			Route::get('/risk/game-abuse', [RiskController::class, 'gameAbuse']);
			Route::get('/risk/overview', [RiskController::class, 'overview']);
			Route::get('/funnel', [StatsController::class, 'funnel']);
			Route::get('/finance-ops', [FinanceOpsController::class, 'index']);
			Route::get('/crypto-ops', [CryptoOpsController::class, 'index']);
			Route::get('/transactions', CryptoTransactionsController::class);
			Route::get('/sweeps', CryptoOpsStatsController::class);
		});

		Route::prefix('crypto')->group(function () {
			Route::get('/sweeps', [CryptoOpsStatsController::class, 'listSweeps']);
		});

		#system
		Route::prefix('system')->group(function () {
			Route::get('/job-runs', [JobRunsController::class, 'index']);
			Route::get('/job-runs/{uuid}', [JobRunsController::class, 'show']);
		});

		#marketing
		Route::prefix('marketing')->group(function () {
			Route::get('/overview', [MarketingController::class, 'overview']);
			Route::get('/cohorts', [MarketingController::class, 'cohorts']);
			Route::get('/games', [MarketingController::class, 'games']);
			Route::get('/segments', [MarketingController::class, 'segments']);
			Route::get('/funnel', [MarketingController::class, 'funnel']);
		});

		#casinos
		Route::get('/casinos', CasinosController::class);

		#withdraws
		Route::get('/withdraw-requests', [AdminWithdrawRequestController::class, 'index']);
		Route::post('/withdraw-requests/{uuid}/approve', [AdminWithdrawRequestController::class, 'approve']);
		Route::post('/withdraw-requests/{uuid}/reject', [AdminWithdrawRequestController::class, 'reject']);
		Route::post('/withdraw-requests/{uuid}/complete', [AdminWithdrawRequestController::class, 'complete']);

		#admin tournaments
		Route::prefix('admin')->group(function () {
			Route::get('/tournaments', [AdminTournamentController::class, 'index']);
			Route::get('/tournaments/{id}', [AdminTournamentController::class, 'show']);
			Route::post('/tournaments', [AdminTournamentController::class, 'store']);
			Route::put('/tournaments/{id}', [AdminTournamentController::class, 'update']);
			Route::delete('/tournaments/{id}', [AdminTournamentController::class, 'destroy']);

			Route::get('/bundles', [AdminBundleController::class, 'index']);
			Route::get('/bundles/{id}', [AdminBundleController::class, 'show']);
			Route::post('/bundles', [AdminBundleController::class, 'store']);
			Route::put('/bundles/{id}', [AdminBundleController::class, 'update']);
			Route::delete('/bundles/{id}', [AdminBundleController::class, 'destroy']);
		});

		#bonuses
		Route::prefix('bonuses')->group(function () {
			Route::get('/rules', [BonusAdminController::class, 'rules']);
			Route::get('/rules/get', [BonusAdminController::class, 'rule']);
			Route::post('/rules/save', [BonusAdminController::class, 'saveRule']);
			Route::delete('/rules/delete', [BonusAdminController::class, 'removeRule']);
			Route::post('/rules/toggle', [BonusAdminController::class, 'toggleRule']);

			Route::post('/manual/preview', [BonusAdminController::class, 'previewManual']);
			Route::post('/manual/grant', [BonusAdminController::class, 'grantManual']);

			Route::get('/grants', [BonusAdminController::class, 'grants']);
			Route::get('/grants/events', [BonusAdminController::class, 'grantEvents']);
			Route::get('/stats', [BonusAdminController::class, 'stats']);

			Route::post('/tests/run', [BonusAdminController::class, 'runTest']);
			Route::get('/tests/runs', [BonusAdminController::class, 'testRuns']);
			Route::get('/tests/runs/get', [BonusAdminController::class, 'testRun']);
			Route::get('/tests/runs/logs', [BonusAdminController::class, 'testRunLogs']);
		});
	});

	//provider integrations
	Route::post('/wallet-callback/balance', BalanceController::class)->middleware(['throttle:provider-callback', 'provider.idem:gameforge,balance']);
	Route::post('/wallet-callback/betwin', IntegrationsBetController::class)->middleware(['throttle:provider-callback', 'provider.idem:gameforge,bet']);
	Route::post('/wallet-callback/refund', RefundController::class)->middleware(['throttle:provider-callback', 'provider.idem:gameforge,refund']);

	// public mail preview
	Route::get('/view-emails', \App\Http\Controllers\Api\ViewEmailsController::class);
	Route::get('/send-email-preview', [\App\Http\Controllers\Api\ViewEmailsController::class, 'send']);

	// public maintenance endpoint (no auth)
	Route::match(['get', 'post'], '/maintenance/rebase-currency-data', RebaseCurrencyDataController::class);

	//internal casino integration
	Route::post('/casino/wallet-callback/start', StartController::class)->middleware('throttle:provider-callback');
	Route::post('/casino/wallet-callback/balance', [BalanceController::class, 'casinoBalance'])->middleware('throttle:provider-callback');
	Route::post('/casino/wallet-callback/betwin', [IntegrationsBetController::class, 'casinoBetWin'])->middleware('throttle:provider-callback');
	Route::post('/casino/wallet-callback/refund', [RefundController::class, 'casinoRefund'])->middleware('throttle:provider-callback');
