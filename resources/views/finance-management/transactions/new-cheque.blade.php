@extends('layouts.cp')
@section('title', 'New Cheque Transaction - Finance Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>
        <a href="{{ url('/finance-management/transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Cheque Transaction
            </h2>
        </div>

        <form method="post" action="{{ url('/finance-management/transactions/store/cheque') }}"
            enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="balance" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="account_id">Account</label>
                                        <select onchange="getAccountBalance(this.value);" name="account_id" autofocus
                                            id="account_id" data-select2-id="account_id" class="form-control select2 w-100"
                                            required>
                                            <option value="">choose client account</option>
                                            @foreach ($accounts as $account)
                                                @if ($account->id == old('account_id'))
                                                    <option value="{{ $account->id }}" selected>{{ $account->title . ' - ' . $account->account_number }}
                                                    </option>
                                                @else
                                                    <option value="{{ $account->id }}">{{ $account->title . ' - ' . $account->account_number }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                        @error('account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account_balance">Account Balance</label>
                                        <div class="form-control" id="account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Transaction Amount</label>
                                        <input type="number" step="any" id="amount" name="amount" min="0"
                                            value="{{ old('amount') }}" class="form-control" required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Transaction Date</label>
                                        <input type="text" autocomplete="off" class="form-control"
                                            name="transaction_date" id="transaction_date" placeholder="Select date"
                                            required>
                                        @error('transaction_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <label class="form-label mt-4">Type: </label>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input onblur="verifyAmount(this.value);" type="radio" class="custom-control-input"
                                    id="credit" value="Credit" name="transaction_type" required>
                                <label class="custom-control-label" for="credit">Credit</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input onblur="verifyAmount(this.value);" type="radio" class="custom-control-input"
                                    id="debit" value="Debit" name="transaction_type" required>
                                <label class="custom-control-label" for="debit">Debit</label>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="name">Remarks</label>
                                <textarea maxlength="92" class="form-control" name="remarks" id="remarks" cols="30" rows="2"
                                    placeholder="Please provide relvant remarks for the transaction." required>{{ old('remarks') }}</textarea>
                                <small>maximum 92 characters allowed.</small>
                            </div>

                            @include('finance-management.transactions.partials.new-supporting-documents')

                        </div>
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="in_fav_of">In Fav Of</label>
                                <input type="text" id="in_fav_of" name="in_fav_of" value="{{ old('in_fav_of') }}"
                                    class="form-control" required>
                                @error('in_fav_of')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="cheque_number">Cheque Number</label>
                                        <input type="text" id="cheque_number" name="cheque_number"
                                            value="{{ old('cheque_number') }}" class="form-control" required>
                                        @error('cheque_number')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="cheque_date">Cheque Date</label>
                                        <input type="text" autocomplete="off" class="form-control" name="cheque_date"
                                            id="cheque_date" placeholder="Select date" required>
                                        @error('cheque_date')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            <div class="form-group mt-4">
                                <label class="form-label" for="cheque_bank">Bank Name</label>
                                <input type="text" id="name" name="cheque_bank"
                                    value="{{ old('cheque_bank') }}" class="form-control" required>
                                @error('cheque_bank')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" class="btn btn-primary">Submit</button>
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
        const controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        };

        $(document).ready(() => {

            $('.select2').select2();

            // minimum setup
            $('#transaction_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#cheque_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });
        })

        function verifyAmount(type) {
            var balance = parseFloat($("#balance").val());
            var amount = parseFloat($("#amount").val());

            if (type == "Debit") {
                if (balance < parseFloat(amount)) {
                    alert("Insufficient balance available, please use lower value than available balance (" + balance +
                        ")");
                    $("#amount").val(0);
                    return false;
                } else {
                    return true;
                }
            } else {
                return true;
            }
        }

        function getAccountBalance(account_id) {
            $.post("{{ url('/finance-management/get-balance') }}", {
                    "account_id": account_id
                },
                function(data, status) {
                    $("#account_balance").html(data.balance);
                    if (!parseFloat(data.balance) > 0) {
                        $("#debit").attr("disabled", true);
                        $("#debit").prop('checked', false);
                        $("#balance").val();
                    } else {
                        $("#debit").attr("disabled", false);
                        $("#balance").val(parseFloat(data.balance));
                    }

                    $("#debit").prop('checked', false);
                    $("#credit").prop('checked', false);
                    $("#amount").val(0);
                });
        }
    </script>
@stop
