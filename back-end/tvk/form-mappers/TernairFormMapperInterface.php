<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Contracts/TernairFormMapperInterface.php
 * Contract voor de Ternair-mappers (nieuwsbrief, proefrit, offerte).
 */

namespace TvkCore\Contracts;

use TvkCore\DataTransferObjects\Ternair\NewsletterRequestDto;
use TvkCore\DataTransferObjects\Ternair\QuoteRequestDto;
use TvkCore\DataTransferObjects\Ternair\TestDriveRequestDto;

interface TernairFormMapperInterface
{
	public function map(array $entry, array $form): TestDriveRequestDto|QuoteRequestDto|NewsletterRequestDto;
}
