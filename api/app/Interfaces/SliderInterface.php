<?php

	namespace App\Interfaces;

	interface SliderInterface
	{
		public function list(array $params = []);

		public function save(array $params = []);

		public function remove($id);
	}