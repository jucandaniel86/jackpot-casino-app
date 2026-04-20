<?php

	namespace App\Interfaces;

	interface ProvidersInterface
	{
		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);
	}