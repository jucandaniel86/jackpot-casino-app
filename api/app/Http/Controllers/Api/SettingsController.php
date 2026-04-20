<?php

	namespace App\Http\Controllers\Api;

	use App\Http\Controllers\Controller;
	use App\Http\Requests\SettingRequest;
	use App\Http\Responses\ApiResponseClass;
	use App\Interfaces\SettingsInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class SettingsController extends Controller
	{
		private SettingsInterface $settings;

		public function __construct(SettingsInterface $settings)
		{
			$this->settings = $settings;
		}

		/**
		 * @url /api/settings
		 * @method GET
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function settings(Request $request): JsonResponse
		{
			$request->validate([
				'operator_id' => 'required'
			]);

			return ApiResponseClass::sendResponse($this->settings->formattedSettings($request->all()), '');
		}

		/**
		 * @url /api/settings/save
		 * @method POST
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function save(Request $request): JsonResponse
		{
			$result = $this->settings->save($request->all());
			if (!$result['success']) {
				return ApiResponseClass::sendError($result, '');
			}
			return ApiResponseClass::sendResponse($result, '');
		}

		/**
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function getLoaderStyles(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->settings->getLoaderStyles($request->all()), '');
		}

		/**
		 * @url /api/settings/save-loader-logo
		 * @method POST
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function saveLoaderLogo(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->settings->saveLoaderLogo($request, $request->get('operatorID')), '');
		}

		/**
		 * @url /api/settings/save-loader-styles
		 * @method POST
		 * @param Request $request
		 * @return JsonResponse
		 */
		public function saveLoaderStyle(Request $request): JsonResponse
		{
			return ApiResponseClass::sendResponse($this->settings->saveLoaderStyle($request->all()), '');
		}
	}