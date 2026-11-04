{{-- Expects optional $fireblocksCryptoBeneficiary (CryptoBeneficiary|null) --}}
@if (!empty($fireblocksCryptoBeneficiary))
    @php
        $fbBenName = \App\Modules\Fireblocks\Support\TrFireblocksBeneficiaryResolver::displayName($fireblocksCryptoBeneficiary);
    @endphp
    <a href="#" class="fb-crypto-beneficiary-link font-weight-bold"
        data-beneficiary-id="{{ $fireblocksCryptoBeneficiary->id }}">
        #{{ $fireblocksCryptoBeneficiary->id }} — {{ $fbBenName }}
    </a>
@else
    <span class="text-muted">—</span>
@endif
