<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Enums/VolvoFormType.php
 * Enum per formuliertype; koppelt elk formulier aan de juiste LEF- en/of Ternair-mapper.
 */

namespace TvkCore\Enums;

use TvkCore\Contracts\LefFormMapperInterface;
use TvkCore\Contracts\TernairFormMapperInterface;
use TvkCore\Mappers\Lef\FormMappers\FinancialLeaseFormMapper;
use TvkCore\Mappers\Lef\FormMappers\FinancingCalculationFormMapper;
use TvkCore\Mappers\Lef\FormMappers\HomeTestDriveRequestFormMapper;
use TvkCore\Mappers\Lef\FormMappers\LeasePriceRequestFormMapper;
use TvkCore\Mappers\Lef\FormMappers\LeaseQuoteRequestFormMapper;
use TvkCore\Mappers\Lef\FormMappers\LynkCoWelcomeTestDriveFormMapper;
use TvkCore\Mappers\Lef\FormMappers\ModelFinancingCalculationFormMapper;
use TvkCore\Mappers\Lef\FormMappers\ModelQuestionFormMapper;
use TvkCore\Mappers\Lef\FormMappers\MaintenanceHistoryRequestFormMapper;
use TvkCore\Mappers\Lef\FormMappers\OperationalLeaseFormMapper;
use TvkCore\Mappers\Lef\FormMappers\PrivateLeaseFormMapper;
use TvkCore\Mappers\Lef\FormMappers\RequestQuoteFormMapper;
use TvkCore\Mappers\Lef\FormMappers\RequestQuoteModelFormMapper;
use TvkCore\Mappers\Lef\FormMappers\RequestTestDriveFormMapper;
use TvkCore\Mappers\Lef\FormMappers\SintCoFormMapper;
use TvkCore\Mappers\Lef\FormMappers\TestDriveFormMapper;
use TvkCore\Mappers\Lef\FormMappers\TradeInFormMapper;
use TvkCore\Mappers\Lef\FormMappers\VehicleQuestionFormMapper;
use TvkCore\Mappers\Ternair\FormMappers\NewsletterFormMapper;
use TvkCore\Mappers\Ternair\FormMappers\RequestQuoteFormMapper as TernairRequestQuoteFormMapper;
use TvkCore\Mappers\Ternair\FormMappers\TestDriveFormMapper as TernairTestDriveFormMapper;
use TvkCore\Mappers\Ternair\FormMappers\WelcomeTestDriveFormMapper;

enum VolvoFormType: int
{
	case Newsletter = 1;
	case TradeIn = 2;
	case LeasePriceRequest = 4;
	case PrivateLease = 6;
	case OperationalLease = 7;
	case FinancialLease = 8;
	case HomeTestDriveRequest = 10;
	case OnderhoudshistorieAanvraag = 11;
	case RequestTestDrive = 12;
	case VehicleQuestion = 13;
	case FinancingCalculation = 14;
	case TestDrive = 15;
	case LeaseQuoteRequest = 16;
	case LynkCoWelcomeTestDrive = 24;
	case RequestQuote = 27;
	case RequestQuoteModel = 28;
	case ModelFinancingCalculation = 29;
	case ModelQuestion = 30;
	case SintCo = 32;

	public function lefMapper(): ?LefFormMapperInterface
	{
		return match ($this) {
			self::TradeIn => new TradeInFormMapper(),
			self::LeasePriceRequest => new LeasePriceRequestFormMapper(),
			self::PrivateLease => new PrivateLeaseFormMapper(),
			self::OperationalLease => new OperationalLeaseFormMapper(),
			self::FinancialLease => new FinancialLeaseFormMapper(),
			self::HomeTestDriveRequest => new HomeTestDriveRequestFormMapper(),
			self::OnderhoudshistorieAanvraag => new MaintenanceHistoryRequestFormMapper(),
			self::RequestTestDrive => new RequestTestDriveFormMapper(),
			self::VehicleQuestion => new VehicleQuestionFormMapper(),
			self::FinancingCalculation => new FinancingCalculationFormMapper(),
			self::TestDrive => new TestDriveFormMapper(),
			self::LeaseQuoteRequest => new LeaseQuoteRequestFormMapper(),
			self::LynkCoWelcomeTestDrive => new LynkCoWelcomeTestDriveFormMapper(),
			self::RequestQuote => new RequestQuoteFormMapper(),
			self::RequestQuoteModel => new RequestQuoteModelFormMapper(),
			self::ModelFinancingCalculation => new ModelFinancingCalculationFormMapper(),
			self::ModelQuestion => new ModelQuestionFormMapper(),
			self::SintCo => new SintCoFormMapper(),
			default => null,
		};
	}

	public function ternairMapper(): ?TernairFormMapperInterface
	{
		return match ($this) {
			self::Newsletter => new NewsletterFormMapper(),
			self::RequestTestDrive => new WelcomeTestDriveFormMapper(),
			self::TestDrive => new TernairTestDriveFormMapper(),
			self::RequestQuote => new TernairRequestQuoteFormMapper(),
			default => null,
		};
	}
}
