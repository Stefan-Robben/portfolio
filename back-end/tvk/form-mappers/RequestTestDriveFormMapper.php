<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Mappers/Lef/FormMappers/RequestTestDriveFormMapper.php
 * Voorbeeldmapper: proefrit aanvragen, met optioneel thuisadres bij een proefrit aan huis.
 */

namespace TvkCore\Mappers\Lef\FormMappers;

use TvkCore\Contracts\LefFormMapperInterface;
use TvkCore\DataTransferObjects\Lef\AddressDto;
use TvkCore\DataTransferObjects\Lef\CustomerDto;
use TvkCore\DataTransferObjects\Lef\ExtraInfoDto;
use TvkCore\DataTransferObjects\Lef\LeadDto;
use TvkCore\Support\AccountNumberResolver;
use TvkCore\Support\AddressHelper;
use TvkCore\Support\ExtraInfoBuilder;
use TvkCore\Support\PreferredVehicleResolver;

class RequestTestDriveFormMapper implements LefFormMapperInterface
{
	public function map(array $entry, array $form): LeadDto
	{
		$isHomeVisit = stripos($entry[13] ?? '', 'thuis') !== false;

		$address = $isHomeVisit
			? [
				AddressDto::fromArray([
					'addressType' => 'HomeAddress',
					...(
					function () use ($entry) {
						[$street, $houseNumber] = AddressHelper::splitStreetAndNumber($entry['15.1'] ?? '');

						return [
							'street' => $street,
							'houseNumber' => $houseNumber,
							'city' => $entry['15.3'] ?? null,
							'zipcode' => $entry['15.5'] ?? null,
							'country' => $entry['15.6'] ?? null,
						];
					}
					)(),
				])
			]
			: [];

		return new LeadDto(
			leadType: 'Sales',
			leadId: $form['tvk_lef_id_prepend'] . $entry['id'],
			leadKind: 'TestDrive',
			leadInitiative: 'Client',
			accountNumber: $isHomeVisit ? null : AccountNumberResolver::resolve($entry[4] ?? null),
			description: 'Proefrit aanvragen',
			trackbackNumber: null,
			leadSource: 'website tvk.nl',
			customerSiteTrackId: null,
			customer: CustomerDto::fromArray([
				'customerType' => 'Private',
				'firstName' => $entry['10.3'] ?? null,
				'lastName' => $entry['10.6'] ?? null,
				'email' => $entry[11] ?? null,
				'phoneNumberMobile' => $entry[12] ?? null,
				'addresses' => $address,
			]),
			currentVehicle: null,
			preferredVehicle: PreferredVehicleResolver::fromCurrentPost(),
			extraInfo: [
				ExtraInfoBuilder::formulierGegevens(),
				new ExtraInfoDto('Proefrit aanvraag', array_filter([
					['key' => 'Voorkeursdatum', 'value' => $entry[5] ?? ''],
					['key' => 'Voorkeurstijd', 'value' => $entry[7] ?? ''],
				])),
				ExtraInfoBuilder::utm(),
			]
		);
	}
}
