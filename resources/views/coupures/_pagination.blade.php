<nav aria-label="Outage pagination">
    <p>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results · Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</p>
    <div class="flex flex-wrap items-center gap-3">
        @if($paginator->onFirstPage())
            <button type="button" class="ha-btn ha-btn--outline" disabled>Previous</button>
        @else
            <a class="ha-btn ha-btn--outline" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>
        @endif
        @if($paginator->hasMorePages())
            <a class="ha-btn ha-btn--primary" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>
        @else
            <button type="button" class="ha-btn ha-btn--primary" disabled>Next</button>
        @endif
    </div>
</nav>
