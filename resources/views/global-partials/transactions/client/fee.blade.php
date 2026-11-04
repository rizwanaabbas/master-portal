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
{{-- @php
    $service_fee_client_account_id = $debetingAccount->id;
    $service_fee_currency_id = $debetingAccount->account_currency;
    $service_fee = 0;
    $is_service_fee_trn_hidden_for_client = 0;
    $service_fee_remarks = $transactionRequest->remarks;
    $trn_fee_client_account_id = $debetingAccount->id;
    $trn_fee_currency_id = $debetingAccount->account_currency;
    $trn_fee = 0;
    $is_trn_fee_trn_hidden_for_client = 0;
    $trn_fee_remarks = $transactionRequest->remarks;
@endphp --}}

{{-- @can('approve transaction') --}}
@php
    $trn_details = $transactionRequest->transactionDetails;
    $service_fee_client_account_id = isset($detailsChanges['service_fee_client_account_id'])
        ? $detailsChanges['service_fee_client_account_id']['new_value'][0]
        : $debetingAccount->id;

    $service_fee_currency_id = isset($detailsChanges['service_fee_currency_id'])
        ? $detailsChanges['service_fee_currency_id']['new_value'][0]
        : $debetingAccount->account_currency;

    // $service_fee = isset($detailsChanges['service_fee']) ? $detailsChanges['service_fee']['new_value'][0] : 0;
    $service_fee = isset($detailsChanges['service_fee']) ? $detailsChanges['service_fee']['new_value'][0] : ($trn_details->service_fee ?? 0);

    $is_service_fee_trn_hidden_for_client = isset($detailsChanges['is_service_fee_trn_hidden_for_client'])
        ? $detailsChanges['is_service_fee_trn_hidden_for_client']['new_value'][0]
        : 0;

    $service_fee_remarks = isset($detailsChanges['service_fee_remarks'])
        ? $detailsChanges['service_fee_remarks']['new_value'][0]
        : $transactionRequest->remarks;

    // $trn_fee_bank_account_id = isset($detailsChanges['trn_fee_bank_account_id'])
    //     ? $detailsChanges['trn_fee_bank_account_id']['new_value'][0]
    //     : null;

    $trn_fee_client_account_id = isset($detailsChanges['trn_fee_client_account_id']['new_value'][0])
        ? $detailsChanges['trn_fee_client_account_id']['new_value'][0]
        : $debetingAccount->id;

    $trn_fee_currency_id = isset($detailsChanges['trn_fee_currency_id'])
        ? $detailsChanges['trn_fee_currency_id']['new_value'][0]
        : $debetingAccount->account_currency;

    // $trn_fee = isset($detailsChanges['trn_fee']) ? $detailsChanges['trn_fee']['new_value'][0] : null;
    $trn_fee = isset($detailsChanges['trn_fee']) ? $detailsChanges['trn_fee']['new_value'][0] : ($trn_details->trn_fee ?? 0);

    $is_trn_fee_trn_hidden_for_client = isset($detailsChanges['is_trn_fee_trn_hidden_for_client'])
        ? $detailsChanges['is_trn_fee_trn_hidden_for_client']['new_value'][0]
        : 0;

    $trn_fee_remarks = isset($detailsChanges['trn_fee_remarks'])
        ? $detailsChanges['trn_fee_remarks']['new_value'][0]
        : $transactionRequest->remarks;
@endphp
{{-- @endcan --}}

<input type="hidden" id="service_fee_bank_account_id" name="service_fee_bank_account_id"
    value="{{ $serviceFeeAccounts[$service_fee_currency_id] }}" />
<input type="hidden" id="service_fee_currency_id" name="service_fee_currency_id" value="{{ $service_fee_currency_id }}" />

@if ('Internal' !== $transactionRequest->transaction_nature)
    {{-- @if ($transactionRequest->transaction_nature != 'Top-up') --}}
    <input type="hidden" id="trn_fee_bank_account_id" name="trn_fee_bank_account_id"
        value="{{ $trnFeeAccounts[$trn_fee_currency_id] }}" />
    <input type="hidden" id="trn_fee_currency_id" name="trn_fee_currency_id" value="{{ $trn_fee_currency_id }}" />
@endif

