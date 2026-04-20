<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface BetInterface
	{
		public function search(Request $request): array;
	}