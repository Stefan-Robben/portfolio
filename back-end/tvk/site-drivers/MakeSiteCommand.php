<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Commands/MakeSiteCommand.php
 * WP-CLI-command (wp tvk make-site) dat een nieuwe merk-site scaffoldt: driver, enum-case, registratie, config en CSS.
 */

namespace App\Commands;

use App\Drivers\SiteDriverManager;
use App\Enums\Site;
use WP_CLI;

/**
 * Scaffold a new site for the TVK multisite.
 *
 * ## EXAMPLES
 *
 *     # Scaffold a new site interactively
 *     wp tvk make-site
 *
 *     # Scaffold with all options
 *     wp tvk make-site --name=BrandX --blog-id=6 --class-name=brandx
 *
 *     # Copy config from an existing site
 *     wp tvk make-site --name=BrandX --blog-id=6 --copy-from=2
 */
class MakeSiteCommand
{
    private string $themePath;

    public function __construct()
    {
        $this->themePath = get_stylesheet_directory();
    }

    /**
     * Scaffold all files needed to add a new site to the multisite.
     *
     * Creates the SiteDriver class, registers it, adds the Site enum case,
     * and scaffolds the voertuig config directory.
     *
     * ## OPTIONS
     *
     * [--name=<name>]
     * : The brand/display name for the site (e.g. "BrandX").
     *
     * [--blog-id=<id>]
     * : The WordPress blog ID for the new site.
     *
     * [--class-name=<class-name>]
     * : The CSS class name / slug (e.g. "brandx"). Defaults to lowercased name.
     *
     * [--copy-from=<blog-id>]
     * : Copy voertuig config from an existing site's blog ID.
     *
     * [--dry-run]
     * : Show what would be created without writing files.
     *
     * ## EXAMPLES
     *
     *     wp tvk make-site --name=BrandX --blog-id=6
     *     wp tvk make-site --name=BrandX --blog-id=6 --copy-from=2
     *     wp tvk make-site --name=BrandX --blog-id=6 --dry-run
     *
     * @when after_wp_load
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $dryRun = (bool) \WP_CLI\Utils\get_flag_value($assocArgs, 'dry-run', false);

        $name = $assocArgs['name'] ?? $this->prompt('Brand name (e.g. "BrandX")');
        $blogId = (int) ($assocArgs['blog-id'] ?? $this->prompt('Blog ID'));
        $className = $assocArgs['class-name'] ?? strtolower(
            preg_replace('/[^a-zA-Z0-9]/', '', $name)
        );
        $copyFrom = isset($assocArgs['copy-from']) ? (int) $assocArgs['copy-from'] : null;

        $this->validate($name, $blogId, $className, $copyFrom);

        $enumCase = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $name));
        $driverClassName = ucfirst($className).'SiteDriver';

        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%BSite Scaffold Summary%n'));
        WP_CLI::log(str_repeat('─', 40));
        WP_CLI::log("  Name:           {$name}");
        WP_CLI::log("  Blog ID:        {$blogId}");
        WP_CLI::log("  CSS class:      {$className}");
        WP_CLI::log("  Enum case:      Site::{$enumCase}");
        WP_CLI::log("  Driver class:   {$driverClassName}");
        if ($copyFrom) {
            WP_CLI::log("  Copy from:      site-{$copyFrom}");
        }
        WP_CLI::log('');

        if ($dryRun) {
            $this->previewFiles($blogId, $className, $driverClassName, $enumCase, $name, $copyFrom);
            WP_CLI::success('Dry run complete. No files were written.');

            return;
        }

        $this->createDriverClass($driverClassName, $enumCase, $className, $name, $blogId);
        $this->addEnumCase($enumCase, $blogId);
        $this->registerDriver($enumCase, $driverClassName);
        $this->scaffoldVoertuigConfig($blogId, $copyFrom);
        $this->scaffoldStylesheet($className, $copyFrom ? $this->getClassNameForBlogId($copyFrom) : null);

        WP_CLI::log('');
        WP_CLI::success("Site \"{$name}\" scaffolded successfully!");
        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%YRemaining manual steps:%n'));
        WP_CLI::log("  1. Add logo SVGs to resources/img/ (logo-{$className}.svg, logo-{$className}-single.svg)");
        WP_CLI::log("  2. Update the driver's logoUrl() and logoSingleUrl() if paths differ");
        WP_CLI::log("  3. Create the WordPress subsite in wp-admin > Network > Sites with blog ID {$blogId}");
        WP_CLI::log("  4. Customize CSS variables in resources/css/config/site-{$className}.css");
        WP_CLI::log("  5. Run: composer dump-autoload (in theme directory)");
        WP_CLI::log("  6. Run: npm run dev (to compile new stylesheets)");
        WP_CLI::log("  7. Configure voertuig config in config/autobedrijfvoertuigen/site-{$blogId}/");
        WP_CLI::log('');
    }

    private function validate(string $name, int $blogId, string $className, ?int $copyFrom): void
    {
        if (empty($name)) {
            WP_CLI::error('Brand name is required.');
        }

        if ($blogId < 1) {
            WP_CLI::error('Blog ID must be a positive integer.');
        }

        if (Site::tryFrom($blogId) !== null) {
            WP_CLI::error("Blog ID {$blogId} is already registered as Site::{$this->getExistingEnumCase($blogId)}.");
        }

        if (empty($className)) {
            WP_CLI::error('Class name cannot be empty.');
        }

        $driverPath = $this->themePath.'/includes/Drivers/'.ucfirst($className).'SiteDriver.php';
        if (file_exists($driverPath)) {
            WP_CLI::error("Driver file already exists: {$driverPath}");
        }

        if ($copyFrom !== null) {
            $sourceDir = $this->themePath.'/config/autobedrijfvoertuigen/site-'.$copyFrom;
            if (! is_dir($sourceDir)) {
                WP_CLI::error("Source site config directory not found: {$sourceDir}");
            }
        }
    }

    private function createDriverClass(string $driverClassName, string $enumCase, string $className, string $name, int $blogId): void
    {
        $content = <<<PHP
<?php

namespace App\Drivers;

use App\Enums\Site;

class {$driverClassName} extends AbstractSiteDriver
{
    public function siteEnum(): Site
    {
        return Site::{$enumCase};
    }

    public function className(): string
    {
        return '{$className}';
    }

    public function brandName(): string
    {
        return '{$name}';
    }

    public function logoUrl(): string
    {
        return get_theme_file_uri('dist/img/logo-{$className}.svg');
    }

    public function logoSingleUrl(): string
    {
        return get_theme_file_uri('dist/img/logo-{$className}-single.svg');
    }
}

PHP;

        $path = $this->themePath.'/includes/Drivers/'.$driverClassName.'.php';
        $this->writeFile($path, $content);
        WP_CLI::log(WP_CLI::colorize("%g✓%n Created driver:    includes/Drivers/{$driverClassName}.php"));
    }

    private function addEnumCase(string $enumCase, int $blogId): void
    {
        $path = $this->themePath.'/includes/Enums/Site.php';
        $content = file_get_contents($path);

        // Find the last enum case line and add the new one after it
        $pattern = '/(case\s+\w+\s*=\s*\d+\s*;)(?!.*case\s)/s';
        if (preg_match($pattern, $content, $matches, PREG_OFFSET_MATCH)) {
            $lastCaseEnd = $matches[1][1] + strlen($matches[1][0]);
            $newCase = "\n    case {$enumCase} = {$blogId};";
            $content = substr_replace($content, $newCase, $lastCaseEnd, 0);
            file_put_contents($path, $content);
            WP_CLI::log(WP_CLI::colorize("%g✓%n Added enum case:   Site::{$enumCase} = {$blogId}"));
        } else {
            WP_CLI::warning("Could not auto-add enum case. Add manually: case {$enumCase} = {$blogId};");
        }
    }

    private function registerDriver(string $enumCase, string $driverClassName): void
    {
        $path = $this->themePath.'/includes/Drivers/bootstrap.php';
        $content = file_get_contents($path);

        $registration = "\nSiteDriverManager::register(Site::{$enumCase}->value, {$driverClassName}::class);";

        // Append before the final newline
        $content = rtrim($content)."\n".$registration."\n";
        file_put_contents($path, $content);

        WP_CLI::log(WP_CLI::colorize('%g✓%n Registered driver: bootstrap.php'));
    }

    private function scaffoldVoertuigConfig(int $blogId, ?int $copyFrom): void
    {
        $targetDir = $this->themePath.'/config/autobedrijfvoertuigen/site-'.$blogId;

        if ($copyFrom !== null) {
            $sourceDir = $this->themePath.'/config/autobedrijfvoertuigen/site-'.$copyFrom;
            $this->copyDirectory($sourceDir, $targetDir);
            WP_CLI::log(WP_CLI::colorize("%g✓%n Copied config:     site-{$copyFrom} → site-{$blogId}"));
        } else {
            $this->scaffoldEmptyConfig($targetDir, $blogId);
            WP_CLI::log(WP_CLI::colorize("%g✓%n Scaffolded config: config/autobedrijfvoertuigen/site-{$blogId}/"));
        }
    }

    private function scaffoldEmptyConfig(string $targetDir, int $blogId): void
    {
        @mkdir($targetDir.'/voertuig/cache', 0755, true);

        // … (ingekort) schrijft config.php, functions.php en voertuig/config.php
        // met standaardwaarden (archief-URL's, sorteringen) via heredocs.
    }

    private function scaffoldStylesheet(string $className, ?string $copyFrom): void
    {
        $cssDir = $this->themePath.'/resources/css';
        $configDir = $cssDir.'/config';

        // Create config CSS (the brand variables file)
        $configFile = $configDir."/site-{$className}.css";
        if ($copyFrom !== null) {
            $sourceConfig = $configDir."/site-{$copyFrom}.css";
            if (file_exists($sourceConfig)) {
                copy($sourceConfig, $configFile);
                WP_CLI::log(WP_CLI::colorize("%g✓%n Copied CSS config: config/site-{$copyFrom}.css → config/site-{$className}.css"));
            }
        } elseif (! file_exists($configFile)) {
            $this->writeFile($configFile, <<<CSS
:root {
    /* Add {$className} site CSS custom properties here */
}

