<?php

	namespace App\Interfaces;

	interface CategoriesInterface
	{
		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);
	}