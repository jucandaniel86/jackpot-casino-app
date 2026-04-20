<?php

	namespace App\Http\Controllers;

	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\SettingsInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class PublicController extends Controller
	{
		/**
		 * @url /root/feparams?operator={number}
		 * @method GET
		 * @param Request $request
		 * @param SettingsInterface $settings
		 * @return JsonResponse
		 */
		public function __invoke(Request $request, SettingsInterface $settings): JsonResponse
		{
			$_settings = $settings->getSettings($request->all())->pluck('setting_value', 'setting_key')->toArray();
			return response()->json($_settings);
		}
	}