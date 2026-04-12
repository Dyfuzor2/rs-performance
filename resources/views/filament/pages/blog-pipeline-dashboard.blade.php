<x-filament-panels::page>
    @php
        $stats = $this->getStats();
    @endphp

    <style>
        .bpd-wrap {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            position: relative;
        }

        .bpd-wrap::before {
            content: '';
            pointer-events: none;
            position: absolute;
            inset: 0;
            z-index: 0;
            opacity: 0.14;
            background:
                radial-gradient(ellipse 80% 50% at 20% 10%, rgba(220, 38, 38, 0.35), transparent 55%),
                radial-gradient(ellipse 60% 40% at 90% 80%, rgba(59, 130, 246, 0.2), transparent 50%),
                radial-gradient(ellipse 50% 30% at 50% 100%, rgba(16, 185, 129, 0.12), transparent 45%);
        }

        .bpd-wrap > * {
            position: relative;
            z-index: 1;
        }

        .bpd-grid {
            display: grid;
            gap: 1rem;
            grid-template-columns: repeat(2, 1fr);
        }

        .bpd-stat {
            position: relative;
            overflow: hidden;
            border-radius: 1rem;
            padding: 1rem 1.125rem;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 41, 59, 0.75) 100%);
            border: 1px solid rgba(148, 163, 184, 0.12);
            transition: border-color 0.2s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }

        .bpd-stat:hover {
            border-color: rgba(248, 113, 113, 0.25);
            transform: translateY(-1px);
            box-shadow: 0 16px 32px -12px rgba(0, 0, 0, 0.45);
        }

        .bpd-stat-val {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.1;
            color: #f8fafc;
        }

        .bpd-stat-lbl {
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: #64748b;
            margin-top: 0.35rem;
        }

        .bpd-stat-sub {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-top: 0.25rem;
        }

        .bpd-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.875rem;
            background: rgba(15, 23, 42, 0.55);
            border: 1px solid rgba(51, 65, 85, 0.35);
        }

        .bpd-select {
            appearance: none;
            border-radius: 0.5rem;
            border: 1px solid rgba(71, 85, 105, 0.6);
            background: rgba(30, 41, 59, 0.9);
            color: #e2e8f0;
            font-size: 0.8125rem;
            font-weight: 600;
            padding: 0.45rem 2rem 0.45rem 0.65rem;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.4rem center;
            background-size: 1rem;
        }

        .bpd-tbl {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        .bpd-tbl thead th {
            text-align: left;
            padding: 0.65rem 0.75rem;
            font-size: 0.625rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #64748b;
            background: rgba(15, 23, 42, 0.85);
            border-bottom: 1px solid rgba(51, 65, 85, 0.45);
            position: sticky;
            top: 0;
            z-index: 2;
        }

        .bpd-tbl tbody td {
            padding: 0.5rem 0.75rem;
            vertical-align: middle;
            border-top: 1px solid rgba(51, 65, 85, 0.25);
            color: #e2e8f0;
        }

        .bpd-tbl tbody tr:hover td {
            background: rgba(30, 41, 59, 0.35);
        }

        .bpd-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            padding: 0.2rem 0.55rem;
            font-size: 0.6875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            white-space: nowrap;
            max-width: 11rem;
        }

        .bpd-dot {
            height: 0.35rem;
            width: 0.35rem;
            border-radius: 9999px;
            flex-shrink: 0;
            box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.9);
        }

        .bpd-topic {
            max-width: 14rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-weight: 600;
            color: #f1f5f9;
        }

        .bpd-meta {
            font-size: 0.6875rem;
            color: #94a3b8;
            font-variant-numeric: tabular-nums;
        }

        .bpd-panel {
            border-radius: 1rem;
            overflow: hidden;
            border: 1px solid rgba(51, 65, 85, 0.45);
            background: linear-gradient(165deg, rgba(15, 23, 42, 0.55) 0%, rgba(2, 6, 23, 0.72) 100%);
            box-shadow:
                0 0 0 1px rgba(248, 113, 113, 0.06) inset,
                0 24px 48px -24px rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(8px);
        }

        .bpd-scroll {
            overflow-x: auto;
            max-width: 100%;
        }

        .bpd-live {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        .bpd-live-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
            animation: bpd-pulse 2.2s ease-in-out infinite;
        }

        @@keyframes bpd-pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.65;
                transform: scale(1.15);
            }
        }

        .bpd-hero-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.75rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #dc2626, #f97316);
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35);
        }

        @@media (min-width: 768px) {
            .bpd-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }
    </style>

    <div class="bpd-wrap" wire:poll.15s>

        {{-- Hero row: brand continuity with AI Traffic Center --}}
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div class="bpd-hero-icon shrink-0">
                    <svg class="h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M8.25 18.75h.008v.008H8.25v-.008Z" />
                    </svg>
                </div>
                <div class="min-w-0">
                    <div class="bpd-live">
                        <span class="bpd-live-dot" aria-hidden="true"></span>
                        <span>Auto-odświeżanie co 15 s</span>
                        <span class="text-slate-500">·</span>
                        <span class="tabular-nums">{{ now()->format('H:i:s') }}</span>
                    </div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 max-w-xl">KPI i tabela na żywo — bez
                        przeładowania całej strony.</p>
                </div>
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="bpd-grid">
            <div class="bpd-stat">
                <div class="bpd-stat-val">{{ $stats['total'] }}</div>
                <div class="bpd-stat-lbl">Razem</div>
                <div class="bpd-stat-sub">Wszystkie uruchomienia pipeline</div>
            </div>
            <div class="bpd-stat">
                <div class="bpd-stat-val text-emerald-400">{{ $stats['success'] }}</div>
                <div class="bpd-stat-lbl">Gotowe</div>
                <div class="bpd-stat-sub">Draft utworzony</div>
            </div>
            <div class="bpd-stat">
                <div class="bpd-stat-val text-rose-400">{{ $stats['failed'] }}</div>
                <div class="bpd-stat-lbl">Błędy</div>
                <div class="bpd-stat-sub">Wymagają uwagi</div>
            </div>
            <div class="bpd-stat">
                <div class="bpd-stat-val text-amber-300">{{ $stats['pending'] }}</div>
                <div class="bpd-stat-lbl">W toku</div>
                <div class="bpd-stat-sub">Kolejka / przetwarzanie</div>
            </div>
            <div class="bpd-stat">
                <div class="bpd-stat-val">{{ $this->formatDuration($stats['avg_duration_seconds']) }}</div>
                <div class="bpd-stat-lbl">Średni czas</div>
                <div class="bpd-stat-sub">Od startu do końca</div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bpd-toolbar">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-400">Status</span>
                <label class="sr-only" for="statusFilter">Filtr statusu</label>
                <select id="statusFilter" wire:model.live="statusFilter" class="bpd-select">
                    @foreach ($this->getStatusOptions() as $option)
                        <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="text-xs text-slate-500">
                Wiersze z błędem pokazują drugą linię z komunikatem.
            </div>
        </div>

        {{-- Table — $this->runs: Livewire paginator (WithPagination) --}}
        <div class="bpd-panel">
            @if ($this->runs->isEmpty())
                <div class="px-6 py-14 text-center">
                    <div
                        class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-800/80 ring-1 ring-slate-600/50">
                        <x-heroicon-o-inbox class="h-6 w-6 text-slate-500" />
                    </div>
                    <p class="text-base font-semibold text-slate-200">Brak uruchomień</p>
                    <p class="mt-1 max-w-md mx-auto text-sm text-slate-500">Jak tylko pipeline bloga wystartuje (cron,
                        Filament lub Telegram), rekordy pojawią się w tej tabeli.</p>
                </div>
            @else
                <div class="bpd-scroll">
                    <table class="bpd-tbl">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Temat</th>
                                <th>Źródło</th>
                                <th>VPS</th>
                                <th>Post</th>
                                <th>Telegram</th>
                                <th>Czas</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->runs as $run)
                                <tr wire:key="run-{{ $run->id }}">
                                    <td>
                                        <span class="bpd-pill bg-slate-800/90 ring-1 ring-white/5">
                                            <span class="bpd-dot {{ $this->statusAccentClasses($run->status) }}"></span>
                                            <span class="truncate">{{ $run->status->label() }}</span>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="bpd-topic" title="{{ $run->topic ?? '—' }}">
                                            {{ $run->topic ? \Illuminate\Support\Str::limit($run->topic, 52) : '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        @if ($run->requested_via)
                                            <span class="bpd-pill bg-slate-800/90 ring-1 ring-white/5">
                                                <span
                                                    class="bpd-dot {{ $this->sourceAccentClasses($run->requested_via) }}"></span>
                                                <span>{{ $run->requested_via->label() }}</span>
                                            </span>
                                        @else
                                            <span class="bpd-meta">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($run->vps_dispatched)
                                            <span
                                                class="inline-flex items-center rounded-full bg-emerald-500/15 px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-emerald-300 ring-1 ring-emerald-500/30">Tak</span>
                                        @else
                                            <span class="bpd-meta">Nie</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($run->blog_post_id)
                                            <a href="{{ url('/admin/blog-posts/' . $run->blog_post_id . '/edit') }}"
                                                class="font-semibold text-primary-400 underline decoration-primary-500/40 underline-offset-2 hover:text-primary-300">
                                                #{{ $run->blog_post_id }}
                                            </a>
                                        @else
                                            <span class="bpd-meta">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($run->telegram_result)
                                            @case('sent')
                                                <span
                                                    class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[0.65rem] font-bold text-emerald-300 ring-1 ring-emerald-500/25">Wysłano</span>
                                            @break

                                            @case('failed')
                                                <span
                                                    class="inline-flex rounded-full bg-rose-500/15 px-2 py-0.5 text-[0.65rem] font-bold text-rose-300 ring-1 ring-rose-500/25">Błąd</span>
                                            @break

                                            @case('skipped')
                                                <span class="bpd-meta">—</span>
                                            @break

                                            @default
                                                @if ($run->telegram_chat_id)
                                                    <span
                                                        class="inline-flex rounded-full bg-slate-600/40 px-2 py-0.5 text-[0.65rem] font-semibold text-slate-300 ring-1 ring-slate-500/30">Oczekuje</span>
                                                @else
                                                    <span class="bpd-meta">—</span>
                                                @endif
                                        @endswitch
                                    </td>
                                    <td class="bpd-meta">{{ $run->duration ?? '—' }}</td>
                                    <td class="bpd-meta">{{ $run->created_at?->format('d.m H:i') ?? '—' }}</td>
                                </tr>
                                @if ($run->error_message)
                                    <tr class="bg-rose-950/30" wire:key="run-error-{{ $run->id }}">
                                        <td colspan="8" class="!border-t-rose-900/40 text-xs text-rose-200/95">
                                            <span class="font-semibold text-rose-300">Błąd:</span>
                                            {{ \Illuminate\Support\Str::limit($run->error_message, 220) }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-700/50 bg-slate-950/40 px-3 py-2">
                    {{ $this->runs->links('pagination.bpd-tailwind') }}
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
