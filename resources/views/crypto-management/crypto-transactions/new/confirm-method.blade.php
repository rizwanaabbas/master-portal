@extends('layouts.cp')
@section('title', 'Select New Transaction Method - Crypto Transactions Requests')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Crypto Transaction Method</span>
        </h1>
        <a href="{{ url('/crypto-management/crypto-transactions') }}" class="btn btn-primary mr-3"><i
                class="fal fa-arrow-alt-left"></i> Go Back </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
              Select  New Transaction Method
            </h2>
        </div>

      
            <div class="panel-container show">
                <div class="panel-content">
                    @include('common.error-message')
                    <div class="row">
                        <div class="col-md-6">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="form-group">
                                        
                                        <select 
                                            name="transaction_method" autofocus id="transaction_method" onchange="getMethod()"
                                            data-select2-id="transaction_method" class="form-control select2 w-100" required>
                                            <option value="">Transaction Method</option>
                                            @if (\App\Services\CryptoConfigService::isAdvancedCryptoEnabled())
                                            <option value="nexus">Nexus </option>
                                            <option value="independent_reserve">Independent Reserve </option>
                                            @endif
                                            @if (in_array('Bitpace', $enabledModules))
                                                @can('bitpace management')    
                                                    <option value="bitpace">Bitpace </option>
                                                @endcan
                                            @endif
                                            @if (\App\Services\CryptoConfigService::isAdvancedCryptoEnabled() && in_array('Fireblocks', $enabledModules))
                                                @can('fireblocks management')
                                                    <option value="fireblocks">Fireblocks </option>
                                                @endcan
                                            @endif
                                            @if (\App\Services\CryptoConfigService::isAdvancedCryptoEnabled() && in_array('Liquidity', $enabledModules) && (in_array('B2c2', $enabledModules) || in_array('Aquanow', $enabledModules)) && in_array($type, ['new-buy', 'new-sell']))
                                                @php
                                                    $liquidityProviderLabel = in_array('B2c2', $enabledModules) ? 'B2C2' : 'Aquanow';
                                                @endphp
                                                <option value="liquidity">{{ $liquidityProviderLabel }} </option>
                                            @endif
                                            @if (\App\Services\CryptoConfigService::isManualCryptoEnabled())
                                            <option value="bitpace_manual">Bitpace Manual</option>
                                            @endif
                                        </select>
                                        @error('credit_account_id')
                                            <span class="invalid-feedback"
                                                role="alert"><strong>{{ $message }}</strong></span>
                                        @enderror
                                    </div>
                                </div>
                               
                            </div>


                        </div>
                      
                    </div>
                    <!-- <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <hr>
                                
                                <button id="submit" type="button" class="btn btn-primary" onclick="getMethod()">Submit</button>
                            </div>
                        </div>
                    </div> -->
                   
                </div>

            </div>
        
    </div>
@stop
@section('scripts')
    <script>
       function getMethod() {
            var method = document.getElementById('transaction_method').value;
            var type = "{{$type}}";
            const urls = [];
            var url = "{{ url('/crypto-management/crypto-transactions/new/deposit')}}";
            // if (method == 'manual') {
            //     urls['new-deposit']= "{{ url('/crypto-management/crypto-transactions/new/deposit')}}";
            //     urls['new-buy']= "{{ url('/crypto-management/crypto-transactions/new/buy')}}";
            //     urls['new-sell']= "{{ url('/crypto-management/crypto-transactions/new/sell')}}";
            //     urls['new-withdraw']= "{{ url('/crypto-management/crypto-transactions/new/withdraw')}}";
            // }
            if (method == 'bitpace') {
                urls['new-deposit']= "{{ url('/crypto-management/crypto-transactions/bitpace/new-address')}}";
                urls['new-buy']= "{{ url('/crypto-management/crypto-transactions/bitpace/buy')}}";
                urls['new-sell']= "{{ url('/crypto-management/crypto-transactions/bitpace/sell')}}";
                urls['new-withdraw']= "{{ url('/crypto-management/crypto-transactions/bitpace/withdraw')}}";
            } else if (method == 'liquidity') {
                // Delegate the full Buy/Sell workflow to the active liquidity provider (B2c2 or Aquanow).
                urls['new-buy']= "{{ url('/liquidity/trade/buy')}}";
                urls['new-sell']= "{{ url('/liquidity/trade/sell')}}";
            } else if (method == 'fireblocks') {
                // Delegate the Withdraw workflow to the Fireblocks module's own form.
                urls['new-withdraw']= "{{ url('/fireblocks/withdraw')}}";
                urls['new-deposit']= "{{ url('/crypto-management/crypto-transactions/new/deposit?method=')}}"+method;
                urls['new-buy']= "{{ url('/crypto-management/crypto-transactions/new/buy?method=')}}"+method;
                urls['new-sell']= "{{ url('/crypto-management/crypto-transactions/new/sell?method=')}}"+method;
            } else {
                urls['new-deposit']= "{{ url('/crypto-management/crypto-transactions/new/deposit?method=')}}"+method;
                urls['new-buy']= "{{ url('/crypto-management/crypto-transactions/new/buy?method=')}}"+method;
                urls['new-sell']= "{{ url('/crypto-management/crypto-transactions/new/sell?method=')}}"+method;
                urls['new-withdraw']= "{{ url('/crypto-management/crypto-transactions/new/withdraw?method=')}}"+method;
            }
            if(method != '')
                url = urls[type];
            else
                return false;

            //var url = "{{ url('/crypto-management/crypto-transactions/new/deposit')}}";
            //if (method == 'bitpace')
            //    url = "{{ url('/crypto-management/crypto-transactions/bitpace/new-address')}}";
            window.location = url;
        }
    </script>
@stop
