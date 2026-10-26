<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Contracts/LefFormMapperInterface.php
 * Contract voor alle LEF-formuliermappers: Gravity Forms-entry -> LeadDto.
 */

namespace TvkCore\Contracts;

use TvkCore\DataTransferObjects\Lef\LeadDto;

interface LefFormMapperInterface
{
	public function map(array $entry, array $form): LeadDto;
}
