<?php

	namespace App\Http\Controllers\Fe;

	use App\Http\Controllers\Controller;
	use App\Repositories\PageGeneratorRepository;
	use Illuminate\Http\Request;

	class TabController extends Controller
	{
		/**
		 * Handle the incoming request.
		 */
		public function __invoke(string $slug, PageGeneratorRepository $repository)
		{
			return $repository->getTab($slug);
		}
	}