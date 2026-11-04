@extends('layouts.cp')
@section('title', 'Update Crypto Currency - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Management</span>
        </h1>
        <a href="{{ url('/crypto-management/crypto-currencies') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Update Crypto Currency ({{ $currency->short }})
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <div id="tabs">
                    <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tab-general" role="tab" aria-selected="true">General</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-networks" role="tab" aria-selected="false">Networks</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-assets" role="tab" aria-selected="false">Assets</a>
                        </li>
                    </ul>

                    <div class="tab-content p-3">
                        <!-- General Tab -->
                        <div class="tab-pane fade active show" id="tab-general" role="tabpanel">
                            <form method="post" action="{{ url('/crypto-management/crypto-currencies/update') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" id="id" name="id" value="{{ $currency->id }}">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label" for="short">Short</label>
                                                    <input type="text" autofocus id="short" maxlength="5" name="short"
                                                        value="{{ $currency->short }}" class="form-control" required>
                                                    @error('short')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" autofocus id="name" name="name"
                                                        value="{{ $currency->name }}" class="form-control" required>
                                                    @error('name')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label" for="min-amount">Min</label>
                                                    <input type="text" autofocus id="min-amount" name="min-amount"
                                                        value="{{ $currency->min_amount }}" class="form-control" required>
                                                    @error('min-amount')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label" for="max-amount">Max</label>
                                                    <input type="text" autofocus id="max-amount" name="max-amount"
                                                        value="{{ $currency->max_amount }}" class="form-control" required>
                                                    @error('max-amount')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label" for="decimals">Crypto Decimals Points</label>
                                                    <input type="text" autofocus id="decimals" name="decimals"
                                                        value="{{ $currency->decimals }}" class="form-control" required>
                                                    @error('decimals')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <div class="custom-control custom-switch mt-5">
                                                    <input type="checkbox" class="custom-control-input"
                                                        @if ($currency->is_active) checked @endif id="is_active"
                                                        value="1" name="is_active">
                                                    <label class="custom-control-label" for="is_active">Active</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <div class="custom-control custom-switch mt-5">
                                                    <input type="checkbox" class="custom-control-input"
                                                        @if ($currency->request_auto_approval) checked @endif id="request_auto_approval"
                                                        value="1" name="request_auto_approval">
                                                    <label class="custom-control-label" for="request_auto_approval">Auto approve service request</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label class="form-label" for="logo">Logo</label><br />
                                                    @if ($currency->logo != '')
                                                        <img src="{{ $currency ? $currency->logo : '' }}"
                                                            style="width: 18px; height:18px"
                                                            class="mr-2 border border-primary profile-image rounded-circle"
                                                            alt="Logo" />
                                                    @endif
                                                    <label for="logo" class="btn btn-light">Upload</label>
                                                    <span id="file-name-display"> png/svg, recommended size 18x18</span>
                                                    <input type="file" name="logo" accept="image/*" onchange="setFile(this)"
                                                        id="logo" class="invisible item-file d-none" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <hr>
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Networks Tab -->
                        <div class="tab-pane fade" id="tab-networks" role="tabpanel">
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#networkModal"
                                    onclick="resetNetworkForm()">
                                    <i class="fal fa-plus-circle"></i> Add Network
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover m-0 shadow">
                                    <thead class="thead-themed">
                                        <tr>
                                            <th>ID</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Testnet</th>
                                            <th>Status</th>
                                            <th width="80">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($currencyNetworks as $network)
                                            <tr>
                                                <td>{{ $network->id }}</td>
                                                <td>{{ $network->code }}</td>
                                                <td>{{ $network->name }}</td>
                                                <td>{{ $network->is_testnet ? 'Yes' : 'No' }}</td>
                                                <td>{{ ($network->is_enabled ?? true) ? 'Enabled' : 'Disabled' }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm js-edit-network"
                                                        data-toggle="modal" data-target="#networkModal"
                                                        data-id="{{ $network->id }}"
                                                        data-code="{{ $network->code }}"
                                                        data-name="{{ $network->name }}"
                                                        data-is-testnet="{{ (int) $network->is_testnet }}"
                                                        data-is-enabled="{{ (int) ($network->is_enabled ?? 1) }}"
                                                        data-logo-url="{{ $network->logo_url ?? '' }}">
                                                        <i class="fal fa-edit"></i>
                                                    </button>
                                                   @can('delete records')
                                                    <form method="post" action="{{ url('/crypto-management/crypto-currencies/networks/delete') }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this network?');">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $network->id }}">
                                                        <button type="submit" class="btn btn-sm ">
                                                            <i class="fal fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">No networks found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Assets Tab -->
                        <div class="tab-pane fade" id="tab-assets" role="tabpanel">
                            <div class="mb-3">
                                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#assetModal"
                                    onclick="resetAssetForm()">
                                    <i class="fal fa-plus-circle"></i> Add Asset
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover m-0 shadow">
                                    <thead class="thead-themed">
                                        <tr>
                                            <th>ID</th>
                                            <th>Asset ID</th>
                                            <th>Network</th>
                                            <th>Name</th>
                                            <th>Type</th>
                                            <th>Vault Account ID</th>
                                            <th>Testnet</th>
                                            <th>Status</th>
                                            <th width="80">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($assets as $asset)
                                            <tr>
                                                <td>{{ $asset->id }}</td>
                                                <td>{{ $asset->asset_id }}</td>
                                                <td>{{ $asset->network->name ?? $asset->network->code ?? '-' }}</td>
                                                <td>{{ $asset->name ?: '-' }}</td>
                                                <td>{{ $asset->type }}</td>
                                                <td>{{ $asset->vault_account_id}}</td>
                                                <td>{{ $asset->is_testnet ? 'Yes' : 'No' }}</td>
                                                <td>{{ ($asset->is_enabled ?? true) ? 'Enabled' : 'Disabled' }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm "
                                                        data-toggle="modal" data-target="#assetModal"
                                                        onclick="loadAssetForEdit({{ $asset->id }})">
                                                        <i class="fal fa-edit"></i>
                                                        
                                                    </button>
                                                     @can('delete records')
                                                    <form method="post" action="{{ url('/crypto-management/crypto-currencies/assets/delete') }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this asset?');">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $asset->id }}">
                                                        <button type="submit" class="btn btn-sm">
                                                            <i class="fal fa-trash"></i>
                                                        </button>
                                                    </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">No assets found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Network Modal -->
    <div class="modal fade" id="networkModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><span  id="networkModalTitle">Add Network</span> ({{$currency->short}})</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" id="networkForm" action="{{ url('/crypto-management/crypto-currencies/networks/store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="network_id" value="">
                    <input type="hidden" name="currency_id" id="currency_id" value="{{ $currency->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="network_code">Code</label>
                            <input type="text" class="form-control" id="network_code" name="code" required>
                        </div>
                        <div class="form-group">
                            <label for="network_name">Name</label>
                            <input type="text" class="form-control" id="network_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="network_is_testnet" name="is_testnet" value="1">
                                <label class="custom-control-label" for="network_is_testnet">Testnet</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="network_is_enabled" name="is_enabled" value="1" checked>
                                <label class="custom-control-label" for="network_is_enabled">Enabled</label>
                            </div>
                        </div>
                        <div class="form-group" id="network_logo_group" style="display: none;">
                            <label for="network_logo">Logo</label>
                            <div class="mb-2" id="network_logo_preview_wrap" style="display: none;">
                                <img id="network_logo_preview" src="" alt="Network logo" height="40">
                            </div>
                            <input type="file" class="form-control-file" id="network_logo" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                            <small class="form-text text-muted">Optional. JPEG, PNG, WebP, or SVG.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Asset Modal -->
    <div class="modal fade" id="assetModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assetModalTitle">Add Asset</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" id="assetForm" action="{{ url('/crypto-management/crypto-currencies/assets/store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="asset_id_row" value="">
                    <input type="hidden" name="currency_id" value="{{ $currency->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="asset_network_id">Network</label>
                            
                            <select class="form-control select2" id="asset_network_id" name="network_id" required>
                                <option value="">Select Network</option>
                                @foreach ($currencyNetworks as $network)
                                    <option value="{{ $network->id }}">{{ $network->name }} ({{ $network->code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="asset_asset_id">Asset ID</label>
                            <input type="text" class="form-control" id="asset_asset_id" name="asset_id" required>
                        </div>
                        <div class="form-group">
                            <label for="asset_name">Asset Name</label>
                            <input type="text" class="form-control" id="asset_name" name="asset_name" required>
                        </div>
                        <div class="form-group">
                            <label for="asset_type">Type</label>
                            <select class="form-control select2" id="asset_type" name="type" required>
                                <option value="utxo">UTXO</option>
                                <option value="account">Account</option>
                                <option value="token">Token</option>
                                <option value="fiat">Fiat</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="asset_contract_address">Contract Address</label>
                            <input type="text" class="form-control" id="asset_contract_address" name="contract_address">
                        </div>
                        <div class="form-group">
                            <label for="asset_vault_account_id">Vault Account ID</label>
                            <input type="text" class="form-control" id="asset_vault_account_id" name="vault_account_id" placeholder="e.g. 0">
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="asset_is_testnet" name="is_testnet" value="1">
                                <label class="custom-control-label" for="asset_is_testnet">Testnet</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="asset_is_enabled" name="is_enabled" value="1" checked>
                                <label class="custom-control-label" for="asset_is_enabled">Enabled</label>
                            </div>
                        </div>
                        <div class="form-group" id="asset_logo_group" style="display: none;">
                            <label for="asset_logo">Logo</label>
                            <div class="mb-2" id="asset_logo_preview_wrap" style="display: none;">
                                <img id="asset_logo_preview" src="" alt="Asset logo" height="40">
                            </div>
                            <input type="file" class="form-control-file" id="asset_logo" name="logo" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                            <small class="form-text text-muted">Optional. JPEG, PNG, WebP, or SVG.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        function setFile(obj) {
            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }

        function setLogoPreview(prefix, url) {
            const wrap = document.getElementById(prefix + '_logo_preview_wrap');
            const img = document.getElementById(prefix + '_logo_preview');
            if (url) {
                img.src = url;
                wrap.style.display = 'block';
            } else {
                img.src = '';
                wrap.style.display = 'none';
            }
        }

        function resetNetworkForm() {
            document.getElementById('networkForm').action = "{{ url('/crypto-management/crypto-currencies/networks/store') }}";
            document.getElementById('networkModalTitle').innerText = 'Add Network';
            document.getElementById('network_id').value = '';
            document.getElementById('network_code').value = '';
            document.getElementById('network_name').value = '';
            document.getElementById('network_is_testnet').checked = false;
            document.getElementById('network_is_enabled').checked = true;
            document.getElementById('network_logo').value = '';
            setLogoPreview('network', '');
            document.getElementById('network_logo_group').style.display = 'none';
        }

        function editNetwork(id, code, name, isTestnet, isEnabled, logoUrl) {
            document.getElementById('networkForm').action = "{{ url('/crypto-management/crypto-currencies/networks/update') }}";
            document.getElementById('networkModalTitle').innerText = 'Edit Network';
            document.getElementById('network_id').value = id;
            document.getElementById('network_code').value = code;
            document.getElementById('network_name').value = name;
            document.getElementById('network_is_testnet').checked = isTestnet == 1;
            document.getElementById('network_is_enabled').checked = isEnabled != 0;
            document.getElementById('network_logo').value = '';
            document.getElementById('network_logo_group').style.display = 'block';
            setLogoPreview('network', logoUrl || '');
        }

        $(document).on('click', '.js-edit-network', function () {
            const $btn = $(this);
            editNetwork(
                $btn.data('id'),
                $btn.data('code'),
                $btn.data('name'),
                $btn.data('is-testnet'),
                $btn.data('is-enabled'),
                $btn.data('logo-url')
            );
        });

        function resetAssetForm() {
            document.getElementById('assetForm').action = "{{ url('/crypto-management/crypto-currencies/assets/store') }}";
            document.getElementById('assetModalTitle').innerText = 'Add Asset';
            document.getElementById('asset_id_row').value = '';
            document.getElementById('asset_network_id').value = '';
            document.getElementById('asset_asset_id').value = '';
            document.getElementById('asset_name').value = '';
            document.getElementById('asset_type').value = 'token';
            document.getElementById('asset_contract_address').value = '';
            document.getElementById('asset_vault_account_id').value = '';
            document.getElementById('asset_is_testnet').checked = false;
            document.getElementById('asset_is_enabled').checked = true;
            document.getElementById('asset_logo').value = '';
            setLogoPreview('asset', '');
            document.getElementById('asset_logo_group').style.display = 'none';
        }

        function loadAssetForEdit(id) {
            $.ajax({
                url: "{{ url('/crypto-management/crypto-currencies/assets') }}/" + id,
                type: "GET",
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        document.getElementById('assetForm').action = "{{ url('/crypto-management/crypto-currencies/assets/update') }}";
                        document.getElementById('assetModalTitle').innerText = 'Edit Asset';
                        document.getElementById('asset_id_row').value = data.id;
                        document.getElementById('asset_network_id').value = data.network_id;
                        document.getElementById('asset_name').value = data.asset_name;
                        document.getElementById('asset_asset_id').value = data.asset_id;
                        document.getElementById('asset_type').value = data.type;
                        document.getElementById('asset_contract_address').value = data.contract_address || '';
                        document.getElementById('asset_vault_account_id').value = data.vault_account_id || '';
                        document.getElementById('asset_is_testnet').checked = data.is_testnet == 1;
                        document.getElementById('asset_is_enabled').checked = data.is_enabled != 0;
                        document.getElementById('asset_logo').value = '';
                        document.getElementById('asset_logo_group').style.display = 'block';
                        setLogoPreview('asset', data.logo_url || '');
                    } else {
                        Swal.fire('Error', 'Failed to load asset data.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load asset data.', 'error');
                }
            });
        }

        $('.modal').on('shown.bs.modal', function () {
            $(this).find('.select2').each(function () {

                // destroy global instance (if already initialized)
                if ($(this).hasClass("select2-hidden-accessible")) {
                    $(this).select2('destroy');
                }

                // re-init with correct parent
                $(this).select2({
                    dropdownParent: $(this).closest('.modal'),
                    width: '100%'
                });

            });
        });
    </script>
@stop
