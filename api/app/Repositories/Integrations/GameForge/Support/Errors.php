<?php

	namespace App\Repositories\Integrations\GameForge\Support;

	class Errors
	{
		//INTERNAL ERRORS
		const INTERNAL_ERRORS = [
			6001 => "INVALID SIGNATURE",
			6002 => "INVALID REQUEST PARAMS",
			6003 => "SESSION NOT FOUND",
			6004 => "SESSION EXPIRED",
			6005 => "SESSION NOT MATCH USER",
			6006 => "INVALID USER",
			6007 => "INVALID WALLET",
			6008 => 'INVALID PARAMS',
			6009 => 'INVALID WALLET CURRENCY',
			6010 => 'INSUFFICIENT FOUNDS',
			6011 => 'INVALID TRANSACTION TYPE',
			6012 => 'DATABASE ERROR',
			6013 => 'TRANSACTION NOT FOUND'
		];

		static function error(int $internalErrorCode)
		{
			return (isset(self::INTERNAL_ERRORS[$internalErrorCode])) ?
				self::INTERNAL_ERRORS[$internalErrorCode] :
				"INTERNAL ERROR";
		}
	}