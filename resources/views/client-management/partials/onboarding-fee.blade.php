@php
    if ($client->type == 'individual') {
        $email = $client->individual->email_address ?? '';
    } else {
        $email = $client->company->contact->email_address ?? '';
    }
    if (isset($onboardingFeeRecord)) {
        switch ($onboardingFeeRecord->transaction_status) {
            case 'Success':
                $badgeClass = 'success';
                break;

            case 'Waiting For 3D Payment':
                $badgeClass = 'primary';
                break;

            default:
                $badgeClass = 'danger';
                break;
        }
    }
@endphp
@if (@isset($onboardingFeeRecord))
    @if ($onboardingFeeRecord->transaction_status)
        <table class="table table-bordered table-hover m-0 mb-3 shadow" id="data-grid">
            <thead class="thead-dark">

                <tr>
                    <th>Transaction Status</th>
                    <td>
                        <span
                            class="badge badge-{{ $badgeClass }}">{{ $onboardingFeeRecord->transaction_status }}</span>

                    </td>
                </tr>
                <tr>
                    <th>Transaction Amount</th>
                    <td>
                        <span>{{ $onboardingFeeRecord->transaction_amount }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Currency</th>
                    <td><span>{{ $onboardingFeeRecord->currency_code }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Order ID</th>
                    <td><span>{{ $onboardingFeeRecord->order_id }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Reference Number</th>
                    <td><span>{{ $onboardingFeeRecord->reference_number }}</span>
                    </td>
                </tr>
                <tr>
                    <th>Transaction Error Description</th>
                    <td><span>{{ $onboardingFeeRecord->transaction_error_description }}</span>
                    </td>
                </tr>
                {{--                 @if ($onboardingFeeRecord->transaction_status != 'Success' && $onboardingFeeRecord->transaction_status != 'Declined' && $onboardingFeeRecord->transaction_status != 'Refunded') --}}
                <tr>
                    <th>Check Transaction</th>
                    <td><button onclick="onboardingFeeFindTransaction(); return false;" class="btn btn-secondary ">Check
                            Transaction</button>
                    </td>
                </tr>
                {{--        @endif --}}

                <tr>
                    <th>Transaction Date</th>
                    <td>
                        <span>{{ $onboardingFeeRecord->transaction_date }}</span>
                    </td>
                </tr>
            </thead>
        </table>
    @else
        <div class="col-md-4">
            <div>Payment has been requested</div>
            <button onclick="onboardingFeeFindTransaction(); return false;" class="btn btn-secondary mt-3 mb-3">Find
                Transaction</button>
        </div>
    @endif
    @if ($onboardingFeeRecord->transaction_status == 'Success')
        <button onclick="refundPayment(); return false;" class="btn btn-danger mt-3 mb-3">Refund Payment
        </button>
    @endif
@endif

@if (!@isset($onboardingFeeRecord) || $onboardingFeeRecord->transaction_status != 'Success')
    <form method="POST" name="onboarding-fee-create-payment-link" id="onboarding_fee_create_payment_link"
        action="{{ url('/client-management/clients/onboarding-fee-create-payment-link') }}">
        @csrf
        <div class="col-md-4">
            <div class="form-group">
                <select name="currency_id" id="currency_id" data-select2-id="currency_id"
                    class="form-control select2 w-100" required>
                    <option value="">Choose Currency</option>
                    @foreach ($currencies as $currency)
                        @if ($client->type == 'individual' && $currency->onboarding_fee_individual != 0)
                            <option value="{{ $currency->id }}">{{ $currency->short }}</option>
                        @elseif($client->type == 'company' && $currency->onboarding_fee_company != 0)
                            <option value="{{ $currency->id }}">{{ $currency->short }}</option>
                        @endif
                    @endforeach
                </select>
                @error('currency_id')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
        </div>
        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
        <input type="hidden" name="email_address" id="email_address" value="{{ $email }}">
        <div class="col-md-4">
            <button onclick="onboardingFeeRequest(); return false;" class="btn btn-primary mt-3 mb-3">Send Payment
                Link</button>
        </div>
    </form>
@endif



<form method="POST" name="onboarding-fee-find-transaction" id="onboarding_fee_find_transaction"
    action="{{ url('/client-management/clients/request-onboarding-fee-find-transaction') }}">
    @csrf
    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
</form>

<form method="POST" name="refund-payment" id="refund_payment"
    action="{{ url('/client-management/clients/request-onboarding-fee-refund-payment') }}">
    @csrf
    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
</form>

<script>
    function onboardingFeeRequest() {

        Swal.fire({
            title: "Are you sure?",
            text: "Request payment link will be sent to the client.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, send it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('onboarding_fee_create_payment_link').submit()
            }
        });
    }

    function onboardingFeeFindTransaction() {

        Swal.fire({
            title: "Are you sure?",
            text: "Find Transaction.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, find it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('onboarding_fee_find_transaction').submit()
            }
        });
    }

    function refundPayment() {

        Swal.fire({
            title: "Are you sure?",
            text: "payment will be refunded.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, refund it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('refund_payment').submit()
            }
        });
    }
</script>
