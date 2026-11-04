@extends('layouts.cp')
@section('title', 'Active Users List - User Trace')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Trace</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Active Users List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th>
                                Name
                            </th>
                            <th>
                                User Role(s)
                            </th>
                            <th>
                                Current URL
                            </th>
                            <th>
                                Last Activity
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($activeUsers)
                            @foreach ($activeUsers as $activeUser)
                                @if (!$activeUser->user)
                                    @continue;
                                @endif
                                <tr>
                                    <td>
                                        {{ $activeUser->user->name }}
                                    </td>
                                    <td>
                                        @foreach ($activeUser->user->roles->pluck('name')->toArray() as $roleName)
                                            <span class="badge badge-warning">{{ $roleName }}</span>
                                        @endforeach
                                    </td>
                                    <td>
                                        @php
                                            $payload = unserialize(base64_decode($activeUser->payload));
                                        @endphp
                                        {{ isset($payload['_previous']) ? $payload['_previous']['url'] : '--' }}
                                    </td>
                                    <td>
                                        {{ \Carbon\Carbon::parse($activeUser->last_activity)->format('d M, Y H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td>No Active Users.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });
        });
    </script>
@stop
