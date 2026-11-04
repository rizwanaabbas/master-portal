{{-- @if ($changes && $changes->count() > 0) --}}
    <div class="panel-content position-relative" style="overflow: auto;">
        <form id="changes-form" name="changes-form" method="post" action="{{ url('/changes/approve') }}">
            @csrf
            <input type="hidden" name="reject_text" id="reject_text">

            <div class="d-flex justify-content-end align-items-center mb-3 sticky-top bg-white">
                <button type="submit" class="btn btn-primary mr-2">
                    Approve Changes
                </button>
                <button onclick="rejectChangeModel(); return false;" class="btn btn-danger">
                    Reject Changes
                </button>
            </div>

            <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                <thead class="thead-themed">
                    <tr>
                        <th width="30"></th>
                        <th>User</th>
                        <th>Type</th>
                        <th>Old</th>
                        <th>New</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($changes as $change)
                        @php $maker = $change->maker; @endphp
                        @foreach ($change->details as $detail)
                            @php $transform = App\Helpers\ChangeLogger::transform($detail); @endphp
                            <tr>
                                <td align="center" scope="row">
                                    @if ($change->status == 'Pending')
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" value="{{ $change->id }}"
                                                id="change-{{ $detail->id }}" name="change_ids[]">
                                        </div>
                                    @endif
                                </td>

                                <td class="d-flex flex-row mb-3 align-items-center">
                                    @if ($maker && $maker->profile_photo_url)
                                        <img src="{{ $maker->profile_photo_url }}" class="rounded-circle mr-2"
                                            width="25" height="25" alt="profile-pic">
                                    @else
                                        <img src="{{ url('assets/img/avatar.png') }}" class="rounded-circle mr-2"
                                            width="25" height="25" alt="profile-pic">
                                    @endif
                                    <div>{{ $maker->name }}</div>
                                </td>

                                <td><strong>{{ $change->title }}</strong></td>
                                <td class="col-md-3">{{ $detail->old_value }}</td>
                                <td class="col-md-3">{{ $detail->new_value }}</td>
                                <td>{{ \Carbon\Carbon::parse($change->created_at)->format('d M, Y') }}</td>
                                <td>
                                    {{ \App\Helpers\Common::formatStatus($change->status) }}
                                    @if ($change->status == 'Rejected')
                                        <div class="mt-1">
                                            <strong>Reason:</strong> {{ $change->reject_reason }}
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </form>
    </div>
{{-- @else
    <p>There are no changes submitted yet.</p>
@endif --}}

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
                    <textarea class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2" required></textarea>
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
    function rejectChangeModel() {
        $("#reject-change").modal('show');
    }

    function rejectChanges() {
        var reason = $("#reject_reason").val().trim();
        if (!reason) {
            // show inline validation message and prevent submit
            $("#reject_reason").addClass("is-invalid");
            if ($("#reject_reason").next(".invalid-feedback").length === 0) {
                $("#reject_reason").after('<div class="invalid-feedback">Please enter a reason for rejection.</div>');
            }
            $("#reject_reason").focus();
            return;
        }
        
        $("#reject_reason").removeClass("is-invalid");
        $("#reject_reason").next(".invalid-feedback").remove();
        $("#reject_text").val(reason);
        $("#changes-form").attr("action", "/changes/reject");
        $("#changes-form").submit();

    }
</script>

<style>
    .profile-image {
        width: 20px;
        height: 20px;
        object-fit: contain;
        border-radius: 50%;
    }
</style>
