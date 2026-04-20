<?php

	namespace App\Interfaces;

	interface WalletInterface
	{
		public function list(array $params = []): array;

		public function save(array $params = []);

		public function remove($id);

		public function createUserWallets(): array;

		public function currencies(): array;
	}