@if (count($changes) > 0)
    <div class="row">
        @foreach ($changes as $bulk)
            @if ($bulk)
                @foreach ($bulk as $change)
                    @php $checker = $change->checker; @endphp
                    @foreach ($change->details as $detail)
                        @php $transform = App\Helpers\ChangeLogger::transform($detail); @endphp
                        <div class="col-md-6 mb-4">
                            <div class="card border mb-0">
                                <div class="card-header">
                                    <label class="form-label"
                                        for="change-{{ $change->id }}"><strong>{{ $change->title }}</strong> </label>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-5 text-center">
                                            @if ($checker && $checker->profile_photo_url)
                                                <img src="{{ $checker->profile_photo_url }}"
                                                    class=" profile-image rounded-circle" alt="">
                                            @else
                                                <img src="{{ url('assets/img/avatar.png') }}"
                                                    class="profile-image rounded-circle" alt="">
                                            @endif
                                            <br><strong><small>{{ $checker ? $checker->name : 'N/A' }}</small></strong>
                                            <br><strong><small>{{ \Carbon\Carbon::parse($change->created_at)->format('d M, Y') }}</small></strong>
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
                                @if ($change->status == 'Rejected')
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
@else
    <p>There are no changes submitted yet</p>
@endif
