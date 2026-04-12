<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\BlogPipelineSource;
use App\Enums\BlogPipelineStatus;
use App\Models\BlogPipelineRun;
use Filament\Pages\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\WithPagination;

final class BlogPipelineDashboard extends Page
{
    use WithPagination;
    protected static ?string $navigationLabel = 'Blog Pipeline';

    protected static ?string $title = 'Blog Pipeline';

    protected static ?int $navigationSort = 35;

    protected string $view = 'filament.pages.blog-pipeline-dashboard';

    public string $statusFilter = '';

    public function getSubheading(): ?string
    {
        return 'Operacyjny podgląd pipeline Vertex: cron, Filament, Telegram, API i support plane — ze statusem draftu i czasem trwania.';
    }

    public function statusAccentClasses(BlogPipelineStatus $status): string
    {
        return match ($status) {
            BlogPipelineStatus::Pending => 'bg-slate-400 shadow-slate-400/40',
            BlogPipelineStatus::Dispatched => 'bg-sky-400 shadow-sky-400/40',
            BlogPipelineStatus::Processing => 'bg-amber-400 shadow-amber-400/40 animate-pulse',
            BlogPipelineStatus::DraftCreated => 'bg-emerald-400 shadow-emerald-400/50',
            BlogPipelineStatus::Failed => 'bg-rose-500 shadow-rose-500/50',
        };
    }

    public function sourceAccentClasses(BlogPipelineSource $source): string
    {
        return match ($source) {
            BlogPipelineSource::Filament => 'bg-primary-500 shadow-primary-500/30',
            BlogPipelineSource::Cron => 'bg-slate-400 shadow-slate-400/30',
            BlogPipelineSource::Telegram => 'bg-sky-400 shadow-sky-400/30',
            BlogPipelineSource::Api => 'bg-amber-400 shadow-amber-400/30',
            BlogPipelineSource::SupportPlane => 'bg-emerald-400 shadow-emerald-400/30',
        };
    }

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-signal';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Treści';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isRoot() ?? false;
    }

    /**
     * @return array{total: int, success: int, failed: int, pending: int, avg_duration_seconds: int|null}
     */
    public function getStats(): array
    {
        $query = BlogPipelineRun::query();

        $total = (int) (clone $query)->count();
        $success = (int) (clone $query)->where('status', BlogPipelineStatus::DraftCreated)->count();
        $failed = (int) (clone $query)->where('status', BlogPipelineStatus::Failed)->count();
        $pending = (int) (clone $query)
            ->whereIn('status', [
                BlogPipelineStatus::Pending,
                BlogPipelineStatus::Dispatched,
                BlogPipelineStatus::Processing,
            ])
            ->count();

        $avgDuration = (clone $query)
            ->whereNotNull('started_at')
            ->whereNotNull('completed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, started_at, completed_at)) as avg_seconds')
            ->value('avg_seconds');

        return [
            'total' => $total,
            'success' => $success,
            'failed' => $failed,
            'pending' => $pending,
            'avg_duration_seconds' => $avgDuration !== null ? (int) round((float) $avgDuration) : null,
        ];
    }

    public function getRunsProperty(): LengthAwarePaginator
    {
        $query = BlogPipelineRun::query()
            ->with('blogPost:id,title,slug')
            ->latest();

        if ($this->statusFilter !== '') {
            $status = BlogPipelineStatus::tryFrom($this->statusFilter);

            if ($status !== null) {
                $query->where('status', $status);
            }
        }

        return $query->paginate(25);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function getStatusOptions(): array
    {
        return [
            ['value' => '', 'label' => 'Wszystkie'],
            ...array_map(
                static fn(BlogPipelineStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                BlogPipelineStatus::cases(),
            ),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function getSourceOptions(): array
    {
        return array_map(
            static fn(BlogPipelineSource $source): array => [
                'value' => $source->value,
                'label' => $source->label(),
            ],
            BlogPipelineSource::cases(),
        );
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }

        return match (true) {
            $seconds < 60 => $seconds . 's',
            $seconds < 3600 => intdiv($seconds, 60) . 'm ' . ($seconds % 60) . 's',
            default => intdiv($seconds, 3600) . 'h ' . intdiv($seconds % 3600, 60) . 'm',
        };
    }
}
