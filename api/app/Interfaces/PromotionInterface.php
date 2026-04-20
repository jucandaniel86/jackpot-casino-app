<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface PromotionInterface
	{
		public function savePromotionDraft(array $params = []);

		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);

		public function getItem($ID): array;
	}