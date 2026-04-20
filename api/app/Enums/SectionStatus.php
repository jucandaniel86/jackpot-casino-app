<?php

	namespace App\Enums;
	enum SectionStatus: string
	{
		case DRAFT = 'DRAFT';
		case PUBLISHED = 'PUBLISHED';
		case PRIVATE = 'PRIVATE';
	}