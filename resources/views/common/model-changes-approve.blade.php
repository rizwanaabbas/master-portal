@if (count($changes) > 0)
    <form id="changes-form" name="changes-form" method="post" action="{{ url("/changes/approve") }}">
        @csrf
        <input type="hidden" name="reject_text" id="reject_text">
        <div class="row">
        @foreach ($changes as $bulk)
            @if ($bulk)
                @foreach ($bulk as $change)
                        @php $maker = $change->maker; @endphp
                        @foreach ($change->details as $detail)
                            @php $transform = App\Helpers\ChangeLogger::transform($detail); @endphp
                            <div class="col-md-6 mb-4">
                                <div class="card border">
                                    <div class="card-header">
                                        @if ($change->status == "Pending")
                                        <div class="custom-control custom-checkbox custom-control-inline">
                                            <input type="checkbox" class="custom-control-input" value="{{ $change->id }}" id="change-{{ $detail->id }}"name="change_ids[]">
                                            <label class="custom-control-label" for="change-{{ $detail->id }}"><strong>{{ $change->title }}</strong> </label>
                                        </div>
                                        @else
                                            <label class="form-label" for="change-{{ $change->id }}"><strong>{{ $change->title }}</strong> </label>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-5 text-center">
                                                @if ($maker->profile_photo_url)
                                                    <img src="{{ $maker->profile_photo_url }}" class=" profile-image rounded-circle" alt="">
                                                @else
                                                    <img src="{{ url("assets/img/avatar.png") }}" class="profile-image rounded-circle" alt="">
                                                @endif
                                                <br><strong><small>{{ $maker->name }}</small></strong>
                                                <br><strong><small>{{ \Carbon\Carbon::parse($change->created_at)->format("d M, Y") }}</small></strong>
                                                <br>{{ \App\Helpers\Common::formatStatus($change->status) }}
                                            </div>
                                            <div class="col-md-7">
                                                <table class="table">
                                                    <tr>
                                                        <th>Field</th>
                                                        <td>{{ $detail->key }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>Old</th>
                                                        <td>{{ $detail->old_value }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th>New</th>
                                                        <td>{{ $detail->new_value }}</td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($change->status == "Rejected")
                                        <div class="card-footer">
                                            <strong>Reject Reason: </strong>{{ $change->reject_reason }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                @endforeach
            @endif

        @endforeach
        </div>
        <hr>
        <button type="submit" class="btn btn-primary">Approve Changes</button>
        <button onclick="rejectChangeModel(); return false;" class="btn btn-danger">Reject Changes</button>
    </form>
@else
    <p>There are no changes submitted yet</p>
@endif

<div class="modal fade" id="reject-change" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title ">Reject Client</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label" for="reject_reason">Reject Reason</label>
                    <textarea class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <button onclick="rejectChanges(); return false;" class="btn btn-danger">Reject</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>

        </div>
    </div>
</div>

    <script>
        function rejectChangeModel(){
            $("#reject-change").modal('show');
        }

        function rejectChanges(){
            $("#changes-form").attr("action", "/changes/reject");
            $("#reject_text").val($("#reject_reason").val());
            $("#changes-form").submit();

        }
    </script>
