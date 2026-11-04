{{-- @php
    if ($client->type == 'individual') {
        $email = $client->individual->email_address ?? '';
    } else {
        $email = $client->company->contact->email_address ?? '';
    }
    /*     if (isset($onboardingFeeRecord)) {
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
    } */
@endphp --}}
@if ($card)
    <div class="mt-6">
        <div class="col-sm-4 col-md-4 col-lg-4 col-xl-3">
            <div class="p-3 rounded vista-card overflow-hidden position-relative text-white mb-g"
                style="height: 150px; width: 100%; object-fit: cover;">
                <h3 class="display-4 d-block l-h-n m-0 fw-500">
                    <span class="m-0 l-h-n vista-card-title" style="font-size: 1.3rem;">{{ $card->cardHolderName }}</span>
                    <small class="m-0 l-h-n vista-card-title"
                        style="position: absolute; bottom: 10px; left: 10px; font-size: 1rem;">&nbsp;{{ $card->card_reference_number }}&nbsp;</small>
                </h3>
                <i class="position-absolute pos-right pos-bottom opacity-15 mb-n1 mr-n4" style="font-size: 6rem;"></i>
            </div>
            </a>
        </div>
    </div>
@else
    <form method="POST" name="onboarding-fee-create-payment-link" id="fingate-card-create"
        action="{{ url('/client-management/clients/fingate-card-create') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
        {{--  <input type="hidden" name="email_address" id="email_address" value="{{ $email }}"> --}}
        <div class="col-md-4">
            <button onclick="fingateCreateCard(); return false;" class="btn btn-primary mt-6 mb-3">Create Virtual
                Card</button>
        </div>
    </form>
@endif
{{--
<form method="POST" name="onboarding-fee-create-payment-link" id="fingate-find-card"
    action="{{ url('/client-management/clients/fingate-find-card') }}">
    @csrf
    <input type="hidden" name="card_id" id="card_id" value="{{ $card->card_id }}">
    <div class="col-md-4">
        <button onclick="fingateFindCard(); return false;" class="btn btn-primary mt-6 mb-3">find Card</button>
    </div>
</form> --}}

<script>
    function fingateCreateCard() {

        Swal.fire({
            title: "Are you sure?",
            text: "A new Card will be created and assigned to the client.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, send it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('fingate-card-create').submit()
            }
        });
    }

    function fingateFindCard() {

        Swal.fire({
            title: "Are you sure?",
            text: "find Card information",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, find it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('fingate-find-card').submit()
            }
        });
    }
</script>
