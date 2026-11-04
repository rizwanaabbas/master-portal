@extends('layouts.cp')
@section('title', 'New Card Top-up Request - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
        <a href="{{ url('/card-management/card/topup-requests/list') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                New Card Top-up Request
            </h2>
        </div>

        <form method="post" action="{{ url('/card-management/card/topups/storeTopupBackOffice') }}"
            enctype="multipart/form-data">
            @csrf
            {{-- <input type="hidden" name="card_id" value="{{ $id }}"> --}}
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label" for="debit_account_id">Select Card</label>
                                <select name="card_id" autofocus id="card_id" value="" data-select2-id="card_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">Choose Card</option>
                                    @foreach ($cardsJson as $row)
                                        <option value="{{ $row['card_id'] }}" @selected(old('card_id') == $row['card_id'])>
                                            {{ $row['card_number'] ?? 'Card #' . $row['card_id'] }}
                                            @if (!empty($row['card_holder_name']))
                                                - {{ $row['card_holder_name'] }}
                                            @endif
                                            @if (!empty($row['card_type']))
                                                ({{ $row['card_type'] }})
                                            @endif
                                        </option>
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
                                        <select name="debit_account_id" autofocus id="debit_account_id" value=""
                                            data-select2-id="debit_account_id" class="form-control select2 w-100" required>
                                            <option value="">choose client account</option>
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

                            <input type="hidden" id="balance" value="0" />

                            <div class="row mt-4">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="amount">Top-up Amount</label>
                                        <input onblur="verifyAmount(this.value);" type="number" step="any"
                                            id="amount" name="amount" value="{{ old('amount') }}" class="form-control"
                                            required>
                                        @error('amount')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                {{-- <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="transaction_date">Top-up Date</label>
                                        <input type="text" required autocomplete="off" class="form-control"
                                            name="transaction_date" id="transaction_date" placeholder="Select date"
                                            required>
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
                                            rows="2" placeholder="Please provide relvant remarks for the transaction.">{{ old('debit_remarks') }}</textarea>
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
        const cardsData = @json($cardsJson);

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

        function getAccountBalance(type, account_id) {
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

                    $("#amount").val('');
                });
        }

        function findCardRow(cardId) {
            return cardsData.find(r => String(r.card_id) === String(cardId));
        }

        function formatAmount(value) {
            if (value === null || value === undefined || value === '') return '--';
            const numberValue = Number(value);
            if (Number.isNaN(numberValue)) return value;
            return numberValue.toLocaleString(undefined, {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function populateAccountsForCard(cardId) {
            const row = findCardRow(cardId);
            const $account = $('#debit_account_id');

            $account.empty();
            $account.append(new Option('choose client account', ''));

            if (!row || !row.accounts || row.accounts.length === 0) {
                $('#credit_account_balance').html('--');
                $('#balance').val('0');
                $account.trigger('change');
                return;
            }

            row.accounts.forEach(acc => {
                const option = new Option(acc.label, acc.id);
                option.setAttribute('data-amount', acc.amount);
                $account.append(option);
            });

            const oldAccount = "{{ old('debit_account_id') }}";
            if (oldAccount) {
                $account.val(oldAccount);
            }

            $account.trigger('change');
        }

        function syncSelectedAccountBalance() {
            const $selected = $('#debit_account_id option:selected');
            const amount = $selected.data('amount');
            $('#credit_account_balance').html(formatAmount(amount));
            $('#balance').val(amount ?? '0');
        }

        $(document).ready(function() {

            // not working
            $('.select2').select2();

            $('#card_id').on('change', function() {
                populateAccountsForCard(this.value);
            });

            $('#debit_account_id').on('change', function() {
                syncSelectedAccountBalance();
            });

            // Initial load
            const initialCard = $('#card_id').val() || "{{ old('card_id') }}";
            if (initialCard) {
                populateAccountsForCard(initialCard);
            }
            syncSelectedAccountBalance();

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
