@php
    $sweepNavActive = $sweepNavActive ?? null;
@endphp
<div class="sweep-module-nav d-flex flex-wrap align-items-center mb-3" style="gap: .5rem;">
    <a href="{{ route('fireblocks.vault-sweeping.index') }}"
       class="btn btn-sm {{ $sweepNavActive === 'dashboard' ? 'btn-primary' : 'btn-outline-secondary' }}">
        <i class="fal fa-tachometer-alt mr-1"></i> Dashboard
    </a>
    <a href="{{ route('fireblocks.vault-sweeping.rules.index') }}"
       class="btn btn-sm {{ $sweepNavActive === 'rules' ? 'btn-primary' : 'btn-outline-secondary' }}">
        <i class="fal fa-list mr-1"></i> Manage Rules
    </a>
    @if (!empty($sweepNavExtra))
        {!! $sweepNavExtra !!}
    @endif
</div>
