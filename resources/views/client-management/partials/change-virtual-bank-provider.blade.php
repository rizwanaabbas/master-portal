<div class="modal fade" id="virtual-bank-provider-modal" tabindex="-1" role="dialog"
    aria-labelledby="virtualBankProviderModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow">

            {{-- Header --}}
            <div class="modal-header bg-light">
                <div>
                    <h5 class="modal-title mb-1">Account Settings</h5>
                    <small class="text-muted">
                        Switch the virtual bank provider for this account
                    </small>
                </div>

                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>

            <form method="POST" action="{{ url('/client-management/accounts/update/virtual-bank-provider') }}">
                @csrf

                <input type="hidden" name="account_id" id="modal_account_id">

                <div class="modal-body">

                    <label class="font-weight-bold mb-3 d-block">
                        Select Virtual Bank Provider
                    </label>

                    <div class="row">

                        @foreach ($virtual_providers as $provider)
                            @php
                                $providerValue = strtolower($provider);
                            @endphp

                            <div class="col-md-4 mb-3">

                                <label id="provider-{{ $providerValue }}" data-provider="{{ $providerValue }}"
                                    onclick="changeVirtualProvider('{{ $providerValue }}')"
                                    class="provider-card w-100 mb-0">

                                    <input type="radio" name="virtual_bank_provider" value="{{ $providerValue }}"
                                        class="provider-radio">

                                    <div class="card provider-inner border-0">
                                        <div class="card-body d-flex align-items-center">

                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 font-weight-semibold">
                                                    {{ $provider }}
                                                </h6>
                                            </div>

                                            <div class="provider-check check-circle">
                                                <i class="fal fa-check"></i>
                                            </div>

                                        </div>
                                    </div>

                                </label>

                            </div>
                        @endforeach

                    </div>

                </div>

                <div class="modal-footer">

                    <button onclick="$('#virtual-bank-provider-modal').modal('hide');" type="button"
                        class="btn btn-light border" data-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        Update Provider
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

<style>
    .provider-card {
        cursor: pointer;
        display: block;
    }

    .provider-radio {
        display: none;
    }

    .provider-inner {
        border-radius: 14px;
        transition: all 0.2s ease;
        border: 1px solid #dee2e6;
    }

    .provider-card:hover .provider-inner {
        border-color: var(--theme-primary) !important;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
    }

    .provider-card.active .provider-inner {
        border: 2px solid var(--theme-primary) !important;
        background: rgba(var(--theme-primary-rgb), 0.06);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
    }

    /* Checkbox UI */
    .provider-check {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .check-circle {
        width: 24px;
        height: 24px;
        border-radius: 50%;
        border: 2px solid #d0d5dd;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #fff;
        transition: all 0.2s ease;
    }

    .check-circle i {
        font-size: 11px;
        color: #fff;
        opacity: 0;
        transition: all 0.2s ease;
    }

    /* Selected */
    .provider-card.active .check-circle {
        background: var(--theme-primary);
        border-color: var(--theme-primary);
        transform: scale(1.05);
    }

    .provider-card.active .check-circle i {
        opacity: 1;
    }

    /* Hover */
    .provider-card:hover .check-circle {
        border-color: var(--theme-primary);
    }
</style>

<script>
    /**
     * MASTER CONTROLLER
     * Use this function to control BOTH UI + form state
     */
    function changeVirtualProvider(providerValue) {
        providerValue = providerValue.toString().toLowerCase();

        $('.provider-card').removeClass('active');
        $('.provider-radio').prop('checked', false);

        const selector = `#provider-${providerValue}`;

        $(selector).addClass('active');
        $(`.provider-radio[value="${providerValue}"]`).prop('checked', true);
    }

    /**
     * Open modal
     */
    document.getElementById("open-virtual-bank-provider-modal")?.addEventListener("click", function() {
        const modalElement = document.getElementById("virtual-bank-provider-modal");

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);

        modal.show();
    });
</script>
