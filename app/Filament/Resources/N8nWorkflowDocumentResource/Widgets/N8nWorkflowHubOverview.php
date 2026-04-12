<?php

declare(strict_types=1);

namespace App\Filament\Resources\N8nWorkflowDocumentResource\Widgets;

use App\Models\N8nWorkflowDocument;
use App\Support\N8n\N8nConfigPresenter;
use App\Support\N8n\N8nPublicApiHealthService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

final class N8nWorkflowHubOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $base = N8nWorkflowDocument::query();
        $total = (int) (clone $base)->count();
        $visible = (int) (clone $base)->where('is_visible', true)->count();
        $linked = (int) (clone $base)
            ->whereNotNull('n8n_workflow_id')
            ->where('n8n_workflow_id', '!=', '')
            ->count();
        $recentSync = (int) (clone $base)
            ->whereNotNull('last_synced_at')
            ->where('last_synced_at', '>=', now()->subHours(24))
            ->count();

        $health = app(N8nPublicApiHealthService::class)->snapshot();
        $apiLine = $this->formatApiStatLine($health);
        $executeLine = $this->formatExecuteCapabilityStatLine($health);
        $mcpLine = $this->formatMcpStatLine($health);

        return [
            Stat::make('Połączenie API n8n', $apiLine['title'])
                ->description($apiLine['description'])
                ->descriptionIcon($apiLine['icon'])
                ->color($apiLine['color']),
            Stat::make('WOW · Public API execute', $executeLine['title'])
                ->description($executeLine['description'])
                ->descriptionIcon($executeLine['icon'])
                ->color($executeLine['color']),
            Stat::make('MCP HTTP (VPS)', $mcpLine['title'])
                ->description($mcpLine['description'])
                ->descriptionIcon($mcpLine['icon'])
                ->color($mcpLine['color']),
            Stat::make('Katalog', (string) $total)
                ->description('Wpisy dokumentacji workflowów')
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->chart($this->sparklineCounts())
                ->color('primary'),
            Stat::make('Widoczne', (string) $visible)
                ->description('Na liście operacyjnej')
                ->descriptionIcon('heroicon-o-eye')
                ->color('success'),
            Stat::make('Powiązane z n8n', (string) $linked)
                ->description('Mają ID workflow z edytora')
                ->descriptionIcon('heroicon-o-link')
                ->color('info'),
            Stat::make('Sync 24h', (string) $recentSync)
                ->description('Świeża synchronizacja nazw z API')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),
        ];
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array{title: string, description: string, icon: string, color: string}
     */
    /**
     * @param  array<string, mixed>  $health
     * @return array{title: string, description: string, icon: string, color: string}
     */
    private function formatExecuteCapabilityStatLine(array $health): array
    {
        if (! ($health['env_configured'] ?? false) || ! ($health['ok'] ?? false)) {
            return [
                'title' => '—',
                'description' => 'Najpierw musi być OK: GET /api/v1/workflows (lewy kafelek).',
                'icon' => 'heroicon-o-question-mark-circle',
                'color' => 'gray',
            ];
        }

        $supported = $health['execute_post_supported'] ?? null;
        $st = $health['execute_probe_http_status'] ?? null;
        $detail = (string) ($health['execute_probe_detail'] ?? '');

        if ($supported === true) {
            $code = $st !== null ? sprintf(' · probe HTTP %d', $st) : '';

            return [
                'title' => 'Natywny POST ✓',
                'description' => 'Kwiecień 2026+ — „Wyzwól” z panelu idzie w execute API (404/403 na probe = trasa żywa).' . $code,
                'icon' => 'heroicon-o-bolt',
                'color' => 'success',
            ];
        }

        if ($supported === false) {
            return [
                'title' => 'Hybrid webhook WOW',
                'description' => $detail !== '' ? $detail : 'HTTP 405 — instancja bez POST …/execute. Użyj ścieżki webhook w rekordzie + `docker compose pull n8n` na VPS.',
                'icon' => 'heroicon-o-sparkles',
                'color' => 'warning',
            ];
        }

        return [
            'title' => 'Nieznany',
            'description' => $detail !== '' ? $detail : 'Probe execute nie ustalił trybu — „Test API n8n” odświeży.',
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'gray',
        ];
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array{title: string, description: string, icon: string, color: string}
     */
    private function formatApiStatLine(array $health): array
    {
        if (! ($health['env_configured'] ?? false)) {
            return [
                'title' => 'Brak .env',
                'description' => 'N8N_API_URL + N8N_API_KEY (jak MCP) — docs.n8n.io (Settings → API)',
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'danger',
            ];
        }

        $host = N8nConfigPresenter::publicApiHostLabel();

        if ($health['ok'] ?? false) {
            $latency = $health['latency_ms'] !== null ? sprintf('%.0f ms', $health['latency_ms']) : '—';
            $hz = ($health['health_ok'] ?? null) === true ? ' · /healthz OK' : '';
            $mcp = $this->mcpHintSuffix($health);

            return [
                'title' => 'Live · ' . $latency,
                'description' => ($health['message'] ?? 'OK') . $hz . ' · ' . $host . $mcp,
                'icon' => 'heroicon-o-signal',
                'color' => 'success',
            ];
        }

        $desc = (string) ($health['message'] ?? 'Sprawdź URL i klucz API') . ' · ' . $host;
        $hints = $health['hints'] ?? [];
        if (is_array($hints) && $hints !== []) {
            $desc .= ' · ' . (string) ($hints[0] ?? '');
        }

        return [
            'title' => 'Błąd',
            'description' => $desc . $this->mcpHintSuffix($health),
            'icon' => 'heroicon-o-x-circle',
            'color' => 'danger',
        ];
    }

    /**
     * @param  array<string, mixed>  $health
     */
    private function mcpHintSuffix(array $health): string
    {
        if (($health['mcp_health_ok'] ?? null) === true) {
            $ms = $health['mcp_latency_ms'] !== null ? sprintf(' · MCP %.0f ms', $health['mcp_latency_ms']) : '';

            return $ms . ' · ' . (string) ($health['mcp_label'] ?? '');
        }

        if (($health['mcp_health_ok'] ?? null) === false) {
            return ' · MCP: brak odpowiedzi /health (VPS)';
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $health
     * @return array{title: string, description: string, icon: string, color: string}
     */
    private function formatMcpStatLine(array $health): array
    {
        $label = (string) ($health['mcp_label'] ?? 'MCP');

        if (($health['mcp_health_ok'] ?? null) === null) {
            return [
                'title' => 'Wyłączony',
                'description' => 'Ustaw N8N_MCP_HEALTH_URL w .env (np. https://n8n-mcp.rs3d.pl/health)',
                'icon' => 'heroicon-o-minus-circle',
                'color' => 'gray',
            ];
        }

        if (($health['mcp_health_ok'] ?? false) === true) {
            $ms = $health['mcp_latency_ms'] !== null ? sprintf('%.0f ms', $health['mcp_latency_ms']) : '—';

            return [
                'title' => 'Live · ' . $ms,
                'description' => '/health OK · ' . $label . ' · agent tools (Cursor)',
                'icon' => 'heroicon-o-cpu-chip',
                'color' => 'success',
            ];
        }

        return [
            'title' => 'Offline',
            'description' => 'Hosting nie widzi ' . $label . ' — DNS / firewall / Caddy na VPS',
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'warning',
        ];
    }

    /**
     * @return array<int, int>
     */
    private function sparklineCounts(): array
    {
        $points = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = now()->subDays($i)->startOfDay();
            $points[] = (int) N8nWorkflowDocument::query()
                ->where('created_at', '<=', $day->copy()->endOfDay())
                ->count();
        }

        return $points;
    }
}
