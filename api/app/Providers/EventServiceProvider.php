<?php

	namespace App\Providers;

	use Illuminate\Auth\Events\Registered;
	use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
	use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
	use Illuminate\Support\Facades\Event;

	class EventServiceProvider extends ServiceProvider
	{
		/**
		 * The event to listener mappings for the application.
		 *
		 * @var array<class-string, array<int, class-string>>
		 */
		protected $listen = [
			Registered::class => [
				SendEmailVerificationNotification::class,
			],
			\App\Events\DepositDetected::class => [
				\App\Listeners\SendDepositNotification::class,
				\App\Listeners\GrantFirstDepositBonus::class,
				\App\Listeners\TriggerSweepOnDeposit::class,
			],
			\App\Events\WithdrawApproved::class => [
				\App\Listeners\SendWithdrawStatusEmail::class,
			],
			\App\Events\WithdrawRejected::class => [
				\App\Listeners\SendWithdrawStatusEmail::class,
			],
			\App\Events\WithdrawCompleted::class => [
				\App\Listeners\SendWithdrawStatusEmail::class,
			],
			\App\Events\WithdrawRequested::class => [
				\App\Listeners\SendWithdrawRequestedEmail::class,
			],
			\App\Events\PlayerRegistered::class => [
				\App\Listeners\SendWelcomeEmail::class,
			],
		];

		/**
		 * Register any events for your application.
		 */
		public function boot(): void
		{
			//
		}

		/**
		 * Determine if events and listeners should be automatically discovered.
		 */
		public function shouldDiscoverEvents(): bool
		{
			return false;
		}
	}
