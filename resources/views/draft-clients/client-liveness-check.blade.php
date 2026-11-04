@extends('layouts.public')
@section('title', 'Liveness Check')
@section('styles')
    <link rel="stylesheet" media="screen, print"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="{{ asset('css/client-on-boarding.css') }}">
@stop

@section('content')
    <div id="error-case" style="display: none">
        <div class="text">
            <h2 style="color: red;">Liveness Failed</h2>
            <h4>Not successful please repeat the proccess.</h4>
        </div>
        @if ($errors->any())
            @foreach ($errors->all() as $error)
                <div class="alert danger">{{ $error }}</div>
            @endforeach
        @endif
        <hr>

        <button type="button" onclick=reload() class="btn btn-primary">Reload <i class="fal "></i></button>
    </div>
    <div id="show-case">
        <div id="webview-container" data-base-url="{{ url('/') }}"></div>
        @if (!empty(\Illuminate\Support\Facades\Auth::user()->id) && empty($hash))
            <form method="POST" name="liveness-check" id="liveness_check"
                action="{{ url('/client-profile/liveness-check') }}">
                @csrf
                <input type="text" autofocus id="photo-url" name="photo-url" hidden>
            </form>
        @else
            <form method="POST" name="liveness-check" id="liveness_check" action="{{ url('/client-kyc/liveness-check') }}">
                @csrf
                <input type="text" autofocus id="photo-url" name="photo-url" hidden>
                <input type="text" autofocus id="hash" name="hash" value="{{ $hash }}" hidden>
            </form>
        @endif
    </div>

@stop
@section('scripts')
    <script>
        var liveness_base_url = "{{ env('AWS_AMPLIFY_LIVENESS_URL') }}"

        livnessCheck();
        const errorCase = document.getElementById('error-case');
        const showCase = document.getElementById('show-case');

        function reload() {
            errorCase.style.display = 'none';
            showCase.style.display = 'block';
            livnessCheck();
        }

        function livnessCheck() {

            // Create a webview container
            const webviewContainer = document.getElementById('webview-container');

            // Check if there is an existing iframe, and remove it if it exists
            const existingIframe = webviewContainer.querySelector('iframe');

            if (existingIframe) {
                webviewContainer.removeChild(existingIframe);
            }
            // Create a new iframe element
            const iframe = document.createElement('iframe');
            var base_url = webviewContainer.getAttribute('data-base-url');
            iframe.src = liveness_base_url + "?platform=web&baseUrl=" + base_url;
            iframe.style = "width: 100%; height: 115vh; border: none;";
            iframe.setAttribute('allow', 'camera');
            // Append the iframe to the webview container
            webviewContainer.appendChild(iframe);

            // Set up event listeners and handle WebView events
            iframe.addEventListener('load', function() {
                // Handle page load finished
                window.addEventListener('message', function(e) {
                    if (e.origin === liveness_base_url) {
                        try {
                            if (e.data.liveness_status) {
                                document.getElementById('photo-url').value = e.data.selfie_image;
                                document.getElementById('liveness_check').submit();
                            } else {
                                // iframe.src = iframe.src;
                                errorCase.style.display = 'block';
                                showCase.style.display = 'none';
                                iframe.removeEventListener('load', handleIFrameLoad);

                            }
                        } catch (error) {
                            console.error("Error :", error);
                        }
                    }
                }, false);
            });
        }
    </script>
@stop
<style>
    .container.py-4.py-lg-5.my-lg-5.px-4.px-sm-0 .row {
        display: block;
    }
</style>
