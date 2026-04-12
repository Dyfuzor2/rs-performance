<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\N8nWorkflowCategory;
use App\Filament\Resources\N8nWorkflowDocumentResource\Pages;
use App\Models\N8nWorkflowDocument;
use App\Models\User;
use App\Support\N8n\N8nConfigPresenter;
use App\Support\N8n\N8nWorkflowExecuteService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use UnitEnum;

final class N8nWorkflowDocumentResource extends Resource
{
    protected static ?string $model = N8nWorkflowDocument::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-command-line';
    protected static ?string $navigationLabel = 'n8n WOW Ops Hub';
    protected static ?string $modelLabel = 'Workflow n8n';
    protected static ?string $pluralModelLabel = 'Workflowy n8n';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 11;

    public static function canViewAny(): bool
    {
        return self::isRoot();
    }

    public static function canCreate(): bool
    {
        return self::isRoot();
    }

    public static function canEdit(Model $record): bool
    {
        return self::isRoot();
    }

    public static function canDelete(Model $record): bool
    {
        return self::isRoot();
    }

    public static function canDeleteAny(): bool
    {
        return self::isRoot();
    }

    private static function isRoot(): bool
    {
        /** @var User|null $user */
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isRoot();
    }

    /**
     * Wyzwala workflow przez Public API (POST /api/v1/workflows/{id}/execute) i pokazuje toast.
     */
    public static function notifyTriggerWorkflowExecution(N8nWorkflowDocument $record): void
    {
        $result = app(N8nWorkflowExecuteService::class)->execute((string) ($record->n8n_workflow_id ?? ''));

        if ($result['ok']) {
            Notification::make()
                ->title('Workflow wyzwolony')
                ->body($result['message'])
                ->success()
                ->send();

            return;
        }

        Notification::make()
            ->title('Nie udało się wyzwolić workflow')
            ->body($result['message'])
            ->danger()
            ->persistent()
            ->send();
    }

    /**
     * Bazowa akcja „Wyzwól” — dołącz visible + action w tabeli lub na stronie edycji.
     */
    public static function makeTriggerWorkflowAction(): Action
    {
        return Action::make('triggerWorkflow')
            ->label('Wyzwól')
            ->icon('heroicon-o-bolt')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Wyzwolenie workflow w n8n')
            ->modalDescription('Wyśle POST do Public API: /api/v1/workflows/{id}/execute (nagłówek X-N8N-API-KEY). Upewnij się, że klucz API ma uprawnienie workflow:execute.')
            ->modalSubmitActionLabel('Wyzwól');
    }

