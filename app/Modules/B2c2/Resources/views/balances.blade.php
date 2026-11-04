@extends('layouts.cp')
@section('title', 'B2c2 Balances - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span> &mdash; B2c2 Balances
        </h1>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Balances</h2>
            <div class="panel-toolbar">
                <button type="button" class="btn btn-primary btn-sm" id="sync-button" onclick="syncBalances()">
                    <i class="fal fa-sync"></i> Sync from B2c2
                </button>
            </div>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                <div id="sync-message"></div>

                <table class="table table-bordered table-hover table-striped m-0">
                    <thead class="thead-themed">
                        <tr>
                            <th>#</th>
                            <th>Currency</th>
                            <th>Name</th>
                            <th>Symbol</th>
                            <th class="text-right">Amount</th>
                            <th class="text-right">Hold</th>
                            <th class="text-right">Available</th>
                            <th>Last Synced</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($balances as $i => $balance)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $balance->currency }}</td>
                                <td>{{ optional($balance->currencyInfo)->name ?? '—' }}</td>
                                <td>{{ optional($balance->currencyInfo)->symbol ?? '—' }}</td>
                                <td class="text-right">{{ $balance->amount }}</td>
                                <td class="text-right">{{ $balance->hold }}</td>
                                <td class="text-right">{{ $balance->available }}</td>
                                <td>{{ optional($balance->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">
                                    No balances yet. Click <strong>Sync from B2c2</strong> to fetch them.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } });

        function syncBalances() {
            $('#sync-button').prop('disabled', true);
            $('#sync-message').html('').removeClass('alert alert-danger alert-success');

            $.ajax({
                url: "{{ url('/b2c2/balances/live') }}",
                method: 'GET',
                success: function (res) {
                    if (!res.success) {
                        $('#sync-message').html(res.message || 'Sync failed.').addClass('alert alert-danger');
                        $('#sync-button').prop('disabled', false);
                        return;
                    }
                    $('#sync-message').html('Balances synced successfully.').addClass('alert alert-success');
                    setTimeout(function () { window.location.reload(); }, 800);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    $('#sync-message').html(res.message || res.error || 'Sync failed.').addClass('alert alert-danger');
                    $('#sync-button').prop('disabled', false);
                }
            });
        }

        $(document).ajaxStart(function () { $('#loading-spinner').show(); })
                   .ajaxStop(function () { $('#loading-spinner').hide(); });
    </script>
@stop
