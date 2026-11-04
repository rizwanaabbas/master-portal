@extends('layouts.cp')
@section('title', 'Edit Card Top-up Request - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        <a href="{{ url('/client/card-management/card/topups/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Edit Card Top-up Request
            </h2>
        </div>

        <form method="post" action="{{ url('/client/card-management/card/topups/update') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">
            <input type="hidden" name="card_id" value="{{ $card_id }}">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')

                    <div class="d-flex justify-content-center">
                        <h4 style="margin-top: 10px;">Current Card..</h4>
                        <div class="col-sm-12 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                            <div class="form-group">
                                <select name="card_id" autofocus id="card_id" value="" data-select2-id="card_id"
                                    class="form-control select2 w-100" required>
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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_account_id">From Account</label>
                                        <select onchange="getAccountBalance(this.value, 'credit');" name="debit_account_id"
                                            autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">choose client account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == $transaction->account_id)
                                                    <option value="{{ $account->id }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('debit_account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="credit_account_balance">Account Balance</label>
                                        <div class="form-control" id="credit_account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Top-up Amount</label>
                                        <input onblur="verifyAmount(this.value);" type="number" step="any"
                                            id="amount" name="amount" value="{{ $transaction->amount }}"
                                            class="form-control" required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Top-up Date</label>
                                        <input type="text"
                                            value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}"
                                            required autocomplete="off" class="form-control" name="transaction_date"
                                            id="transaction_date" placeholder="Select date" required>
                                        @error('transaction_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div> --}}
                            </div>
                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="debit_remarks">Top-up Remarks</label>
                                        <textarea maxlength="92" class="form-control" required name="debit_remarks" id="debit_remarks" cols="30"
                                            rows="2" placeholder="Please provide relvant remarks for the transaction.">{{ $transaction->remarks }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button id="submit" type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        function verifyAmount(amount) {
            var balance = parseFloat($("#balance").val());
            if (balance < parseFloat(amount)) {
                alert("Insufficient balance available, please use lower value than available balance (" + balance + ")");
                $("#amount").val(0);
                return false;
            } else {
                return true;
            }
        }

        function getAccountBalance(account_id, type) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#" + type + "_account_balance").html(data.formatted_balance);
                    if (type == "debit" && !parseFloat(data.balance) > 0) {
                        $("#amount").attr("disabled", true);
                        $("#submit").attr("disabled", true);
                        $("#balance").val();

                    } else {
                        $("#balance").val(parseFloat(data.balance));
                        $("#amount").attr("disabled", false);
                        $("#submit").attr("disabled", false);
                    }

                    // $("#amount").val('');
                });
        }

        $(document).ready(function() {
            getAccountBalance('@php echo $transaction->account_id @endphp', 'credit')

            // not working
            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })
    </script>
@stop
