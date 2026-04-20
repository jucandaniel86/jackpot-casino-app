<?php

	namespace App\Repositories;

	use App\Exceptions\ApiResponseException;
	use App\Interfaces\UserInterface;
	use App\Models\User;
	use App\Traits\QueryTrait;
	use Illuminate\Database\QueryException;
	use Illuminate\Support\Facades\Hash;

	class UserRepository implements UserInterface
	{
		use QueryTrait;

		public function getUsersList(array $params = []): array
		{
			$search = [];

			$search[] = [
				"condition" => isset($params['search']) && $params['search'] != "" && strlen($params['search']) > 2,
				"query" => "name LIKE '%{$params['search']}%' OR email LIKE '%{$params['search']}%' "
			];

			return $this->getFilteredList(User::query(), $params, $search);
		}

		public function createUser(array $params = [])
		{
			try {
				$User = (isset($params['id']) ? User::find($params['id']) : new User());
				$User->name = $params['name'];
				$User->email = $params['email'];
				if (isset($params['password']) && $params['password'] !== "") {
					$User->password = bcrypt($params['password']);
				}
		 		$User->save();
 				return $User;

			} catch (QueryException $exception) {
				activity()
					->causedBy(null)
					->withProperties([
							'message' => $exception->getMessage(),
							'line' => $exception->getLine(),
							'code' => $exception->getCode(),
							'file' => $exception->getFile()
						])
					->log(config('errors.31'));
				throw  new ApiResponseException($exception->getMessage());
			}
		}

		public function deleteUser($id)
		{
			$result = $this->deleteByID(User::query(), $id);
 			return $result;
		}

		public function changePassword($params)
		{
			$userID = (int)$params['id'];
			$User = User::find($userID);

			if (!$User) {
				throw new ApiResponseException("Invalid User");
			}

			$oldPassword = $params['old_password'];
			$new_password = $params['new_password'];

			if (!Hash::check($oldPassword, $User->password)) {
				throw new ApiResponseException("Passwords not match");
			}

			$User->password = Hash::make($new_password);
			$User->save();

			return true;
		}
	}