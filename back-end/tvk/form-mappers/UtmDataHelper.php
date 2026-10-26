<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Support/UtmDataHelper.php
 * Leest UTM-parameters uit cookies en zet ze om naar rijen voor de lead.
 */

namespace TvkCore\Support;

class UtmDataHelper
{
	/**
	 * Read UTM parameters from cookies.
	 *
	 * @return array<string, string>
	 */
	public static function fromCookies(): array
	{
		$utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
		$utmData = [];

		foreach ($utmKeys as $key) {
			$utmData[$key] = !empty($_COOKIE[$key])
				? sanitize_text_field(wp_unslash($_COOKIE[$key]))
				: 'Geen data';
		}

		return $utmData;
	}

	/**
	 * Format UTM data as ExtraInfoDto-compatible rows.
	 *
	 * @return array<array{key: string, value: string|null}>
	 */
	public static function forExtraInfo(): array
	{
		$utmData = self::fromCookies();

		return array_map(
			fn($key, $value) => ['key' => $key, 'value' => $value],
			array_keys($utmData),
			$utmData
		);
	}
}
