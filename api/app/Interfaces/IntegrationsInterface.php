<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface IntegrationsInterface
	{
		public function balance(Request $request): array;

		public function bet(Request $request): array;

		public function refund(Request $request): array;
	}