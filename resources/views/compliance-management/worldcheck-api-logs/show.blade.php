@extends('layouts.cp')
@section('title', 'World-Check API Log #' . $log->id)

@section('content')
    <div class="subheader">
        <h1 class="subheader-title">
            <span class="fw-300">Compliance Management</span> / World-Check API Log #{{ $log->id }}
        </h1>
        <a href="{{ url('/dashboards/compliance-management/worldcheck-api-logs') }}" class="btn btn-secondary">Back to list</a>
    </div>

    <div class="panel mb-3">
        <div class="panel-hdr">
            <h2>Summary</h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <table class="table table-bordered m-0">
                    <tr>
                        <th width="200">When</th>
                        <td>{{ $log->created_at ? $log->created_at->format('d/m/Y H:i:s') : '—' }}</td>
                    </tr>
                    <tr>
                        <th>Client</th>
                        <td>
                            @php $editUrl = $log->clientEditUrl(); @endphp
                            @if ($editUrl)
                                <a href="{{ $editUrl }}" target="_blank">{{ $log->displayClientName() }}</a>
                                <span class="text-muted">(ID {{ $log->client_id }} · {{ $log->client_type }})</span>
                            @elseif ($log->entity_label)
                                {{ $log->entity_label }}
                                <span class="text-muted">(ad-hoc screening)</span>
                            @else
                                —
                            @endif
                            @if ($log->entity_label && $log->client_name && $log->entity_label !== $log->client_name)
                                <div class="text-muted small mt-1">Screened entity: {{ $log->entity_label }}</div>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>User</th>
                        <td>{{ $log->user->name ?? '—' }} @if ($log->user)<span class="text-muted">({{ $log->user->email }})</span>@endif</td>
                    </tr>
                    <tr>
                        <th>Method</th>
                        <td><code>{{ $log->method }}</code></td>
                    </tr>
                    <tr>
                        <th>Endpoint</th>
                        <td><code>{{ $log->endpoint }}</code></td>
                    </tr>
                    <tr>
                        <th>URL</th>
                        <td><code>{{ $log->url }}</code></td>
                    </tr>
                    <tr>
                        <th>HTTP Status</th>
                        <td>{{ $log->http_status ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Duration</th>
                        <td>{{ $log->duration_ms !== null ? $log->duration_ms . ' ms' : '—' }}</td>
                    </tr>
                    <tr>
                        <th>Attempt</th>
                        <td>{{ $log->attempt }}</td>
                    </tr>
                    @if ($log->error_message)
                        <tr>
                            <th>Error</th>
                            <td class="text-danger">{{ $log->error_message }}</td>
                        </tr>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-3">
            <div class="panel h-100">
                <div class="panel-hdr">
                    <h2>Request</h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <h5>Headers</h5>
                        <pre class="bg-faded p-3" style="max-height:240px;overflow:auto;white-space:pre-wrap;">{{ json_encode(json_decode($log->request_headers, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        <h5 class="mt-3">Payload</h5>
                        <pre class="bg-faded p-3" style="max-height:420px;overflow:auto;white-space:pre-wrap;">{{ is_array($log->decoded_request_payload) ? json_encode($log->decoded_request_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ($log->request_payload ?: '—') }}</pre>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6 mb-3">
            <div class="panel h-100">
                <div class="panel-hdr">
                    <h2>Response</h2>
                </div>
                <div class="panel-container show">
                    <div class="panel-content">
                        <h5>Headers</h5>
                        <pre class="bg-faded p-3" style="max-height:240px;overflow:auto;white-space:pre-wrap;">{{ json_encode(json_decode($log->response_headers, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        <h5 class="mt-3">Body</h5>
                        <pre class="bg-faded p-3" style="max-height:420px;overflow:auto;white-space:pre-wrap;">{{ is_array($log->decoded_response_body) ? json_encode($log->decoded_response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : ($log->response_body ?: '—') }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
