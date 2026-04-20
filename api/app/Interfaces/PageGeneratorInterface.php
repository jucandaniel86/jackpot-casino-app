<?php

	namespace App\Interfaces;

	interface PageGeneratorInterface
	{
		public function getPage(string $slug): array;

		public function getCategory(string $slug, string $casinoID): array;

		public function getPromotion(string $slug, string $casinoID): array;

		public function getGame(string $slug): array;

		public function getGamesProviders(string $slug): array;
	}
