@php $settings = []; @endphp
<!-- BEGIN Page Header -->
<header class="page-header @if (env('APP_ENV') == 'staging') bg-primary @endif" role="banner">
    <!-- we need this logo when user switches to nav-function-top -->
    <div class="page-logo">
        <a href="#" class="page-logo-link press-scale-down d-flex align-items-center position-relative"
            data-toggle="modal" data-target="#modal-shortcut">
            <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="{{ $ownCompany ? $ownCompany->short_name : '' }}"
                aria-roledescription="logo">
            <span class="page-logo-text mr-1">{{ $ownCompany ? $ownCompany->short_name : '' }}</span>
            <span class="position-absolute text-white opacity-50 small pos-top pos-right mr-2 mt-n2"></span>
            <i class="fal fa-angle-down d-inline-block ml-1 fs-lg color-primary-300"></i>
        </a>

        @if ($settings && $settings->logo_url != '')
            <img style="max-height: 40px;" class="mt-2" src="{{ $settings->logo_url }}"
                alt="{{ $ownCompany ? $ownCompany->short_name : '' }}" aria-roledescription="logo">
        @else
            <img src="{{ $ownCompany ? $ownCompany->logo : '' }}" alt="{{ $ownCompany ? $ownCompany->short_name : '' }}"
                aria-roledescription="logo">
        @endif
    </div>

    <!-- DOC: mobile button appears during mobile width -->
    <div class="hidden-lg-up">
        <a href="#" class="header-btn btn press-scale-down" data-action="toggle" data-class="mobile-nav-on">
            <i class="ni ni-menu"></i>
        </a>
    </div>

    @php
        if (\Illuminate\Support\Facades\Auth::check()) {
            $notifications = \App\Helpers\Common::getNotifications(\Illuminate\Support\Facades\Auth::user()->id);
        } else {
            $notifications = [];
        }
        
    @endphp
    @if (env('APP_ENV') == 'staging')
        <div>
            <h3><strong><i class="fal fa-ruler-triangle"></i> {{ $ownCompany ? $ownCompany->short_name : '' }} Testing
                    Environment</strong></h3>
        </div>
    @endif
    <div class="ml-auto d-flex">
        <!-- app notification -->
        <div>
            @if (\Illuminate\Support\Facades\Session::has('impersonator'))
                <a class="mt-3 btn btn-sm btn-secondary" href="{{ url('/user-management/user/switch') }}">
                    <i class="fal fa-lock" title="Switch user back"></i> Switch
                </a>
            @endif
        </div>
        <div>
            <a href="#" class="header-icon" data-toggle="dropdown" title="You got 11 notifications">
                <i class="fal fa-bell @if (env('APP_ENV') == 'staging') text-white @endif"></i>
                
                    @if ($notifications)
                        @if ($notifications->where('status', 'unread')->count() > 0)
                            <span class="badge badge-icon">{{ $notifications->where('status', 'unread')->count() }}</span>
                        @endif
                    @endif
                
            </a>
            <div class="dropdown-menu dropdown-menu-animated dropdown-xl">
                <div
                    class="dropdown-header bg-trans-gradient d-flex justify-content-center align-items-center rounded-top mb-2">
                    <h4 class="m-0 text-center color-white">
                        <small class="mb-0 opacity-80">Notifications</small>
                    </h4>
                </div>

                <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link px-4 fs-md js-waves-on fw-500 active" data-toggle="tab" href="#tab-messages"
                            data-i18n="drpdwn.messages">System</a>
                    </li>
                </ul>
                <div class="tab-content tab-notification">
                    <div class="tab-pane active" id="tab-messages" role="tabpanel">
                        <div class="custom-scroll h-100">
                            <ul class="notification">
                            @if ($notifications)
                                @if ($notifications->count() > 0)
                                    @foreach ($notifications as $notification)
                                        <li id="notification-{{ $notification->id }}"
                                            class="{{ $notification->status }} notify">
                                            <a href="@if (strpos($notification->action_url, 'compliance-management/cases')) {{ $notification->action_url }}@else {{ $notification->action_url }}/{{ $notification->id }} @endif"
                                                class="d-flex align-items-center">
                                                <span class="mr-2">
                                                    @if ($notification->photo_url)
                                                        <img src="{{ $notification->photo_url }}"
                                                            class="profile-image rounded-circle" alt="Profile Photo">
                                                    @else
                                                        <img src="{{ asset('assets/img/avatar.png') }}"
                                                            class="profile-image rounded-circle" alt="Profile Photo">
                                                    @endif
                                                </span>
                                                <span class="d-flex flex-column flex-1 ml-1">
                                                    <span class="name">{{ $notification->title }} </span>
                                                    <span class="msg-b fs-xs">{{ $notification->message }}.</span>
                                                    <span
                                                        class="fs-nano text-muted mt-1">{{ $notification->created_at->diffForHumans() }}</span>
                                                </span>
                                            </a>
                                            <a href="javascript:deleteNotification({{ $notification->id }})"><span
                                                    class="badge fw-n position-absolute pos-bottom pos-right mb-2 mr-2"><i
                                                        class="alert-danger fal fa-times-circle rounded-circle"></i>
                                                    Delete</span></a>
                                        </li>
                                    @endforeach
                                @else
                                    <li class="">
                                        <a href="#" class="d-flex align-items-center">
                                            <span class="d-flex flex-column flex-1 ml-1">
                                                No new messages.
                                            </span>
                                        </a>
                                    </li>
                                @endif
                            @endif
                            </ul>
                        </div>
                    </div>
                </div>
                <div
                    class="py-2 px-3 bg-faded d-block rounded-bottom text-right border-faded border-bottom-0 border-right-0 border-left-0">
                    <a href="javascript:deleteAllNotifications()" class="fs-xs fw-500 ml-auto">Delete all
                        notifications</a>
                </div>
            </div>
        </div>
        <!-- app user menu -->
        <div>
            <a href="#" data-toggle="dropdown" title="drlantern@gotbootstrap.com"
                class="header-icon d-flex align-items-center justify-content-center ml-2">
            @if(!empty(\Illuminate\Support\Facades\Auth::user()->id))
                @if (\Illuminate\Support\Facades\Auth::user()->profile_photo_url)
                    <img src="{{ \Illuminate\Support\Facades\Auth::user()->profile_photo_url }}"
                        style="width: 32px; height:32px" class="border border-primary profile-image rounded-circle"
                        alt="Profile Photo">
                @else
                    <img src="{{ asset('assets/img/avatar.png') }}" class="profile-image rounded-circle"
                        alt="Dr. Codex Lantern">
                @endif
            @endif

            </a>
            @if(!empty(\Illuminate\Support\Facades\Auth::user()->id))
            <div class="dropdown-menu dropdown-menu-animated dropdown-lg">
                <div class="dropdown-header bg-trans-gradient d-flex flex-row py-4 rounded-top">
                    <div class="d-flex flex-row align-items-center mt-1 mb-1 color-white">
                        <span class="mr-2">
                            @if (\Illuminate\Support\Facades\Auth::user()->profile_photo_url)
                                <img src="{{ \Illuminate\Support\Facades\Auth::user()->profile_photo_url }}"
                                    style="width: 50px; height:50px"
                                    class="border border-primary profile-image rounded-circle" alt="Profile Photo">
                            @else
                                <img src="{{ asset('assets/img/avatar.png') }}" class="profile-image rounded-circle"
                                    alt="Dr. Codex Lantern">
                            @endif
                        </span>
                        <div class="info-card-text">
                            <div class="fs-lg text-truncate text-truncate-lg">
                                {{ \Illuminate\Support\Facades\Auth::user()->name }}</div>
                            <span
                                class="text-truncate text-truncate-md opacity-80">{{ \Illuminate\Support\Facades\Auth::user()->email }}</span>
                        </div>
                    </div>
                </div>
                <div class="dropdown-divider m-0"></div>
                <a href="{{ url('/profile') }}" class="dropdown-item">
                    <span>Profile</span>
                </a>
                <div class="dropdown-divider m-0"></div>
                <a href="{{ url('/change-password') }}" class="dropdown-item">
                    <span>Change Password</span>
                </a>
                {{-- @role('client')
                <div class="dropdown-divider m-0"> </div>
                    <button  onclick="closeAccount()" class="dropdown-item">
                        Close Account
                    </button>
                @endrole --}}
                <div class="dropdown-divider m-0"></div>
                <a href="{{ url('/logout') }}" class="dropdown-item">
                    <span>Logout</span>
                </a>



            </div>
            @endif
        </div>
    </div>
</header>
<!-- END Page Header -->

{{-- <form action="{{ url('/client/services/requests/store') }}" method="post" name="close-account" id="close_account">
    @csrf
    <input type="hidden" name="service_type" id="close_service" value="CLOSE_ACCOUNT">
</form> --}}

<script>
    // function closeAccount() {
    //     Swal.fire({
    //         title: "Are you sure",
    //         text: "You are about to request to close your account. Do you want to proceed?",
    //         type: "danger",
    //         icon: "warning",
    //         dangerMode: true,
    //         showCancelButton: true,
    //         confirmButtonText: "Yes!"
    //     }).then(function(result) {
    //         if (result.value) {
    //             document.getElementById('close_account').submit()
    //         }
    //     })
    // }
</script>
