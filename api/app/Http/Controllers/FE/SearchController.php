<?php

	namespace App\Http\Controllers\Fe;

	use App\Http\Controllers\Controller;
	use App\Repositories\SearchRepository;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class SearchController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(Request $request, SearchRepository $repository)
		{
			return response()->json($repository->search($request->all()));
		}

		/**
		 * @url /api/search/game
		 * @param Request $request
		 * @param SearchRepository $repository
		 * @return JsonResponse
		 */
		public function game(Request $request, SearchRepository $repository): JsonResponse
		{
			return response()->json($repository->games($request->all()));
		}
	}
