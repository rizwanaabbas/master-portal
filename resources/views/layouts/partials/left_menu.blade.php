@php $settings = []; @endphp

<!-- BEGIN Left Aside -->
<aside class="page-sidebar">
    <div class="page-logo @if (env('APP_ENV') == 'staging') bg-primary @endif">
        <img class="mx-auto" style="max-height: 55px;" src="{{ $ownCompany ? $ownCompany->logo : '' }}"
            alt="{{ $ownCompany ? $ownCompany->short_name : '' }}" aria-roledescription="logo">
        <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
    </div>
    <!-- BEGIN PRIMARY NAVIGATION -->
    <nav id="js-primary-nav" class="primary-nav" role="navigation">
        <div class="nav-filter">
            <div class="position-relative">
                <input type="text" id="nav_filter_input" placeholder="Filter menu" class="form-control"
                    tabindex="0">
                <a href="#" onclick="return false;" class="btn-primary btn-search-close js-waves-off"
                    data-action="toggle" data-class="list-filter-active" data-target=".page-sidebar">
                    <i class="fal fa-chevron-up"></i>
                </a>
            </div>
        </div>
        <div class="info-card">
            @if (\Illuminate\Support\Facades\Auth::user()->profile_photo_url)
                <img src="{{ \Illuminate\Support\Facades\Auth::user()->profile_photo_url }}"
                    style="width: 50px; height:50px" class="border border-primary profile-image rounded-circle"
                    alt="Profile Photo">
            @else
                <img src="{{ asset('assets/img/avatar.png') }}" class="profile-image rounded-circle"
                    alt="Profile Photo">
            @endif

            <div class="info-card-text">
                <a href="#" class="d-flex align-items-center text-white">
                    <span class="text-truncate text-truncate-sm d-inline-block">
                        {{ \Illuminate\Support\Facades\Auth::user()->name }}
                    </span>
                </a>
                <span class="d-inline-block text-truncate text-truncate-sm">Welcome back</span>
            </div>
            <img src="{{ asset('assets/img/card-backgrounds/burj-khalifa-cover.jpeg') }}" class="cover" alt="cover">
            <a href="#" onclick="return false;" class="pull-trigger-btn" data-action="toggle"
                data-class="list-filter-active" data-target=".page-sidebar" data-focus="nav_filter_input">
                <i class="fal fa-angle-down"></i>
            </a>
        </div>
        <ul id="js-nav-menu" class="nav-menu">
            <li @if (strpos(Request::route()->uri, 'home') !== false ||
                    (strpos(Request::route()->uri, 'client/dashboard') !== false &&
                        strpos(Request::route()->uri, 'account-statement') === false)) class="active" @endif>
                <a href="{{ url('/home') }}" title="Home" data-filter-tags="home">
                    <i class="fal fa-home"></i>
                    <span class="nav-link-text">Home</span>
                </a>
            </li>
            {{-- @role('draft client')
                <li @if (strpos(Request::route()->uri, 'client-profile') !== false) class="active" @endif>
                    <a href="{{ url('/client-profile') }}" title="Client Profile" data-filter-tags="client profile">
                        <i class="fal fa-address-card"></i>
                        <span class="nav-link-text">Client Profile</span>
                    </a>
                </li>
            @endrole --}}

            {{-- @role('basic client')
                <li @if (strpos(Request::route()->uri, 'account-statement') !== false) class="active open" @endif>
                    <a href="#" title="Investment Management" data-filter-tags="investment">
                        <i class="fal fa-money-check-alt"></i>
                        <span class="nav-link-text">Investment Management</span>
                    </a>
                    <ul>
                        <li @if (strpos(Request::route()->uri, 'account-statement') !== false) class="active" @endif>
                            <a href="{{ url('/client/dashboard/account-statement') }}" title="Account Statement"
                                data-filter-tags="statement">
                                <span class="nav-link-text">Account Statement</span>
                            </a>
                        </li>
                    </ul>
                </li>
            @endrole --}}

            {{-- @role('client')
                <li @if (strpos(Request::route()->uri, 'account-statement') !== false) class="active open" @endif>
                    <a href="#" title="Investment Management" data-filter-tags="investment">
                        <i class="fal fa-money-bill"></i>
                        <span class="nav-link-text">Investment Management</span>
                    </a>
                    <ul>
                        <li @if (strpos(Request::route()->uri, 'account-statement') !== false) class="active" @endif>
                            <a href="{{ url('/client/dashboard/account-statement') }}" title="Account Statement"
                                data-filter-tags="statement">
                                <span class="nav-link-text">Account Statement</span>
                            </a>
                        </li>
                    </ul>
                </li>
                <li @if (strpos(Request::route()->uri, '/transaction-requests') !== false) class="active open" @endif>
                    <a href="#" title="Transaction Requests" data-filter-tags="finance">
                        <i class="fal fa-money-check-alt"></i>
                        <span class="nav-link-text">Transactions Requests</span>
                    </a>
                    <ul>
                        <li @if (strpos(Request::route()->uri, 'client/transaction-requests/beneficiaries') !== false) class="active" @endif>
                            <a href="{{ url('/client/transaction-requests/beneficiaries') }}" title="Beneficiary List"
                                data-filter-tags="beneficiary list">
                                <span class="nav-link-text">Beneficiary List</span>
                            </a>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'client/transaction-requests/list') !== false) class="active" @endif>
                            <a href="{{ url('/client/transaction-requests/list') }}" title="Transaction Requests"
                                data-filter-tags="transaction requests">
                                <span class="nav-link-text">Request List</span>
                            </a>
                        </li>
                    </ul>
                </li>
                @if (in_array('Card', $enabledModules) && \App\Helpers\Common::showCardManagement())
                    <li @if (strpos(Request::route()->uri, 'client/card-management') !== false) class="active open" @endif>
                        <a href="#" title="Card Management" data-filter-tags="card management">
                            <i class="fal fa-money-check"></i>
                            <span class="nav-link-text">Card Management</span>
                        </a>
                        <ul>
                            @if (!\App\Helpers\Common::showClientManagement())
                                    <li @if (strpos(Request::route()->uri, 'client/card-management/request-instant-card') !== false) class="active" @endif>
                                        <a href="{{ url('/client/card-management/request-instant-card') }}"
                                            title="Request Instant Card" data-filter-tags="request Instant card">
                                            <span class="nav-link-text">Request Instant Card</span>
                                        </a>
                                    </li>
                                @else
                            <li @if (strpos(Request::route()->uri, 'client/card-management/card/view') !== false) class="active" @endif>
                                <a href="{{ url('/client/card-management/card/view') }}" title="Card Details"
                                    data-filter-tags="card details">
                                    <span class="nav-link-text">Card Details</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'client/card-management/card/statement') !== false) class="active" @endif>
                                <a href="{{ url('/client/card-management/card/statement') }}" title="Card Statement"
                                    data-filter-tags="card statement">
                                    <span class="nav-link-text">Card Statement</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'client/card-management/card/topups/list') !== false) class="active" @endif>
                                <a href="{{ url('/client/card-management/card/topups/list') }}"
                                    title="Card Top-up Requests" data-filter-tags="card topup requests">
                                    <span class="nav-link-text">Card Top-up Requests</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'client/card-management/unblock-requests/list') !== false) class="active" @endif>
                                    <a href="{{ url('/client/card-management/unblock-requests/list') }}"
                                        title="Card unblock Requests" data-filter-tags="card unblock requests">
                                        <span class="nav-link-text">Card Unblock Requests</span>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endif

                @if (\App\Helpers\Common::showClientCrypto() && in_array('Crypto', $enabledModules))
                    <li @if (strpos(Request::route()->uri, 'client/crypto') !== false) class="active open" @endif>
                        <a href="#" title="Crypto Transaction Requests" data-filter-tags="finance">
                            <i class="fal fa-badge-dollar"></i>
                            <span class="nav-link-text">Crypto Requests</span>
                        </a>
                        <ul>
                            <li @if (strpos(Request::route()->uri, 'client/crypto-transaction-requests') !== false) class="active" @endif>
                                <a href="{{ url('/client/crypto-transaction-requests/list') }}"
                                    title="Crypto Transaction Requests List"
                                    data-filter-tags="crypto transaction requests">
                                    <span class="nav-link-text">Crypto Requests List</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endif
                @if (in_array('MobileRequests', $enabledModules))
                    <li @if (strpos(Request::route()->uri, 'client/services/requests') !== false) class="active open" @endif>
                        <a href="#" title="Services Requests" data-filter-tags="service">
                            <i class="fal fa-wrench"></i>
                            <span class="nav-link-text">Services Requests</span>
                        </a>
                        <ul>
                            <li @if (strpos(Request::route()->uri, 'client/services/requests/list') !== false) class="active" @endif>
                                <a href="{{ url('/client/services/requests/list') }}" title="Service Requests List"
                                    data-filter-tags="service requests">
                                    <span class="nav-link-text">Requests List</span>
                                </a>
                            </li>
                        </ul>
                    </li>
                @endif
            @endrole --}}

            @can('manage cards')
                @if (in_array('Card', $enabledModules))
                    <li @if (strpos(Request::route()->uri, 'card-management') !== false) class="active open" @endif>
                        <a href="#" title="Card Management" data-filter-tags="card management">
                            <i class="fal fa-money-check"></i>
                            <span class="nav-link-text">Card Management</span>
                        </a>
                        <ul>
                            <li @if (strpos(Request::route()->uri, 'card-management/link-users/list') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/link-users/list') }}" title="User-Card Links List"
                                    data-filter-tags="link-users">
                                    <span class="nav-link-text">User-Card Links List</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'card-management/cards') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/cards/list') }}" title="Cards List"
                                    data-filter-tags="cards list">
                                    <span class="nav-link-text">Cards List</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'card-management/cards/unassigned/list') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/cards/unassigned/list') }}"
                                    title="Unassigned Cards List" data-filter-tags="unassigned cards list">
                                    <span class="nav-link-text">Unassigned Cards List</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'card-management/card/topup-requests') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/card/topup-requests/list') }}" title="Top-up Requests"
                                    data-filter-tags="top-up requests">
                                    <span class="nav-link-text">Top-up Requests</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'card-management/card/topup-pending-requests/list') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/card/topup-pending-requests/list') }}"
                                    title="Card Topup Pending Requests" data-filter-tags="card topup pending requests">
                                    <span class="nav-link-text">Topup Pending Requests</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'card-management/card/topup-rejected-requests/list') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/card/topup-rejected-requests/list') }}"
                                    title="Card Topup Rejected Requests" data-filter-tags="card topup rejected requests">
                                    <span class="nav-link-text">Topup Rejected Requests</span>
                                </a>
                            </li>
                            {{-- <li @if (strpos(Request::route()->uri, 'card-management/card/unblock-requests') !== false) class="active" @endif>
                                <a href="{{ url('/card-management/card/unblock-requests/list') }}"
                                    title="Card Unblock Requests" data-filter-tags="card unblock requests">
                                    <span class="nav-link-text">Unblock Requests</span>
                                </a>
                            </li> --}}
                        </ul>
                    </li>
                @endif
            @endcan

            @can('manage clients')
                <li @if (strpos(Request::route()->uri, 'client-management') !== false) class="active open" @endif>
                    <a href="#" title="Client Management" data-filter-tags="client">
                        <i class="fal fa-handshake"></i>
                        <span class="nav-link-text">Client Management</span>
                    </a>
                    <ul>
                        @can('client list')
                            <li @if (strpos(Request::route()->uri, 'client-management/clients') !== false) class="active" @endif>
                                <a href="{{ url('/client-management/clients') }}" title="Clients"
                                    data-filter-tags="clients">
                                    <span class="nav-link-text">Client List</span>
                                </a>
                            </li>
                        @endcan
                        {{-- @can('approve client') --}}
                        <li @if (strpos(Request::route()->uri, 'client-management/pending-clients') !== false) class="active" @endif>
                            <a href="{{ url('/client-management/pending-clients') }}" title="Pending Clients"
                                data-filter-tags="pending, clients">
                                <span class="nav-link-text">Pending Client List</span>
                            </a>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'client-management/rejected-clients') !== false) class="active" @endif>
                            <a href="{{ url('/client-management/rejected-clients') }}" title="Rejected Clients"
                                data-filter-tags="rejected, clients">
                                <span class="nav-link-text">Rejected Client List</span>
                            </a>
                        </li>
                        @can('closed clients list')
                            <li @if (strpos(Request::route()->uri, 'client-management/closed-clients') !== false) class="active" @endif>
                                <a href="{{ url('/client-management/closed-clients') }}" title="Closed Clients"
                                    data-filter-tags="closed, clients">
                                    <span class="nav-link-text">Closed Client List</span>
                                </a>
                            </li>
                        @endcan
                        <li @if (strpos(Request::route()->uri, 'client-management/draft-clients') !== false) class="active" @endif>
                            <a href="{{ url('/client-management/draft-clients') }}" title="Draft Clients"
                                data-filter-tags="draft, clients">
                                <span class="nav-link-text">Draft Client List</span>
                            </a>
                        </li>
                        {{-- @endcan --}}
                        @can('approve client')
                            <li @if (strpos(Request::route()->uri, 'client-management/pending-changes') !== false) class="active" @endif>
                                <a href="{{ url('/client-management/pending-changes') }}" title="Pending Changes"
                                    data-filter-tags="pending changes">
                                    <span class="nav-link-text">Pending Changes</span>
                                </a>
                            </li>
                        @endcan
                        @can('notify client')
                            <li @if (strpos(Request::route()->uri, 'client-management/client-notifications') !== false) class="active" @endif>
                                <a href="{{ url('/client-management/client-notifications') }}"
                                    title="Client Notifications & Actions" data-filter-tags="client notifications">
                                    <span class="nav-link-text">Client Notifications</span>
                                </a>
                            </li>
                        @endcan
                        @can('linked users')
                            <li @if (strpos(Request::route()->uri, 'client-management/client-users') !== false) class="active" @endif>
                                <a href="{{ url('/client-management/client-users') }}" title="Clients"
                                    data-filter-tags="clients">
                                    <span class="nav-link-text">Linked Users</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan
            @if (in_array('Compliance', $enabledModules))
                @can('compliance management')
                    <li @if (strpos(Request::route()->uri, 'dashboards/compliance') !== false) class="active open" @endif>
                        <a href="#" title="Compliance Management" data-filter-tags="Compliance">
                            <i class="fal fa-gavel"></i>
                            <span class="nav-link-text">Compliance Management</span>
                        </a>

                        @if (Auth::user()->can('sanctions screening') || Auth::user()->can('view worldcheck api logs'))
                            <ul>
                                @can('sanctions screening')
                                    {{--   <li @if (strpos(Request::route()->uri, 'dashboards/compliance-management/transactions-cases') !== false || strpos(Request::route()->uri, 'dashboards/compliance-management/transactions-detailed-case') !== false) class="active" @endif>
                                        <a href="{{ url('/dashboards/compliance-management/transactions-cases') }}"
                                            title="Transactions Cases" data-filter-tags="transactions-cases">
                                            <span class="nav-link-text">Transactions Cases List</span>
                                        </a>
                                    </li> --}}
                                    <li @if (strpos(Request::route()->uri, 'dashboards/compliance-management/cases') !== false ||
                                            strpos(Request::route()->uri, 'dashboards/compliance-management/detailed-case') !== false) class="active" @endif>
                                        <a href="{{ url('/dashboards/compliance-management/cases') }}" title="Cases"
                                            data-filter-tags="cases">
                                            <span class="nav-link-text">Screening Cases List</span>
                                        </a>
                                    </li>
                                    <li @if (strpos(Request::route()->uri, 'dashboards/compliance-management/flagged-transactions') !== false) class="active" @endif>
                                        <a href="{{ url('dashboards/compliance-management/flagged-transactions') }}" title="Flagged Transactions"
                                            data-filter-tags="cases">
                                            <span class="nav-link-text">Transaction Monitoring</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('view worldcheck api logs')
                                    <li @if (strpos(Request::route()->uri, 'dashboards/compliance-management/worldcheck-api-logs') !== false) class="active" @endif>
                                        <a href="{{ url('/dashboards/compliance-management/worldcheck-api-logs') }}"
                                            title="World-Check API Logs" data-filter-tags="worldcheck api logs">
                                            <span class="nav-link-text">World-Check API Logs</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        @endif

                    </li>
                @endcan
            @endif
            @if (in_array('Crypto', $enabledModules) )
                @can('crypto management')
                    @php
                        $navPath = ltrim((string) Request::path(), '/');
                        $isAquanowNav = strpos($navPath, 'aquanow') !== false;
                        $isFireblocksNav = strpos($navPath, 'fireblocks') !== false;
                        $isLiquidityNav = strpos($navPath, 'liquidity') !== false;
                        $isCryptoMgmtNav = strpos($navPath, 'crypto-management') !== false
                            || $isFireblocksNav || $isLiquidityNav || $isAquanowNav;
                    @endphp
                    <li @if ($isCryptoMgmtNav) class="active open" @endif>
                        <a href="#" title="Crypto Transactions" data-filter-tags="crypto transactions">
                            <i class="fal fa-badge-dollar"></i>
                            <span class="nav-link-text">Crypto Management</span>
                        </a>
                        <ul>
                            @can('currency list')
                                <li @if (strpos(Request::route()->uri, 'crypto-management/crypto-currencies') !== false) class="active" @endif>
                                    <a href="{{ url('/crypto-management/crypto-currencies') }}" title="Crypto Currency List"
                                        data-filter-tags="crypto currency">
                                        <span class="nav-link-text">Crypto Currency List</span>
                                    </a>
                                </li>
                            @endcan

                            @can('crypto transaction list')
                                <li @if (strpos(Request::route()->uri, 'crypto-management/crypto-transactions') !== false) class="active" @endif>
                                    <a href="{{ url('/crypto-management/crypto-transactions') }}" title="Crypto Transactions"
                                        data-filter-tags="crypto transactions">
                                        <span class="nav-link-text">Crypto Transactions List</span>
                                    </a>
                                </li>
                            @endcan
                            @can('crypto pending transactions list')
                                <li @if (strpos(Request::route()->uri, 'crypto-management/crypto-pending-transactions') !== false) class="active" @endif>
                                    <a href="{{ url('/crypto-management/crypto-pending-transactions') }}"
                                        title="Crypto Pending Transactions" data-filter-tags="pending transactions list">
                                        <span class="nav-link-text">Pending Requests List</span>
                                    </a>
                                </li>
                                <li @if (strpos(Request::route()->uri, 'crypto-management/crypto-rejected-transactions') !== false) class="active" @endif>
                                    <a href="{{ url('/crypto-management/crypto-rejected-transactions') }}"
                                        title="Crypto Rejected Transactions" data-filter-tags="rejected transactions list">
                                        <span class="nav-link-text">Rejected Requests List</span>
                                    </a>
                                </li>
                            @endcan
                            @if (in_array('Crypto', $enabledModules))
                                @can('crypto management')
                                    <li @if (strpos(Request::route()->uri, 'crypto-management/crypto-transactions/deposit-addresses') !== false) class="active" @endif>
                                        <a href="{{ url('/crypto-management/crypto-transactions/deposit-addresses') }}"
                                            title="Crypto Pending Transactions" data-filter-tags="bitpace deposit addresses">
                                            <span class="nav-link-text"> Deposit Addresses</span>
                                        </a>
                                    </li>
                                @endcan
                            @endif

                            @if (in_array('Crypto', $enabledModules) && in_array('Fireblocks', $enabledModules))
                                @can('crypto management')
                                 <li class="nav-item @if (strpos(Request::route()->uri, '/fireblocks/') !== false) active menu-open @endif">
                                    <a href="#" class="nav-link">
                                        <img src="{{ asset('assets/img/fireblocks/logo.svg') }}" alt="Fireblocks"
                                            style="width:18px;height:18px;margin-right:12px;vertical-align:middle;">
                                        <span class="nav-link-text">Fireblocks</span>

                                    </a>
                                    <ul class="nav nav-treeview" @if (strpos(Request::route()->uri, 'fireblocks') !== false) style="display: block;" @endif>
                                        @can('view fireblocks crypto networks')
                                        <li @if (strpos(Request::route()->uri, 'fireblocks/crypto-networks') !== false) class="active" @endif>
                                            <a href="{{ url('/fireblocks/crypto-networks') }}" title="Crypto Networks"
                                                data-filter-tags="crypto networks">
                                                <span class="nav-link-text">Crypto Networks</span>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('view fireblocks beneficiaries')
                                        <li @if (strpos(Request::route()->uri, 'fireblocks/beneficiaries') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/beneficiaries') }}"
                                            title="Fireblocks Beneficiaries" data-filter-tags="fireblocks beneficiaries">
                                            <span class="nav-link-text">Fireblocks Beneficiaries</span>
                                        </a>
                                    </li>
                                        @endcan
                                    {{-- <li @if (strpos(Request::route()->uri, 'fireblocks/withdraw') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/withdraw') }}"
                                            title="Fireblocks Withdraw" data-filter-tags="fireblocks withdraw">
                                            <span class="nav-link-text">Fireblocks Withdraw</span>
                                        </a>
                                    </li> --}}
                                    @if (\App\Modules\Fireblocks\Services\FireblocksConfigService::showVaultsMenu())
                                    @can('fireblocks custody')
                                    <li @if (strpos(Request::route()->uri, 'fireblocks/vaults') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/vaults') }}"
                                            title="Client vaults" data-filter-tags="fireblocks vaults client vaults">
                                            <span class="nav-link-text">Client vaults</span>
                                        </a>
                                    </li>
                                    @endcan
                                    @endif
                                    @can('view fireblocks withdrawal pool balances')
                                    <li @if (strpos(Request::route()->uri, 'fireblocks/withdrawal-pool-balances') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/withdrawal-pool-balances') }}"
                                            title="Withdrawal Pool Balances" data-filter-tags="fireblocks withdrawal pool balances custody">
                                            <span class="nav-link-text">Withdrawal Pool Balances</span>
                                        </a>
                                    </li>
                                    <li @if (strpos(Request::route()->uri, 'fireblocks/omnibus-balances') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/omnibus-balances') }}"
                                            title="Omnibus Balances" data-filter-tags="fireblocks omnibus balances source vault">
                                            <span class="nav-link-text">Omnibus Balances</span>
                                        </a>
                                    </li>
                                    @endcan
                                    @can('view fireblocks users')
                                    <li @if (strpos(Request::route()->uri, 'fireblocks/users') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/users') }}"
                                            title="Fireblocks Users" data-filter-tags="fireblocks users">
                                            <span class="nav-link-text">Fireblocks Users</span>
                                        </a>
                                    </li>
                                    @endcan
                                    @if (in_array('FireblocksTransactionsMonitoring', $enabledModules))
                                    @can('view fireblocks transaction monitoring')
                                    <li @if (strpos(Request::route()->uri, 'fireblocks/transaction-monitoring') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/transaction-monitoring') }}"
                                            title="Transaction Monitoring" data-filter-tags="fireblocks transaction monitoring">
                                            <span class="nav-link-text">Transaction Monitoring</span>
                                        </a>
                                    </li>
                                    @endcan
                                    @endif
                                    @if (in_array('FireblocksVaultSweeping', $enabledModules))
                                    @can('view fireblocks vault sweeping')
                                    <li @if (strpos(Request::route()->uri, 'fireblocks/vault-sweeping') !== false) class="active" @endif>
                                        <a href="{{ url('/fireblocks/vault-sweeping') }}"
                                            title="Vault Sweeping" data-filter-tags="fireblocks vault sweeping">
                                            <span class="nav-link-text">Vault Sweeping</span>
                                        </a>
                                    </li>
                                    @endcan
                                    @endif

                                    </ul>
                                </li>

                                @endcan
                            @endif
                            @if (in_array('Liquidity', $enabledModules) && in_array('B2c2', $enabledModules))
                                @can('crypto management')
                                 <li class="nav-item @if (strpos(Request::route()->uri, 'liquidity/crypto-management') !== false) active open @endif">
                                    <a href="#" class="nav-link">
                                        <img src="{{ asset('assets/img/b2c2/logo.svg') }}" alt="B2C2"
                                            style="width:18px;height:18px;margin-right:12px;vertical-align:middle;">
                                        <span class="nav-link-text">B2C2</span>
                                    </a>
                                    <ul class="nav nav-treeview" @if (strpos(Request::route()->uri, 'liquidity/crypto-management') !== false) style="display: block;" @endif>
                                        <li @if (strpos(Request::route()->uri, 'liquidity/crypto-management/instruments') !== false) class="active" @endif>
                                            <a href="{{ url('/liquidity/crypto-management/instruments/manage') }}"
                                                title="B2C2 Instruments" data-filter-tags="liquidity instruments b2c2">
                                                <span class="nav-link-text">Instruments</span>
                                            </a>
                                        </li>
                                        <li @if (strpos(Request::route()->uri, 'liquidity/crypto-management/balances') !== false) class="active" @endif>
                                            <a href="{{ url('/liquidity/crypto-management/balances/manage') }}"
                                                title="B2C2 Balances" data-filter-tags="liquidity balances b2c2">
                                                <span class="nav-link-text"> Balances</span>
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                                @endcan
                            @endif
                            @php
                                $aquanowModuleEnabled = false;
                                foreach ($enabledModules ?? [] as $enabledModuleName) {
                                    if (strcasecmp((string) $enabledModuleName, 'Aquanow') === 0) {
                                        $aquanowModuleEnabled = true;
                                        break;
                                    }
                                }
                                if (!$aquanowModuleEnabled) {
                                    $aquanowModuleEnabled = \App\Helpers\Common::isModuleEnabled('Aquanow');
                                }
                            @endphp
                            @if ($aquanowModuleEnabled)
                                @can('crypto management')
                                 <li class="nav-item @if ($isAquanowNav) active open @endif">
                                    <a href="#" class="nav-link" title="Aquanow" data-filter-tags="aquanow aqua now">
                                        <img src="{{ asset('assets/img/aquanow/logo.png') }}" alt="Aquanow"
                                            style="width:18px;height:18px;margin-right:12px;vertical-align:middle;">
                                        <span class="nav-link-text">Aquanow</span>
                                    </a>
                                    <ul class="nav nav-treeview" @if ($isAquanowNav) style="display: block;" @endif>
                                        @can('view aquanow instruments')
                                        <li @if (strpos($navPath, 'aquanow/crypto-management/instruments') !== false) class="active" @endif>
                                            <a href="{{ url('/aquanow/crypto-management/instruments/manage') }}"
                                                title="Aquanow Instruments" data-filter-tags="aquanow instruments">
                                                <span class="nav-link-text">Instruments</span>
                                            </a>
                                        </li>
                                        @endcan
                                        @can('view aquanow balances')
                                        <li @if (strpos($navPath, 'aquanow/crypto-management/balances') !== false) class="active" @endif>
                                            <a href="{{ url('/aquanow/crypto-management/balances/manage') }}"
                                                title="Aquanow Balances" data-filter-tags="aquanow balances">
                                                <span class="nav-link-text"> Balances</span>
                                            </a>
                                        </li>
                                        @endcan
                                    </ul>
                                </li>
                                @endcan
                            @endif
                        </ul>
                    </li>
                @endcan
            @endif

            @can('manage finance')
                <li @if (strpos(Request::route()->uri, 'finance-management') !== false && strpos(Request::route()->uri, 'crypto') === false) class="active open" @endif>
                    <a href="#" title="Finance Management" data-filter-tags="finance">
                        <i class="fal fa-money-check-alt"></i>
                        <span class="nav-link-text">Finance Management</span>
                    </a>
                    <ul>
                        @can('approve beneficiary')
                            <li @if (strpos(Request::route()->uri, 'finance-management/beneficiaries') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/beneficiaries/') }}" title="Beneficiary List"
                                    data-filter-tags="beneficiary list">
                                    <span class="nav-link-text">Beneficiary List</span>
                                </a>
                            </li>
                        @endcan

                        @can('currency list')
                            <li @if (strpos(Request::route()->uri, 'finance-management/currencies') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/currencies') }}" title="Currency List"
                                    data-filter-tags="currency">
                                    <span class="nav-link-text">Currency List</span>
                                </a>
                            </li>
                        @endcan
                        @can('bank list')
                            <li @if (strpos(Request::route()->uri, 'finance-management/banks') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/banks') }}" title="Banks" data-filter-tags="bank">
                                    <span class="nav-link-text">Bank List</span>
                                </a>
                            </li>
                        @endcan
                        @can('bank list')
                            <li @if (strpos(Request::route()->uri, 'finance-management/bank-ssi') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/bank-ssi') }}" title="SSI" data-filter-tags="SSI">
                                    <span class="nav-link-text">Bank SSI List</span>
                                </a>
                            </li>
                        @endcan
                        @can('transaction list')
                            @can('musd transactions list')
                                <li @if (strpos(Request::route()->uri, 'finance-management/transaction/musd-transactions') !== false) class="active" @endif>
                                    <a href="{{ url('/finance-management/transaction/musd-transactions') }}"
                                        title="MUSD Transactions List"
                                        data-filter-tags="musd transactions list">
                                        <span class="nav-link-text">MUSD Transactions List</span>
                                    </a>
                                </li>
                            @endcan
                            @can('maintenance fees list')
                                <li @if (strpos(Request::route()->uri, 'finance-management/transaction/maintenance-fees') !== false) class="active" @endif>
                                    <a href="{{ url('/finance-management/transaction/maintenance-fees') }}"
                                        title="Maintenance Fee Transactions List"
                                        data-filter-tags="maintenance fee transactions list">
                                        <span class="nav-link-text">Maintenance Fees List</span>
                                    </a>
                                </li>
                            @endcan
                        @endcan
                        @can('transaction list')
                            <li @if (strpos(Request::route()->uri, 'finance-management/transactions') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/transactions') }}" title="Approved Transactions List"
                                    data-filter-tags="transactions">
                                    <span class="nav-link-text">Transactions List</span>
                                </a>
                            </li>
                        @endcan
                        @can('pending transactions list')
                            <li @if (strpos(Request::route()->uri, 'finance-management/pending-transactions') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/pending-transactions') }}"
                                    title="Pending Transactions List" data-filter-tags="pending transactions">
                                    <span class="nav-link-text">Pending Requests List</span>
                                </a>
                            </li>
                        @endcan
                        @can('reviewed transactions requests')
                            <li @if (strpos(Request::route()->uri, 'finance-management/reviewed-transactions') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/reviewed-transactions') }}"
                                    title="Reviewed Only Transactions List" data-filter-tags="reviewed only transactions">
                                    <span class="nav-link-text">Reviewed Only Requests List</span>
                                </a>
                            </li>
                        @endcan
                        @can('initial review transactions requests')
                            <li @if (strpos(Request::route()->uri, 'finance-management/initial-review') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/initial-review-transactions') }}"
                                    title="Initial Review Transactions List" data-filter-tags="initial review transactions">
                                    <span class="nav-link-text">Initial Review Requests List</span>
                                </a>
                            </li>
                        @endcan
                        @can('rejected transactions requests')
                            <li @if (strpos(Request::route()->uri, 'finance-management/rejected-transactions') !== false) class="active" @endif>
                                <a href="{{ url('/finance-management/rejected-transactions') }}"
                                    title="Rejected Transactions List" data-filter-tags="rejected transactions">
                                    <span class="nav-link-text">Rejected Requests List</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endcan

            @if (in_array('MobileRequests', $enabledModules))
                @can('mobile requests list')
                    <li @if (strpos(Request::route()->uri, 'mobile-client-requests') !== false &&
                            strpos(Request::route()->uri, 'crypto') === false) class="active open" @endif>
                        <a href="#" title="Mobile Client Request" data-filter-tags="mobile">
                            <i class="fal fa-money-check-alt"></i>
                            <span class="nav-link-text">Client Service Requests</span>
                        </a>
                        <ul>
                            <li @if (strpos(Request::route()->uri, 'mobile-client-requests/approved-list') !== false) class="active" @endif>
                                <a href="{{ url('/mobile-client-requests/approved-list') }}"
                                    title="Approved Requests List" data-filter-tags="approved">
                                    <span class="nav-link-text">Approved Requests List</span>
                                </a>
                            </li>

                            <li @if (strpos(Request::route()->uri, 'mobile-client-requests/pending-list') !== false) class="active" @endif>
                                <a href="{{ url('/mobile-client-requests/pending-list') }}" title="Pending Requests List"
                                    data-filter-tags="pending">
                                    <span class="nav-link-text">Pending Requests List</span>
                                </a>
                            </li>

                            {{-- <li @if (strpos(Request::route()->uri, 'mobile-client-requests/otp-pending-list') !== false) class="active" @endif>
                                <a href="{{ url('/mobile-client-requests/otp-pending-list') }}"
                                    title="OTP Pending Requests List" data-filter-tags="otp pending">
                                    <span class="nav-link-text">OTP Pending Requests List</span>
                                </a>
                            </li> --}}

                            <li @if (strpos(Request::route()->uri, 'mobile-client-requests/rejected-list') !== false) class="active" @endif>
                                <a href="{{ url('/mobile-client-requests/rejected-list') }}"
                                    title="Rejected Requests List" data-filter-tags="rejected">
                                    <span class="nav-link-text">Rejected Requests List</span>
                                </a>
                            </li>

                        </ul>
                    </li>
                @endcan
            @endif
            @role('basic admin')
                <li @if (strpos(Request::route()->uri, 'reports-management') !== false) class="active open" @endif>
                    <a href="#" title="Reports Management" data-filter-tags="report">
                        <i class="fal fa-chart-bar"></i>
                        <span class="nav-link-text">Reports Management</span>
                    </a>
                    <ul>
                        @can('client account statement report')
                            <li @if (strpos(Request::route()->uri, 'reports-management/client/client-account-statement') !== false) class="active" @endif>
                                <a href="{{ url('/reports-management/client/client-account-statement') }}"
                                    title="Account Statement" data-filter-tags="statement">
                                    <span class="nav-link-text">Client Account Statement</span>
                                </a>
                            </li>
                        @endcan
                    </ul>
                </li>
            @endrole

            @can('reports management')

                <li @if (strpos(Request::route()->uri, 'reports-management') !== false) class="active open" @endif>
                    <a href="#" title="Reports Management" data-filter-tags="report">
                        <i class="fal fa-chart-bar"></i>
                        <span class="nav-link-text">Reports Management</span>
                    </a>
                    <ul>

                        <li @if (strpos(Request::route()->uri, 'reports-management/client/') !== false) class="active" @endif>
                            <a href="#" title="Clients Reports" data-filter-tags="client reports">
                                <span class="nav-link-text">Clients Reports</span>
                            </a>
                            <ul>
                                @can('currency wise')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/currency-wise') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/currency-wise') }}"
                                            title="Currency Deposit" data-filter-tags="currency deposit">
                                            <span class="nav-link-text">Currency Deposit Report</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('daily currency wise report')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/daily-currency-wise') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/daily-currency-wise') }}"
                                            title="Daily Currency Deposit" data-filter-tags="daily currency deposit">
                                            <span class="nav-link-text">Daily Currency Deposit Report</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('client wise')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/account-wise') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/account-wise') }}"
                                            title="Client Deposit List" data-filter-tags="client deposit list">
                                            <span class="nav-link-text">Client Deposit List Report</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('all clients')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/all-clients') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/all-clients') }}" title="Clients Report"
                                            data-filter-tags="clients report">
                                            <span class="nav-link-text">Clients Report</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('daily all clients report')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/daily-all-clients') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/daily-all-clients') }}"
                                            title="Daily All Clients" data-filter-tags="all clients">
                                            <span class="nav-link-text">Daily All Clients Report</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('user client wise')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/user-client-wise') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/user-client-wise') }}"
                                            title="User Wise Client Report" data-filter-tags="user wise client report">
                                            <span class="nav-link-text">User Clients Report</span>
                                        </a>
                                    </li>
                                @endcan
                                @can('account transaction wise')
                                    <li @if (strpos(Request::route()->uri, '6-months') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/account-transactions-6-months') }}"
                                            title="Account Transactions 6 Months"
                                            data-filter-tags="account transactions 6 months">
                                            <span class="nav-link-text">Transactions Report 6 Months</span>
                                        </a>
                                    </li>
                                    <li @if (Request::route()->uri == 'reports-management/client/account-transactions') class="active" @endif>
                                        <a href="{{ url('/reports-management/client/account-transactions') }}"
                                            title="Account Transactions" data-filter-tags="account transactions">
                                            <span class="nav-link-text">Transactions Report</span>
                                        </a>
                                    </li>
                                @endcan

                                <li @if (strpos(Request::route()->uri, 'reports-management/client/client-account-statement') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/client/client-account-statement') }}"
                                        title="Account Statement" data-filter-tags="statement">
                                        <span class="nav-link-text">Client Account Statement</span>
                                    </a>
                                </li>
                                <li @if (strpos(Request::route()->uri, 'reports-management/client/closed-client-account-statement') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/client/closed-client-account-statement') }}"
                                        title="Account Statement" data-filter-tags="statement">
                                        <span class="nav-link-text">Closed Client Account Statement</span>
                                    </a>
                                </li>
                                {{-- <li @if (strpos(Request::route()->uri, 'reports-management/client/onboarding-fee-report') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/client/onboarding-fee-report') }}"
                                        title="Onboarding Fee Report" data-filter-tags="onboarding">
                                        <span class="nav-link-text">Onboarding Fee Report</span>
                                    </a>
                                </li> --}}
                                @can('client risk report')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client/risk') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client/risk') }}"
                                            title="User Wise Client Report" data-filter-tags="Client risk report">
                                            <span class="nav-link-text">Client Risk Report</span>
                                        </a>
                                    </li>
                                @endcan
                            </ul>
                        </li>


                        <li @if (strpos(Request::route()->uri, 'reports-management/client-bank') !== false) class="active" @endif>
                            <a href="#" title="Client Banks Reports" data-filter-tags="client banks reports">
                                <span class="nav-link-text">Client Banks Reports</span>
                            </a>
                            <ul>
                                <li @if (strpos(Request::route()->uri, 'reports-management/client-bank-reports/all-banks-report') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/client-bank-reports/all-banks-report') }}"
                                        title="All Banks Report" data-filter-tags="all banks report">
                                        <span class="nav-link-text">All Banks Report</span>
                                    </a>
                                </li>

                                @can('daily all banks report')
                                    <li @if (strpos(Request::route()->uri, 'reports-management/client-bank-reports/daily-all-banks-report') !== false) class="active" @endif>
                                        <a href="{{ url('/reports-management/client-bank-reports/daily-all-banks-report') }}"
                                            title="Daily All Banks Report" data-filter-tags="all banks report">
                                            <span class="nav-link-text">Daily All Banks Report</span>
                                        </a>
                                    </li>
                                @endcan

                                <li @if (strpos(Request::route()->uri, 'reports-management/client-bank-reports/bank-account-statement') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/client-bank-reports/bank-account-statement') }}"
                                        title="Bank Client Account Statement" data-filter-tags="statement">
                                        <span class="nav-link-text">Bank Account Statement</span>
                                    </a>
                                </li>

                                <li @if (strpos(Request::route()->uri, 'reports-management/client-bank-reports/closed-bank-account-statement') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/client-bank-reports/closed-bank-account-statement') }}"
                                        title="Closed Bank Client Account Statement" data-filter-tags="statement">
                                        <span class="nav-link-text">Closed Bank Account Statement</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li @if (strpos(Request::route()->uri, 'reports-management/vostro-banks-reports') !== false) class="active" @endif>
                            <a href="#" title="Vostro Banks Reports" data-filter-tags="vostro banks reports">
                                <span class="nav-link-text">Vostro Banks Reports</span>
                            </a>
                            <ul>
                                <li @if (strpos(Request::route()->uri, 'reports-management/vostro-banks-reports/daily-banks-balances') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/vostro-banks-reports/daily-banks-balances') }}"
                                        title="Daily Banks Balances Reports"
                                        data-filter-tags="daily banks balances reports">
                                        <span class="nav-link-text">Daily Banks Balances Reports</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li @if (strpos(Request::route()->uri, 'reports-management/bank') !== false) class="active" @endif>
                            <a href="#" title="Vostro Banks Reports" data-filter-tags="vostro banks reports">
                                <span class="nav-link-text">Own Banks Reports</span>
                            </a>
                            <ul>
                                <li @if (strpos(Request::route()->uri, 'reports-management/bank-account-statement') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/bank-account-statement') }}"
                                        title="Own Banks Account Statement"
                                        data-filter-tags="own banks account statement">
                                        <span class="nav-link-text">Own Banks Account Statement</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'maintenance-fee') !== false) class="active" @endif>
                            <a href="#" title="Maintenance Fee Reports" data-filter-tags="maintenance fee reports">
                                <span class="nav-link-text">Maintenance Fee Reports</span>
                            </a>
                            <ul>
                                <li @if (strpos(Request::route()->uri, 'reports-management/maintenance-fee/revenue') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/maintenance-fee/revenue') }}"
                                        title="Maintenance Fee Revenue" data-filter-tags="maintenance fee revenue">
                                        <span class="nav-link-text">Maintenance Fee Revenue</span>
                                    </a>
                                </li>
                                <li @if (strpos(Request::route()->uri, 'reports-management/maintenance-fee-report') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/maintenance-fee-report') }}"
                                        title="Maintenance Fee Report" data-filter-tags="maintenance">
                                        <span class="nav-link-text">Maintenance Fee Report</span>
                                    </a>
                                </li>
                                <li @if (strpos(Request::route()->uri, 'reports-management/pending-maintenance-fee-report') !== false) class="active" @endif>
                                    <a href="{{ url('/reports-management/pending-maintenance-fee-report') }}"
                                        title="Pending Maintenance Fee Report"
                                        data-filter-tags="pending maintenance fee report">
                                        <span class="nav-link-text">Pending Maintenance Fee Report</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </li>
            @endcan

            @can('manage users')<li @if (strpos(Request::route()->uri, 'user-management') !== false) class="active open" @endif>
                    <a href="#" title="User Management" data-filter-tags="package">
                        <i class="fal fa-users"></i>
                        <span class="nav-link-text">User Management</span>
                    </a>

                    <ul>
                        @can('list users')
                            <li @if (strpos(Request::route()->uri, 'user-management/back-office-users') !== false) class="active" @endif>
                                <a href="{{ url('/user-management/back-office-users') }}" title="Back Office User List" data-filter-tags="back-office-users">
                                    <span class="nav-link-text">Back Office Users List</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'user-management/users') !== false) class="active" @endif>
                                <a href="{{ url('/user-management/users') }}" title="User List" data-filter-tags="user">
                                    <span class="nav-link-text">Clients Users List</span>
                                </a>
                            </li>
                        @endcan
                        @can('link user to client')
                            <li @if (strpos(Request::route()->uri, 'user-management/link-user') !== false) class="active" @endif>
                                <a href="{{ url('/user-management/link-user') }}" title="Link User to Client"
                                    data-filter-tags="link user to client">
                                    <span class="nav-link-text">Link User to Client</span>
                                </a>
                            </li>
                        @endcan

                        @role('super admin')
                            <li @if (strpos(Request::route()->uri, 'user-management/roles') !== false) class="active" @endif>
                                <a href="{{ url('/user-management/roles') }}" title="User Roles" data-filter-tags="role">
                                    <span class="nav-link-text">Roles</span>
                                </a>
                            </li>
                        @endrole

                        @role('super admin')
                            <li @if (strpos(Request::route()->uri, 'user-management/permissions') !== false) class="active" @endif>
                                <a href="{{ url('/user-management/permissions') }}" title="User Permissions"
                                    data-filter-tags="permission">
                                    <span class="nav-link-text">Permissions</span>
                                </a>
                            </li>
                        @endrole

                    </ul>
            </li>@endcan

            @can('manage users')<li @if (strpos(Request::route()->uri, 'user-trace') !== false) class="active open" @endif>
                    <a href="#" title="User Trace" data-filter-tags="package">
                        <i class="fal fa-user-alien"></i>
                        <span class="nav-link-text">Monitoring</span>
                    </a>

                    <ul>
                        @role('super admin')
                            <li @if (strpos(Request::route()->uri, 'user-trace/active-users') !== false) class="active" @endif>
                                <a href="{{ url('/user-trace/active-users') }}" title="Active Users"
                                    data-filter-tags="active users">
                                    <span class="nav-link-text">Active Users</span>
                                </a>
                            </li>
                        @endrole

                        @role('super admin')
                            <li @if (strpos(Request::route()->uri, 'user-trace/user-access-logs') !== false) class="active" @endif>
                                <a href="{{ url('/user-trace/user-access-logs') }}" title="User Access Logs"
                                    data-filter-tags="user access logs">
                                    <span class="nav-link-text">User Access Logs</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'user-trace/user-journey-logs') !== false) class="active" @endif>
                                <a href="{{ url('/user-trace/user-journey-logs') }}" title="User Journey Logs"
                                    data-filter-tags="user journey logs analytics">
                                    <span class="nav-link-text">User Journey {{ \App\Helpers\Common::isModuleEnabled('UserJourneyAnalytics') ? 'Analytics' : 'Logs' }}</span>
                                </a>
                            </li>
                        @endrole

                        @role('super admin')
                            <li @if (strpos(Request::route()->uri, 'user-trace/user-audits') !== false) class="active" @endif>
                                <a href="{{ url('/user-trace/user-audits') }}" title="User Audit"
                                    data-filter-tags="user audit">
                                    <span class="nav-link-text">User Audit</span>
                                </a>
                            </li>
                        @endrole

                    </ul>
            </li>@endcan

            @role('super admin')
                @can('manage data')
                    <li @if (strpos(Request::route()->uri, 'data-management') !== false) class="active open" @endif>
                        <a href="#" title="Data Management" data-filter-tags="data">
                            <i class="fal fa-database"></i>
                            <span class="nav-link-text">Data Management</span>
                        </a>
                        <ul>
                            @can('database backups')
                                <li @if (strpos(Request::route()->uri, 'data-management/backups') !== false) class="active" @endif>
                                    <a href="{{ url('/data-management/backups') }}" title="Database Backups"
                                        data-filter-tags="database backups">
                                        <span class="nav-link-text">Database Backups</span>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endcan
            @endrole

            @can('settings')
                <li @if (strpos(Request::route()->uri, 'settings') !== false) class="active" @endif>
                    <a href="#" title="Settings" data-filter-tags="settings">
                        <i class="fal fa-cog"></i>
                        <span class="nav-link-text">System Settings</span>
                    </a>

                    <ul>
                        <li @if (strpos(Request::route()->uri, 'settings/client-file-config') !== false) class="active" @endif>
                            <a href="{{ url('/settings/client-file-config') }}" title="Client File Config"
                                data-filter-tags="client file config">
                                <span class="nav-link-text">Client Files Config</span>
                            </a>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'settings/webhook-ip-whitelists') !== false) class="active" @endif>
                            <a href="{{ url('/settings/webhook-ip-whitelists') }}" title="Webhook IP Whitelists"
                                data-filter-tags="webhook ip whitelists">
                                <span class="nav-link-text">Webhook IP Whitelists</span>
                            </a>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'settings/general-configurations') !== false) class="active" @endif>
                            <a href="#" title="General Configurations" data-filter-tags="general configurations">
                                <span class="nav-link-text">General Configurations</span>
                            </a>
                            <ul>
                                <li @if (strpos(Request::route()->uri, 'settings/general-configurations/dropdown-configurations') !== false) class="active" @endif>
                                    <a href="{{ url('/settings/general-configurations/dropdown-configurations') }}"
                                        title="Dropdowns Configurations" data-filter-tags="dropdowns configurations">
                                        <span class="nav-link-text">Dropdowns Configurations</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'settings/own-company-details') !== false) class="active" @endif>
                            <a href="{{ url('/settings/own-company-details') }}" title="Own Company Settings"
                                data-filter-tags="Own Company Settings">
                                <span class="nav-link-text">Own Company Settings</span>
                            </a>
                        </li>
                        <li @if (strpos(Request::route()->uri, 'settings/aml') !== false) class="active" @endif>
                            <a href="{{ url('/settings/aml') }}" title="Own Company Settings"
                                data-filter-tags="Own Company Settings">
                                <span class="nav-link-text">AML Rules</span>
                            </a>
                        </li>

                        @if (in_array('OpenPayd', $enabledModules))
                            <li @if (strpos(Request::route()->uri, 'settings/openpayd-sync-data') !== false) class="active" @endif>
                                <a href="{{ url('/settings/openpayd-sync-data') }}" title="Openpayd Sync Data"
                                    data-filter-tags="Openpayd sync data">
                                    <span class="nav-link-text">Openpayd Sync Data</span>
                                </a>
                            </li>
                        @endif

                        @if (in_array('Lorum', $enabledModules))
                            <li @if (strpos(Request::route()->uri, 'settings/lorum-sync-data') !== false) class="active" @endif>
                                <a href="{{ url('/settings/lorum-sync-data') }}" title="Lorum Sync Data"
                                    data-filter-tags="lorum sync data">
                                    <span class="nav-link-text">Lorum Sync Data</span>
                                </a>
                            </li>
                        @endif

                        @role('super admin')
                            <li @if (strpos(Request::route()->uri, 'settings/modules') !== false) class="active" @endif>
                                <a href="{{ url('/settings/modules') }}" title="Modules" data-filter-tags="Modules">
                                    <span class="nav-link-text">Modules</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'settings/module-configurations') !== false) class="active" @endif>
                                <a href="{{ url('/settings/module-configurations') }}" title="Module Configurations"
                                    data-filter-tags="Module Configurations">
                                    <span class="nav-link-text">Module Configurations</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'settings/api-clients') !== false) class="active" @endif>
                                <a href="{{ url('/settings/api-clients') }}" title="API Clients"
                                    data-filter-tags="API Clients">
                                    <span class="nav-link-text">API Clients</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'settings/fee-engine') !== false) class="active" @endif>
                                <a href="{{ url('/settings/fee-engine/groups') }}" title="Fee Engine"
                                    data-filter-tags="Fee Engine Fees Tier">
                                    <span class="nav-link-text">Fee Engine</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'settings/queue-management') !== false) class="active" @endif>
                                <a href="{{ url('/settings/queue-management') }}" title="Queue Management"
                                    data-filter-tags="queue jobs failed">
                                    <span class="nav-link-text">Queue Management</span>
                                </a>
                            </li>
                            <li @if (strpos(Request::route()->uri, 'settings/scheduler-management') !== false) class="active" @endif>
                                <a href="{{ url('/settings/scheduler-management') }}" title="Scheduler Management"
                                    data-filter-tags="cron scheduler tasks">
                                    <span class="nav-link-text">Cron / Scheduler</span>
                                </a>
                            </li>
                        @endrole

                    </ul>
                </li>
            @endcan

        </ul>
        <div class="filter-message js-filter-message bg-success-600"></div>
    </nav>
    <!-- END PRIMARY NAVIGATION -->
</aside>
<!-- END Left Aside -->
