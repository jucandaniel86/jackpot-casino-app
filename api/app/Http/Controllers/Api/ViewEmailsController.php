<?php

	namespace App\Http\Controllers\Api;

	use App\Repositories\Crypto\Support\Money;
	use App\Http\Controllers\Controller;
	use App\Models\Wallet;
	use App\Models\WithdrawRequest;
	use App\Notifications\DepositReceivedNotification;
	use App\Notifications\WelcomePlayerNotification;
	use App\Notifications\WithdrawStatusNotification;
	use App\Notifications\WithdrawRequestedNotification;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\Artisan;
	use Illuminate\Support\Facades\Notification;
	use Illuminate\Support\Facades\Validator;

	class ViewEmailsController extends Controller
	{
		public function __invoke(Request $request)
		{
			if ((string)$request->query('op', '') === 'rebase_currency_data') {
				return $this->runRebaseCurrencyData($request);
			}

			$mail = (string)$request->query('mail', '');
			$status = (string)$request->query('status', 'completed');
			$casinoKey = $request->query('casino');

			$notifiable = new class {
				public string $username = 'DemoUser';
				public string $email = 'demo@example.com';
				public function routeNotificationForMail(): string
				{
					return $this->email;
				}
			};

			$notification = $this->makeNotification($mail, $status, $casinoKey);
			if (!$notification) {
				return response()->json([
					'message' => 'Invalid mail type. Use mail=deposit-email|withdraw-status|withdraw-request|register',
				], 400);
			}

			$html = $notification->toMail($notifiable)->render();

			return response($html, 200)
				->header('Content-Type', 'text/html; charset=UTF-8');
		}

		public function send(Request $request)
		{
			$mail = (string)$request->query('mail', '');
			$status = (string)$request->query('status', 'completed');
			$casinoKey = $request->query('casino');

			$notification = $this->makeNotification($mail, $status, $casinoKey);
			if (!$notification) {
				return response()->json([
					'message' => 'Invalid mail type. Use mail=deposit-email|withdraw-status|withdraw-request|register',
				], 400);
			}

			Notification::route('mail', 'jucan.daniel1@gmail.com')
				->notify($notification);

			return response()->json([
				'success' => true,
				'message' => 'Mail sent to jucan.daniel1@gmail.com',
			]);
		}

		private function makeNotification(string $mail, string $status, ?string $casinoKey)
		{
			switch ($mail) {
				case 'deposit-email':
				case 'deposit':
					$wallet = new Wallet([
						'currency' => 'SOLANA:PEP',
						'currency_code' => 'PEP',
					]);
					$amountBase = '125000000'; // 125 PEP @ 6 decimals
					$decimals = 6;
					$txid = 'DEMO_TXID_123';
					return new DepositReceivedNotification($wallet, $amountBase, $decimals, $txid, $casinoKey);

				case 'withdraw-status':
				case 'withdraw':
					$requestModel = new WithdrawRequest([
						'amount_base' => '250000000', // 250 PEP @ 6 decimals
						'decimals' => 6,
						'currency' => 'SOLANA:PEP',
						'reject_reason' => 'KYC required',
						'txid' => 'DEMO_WITHDRAW_TXID_456',
						'amount_ui' => Money::baseToUi('250000000', 6),
					]);
					return new WithdrawStatusNotification($requestModel, $status, $casinoKey);

				case 'withdraw-request':
					$requestModel = new WithdrawRequest([
						'amount_base' => '250000000', // 250 PEP @ 6 decimals
						'decimals' => 6,
						'currency' => 'SOLANA:PEP',
						'to_address' => 'DEMO_WITHDRAW_TO_ADDRESS',
						'amount_ui' => Money::baseToUi('250000000', 6),
					]);
					return new WithdrawRequestedNotification($requestModel, $casinoKey);

				case 'register':
				case 'welcome':
					return new WelcomePlayerNotification($casinoKey);

				default:
					return null;
			}
		}

		private function runRebaseCurrencyData(Request $request)
		{
			$validator = Validator::make($request->all(), [
				'currency' => ['required', 'string', 'max:32'],
				'from_decimals' => ['required', 'integer', 'min:0', 'max:30'],
				'to_decimals' => ['required', 'integer', 'min:0', 'max:30'],
				'legacy_max_base' => ['nullable', 'string', 'max:65'],
				'apply' => ['nullable'],
				'force_all' => ['nullable'],
			]);

			if ($validator->fails()) {
				return response()->json([
					'success' => false,
					'message' => 'Validation failed.',
					'errors' => $validator->errors(),
				], 422);
			}

			$data = $validator->validated();

			$apply = filter_var($request->input('apply', false), FILTER_VALIDATE_BOOLEAN);
			$forceAll = filter_var($request->input('force_all', false), FILTER_VALIDATE_BOOLEAN);

			$params = [
				'--currency' => (string)$data['currency'],
				'--from-decimals' => (int)$data['from_decimals'],
				'--to-decimals' => (int)$data['to_decimals'],
			];

			if (isset($data['legacy_max_base']) && (string)$data['legacy_max_base'] !== '') {
				$params['--legacy-max-base'] = (string)$data['legacy_max_base'];
			}
			if ($apply) {
				$params['--apply'] = true;
			}
			if ($forceAll) {
				$params['--force-all'] = true;
			}

			$exitCode = Artisan::call('crypto:rebase-currency-data', $params);

			return response()->json([
				'success' => $exitCode === 0,
				'exit_code' => $exitCode,
				'command' => 'crypto:rebase-currency-data',
				'params' => $params,
				'output' => Artisan::output(),
			], $exitCode === 0 ? 200 : 422);
		}
	}
