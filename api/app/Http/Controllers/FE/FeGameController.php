<?php

	namespace App\Http\Controllers\FE;

	use App\Http\Controllers\Controller;
	use App\Interfaces\PageGeneratorInterface;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;

	class FeGameController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(string $slug, PageGeneratorInterface $pageGenerator): JsonResponse
		{
			return response()->json($pageGenerator->getGame($slug));
		}
	}