<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	class TestController extends Controller
	{
		public function test()
		{
			$API_PATHS_PEP = [
				"balance" => "https://api.pepagy.casino/api/casino/wallet-callback/balance",
				"start" => "https://api-casino.codebuilders.ro/api/casino/wallet-callback/start",
				"betwin" => "https://api.pepagy.casino/api/casino/wallet-callback/betwin",
				"refund" => "https://api.pepagy.casino/api/casino/wallet-callback/refund"
			];

			echo json_encode($API_PATHS_PEP);
		}
	}