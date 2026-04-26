<?php

declare(strict_types=1);

namespace App\Support\SearchOps;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Arr;
use RuntimeException;

class SearchConsoleService
{
    /**
     * @return array<string, mixed>
     */
    public function fetchSignals(int $days = 14, int $limit = 10): array
    {
        $siteUrl = (string) config('search_ops.site_url');
        $credentialsPath = (string) config('search_ops.credentials_path');
        $caBundlePath = (string) config('search_ops.ca_bundle_path');

        if ($siteUrl === '') {
            throw new RuntimeException('SEARCH_OPS_SITE_URL is missing.');
        }

        if ($credentialsPath === '' || ! is_file($credentialsPath)) {
            throw new RuntimeException('Search Console credentials file is missing.');
        }

        $credentials = json_decode((string) file_get_contents($credentialsPath), true);

        if (! is_array($credentials) || empty($credentials['client_email']) || empty($credentials['private_key'])) {
            throw new RuntimeException('Search Console credentials JSON is invalid.');
        }

        $clientOptions = [
            'timeout' => 20,
        ];

        if ($caBundlePath !== '' && is_file($caBundlePath)) {
            $clientOptions['verify'] = $caBundlePath;
        }

        $client = new Client($clientOptions);

        $accessToken = $this->fetchAccessToken($client, $credentials);
        $endDate = now()->subDay()->toDateString();
        $startDate = now()->subDays(max($days, 2))->toDateString();

        $verifiedSites = $this->fetchVerifiedSites($client, $accessToken);
        $queryProperty = $this->resolveQueryProperty($verifiedSites, $siteUrl, (string) $credentials['client_email']);

        $queryRows = $this->runQuery($client, $accessToken, $queryProperty, $startDate, $endDate, ['query'], $limit);
        $pageRows = $this->runQuery($client, $accessToken, $queryProperty, $startDate, $endDate, ['page'], $limit);
        $deviceRows = $this->runQuery($client, $accessToken, $queryProperty, $startDate, $endDate, ['device'], 5);
        $countryRows = $this->runQuery($client, $accessToken, $queryProperty, $startDate, $endDate, ['country'], 5);

        return [
            'status' => 'ok',
            'fetched_at' => now()->toDateTimeString(),
            'site_url' => $siteUrl,
            'query_property' => $queryProperty,
            'days' => $days,
            'verified_sites' => $verifiedSites,
            'summary' => [
                'query_clicks' => array_sum(array_map(fn (array $row): float => (float) ($row['clicks'] ?? 0), $queryRows)),
                'page_clicks' => array_sum(array_map(fn (array $row): float => (float) ($row['clicks'] ?? 0), $pageRows)),
                'query_impressions' => array_sum(array_map(fn (array $row): float => (float) ($row['impressions'] ?? 0), $queryRows)),
                'page_impressions' => array_sum(array_map(fn (array $row): float => (float) ($row['impressions'] ?? 0), $pageRows)),
            ],
            'top_queries' => $queryRows,
            'top_pages' => $pageRows,
            'top_devices' => $deviceRows,
            'top_countries' => $countryRows,
            'error' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function fetchAccessToken(Client $client, array $credentials): string
    {
        $now = time();
        $jwt = $this->buildJwt($credentials, $now);

        try {
            $response = $client->post((string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'), [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('OAuth token request failed: ' . $e->getMessage(), previous: $e);
        }

        $payload = json_decode((string) $response->getBody(), true);

        if (! is_array($payload) || empty($payload['access_token'])) {
            throw new RuntimeException('OAuth token response did not include access_token.');
        }

        return (string) $payload['access_token'];
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function buildJwt(array $credentials, int $now): string
    {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ], JSON_UNESCAPED_SLASHES));

        $claims = $this->base64UrlEncode(json_encode([
            'iss' => (string) $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
            'aud' => (string) ($credentials['token_uri'] ?? 'https://oauth2.googleapis.com/token'),
            'iat' => $now,
            'exp' => $now + 3600,
        ], JSON_UNESCAPED_SLASHES));

        $signingInput = $header . '.' . $claims;
        $signature = '';

        $privateKey = openssl_pkey_get_private((string) $credentials['private_key']);

        if ($privateKey === false) {
            throw new RuntimeException('Unable to load private key from Search Console credentials.');
        }

        $signed = openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);

        if ($signed !== true) {
            throw new RuntimeException('Unable to sign Search Console JWT.');
        }

        return $signingInput . '.' . $this->base64UrlEncode($signature);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchVerifiedSites(Client $client, string $accessToken): array
    {
        try {
            $response = $client->get('https://www.googleapis.com/webmasters/v3/sites', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Accept' => 'application/json',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new RuntimeException('Verified sites request failed: ' . $e->getMessage(), previous: $e);
        }

        $payload = json_decode((string) $response->getBody(), true);

        return collect((array) ($payload['siteEntry'] ?? []))
            ->map(fn (array $site): array => [
                'site_url' => (string) ($site['siteUrl'] ?? ''),
                'permission_level' => (string) ($site['permissionLevel'] ?? ''),
            ])
            ->filter(fn (array $site): bool => ($site['site_url'] ?? '') !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $verifiedSites
     */
    private function resolveQueryProperty(array $verifiedSites, string $siteUrl, string $clientEmail): string
    {
        $normalizedSiteUrl = rtrim($siteUrl, '/') . '/';
        $host = (string) parse_url($normalizedSiteUrl, PHP_URL_HOST);

        foreach ($verifiedSites as $site) {
            if ((string) ($site['site_url'] ?? '') === $normalizedSiteUrl) {
                return $normalizedSiteUrl;
            }
        }

        foreach ($verifiedSites as $site) {
            if ((string) ($site['site_url'] ?? '') === 'sc-domain:' . $host) {
                return 'sc-domain:' . $host;
            }
        }

        foreach ($verifiedSites as $site) {
            $candidate = (string) ($site['site_url'] ?? '');
            $candidateHost = str_starts_with($candidate, 'sc-domain:')
                ? substr($candidate, strlen('sc-domain:'))
                : (string) parse_url($candidate, PHP_URL_HOST);

            if ($candidateHost !== '' && $candidateHost === $host) {
                return $candidate;
            }
        }

        $visible = collect($verifiedSites)->pluck('site_url')->filter()->implode(', ');

        throw new RuntimeException(sprintf(
            'Service account %s is authenticated but has no Search Console property access for host %s. Visible properties: %s',
            $clientEmail,
            $host,
            $visible !== '' ? $visible : 'none'
        ));
    }

    /**
     * @param  array<int, string>  $dimensions
     * @return array<int, array<string, mixed>>
     */
    private function runQuery(
        Client $client,
        string $accessToken,
        string $siteUrl,
        string $startDate,
        string $endDate,
        array $dimensions,
        int $limit
    ): array {
        try {
            $response = $client->post(
                'https://www.googleapis.com/webmasters/v3/sites/' . rawurlencode($siteUrl) . '/searchAnalytics/query',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'dimensions' => $dimensions,
                        'rowLimit' => $limit,
                    ],
                ]
            );
        } catch (GuzzleException $e) {
            throw new RuntimeException('Search Console query failed: ' . $e->getMessage(), previous: $e);
        }

        $payload = json_decode((string) $response->getBody(), true);

        return collect((array) ($payload['rows'] ?? []))
            ->map(function (array $row) use ($dimensions): array {
                $key = (string) (Arr::first((array) ($row['keys'] ?? [])) ?? '');
                $result = [
                    'clicks' => (float) ($row['clicks'] ?? 0),
                    'impressions' => (float) ($row['impressions'] ?? 0),
                    'ctr' => round((float) ($row['ctr'] ?? 0), 4),
                    'position' => round((float) ($row['position'] ?? 0), 2),
                ];

                return match ($dimensions[0] ?? '') {
                    'query' => $result + ['query' => $key],
                    'page' => $result + [
                        'page' => $key,
                        'path' => (string) parse_url($key, PHP_URL_PATH),
                    ],
                    'device' => $result + ['device' => $key],
                    'country' => $result + ['country' => strtoupper($key)],
                    default => $result + ['key' => $key],
                };
            })
            ->filter(fn (array $row): bool => collect($row)->contains(fn ($value) => $value !== '' && $value !== 0.0))
            ->values()
            ->all();
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
