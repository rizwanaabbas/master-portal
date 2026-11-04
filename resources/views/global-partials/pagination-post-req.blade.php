<div class="row d-flex justify-content-between">
    <div class="col-md-5">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700 leading-5">
                    Showing
                    <span class="font-medium">{{ $perPage * $currentPage - ($perPage - 1) }}</span>
                    to
                    <span class="font-medium">{{ $perPage * $currentPage }}</span>
                    of
                    <span class="font-medium">{{ $total }}</span>
                    results
                </p>
            </div>


        </div>
    </div>

    <div class="col-md-7 d-flex justify-content-end">
        @if ($paginator->hasPages())
            <div class="dataTables_wrapper dt-bootstrap4">
                <nav class="dataTables_paginate paging_simple_numbers">
                    <ul class="pagination">
                        {{-- Previous Page Link --}}
                        @if ($paginator->onFirstPage())
                            <li class="paginate_button page-item previous disabled">
                                <a class="page-link" href="#">
                                    <i class="fal fa-chevron-left"></i>
                                </a>
                            </li>
                        @else
                            <li class="paginate_button page-item previous">
                                <button class="page-link" rel="prev"
                                    onclick="searchWithQueryString(false, '{{ $paginator->previousPageUrl() }}')">
                                    <i class="fal fa-chevron-left"></i>
                                </button>
                            </li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($elements as $element)
                            {{-- "Three Dots" Separator --}}
                            @if (is_string($element))
                                <li class="paginate_button page-item disabled"><span>{{ $element }}</span></li>
                            @endif

                            {{-- Array Of Links --}}
                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    @if ($page == $paginator->currentPage())
                                        <li class="paginate_button page-item active">
                                            <a class="page-link" href="#">{{ $page }}</a>
                                        </li>
                                    @else
                                        <li class="paginate_button page-item">
                                            <button class="page-link text-dark" style="display: inline;"
                                                onclick="searchWithQueryString({{ $page }}, false)">
                                                {{ $page }}
                                            </button>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <li class="paginate_button page-item next">
                                <button class="page-link" rel="next"
                                    onclick="searchWithQueryString(false, '{{ $paginator->nextPageUrl() }}')">
                                    <i class="fal fa-chevron-right"></i>
                                </button>
                            </li>
                        @else
                            <li class="paginate_button page-item next disabled">
                                <a class="page-link" href="#">
                                    <i class="fal fa-chevron-right"></i>
                                </a>
                            </li>
                        @endif
                    </ul>
                </nav>
            </div>
        @endif
    </div>
</div>

<form method="POST" action="" id="search_with_query">
    @csrf
    @foreach ($hiddenInputs as $key => $value)
        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
    @endforeach
</form>

<script>
    function searchWithQueryString(pageNumber, pageURL) {
        searchWithQSForm = document.getElementById('search_with_query')

        if (!pageURL) {
            action = document.getElementById('search').action
            // searchWithQSForm.action = searchWithQSForm.action + "?page=" + pageNumber
            searchWithQSForm.action = action + "?page=" + pageNumber
        } else {
            pageURL = new URL(pageURL)
            fullURL = "{{ url('/') }}" + pageURL.pathname + pageURL.search
            searchWithQSForm.action = fullURL
        }

        searchWithQSForm.submit()
    }
</script>
