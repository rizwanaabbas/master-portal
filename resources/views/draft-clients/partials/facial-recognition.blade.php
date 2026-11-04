<div class="main">
    <div id="error-case" style="display: none">
        <div class="text">
            <h2 style="color: red;">Liveness Failed</h2>
            <h4>Not successful please repeat the proccess.</h4>
        </div>
        <hr>

        <button type="button" class="btn btn-primary reload_button">Reload <i class="fal "></i></button>
    </div>
    <div id="show-case">
        <div id="webview-container" data-base-url="{{ url('/') }}"></div>
        <input type="text" autofocus id="photo-url" name="photo-url" hidden>
        <div class="form-group">
            <hr>
            <button type="button" class="btn btn-outline-secondary back_button"><i class="fal fa-arrow-left"></i>
                Back</button>
        </div>
    </div>

</div>
