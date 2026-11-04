@extends('layouts.cp')
@section('title', 'Client Pending Request - Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Card Management</span>
        </h1>
        <a href="{{ url('/card-management/card/unblock-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Pending Request
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
                <hr>
                @if ($request->status == 'Pending')
                    <div class="row">
                        <div class="col-md-12">
                            @if ($request->status != 'Approved')
                                <form id="approval-form" method="post"
                                    action="{{ url('/card-management/card/unblock-requests/approve') }}">
                                    @csrf
                                    <input type="hidden" id="id" name="id" value="{{ $request->id }}">
                                </form>

                                <button type="button" onclick="approve();" class="btn btn-success">Approve</button>

                                @if ($request->status != 'Rejected')
                                    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header bg-primary text-white">
                                                    <h5 class="modal-title ">Reject Request</h5>
                                                    <button type="button" class="close" data-dismiss="modal"
                                                        aria-label="Close">
                                                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form
                                                        action="{{ url('/card-management/card/unblock-requests/reject') }}"
                                                        method="post">
                                                        @csrf
                                                        <input type="hidden" name="id" id="id"
                                                            value="{{ $request->id }}">
                                                        <div class="form-group">
                                                            <label class="form-label" for="reject_reason">Reject
                                                                Reason</label>
                                                            <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                                                        </div>

                                                        <div class="form-group">
                                                            <button type="submit" class="btn btn-danger">Reject</button>
                                                            <button type="button" class="btn btn-secondary"
                                                                data-dismiss="modal">Close</button>
                                                        </div>
                                                    </form>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <button onclick="rejectRequest(); return false;" class="btn btn-danger">Reject</button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>


@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function approve() {

            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be approved",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approval-form').submit()
                }
            });
        }

        function rejectRequest() {
            $("#reject-modal").modal('show');
        }
    </script>
@stop
