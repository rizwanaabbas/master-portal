@extends('layouts.cp')
@section('title', 'Audit Details - User Trace')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>User Trace</span>
        </h1>
        <a href="{{ url('user-trace/user-audits') }}" class="btn btn-primary mr-3"><i class="fal fa-arrow-alt-left"></i> Go
            Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Audit Details
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <tr>
                        <th width="200">User Name</th>
                        <td>{{ $audit->user ? $audit->user->name : 'Guest' }}</td>
                    </tr>
                    <tr>
                        <th>User Role(s)</th>
                        <td>
                            @if ($audit->user)
                                @foreach ($audit->user->roles->pluck('name')->toArray() as $roleName)
                                    <span class="badge badge-warning">{{ $roleName }}</span>
                                @endforeach
                            @else
                                --
                            @endif
                        </td>
                    </tr>
                    @php
                        $data = $audit->event != 'created' ? $audit->old_values : $audit->new_values;
                        $modelMapping = [
                            'App\\Models\\Account' => 'Account',
                            'App\\Models\\AccountTransaction' => 'Transaction',
                            'App\\Models\\AccountTransactionCheque' => 'Cheque Transaction',
                            'App\\Models\\Bank' => 'Bank',
                            'App\\Models\\Client' => 'Client',
                            'App\\Models\\ClientCompany' => 'Company Client',
                            'App\\Models\\ClientIndividual' => 'Individual Client',
                        ];

                        $auditableEntity = app()->make($audit->auditable_type);
                    @endphp
                    <tr>
                        <th>Action</th>
                        <td><span
                                class="badge {{ $statusBadge[$audit->event] }}">{{ $modelMapping[$audit->auditable_type] . ' ' . $audit->event }}</span>
                        </td>
                    </tr>

                    <tr>
                        <th>Data</th>
                        <td>
                            <table width="100%">
                                <tr>
                                    <td><b>Label</b></td>
                                    @if ($audit->event != 'created')
                                        <td><b>Old Value(s)</b></td>
                                    @endif

                                    @if ($audit->event != 'deleted')
                                        <td><b>New Value(s)</b></td>
                                    @endif
                                </tr>
                                @foreach ($data as $key => $value)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                                        @if ($audit->event != 'created')
                                            <td>{{ $audit->old_values[$key] }}</td>
                                        @endif

                                        @if ($audit->event != 'deleted')
                                            <td>{{ $audit->new_values[$key] }}
                                        @endif
                                    </tr>
                                @endforeach
                            </table>
                        </td>

                    </tr>

                    <tr>
                        <th>Auditable ID</th>
                        <td>{{ $audit->auditable_id }}</td>
                    </tr>

                    <tr>
                        <th>Auditable Entity Name</th>
                        <td>
                            {{ $modelMapping[$audit->auditable_type] == 'Client' ? $auditableEntity::find($audit->auditable_id)->name : '--' }}
                        </td>
                    </tr>

                    <tr>
                        <th>URL</th>
                        <td>{{ $audit->url }}</td>
                    </tr>
                    <tr>
                        <th>IP Address</th>
                        <td>{{ $audit->ip_address }}</td>
                    </tr>
                    <tr>
                        <th>User Agent</th>
                        <td>{{ $audit->user_agent }}</td>
                    </tr>
                    <tr>
                        <th>Audit Date & Time</th>
                        <td>{{ \Carbon\Carbon::parse($audit->created_at)->format('d M, Y H:i:s') }}</td>
                    </tr>
                </table>
                <hr>
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

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });
        });
    </script>
@stop
