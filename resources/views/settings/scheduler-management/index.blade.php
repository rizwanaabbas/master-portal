@extends('layouts.cp')
@section('title', 'Scheduler Management - System Settings')

@section('content')
<div class="subheader">
    <h1 class="subheader-title"><span class="fw-300">System Settings</span> <i class="fal fa-angle-right"></i> Cron / Scheduler</h1>
</div>

<div class="alert alert-info">
    <i class="fal fa-clock"></i> Environment: <strong>{{ $environment }}</strong>.
    Scheduled tasks below reflect <code>app/Console/Kernel.php</code> for the current environment.
    Ensure server cron runs: <code>* * * * * php artisan schedule:run</code>
</div>

<div class="panel mb-4">
    <div class="panel-hdr"><h2>Registered Scheduled Tasks</h2></div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="thead-themed">
                        <tr>
                            <th>Command</th>
                            <th>Description</th>
                            <th>Schedule</th>
                            <th>Module</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tasks as $task)
                            <tr>
                                <td><code>{{ $task['command'] }}</code></td>
                                <td>{{ $task['description'] }}</td>
                                <td>{{ $task['schedule'] }}</td>
                                <td>{{ $task['module'] ?: '—' }}</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-run-scheduled" data-command="{{ $task['command'] }}">
                                        <i class="fal fa-play"></i> Run now
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted">No scheduled tasks for this environment.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-hdr"><h2>Execution Logs</h2></div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select id="filter-log-status" class="form-control form-control-sm">
                        <option value="all">All</option>
                        <option value="running">Running</option>
                        <option value="success">Success</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover m-0" id="scheduler-logs-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th>Command</th>
                            <th>Status</th>
                            <th>Started</th>
                            <th>Finished</th>
                            <th>Duration</th>
                            <th>Error</th>
                            <th width="60">View</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="scheduler-log-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Execution Log</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="scheduler-log-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="run-command-modal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Manual Command Run</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="run-command-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const csrf = '{{ csrf_token() }}';
    const baseUrl = '{{ url('/settings/scheduler-management') }}';

    const logsTable = $('#scheduler-logs-grid').DataTable({
        processing: true,
        serverSide: true,
        order: [[2, 'desc']],
        ajax: {
            url: baseUrl + '/logs-data',
            data: function (d) {
                d.status = $('#filter-log-status').val();
            }
        },
        columns: [
            { data: 'command' },
            { data: 'status_badge', orderable: false, searchable: false },
            { data: 'started_at_fmt' },
            { data: 'finished_at_fmt' },
            { data: 'duration_fmt', searchable: false },
            { data: 'error_short', orderable: false },
            { data: 'actions', orderable: false, searchable: false },
        ]
    });

    $('#filter-log-status').on('change', () => logsTable.ajax.reload());

    $(document).on('click', '.btn-run-scheduled', function () {
        const command = $(this).data('command');
        if (!confirm('Run "' + command + '" now?')) return;

        $.post(baseUrl + '/run-now', { _token: csrf, command: command }).done(function (res) {
            let html = '<div class="alert ' + (res.success ? 'alert-success' : 'alert-danger') + '">' + (res.message || 'Done') + '</div>';
            if (res.output) {
                html += '<pre style="white-space:pre-wrap;font-size:12px;">' + res.output + '</pre>';
            }
            $('#run-command-body').html(html);
            $('#run-command-modal').modal('show');
            logsTable.ajax.reload(null, false);
        }).fail(function (xhr) {
            $('#run-command-body').html('<div class="alert alert-danger">' + (xhr.responseJSON?.message || 'Failed') + '</div>');
            $('#run-command-modal').modal('show');
        });
    });

    $(document).on('click', '.btn-view-log', function () {
        $.get(baseUrl + '/logs/' + $(this).data('id')).done(function (res) {
            if (!res.success) return;
            const log = res.log;
            let html = '<p><strong>Command:</strong> ' + log.command + '</p>';
            html += '<p><strong>Status:</strong> ' + log.status + '</p>';
            html += '<p><strong>Started:</strong> ' + (log.started_at || '—') + '</p>';
            html += '<p><strong>Finished:</strong> ' + (log.finished_at || '—') + '</p>';
            if (log.error_message) {
                html += '<h6>Error</h6><pre style="white-space:pre-wrap;font-size:12px;">' + log.error_message + '</pre>';
            }
            if (log.output) {
                html += '<h6>Output</h6><pre style="white-space:pre-wrap;font-size:12px;">' + log.output + '</pre>';
            }
            $('#scheduler-log-body').html(html);
            $('#scheduler-log-modal').modal('show');
        });
    });
});
</script>
@endsection
