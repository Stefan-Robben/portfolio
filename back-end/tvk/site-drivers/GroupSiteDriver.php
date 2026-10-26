<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Drivers/GroupSiteDriver.php
 * Concrete driver voor de groepssite (wijkt op meer punten af van de defaults).
 */

namespace App\Drivers;

use App\Enums\Site;

class GroupSiteDriver extends AbstractSiteDriver
{
    public function siteEnum(): Site
    {
        return Site::GROUP;
    }

    public function className(): string
    {
        return 'group';
    }

    public function brandName(): string
    {
        return 'Groep';
    }

    public function buttonIconName(): string
    {
        return 'angle-right';
    }

    public function logoUrl(): string
    {
        return get_theme_file_uri('dist/img/logo-ton-van-kuyk.svg');
    }

    public function vehicleMake(): string
    {
        return 'Volvo';
    }

    public function vehicleModelPrefix(): string
    {
        return 'Volvo ';
    }

    public function formMapperKey(): string
    {
        return 'group';
    }

    public function showInSiteSwitcher(): bool
    {
        return false;
    }
}