    public static function triggerWorkflowTableAction(): Action
    {
        return self::makeTriggerWorkflowAction()
            ->visible(fn (N8nWorkflowDocument $r): bool => $r->n8n_workflow_id !== null && $r->n8n_workflow_id !== '')
            ->action(function (N8nWorkflowDocument $record): void {
                self::notifyTriggerWorkflowExecution($record);
            });
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Identyfikacja')
                ->icon('heroicon-o-link')
                ->description('Powiąż wpis z workflow w n8n (ID z URL edytora `/workflow/{id}`).')
                ->schema([
                    Grid::make(2)->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nazwa wyświetlana')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, ?Model $record): void {
                                if ($record !== null) {
                                    return;
                                }
                                $slug = Str::slug((string) $state);
                                if ($slug !== '') {
                                    $set('slug', $slug);
                                }
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL w panelu)')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Unikalny identyfikator w tym module — nie mylić ze slugiem strony WWW.'),
                    ]),
                    Forms\Components\TextInput::make('n8n_workflow_id')
                        ->label('ID workflow w n8n')
                        ->maxLength(64)
                        ->placeholder('np. W1xRg73xFDUXYrRI'),
                    Forms\Components\Select::make('category')
                        ->label('Kategoria')
                        ->options(collect(N8nWorkflowCategory::cases())->mapWithKeys(
                            fn (N8nWorkflowCategory $c): array => [$c->value => $c->label()]
                        ))
                        ->required()
                        ->native(false),
                ]),
            Section::make('Opis operacyjny')
                ->icon('heroicon-o-document-text')
                ->description('Markdown + tagi — pod AEO, operacje i onboarding nowych agentów.')
                ->schema([
                    Forms\Components\Textarea::make('summary')
                        ->label('Jedno zdanie')
                        ->rows(2)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('body_markdown')
                        ->label('Pełny opis (Markdown)')
                        ->rows(16)
                        ->helperText('Co robi, jakie API, Telegram, cron, zależności. Ten tekst widzisz tylko Ty — idealny na „pamięć zespołu”.')
                        ->columnSpanFull(),
                    Forms\Components\TextInput::make('schedule_hint')
                        ->label('Harmonogram (tekst)')
                        ->placeholder('np. cron co 1 h, Europe/Warsaw')
                        ->maxLength(255),
                    Forms\Components\TagsInput::make('tags')
                        ->label('Tagi')
                        ->separator(',')
                        ->placeholder('np. telegram, dtc, vertex')
                        ->columnSpanFull(),
                    Grid::make(2)->schema([
                        Forms\Components\Toggle::make('is_visible')
                            ->label('Widoczny na liście')
                            ->default(true),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Kolejność')
                            ->numeric()
                            ->default(0),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(
                new HtmlString(
                    '<div class="relative mb-4 overflow-hidden rounded-3xl border border-white/10 bg-linear-to-br from-slate-950 via-rose-950/40 to-indigo-950/50 p-5 text-sm text-slate-100 shadow-[0_0_40px_-10px_rgba(244,63,94,0.45)] ring-1 ring-rose-500/20 backdrop-blur-sm">'
                        . '<div class="pointer-events-none absolute -right-16 -top-16 size-48 rounded-full bg-fuchsia-500/25 blur-3xl"></div>'
                        . '<div class="pointer-events-none absolute -bottom-20 left-1/4 size-56 rounded-full bg-cyan-500/15 blur-3xl"></div>'
                        . '<div class="relative flex flex-wrap items-start gap-3">'
                        . '<span class="inline-flex items-center gap-1.5 rounded-full bg-rose-500/25 px-3 py-1 text-[11px] font-bold uppercase tracking-[0.2em] text-rose-50 ring-1 ring-rose-400/40">April 2026+ WOW</span>'
                        . '<span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-emerald-100 ring-1 ring-emerald-400/30">AEO-first</span>'
                        . '<span class="inline-flex items-center gap-1.5 rounded-full bg-indigo-500/15 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-indigo-100 ring-1 ring-indigo-400/30">Live sync</span>'
                        . '</div>'
                        . '<p class="relative mt-3 max-w-4xl text-[13px] leading-relaxed text-slate-200">Dokumentacja operacyjna: <strong class="text-white">co robi workflow</strong>, API, harmonogram, Telegram. <strong class="text-white">Public REST API</strong> (sync, „Graf n8n”, execute) = docker <strong class="text-white">n8n na VPS</strong> — <code class="rounded-md bg-black/40 px-1 font-mono text-cyan-200">N8N_API_URL</code> + <code class="rounded-md bg-black/40 px-1 font-mono text-cyan-200">N8N_API_KEY</code> w <code class="rounded-md bg-black/40 px-1 font-mono text-cyan-200">.env</code> hostingu. Osobno: <strong class="text-white">n8n-mcp</strong> (HTTP + <code class="rounded-md bg-black/40 px-1 font-mono text-fuchsia-200">AUTH_TOKEN</code>) — narzędzia dla Cursora; status w kafelku „MCP HTTP (VPS)”.</p>'
                        . '<p class="relative mt-2 text-xs text-slate-300">Aktywny host Public API: <code class="rounded-md bg-black/50 px-1.5 py-0.5 font-mono text-emerald-200">' . e(N8nConfigPresenter::publicApiHostLabel()) . '</code></p>'
                        . '<p class="relative mt-2 text-xs text-slate-400">Most hosting ↔ wywołania HTTP z n8n: <code class="rounded-md bg-black/40 px-1.5 py-0.5 font-mono text-rose-200">php artisan ops:verify-n8n-hosting-bridge</code> — spójność <code class="rounded-md bg-black/40 px-1 font-mono">N8N_API_TOKEN</code> z nagłówkami w workflowach.</p>'
                        . '</div>'
                )
            )
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width('4rem'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nazwa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (N8nWorkflowDocument $r): ?string => $r->summary),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategoria')
                    ->badge()
                    ->formatStateUsing(fn (?N8nWorkflowCategory $state): string => $state?->label() ?? '—')
                    ->color(fn (?N8nWorkflowCategory $state): string => $state?->filamentBadgeColor() ?? 'gray'),
                Tables\Columns\TextColumn::make('n8n_workflow_id')
                    ->label('n8n ID')
                    ->fontFamily('mono')
                    ->size('xs')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('schedule_hint')
                    ->label('Harmonogram')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_visible')
                    ->label('Widoczny')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_synced_at')
                    ->label('Ostatnia sync')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Edycja')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategoria')
                    ->options(collect(N8nWorkflowCategory::cases())->mapWithKeys(
                        fn (N8nWorkflowCategory $c): array => [$c->value => $c->label()]
                    )),
                Tables\Filters\TernaryFilter::make('is_visible')
                    ->label('Widoczny'),
            ])
            ->actions([
                self::triggerWorkflowTableAction(),
                EditAction::make(),
                Action::make('openEditor')
                    ->label('Graf n8n')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('info')
                    ->url(fn (N8nWorkflowDocument $r): ?string => $r->editorUrl())
                    ->openUrlInNewTab()
                    ->visible(fn (N8nWorkflowDocument $r): bool => $r->editorUrl() !== null),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order', 'asc')
            ->reorderable('sort_order')
            ->poll('60s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListN8nWorkflowDocuments::route('/'),
            'create' => Pages\CreateN8nWorkflowDocument::route('/create'),
            'edit' => Pages\EditN8nWorkflowDocument::route('/{record}/edit'),
        ];
    }
}
