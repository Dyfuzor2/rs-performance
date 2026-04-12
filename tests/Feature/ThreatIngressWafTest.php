<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class ThreatIngressWafTest extends TestCase
{
    public function test_probe_paths_return_stealth_not_found(): void
    {
        foreach (['/wp-admin', '/wp-login.php', '/xmlrpc.php', '/phpmyadmin/', '/vendor/phpunit/anything'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }

    public function test_filament_admin_is_not_wordpress_wp_admin_probe(): void
    {
        $status = $this->get('/admin')->getStatusCode();
        $this->assertNotSame(404, $status, 'Real /admin must not be blocked as wp-admin probe');
    }

    public function test_public_hub_pages_remain_reachable(): void
    {
        foreach (['/uslugi', '/problemy', '/blog'] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_sql_injection_toy_in_query_is_blocked(): void
    {
        $this->get('/?id=1%20union%20select%20null')->assertNotFound();
    }

    public function test_json_clients_get_404_payload(): void
    {
        $this->getJson('/wp-admin')->assertNotFound()->assertJson(['message' => 'Not Found']);
    }

    public function test_waf_can_be_disabled_via_config(): void
    {
        config(['waf.enabled' => false]);
        $this->get('/?x=union%20select%20null')->assertOk();

        config(['waf.enabled' => true]);
        $this->get('/?y=union%20select%20null')->assertNotFound();
    }

    public function test_waf_regex_patterns_are_valid_pcre(): void
    {
        foreach (config('waf.query_patterns') as $i => $pattern) {
            $m = @preg_match((string) $pattern, '');
            $this->assertNotFalse($m, 'Invalid waf.query_patterns['.$i.']');
        }

        foreach (config('waf.full_uri_patterns') as $i => $pattern) {
            $m = @preg_match((string) $pattern, '');
            $this->assertNotFalse($m, 'Invalid waf.full_uri_patterns['.$i.']');
        }
    }

    public function test_benign_numeric_query_does_not_break_waf_or_page(): void
    {
        $this->get('/uslugi?batch_size=2')->assertOk();
    }
}
