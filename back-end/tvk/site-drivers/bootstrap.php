<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Drivers/bootstrap.php
 * Registratie van alle drivers.
 */

namespace App\Drivers;

use App\Enums\Site;

/**
 * Register all site drivers.
 *
 * To add a new site:
 * 1. Add a case to App\Enums\Site
 * 2. Create a new driver class extending AbstractSiteDriver
 * 3. Register it below
 */
SiteDriverManager::register(Site::GROUP->value, GroupSiteDriver::class);
SiteDriverManager::register(Site::VOLVO->value, VolvoSiteDriver::class);
SiteDriverManager::register(Site::LYNKCO->value, LynkCoSiteDriver::class);
SiteDriverManager::register(Site::POLESTAR->value, PolestarSiteDriver::class);
SiteDriverManager::register(Site::GEELY->value, GeelySiteDriver::class);
