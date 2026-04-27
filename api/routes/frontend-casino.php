<?php

use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\IconsController;
use App\Http\Controllers\FE\ExchangeRateController;
use App\Http\Controllers\FE\FeCategoryController;
use App\Http\Controllers\FE\FeCategoryGamesController;
use App\Http\Controllers\FE\FeGameController;
use App\Http\Controllers\FE\FePageController;
use App\Http\Controllers\FE\ForgotPasswordController;
use App\Http\Controllers\FE\GameSessionController;
use App\Http\Controllers\FE\NotificationController;
use App\Http\Controllers\FE\PlayersController;
use App\Http\Controllers\FE\PromotionController as PromoController;
use App\Http\Controllers\FE\ProviderController;
use App\Http\Controllers\FE\SearchController;
use App\Http\Controllers\FE\TabController;
use App\Http\Controllers\FE\TransactionsController;
use App\Http\Controllers\FE\TournamentLeaderboardController;
use App\Http\Controllers\FE\WalletController;
use App\Http\Controllers\FE\WinStreamController;
use App\Http\Controllers\FE\WithdrawRequestsController;
use App\Http\Controllers\FE\WithdrawRequestsHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['payload.crypto:both', 'casino.id.decode'])->group(function () {
	Route::middleware('throttle:casino-public')->group(function () {
		Route::get('/page/{slug}', FePageController::class);
		Route::get('/game/{slug}', FeGameController::class);
		Route::get('/provider/{slug}', ProviderController::class);
		Route::get('/provider/games/{slug}', [ProviderController::class, 'games'])->name('fe.provider-games');
		Route::get('/category/{slug}', FeCategoryController::class);
		Route::get('/category/games/{slug}', FeCategoryGamesController::class)->name('fe:category-games');
		Route::get('/tournaments/{id}/leaderboard', [TournamentLeaderboardController::class, 'leaderboard']);
		Route::get('/icons', [IconsController::class, 'list']);
		Route::get('/search', SearchController::class);
		Route::get('/tabs-container/{slug}', TabController::class)->name('fe:tab-container');
		Route::get('/promotion/{slug}', PromoController::class);
		Route::get('/search/game', [SearchController::class, 'game']);
		Route::get('/fx/rate', [ExchangeRateController::class, 'show']);
		Route::get('/import-games', [GameController::class, 'importGames']);
	});

	Route::middleware('throttle:casino-auth')->group(function () {
		Route::post('/registration', [PlayersController::class, 'registration']);
		Route::post('/forgot-password', [ForgotPasswordController::class, 'requestReset']);
		Route::post('/forgot-password/validate', [ForgotPasswordController::class, 'validateToken']);
		Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'reset']);
	});

	Route::post('/demo', [GameSessionController::class, 'demo'])->middleware('throttle:casino-gameplay');
	Route::get('/wins/stream', WinStreamController::class)->middleware('throttle:casino-stream');

	Route::prefix('player')->group(function () {
		Route::middleware('throttle:casino-auth')->group(function () {
			Route::post('/login', [PlayersController::class, 'login']);
			Route::post('/login/verify', [PlayersController::class, 'verifyLogin']);
			Route::post('/refresh', [PlayersController::class, 'refresh']);
			Route::post('/wallet-connect', [PlayersController::class, 'walletConnectLogin']);
		});

		Route::middleware(['auth:casino', 'throttle:casino-player'])->group(function () {
			Route::post('/logout', [PlayersController::class, 'logout']);
			Route::post('/2fa/setup', [PlayersController::class, 'twoFactorSetup']);
			Route::post('/2fa/enable', [PlayersController::class, 'twoFactorEnable']);
			Route::post('/2fa/disable', [PlayersController::class, 'twoFactorDisable']);
			Route::post('/save-profile', [PlayersController::class, 'savePlayerProfile']);
			Route::post('/save-settings', [PlayersController::class, 'savePlayerSettings']);
			Route::get('/profile', [PlayersController::class, 'profile']);
			Route::post('/change-password', [PlayersController::class, 'password']);
			Route::post('/play', [GameSessionController::class, 'start'])->middleware('throttle:casino-gameplay');
			Route::post('/favorite', [PlayersController::class, 'favorite']);
			Route::get('/favorite-games', [PlayersController::class, 'getFavGames']);
			Route::get('/bets', [PlayersController::class, 'bets']);

			Route::get('/transactions', TransactionsController::class);
			Route::get('/notifications/stream', NotificationController::class)->middleware('throttle:casino-stream');
			Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);

			Route::get('/wallets', WalletController::class);
			Route::get('/wallets/current', [WalletController::class, 'current']);
			Route::post('/wallets/current', [WalletController::class, 'setCurrent']);

			Route::get('/tournaments/{id}/standing', [TournamentLeaderboardController::class, 'standing']);

			Route::get('/withdraw-requests', WithdrawRequestsHistoryController::class);
			Route::post('/withdraw-requests', WithdrawRequestsController::class);
		});
	});
});
