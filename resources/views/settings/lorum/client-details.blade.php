@extends('layouts.cp')
@section('title', 'Lorum Client Details')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Lorum Client Details</span>
        </h1>
    </div>

    {{-- ============================= --}}
    {{-- 🔹 LORUM CLIENT DATA --}}
    {{-- ============================= --}}
    <div class="panel">
        <div class="panel-hdr">
            <h2>Lorum Data</h2>
        </div>

        @php
            $customersId = $customers['id'];
            $localClientId = (int) ($customers['reference'] ?? 0);
            $companyName = $ownCompany ? $ownCompany->short_name : 'our';
        @endphp

        <div class="panel-container show mb-3">
            <div class="panel-content">

                @include('common.error-message')

                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td><strong>Client ID</strong></td>
                            <td>{{ $customers['id'] }}</td>
                        </tr>

                        <tr>
                            <td><strong>Reference (Local ID)</strong></td>
                            <td>{{ $customers['reference'] ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td><strong>Name</strong></td>
                            <td>{{ $customers['display_name'] ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td><strong>Business Name</strong></td>
                            <td>{{ $customers['business']['business_name'] ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td><strong>Country</strong></td>
                            <td>{{ $customers['address']['country_code'] ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td><strong>Approval Status</strong></td>
                            <td>
                                <span class="badge badge-success">{{ $customers['approval_status'] ?? '-' }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Client Synced Status</strong></td>
                            <td>
                                @if (!$is_client_synced)
                                    <span class="badge badge-warning">Out of Sync</span>
                                @else
                                    <span class="badge badge-success">Synced</span>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Relationship Type</strong></td>
                            <td>{{ $customers['relationship_type'] ?? '-' }}</td>
                        </tr>

                        <tr>
                            <td><strong>Documents</strong></td>
                            <td>
                                @if (!empty($customers['documents']))
                                    <ul class="list-unstyled mb-0">
                                        @foreach ($customers['documents'] as $doc)
                                            <li class="mb-2">
                                                <strong>{{ ucfirst(str_replace('_', ' ', $doc['document_type'])) }}</strong>
                                                <br>

                                                <small>Reference: {{ $doc['reference'] }}</small>

                                                {{-- TODO: Create View and Re-Upload features --}}
                                                {{-- <div class="mt-1">
                                                    <a href="{{ url($doc['id']) }}" class="btn btn-sm btn-primary">
                                                        View
                                                    </a>

                                                    <a href="{{ url($doc['id']) }}" class="btn btn-sm btn-warning">
                                                        Re-upload
                                                    </a>
                                                </div> --}}
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted">No documents found</span>
                                @endif
                            </td>
                        </tr>

                        @if (!empty($local_client))
                            <tr>
                                <td><strong>View Client</strong></td>
                                <td>
                                    <a href="{{ url('/client-management/clients/' . $local_client->type . '/edit/' . $local_client->id) }}"
                                        type="button" class="btn btn-sm btn-primary">
                                        View Client
                                    </a>
                                </td>
                            </tr>
                        @endif

                    </tbody>
                </table>
            </div>
        </div>
    </div>


    {{-- ============================= --}}
    {{-- 🔹 LOCAL (CIB) DATA --}}
    {{-- ============================= --}}
    <div class="panel">
        <div class="panel-hdr">
            <h2>Local ({{ $companyName }}) Data</h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content">

                @if (!empty($local_client))
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td><strong>Client Name</strong></td>
                                <td>{{ $local_client->name }}</td>
                            </tr>

                            <tr>
                                <td><strong>Client ID</strong></td>
                                <td>{{ $local_client->id }}</td>
                            </tr>

                            <tr>
                                <td><strong>Client Type</strong></td>
                                <td>{{ $local_client->type }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <hr class="mt-6">
                    {{-- 🔘 Sync Actions --}}
                    <div class="mb-3 row">
                        <form method="POST" action="{{ url('/settings/sync-lorum-client') }}" id="sync-client">
                            @csrf
                            <input type="hidden" name="lorum_customer_id" value="{{ $customersId }}">
                            <input type="hidden" name="client_id" value="{{ $localClientId }}">
                        </form>

                        <button onclick="syncClient()" class="btn btn-primary mx-3">
                            Sync Client
                        </button>
                    </div>
                @else
                    <div class="alert alert-danger">
                        Linked client does NOT exists in local database.
                    </div>

                    {{-- 🔘 Create + Sync --}}
                    <form method="POST" id="sync-selected-client" action="{{ url('/settings/sync-lorum-client') }}">
                        @csrf
                        <input type="hidden" name="lorum_customer_id" value="{{ $customersId }}">

                        <div class="form-group">
                            <label>Select Local Client</label>
                            <select id="selected_client" name="client_id" class="form-control select2" required>
                                <option value="">Select Client </option>
                                @foreach ($clients as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <hr class="mt-6">

                        <button class="btn btn-primary mt-2" onclick="syncWithSelectedClient()">
                            Create & Sync Client
                        </button>
                    </form>
                @endif

            </div>
        </div>
    </div>

    {{-- ============================= --}}
    {{-- 🔹 LORUM ACCOUNTS --}}
    {{-- ============================= --}}
    <div class="panel">
        <div class="panel-hdr">
            <h2>Lorum Accounts</h2>
        </div>

        <div class="panel-container show mb-3">
            @if (!empty($virtual_accounts))
                <div class="panel-content">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Account ID</th>
                                <th>External ID (Local)</th>
                                <th>Name</th>
                                <th>Currency</th>
                                <th>IBAN / Account</th>
                                <th>Status</th>
                                <th>Account Confirmation</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($virtual_accounts as $account)
                                @php
                                    $details = $account['default_details'] ?? [];

                                    if (isset($details['iban'])) {
                                        $accountDetails = $details['iban'];
                                    } elseif (isset($details['wire'])) {
                                        $accountDetails =
                                            'Account: ' .
                                            $details['wire']['account_number'] .
                                            ' | Routing: ' .
                                            $details['wire']['routing_number'];
                                    } else {
                                        $accountDetails = '-';
                                    }
                                @endphp

                                <tr class="{{ $account['is_synced'] ? '' : 'table-warning' }}">

                                    <td>{{ $account['id'] }}</td>

                                    <td>{{ $account['external_id'] }}</td>

                                    <td>{{ $account['account_name'] ?? '-' }}</td>

                                    <td>
                                        <span class="badge badge-info">
                                            {{ $account['currency'] }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $accountDetails }}
                                    </td>

                                    <td>
                                        @if ($account['is_synced'])
                                            <span class="badge badge-success">Synced</span>
                                        @else
                                            <span class="badge badge-warning">Not Synced</span>
                                        @endif
                                    </td>

                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="downloadAccountConfirmation('{{ $account['id'] }}')">
                                            Account Confirmation Letter
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <hr class="mt-6">
                    <form method="POST" action="{{ url('/settings/sync-lorum-accounts') }}" id="sync-accounts"
                        class="mt-">
                        @csrf
                        <input type="hidden" name="lorum_customer_id" value="{{ $customersId }}">
                    </form>

                    @if (!empty($local_client))
                        <button onclick="syncAccounts()" class="btn btn-success mt-">
                            Sync Accounts
                        </button>
                    @else
                        <button class="btn btn-success" disabled data-toggle="tooltip"
                            title="To synced lorum account, first linked the client">
                            Sync Accounts
                        </button>
                    @endif

                </div>
            @else
                <div class="panel-content">
                    <div class="alert alert-danger">
                        No virtual account found for this clinet in lorum system.
                    </div>
                </div>
            @endif
        </div>
    </div>


    {{-- ============================= --}}
    {{-- 🔹 LOCAL CLIENT VIRTUAL BANK ACCOUNTS --}}
    {{-- ============================= --}}
    {{-- <div class="panel">
        <div class="panel-hdr">
            <h2>Local Virtual Bank Accounts</h2>
        </div>

        <div class="panel-container show mb-3">
            @if (!empty($local_bank_accounts) && count($local_bank_accounts) > 0)
                <div class="panel-content">

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Account ID</th>
                                <th>Local Account ID</th>
                                <th>Bank Name</th>
                                <th>Currency</th>
                                <th>IBAN / Account</th>
                                <th>Status</th>
                                <th>Account Confirmation</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($local_bank_accounts as $account)
                                @php
                                    // Determine account details
                                    if (!empty($account['iban'])) {
                                        $accountDetails = $account['iban'];
                                    } elseif (!empty($account->account_number)) {
                                        $routing = $account->routing_code_entries ?? [];
                                        $routingText = '';

                                        if (is_array($routing) && count($routing) > 0) {
                                            foreach ($routing as $r) {
                                                $routingText = $r['routingCodeKey'] . ': ' . $r['routingCodeValue'] . ' ';
                                            }
                                        }
                                        $accountDetails = 'Account: ' . $account->account_number . (!empty($routingText) ? ' | ' . $routingText : '');
                                    } else {
                                        $accountDetails = '-';
                                    }
                                    $isSynced = !empty($account['virtual_bank_account_id']);
                                @endphp

                                <tr class="{{ $isSynced ? '' : 'table-warning' }}">
                                    <td>{{ $account['id'] }}</td>
                                    <td>{{ $account['account_id'] }}</td>
                                    <td>{{ $account['bank_name'] ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-info">{{ $account['currency'] ?? '-' }}</span>
                                    </td>
                                    <td>{{ $accountDetails }}</td>
                                    <td>
                                        @if ($isSynced)
                                            <span class="badge badge-success">Synced</span>
                                        @else
                                            <span class="badge badge-warning">Not Synced</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary"
                                            onclick="downloadAccountConfirmation('{{ $account['virtual_bank_account_id'] }}')">
                                            Account Confirmation Letter
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <hr class="mt-3">

                    @if (!empty($local_client))
                        <form method="POST" action="{{ url('/settings/sync-local-accounts') }}" id="sync-local-accounts">
                            @csrf
                            <input type="hidden" name="client_id" value="{{ $local_client->client_id }}">
                        </form>

                        <button onclick="syncLocalAccounts()" class="btn btn-success mt-2">
                            Sync Local Accounts
                        </button>
                    @else
                        <button class="btn btn-success mt-2" disabled data-toggle="tooltip"
                            title="Link client first to sync accounts">
                            Sync Local Accounts
                        </button>
                    @endif

                </div>
            @else
                <div class="panel-content">
                    <div class="alert alert-danger">
                        No virtual accounts found for this client in the local system.
                    </div>
                </div>
            @endif
        </div>
    </div> --}}

@stop

@section('scripts')
<script>
    function syncLocalAccounts() {
        Swal.fire({
            title: "Sync Local Accounts?",
            text: "This will sync all missing virtual bank accounts for this client.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes"
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('sync-local-accounts').submit();
            }
        });
    }    
    function syncClient() {
        Swal.fire({
            title: "Sync Client?",
            text: "Are you sure, This will create client linkage.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Link it!"
        }).then((result) => {
            if (result.value) {
                document.getElementById('sync-client').submit();
            }
        });
    }

    function syncWithSelectedClient() {
        const selected_client = document.getElementById('selected_client').value

        if (!selected_client) return;

        Swal.fire({
            title: "Sync Client?",
            text: "This will create client linkage, with the selected client.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Link it!"
        }).then((result) => {
            if (result.value) {
                document.getElementById('sync-selected-client').submit();
            }
        });
    }

    function syncAccounts() {
        Swal.fire({
            title: "Sync Accounts?",
            text: "This will sync all missing accounts.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Link it!"
        }).then((result) => {
            if (result.value) {
                document.getElementById('sync-accounts').submit();
            }
        });
    }

    function downloadAccountConfirmation(accountId) {
        if (!accountId) {
            Swal.fire('Error', 'Account ID missing', 'error');
            return;
        }

        window.open(`/client-management/clients/account-confirmation-letter/${accountId}`, '_blank');
    }
</script>
@stop
