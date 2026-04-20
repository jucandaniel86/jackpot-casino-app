<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;
	use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

	interface  MenuInterface
	{
		public function items(Request $request): array;

		public function save(Request $request): array;

		public function remove(int $id): array;

		public function menu($menuType): AnonymousResourceCollection;
	}