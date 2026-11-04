@extends('layouts.cp')
@section('title', 'Fireblocks Withdraw')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Fireblocks Withdraw</span></h1>
        <a href="{{ url('/fireblocks/withdrawals') }}" class="btn btn-primary ml-auto"><i class="fal fa-arrow-alt-left"></i> Go Back</a>
    </div>

    <div class="panel">
        <div class="panel-hdr"><h2>New Fireblocks Withdrawal</h2></div>
        <form method="post" action="{{ url('/fireblocks/withdraw') }}" enctype="multipart/form-data" id="fb-withdraw-form">
            @csrf
            <input type="hidden" name="balance" id="balance" value="0">
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <!-- Left: account + amount -->
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        <label class="form-label" for="account_id">From Crypto Account <span class="text-danger">*</span></label>
                                        <select name="account_id" id="account_id" class="form-control select2 w-100" required
                                            onchange="getAccountBalance(this.value); loadBeneficiaries(this.value);">
                                            <option value="">Choose Crypto Account</option>
                                            @foreach ($cryptoAccounts as $acc)
                                                <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>{{ $acc->title }} - {{ $acc->account_number }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label">Account Balance</label>
                                        <div class="form-control" id="account_balance" readonly>--</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" id="crypto-amount-label" for="amount">Amount to Withdraw <span class="text-danger">*</span></label>
                                        <input type="number" step="any" id="amount" name="amount" min="1" value="{{ old('amount') }}" class="form-control" required>
                                        <small id="crypto-amount-limits"></small>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="remarks">Remarks</label>
                                        <textarea maxlength="92" class="form-control" name="remarks" id="remarks" rows="2" placeholder="Relevant remarks for the transaction.">{{ old('remarks') }}</textarea>
                                        <small>maximum 92 characters allowed.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: beneficiary -->
                        <div class="col-md-6" style="border-left: 1px solid lightgray;">
                            <div class="form-group">
                                <label class="form-label" for="crypto_beneficiary_id">Fireblocks Beneficiary <span class="text-danger">*</span></label>
                                <select name="crypto_beneficiary_id" id="crypto_beneficiary_id" class="form-control select2 w-100" required onchange="fillBeneficiary()">
                                    <option value="">Select a beneficiary</option>
                                </select>
                                <small class="text-muted">Only whitelisted Fireblocks beneficiaries for the selected account are shown.
                                    <a href="{{ url('/fireblocks/beneficiaries/create') }}" target="_blank">Add a beneficiary</a>.
                                </small>
                            </div>

                            <div class="form-group mt-3">
                                <label class="form-label">Destination Wallet Address</label>
                                <div class="form-control" id="disp_wallet_address" readonly style="word-break: break-all; min-height: calc(1.47em + 1.25rem + 2px);">--</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Network</label>
                                        <div class="form-control" id="disp_network" readonly>--</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label">Destination Tag / Memo</label>
                                        <div class="form-control" id="disp_tag" readonly>--</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12">
                            <hr>
                            <button type="submit" class="btn btn-primary">Submit Withdrawal</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@section('scripts')
    <script>
        let beneficiariesCache = [];

        function getAccountBalance(account_id) {
            if (!account_id) { $('#account_balance').html('--'); $('#crypto-amount-limits').html(''); return; }
            $.post("{{ url('/finance-management/get-balance') }}", { account_id: account_id }, function(data) {
                $('#account_balance').html(data.formatted_balance);
                $('#balance').val(parseFloat(data.balance) || 0);
                $('#crypto-amount-label').html(`${data.currency_short} Amount to Withdraw`);
                $('#amount').attr('min', data.min_currency_limit);
                let max = '';
                if (data.max_currency_limit != -1) {
                    max = `, max: ${Intl.NumberFormat('en-US').format(data.max_currency_limit)}`;
                    $('#amount').attr('max', data.max_currency_limit);
                }
                $('#crypto-amount-limits').html(`<b>Limits: </b>(min: ${Intl.NumberFormat('en-US').format(data.min_currency_limit) + max})`);
                $('#amount').val('');
            });
        }

        function loadBeneficiaries(account_id) {
            $('#crypto_beneficiary_id').html('<option value="">Select a beneficiary</option>').trigger('change');
            clearBeneficiaryDisplay();
            if (!account_id) return;
            $.get("{{ url('/fireblocks/withdraw/beneficiaries') }}/" + account_id, function(list) {
                beneficiariesCache = list || [];
                let options = '<option value="">Select a beneficiary</option>';
                beneficiariesCache.forEach(function(b) {
                    options += `<option value="${b.id}">${b.label} — ${b.wallet_address.substring(0, 14)}… (${b.network_code || ''})</option>`;
                });
                $('#crypto_beneficiary_id').html(options).trigger('change');
            });
        }

        function clearBeneficiaryDisplay() {
            $('#disp_wallet_address').text('--');
            $('#disp_network').text('--');
            $('#disp_tag').text('--');
        }

        function fillBeneficiary() {
            const id = $('#crypto_beneficiary_id').val();
            const b = beneficiariesCache.find(x => String(x.id) === String(id));
            if (!b) { clearBeneficiaryDisplay(); return; }
            $('#disp_wallet_address').text(b.wallet_address);
            $('#disp_network').text(`${b.network_name || ''} (${b.network_code || ''})`);
            $('#disp_tag').text(b.destination_tag || '--');
        }

        $('#fb-withdraw-form').on('submit', function(e) {
            e.preventDefault();
            if (!$('#crypto_beneficiary_id').val()) {
                Swal.fire('Error', 'Please select a beneficiary.', 'error');
                return false;
            }
            const balance = parseFloat($('#balance').val());
            const amount = parseFloat($('#amount').val());
            if (amount > balance) {
                Swal.fire('Error', 'Amount exceeds available balance (' + balance + ').', 'error');
                return false;
            }
            const addr = $('#disp_wallet_address').text();
            Swal.fire({
                title: 'Are you sure?',
                text: `Withdraw to ${addr}? This will submit to Fireblocks and debit the account immediately. It cannot be reversed.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Submit!'
            }).then(function(result) {
                if (result.value) { document.getElementById('fb-withdraw-form').submit(); }
            });
        });

        $(document).ready(function() {
            $('.select2').select2();
            @if (old('account_id'))
                getAccountBalance("{{ old('account_id') }}");
                loadBeneficiaries("{{ old('account_id') }}");
            @endif
        });
    </script>
@stop
@stop
