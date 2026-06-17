<style>
    .custom-page-link {
        width: 36px;
        height: 36px;
        background-color: #fff;
        border: 1px solid #dee2e6;
        color: #dc3545;
        /* text-danger */
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        text-decoration: none;
        transition: all 0.2s ease-in-out;
    }

    .custom-page-link:hover {
        background-color: #dc3545;
        color: #fff !important;
        border-color: #dc3545;
    }

    .custom-page-active {
        width: 36px;
        height: 36px;
        background-color: #198754;
        /* text-success */
        border: 1px solid #198754;
        color: #fff;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px rgba(25, 135, 84, 0.25);
    }

    .custom-page-disabled {
        width: 36px;
        height: 36px;
        background-color: #fff;
        border: 1px solid #dee2e6;
        color: #adb5bd;
        /* text-muted */
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.375rem;
        cursor: not-allowed;
    }
</style>

@if ($paginator->hasPages())
<div class="d-flex flex-column align-items-center mt-3 mb-3">

    <!-- Tombol Navigasi -->
    <nav class="mb-2">
        <ul class="d-flex gap-2 list-unstyled m-0 p-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
            <li>
                <span class="custom-page-disabled">&lsaquo;</span>
            </li>
            @else
            <li>
                <a href="{{ $paginator->previousPageUrl() }}" class="custom-page-link" rel="prev">&lsaquo;</a>
            </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
            <li>
                <span class="custom-page-disabled">{{ $element }}</span>
            </li>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
            @foreach ($element as $page => $url)
            @if ($page == $paginator->currentPage())
            <li>
                <span class="custom-page-active">{{ $page }}</span>
            </li>
            @else
            <li>
                <a href="{{ $url }}" class="custom-page-link">{{ $page }}</a>
            </li>
            @endif
            @endforeach
            @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
            <li>
                <a href="{{ $paginator->nextPageUrl() }}" class="custom-page-link" rel="next">&rsaquo;</a>
            </li>
            @else
            <li>
                <span class="custom-page-disabled">&rsaquo;</span>
            </li>
            @endif
        </ul>
    </nav>

    <!-- Keterangan Data -->
    <div class="text-muted mt-2" style="font-size: 0.85rem;">
        Menampilkan <span class="fw-bold text-dark">{{ $paginator->firstItem() }}</span>
        - <span class="fw-bold text-dark">{{ $paginator->lastItem() }}</span>
        dari <span class="fw-bold text-dark">{{ $paginator->total() }}</span> data
    </div>

</div>
@elseif($paginator->total() > 0)
<div class="d-flex justify-content-center mt-3 mb-3">
    <div class="text-muted" style="font-size: 0.85rem;">
        Total <span class="fw-bold text-dark">{{ $paginator->total() }}</span> data
    </div>
</div>
@endif