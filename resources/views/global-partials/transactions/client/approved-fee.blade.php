@if (
    'Internal' == $transactionRequest->transaction_nature &&
        $transactionRequest->account->account_currency !== $transactionRequest->receiver->account_currency)
    @php
        $currencyExchange = true;
        $debetingAccount = $transactionRequest->receiver;
    @endphp
@else
    @php
        $currencyExchange = false;
        $debetingAccount = $transactionRequest->account;
    @endphp
@endif

@if ($service_fee_trn)
    @php
        $serviceFeeTrnId = $service_fee_trn->id;
        $serviceFeeCurrencyId = $service_fee_trn->currency_id;
        $serviceFeeDebitAccountId = $service_fee_trn->fee_debit_account_id;

        if ($service_fee_trn->linkedTrn) {
            $is_service_fee_hidden_for_clients = $service_fee_trn->linkedTrn->is_hidden_for_clients;
        } else {
            $is_service_fee_hidden_for_clients = $service_fee_trn->is_hidden_for_clients;
        }

        $serviceFeeAmount = $service_fee_trn->amount;
        $serviceFeeRemarks = $service_fee_trn->remarks;
    @endphp
@else
    @php
        $serviceFeeTrnId = null;
        $serviceFeeCurrencyId = $debetingAccount->account_currency;
        $serviceFeeDebitAccountId = $debetingAccount->id;
        $is_service_fee_hidden_for_clients = false;
        $serviceFeeAmount = 0;
        $serviceFeeRemarks = $transaction->remarks;
    @endphp
@endif

@if ($trn_fee_trn)
    @php
        $trnFeeTrnId = $trn_fee_trn->id;
        $trnFeeCurrencyId = $trn_fee_trn->currency_id;
        $trnFeeDebitAccountId = $trn_fee_trn->fee_debit_account_id;

        if ($trn_fee_trn->linkedTrn) {
            $is_trn_fee_hidden_for_clients = $trn_fee_trn->linkedTrn->is_hidden_for_clients;
        } else {
            $is_trn_fee_hidden_for_clients = $trn_fee_trn->is_hidden_for_clients;
        }

        $trnFeeAmount = $trn_fee_trn->amount;
        $trnFeeRemarks = $trn_fee_trn->remarks;

    @endphp
@else
    @php
        $trnFeeTrnId = null;
        $trnFeeCurrencyId = $debetingAccount->account_currency;
        $trnFeeDebitAccountId = $debetingAccount->id;
        $is_trn_fee_hidden_for_clients = false;
        $trnFeeAmount = 0;
        $trnFeeRemarks = $transaction->remarks;
    @endphp
@endif

{{-- <input type="hidden" id="parent_trn_id" name="parent_trn_id" value="{{ $transaction->parent_trn_id }}" /> --}}
<input type="hidden" id="service_fee_trn_id" name="service_fee_trn_id" value="{{ $serviceFeeTrnId }}" />
<input type="hidden" id="trn_fee_trn_id" name="trn_fee_trn_id" value="{{ $trnFeeTrnId }}" />
<input type="hidden" id="service_fee_bank_account_id" name="service_fee_bank_account_id"
    value="{{ $serviceFeeAccounts[$debetingAccount->account_currency] }}" />
<input type="hidden" id="service_fee_currency_id" name="service_fee_currency_id" value="{{ $serviceFeeCurrencyId }}" />

@if ('Internal' !== $transactionRequest->transaction_nature)
    {{-- @if ($transactionRequest->transaction_nature != 'Top-up') --}}
    <input type="hidden" id="trn_fee_bank_account_id" name="trn_fee_bank_account_id"
        value="{{ $trnFeeAccounts[$debetingAccount->account_currency] }}" />
    <input type="hidden" id="trn_fee_currency_id" name="trn_fee_currency_id" value="{{ $trnFeeCurrencyId }}" />
@endif

