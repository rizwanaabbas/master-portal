@extends('layouts.cp')
@section('title', 'Topup Requests List - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        <a href="{{ url('/client/card-management/card/topups/new/' . $card_id) }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Topup Request </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Topup Requests List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <div class="d-flex justify-content-center">
                    <h4 style="margin-top: 10px;">Current Card..</h4>
                    <div class="col-sm-12 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <div class="form-group">
                            <select onchange="getCardTopups(this.value);" name="card_id" autofocus id="card_id"
                                value="" data-select2-id="card_id" class="form-control select2 w-100" required>
                                <option value="">Choose Card</option>
                                @foreach ($linkedCards as $card)
                                    @if ($card_id !== null && $card->id == $card_id)
                                        <option value="{{ $card->id }}" selected>
                                            {{ $card->cardDetails->cardNumber }}
                                        </option>
                                    @else
                                        <option value="{{ $card->id }}">{{ $card->cardDetails->cardNumber }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('card_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr />

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">View/Edit</th>
                            <th>Account Title</th>
                            <th>Remarks</th>
                            <th>Amount</th>
                            <th>Request Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if ($transactions)
                            @foreach ($transactions as $trn)
                                <tr>
                                    {{-- <td>{{ $loop->iteration }}</td> --}}
                                    <td align="center" scope="row"><a
                                            href="{{ url('/client/card-management/card/topups/' . ($trn->status == 'Rejected' ? 'edit' : 'view') . '/' . $trn->id) . '/' . $card_id }}"><i
                                                class="fal fa-{{ $trn->status == 'Rejected' ? 'edit' : 'tv' }}"></i></a>
                                    </td>
                                    <td>{{ $trn->account->title }}</td>
                                    <td width="40%">{{ $trn->remarks }}</td>
                                    <td>{{ number_format($trn->amount, 2) }}</td>
                                    <td>{{ \Carbon\Carbon::parse($trn->transaction_date)->format('d M, Y') }}</td>
                                    <td><span class="badge {{ $statusBadge[$trn->status] }}">
                                            @if ($trn->status != 'Rejected')
                                                {{ $trn->status }}
                                        </span>
                                    @else
                                        {{ $trn->status }}
                                        </span>
                                        <i class="fal fa-question-circle rejected_tooltip" data-toggle="tooltip"
                                            data-placement="auto" title=""
                                            data-original-title="{{ $trn->reject_reason }}"></i>
                            @endif
                            </td>

                            </tr>
                        @endforeach
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

        function getCardTopups(card_id) {
            window.location.href = "{{ url('/client/card-management/card/topups/list') }}/" + card_id
        }
    </script>
@stop
