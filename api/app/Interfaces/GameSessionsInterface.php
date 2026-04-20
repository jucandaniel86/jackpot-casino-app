<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface GameSessionsInterface
	{
		public function start(Request $request): array;

		public function startDemo(Request $request);
	}