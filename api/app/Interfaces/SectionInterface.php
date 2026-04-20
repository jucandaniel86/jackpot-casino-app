<?php

	namespace App\Interfaces;

	use Illuminate\Http\Request;

	interface SectionInterface
	{
		public function addNewDraft(Request $request);

		public function remove($id);

		public function saveSectionData(Request $request);

		public function changeSectionOrder($payload): array;
	}