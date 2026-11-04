<div class="col col-md-6 col-lg-7 hidden-sm-down">
    <h2 class="fs-xxl fw-500 mt-4 text-white">
        {{ $ownCompany ? $ownCompany->name : '' }}
    </h2>
    <p>Manage your accounts with ease, giving you the peace of mind you ever wanted. Login to your account and explore
        your accounts, watch your account balance and your cash flow dashboards.</p>
    <p>
        We will be launching our registration feature soon, through which one can register easily, and upon KYC the
        account will be approved and access will be granted.</p>
    <p>
        If you have any trouble or inquiry, please reach us any time by {{ $ownCompany ? $ownCompany->tech_email : '' }}
    </p>
    @php $urls = explode(',', $ownCompany->find_us); @endphp
    @if (count($urls) > 0 && $urls[0] !== '')
        <div class="d-sm-flex flex-column align-items-center justify-content-center d-md-block">
            <div class="px-0 py-1 mt-5 text-white fs-nano opacity-50">
                Find us on social media
            </div>
            <div class="d-flex flex-row opacity-70">
                {{--                                    <a href="#" class="mr-2 fs-xxl text-white"> --}}
                {{--                                        <i class="fab fa-facebook-square"></i> --}}
                {{--                                    </a> --}}
                {{--                                    <a href="#" class="mr-2 fs-xxl text-white"> --}}
                {{--                                        <i class="fab fa-twitter-square"></i> --}}
                {{--                                    </a> --}}
                {{--                                    <a href="#" class="mr-2 fs-xxl text-white"> --}}
                {{--                                        <i class="fab fa-google-plus-square"></i> --}}
                {{--                                    </a> --}}
                @foreach ($urls as $url)
                    @php
                        preg_match('/www\.([a-z]+)\./i', $url, $matches);
                        $icon = $matches[1];
                    @endphp

                    <a target="_blank" href="{{ $url }}" class="mr-2 fs-xxl text-white">
                        <i class="fab fa-{{ $icon }}"></i>
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>