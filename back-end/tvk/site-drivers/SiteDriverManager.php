<?php

/*
 * Project:  TVK Groep (tvk-group, thema tvk-theme)
 * Origineel: web/app/themes/tvk-theme/includes/Drivers/SiteDriverManager.php
 * Registry die per blog-ID de juiste driver oplost en cachet.
 */

namespace App\Drivers;

use App\Contracts\SiteDriverInterface;

class SiteDriverManager
{
    /** @var array<int, class-string<SiteDriverInterface>> */
    private static array $drivers = [];

    /** @var array<int, SiteDriverInterface> */
    private static array $resolved = [];

    /**
     * Register a driver class for a given blog ID.
     */
    public static function register(int $blogId, string $driverClass): void
    {
        static::$drivers[$blogId] = $driverClass;

        // Clear cached instance when re-registering
        unset(static::$resolved[$blogId]);
    }

    /**
     * Resolve the driver for a given blog ID.
     * Falls back to the current blog ID if none specified.
     */
    public static function resolve(?int $blogId = null): SiteDriverInterface
    {
        $blogId = $blogId ?? get_current_blog_id();

        if (isset(static::$resolved[$blogId])) {
            return static::$resolved[$blogId];
        }

        if (! isset(static::$drivers[$blogId])) {
            throw new \RuntimeException("No site driver registered for blog ID {$blogId}.");
        }

        $driverClass = static::$drivers[$blogId];

        return static::$resolved[$blogId] = new $driverClass();
    }

    /**
     * Get the driver for the current blog.
     */
    public static function current(): SiteDriverInterface
    {
        return static::resolve();
    }

    /**
     * Check if a driver is registered for the given blog ID.
     */
    public static function has(int $blogId): bool
    {
        return isset(static::$drivers[$blogId]);
    }

    /**
     * Get all registered drivers (resolved instances).
     *
     * @return SiteDriverInterface[]
     */
    public static function all(): array
    {
        $instances = [];

        foreach (static::$drivers as $blogId => $driverClass) {
            $instances[$blogId] = static::resolve($blogId);
        }

        return $instances;
    }

    /**
     * Get all drivers that should appear in the site switcher.
     *
     * @return SiteDriverInterface[]
     */
    public static function switchable(): array
    {
        return array_filter(static::all(), fn (SiteDriverInterface $driver) => $driver->showInSiteSwitcher());
    }

    /**
     * Reset all registrations (useful for testing).
     */
    public static function flush(): void
    {
        static::$drivers = [];
        static::$resolved = [];
    }
}
