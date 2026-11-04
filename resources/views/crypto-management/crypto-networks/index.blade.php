@extends('layouts.cp')
@section('title', 'Crypto Networks - Crypto Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Networks</span>
        </h1>

        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#networkModal" onclick="resetNetworkForm()">
                    <i class="fal fa-plus-circle"></i> Add Network
                </button>
                <button type="button" class="btn btn-primary ml-2" data-toggle="modal" data-target="#assetModal" onclick="resetAssetForm()">
                    <i class="fal fa-plus-circle"></i> Add Asset
                </button>
            </div>
            
            <a href="{{ url('/crypto-management/crypto-currencies') }}" class="btn btn-primary ml-2">
                <i class="fal fa-arrow-alt-left"></i> Go Back 
            </a>
        </div>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Crypto Networks & Assets
            </h2>
        </div>

        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                <div id="tabs">
                    <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#tab-networks" role="tab" aria-selected="true">Networks</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#tab-assets" role="tab" aria-selected="false">Assets</a>
                        </li>
                    </ul>

                    <div class="tab-content p-3">
                        <!-- Networks Tab -->
                        <div class="tab-pane fade active show" id="tab-networks" role="tabpanel">                            
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover m-0 shadow" id="networks-table">
                                    <thead class="thead-themed">
                                        <tr>
                                            <th width="30"></th>
                                            <th>ID</th>
                                            <th>Currency</th>
                                            <th>Code</th>
                                            <th>Name</th>
                                            <th>Testnet</th>
                                            <th>Status</th>
                                            {{-- <th width="120">Action</th> --}}
                                        </tr>
                                    </thead>


                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Assets Tab -->
                        <div class="tab-pane fade" id="tab-assets" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover m-0 shadow" id="assets-table">
                                    <thead class="thead-themed">
                                        <tr>
                                            <th width="30"></th>
                                            <th>ID</th>
                                            <th>Asset ID</th>
                                            <th>Currency</th>
                                            <th>Network</th>
                                            <th>Type</th>
                                            <th>Contract Address</th>
                                            <th>Vault Account ID</th>
                                            <th>Testnet</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>

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
                    <h5 class="modal-title" id="networkModalTitle">Add Network</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" id="networkForm" action="{{ url('/crypto-management/crypto-currencies/networks/store') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="id" id="network_id" value="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="network_currency_id">Currency</label>
                            <select class="form-control select2-currency" id="network_currency_id" name="currency_id" required>
                                <option value="">Select Currency</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->short }})</option>
                                @endforeach
                            </select>
                        </div>
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
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="asset_currency_id">Currency</label>
                            <select class="form-control select2" id="asset_currency_id" name="currency_id" required>
                                <option value="">Select Currency</option>
                                @foreach ($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->name }} ({{ $currency->short }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="asset_network_id">Network</label>
                            <select class="form-control select2" id="asset_network_id" name="network_id" required>
                                <option value="">Select Network</option>
                                @foreach ($allNetworks as $network)
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
                            <select class="form-control" id="asset_type" name="type" required>
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
        const allNetworks = @json($networkOptions);

        const networksDataUrl = "{{ url('/fireblocks/crypto-networks/networks-data') }}";
        const assetsDataUrl = "{{ url('/fireblocks/crypto-networks/assets-data') }}";
        let assetsTable = null;
        let assetsTableInitialized = false;

        $(document).ready(function () {
            $('#networks-table').DataTable({
                processing: true,
                serverSide: true,
                pageLength: 25,
                responsive: true,
                autoWidth: false,
                order: [[1, 'desc']],
                ajax: networksDataUrl,
                columns: [
                    { data: 'edit_action', orderable: false, searchable: false, className: 'text-center' },
                    { data: 'id', name: 'id' },
                    { data: 'currency_short', name: 'currency_short' },
                    { data: 'code', name: 'code' },
                    { data: 'name', name: 'name' },
                    { data: 'testnet_badge', name: 'testnet_badge' },
                    { data: 'enabled_badge', name: 'enabled_badge' },
                ],
            });

            $('a[data-toggle="tab"][href="#tab-assets"]').on('shown.bs.tab', function () {
                if (!assetsTableInitialized) {
                    assetsTable = $('#assets-table').DataTable({
                        processing: true,
                        serverSide: true,
                        pageLength: 25,
                        responsive: true,
                        autoWidth: false,
                        order: [[1, 'desc']],
                        ajax: assetsDataUrl,
                        columns: [
                            { data: 'edit_action', orderable: false, searchable: false, className: 'text-center' },
                            { data: 'id', name: 'id' },
                            { data: 'asset_id', name: 'asset_id' },
                            { data: 'currency_short', name: 'currency_short' },
                            { data: 'network_name', name: 'network_name' },
                            { data: 'type', name: 'type' },
                            { data: 'contract_address_label', name: 'contract_address_label' },
                            { data: 'vault_account_id_label', name: 'vault_account_id_label' },
                            { data: 'testnet_badge', name: 'testnet_badge' },
                            { data: 'enabled_badge', name: 'enabled_badge' },
                        ],
                    });
                    assetsTableInitialized = true;
                } else {
                    assetsTable.columns.adjust();
                }
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function () {
                $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            });

            $('#asset_currency_id').on('change', function () {
                populateAssetNetworks($(this).val(), '');
            });

            $(document).on('click', '.js-edit-network', function () {
                const $btn = $(this);
                editNetwork(
                    $btn.data('id'),
                    $btn.data('currency-id'),
                    $btn.data('code'),
                    $btn.data('name'),
                    $btn.data('is-testnet'),
                    $btn.data('is-enabled'),
                    $btn.data('logo-url')
                );
            });
        });

        function populateAssetNetworks(currencyId, selectedNetworkId) {
            const $networkSelect = $('#asset_network_id');
            const previousValue = selectedNetworkId || $networkSelect.val();

            $networkSelect.empty().append('<option value="">Select Network</option>');

            allNetworks
                .filter(function (network) {
                    return String(network.currency_id) === String(currencyId);
                })
                .forEach(function (network) {
                    const selected = String(network.id) === String(previousValue) ? ' selected' : '';
                    $networkSelect.append(
                        '<option value="' + network.id + '"' + selected + '>' + network.label + '</option>'
                    );
                });

            if ($networkSelect.hasClass('select2-hidden-accessible')) {
                $networkSelect.trigger('change.select2');
            }
        }

        function resetAssetForm() {
            document.getElementById('assetForm').action = "{{ url('/crypto-management/crypto-currencies/assets/store') }}";
            document.getElementById('assetModalTitle').innerText = 'Add Asset';
            document.getElementById('asset_id_row').value = '';
            document.getElementById('asset_currency_id').value = '';
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
            populateAssetNetworks('', '');
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
            $('#network_currency_id').val('').trigger('change.select2');
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

        function editNetwork(id, currencyId, code, name, isTestnet, isEnabled, logoUrl) {
            document.getElementById('networkForm').action = "{{ url('/crypto-management/crypto-currencies/networks/update') }}";
            document.getElementById('networkModalTitle').innerText = 'Edit Network';
            document.getElementById('network_id').value = id;
            document.getElementById('network_code').value = code || '';
            document.getElementById('network_name').value = name || '';
            document.getElementById('network_is_testnet').checked = Number(isTestnet) === 1;
            document.getElementById('network_is_enabled').checked = Number(isEnabled) !== 0;
            document.getElementById('network_logo').value = '';
            document.getElementById('network_logo_group').style.display = 'block';
            setLogoPreview('network', logoUrl || '');
            $('#network_currency_id').val(String(currencyId)).trigger('change.select2');
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
                        document.getElementById('asset_currency_id').value = data.currency_id;
                        populateAssetNetworks(data.currency_id, data.network_id);
                        document.getElementById('asset_asset_id').value = data.asset_id;
                        document.getElementById('asset_name').value = data.asset_name;
                        document.getElementById('asset_type').value = data.type;
                        document.getElementById('asset_contract_address').value = data.contract_address || '';
                        document.getElementById('asset_vault_account_id').value = data.vault_account_id || '';
                        document.getElementById('asset_is_testnet').checked = data.is_testnet == 1;
                        document.getElementById('asset_is_enabled').checked = data.is_enabled != 0;
                        document.getElementById('asset_logo').value = '';
                        document.getElementById('asset_logo_group').style.display = 'block';
                        setLogoPreview('asset', data.logo_url || '');

                        $('#asset_currency_id, #asset_network_id').trigger('change.select2');
                    } else {
                        Swal.fire('Error', 'Failed to load asset data.', 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Failed to load asset data.', 'error');
                }
            });
        }

        $('#networkModal, #assetModal').on('shown.bs.modal', function () {
            const $modal = $(this);
            const isNetworkModal = $modal.attr('id') === 'networkModal';
            const savedNetworkCurrencyId = isNetworkModal ? $('#network_currency_id').val() : null;

            $modal.find('select').each(function () {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }

                $(this).select2({
                    dropdownParent: $modal,
                    width: '100%'
                });
            });

            if (isNetworkModal && savedNetworkCurrencyId) {
                $('#network_currency_id').val(savedNetworkCurrencyId).trigger('change.select2');
            }
        });

    </script>
@stop
