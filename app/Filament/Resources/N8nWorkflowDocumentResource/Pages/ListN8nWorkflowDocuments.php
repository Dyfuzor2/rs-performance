<?php

declare(strict_types=1);

namespace App\Filament\Resources\N8nWorkflowDocumentResource\Pages;

use App\Filament\Resources\N8nWorkflowDocumentResource;
use App\Filament\Resources\N8nWorkflowDocumentResource\Widgets\N8nWorkflowHubOverview;
use App\Models\N8nWorkflowDocument;
use App\Support\N8n\N8nConfigPresenter;
use App\Support\N8n\N8nPublicApiHealthService;
use App\Support\N8n\N8nWorkflowCatalogService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;

final class ListN8nWorkflowDocuments extends ListRecords
{
    protected static string $resource = N8nWorkflowDocumentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function getTitle(): string
    {
        return 'n8n — WOW Ops Hub';
    }

    public function getSubheading(): ?string
    {
        $n = N8nWorkflowDocument::query()->count();
        $linked = N8nWorkflowDocument::query()
            ->whereNotNull('n8n_workflow_id')
            ->where('n8n_workflow_id', '!=', '')
            ->count();

        $cap = app(N8nPublicApiHealthService::class)->snapshot();
        $execHint = '';
        if (($cap['env_configured'] ?? false) && ($cap['ok'] ?? false)) {
            $execHint = match ($cap['execute_post_supported'] ?? null) {
                true => ' · Execute API: natywny POST (WOW)',
                false => ' · Execute API: hybrid webhook (do czasu upgrade n8n)',
                default => '',
            };
        }

        return sprintf(
            'Kwiecień 2026+ — VPS: Public API n8n (N8N_API_URL — edytor „Graf n8n” + sync) oraz n8n-mcp HTTP (narzędzia agentów). Instancja API: %s · Katalog: %d wpisów · powiązanych z n8n: %d%s.',
            N8nConfigPresenter::publicApiHostLabel(),
            $n,
            $linked,
            $execHint
        );
    }

    protected function getHeaderWidgets(): array
    {
        return [
            N8nWorkflowHubOverview::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('probeN8nApi')
                ->label('Test API n8n')
                ->icon('heroicon-o-signal')
                ->color('info')
                ->modalHeading('Public REST API (n8n docs)')
                ->modalDescription('Public REST n8n (nie MCP): nagłówek X-N8N-API-KEY, GET /api/v1/workflows. Osobno: ping /health serwera n8n-mcp na VPS (drugi kafelek). Odświeża oba podglądy.')
                ->action(function (): void {
                    $h = app(N8nPublicApiHealthService::class)->probeFresh();

                    if ($h['ok']) {
                        $body = $h['message'] . ($h['latency_ms'] !== null ? sprintf(' · %.0f ms', $h['latency_ms']) : '');
                        $xd = $h['execute_probe_detail'] ?? null;
                        if (is_string($xd) && $xd !== '') {
                            $body .= "\n\n" . $xd;
                        }

                        Notification::make()
                            ->title('API n8n: OK')
                            ->body($body)
                            ->success()
                            ->send();

                        return;
                    }

                    $body = (string) $h['message'];
                    $hints = $h['hints'] ?? [];
                    if (is_array($hints) && $hints !== []) {
                        $body .= "\n\n" . implode("\n", array_map(static fn (string $line): string => '• ' . $line, $hints));
                    }

                    Notification::make()
                        ->title('API n8n: problem')
                        ->body($body)
                        ->danger()
                        ->persistent()
                        ->send();
                }),
            Action::make('syncN8n')
                ->label('Synchronizuj z n8n')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Synchronizacja z API n8n')
                ->modalDescription('Pobierze listę workflowów z instancji `N8N_API_URL` (docker n8n na VPS — np. auto.rs3d.pl). n8n-mcp to osobny serwer narzędzi; katalog w Filamentie buduje się z Public API.')
                ->action(function (): void {
                    $result = app(N8nWorkflowCatalogService::class)->syncFromRemote();

                    if ($result['error'] !== null) {
                        Notification::make()
                            ->title('Synchronizacja nieudana')
                            ->body($result['error'])
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Synchronizacja zakończona')
                        ->body(sprintf(
                            'Nowe: %d | Zaktualizowane nazwy: %d | Pominięte: %d',
                            $result['created'],
                            $result['updated'],
                            $result['skipped']
                        ))
                        ->success()
                        ->send();
                }),
            CreateAction::make()
                ->label('Nowy dokument workflow')
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
