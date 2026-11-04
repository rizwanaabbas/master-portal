<hr>
<h4><strong>Transaction Fees</strong></h4>

<form method="POST" name="approval-form" id="approval-form" action="{{ url('/finance-management/transactions/approve') }}">
    @csrf
    <input type="hidden" name="id" id="id" value="{{ $transaction->id }}">

    <input type="hidden" id="service_fee_bank_account_id" name="service_fee_bank_account_id"
        value="{{ $serviceFeeBankAccount ? $serviceFeeBankAccount : $serviceFeeAccounts[$transactionRequest->account->account_currency] }}" />
    <input type="hidden" id="service_fee_currency_id" name="service_fee_currency_id"
        value="{{ $transactionRequest->account->account_currency }}" />

    <input type="hidden" id="trn_fee_bank_account_id" name="trn_fee_bank_account_id"
        value="{{ $trnFeeBankAccount ? $trnFeeBankAccount : $trnFeeAccounts[$transactionRequest->account->account_currency] }}" />
    <input type="hidden" id="trn_fee_currency_id" name="trn_fee_currency_id"
        value="{{ $transactionRequest->account->account_currency }}" />
    <input type="hidden" name="transaction_date"
        value="{{ \Carbon\Carbon::parse($transaction->transaction_date)->format('d/m/Y') }}" />
    <input type="hidden" id="trn_remarks" name="remarks" value="{{ $transaction->remarks }}" />

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="row mt-4">

                @if ($clientAccounts)
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label" for="service_fee_client_account">
                                Account for {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee
                            </label>
                            <select
                                onchange="handleOnchange(this, {{ $transactionRequest->account_id }}, 'service_fee_exchange_rate')"
                                name="service_fee_client_account" autofocus id="service_fee_client_account"
                                value="" data-select2-id="service_fee_client_account" class="form-control select2"
                                style="max-width: 70%" required>
                                <option value="">Choose Client Account for
                                    {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee</option>

                                @foreach ($clientAccounts as $account)
                                    <option value="{{ $account->id }}"
                                        data-fee-account-id="{{ $serviceFeeAccounts[$account->account_currency] }}"
                                        data-fee-currency-id="{{ $account->account_currency }}"
                                        {{ $account->id == $transactionRequest->account_id ? 'selected' : '' }}>
                                        {{ $account->title . ' - ' . $account->account_number }}
                                    </option>
                                @endforeach

                            </select>
                        </div>
                    </div>
                @elseif (
                    $transactionRequest->transaction_nature == 'Internal' &&
                        $transactionRequest->account->account_currency !== $transactionRequest->receiver->account_currency)
                    <input type="hidden" id="service_fee_client_account" name="service_fee_client_account"
                        value="{{ $transactionRequest->receiver->id }}" />
                @else
                    <input type="hidden" id="service_fee_client_account" name="service_fee_client_account"
                        value="{{ $transactionRequest->account->id }}" />
                @endif
                <div class="col-md-5">
                    <div class="form-group">
                        <label class="form-label" for="service_fee">
                            {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee Amount
                        </label>
                        <input type="number" step="any" id="service_fee" name="service_fee"
                            onfocus="handleOnfocus('service_fee_message')" value="0" class="form-control"
                            required />
                        <span id="service_fee_message" style="color: red;"></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="custom-control custom-switch mt-5">
                            <input type="checkbox" class="custom-control-input"
                                id="is_service_fee_trn_hidden_for_client" value="1"
                                name="is_service_fee_trn_hidden_for_client">
                            <label class="custom-control-label"
                                for="is_service_fee_trn_hidden_for_client">Hidden</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label" for="service_fee_remarks">
                            {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee Remarks
                        </label>
                        <textarea name="service_fee_remarks" id="service_fee_remarks" class="form-control" cols="30" required
                            rows="2">{{ $transactionRequest->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6" style="border-left: 1px solid lightgray;">
            <div class="row mt-4">

                @if ($clientAccounts)
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="form-label" for="trn_fee_client_account">
                                Account for Transaction Fee
                            </label>
                            <select
                                onchange="handleOnchange(this, {{ $transactionRequest->account_id }}, 'trn_fee_exchange_rate')"
                                name="trn_fee_client_account" autofocus id="trn_fee_client_account" value=""
                                data-select2-id="trn_fee_client_account" class="form-control select2"
                                style="max-width: 70%" required>
                                <option value="">Choose Client Account for Correspondent Charges</option>

                                @foreach ($clientAccounts as $account)
                                    <option value="{{ $account->id }}"
                                        data-fee-account-id="{{ $serviceFeeAccounts[$account->account_currency] }}"
                                        data-fee-currency-id="{{ $account->account_currency }}"
                                        {{ $account->id == $transactionRequest->account_id ? 'selected' : '' }}>
                                        {{ $account->title . ' - ' . $account->account_number }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @elseif (
                    $transactionRequest->transaction_nature == 'Internal' &&
                        $transactionRequest->account->account_currency !== $transactionRequest->receiver->account_currency)
                    <input type="hidden" id="trn_fee_client_account" name="trn_fee_client_account"
                        value="{{ $transactionRequest->receiver->id }}" />
                @else
                    <input type="hidden" id="trn_fee_client_account" name="trn_fee_client_account"
                        value="{{ $transactionRequest->account->id }}" />
                @endif
                <div class="col-md-5">
                    <div class="form-group">
                        <label class="form-label" for="trn_fee">
                            Correspondent Charges
                        </label>
                        <input type="number" step="any" id="trn_fee" name="trn_fee"
                            onfocus="handleOnfocus('trn_fee_message')" value="0" class="form-control"
                            required />
                        <span id="trn_fee_message" style="color: red;"></span>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <div class="custom-control custom-switch mt-5">
                            <input type="checkbox" class="custom-control-input" id="is_trn_fee_trn_hidden_for_client"
                                value="1" name="is_trn_fee_trn_hidden_for_client">
                            <label class="custom-control-label" for="is_trn_fee_trn_hidden_for_client">Hidden</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="form-group">
                        <label class="form-label" for="trn_fee_remarks">
                            Correspondent Charges Remarks
                        </label>
                        <textarea name="trn_fee_remarks" id="trn_fee_remarks" class="form-control" cols="30" required rows="2">{{ $transactionRequest->remarks }}</textarea>
                    </div>
                </div>
            </div>
        </div>

    </div>
</form>

<script>
    function approve() {

        const isValid = validateFields()

        if (isValid) {
            const remarks = document.getElementById('remarks').value
            document.getElementById('trn_remarks').value = remarks

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
        document.getElementById(id).innerHTML = ''
    }

    function handleOnchange(dropdown, trn_account, id) {
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
    }
</script>
