{{-- Blog Pipeline: Livewire pagination (wire:click) + bounded SVG icons --}}
@php
    if (! isset($scrollTo)) {
        $scrollTo = '.bpd-scroll';
    }

    $scrollIntoViewJsSnippet = $scrollTo !== false
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}'))?.scrollIntoView({ behavior: 'smooth', block: 'start' })
        JS
        : '';
@endphp

@if ($paginator->hasPages())
    <nav class="bpd-pagination" role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <style>
            .bpd-pagination svg {
                width: 1.25rem !important;
                height: 1.25rem !important;
                max-width: 1.25rem !important;
                max-height: 1.25rem !important;
                flex-shrink: 0;
            }
        </style>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-slate-400 leading-5">
                {!! __('Showing') !!}
                @if ($paginator->firstItem())
                    <span class="font-semibold text-slate-200">{{ $paginator->firstItem() }}</span>
                    {!! __('to') !!}
                    <span class="font-semibold text-slate-200">{{ $paginator->lastItem() }}</span>
                @else
                    {{ $paginator->count() }}
                @endif
                {!! __('of') !!}
                <span class="font-semibold text-slate-200">{{ $paginator->total() }}</span>
                {!! __('results') !!}
            </p>

            <span class="inline-flex rtl:flex-row-reverse rounded-lg border border-slate-600/80 bg-slate-900/80 shadow-sm">
                @if ($paginator->onFirstPage())
                    <span
                        class="inline-flex items-center px-2 py-1.5 text-slate-500 cursor-not-allowed rounded-l-lg"
                        aria-disabled="true"
                        aria-label="{!! __('pagination.previous') !!}"
                    >
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path
                                fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                @else
                    <button
                        type="button"
                        wire:click="previousPage('{{ $paginator->getPageName() }}')"
                        @if ($scrollTo !== false)
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        @endif
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-2 py-1.5 text-slate-300 rounded-l-lg hover:bg-slate-800/90 focus:outline-none focus:ring-2 focus:ring-primary-500/40"
                        aria-label="{!! __('pagination.previous') !!}"
                    >
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path
                                fill-rule="evenodd"
                                d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="inline-flex items-center px-3 py-1.5 -ml-px text-sm text-slate-500 border-l border-slate-600/80">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span
                                    aria-current="page"
                                    class="inline-flex items-center px-3 py-1.5 -ml-px text-sm font-semibold text-primary-300 border-l border-slate-600/80 bg-slate-800/90"
                                >
                                    {{ $page }}
                                </span>
                            @else
                                <button
                                    type="button"
                                    wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                    @if ($scrollTo !== false)
                                        x-on:click="{{ $scrollIntoViewJsSnippet }}"
                                    @endif
                                    class="inline-flex items-center px-3 py-1.5 -ml-px text-sm font-medium text-slate-300 border-l border-slate-600/80 hover:bg-slate-800/90"
                                    aria-label="{{ __('Go to page :page', ['page' => $page]) }}"
                                >
                                    {{ $page }}
                                </button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <button
                        type="button"
                        wire:click="nextPage('{{ $paginator->getPageName() }}')"
                        @if ($scrollTo !== false)
                            x-on:click="{{ $scrollIntoViewJsSnippet }}"
                        @endif
                        wire:loading.attr="disabled"
                        class="inline-flex items-center px-2 py-1.5 -ml-px text-slate-300 rounded-r-lg border-l border-slate-600/80 hover:bg-slate-800/90 focus:outline-none focus:ring-2 focus:ring-primary-500/40"
                        aria-label="{!! __('pagination.next') !!}"
                    >
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </button>
                @else
                    <span
                        class="inline-flex items-center px-2 py-1.5 -ml-px text-slate-500 cursor-not-allowed rounded-r-lg border-l border-slate-600/80"
                        aria-disabled="true"
                        aria-label="{!! __('pagination.next') !!}"
                    >
                        <svg fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path
                                fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif
