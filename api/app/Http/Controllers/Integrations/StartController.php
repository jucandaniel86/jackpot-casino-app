<?php

	namespace App\Http\Controllers\Integrations;

	use App\Http\Controllers\Controller;
	use Illuminate\Http\Request;

	class StartController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request)
		{
			$registry = app(\App\Repositories\Integrations\Services\IntegrationApiService::class);
			return $registry->start($request->all());
		}
	}