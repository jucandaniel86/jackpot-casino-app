<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Repositories\PageGeneratorRepository;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class FeCategoryGamesController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(string $slug, Request $request, PageGeneratorRepository $generatorRepository): JsonResponse
		{
			return response()->json($generatorRepository->getCategoryGames($slug, $request));
		}
	}