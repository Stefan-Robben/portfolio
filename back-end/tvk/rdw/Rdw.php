<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Services/Rdw/Rdw.php
 * Kentekenopzoeking bij RDW Open Data; vervangt een niet-onderhouden package.
 */

namespace TvkCore\Services\Rdw;

/**
 * RDW Open Data license plate lookup.
 * Replaces jeffreyvanrossum/rdw-opendata-php (Guzzle 6, unmaintained).
 */
class Rdw
{
    protected RdwApi $api;

    public function __construct(?RdwApi $api = null)
    {
        $this->api = $api ?? new RdwApi();
    }

    public function get(string $license, string $data = 'info'): mixed
    {
        return $this->api->get(
            strtoupper(str_replace('-', '', $license)),
            $data
        );
    }
}
