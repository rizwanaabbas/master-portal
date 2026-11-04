@extends('layouts.cp')
@section('title', 'Queue Management - System Settings')

@section('content')
<div class="subheader">
    <h1 class="subheader-title"><span class="fw-300">System Settings</span> <i class="fal fa-angle-right"></i> Queue Management</h1>
</div>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="p-3 bg-primary-100 rounded">
            <div class="text-muted">Queue driver</div>
            <div class="h4 mb-0"><code>{{ $queueDriver }}</code></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-info-100 rounded">
            <div class="text-muted">Jobs table</div>
            <div class="h4 mb-0">{{ $hasJobsTable ? 'Available' : 'Not available' }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-warning-100 rounded">
            <div class="text-muted">Failed jobs table</div>
            <div class="h4 mb-0">{{ $hasFailedJobsTable ? 'Available' : 'Not available' }}</div>
        </div>
    </div>
</div>

@if ($queueDriver === 'sync')
    <div class="alert alert-warning">
        <i class="fal fa-info-circle"></i> Queue driver is <strong>sync</strong> — jobs run immediately and will not appear in the pending queue. Set <code>QUEUE_CONNECTION=database</code> (or redis) to use background queues.
    </div>
@endif

<div class="panel mb-4">
    <div class="panel-hdr">
        <h2>Pending Jobs</h2>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="table-responsive">
                <table class="table table-bordered table-hover m-0" id="pending-jobs-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th>ID</th>
                            <th>Job</th>
                            <th>Queue</th>
                            <th>Status</th>
                            <th>Attempts</th>
                            <th>Available At</th>
                            <th>Created</th>
                            <th width="80">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="panel">
    <div class="panel-hdr">
        <h2>Failed Jobs</h2>
        <div class="panel-toolbar">
            <button type="button" class="btn btn-sm btn-outline-success" id="btn-retry-all-failed">
                <i class="fal fa-redo"></i> Retry All
            </button>
        </div>
    </div>
    <div class="panel-container show">
        <div class="panel-content">
            <div class="table-responsive">
                <table class="table table-bordered table-hover m-0" id="failed-jobs-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th>ID</th>
                            <th>UUID</th>
                            <th>Job</th>
                            <th>Queue</th>
                            <th>Exception</th>
                            <th>Failed At</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="failed-job-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Failed Job Details</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="failed-job-body"></div>
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
    const baseUrl = '{{ url('/settings/queue-management') }}';

    $('#pending-jobs-grid').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: baseUrl + '/pending-data',
        columns: [
            { data: 'id' },
            { data: 'job_name', orderable: false },
            { data: 'queue' },
            { data: 'status', orderable: false, searchable: false },
            { data: 'attempts' },
            { data: 'available_at_fmt', orderable: false },
            { data: 'created_at_fmt', orderable: false },
            { data: 'actions', orderable: false, searchable: false },
        ]
    });

    const failedTable = $('#failed-jobs-grid').DataTable({
        processing: true,
        serverSide: true,
        order: [[0, 'desc']],
        ajax: baseUrl + '/failed-data',
        columns: [
            { data: 'id' },
            { data: 'uuid' },
            { data: 'job_name', orderable: false },
            { data: 'queue' },
            { data: 'exception_short', orderable: false },
            { data: 'failed_at_fmt' },
            { data: 'actions', orderable: false, searchable: false },
        ]
    });

    $(document).on('click', '.btn-delete-job', function () {
        if (!confirm('Remove this job from the queue?')) return;
        $.post(baseUrl + '/delete-pending', { _token: csrf, id: $(this).data('id') }).done(function (res) {
            toastr[res.success ? 'success' : 'error'](res.message);
            $('#pending-jobs-grid').DataTable().ajax.reload(null, false);
        });
    });

    $(document).on('click', '.btn-retry-failed', function () {
        $.post(baseUrl + '/retry-failed', { _token: csrf, uuid: $(this).data('uuid') }).done(function (res) {
            toastr[res.success ? 'success' : 'error'](res.message);
            failedTable.ajax.reload(null, false);
            $('#pending-jobs-grid').DataTable().ajax.reload(null, false);
        });
    });

    $(document).on('click', '.btn-forget-failed', function () {
        if (!confirm('Remove this failed job record?')) return;
        $.post(baseUrl + '/forget-failed', { _token: csrf, uuid: $(this).data('uuid') }).done(function (res) {
            toastr[res.success ? 'success' : 'error'](res.message);
            failedTable.ajax.reload(null, false);
        });
    });

    $('#btn-retry-all-failed').on('click', function () {
        if (!confirm('Retry all failed jobs?')) return;
        $.post(baseUrl + '/retry-all-failed', { _token: csrf }).done(function (res) {
            toastr[res.success ? 'success' : 'error'](res.message);
            failedTable.ajax.reload(null, false);
        });
    });

    $(document).on('click', '.btn-view-failed', function () {
        $.get(baseUrl + '/failed/' + $(this).data('id')).done(function (res) {
            if (!res.success) return;
            let html = '<p><strong>Job:</strong> ' + res.job.job_name + '</p>';
            html += '<p><strong>Queue:</strong> ' + res.job.queue + '</p>';
            html += '<p><strong>Failed at:</strong> ' + res.job.failed_at + '</p>';
            html += '<h6>Exception</h6><pre style="white-space:pre-wrap;font-size:12px;">' + res.job.exception + '</pre>';
            $('#failed-job-body').html(html);
            $('#failed-job-modal').modal('show');
        });
    });
});
</script>
@endsection
