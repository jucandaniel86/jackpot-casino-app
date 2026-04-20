<?php

	namespace App\Providers;

	use App\Models\Player;
	use App\Observers\PlayerObserver;
	use Illuminate\Support\ServiceProvider;
	use Illuminate\Mail\Events\MessageSending;
	use Illuminate\Mail\Events\MessageSent;
	use Illuminate\Support\Facades\Event;
	use Illuminate\Support\Facades\Log;

	class AppServiceProvider extends ServiceProvider
	{
		/**
		 * Register any application services.
		 */
		public function register(): void
		{
			$this->app->bind(
				\App\Repositories\System\Contracts\JobLogServiceInterface::class,
				\App\Repositories\System\Services\JobLogService::class
			);
		}

		/**
		 * Bootstrap any application services.
		 */
		public function boot(): void
		{
			Player::observe(PlayerObserver::class);

			Event::listen([MessageSending::class, MessageSent::class], function (object $event) {
				$message = $event->message ?? null;
				if (!$message || !method_exists($message, 'getSubject')) {
					return;
				}

				$to = method_exists($message, 'getTo') ? array_keys($message->getTo() ?? []) : [];
				$from = method_exists($message, 'getFrom') ? array_keys($message->getFrom() ?? []) : [];

				if ($event instanceof MessageSending) {
					Log::info('Mail sending', [
						'to' => $to,
						'from' => $from,
						'subject' => $message->getSubject(),
					]);
				}

				if ($event instanceof MessageSent) {
					Log::info('Mail sent', [
						'to' => $to,
						'from' => $from,
						'subject' => $message->getSubject(),
					]);
				}
			});
		}
	}
