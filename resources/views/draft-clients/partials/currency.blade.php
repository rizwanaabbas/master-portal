{{-- <form method="post" action="{{ url('/client-profile/currency/store') }}" enctype="multipart/form-data">
    @csrf --}}
<input type="hidden" name="client_id" value="{{ $client->id }}">

@if ($client->type == 'individual')
    <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
@elseif ($client->type == 'company')
    <input type="hidden" name="company_id" value="{{ $client->company->id }}">
@endif
<div class="main">
    <div class="text">
        <h2>Currencies</h2>
        <p>Choose currencies for your account(s).</p>
    </div>

    @php
        $existing = $client->draftCurrencies->pluck('currency_id')->toArray();
        $cryptoCurrencies = [];
    @endphp

    @foreach ($currencies as $currency)
        @if (!$currency->is_crypto)
            <div class="row">
                <div class="col-md-12">
                    <div class="custom-control custom-checkbox custom-control-inline mb-3">
                        <input type="checkbox" @if (in_array($currency->id, $existing)) checked @endif
                            class="custom-control-input" value="{{ $currency->id }}" id="currency-{{ $currency->id }}"
                            name="currency[]">
                        <label class="custom-control-label" for="currency-{{ $currency->id }}">
                            <img src="{{ $currency->logo ? $currency->logo : '' }}" alt="{{ $currency->name }}"
                                height="17px" />&nbsp;
                            {{ $currency->name . ' (' . $currency->short . ')' }}
                        </label>
                    </div>
                </div>
            </div>
        @else
            @php $cryptoCurrencies[] = $currency; @endphp
        @endif
    @endforeach
    <span class="error-input" style="color:#fd1381;" role="alert"><strong></strong></span>
    {{--
            The following code has been commented-out on Operation Team's request.
            If they need this feature in the future, then just uncomment the following code.
            And the rest of the functionality is already there.
        --}}
    {{-- Show Crypto Currencies to client for creating crypto accounts. --}}
    {{--
        @if (count($cryptoCurrencies) > 0 && in_array('Crypto', $enabledModules))
            <div class="text">
                <h2>Digital Assets</h2>
                <p>Choose digital asset(s).</p>
            </div>

            @foreach ($cryptoCurrencies as $cryptoCurrency)
                @if ($cryptoCurrency->is_crypto)
                    <div class="row">
                        <div class="col-md-12">
                            <div class="custom-control custom-checkbox custom-control-inline mb-3">
                                <input type="checkbox" @if (in_array($cryptoCurrency->id, $existing)) checked @endif
                                    class="custom-control-input" value="{{ $cryptoCurrency->id }}"
                                    id="currency-{{ $cryptoCurrency->id }}" name="currency[]">
                                <label class="custom-control-label" for="currency-{{ $cryptoCurrency->id }}">
                                    <img src="{{ $cryptoCurrency->logo ? $cryptoCurrency->logo : '' }}"
                                        alt="{{ $cryptoCurrency->name }}" height="17px" />&nbsp;
                                    {{ $cryptoCurrency->name . ' (' . $cryptoCurrency->short . ')' }}
                                </label>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        @endif --}}

    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <hr>
                <button type="button" class="btn btn-outline-secondary back_button"><i class="fal fa-arrow-left"></i>
                    Back</button>
                <button type="button" class="btn btn-primary next_step_button">Next <i
                        class="fal fa-arrow-right"></i></button>
            </div>
        </div>
    </div>
</div>
{{-- </form>
 --}}
