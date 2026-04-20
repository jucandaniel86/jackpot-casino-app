<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Repositories\PageGeneratorRepository;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class PromotionController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke($slug, Request $request, PageGeneratorRepository $generatorRepository): JsonResponse
		{
			return response()->json($generatorRepository->getPromotion($slug,  $request->get('casino_id')));
		}
	}
