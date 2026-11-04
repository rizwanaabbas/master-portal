{{--
    Fee Tier Group selector for the client edit screen (Digital Assets tab),
    placed as a column next to "Add New Crypto Currencies".

    It sits inside the account-currency <form>, so it cannot be a nested <form>.
    The Assign button builds and submits its own form via an inline handler
    (element ids are suffixed with the client id to avoid any collision).

    Expects: $client.
--}}
@if (\App\Helpers\Common::isModuleAvailable('FeeEngine'))
    @php
        $feeTierGroups = \App\Modules\FeeEngine\Models\FeeTierGroup::where('is_active', true)
            ->orderBy('name')
            ->get();
        $defaultGroup = \App\Modules\FeeEngine\Models\FeeTierGroup::where('is_default', true)->first();
        $selId = 'fee_tier_group_id_' . $client->id;
    @endphp

    <div class="col-md-6">
        <h5>Fee Tier Group</h5>
        <hr>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <p class="text-muted mb-2">
            Override the system default
            @if ($defaultGroup)
                (<strong>{{ $defaultGroup->name }}</strong>)
            @endif
            for this client.
        </p>

        <div class="form-group">
            <label class="form-label" for="{{ $selId }}">Fee Tier Group</label>
            <select id="{{ $selId }}" class="form-control select2 w-100" data-placeholder="Use default group">
                <option value="">— Use default group —</option>
                @foreach ($feeTierGroups as $group)
                    <option value="{{ $group->id }}" {{ (int) $client->fee_tier_group_id === (int) $group->id ? 'selected' : '' }}>
                        {{ $group->name }} ({{ $group->code }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <button type="button" class="btn btn-primary"
                onclick="(function(){
                    var f=document.createElement('form');
                    f.method='post';
                    f.action='{{ url('/client-management/clients/update/fee-tier-group') }}';
                    var d={'_token':'{{ csrf_token() }}','client_id':'{{ $client->id }}','fee_tier_group_id':document.getElementById('{{ $selId }}').value};
                    for(var k in d){var i=document.createElement('input');i.type='hidden';i.name=k;i.value=d[k];f.appendChild(i);}
                    document.body.appendChild(f);
                    f.submit();
                })();">
                <i class="fal fa-save"></i> Assign Fee Tier group
            </button>
        </div>
    </div>
@endif
