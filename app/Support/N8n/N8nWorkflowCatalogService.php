<?php

declare(strict_types=1);

namespace App\Support\N8n;

use App\Enums\N8nWorkflowCategory;
use App\Models\N8nWorkflowDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class N8nWorkflowCatalogService
{
    /**
     * @return array{created: int, updated: int, skipped: int, error: ?string}
     */
    public function syncFromRemote(): array
    {
        $base = rtrim((string) config('n8n.public_api.base_url'), '/');
        $key = (string) config('n8n.public_api.key');

        if ($base === '' || $key === '') {
            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'error' => 'Brak N8N_API_URL lub N8N_API_KEY w .env (synchronizacja wyłączona).',
            ];
        }

        try {
            $rows = $this->fetchAllWorkflowListRows($base, $key);
        } catch (Throwable $e) {
            Log::warning('n8n.sync_failed', ['exception' => $e->getMessage()]);

            return [
                'created' => 0,
                'updated' => 0,
                'skipped' => 0,
                'error' => 'Nie udało się połączyć z API n8n: ' . $e->getMessage(),
            ];
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $now = now();

        foreach ($rows as $row) {
            if (! is_array($row)) {
                $skipped++;

                continue;
            }

            $wid = isset($row['id']) ? (string) $row['id'] : '';
            $name = isset($row['name']) ? trim((string) $row['name']) : '';

            if ($wid === '' || $name === '') {
                $skipped++;

                continue;
            }

            $existing = N8nWorkflowDocument::query()->where('n8n_workflow_id', $wid)->first();

            if ($existing instanceof N8nWorkflowDocument) {
                $existing->update([
                    'name' => $name,
                    'last_synced_at' => $now,
                ]);
                $updated++;
            } else {
                $slug = $this->uniqueSlug(Str::slug($name) ?: 'workflow');
                N8nWorkflowDocument::query()->create([
                    'name' => $name,
                    'slug' => $slug,
                    'n8n_workflow_id' => $wid,
                    'category' => N8nWorkflowCategory::Other,
                    'summary' => 'Zaimportowane z n8n — uzupełnij opis i kategorię.',
                    'body_markdown' => null,
                    'is_visible' => true,
                    'sort_order' => 100,
                    'last_synced_at' => $now,
                ]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'error' => null,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllWorkflowListRows(string $base, string $key): array
    {
        $limit = 250;
        $cursor = null;
        $merged = [];
        $page = 0;
        $maxPages = 100;

        do {
            $page++;
            if ($page > $maxPages) {
                throw new \RuntimeException('n8n workflows list: pagination exceeded ' . $maxPages . ' pages.');
            }

            $query = ['limit' => $limit];
            if ($cursor !== null) {
                $query['cursor'] = $cursor;
            }

            $response = Http::timeout(90)
                ->withHeaders(['X-N8N-API-KEY' => $key])
                ->acceptJson()
                ->get($base . '/api/v1/workflows', $query);

            if (! $response->successful()) {
                throw new \RuntimeException('API n8n zwróciło HTTP ' . $response->status() . '.');
            }

            /** @var array<string, mixed>|list<mixed> $payload */
            $payload = $response->json() ?? [];

            if (is_array($payload) && array_is_list($payload)) {
                foreach ($payload as $item) {
                    if (is_array($item)) {
                        $merged[] = $item;
                    }
                }

                break;
            }

            if (! is_array($payload)) {
                break;
            }

            $chunk = $payload['data'] ?? null;
            if (is_array($chunk)) {
                foreach ($chunk as $item) {
                    if (is_array($item)) {
                        $merged[] = $item;
                    }
                }
            }

            $next = $payload['nextCursor'] ?? null;
            $cursor = is_string($next) && $next !== '' ? $next : null;
        } while ($cursor !== null);

        return $merged;
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 0;

        while (N8nWorkflowDocument::query()->where('slug', $slug)->exists()) {
            $i++;
            $slug = $base . '-' . $i;
        }

        return $slug;
    }
}
