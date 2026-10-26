<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Drivers/VolvoSiteDriver.php
 * Concrete driver voor de Volvo-site.
 */

namespace App\Drivers;

use App\Enums\Site;

class VolvoSiteDriver extends AbstractSiteDriver
{
    public function siteEnum(): Site
    {
        return Site::VOLVO;
    }

    public function className(): string
    {
        return 'volvo';
    }

    public function brandName(): string
    {
        return 'Volvo';
    }

    public function buttonIconName(): string
    {
        return 'angle-right';
    }

    public function logoUrl(): string
    {
        return get_theme_file_uri('dist/img/logo.svg');
    }

    public function logoSingleUrl(): string
    {
        return get_theme_file_uri('dist/img/logo-volvo.svg');
    }
}
