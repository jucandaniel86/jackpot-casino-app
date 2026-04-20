<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface GameInterface
	{
		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);

		public function getArrayList(): array;

		public function getItem($ID): array;

		public function lightSearchByName(Request $request);

		public function getGamesByCategories(Request $request, $with = []);

		public function getByIds(Request $request);

		public function getGamesBySection($sectionID): array;
	}