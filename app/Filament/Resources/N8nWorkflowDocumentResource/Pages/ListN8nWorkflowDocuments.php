<?php

declare(strict_types=1);

namespace App\Filament\Resources\N8nWorkflowDocumentResource\Pages;

use App\Filament\Resources\N8nWorkflowDocumentResource;
use App\Filament\Resources\N8nWorkflowDocumentResource\Widgets\N8nWorkflowHubOverview;
use App\Models\N8nWorkflowDocument;
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

        return sprintf(
            'Kwiecień 2026+ — glassmorphism, statystyki na żywo, kolory kategorii AEO. Katalog: %d wpisów · powiązanych z n8n: %d.',
            $n,
            $linked
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
                ->modalDescription('HEAD/GET jak w dokumentacji: nagłówek X-N8N-API-KEY, endpoint /api/v1/workflows. Odświeża podgląd „Połączenie API” w statystykach.')
                ->action(function (): void {
                    $h = app(N8nPublicApiHealthService::class)->probeFresh();

                    if ($h['ok']) {
                        Notification::make()
                            ->title('API n8n: OK')
                            ->body($h['message'] . ($h['latency_ms'] !== null ? sprintf(' · %.0f ms', $h['latency_ms']) : ''))
                            ->success()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('API n8n: problem')
                        ->body($h['message'])
                        ->danger()
                        ->send();
                }),
            Action::make('syncN8n')
                ->label('Synchronizuj z n8n')
                ->icon('heroicon-o-arrow-path')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Synchronizacja z API n8n')
                ->modalDescription('Pobierze listę workflowów i utworzy brakujące wpisy lub zaktualizuje nazwy istniejących (dopasowanie po ID workflow).')
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
