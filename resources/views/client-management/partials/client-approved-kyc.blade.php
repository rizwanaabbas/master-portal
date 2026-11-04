@php
    $updatedAtTimestamp =
        isset($sanctionReport) && isset($sanctionReport->updated_at)
            ? \Carbon\Carbon::parse($sanctionReport->updated_at)->timestamp
            : null;
@endphp

@if ($client->type !== 'company')
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                {{-- @php
                    $btnText = $client->is_kyc_requested ? 'Resend KYC Request' : 'Request KYC';
                @endphp

                @if (in_array('Sumsub', $enabledModules))
                    <div class="col-md-12 mt-4 p-3 border rounded bg-light">
                        <label class="font-weight-bold mb-2 text-primary">Sumsub Verification:</label>

                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="client-req-selfie" value="1" checked>
                            <label class="form-check-label" for="client-req-selfie">Request Selfie</label>
                        </div>
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" id="client-req-passport" value="1"
                                checked>
                            <label class="form-check-label" for="client-req-passport">Request Passport</label>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm mt-2" id="client-req-btn"
                            onclick="sendClientSumsubKYCRequest('{{ $clientKYC->sumsub_applicant_id ?? '' }}')">
                            {{ $btnText }}
                        </button>

                        <div class="client-error-message mt-4">
                            @if ($client->is_kyc_requested)
                                <div class="text-info font-weight-bold"> Kyc Request sent </div>
                            @endif
                        </div>
                    </div>
                @else --}}
                @if ($client->is_kyc_requested)
                    <div>KYC already requested</div>
                    <hr>
                    <button onclick="requestClientCompliance()" class="btn btn-primary">Request selfie photo kyc
                        again</button>
                @else
                    <button onclick="requestClientCompliance()" class="btn btn-primary">Request selfie photo
                        kyc</button>
                    {{-- @endif --}}
                @endif
            </div>
        </div>
    </div>
    @if (in_array('Compliance', $enabledModules))
        @php $worldCheckEnabled = \App\Services\WorldCheckConfigService::isEnabled(); @endphp
        <br>
        <div class="row">
            <div class="col-md-6 d-flex align-items-center flex-wrap">
                @if ($worldCheckEnabled || !empty($sanctionReport->url ?? null))
                    @isset($sanctionReport)
                        @isset($sanctionReport->url)
                            <a href="{{ $sanctionReport->url }}" class="btn btn-sm btn-outline-secondary mr-2" target="_blank">
                                <i class="fal fa-file-alt"></i> View Report
                            </a>
                            @if ($worldCheckEnabled)
                                <button onclick="worldCheckReportRequest(); return false;" class="btn btn-primary mr-3">
                                    Request new Report
                                </button>
                            @endif
                        @elseif ($worldCheckEnabled)
                            <button id="getReportBtn" onclick="worldCheckReportGetStatusAndSave();  return false;"
                                class="btn btn-sm btn-outline-secondary mr-2"
                                @if (isset($sanctionReport) &&
                                        isset($sanctionReport->updated_at) &&
                                        \Carbon\Carbon::now()->diffInSeconds($sanctionReport->updated_at) < 60) disabled data-toggle="tooltip" data-placement="top"
                            title="This button is disabled for 60 seconds after requesting a report." @endif>
                                <i class="fal fa-file-alt"></i> Get Report
                            </button>

                            <button onclick="worldCheckReportRequest(); return false;" class="btn btn-primary mr-3">
                                Request new Report
                            </button>
                        @endisset
                    @elseif ($worldCheckEnabled)
                        <button onclick="worldCheckReportRequest(); return false;" class="btn btn-primary mr-3">
                            Request Report
                        </button>
                    @endisset
                @endif

                <form method="post" action="{{ url('/client-management/clients/upload-selfie-photo') }}"
                    enctype="multipart/form-data" id="uploadSelfieForm" class="d-flex align-items-center">
                    @csrf
                    <input type="hidden" name="client_id" value="{{ $client->id }}">
                    <label for="selfie_photo_url" class="btn btn-primary btn-sm mr-2 mb-0">Upload Selfie</label>
                    <span id="file-name-display" class="text-muted small mb-0">
                        choose jpg/png, recommended size 50x50
                    </span>
                    <input type="file" name="selfie_photo_url" accept="image/*" id="selfie_photo_url"
                        class="invisible item-file" />
                </form>
            </div>

            @if ($worldCheckEnabled)
                <div class="col-md-6 text-right">
                    <p class="text-muted fs-base">
                        Powered by <span class="lseg-color">LSEG</span> World-Check One
                    </p>
                </div>
            @endif
        </div>
    @endif
