<?php

	namespace App\Interfaces;

	interface PageInterface
	{
		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);

		public function getPage($id);
	}