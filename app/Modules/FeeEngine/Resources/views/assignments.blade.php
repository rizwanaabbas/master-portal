@extends('layouts.cp')
@section('title', 'Client Fee Tier Assignments - Fee Engine')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Fee Engine</span> &mdash; Client Assignments</h1>
        <a href="{{ url('/settings/fee-engine/groups') }}" class="btn btn-primary ml-auto"><i class="fal fa-arrow-alt-left"></i> Groups</a>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>Client Fee Tier Assignments</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('feeengine::partials.flash')

                <form method="get" class="form-inline mb-3">
                    <input type="text" name="q" value="{{ $search }}" class="form-control mr-2" placeholder="Search client name">
                    <button class="btn btn-primary">Search</button>
                </form>

                <table class="table table-bordered table-hover table-striped m-0">
                    <thead class="thead-themed">
                        <tr><th>Client</th><th>Current Group</th><th style="width:45%">Assign</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($clients as $client)
                            <tr>
                                <td>{{ $client->name }} <small class="text-muted">#{{ $client->id }}</small></td>
                                <td>
                                    @if ($client->feeTierGroup)
                                        {{ $client->feeTierGroup->name }}
                                    @else
                                        <span class="text-muted">— (uses default)</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ url('/settings/fee-engine/assignments') }}" class="form-inline">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                                        <select name="fee_tier_group_id" class="form-control form-control-sm mr-2">
                                            <option value="">— Default —</option>
                                            @foreach ($groups as $g)
                                                <option value="{{ $g->id }}" {{ $client->fee_tier_group_id == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="reason" class="form-control form-control-sm mr-2" placeholder="Reason (optional)">
                                        <button class="btn btn-sm btn-primary">Assign</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-center text-muted">No clients found.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-3">{{ $clients->links() }}</div>
            </div>
        </div>
    </div>
@stop
