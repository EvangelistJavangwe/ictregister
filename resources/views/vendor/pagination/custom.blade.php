@if ($paginator->hasPages())
<nav class="pagination" aria-label="Pagination">

    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="pagination-btn disabled" aria-disabled="true">
            <i class="fas fa-chevron-left"></i>
        </span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="pagination-btn" rel="prev" aria-label="Previous">
            <i class="fas fa-chevron-left"></i>
        </a>
    @endif

    {{-- Page numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="pagination-btn pagination-dots">{{ $element }}</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="pagination-btn active" aria-current="page">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="pagination-btn">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="pagination-btn" rel="next" aria-label="Next">
            <i class="fas fa-chevron-right"></i>
        </a>
    @else
        <span class="pagination-btn disabled" aria-disabled="true">
            <i class="fas fa-chevron-right"></i>
        </span>
    @endif

</nav>
@endif
