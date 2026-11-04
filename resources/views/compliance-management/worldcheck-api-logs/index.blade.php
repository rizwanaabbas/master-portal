@extends('layouts.cp')
@section('title', 'World-Check API Logs')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Compliance Management</span> / World-Check API Logs
        </h1>
    </div>

    @if (!$worldCheckEnabled)
        <div class="alert alert-warning">
            World-Check module is currently disabled. Historical logs below remain available.
        </div>
    @endif

    <div class="panel">
        <div class="panel-hdr">
            <h2>API Calls</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <form method="get" action="{{ url('/dashboards/compliance-management/worldcheck-api-logs') }}" class="mb-3">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <input type="text" name="search_string" class="form-control"
                                value="{{ $request->get('search_string') }}" placeholder="Search endpoint / status / method">
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="text" name="endpoint" class="form-control"
                                value="{{ $request->get('endpoint') }}" placeholder="Endpoint prefix e.g. cases/">
                        </div>
                        <div class="col-md-2 mb-2">
                            <input type="number" name="http_status" class="form-control"
                                value="{{ $request->get('http_status') }}" placeholder="HTTP status">
                        </div>
                        <div class="col-md-2 mb-2">
                            <select name="per_page" class="form-control">
                                @foreach ([25, 50, 100] as $n)
                                    <option value="{{ $n }}" @if ((int) $request->get('per_page', 25) === $n) selected @endif>{{ $n }} / page</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1 mb-2">
                            <button class="btn btn-primary btn-block" type="submit">Filter</button>
                        </div>
                    </div>
                </form>

                <div style="overflow:auto;">
                    <table class="table table-bordered table-hover m-0">
                        <thead class="thead-themed">
                            <tr>
                                <th width="40"></th>
                                <th>When</th>
                                <th>Client</th>
                                <th>User</th>
                                <th>Method</th>
                                <th>Endpoint</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Attempt</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr>
                                    <td class="text-center">
                                        @can('view worldcheck api log detail')
                                            <a href="{{ url('/dashboards/compliance-management/worldcheck-api-logs/' . $log->id) }}"
                                                title="View"><i class="fal fa-tv"></i></a>
                                        @endcan
                                    </td>
                                    <td>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}</td>
                                    <td>
                                        @php $editUrl = $log->clientEditUrl(); @endphp
                                        @if ($editUrl)
                                            <a href="{{ $editUrl }}" target="_blank">{{ $log->displayClientName() }}</a>
                                            @if ($log->entity_label && $log->entity_label !== $log->client_name)
                                                <div class="text-muted small">{{ $log->entity_label }}</div>
                                            @endif
                                        @elseif ($log->entity_label)
                                            {{ $log->entity_label }}
                                            <div class="text-muted small">Ad-hoc screening</div>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td>{{ $log->user->name ?? '—' }}</td>
                                    <td><code>{{ $log->method }}</code></td>
                                    <td><code>{{ $log->endpoint }}</code></td>
                                    <td>
                                        @php
                                            $ok = $log->http_status >= 200 && $log->http_status < 300;
                                        @endphp
                                        <span class="badge {{ $ok ? 'badge-success' : 'badge-danger' }}">
                                            {{ $log->http_status ?? '—' }}
                                        </span>
                                    </td>
                                    <td>{{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : '—' }}</td>
                                    <td>{{ $log->attempt }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No World-Check API calls logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection
