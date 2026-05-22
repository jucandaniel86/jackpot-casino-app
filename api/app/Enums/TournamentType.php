<?php

namespace App\Enums;

enum TournamentType: string
{
	case DEFAULT = 'DEFAULT';
	case RANDOM = 'RANDOM';

	public static function values(): array
	{
		return array_column(self::cases(), 'value');
	}

	public function label(): string
	{
		return match ($this) {
			self::DEFAULT => 'Default',
			self::RANDOM => 'Random extraction',
		};
	}

	public static function options(): array
	{
		return array_map(fn (self $type) => [
			'title' => $type->label(),
			'value' => $type->value,
		], self::cases());
	}
}