@endif

<form method="POST" name="request-client-kyc" id="request_client_kyc"
    action="{{ url('/client-management/clients/request-client-kyc') }}">
    @csrf
    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
</form>

<form method="POST" name="worldCheck-report-request" id="worldCheck-report-request"
    action="{{ url('/client-management/clients/worldCheck-report-request') }}">
    @csrf
    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
</form>

<form method="POST" name="worldCheck-report-get-status" id="worldCheck_report_get_status_and_save"
    action="{{ url('/client-management/clients/worldCheck-report-get-status-and-save') }}">
    @csrf
    <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
</form>

<script>
    // function sendClientSumsubKYCRequest(applicantId) {
    //     $('#loading-spinner').show();
    //     var client_id = "{{ $client->id }}"; // Fixed to always resolve Client ID natively!

    //     var reqSelfie = $('#client-req-selfie').is(':checked');
    //     var reqPassport = $('#client-req-passport').is(':checked');

    //     $('.client-error-message').html('').hide().removeClass('alert-danger alert-info');

    //     if (!reqSelfie && !reqPassport) {
    //         $('.client-error-message').html('Please select Either selfie or passport to send request.')
    //             .addClass('alert alert-danger').show();
    //         $('#loading-spinner').hide();
    //         return false;
    //     }

    //     var level_name = '';
    //     if (reqSelfie && reqPassport) {
    //         level_name = 'passport-n-liveness-link';
    //     } else if (reqSelfie) {
    //         level_name = 'liveness-link';
    //     } else if (reqPassport) {
    //         level_name = 'passport-link';
    //     }

    //     // Removed applicantId check so we can request natively
    //     if (empty(client_id) || empty(level_name)) {
    //         $('.client-error-message').html('Invalid data. Please ensure the level is selected.')
    //             .addClass('alert alert-danger').show();
    //         $('#loading-spinner').hide();
    //         return false;
    //     }

    //     $.post("{{ url('/client-management/clients/request-sumsub-kyc') }}", {
    //             "_token": "{{ csrf_token() }}",
    //             "client_id": client_id,
    //             "applicant_id": applicantId,
    //             "level_name": level_name
    //         },
    //         function(data, status) {
    //             if (typeof data === 'string') {
    //                 data = $.parseJSON(data);
    //             }

    //             $('.client-error-message').show();
    //             if (data.status === 'error') {
    //                 $('.client-error-message').text(data.message).addClass('alert alert-danger');
    //                 $('#loading-spinner').hide();
    //                 return false;
    //             }

    //             if (data.status === 'success') {
    //                 $('#loading-spinner').hide();
    //                 var successHtml = data.message + '<br><a href="' + data.url +
    //                     '" target="_blank" class="btn btn-sm btn-success mt-2">Open Sumsub Link</a>';
    //                 $('.client-error-message').html(successHtml).addClass('alert alert-info');

    //                 // Update the button text to "Resend"
    //                 $('#client-req-btn').html('Resend KYC Request');
    //                 return false;
    //             }
    //         }).fail(function(xhr) {
    //         $('#loading-spinner').hide();
    //         $('.client-error-message').html('Server error occurred while communicating with Sumsub.')
    //             .addClass('alert alert-danger').show();
    //     });
    // }

    function requestClientCompliance() {

        Swal.fire({
            title: "Are you sure?",
            text: "The Client request KYC will be sent.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, request it!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('request_client_kyc').submit()
            }
        });
    }

    function worldCheckReportRequest() {

        Swal.fire({
            title: "Are you sure?",
            text: "The Client Report will be requested.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, request it!"
        }).then(function(result) {
            if (result.value) {
                $('#loading-spinner').show();
                document.getElementById('worldCheck_report_request').submit()
            }
        });
    }

    function worldCheckReportGetStatusAndSave() {
        Swal.fire({
            title: "Are you sure?",
            text: "The Client Report status will be checked.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, check Report!"
        }).then(function(result) {
            if (result.value) {
                document.getElementById('worldCheck_report_get_status_and_save').submit()
            }
        });
    }

    document.getElementById('selfie_photo_url').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        document.getElementById('uploadSelfieForm').submit();
    });


    document.addEventListener('DOMContentLoaded', function() {
        var updatedAt = {{ $updatedAtTimestamp ?? 'null' }};
        if (updatedAt) {
            var now = Math.floor(Date.now() / 1000);
            var diff = now - updatedAt;
            if (diff < 60) {
                var remaining = (60 - diff) * 1000;
                setTimeout(function() {
                    document.getElementById('getReportBtn').disabled = false;
                }, remaining);
            }
        }
    });
</script>
