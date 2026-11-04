@extends('layouts.cp')
@section('title', 'Aquanow Instruments - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span> &mdash; Aquanow Instruments
        </h1>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Instruments</h2>
            <div class="panel-toolbar">
                <button type="button" class="btn btn-primary btn-sm" id="sync-button" onclick="syncInstruments()">
                    <i class="fal fa-sync"></i> Sync from Aquanow
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
                            <th>Instrument</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>From Currency</th>
                            <th>To Currency</th>
                            <th class="text-right">Min Trade</th>
                            <th class="text-right">Max Trade</th>
                            <th class="text-right">Price Sig. Digits</th>
                            <th>Status</th>
                            <th>Action</th>
                            <th>Last Synced</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($instruments as $i => $instrument)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $instrument->instrument }}</td>
                                <td>{{ $instrument->name ?? '—' }}</td>
                                <td>{{ $instrument->instrument_type ?? '—' }}</td>
                                <td>
                                    @if ($instrument->fromCurrency)
                                        {{ $instrument->fromCurrency->name }} ({{ $instrument->fromCurrency->symbol ?? $instrument->fromCurrency->short }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($instrument->toCurrency)
                                        {{ $instrument->toCurrency->name }} ({{ $instrument->toCurrency->symbol ?? $instrument->toCurrency->short }})
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-right">{{ $instrument->min_quantity ?? '—' }}</td>
                                <td class="text-right">{{ $instrument->max_quantity ?? '—' }}</td>
                                <td class="text-right">{{ $instrument->price_significant_digits ?? '—' }}</td>
                                <td id="status-{{ $instrument->id }}">
                                    <span class="badge {{ $instrument->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $instrument->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <button type="button"
                                        class="btn btn-sm {{ $instrument->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}"
                                        id="toggle-{{ $instrument->id }}"
                                        onclick="toggleStatus({{ $instrument->id }})">
                                        {{ $instrument->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </td>
                                <td>{{ optional($instrument->updated_at)->format('Y-m-d H:i') ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted">
                                    No instruments yet. Click <strong>Sync from Aquanow</strong> to fetch them.
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

        function syncInstruments() {
            $('#sync-button').prop('disabled', true);
            $('#sync-message').html('').removeClass('alert alert-danger alert-success');

            $.ajax({
                url: "{{ url('/aquanow/instruments/live') }}",
                method: 'GET',
                success: function (res) {
                    if (!res.success) {
                        $('#sync-message').html(res.message || 'Sync failed.').addClass('alert alert-danger');
                        $('#sync-button').prop('disabled', false);
                        return;
                    }
                    $('#sync-message').html('Instruments synced successfully.').addClass('alert alert-success');
                    setTimeout(function () { window.location.reload(); }, 800);
                },
                error: function (xhr) {
                    const res = xhr.responseJSON || {};
                    $('#sync-message').html(res.message || res.error || 'Sync failed.').addClass('alert alert-danger');
                    $('#sync-button').prop('disabled', false);
                }
            });
        }

        // Enable / disable an instrument. This status is user-controlled and
        // is never overwritten by the API sync.
        function toggleStatus(id) {
            const $btn = $('#toggle-' + id);
            $btn.prop('disabled', true);

            $.ajax({
                url: "{{ url('/aquanow/instruments') }}/" + id + "/toggle-status",
                method: 'POST',
                success: function (res) {
                    if (!res.success) {
                        $btn.prop('disabled', false);
                        return;
                    }
                    if (res.is_active) {
                        $('#status-' + id).html('<span class="badge badge-success">Active</span>');
                        $btn.removeClass('btn-outline-success').addClass('btn-outline-danger').text('Disable');
                    } else {
                        $('#status-' + id).html('<span class="badge badge-secondary">Inactive</span>');
                        $btn.removeClass('btn-outline-danger').addClass('btn-outline-success').text('Enable');
                    }
                    $btn.prop('disabled', false);
                },
                error: function () {
                    $btn.prop('disabled', false);
                }
            });
        }

        $(document).ajaxStart(function () { $('#loading-spinner').show(); })
                   .ajaxStop(function () { $('#loading-spinner').hide(); });
    </script>
@stop
