<?php

/*
 * Project:  TVK Groep (tvk-group, plugin tvk-core)
 * Origineel: web/app/plugins/tvk-core/app/Services/Rdw/RdwApi.php
 * Dunne Guzzle-client voor de publieke RDW-datasets.
 */

namespace TvkCore\Services\Rdw;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class RdwApi
{
    protected Client $client;

    protected array $endpoints = [
        'info'                 => 'm9d7-ebf2.json',
        'brandstof'            => '8ys7-d773.json',
        'carrosserie'          => 'vezc-m2t6.json',
        'carrosserieSpecifiek' => 'jhie-znh9.json',
        'voertuigklasse'       => 'kmfi-hrps.json',
    ];

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://opendata.rdw.nl/resource/',
            'headers'  => [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ],
            'timeout' => 10,
        ]);
    }

    public function getEndpoint(string $data): string
    {
        if (empty($this->endpoints[$data])) {
            throw new \InvalidArgumentException("Data endpoint {$data} does not exist.");
        }

        return $this->endpoints[$data];
    }

    public function get(string $license, string $data = 'info'): mixed
    {
        try {
            $response = $this->client->get("{$this->getEndpoint($data)}?kenteken={$license}");

            $body = json_decode($response->getBody()->getContents());

            return $body[0] ?? null;
        } catch (GuzzleException $e) {
            return null;
        }
    }
}
