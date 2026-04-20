<?php

	namespace App\Enums;
	enum LinkActionTypes: string
	{
		case OPEN_EXTERNAL_PAGE = 'OPEN_EXTERNAL_PAGE';
		case OPEN_INTERNAL_PAGE = 'OPEN_INTERNAL_PAGE';
		case OPEN_OVERLAY = 'OPEN_OVERLAY';
	}