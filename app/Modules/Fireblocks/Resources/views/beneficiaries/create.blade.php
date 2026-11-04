@extends('layouts.cp')
@section('title', 'Add Fireblocks Beneficiary')

@push('css')
<style>
    .font-monospace {
        font-family: SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        letter-spacing: 0.02em;
    }
    .section-title {
        font-size: 0.95rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #6c757d;
        margin-bottom: 1.25rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .section-title::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #e9ecef;
        margin-left: 0.5rem;
    }
    .form-group label {
        font-weight: 500;
        margin-bottom: 0.35rem;
        font-size: 0.875rem;
    }
    .custom-card {
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        border: 1px solid #e2e8f0;
    }
</style>
@endpush

@section('content')
    <div class="subheader mb-4">
        <h1 class="subheader-title font-weight-bold">
            Add Fireblocks Beneficiary
            <small class="d-block text-muted fs-sm mt-1">Whitelist and register external crypto addresses for outgoing transfers</small>
        </h1>
        <a href="{{ url('/fireblocks/beneficiaries') }}" class="btn btn-outline-secondary ml-auto shadow-none">
            <i class="fal fa-arrow-alt-left mr-1"></i> Back to List
        </a>
    </div>

    <div class="panel custom-card">
        <form method="post" action="{{ url('/fireblocks/beneficiaries') }}" id="beneficiary-form" novalidate>
            @csrf
            <div class="panel-container show">
                <div class="panel-content p-4">
                    @include('common.error-message')

                    <!-- Section: Wallet & Network Setup -->
                    <div class="section-title">
                        <i class="fal fa-wallet text-primary"></i> Account & Routing
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="account_id">Source Crypto Account <span class="text-danger">*</span></label>
                                <select name="account_id" id="account_id" class="form-control select2 w-100" required>
                                    <option value="">Choose Crypto Account</option>
                                    @foreach ($cryptoAccounts as $acc)
                                        <option value="{{ $acc->id }}" {{ old('account_id') == $acc->id ? 'selected' : '' }}>
                                            {{ $acc->title }} ({{ $acc->account_number }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="network_id">Network / Protocol <span class="text-danger">*</span></label>
                                <select name="network_id" id="network_id" class="form-control select2 w-100" required disabled>
                                    <option value="">Select Account First</option>
                                </select>
                                <small class="text-muted d-block mt-1">Options populate based on the selected crypto account.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Destination Details -->
                    <div class="section-title mt-3">
                        <i class="fal fa-address-card text-primary"></i> Beneficiary Address
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="label">Beneficiary Label / Alias <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="label" id="label" 
                                       placeholder="e.g. Binance Hot Wallet, Jane Doe Cold Storage"
                                       value="{{ old('label') }}" maxlength="100" required />
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="form-group">
                                <label class="form-label" for="asset_type">Asset Type <span class="text-danger">*</span></label>
                                <select name="asset_type" id="asset_type" class="form-control w-100 select2" required>
                                    @foreach ($asset_types as $type)
                                        <option value="{{ $type }}" {{ old('asset_type', 'crypto') == $type ? 'selected' : '' }}>
                                            {{ strtoupper($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-8 mb-3" id="wallet_address_col">
                            <div class="form-group">
                                <label class="form-label" for="wallet_address">Destination Wallet Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace" name="wallet_address" id="wallet_address" 
                                       placeholder="Enter 0x... or network address"
                                       value="{{ old('wallet_address') }}" maxlength="255" required autocomplete="off" />
                            </div>
                        </div>

                        <div class="col-md-4 mb-3" id="destination_tag_group" style="display:none;">
                            <div class="form-group">
                                <label class="form-label" for="destination_tag">
                                    Destination Tag / Memo <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control font-monospace" name="destination_tag" id="destination_tag" 
                                       value="{{ old('destination_tag') }}" maxlength="100" autocomplete="off" 
                                       placeholder="Tag / Memo string" />
                                <small class="text-muted" id="tag_hint">Required for transactions on this network.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Travel Rule (if applicable) -->
                    @if (!empty($travelRulesEnabled))
                        <div class="mt-3">
                            @include('fireblocks::beneficiaries._travel-rule-fields')
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="d-flex justify-content-end align-items-center gap-2 mt-4 pt-3 border-top">
                        <a href="{{ url('/fireblocks/beneficiaries') }}" class="btn btn-outline-secondary mr-2">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4" id="submit-btn">
                            <i class="fal fa-check-circle mr-1"></i> Create Beneficiary
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        const oldNetworkId = "{{ old('network_id') }}";
        const controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {
            $('body').on('focus', "#dob", function() {
                if (!$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        format: 'dd/mm/yyyy',
                        todayHighlight: true,
                        templates: controls,
                        autoclose: true,
                        orientation: "bottom left",
                        endDate: "{{ \Carbon\Carbon::today()->format('dd/mm/yyyy') }}"
                    });
                }
            });
        })

        function loadNetworks(accountId, selectedNetworkId = null) {
            const $networkSelect = $('#network_id');

            if (!accountId) {
                $networkSelect.html('<option value="">Select Account First</option>')
                              .prop('disabled', true)
                              .trigger('change');
                return;
            }

            $networkSelect.html('<option value="">Loading networks...</option>')
                          .prop('disabled', true)
                          .trigger('change');

            $.post("{{ url('/fireblocks/currency-networks') }}", {
                account_id: accountId,
                _token: "{{ csrf_token() }}"
            })
            .done(function(networks) {
                let options = '<option value="">Select Network</option>';
                (networks || []).forEach(function(n) {
                    const isSelected = selectedNetworkId && selectedNetworkId == n.id ? 'selected' : '';
                    options += `<option value="${n.id}" data-requires-tag="${n.requires_tag ? 1 : 0}" ${isSelected}>${n.name} (${n.code})</option>`;
                });
                $networkSelect.html(options).prop('disabled', false).trigger('change');
            })
            .fail(function() {
                $networkSelect.html('<option value="">Failed to load networks</option>').trigger('change');
                Swal.fire('Error', 'Unable to fetch networks for this account.', 'error');
            });
        }

        function networkNeedsTag() {
            return $('#network_id').find(':selected').data('requires-tag') == 1;
        }

        function toggleDestinationTag() {
            const needsTag = networkNeedsTag();
            const $tagGroup = $('#destination_tag_group');
            const $tagInput = $('#destination_tag');
            const $addressCol = $('#wallet_address_col');

            if (needsTag) {
                $addressCol.removeClass('col-md-12').addClass('col-md-8');
                $tagGroup.fadeIn(200);
                $tagInput.prop('required', true);
            } else {
                $tagGroup.hide();
                $tagInput.prop('required', false).val('');
                $addressCol.removeClass('col-md-8').addClass('col-md-12');
            }
        }

        $(document).ready(function() {
            $('.select2').select2({ width: '100%' });

            $('#account_id').on('change', function() {
                loadNetworks($(this).val());
            });

            $('#network_id').on('change', toggleDestinationTag);

            @if (old('account_id'))
                loadNetworks("{{ old('account_id') }}", oldNetworkId);
            @endif

            // Form Validation & Submission
            $("#beneficiary-form").on('submit', function(e) {
                if ($('#wallet_type').val() === 'SELF_HOSTED' && !$('#is_ownership_declaration').is(':checked')) {
                    e.preventDefault();
                    Swal.fire('Ownership Declaration Required', 'Please confirm ownership declaration for Self-Hosted wallets.', 'warning');
                    return false;
                }

                if (networkNeedsTag() && $('#destination_tag').val().trim() === '') {
                    e.preventDefault();
                    Swal.fire('Tag / Memo Missing', 'A destination tag or memo is mandatory for this network.', 'warning');
                    $('#destination_tag').focus();
                    return false;
                }
            });
        });
    </script>

    @if (!empty($travelRulesEnabled))
        @include('fireblocks::beneficiaries._travel-rule-scripts')
    @endif
@endsection