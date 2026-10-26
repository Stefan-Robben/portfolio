<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Contracts/SiteDriverInterface.php
 * Contract voor alles wat per merk-site verschilt in de multisite (assets, merk, formulieren, flags).
 */

namespace App\Contracts;

use App\Enums\Site;

interface SiteDriverInterface
{
    // Identity
    public function siteEnum(): Site;

    public function blogId(): int;

    public function label(): string;

    public function className(): string;

    public function brandName(): string;

    // Assets
    public function stylesheet(): string;

    public function editorStylesheet(): string;

    public function adminStylesheet(): string;

    public function logoUrl(): string;

    public function logoSingleUrl(): string;

    public function buttonIconName(): string;

    // Vehicles / Config
    public function voertuigConfigPath(): string;

    public function vehicleMake(): string;

    public function vehicleModelPrefix(): string;

    // Forms
    public function formMapperKey(): string;

    // Feature flags
    public function showInSiteSwitcher(): bool;
}
