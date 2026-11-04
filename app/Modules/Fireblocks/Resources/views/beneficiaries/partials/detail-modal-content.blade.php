@php
    $b = $beneficiary;
@endphp
<table class="table table-sm table-striped table-bordered mb-0">
    <tr>
        <th style="width:35%">Beneficiary ID</th>
        <td>{{ $b->id }}</td>
    </tr>
    <tr>
        <th>Label</th>
        <td>{{ $b->label ?: '—' }}</td>
    </tr>
    <tr>
        <th>Account</th>
        <td>{{ optional($b->account)->title }} — {{ optional($b->account)->account_number }}</td>
    </tr>
    <tr>
        <th>Network</th>
        <td>{{ optional($b->network)->name }} ({{ optional($b->network)->code }})</td>
    </tr>
    <tr>
        <th>Wallet address</th>
        <td><code style="word-break:break-all;">{{ $b->wallet_address }}</code></td>
    </tr>
    @if ($b->destination_tag)
        <tr>
            <th>Destination tag</th>
            <td>{{ $b->destination_tag }}</td>
        </tr>
    @endif
    <tr>
        <th>Active</th>
        <td>{{ $b->is_active ? 'Yes' : 'No' }}</td>
    </tr>
    @if (!empty($travelRulesEnabled))
        <tr>
            <th>Type</th>
            <td>{{ $b->beneficiary_type ?: '—' }}</td>
        </tr>
        @if (strcasecmp((string) $b->beneficiary_type, 'INDIVIDUAL') === 0)
            <tr>
                <th>Name</th>
                <td>{{ trim($b->beneficiary_first_name . ' ' . $b->beneficiary_last_name) ?: '—' }}</td>
            </tr>
        @elseif (strcasecmp((string) $b->beneficiary_type, 'BUSINESS') === 0)
            <tr>
                <th>Legal name</th>
                <td>{{ $b->beneficiary_legal_name ?: '—' }}</td>
            </tr>
        @endif
        <tr>
            <th>Wallet type</th>
            <td>{{ $b->wallet_type ?: '—' }}</td>
        </tr>
        @if ($b->beneficiary_vasp_name || $b->beneficiary_vasp_did)
            <tr>
                <th>VASP</th>
                <td>{{ $b->beneficiary_vasp_name ?: $b->beneficiary_vasp_did }}</td>
            </tr>
        @endif
    @endif
    @if ($b->relationLoaded('compliance') && $b->compliance)
        <tr>
            <th>Chainalysis</th>
            <td>{{ $b->compliance->status ?? '—' }} @if(!empty($b->compliance->risk))<small class="text-muted">({{ $b->compliance->risk }})</small>@endif</td>
        </tr>
    @endif
</table>
<p class="mb-0 mt-2">
    <a href="{{ url('/fireblocks/beneficiaries/' . $b->id . '/edit') }}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer">
        <i class="fal fa-eye"></i> Open full beneficiary record
    </a>
</p>
