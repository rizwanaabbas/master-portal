@auth
    @if (\App\Helpers\UserJourneyRecorder::isEnabled() && Auth::user())
        <script>
            (function () {
                var url = '{{ url('/user-trace/journey-beacon') }}';
                var token = document.querySelector('meta[name="csrf-token"]');
                token = token ? token.getAttribute('content') : '';
                var last = 0;

                function send(payload) {
                    var body = Object.assign({
                        _token: token,
                        path: location.pathname,
                        title: document.title
                    }, payload);
                    try {
                        fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify(body)
                        });
                    } catch (e) {}
                }

                document.addEventListener('click', function (event) {
                    var now = Date.now();
                    if (now - last < 400) return;
                    last = now;
                    var w = window.innerWidth || 1;
                    var h = window.innerHeight || 1;
                    send({
                        event: 'click',
                        x: Math.round((event.clientX / w) * 100),
                        y: Math.round((event.clientY / h) * 100)
                    });
                }, { passive: true });
            })();
        </script>
    @endif
@endauth
