<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;
	use Lcobucci\JWT\JwtFacade;
	use PHPOpenSourceSaver\JWTAuth\JWTAuth;

	class NotificationController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request)
		{
			$user = $request->user();
			$last = $request->header('Last-Event-ID'); // browser îl pune automat după reconnect
			$since = $last ?: now()->subMinutes(10)->toISOString(); // la primul connect: ultimele 10 min

			return response()->stream(function () use ($user, $since) {
				$cursor = $since;

				while (true) {
					$items = $user->notifications()
						->where('created_at', '>', $cursor)
						->where('read_at', '=', null)
						->orderBy('created_at', 'asc')
						->limit(50)
						->get(['id', 'type', 'data', 'created_at']);

					foreach ($items as $n) {
						$cursor = $n->created_at->toISOString();

						// IMPORTANT: "id:" e cursorul, nu UUID-ul (poate fi orice string)
						echo "id: {$cursor}\n";
						echo "event: notification\n";
						echo "data: " . json_encode([
								'id' => $n->id,
								'type' => $n->type,
								'data' => $n->data,
								'created_at' => $cursor,
							]) . "\n\n";

						@ob_flush();
						@flush();
					}

					// heartbeat ca să nu taie proxy-ul conexiunea
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

		/**
		 * Marchează o notificare ca citită
		 * POST /api/notifications/{id}/read
		 */
		public function markAsRead(Request $request, string $id)
		{
			$user = $request->user(); // Player autenticat

			$notification = $user->notifications()
				->where('id', $id)
				->first();

			if (!$notification) {
				return response()->json([
					'error' => 'Notification not found'
				], 404);
			}

			if ($notification->read_at) {
				return response()->json([
					'ok' => true,
					'already_read' => true,
				]);
			}

			$notification->markAsRead();

			return response()->json([
				'ok' => true,
				'read_at' => $notification->read_at?->toISOString(),
			]);
		}
	}