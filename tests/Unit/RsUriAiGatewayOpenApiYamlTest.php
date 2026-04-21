<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\RsUri;
use Tests\TestCase;

final class RsUriAiGatewayOpenApiYamlTest extends TestCase
{
    public function test_ai_gateway_openapi_yaml_is_yaml_sibling_of_json(): void
    {
        config(['ops.ai_gateway_url' => 'https://ai.rsperformance.online']);

        $this->assertSame(
            'https://ai.rsperformance.online/.well-known/openapi.yaml',
            RsUri::aiGatewayOpenApiYaml()
        );
        $this->assertSame(
            'https://ai.rsperformance.online/.well-known/openapi.json',
            RsUri::aiGatewayOpenApi()
        );
    }
}
