@extends('layouts.cp')
@section('title', 'Lorum Data Sync')

@section('content')

<div class="subheader">
    <h1 class="subheader-title">
        <span class='fw-300'>Lorum Data Sync</span>
    </h1>
</div>

<div class="panel">
    <div class="panel-hdr">
        <h2>Lorum Clients</h2>
    </div>

    @php
        $outOfSyncIds = collect($out_of_sync_clients)->pluck('id')->toArray();
        $notInLocalIds = collect($not_in_local_clients ?? [])->pluck('id')->toArray();
        $companyName = $ownCompany ? $ownCompany->short_name : 'our';
    @endphp

    <div class="panel-container show mb-3">
        <div class="panel-content" style="overflow: auto;">
            @include('common.error-message')

            <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                <thead class="thead-themed">
                    <tr>
                        <th width="40">Action</th>
                        <th>#</th>
                        <th>Lorum Customer ID</th>
                        <th>Reference (Local ID)</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Synced</th>
                        <th>Created At</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($lorum_clients as $index => $customer)
                        @php
                            $customerId = $customer['id'];
                            $isOutOfSync = in_array($customerId, $outOfSyncIds);
                            $isNotInLocal = in_array($customerId, $notInLocalIds);
                            $customerType = ucfirst($customer['customer_type']) ?? '-';
                        @endphp

                        <tr class="{{ $isOutOfSync ? 'table-warning' : ($isNotInLocal ? 'table-danger' : '') }}">

                            {{-- Action --}}
                            <td class="text-center">
                                {{-- @if ($isNotInLocal) --}}
                                    <a href="{{ url('/settings/lorum-sync', $customerId) }}"
                                    data-toggle="tooltip"
                                    title="View & Sync Client">
                                        <i class="fal fa-edit text-dark"></i>
                                    </a>
                                {{-- @else --}}
                                    {{-- <i class="fal fa-edit text-dark"></i> --}}
                                {{-- @endif --}}
                            </td>

                            <td>{{ $index + 1 }}</td>
                            <td>{{ $customerId }}</td>
                            <td>{{ $customer['reference'] }}</td>
                            <td>{{ $customer['display_name'] ?? '-' }}</td>

                            <td>
                                <span class="badge badge-{{$customerType === "Individual" ? 'info' : 'success'}}">
                                    {{ $customerType }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td>
                                @if ($isNotInLocal)
                                    <span 
                                        class="badge badge-danger"
                                        data-toggle="tooltip"
                                        title="Exists in lorum But not in {{$companyName}} system"
                                    >
                                        Not in {{$companyName}}
                                    </span>
                                @elseif ($isOutOfSync)
                                    <span class="badge badge-warning">Out of Sync</span>
                                @else
                                    <span class="badge badge-success">Synced</span>
                                @endif
                            </td>

                            {{-- Synced --}}
                            <td>
                                @if ($isNotInLocal)
                                    <span class="badge badge-danger">No</span>
                                @elseif ($isOutOfSync)
                                    <span class="badge badge-warning">Partial</span>
                                @else
                                    <span class="badge badge-success">Yes</span>
                                @endif
                            </td>

                            {{-- Created --}}
                            <td>
                                {{ \Carbon\Carbon::parse($customer['created_at'])->format('Y-m-d H:i') }}
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>

        </div>
    </div>
</div>

@stop

@section('scripts')
<script>
    $(document).ready(function() {
        $('#data-grid').DataTable({
            responsive: true,
            pageLength: 25
        });

        // Enable tooltip
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@stop