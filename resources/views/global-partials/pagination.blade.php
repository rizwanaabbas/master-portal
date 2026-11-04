<div class="row d-flex justify-content-between">
    <div class="col-md-5">
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
             
                <p class="text-sm text-gray-700 leading-5">
                Showing
                <span class="font-medium">
                    {{ isset($perPage, $currentPage) ? ($perPage * ($currentPage - 1) + 1) : '' }}
                </span>
                to
                <span class="font-medium">
                    {{ isset($perPage, $currentPage, $total) ? min($perPage * $currentPage, $total) : '' }}
                </span>
                of
                <span class="font-medium">{{ $total ?? '' }}</span>
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
                                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                                    <i class="fal fa-chevron-left"></i>
                                </a>
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
                                            <a class="page-link" href="{{ $url }}">{{ $page }}</a>
                                        </li>
                                    @endif
                                @endforeach
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($paginator->hasMorePages())
                            <li class="paginate_button page-item next">
                                <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                                    <i class="fal fa-chevron-right"></i>
                                </a>
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
