@extends('layouts.cp')
@section('title', 'Beneficiary Details - Finance Management')

@section('content')

     @php
        $beneficiary_type_map = [
            'RETAIL' => 'individual',
            'CORPORATE' => 'company',
            'individual' => 'individual',
            'company' => 'company',
        ];
        $beneficiary_types = ['individual', 'company'];

        $routing_number = null;
        $sort_code = $ben->sort_code ?? null;        
        $virtual_bank_beneficiary = $ben->virtualBankBeneficiary ? $ben->virtualBankBeneficiary : null;
        
        $sort_code = null;
        $routing_number = null;

        if (!empty($virtual_bank_beneficiary) && !empty($ben->virtualBankBeneficiary->bank_routing_codes)) {
            $provider = strtolower($virtual_bank_beneficiary->provider_name);

            if ($provider === 'openpayd') {
                $sort_code = $virtual_bank_beneficiary->bank_routing_codes[0]['routingCodeValue'] ?? null;
            } elseif (in_array($provider, ['lorum', 'zenus'])) {
                $codes = json_decode($virtual_bank_beneficiary->bank_routing_codes);
                $firstCode = is_array($codes) ? ($codes[0] ?? null) : $codes;
                $routing_number = $firstCode->routingCodeValue ?? null;
            }

            $payment_types = json_encode($virtual_bank_beneficiary->payment_types);    
        }
    @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Finance Management</span>
        </h1>

        <a href="{{ url('finance-management/beneficiaries/') }}" class="btn btn-primary mr-3">
            <i class="fal fa-arrow-alt-left"></i> Go Back 
        </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false" data-panel-toolbar="false">
    <div class="panel-hdr">
        <h2>
            Edit Beneficiary
        </h2>
    </div>

    <div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')
                
                <form method="post" action="{{ url('finance-management/beneficiaries/update/' . $ben->id ) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        
                        <div class="col-md-8">

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id', $ben->client_id) }}">

                                        <label class="form-label" for="account_currency">Account Currency</label>

                                        <select name="account_currency"
                                            id="account_currency"
                                            class="form-control select2 w-100"
                                            required>

                                            <option value="">Choose Currency</option>
                                            
                                            @if (!empty($ben->currency->short))
                                                @foreach ($currencies as $currency)
                                                    <option value="{{ $currency->id }}"
                                                        {{ old('account_currency', $ben->currency->short) == $currency->short ? 'selected' : '' }}>
                                                        {{ $currency->name }} ({{ $currency->short }})
                                                    </option>
                                                @endforeach
                                            @else
                                                @foreach ($currencies as $currency)
                                                    <option value="{{ $currency->id }}">
                                                        {{ $currency->name }} ({{ $currency->short }})
                                                    </option>
                                                @endforeach
                                            @endif

                                        </select>

                                        @error('account_currency')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_country">Beneficiary Country</label>

                                        <select name="beneficiary_country"
                                            id="beneficiary_country"
                                            class="form-control select2 w-100"
                                            required>

                                            <option value="">Choose Country</option>

                                            @if (!empty($ben->beneficiary_country))
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
                                                        {{ old('beneficiary_country', $ben->beneficiary_country) == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}">
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            @endif

                                        </select>

                                        @error('beneficiary_country')
                                            <span class="invalid-feedback d-block" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-4 ">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_account_country">Bank Account Country</label>
                                        <select name="bank_account_country" autofocus id="bank_account_country"
                                            value="" data-select2-id="bank_account_country"
                                            class="form-control select2 w-100" required>
                                            <option value="">Choose Country</option>
                                            
                                            @if (!empty($ben->bank_account_country))    
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}"
                                                        {{ old('bank_account_country', $ben->bank_account_country) == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            @else
                                                @foreach ($countries as $country)
                                                    <option value="{{ $country->id }}">
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            @endif

                                        </select>
                                        @error('bank_account_country')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_type">Beneficiary Type</label>
                                        <select name="beneficiary_type" autofocus id="beneficiary_type" value=""
                                            data-select2-id="beneficiary_type" class="form-control select2 w-100" required>
                                            <option value="">Choose beneficiary type</option>

                                            @if (!empty($beneficiary_type_map[$ben->beneficiary_type]))
                                                @foreach ($beneficiary_types as $type)
                                                    <option value="{{ $type }}"
                                                        {{ old('beneficiary_type', $beneficiary_type_map[$ben->beneficiary_type]) == $type ? 'selected' : '' }}>
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            @else
                                                 @foreach ($beneficiary_types as $type)
                                                    <option value="{{ $type }}">
                                                        {{ $type }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                        @error('beneficiary_type')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" id="name" name="name" value="{{ old('name', $ben->name) }}"
                                            class="form-control" required>
                                        @error('name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-4 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="account_number">Account Number</label>
                                        <input type="number" min="1" step="any" id="account_number"
                                            name="account_number" value="{{ old('account_number', $ben->account_number) }}"
                                            class="form-control" required>
                                        @error('account_number')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-8 mt-4" id="iban">
                                    <div class="form-group">
                                        <label class="form-label" for="iban">IBAN Number</label>
                                        <input type="text" id="iban" name="iban"
                                            value="{{ old('iban', $ben->iban) }}" class="form-control">
                                        @error('iban')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_state">Beneficiary State/Region</label>
                                        <input type="text" id="beneficiary_state" name="beneficiary_state" value="{{ old('beneficiary_state', $ben->beneficiary_state) }}"
                                            class="form-control" required>
                                        @error('beneficiary_state')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="beneficiary_city">Beneficiary City</label>
                                        <input type="text" id="beneficiary_city" name="beneficiary_city" value="{{ old('beneficiary_city', $ben->beneficiary_city) }}"
                                            class="form-control" required>
                                        @error('beneficiary_city')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="col-md-12 mt-4">
                                    <div class="form-group">
                                        <label class="form-label" for="address">Beneficiary Address</label>
                                        <textarea required class="form-control" name="address" id="address" cols="30" rows="2"
                                            placeholder="Please provide the beneficiary address.">{{ old('address', $ben->address) }}</textarea>
                                    </div>
                                </div>

                            </div>
                        </div>
                        


                        <div class="col-md-4" style="border-left: 1px solid lightgray;">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_name">Bank Name</label>
                                        <input type="text" id="bank_name" name="bank_name"
                                            value="{{ old('bank_name', $ben->bank_name) }}" class="form-control" required>
                                        @error('bank_name')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_city">Bank City</label>
                                        <input type="text" id="bank_city" name="bank_city"
                                            value="{{ old('bank_city', $ben->bank_city) }}" class="form-control">
                                        @error('bank_city')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="postal_code">Postal/Zip Code</label>
                                        <input type="text" id="postal_code" name="postal_code"
                                            value="{{ old('postal_code', $ben->postal_code) }}" class="form-control">
                                        @error('postal_code')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="swift_code">Swift Code</label>
                                        <input type="text" id="swift_code" name="swift_code"
                                            value="{{ old('swift_code', $ben->swift_code) }}" class="form-control">
                                        @error('swift_code')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                                
                                {{-- @if ($sort_code !== null) --}}
                                    <div class="col-md-6" id="sort_code_dev">
                                        <div class="form-group">
                                            <label class="form-label" for="sort_code">Sort Code</label>
                                            <input type="text" id="sort_code" name="sort_code"
                                                value="{{ old('sort_code', $ben->sort_code) }}" class="form-control">
                                            @error('sort_code')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                {{-- @elseif ($routing_number !== null) --}}
                                    <div class="col-md-6" id="routing_number_dev">
                                        <div class="form-group">
                                            <label class="form-label" for="routing_number">Routing Number</label>
                                            <input type="text" id="routing_number" name="routing_number"
                                                value="{{ old('routing_number', $routing_number) }}" class="form-control">
                                            @error('routing_number')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                {{-- @endif --}}
                                
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="bank_address">Bank Address</label>
                                        <textarea required class="form-control" name="bank_address" id="bank_address" cols="30" rows="2"
                                            placeholder="Please provide the bank address.">{{ old('bank_address', $ben->bank_address) }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                <button type="submit" id="add-beneficiary-button" class="btn btn-primary d-flex" type="button">
                                    <span id="submit-text">Submit</span>
                                    <span 
                                        id="spinner" 
                                        style="display: none;" 
                                        class="spinner-border spinner-border-sm ml-2" 
                                        role="status"
                                        aria-hidden="true"
                                    >
                                    </span>
                                </button>

                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
            });


            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                console.log(theadColor);
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });
        });
        
        function linkToVirtualBank(ben_id) {

            Swal.fire({
                title: "Are you sure?",
                text: "You want to link this beneficiary to Virtual Bank?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, link it!"
            }).then(function(result) {
                if (result.value) {
                    window.location = "{{ url('/finance-management/beneficiaries/link-to-virtual-bank') }}/" + ben_id;
                }
            });
        }

        function rejectBeneficiary() {
            $("#reject-modal").modal('show');
        }
        
        function toggleBankFields(currency_id) {
            currencies = @json($currencies);
            currency = currencies.find(currency => currency.id == currency_id);

            // hide both first
            $('#sort_code_dev').hide();
            $('#routing_number_dev').hide();

            if (currency && currency.short === 'GBP') {
                $('#sort_code_dev').show();
            } else if (currency && currency.short === 'USD') {
                $('#routing_number_dev').show();
            }
        }

        $(document).ready(function () {

            // on page load (edit case)
            toggleBankFields($('#account_currency').val());

            // on change
            $('#account_currency').on('change', function () {
                toggleBankFields($(this).val());
            });

        });

    </script>

@stop
