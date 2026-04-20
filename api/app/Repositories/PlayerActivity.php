<?php

	namespace App\Repositories;

	use App\Enums\PlayerActivityEnums;
	use App\Models\Login;
	use App\Models\Player;
	use Illuminate\Http\Request;
	use Illuminate\Contracts\Auth\Factory;
	use App\Models\PlayerActivity as Activity;
	use App\Services\GeoData;
	use Auth;

	class PlayerActivity
	{
		/**
		 * @var Request
		 */
		private $request;
		/**
		 * @var Factory
		 */
		private $auth;

		/**
		 * @var Player|null
		 */
		protected $user = null;

		public function __construct(Request $request)
		{
			$this->request = $request;
		}

		/**
		 * Log user action.
		 *
		 * @param $description
		 * @return static
		 */
		public function log(PlayerActivityEnums $type, $description = '', $system = "user", $item_id = null)
		{
			$data = GeoData::get_data();

			if ($type === PlayerActivityEnums::USER_LOGIN) {
				Login::create([
					"device_type" => $data['device'],
					"platform" => $data['os'],
					"browser" => $data['browser'],
					"country" => $data['country'],
					"city" => $data['city'],
					"ip" => $this->request->server('REMOTE_ADDR'),
					'user_agent' => $this->getUserAgent(),
					"authenticatable_type" => "App\Models\Player",
					"authenticatable_id" => $this->getUserId(),
					"created_at" => now(),
					'expires_at' => now()->addMinutes(30)->toDate()
				]);
			}

			return Activity::create([
					'old' => '',
					'description' => $description,
					'type' => $type,
					'system' => $system,
					'item_id' => $item_id,
					'user_id' => $this->getUserId(),
					'ip_address' => $this->request->server('REMOTE_ADDR'),
					'user_agent' => $this->getUserAgent()
				] + $data);
		}

		/**
		 * Get id if the user for who we want to log this action.
		 * If user was manually set, then we will just return id of that user.
		 * If not, we will return the id of currently logged user.
		 *
		 * @return int|mixed|null
		 */
		private function getUserId()
		{
			try {
				$id = Auth::guard('casino')->user()->id;
			} catch (\Exception $e) {
				$id = 1;
			}
			return $id;
		}

		/**
		 * Get user agent from request headers.
		 *
		 * @return string
		 */
		public function getUserAgent()
		{
			return substr((string)$this->request->header('User-Agent'), 0, 500);
		}

		/**
		 * @param User|null $user
		 */
		public function setUser($user)
		{
			$this->user = $user;
		}

		/**
		 * @param array $params
		 * @return array
		 */
		public function userList(array $params = [])
		{
			$start = (isset($params['start'])) ? $params['start'] : 0;
			$length = (isset($params['length']) && $params['length'] > 0) ? $params['length'] : 50;
			$offset = ($start - 1) * $length;

			$ActivityList = Activity::query()
				->where('user_id', $params['user_id'])
				->orderBy('created_at', 'DESC');

			return [
				"success" => true,
				'total' => $ActivityList->count(),
				'items' => $ActivityList->limit($length)
					->offset($offset)
					->get()
			];
		}

	}