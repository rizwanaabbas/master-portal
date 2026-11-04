{{-- Resubmit chain panel wrapper (shared). Expects $detail['retry_chain_tree']. --}}
@if (!empty($detail['retry_chain_tree']) && ($detail['retry_chain_tree']['total'] ?? 0) > 1)
    @php $layout = $layout ?? 'modal'; @endphp
    <h5 class="mt-3 mb-2">
        Resubmit Chain
        <small class="text-muted">({{ $detail['retry_chain_tree']['total'] }} transactions)</small>
    </h5>
    <div class="fb-retry-chain-panel">
        <div class="fb-retry-chain-legend">
            <span><span class="dot" style="background:#4e73df"></span> Original</span>
            <span><span class="dot" style="background:#36b9cc"></span> Current</span>
            <span><span class="dot" style="background:#e74a3b"></span> Failed branch</span>
            <span><i class="fal fa-code-branch"></i> Parallel = same parent</span>
        </div>
        <div class="fb-retry-chain-tree">
            @include('fireblocks-monitoring::partials.retry-chain-tree', [
                'node' => $detail['retry_chain_tree']['tree'],
                'layout' => $layout ?? 'modal',
            ])
        </div>
    </div>
@endif
