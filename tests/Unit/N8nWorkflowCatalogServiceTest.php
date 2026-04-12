<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\N8nWorkflowDocument;
use App\Support\N8n\N8nWorkflowCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class N8nWorkflowCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_from_remote_follows_next_cursor_until_exhausted(): void
    {
        Config::set('n8n.public_api.base_url', 'https://n8n.example.test');
        Config::set('n8n.public_api.key', 'test-key');

        Http::fake(function (Request $request) {
            $url = $request->url();
            if (! str_contains($url, '/api/v1/workflows')) {
                return Http::response('not found', 404);
            }
            if (! str_contains($url, 'cursor=')) {
                return Http::response([
                    'data' => [['id' => 'wf-1', 'name' => 'First']],
                    'nextCursor' => 'page2',
                ], 200);
            }

            return Http::response([
                'data' => [['id' => 'wf-2', 'name' => 'Second']],
                'nextCursor' => null,
            ], 200);
        });

        N8nWorkflowDocument::query()->delete();

        $result = app(N8nWorkflowCatalogService::class)->syncFromRemote();

        $this->assertNull($result['error']);
        $this->assertSame(2, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(2, N8nWorkflowDocument::query()->count());
    }
}
