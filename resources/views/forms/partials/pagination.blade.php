@if ($paginator->hasPages())
    <nav class="fm-pagination" aria-label="Pagination">
        <span>Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }} · {{ $paginator->total() }} results</span>
        <div>@if($paginator->previousPageUrl())<a class="admin-button" href="{{ $paginator->previousPageUrl() }}">← Previous</a>@endif @if($paginator->nextPageUrl())<a class="admin-button" href="{{ $paginator->nextPageUrl() }}">Next →</a>@endif</div>
    </nav>
@endif