CSS
            );
            WP_CLI::log(WP_CLI::colorize("%g✓%n Created CSS config: resources/css/config/site-{$className}.css"));
        }

        // Create the 3 entry point CSS files
        $entries = [
            "site-{$className}.css" => "@import './app.css';\n@import './config/site-{$className}.css';",
            "editor-{$className}.css" => "@import './editor.css';\n@import './config/site-{$className}.css';",
            "admin-{$className}.css" => "@import './admin.css';\n@import './config/site-{$className}.css';",
        ];

        foreach ($entries as $file => $content) {
            $path = $cssDir.'/'.$file;
            if (! file_exists($path)) {
                $this->writeFile($path, $content."\n");
                WP_CLI::log(WP_CLI::colorize("%g✓%n Created CSS:       resources/css/{$file}"));
            }
        }

        // Update webreact.config.js
        $this->addWebpackEntries($className);
    }

    private function addWebpackEntries(string $className): void
    {
        // … (ingekort) voegt site-/editor-/admin-entries voor de nieuwe site toe
        // aan webreact.config.js, direct na de bestaande entries, en slaat
        // entries over die al bestaan.
    }

    private function previewFiles(int $blogId, string $className, string $driverClassName, string $enumCase, string $name, ?int $copyFrom): void
    {
        WP_CLI::log(WP_CLI::colorize('%BFiles that would be created:%n'));
        WP_CLI::log("  includes/Drivers/{$driverClassName}.php");
        WP_CLI::log("  resources/css/config/site-{$className}.css");
        WP_CLI::log("  resources/css/site-{$className}.css");
        WP_CLI::log("  resources/css/editor-{$className}.css");
        WP_CLI::log("  resources/css/admin-{$className}.css");

        if ($copyFrom) {
            WP_CLI::log("  config/autobedrijfvoertuigen/site-{$blogId}/ (copied from site-{$copyFrom})");
        } else {
            WP_CLI::log("  config/autobedrijfvoertuigen/site-{$blogId}/config.php");
            WP_CLI::log("  config/autobedrijfvoertuigen/site-{$blogId}/functions.php");
            WP_CLI::log("  config/autobedrijfvoertuigen/site-{$blogId}/voertuig/config.php");
        }

        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%BFiles that would be modified:%n'));
        WP_CLI::log("  includes/Enums/Site.php          → add case {$enumCase} = {$blogId}");
        WP_CLI::log("  includes/Drivers/bootstrap.php   → register {$driverClassName}");
        WP_CLI::log("  webreact.config.js               → add CSS build entries");
    }

    private function copyDirectory(string $source, string $target): void
    {
        @mkdir($target, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($source));
            $targetPath = $target.$relativePath;

            if ($item->isDir()) {
                @mkdir($targetPath, 0755, true);
            } else {
                // Skip cache files
                if (str_contains($item->getPathname(), '/cache/')) {
                    @mkdir(dirname($targetPath), 0755, true);

                    continue;
                }
                @mkdir(dirname($targetPath), 0755, true);
                copy($item->getPathname(), $targetPath);
            }
        }

        // Ensure cache directory exists
        @mkdir($target.'/voertuig/cache', 0755, true);
    }

    private function writeFile(string $path, string $content): void
    {
        $dir = dirname($path);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        file_put_contents($path, $content);
    }

    private function prompt(string $question): string
    {
        fwrite(STDOUT, $question.': ');
        $answer = trim(fgets(STDIN));

        if (empty($answer)) {
            WP_CLI::error("{$question} is required.");
        }

        return $answer;
    }

    private function getExistingEnumCase(int $blogId): string
    {
        $site = Site::tryFrom($blogId);

        return $site ? $site->name : 'UNKNOWN';
    }

    private function getClassNameForBlogId(int $blogId): ?string
    {
        $site = Site::tryFrom($blogId);

        return $site?->className();
    }
}
