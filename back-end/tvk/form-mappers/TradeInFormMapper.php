<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Mappers/Lef/FormMappers/TradeInFormMapper.php
 * Voorbeeldmapper: inruilformulier (incl. uploads) -> LEF-lead van het type Taxation.
 */

namespace TvkCore\Mappers\Lef\FormMappers;

use TvkCore\Contracts\LefFormMapperInterface;
use TvkCore\DataTransferObjects\Lef\CustomerDto;
use TvkCore\DataTransferObjects\Lef\ExtraInfoDto;
use TvkCore\DataTransferObjects\Lef\LeadDto;
use TvkCore\DataTransferObjects\Lef\VehicleDto;
use TvkCore\Support\AccountNumberResolver;
use TvkCore\Support\ExtraInfoBuilder;
use TvkCore\Support\PreferredVehicleResolver;

class TradeInFormMapper implements LefFormMapperInterface
{
	public function map(array $entry, array $form): LeadDto
	{
		$files = json_decode($entry[13] ?? '[]', true) ?? [];
		$fileRows = array_map(
			fn($file, $index) => [
				'key' => 'Bestand ' . ($index + 1),
				'value' => '<a href="' . $file . '">klik hier voor bestand</a>',
			],
			$files,
			array_keys($files)
		);

		$extraInfoRows = array_merge([
			['key' => 'Kilometerstand', 'value' => (string) ($entry[5] ?? '')],
			['key' => 'Aantal sleutels', 'value' => $entry['12.1'] ?? ''],
			['key' => 'Onderhoudsboekje aanwezig', 'value' => $entry['12.2'] ?? ''],
			['key' => 'Omschrijving eventuele schade', 'value' => $entry['18'] ?? ''],
			['key' => 'Staat van het onderhoud', 'value' => $entry['8'] ?? ''],
			['key' => 'Technische staat', 'value' => $entry['9'] ?? ''],
			['key' => 'Staat interieur', 'value' => $entry['10'] ?? ''],
			['key' => 'Staat carrosserie', 'value' => $entry['11'] ?? ''],
		], $fileRows, [
			['key' => 'Voorwaarden akkoord', 'value' => $entry['16.1'] ?? ''],
		]);

		return new LeadDto(
			leadType: 'Sales',
			leadId: $form['tvk_lef_id_prepend'] . $entry['id'],
			leadKind: 'Taxation',
			leadInitiative: 'Client',
			accountNumber: AccountNumberResolver::resolve($entry[24] ?? null),
			description: 'Inruilaanvraag via website',
			trackbackNumber: null,
			leadSource: 'website tvk.nl',
			customerSiteTrackId: null,
			customer: CustomerDto::fromArray([
				'customerType' => 'Private',
				'firstName' => $entry['7.3'] ?? null,
				'lastName' => $entry['7.6'] ?? null,
				'email' => $entry[6] ?? null,
				'phoneNumberMobile' => $entry[26] ?? null,
				'optIn' => [
					'carCompany' => [
						'car' => [
							'phone' => ['value' => (bool) ($entry[9] ?? null), 'source' => 'tvk-form'],
							'email' => ['value' => (bool) ($entry[10] ?? null), 'source' => 'tvk-form'],
							'messenger' => ['value' => (bool) ($entry[11] ?? null), 'source' => 'tvk-form'],
						],
					],
				],
			]),
			currentVehicle: VehicleDto::fromArray([
				'licensePlate' => $entry[4] ?? null,
			]),
			preferredVehicle: PreferredVehicleResolver::fromCurrentPost(),
			extraInfo: [
				ExtraInfoBuilder::formulierGegevens(),
				new ExtraInfoDto('Opmerkingen', $extraInfoRows),
				ExtraInfoBuilder::utm(),
			]
		);
	}
}
