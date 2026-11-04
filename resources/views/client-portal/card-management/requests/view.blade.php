@extends('layouts.cp')
@section('title', 'Card Unblock Pending Request - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        <a href="{{ url('/client/card-management/unblock-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
        @if ($request->status == 'Rejected')
            <a href="#" onclick="unblockModal()" class="btn btn-primary mr-3">
                <i class="fal fa-arrow-alt-right"></i> Request Unblock Again
            </a>
        @endif

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Card Unblock Pending Request
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-dark">
                        <tr>
                            <th width="200">Type</th>
                            <td>
                                {{ $request->type }}
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><span class="badge {{ $statusBadge[$request->status] }}">{{ $request->status }}</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Request Reason</th>
                            <td>{{ $request->request_reason }}</td>
                        </tr>
                        <tr>
                            <th>Request Date</th>
                            <td>{{ \Carbon\Carbon::parse($request->created_at)->format('d/m/Y') }}</td>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    @if ($request->status == 'Rejected')
        <div class="modal fade" id="unblock_modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="unblock_modal_title">Unblock Request</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/client/card-management/card/unblock') }}" method="post">
                            @csrf
                            <input type="hidden" name="request_id" value="{{ $request->id }}">
                            <input type="hidden" name="card_id" value="{{ $linkedCardId }}">
                            <input type="hidden" name="updatable" value="Unblock">
                            <div class="form-group">
                                <label class="form-label" for="request_reason">Reason</label>
                                <textarea required class="form-control" name="request_reason" id="request_reason" cols="30" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" id="unblock_modal_submit" class="btn btn-danger">Unblock</button>
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    @endif

@stop
@section('scripts')
    <script>
        function unblockModal() {
            $("#unblock_modal").modal('show');
        }
    </script>
@stop
