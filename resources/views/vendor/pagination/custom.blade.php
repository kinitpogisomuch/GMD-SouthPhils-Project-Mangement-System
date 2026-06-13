@if ($paginator->hasPages())
    <nav class="pagination-nav" role="navigation" aria-label="Pagination Navigation">

        @if ($paginator->onFirstPage())
            <span class="pagination-btn disabled" aria-disabled="true">
                <i data-lucide="chevron-left"></i>
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" aria-label="Previous">
                <i data-lucide="chevron-left"></i>
            </a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-dots">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-page active" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination-page">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" aria-label="Next">
                <i data-lucide="chevron-right"></i>
            </a>
        @else
            <span class="pagination-btn disabled" aria-disabled="true">
                <i data-lucide="chevron-right"></i>
            </span>
        @endif

    </nav>
@endif
