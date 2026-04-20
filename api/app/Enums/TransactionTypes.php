<?php

	namespace App\Enums;
	enum TransactionTypes: string
	{
		case BET = 'bet';
		case WIN = 'win';
		case REFUND = 'refund';
	}