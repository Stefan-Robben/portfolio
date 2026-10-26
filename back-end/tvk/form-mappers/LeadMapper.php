<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Mappers/Lef/LeadMapper.php
 * Ingang voor het mappen van een formulier; was ~1000 regels, na de refactor alleen nog resolven en delegeren.
 */

namespace TvkCore\Mappers\Lef;

use App\Enums\Site;
use TvkCore\DataTransferObjects\Lef\LeadDto;
use TvkCore\DataTransferObjects\Ternair\NewsletterRequestDto;
use TvkCore\DataTransferObjects\Ternair\QuoteRequestDto;
use TvkCore\DataTransferObjects\Ternair\TestDriveRequestDto;
use TvkCore\Enums\VolvoFormType;
use TvkCore\Mappers\GravityForms\FormMapper;

class LeadMapper
{
	public static function fromGravityFormForLef(array $entry, array $form): LeadDto
	{
		$formType = self::resolveFormType($form);

		$mapper = $formType->lefMapper()
			?? throw new \InvalidArgumentException("No LEF mapper for form type {$formType->name}");

		return $mapper->map($entry, $form);
	}

	public static function fromGravityFormForTernair(array $entry, array $form): TestDriveRequestDto|QuoteRequestDto|NewsletterRequestDto
	{
		$formType = self::resolveFormType($form);

		$mapper = $formType->ternairMapper()
			?? throw new \InvalidArgumentException("No Ternair mapper for form type {$formType->name}");

		return $mapper->map($entry, $form);
	}

	private static function resolveFormType(array $form): VolvoFormType
	{
		$siteEnum = Site::tryFrom(get_current_blog_id());
		$sourceSiteName = $siteEnum?->toFormMapperKey() ?? 'volvo';
		$volvoFormId = FormMapper::map($sourceSiteName, 'volvo', $form['id']);

		if ($volvoFormId === null) {
			throw new \InvalidArgumentException("No mapper for form ID {$form['id']} on site " . ($sourceSiteName ?: 'unknown'));
		}

		$formType = VolvoFormType::tryFrom($volvoFormId);
		if ($formType === null) {
			throw new \InvalidArgumentException("No mapper for Volvo form ID {$volvoFormId} (source form ID {$form['id']} on site {$sourceSiteName}).");
		}

		return $formType;
	}
}
