{{-- Recursive resubmit / retry chain tree branch. --}}
@php
    $node = $node ?? null;
@endphp
@if ($node && !empty($node['entry']))
    <div class="fb-retry-node">
        @include('fireblocks-monitoring::partials.retry-chain-tree-node', [
            'node' => $node,
            'entry' => $node['entry'],
            'layout' => $layout ?? 'modal',
        ])

        @if (!empty($node['children']))
            @php $parallel = ($node['branch_type'] ?? '') === 'parallel'; @endphp
            <div class="fb-retry-children-wrap">
                @if ($parallel)
                    <div class="fb-retry-parallel-label">Parallel retries from same parent</div>
                @endif
                <div class="fb-retry-children{{ $parallel ? ' fb-retry-children-parallel' : '' }}">
                    @foreach ($node['children'] as $child)
                        <div class="fb-retry-branch">
                            @include('fireblocks-monitoring::partials.retry-chain-tree', [
                                'node' => $child,
                                'layout' => $layout ?? 'modal',
                            ])
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
