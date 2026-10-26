<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Drivers/AbstractSiteDriver.php
 * Basisklasse met verstandige defaults; een nieuwe site overschrijft alleen wat afwijkt.
 */

namespace App\Drivers;

use App\Contracts\SiteDriverInterface;
use App\Enums\Site;

abstract class AbstractSiteDriver implements SiteDriverInterface
{
    public function blogId(): int
    {
        return $this->siteEnum()->value;
    }

    public function label(): string
    {
        return $this->brandName();
    }

    public function className(): string
    {
        return strtolower($this->siteEnum()->name);
    }

    public function stylesheet(): string
    {
        return 'site-'.$this->className().'.modern.css';
    }

    public function editorStylesheet(): string
    {
        return 'editor-'.$this->className().'.modern.css';
    }

    public function adminStylesheet(): string
    {
        return 'admin-'.$this->className().'.modern.css';
    }

    public function logoSingleUrl(): string
    {
        return '';
    }

    public function buttonIconName(): string
    {
        return 'arrow-right';
    }

    public function voertuigConfigPath(): string
    {
        return 'config/autobedrijfvoertuigen/site-'.$this->blogId().'/';
    }

    public function vehicleMake(): string
    {
        return $this->brandName();
    }

    public function vehicleModelPrefix(): string
    {
        return $this->brandName().' ';
    }

    public function formMapperKey(): string
    {
        return $this->className();
    }

    public function showInSiteSwitcher(): bool
    {
        return true;
    }
}
