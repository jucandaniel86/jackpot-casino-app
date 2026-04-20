<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Models\Casino;
	use Illuminate\Http\Request;

	class CasinosController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request)
		{
			$casinos = Casino::query()
				->select(['id', 'uuid', 'int_casino_id', 'brand_id', 'username', 'name', 'active', 'logo'])
				->orderBy('id')
				->get()
				->map(function (Casino $casino) {
					$publicCasinoId = $casino->uuid ?: ($casino->brand_id ?: $casino->username);

					return [
						'id' => $casino->id,
						'int_casino_id' => $casino->int_casino_id,
						'public_casino_id' => $publicCasinoId,
						'brand_id' => $casino->brand_id,
						'username' => $casino->username,
						'name' => $casino->name,
						'active' => $casino->active,
            'logo' => $casino->logo_absolute_path
					];
				});

			return response()->json($casinos);
		}
	}