<tr>
    <th>Account for {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee</th>
    <td>
        <div class="row">
            <div class="col-md-10 field-container">
                <select onchange="handleOnchange(this, {{ $service_fee_client_account_id }}, 'account_balance_service')"
                    name="service_fee_client_account" autofocus id="service_fee_client_account" value=""
                    data-select2-id="service_fee_client_account" class="form-control select2" style="max-width: 70%"
                    required>
                    <option value="">Choose Client Account for
                        {{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee</option>

                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}"
                            data-fee-account-id="{{ $serviceFeeAccounts[$account->account_currency] ?? '' }}"
                            data-fee-currency-id="{{ $account->account_currency }}"
                            {{ $account->id == $service_fee_client_account_id ? 'selected' : '' }}>
                            {{ $account->title . ' - ' . $account->account_number }}
                        </option>
                    @endforeach

                </select>

                @if (isset($detailsChanges['service_fee_client_account_id']))
                    {!! $detailsChanges['service_fee_client_account_id']['tooltip'] !!}
                @endif
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
            <div class="col-md-10 field-container">
                <input type="number" step="any" id="service_fee" name="service_fee"
                    onfocus="handleOnfocus('service_fee_message')" value="{{ old('service_fee', $service_fee) }}" class="form-control"
                    required />

                @if (isset($detailsChanges['service_fee']))
                    {!! $detailsChanges['service_fee']['tooltip'] !!}
                @endif
                <span id="service_fee_message" style="color: red;"></span>
            </div>
            <div class="col-md-2 field-container">
                <div class="form-group">
                    <div class="custom-control custom-switch mt-2">
                        <input type="checkbox" @if ($currencyExchange || $is_service_fee_trn_hidden_for_client) checked @endif
                            class="custom-control-input" id="is_service_fee_trn_hidden_for_client" value="1"
                            name="is_service_fee_trn_hidden_for_client">
                        <label class="custom-control-label" for="is_service_fee_trn_hidden_for_client">Hidden</label>
                    </div>
                </div>

                @if (isset($detailsChanges['is_service_fee_trn_hidden_for_client']))
                    {!! $detailsChanges['is_service_fee_trn_hidden_for_client']['tooltip'] !!}
                @endif
            </div>
        </div>
    </td>
</tr>
<tr>
    <th>{{ $ownCompany ? $ownCompany->short_name : 'Service' }} Fee Remarks</th>
    <td class="field-container">
        <textarea name="service_fee_remarks" id="service_fee_remarks" class="form-control" cols="30" required
            onfocus="handleOnfocus('service_fee_remarks_message')" rows="2">{{ old('service_fee_remarks', $service_fee_remarks) }}</textarea>

        @if (isset($detailsChanges['service_fee_remarks']))
            {!! $detailsChanges['service_fee_remarks']['tooltip'] !!}
        @endif
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
                <div class="col-md-10 field-container">
                    <select onchange="handleOnchange(this, {{ $trn_fee_client_account_id }}, 'account_balance_trn')"
                        name="trn_fee_client_account" autofocus id="trn_fee_client_account" value=""
                        data-select2-id="trn_fee_client_account" class="form-control select2" style="max-width: 70%"
                        required>
                        <option value="">Choose Client Account for Transaction Fee</option>

                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}"
                                data-fee-account-id="{{ $trnFeeAccounts[$account->account_currency] ?? '' }}"
                                data-fee-currency-id="{{ $account->account_currency }}"
                                {{ $account->id == $trn_fee_client_account_id ? 'selected' : '' }}>
                                {{ $account->title . ' - ' . $account->account_number }}
                            </option>
                        @endforeach

                    </select>

                    @if (isset($detailsChanges['trn_fee_client_account_id']))
                        {!! $detailsChanges['trn_fee_client_account_id']['tooltip'] !!}
                    @endif
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
                <div class="col-md-10 field-container">
                    <input type="number" step="any" id="trn_fee" name="trn_fee"
                        onfocus="handleOnfocus('trn_fee_message')" value="{{ old('trn_fee', $trn_fee) }}" class="form-control"
                        required />

                    @if (isset($detailsChanges['trn_fee']))
                        {!! $detailsChanges['trn_fee']['tooltip'] !!}
                    @endif
                    <span id="trn_fee_message" style="color: red;"></span>
                </div>
                <div class="col-md-2 field-container">
                    <div class="form-group">
                        <div class="custom-control custom-switch mt-2">
                            <input type="checkbox" @if ($currencyExchange || $is_trn_fee_trn_hidden_for_client) checked @endif
                                class="custom-control-input" id="is_trn_fee_trn_hidden_for_client" value="1"
                                name="is_trn_fee_trn_hidden_for_client">
                            <label class="custom-control-label" for="is_trn_fee_trn_hidden_for_client">Hidden</label>
                        </div>
                    </div>

                    @if (isset($detailsChanges['is_trn_fee_trn_hidden_for_client']))
                        {!! $detailsChanges['is_trn_fee_trn_hidden_for_client']['tooltip'] !!}
                    @endif
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <th>Correspondent Charges Remarks</th>
        <td class="field-container">
            <textarea name="trn_fee_remarks" id="trn_fee_remarks" class="form-control" cols="30" required
                onfocus="handleOnfocus('trn_fee_remarks_message')" rows="2">{{ old('trn_fee_remarks', $trn_fee_remarks) }}</textarea>

            @if (isset($detailsChanges['trn_fee_remarks']))
                {!! $detailsChanges['trn_fee_remarks']['tooltip'] !!}
            @endif
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
            {{ $service_fee_client_account_id }}, 'account_balance_service')
        if ('Internal' !== '{{ $transactionRequest->transaction_nature }}') {
            handleOnchange(document.getElementById('trn_fee_client_account'),
                {{ $trn_fee_client_account_id }}, 'account_balance_trn')
        }

        // const tooltipCircle = document.querySelector('.tooltip-circle');

        // tooltipCircle.addEventListener('mouseenter', function() {
        //     const tooltip = tooltipCircle.querySelector('::after');
        //     const tooltipContainer = tooltipCircle.closest('.tooltip-container');
        //     const tooltipWidth = tooltip.offsetWidth;
        //     const tooltipCircleRect = tooltipCircle.getBoundingClientRect();
        //     const viewportWidth = window.innerWidth;

        //     // Check if the tooltip goes off the right side of the viewport
        //     if (tooltipCircleRect.right + tooltipWidth > viewportWidth) {
        //         // If it does, position the tooltip to the left of the circle
        //         tooltip.style.left = 'auto';
        //         tooltip.style.right = '0'; // Adjust this as needed
        //     } else {
        //         // Default position, center the tooltip above the circle
        //         tooltip.style.left = '50%';
        //         tooltip.style.right = 'auto';
        //     }
        // });
    })
</script>
