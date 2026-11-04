{{-- Boost fee level modal + generic confirmation modal --}}
@php
    $defaultFeeLevel = strtoupper($defaultFeeLevel ?? 'HIGH');
    $defaultGasPrice = $defaultGasPrice ?? '';
@endphp

<div class="modal fade fb-boost-modal" id="fb-boost-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="fal fa-rocket"></i> Boost Transaction (RBF)</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3">Select the Fireblocks <code>feeLevel</code> for this boost. Higher levels increase the chance of confirmation but cost more.</p>
                <div class="form-group">
                    <label class="form-label d-block">Fee Level</label>
                    @foreach (['LOW' => 'LOW — lowest network fee', 'MEDIUM' => 'MEDIUM — balanced', 'HIGH' => 'HIGH — recommended for stuck transactions'] as $level => $label)
                        <div class="custom-control custom-radio mb-2">
                            <input type="radio" class="custom-control-input fb-boost-fee-level" name="fb_boost_fee_level"
                                id="fb_boost_fee_{{ strtolower($level) }}" value="{{ $level }}"
                                {{ $defaultFeeLevel === $level ? 'checked' : '' }}>
                            <label class="custom-control-label" for="fb_boost_fee_{{ strtolower($level) }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
                <div class="form-group mb-0">
                    <label class="form-label" for="fb_boost_gas_price">Optional Gas Price Override (EVM, gwei)</label>
                    <input type="text" class="form-control" id="fb_boost_gas_price" placeholder="Leave empty to use fee level"
                        value="{{ $defaultGasPrice }}">
                    <small class="form-text text-muted">Only for EVM chains. Overrides automatic fee estimation when set.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="fb-boost-modal-confirm">
                    <i class="fal fa-rocket"></i> Continue
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="fb-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="fb-confirm-modal-title">Confirm Action</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body" id="fb-confirm-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="fb-confirm-modal-proceed">Confirm</button>
            </div>
        </div>
    </div>
</div>
