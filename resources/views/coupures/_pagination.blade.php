<nav aria-label="Pagination des coupures">
    <p>{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} sur {{ $paginator->total() }} résultats · Page {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</p>
    <div class="flex flex-wrap items-center gap-3">
        @if($paginator->onFirstPage())
            <button type="button" class="ha-btn ha-btn--outline" disabled>Précédent</button>
        @else
            <a class="ha-btn ha-btn--outline" href="{{ $paginator->previousPageUrl() }}" rel="prev">Précédent</a>
        @endif
        @if($paginator->hasMorePages())
            <a class="ha-btn ha-btn--primary" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant</a>
        @else
            <button type="button" class="ha-btn ha-btn--primary" disabled>Suivant</button>
        @endif
    </div>
</nav>
