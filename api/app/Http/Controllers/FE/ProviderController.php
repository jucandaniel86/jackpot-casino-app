<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Repositories\PageGeneratorRepository;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class ProviderController extends Controller
	{
		public function __invoke(string $slug, Request $request, PageGeneratorRepository $generatorRepository): JsonResponse
		{
			return response()->json($generatorRepository->getGamesProviders($slug));
		}

		public function games(string $slug, Request $request, PageGeneratorRepository $generatorRepository): JsonResponse
		{
			return response()->json($generatorRepository->getProviderGames($slug, $request));
		}
	}