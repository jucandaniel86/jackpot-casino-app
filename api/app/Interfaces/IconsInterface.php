<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface IconsInterface
	{
		public function list(): array;

		public function save(Request $request);

		public function remove($id);

		public function import();
	}