<div id="loading-spinner"
    style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); height: 100px;z-index:1">
    <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
    <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
        style="background: none; shape-rendering: auto;" width="50px" height="50px" viewBox="0 0 100 100"
        preserveAspectRatio="xMidYMid">
        <circle cx="50" cy="50" fill="none" stroke="#ffc800" stroke-width="13" r="38"
            stroke-dasharray="179.0707812546182 61.690260418206066">
            <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="0.625s"
                values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
        </circle>
    </svg>
</div>

<script>
    window.addEventListener('pageshow', function(event) {
        const delayInMilliseconds = 500;

        // If coming from bfcache, the persisted property will be true
        if (event.persisted) {
            // Re-run the spinner hiding logic
            setTimeout(hideSpinner, delayInMilliseconds);
        }
    });

    window.onload = function() {
        const delayInMilliseconds = 500;
        setTimeout(hideSpinner, delayInMilliseconds);
    };

    function hideSpinner() {
        document.getElementById('loading-spinner').style.display = 'none';
        // $('#loading-spinner').hide();
    }

    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('loading-spinner').style.display = '';
    });

    function showLoadingSpinner() {
        document.getElementById('loading-spinner').style.display = '';
    }
</script>
