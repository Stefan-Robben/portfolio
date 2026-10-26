<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Support/ExtraInfoBuilder.php
 * Gedeelde helper voor de 'extra info'-blokken die elke mapper meestuurt.
 */

namespace TvkCore\Support;

use TvkCore\DataTransferObjects\Lef\ExtraInfoDto;

class ExtraInfoBuilder
{
	/**
	 * Build the "Formulier gegevens" ExtraInfoDto with the current page link.
	 */
	public static function formulierGegevens(): ExtraInfoDto
	{
		return new ExtraInfoDto('Formulier gegevens', array_values(array_filter([
			['key' => 'Link naar voertuig of pagina', 'value' => get_the_ID() ? '<a href="' . get_permalink(get_the_ID()) . '">Klik hier</a>' : null],
		])));
	}

	/**
	 * Build the "UTM" ExtraInfoDto.
	 */
	public static function utm(): ExtraInfoDto
	{
		return new ExtraInfoDto('UTM', UtmDataHelper::forExtraInfo());
	}

	/**
	 * Round value to two decimals if numeric, else return as-is.
	 */
	public static function roundIfNumeric(mixed $value): string
	{
		return is_numeric($value) ? number_format((float) $value, 2, '.', '') : (string) $value;
	}
}
