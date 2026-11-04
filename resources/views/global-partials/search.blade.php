@php
    $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
@endphp

@if ($search_url)
    <form name="search" id="search" method="post" action="{{ $search_url }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="per_page" id="per_page"
            value="{{ isset($request->per_page) ? $request->per_page : '' }}">
        <input type="hidden" name="search_string" id="search_string"
            value="{{ isset($request->search_string) ? $request->search_string : '' }}" />
    </form>
@endif

<script>
    var hiddenInput = document.getElementById('search_string')
    var search_string = "<?php echo $search_string; ?>"
    var perPage = "<?php echo $request['per_page']; ?>"
    var recordsCount = "<?php echo $count; ?>"

    perPage = perPage ? perPage : 10
    document.getElementById('per_page').value = perPage


    window.addEventListener('load', function() {
        const pageLength = document.getElementsByName('data-grid_length')[0]
        document.querySelectorAll('input[type="search"]')[0].value = search_string

        const options = Array.from(pageLength.options)
        let optionToSelect = options.find(item => item.value == perPage)
        optionToSelect.setAttribute('selected', true)

        if (perPage == -1) {
            perPage = recordsCount
        }

        $('#data-grid').DataTable().page.len(perPage).draw();

        pageLength.addEventListener('change', function() {
            document.getElementById('per_page').value = pageLength.value
            document.getElementById('search').submit()
        })

        // Search using debounce mechanism starts
        const searchInput = document.querySelectorAll('input[type="search"]')[0]

        if (search_string) {
            searchInput.value = search_string
        }

        searchInput.addEventListener('input', e => {
            hiddenInput.value = e.target.value
            updateDebounceText(e.target.value)
        })
        // Search using debounce mechanism ends
    });

    const updateDebounceText = debounce(searchString => {
        hiddenInput.value = searchString
        document.getElementById('search').submit();
    })

    function debounce(callback, delay = 1000) {
        let timeout

        return (...args) => {
            clearTimeout(timeout)
            timeout = setTimeout(() => {
                callback(...args)
            }, delay)
        }
    }
</script>
