<?php

	namespace App\Interfaces;

	interface TagsInterface
	{
		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);

		public function getTag($id);
	}