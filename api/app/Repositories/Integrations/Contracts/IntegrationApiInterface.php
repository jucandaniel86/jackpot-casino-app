<?php

	namespace App\Repositories\Integrations\Contracts;

	use Illuminate\Http\Request;

	interface IntegrationApiInterface
	{
		/**
		 * @param $payload
		 *  {
		 * "user_info": {
		 *    "user_id": "string",
		 *    "nickname": "string",
		 *    "is_demo": true,
		 *    "country": "string"
		 * },
		 * "session_id": "string",
		 * "currency": "string",
		 * "brand_id": "string",
		 * "game_id": "string",
		 * "urls": {
		 * "  deposit_url": "string",
		 *    "return_url": "string"
		 * },
		 * "language": "string"
		 * }
		 * @return array
		 */
		public function start($payload);

		/**
		 * @param Request $request
		 * {
		 *    "user_id": "string",
		 *    "currency": "string",
		 *    "session_id": "string",
		 *    "ts": 1757000534817
		 * }
		 * @return array
		 */
		public function balance(Request $request);

		/**
		 * @param Request $request
		 * @return mixed
		 */
		public function betwin(Request $request);
	}