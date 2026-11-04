@extends('layouts.cp')
@section('title', 'Fee Tier Groups - Fee Engine')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Fee Engine</span> &mdash; Fee Tier Groups</h1>
        <div class="ml-auto">
            <a href="{{ url('/settings/fee-engine/assignments') }}" class="btn btn-outline-primary mr-2">Client Assignments</a>
            <a href="{{ url('/settings/fee-engine/charges') }}" class="btn btn-outline-primary mr-2">Charge Log</a>
            <a href="{{ url('/settings/fee-engine/groups/new') }}" class="btn btn-primary"><i class="fal fa-plus-circle"></i> New Group</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>Fee Tier Groups</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('feeengine::partials.flash')

                <table class="table table-bordered table-hover table-striped m-0">
                    <thead class="thead-themed">
                        <tr>
                            <th>#</th><th>Name</th><th>Code</th><th>Rules</th>
                            <th>Default</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($groups as $i => $group)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $group->name }}</td>
                                <td><code>{{ $group->code }}</code></td>
                                <td>{{ $group->currency_fee_tiers_count }}</td>
                                <td>
                                    @if ($group->is_default)
                                        <span class="badge badge-info">Default</span>
                                    @else — @endif
                                </td>
                                <td>
                                    <span class="badge {{ $group->is_active ? 'badge-success' : 'badge-secondary' }}">
                                        {{ $group->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ url('/settings/fee-engine/groups/' . $group->id . '/edit') }}"
                                        class="btn btn-sm btn-primary"><i class="fal fa-edit"></i> Edit</a>
                                    <form method="post" action="{{ url('/settings/fee-engine/groups/delete') }}"
                                        class="d-inline" onsubmit="return confirm('Delete this group?');">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $group->id }}">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fal fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted">No fee tier groups yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@stop
