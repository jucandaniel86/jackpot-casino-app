<?php

	namespace App\Enums;
	enum Zones: string
	{
		case BOTTOM_BAR = 'bottomBar';
		case FOOTER = 'footer';
		case HEADER = 'header';
		case SIDEBAR = 'sidebar';
		case MAIN = 'main';
	}