<tr>
    <th>Account for {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee</th>
    <td>
        <div class="row">
            <div class="col-md-10">
                <select onchange="handleOnchange(this, {{ $serviceFeeDebitAccountId }}, 'account_balance_service')"
                    name="service_fee_client_account" autofocus id="service_fee_client_account" value=""
                    data-select2-id="service_fee_client_account" class="form-control select2" style="max-width: 70%"
                    required readonly>
                    <option value="">Choose Client Account for
                        {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee</option>

                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}"
                            data-fee-account-id="{{ $serviceFeeAccounts[$account->account_currency] ?? '' }}"
                            data-fee-currency-id="{{ $account->account_currency }}"
                            {{ $account->id == $serviceFeeDebitAccountId ? 'selected' : '' }}>
                            {{ $account->title . ' - ' . $account->account_number }}
                        </option>
                    @endforeach

                </select>
            </div>
            <div class="col-md-2">
                <label id="account_balance_service" class="mt-2">Balance: 0</label>
            </div>
        </div>
    </td>
</tr>
<tr>
    <th>{{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee Amount</th>
    <td>
        <div class="row">
            <div class="col-md-10">
                <input type="number" step="any" id="service_fee" name="service_fee"
                    onfocus="handleOnfocus('service_fee_message')" value="{{ $serviceFeeAmount }}" class="form-control"
                    required />
                <span id="service_fee_message" style="color: red;"></span>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" @if ($is_service_fee_hidden_for_clients) checked @endif
                            class="custom-control-input" id="is_service_fee_trn_hidden_for_client" value="1"
                            name="is_service_fee_trn_hidden_for_client">
                        <label class="custom-control-label" for="is_service_fee_trn_hidden_for_client">Hidden</label>
                    </div>
                </div>
            </div>
        </div>
    </td>
</tr>
<tr>
    <th>{{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee Remarks</th>
    <td>
        <textarea name="service_fee_remarks" id="service_fee_remarks" class="form-control" cols="30" required
            onfocus="handleOnfocus('service_fee_remarks_message')" rows="2">{{ $serviceFeeRemarks }}</textarea>
        <span id="service_fee_remarks_message" style="color: red;"></span>
    </td>
</tr>
{{-- <tr id="service_fee_exchange_rate_row" style="display:none">
    <th>{{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee Exchange Rate</th>
    <td>
        <input type="number" step="any" id="service_fee_exchange_rate"
            name="service_fee_exchange_rate"
            onfocus="handleOnfocus('service_fee_exchange_rate_message')" value=""
            class="form-control" required disabled />
        <span id="service_fee_exchange_rate_message" style="color: red;"></span>
    </td>
</tr> --}}

@if ('Internal' !== $transactionRequest->transaction_nature)
    <tr>
        <th>Account for Correspondent Charges</th>
        <td>
            <div class="row">
                <div class="col-md-10">
                    <select onchange="handleOnchange(this, {{ $trnFeeDebitAccountId }}, 'account_balance_trn')"
                        name="trn_fee_client_account" autofocus id="trn_fee_client_account" value=""
                        data-select2-id="trn_fee_client_account" class="form-control select2" style="max-width: 70%"
                        required readonly>
                        <option value="">Choose Client Account for Transaction Fee</option>

                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                data-fee-account-id="{{ $trnFeeAccounts[$account->account_currency] ?? null }}"
                                data-fee-currency-id="{{ $account->account_currency }}"
                                {{ $account->id == $trnFeeDebitAccountId ? 'selected' : '' }}>
                                {{ $account->title . ' - ' . $account->account_number }}
                            </option>
                        @endforeach

                    </select>
                </div>
                <div class="col-md-2">
                    <label id="account_balance_trn" class="mt-2">Balance: 0</label>
                </div>
            </div>
        </td>
    </tr>

    <tr>
        <th>Correspondent Charges</th>
        <td>
            <div class="row">
                <div class="col-md-10">
                    <input type="number" step="any" id="trn_fee" name="trn_fee"
                        onfocus="handleOnfocus('trn_fee_message')" value="{{ $trnFeeAmount }}" class="form-control"
                        required />
                    <span id="trn_fee_message" style="color: red;"></span>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" @if ($is_trn_fee_hidden_for_clients) checked @endif
                                class="custom-control-input" id="is_trn_fee_trn_hidden_for_client" value="1"
                                name="is_trn_fee_trn_hidden_for_client">
                            <label class="custom-control-label" for="is_trn_fee_trn_hidden_for_client">Hidden</label>
                        </div>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <th>Correspondent Charges Remarks</th>
        <td>
            <textarea name="trn_fee_remarks" id="trn_fee_remarks" class="form-control" cols="30" required
                onfocus="handleOnfocus('trn_fee_remarks_message')" rows="2">{{ $trnFeeRemarks }}</textarea>
            <span id="trn_fee_remarks_message" style="color: red;"></span>
        </td>
    </tr>
@endif
{{-- <tr id="trn_fee_exchange_rate_row" style="display:none">
    <th>Transaction Fee Exchange Rate</th>
    <td>
        <input type="number" step="any" id="trn_fee_exchange_rate"
            name="trn_fee_exchange_rate"
            onfocus="handleOnfocus('trn_fee_exchange_rate_message')" value=""
            class="form-control" required disabled />
        <span id="trn_fee_exchange_rate_message" style="color: red;"></span>
    </td>
</tr> --}}
<script>
    function approve() {

        const isValid = validateFields()

        if (isValid) {
            Swal.fire({
                title: "Are you sure?",
                text: "Transaction will be approved",
                type: "warning",
                icon: "warning",
                dangerMode: true,
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approval-form').submit()
                }
            });
        }
    }

    function validateFields() {
        var isValid = true
        const fields = {
            'amount': 'amount_message',
            // 'service_fee': 'service_fee_message',
            // 'trn_fee': 'trn_fee_message',
            // 'service_fee_remarks': 'service_fee_remarks_message',
            // 'trn_fee_remarks': 'trn_fee_remarks_message'
            // 'service_fee_exchange_rate': 'service_fee_exchange_rate_message',
            // 'trn_fee_exchange_rate': 'trn_fee_exchange_rate_message',
        }

        for (key in fields) {
            if (document.getElementById(key) !== null) {
                const field = document.getElementById(key);

                if (field.value == 0 && !field.hasAttribute('disabled')) {
                    document.getElementById(fields[key]).innerHTML = 'Required Field!'
                    isValid = false;
                }
            }
        }

        return isValid;
    }

    function handleOnfocus(id) {
        if (document.getElementById(id))
            document.getElementById(id).innerHTML = ''
    }

    function handleOnchange(dropdown, trn_account, balance_label_id) {
        const fee_account = dropdown.value
        const fee_account_id = dropdown.options[dropdown.selectedIndex].dataset.feeAccountId
        const fee_currency_id = dropdown.options[dropdown.selectedIndex].dataset.feeCurrencyId

        if ('service_fee_client_account' == dropdown.id) {
            document.getElementById('service_fee_bank_account_id').value = fee_account_id
            document.getElementById('service_fee_currency_id').value = fee_currency_id
        } else if ('trn_fee_client_account' == dropdown.id) {
            document.getElementById('trn_fee_bank_account_id').value = fee_account_id
            document.getElementById('trn_fee_currency_id').value = fee_currency_id
        }

        // if (fee_account != trn_account) {
        //     document.getElementById(id).removeAttribute('disabled')
        //     document.getElementById(id + '_row').style = ''
        // } else {
        //     document.getElementById(id).disabled = 'disabled'
        //     document.getElementById(id + '_row').style.display = 'none'
        // }

        getAccountBalance(fee_account, balance_label_id)
    }

    function getAccountBalance(account_id, balance_label_id) {
        $.post("{{ url('/finance-management/get-balance') }}", {
                "account_id": account_id
            },
            function(data, status) {
                $("#" + balance_label_id).html('Balance: ' + data.formatted_balance);
            }
        );
    }

    document.addEventListener('DOMContentLoaded', function() {
        handleOnchange(document.getElementById('service_fee_client_account'),
            {{ $debetingAccount->id }}, 'account_balance_service')
        handleOnchange(document.getElementById('trn_fee_client_account'),
            {{ $debetingAccount->id }}, 'account_balance_trn')
    })
</script>
