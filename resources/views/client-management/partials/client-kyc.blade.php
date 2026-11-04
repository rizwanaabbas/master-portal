@if ($clientKYC->client->type != 'company')

    @php
        $updatedAtTimestamp =
            isset($sanctionReport) && isset($sanctionReport->updated_at)
                ? \Carbon\Carbon::parse($sanctionReport->updated_at)->timestamp
                : null;
    @endphp

    <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
        <thead class="thead-dark">
            <tr>
                <th width="200">Identity Documents</th>
                <td>
                    <div class="row d-flex justify-content-center">
                        <div class="col-xs-10 col-sm-10 col-md-6">
                            <div class="row">
                                @php
                                    $passportUrl = $passport_url == '' ? $clientKYC->passport_copy_url : $passport_url;
                                @endphp
                                @if ($passport_url == '')
                                    <div class="col-md-12 d-flex justify-content-center mt-5 text-center">
                                        No passport has been found. </div>
                                @else
                                    <div class="col-md-12 d-flex justify-content-center mt-2">

                                        <a href=" {{ $passportUrl }}" class="btn btn-sm btn-outline-secondary"
                                            target="_blank"><i class="fal fa-file-alt"></i> Passport</a>
                                    </div>
                                    <div class="col-md-12 d-flex justify-content-center mt-2">
                                        <embed src="{{ $passportUrl }}" alt="Client Passport"
                                            style="max-width: -webkit-fill-available; max-height: 428px; min-height: 300px; outline: none;
                                        border: none;"
                                            height="428px" controls="false" />

                                    </div>
                                @endif

                            </div>
                        </div>
                        <div class="col-xs-10 col-sm-10 col-md-6">
                            <div class="row">
                                @if (empty($clientKYC->selfie_photo_url))
                                    <div class="col-md-12 mt-5 text-center">
                                        No selfie has been found.
                                    </div>

                                    @if (!in_array('Sumsub', $enabledModules))
                                        <div class="col-md-12 mt-2 text-center">
                                            @if ($client->is_kyc_requested)
                                                <button onclick="requestClientCompliance()"
                                                    class="btn btn-primary">Request selfie photo kyc again</button>
                                                <div class="mt-2">KYC already requested</div>
                                            @else
                                                <button onclick="requestClientCompliance()"
                                                    class="btn btn-primary">Request selfie photo kyc</button>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="col-md-12 mt-4 d-flex justify-content-center">
                                        <form method="post"
                                            action="{{ url('/client-management/clients/upload-selfie-photo') }}"
                                            enctype="multipart/form-data" id="uploadSelfieFormKyc"
                                            class="d-flex flex-column align-items-center">
                                            @csrf
                                            <input type="hidden" name="client_id" value="{{ $clientKYC->client_id }}">
                                            <label for="selfie_photo_url_kyc" class="btn btn-primary btn-sm mb-1">
                                                Upload Selfie </label>
                                            <span id="file-name-display-kyc" class="text-muted small mb-0 text-center">
                                                choose jpg/png, recommended size 50x50
                                            </span>
                                            <input type="file" name="selfie_photo_url" accept="image/*"
                                                id="selfie_photo_url_kyc" class="invisible item-file"
                                                style="position: absolute; width: 0; height: 0;" />
                                        </form>
                                    </div>
                                @else
                                    <div class="col-md-12 d-flex justify-content-center mt-2 align-items-center">

                                        <div>
                                            <a href="{{ $clientKYC->selfie_photo_url }}"
                                                class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                                    class="fal fa-file-alt"></i> Selfie Photo</a>
                                        </div>

                                        @if (!empty($clientKYC->selfie_video_url))
                                            <div class="ml-2">
                                                <a href="{{ $clientKYC->selfie_video_url }}"
                                                    class="btn btn-sm btn-outline-secondary" target="_blank"><i
                                                        class="fal fa-file-alt"></i> Selfie Video</a>
                                            </div>
                                        @else
                                            <div class="ml-2">
                                                <span class="text-muted">Selfie Video not uploaded</span>
                                            </div>
                                        @endif

                                    </div>
                                    <div class="col-md-12 d-flex justify-content-center mt-2">
                                        <img src="{{ $clientKYC->selfie_photo_url }}" alt="Client Selfie"
                                            style="max-width: -webkit-fill-available; max-height: 428px;" />
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (in_array('Sumsub', $enabledModules))
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="p-3 border rounded bg-light">
                                    <label class="font-weight-bold mb-2 text-primary">KYC Request:</label>
                                    @if (empty($client->parent_id))
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" id="client-req-selfie"
                                                value="1" checked>
                                            <label class="form-check-label" for="client-req-selfie">Request
                                                Selfie</label>
                                        </div>
                                    @endif
                                    <div class="form-check mb-1">
                                        <input class="form-check-input" type="checkbox" id="client-req-passport"
                                            value="1" checked>
                                        <label class="form-check-label" for="client-req-passport">Request
                                            Passport</label>
                                    </div>

                                    @php
                                        $btnText = $client->is_kyc_requested ? 'Resend KYC Request' : 'Request KYC';
                                    @endphp

                                    <button type="button" class="btn btn-primary btn-sm mt-2" id="client-req-btn"
                                        onclick="sendClientSumsubKYCRequest('{{ $clientKYC->sumsub_applicant_id ?? '' }}')">
                                        {{ $btnText }}
                                    </button>

                                    {{-- <div class="client-error-message mt-3">
                                        @if ($client->is_kyc_requested)
                                            <div class="text-info font-weight-bold"> Kyc Request sent </div>
                                        @endif
                                    </div> --}}
                                </div>
                            </div>
                        </div>
                    @endif

                </td>
            </tr>

            <tr>
                <th>Photo Comparision</th>
                <td>
                    <span class="badge {{ $statusBadge[$clientKYC->kyc_status] }}">{{ $clientKYC->kyc_status }}</span>
                    <small>(The above Client Passport and Selfie have been compared automatically.)</small>
                    @if ($clientKYC->kyc_status != 'Verified' && $passportUrl != '')
                        <button onclick="compareFaces(); return false;" class="btn btn-success ml-3">Compare
                            Faces</button>
                    @endif
                </td>
            </tr>
            <tr>
                <th>Liveness Status</th>
                <td>
                    <span class="badge {{ $statusBadge[$clientKYC->liveness_status] }}">
                        {{ $clientKYC->liveness_status }}
                    </span>
                </td>
            </tr>
            @if (in_array('Compliance', $enabledModules))
                @php $worldCheckEnabled = \App\Services\WorldCheckConfigService::isEnabled(); @endphp
                @if ($worldCheckEnabled || !empty($sanctionReport->url ?? null))
                    <tr>
                        <th>WorldCheck Report</th>
                        <td>
                            <div class="d-flex align-items-center">
                                @isset($sanctionReport)
                                    @isset($sanctionReport->url)
                                        <a href="{{ $sanctionReport->url }}" class="btn btn-sm btn-outline-secondary"
                                            target="_blank"><i class="fal fa-file-alt"></i>View Report</a>
                                        @if ($worldCheckEnabled)
                                            <button onclick="worldCheckReportRequest(); return false;"
                                                class="btn btn-primary ml-3">Request
                                                new
                                                Report</button>
                                        @endif
                                    @elseif ($worldCheckEnabled)
                                        <button id="getReportBtn" onclick="worldCheckReportGetStatusAndSave();  return false;"
                                            class="btn btn-sm btn-outline-secondary"
                                            @if (isset($sanctionReport) &&
                                                    isset($sanctionReport->updated_at) &&
                                                    \Carbon\Carbon::now()->diffInSeconds($sanctionReport->updated_at) < 60) disabled data-toggle="tooltip"
            data-placement="top"
            title="This button is disabled for 60 seconds after requesting a report." @endif>
                                            <i class="fal fa-file-alt"></i> Get Report
                                        </button>
                                        <button onclick="worldCheckReportRequest(); return false;"
                                            class="btn btn-primary ml-3">Request
                                            new
                                            Report</button>
                                    @endisset
                                @elseif ($worldCheckEnabled)
                                    <button onclick="worldCheckReportRequest(); return false;" class="btn btn-primary">Request
                                        new
                                        Report</button>
                                @endisset
                                @if ($worldCheckEnabled)
                                    <span class="ml-auto text-muted fs-base">
                                        Powered by <span class="lseg-color">LSEG</span> World-Check One
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif
            @endif
            @php
                $isSumsubGreen = false;
                if (
                    isset($enabledModules) &&
                    in_array('Sumsub', $enabledModules) &&
                    isset($clientKYC) &&
                    strtolower($clientKYC->sumsub_kyc_status) === 'completed' &&
                    $clientKYC->sumsub_kyc_answer === 'GREEN'
                ) {
                    $isSumsubGreen = true;
                }
            @endphp
            @if (!$isSumsubGreen)
                <tr>
                    <th>Compliance Status</th>
                    <td>
                        <span class="badge {{ $statusBadge[$clientKYC->compliance_status] }}">
                            {{ $clientKYC->compliance_status }}
                        </span>

                        @if ($clientKYC->compliance_status != 'Verified')
                            <button onclick="verifyClientCompliance(); return false;"
                                class="btn btn-success ml-3">Verify</button>
                        @endif

                        @if (count($aml_results) > 0)
                            @foreach ($aml_results as $aml)
                                <span class="badge badge-{{ $aml['badge'] }}"> {{ $aml['rule_name'] }} -
                                    {{ $aml['status'] }}</span>
                                <i class="fal fa-question-circle {{ $aml['badge'] }}" data-toggle="tooltip"
                                    data-placement="auto" title=""
                                    data-original-title="{{ $aml['rule_description'] }}"></i>
                            @endforeach
                        @endif
                    </td>
                </tr>
            @endif
        </thead>
    </table>
    <div class="row">
        <div class="col-md-12">
            <div class="form-group">
                <hr>
                <button type="submit" class="btn btn-primary">Save</button>
                @include('client-management.partials.approve-reject-button')
            </div>
        </div>
    </div>


    <form method="POST" name="verify-client" id="verify_client"
        action="{{ url('/client-management/clients/verify-client-kyc') }}">
        @csrf
        <input type="hidden" name="client_kyc_id" id="client_kyc_id" value="{{ $clientKYC->id }}">
    </form>

    <form method="POST" name="worldCheck-report-request" id="worldCheck_report_request"
        action="{{ url('/client-management/clients/worldCheck-report-request') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $clientKYC->client_id }}">
    </form>

    <form method="POST" name="worldCheck-report-get-status" id="worldCheck_report_get_status_and_save"
        action="{{ url('/client-management/clients/worldCheck-report-get-status-and-save') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $clientKYC->client_id }}">
    </form>
    <form method="POST" name="compare-faces" id="compare_faces"
        action="{{ url('/client-management/clients/compare-faces') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
        <input type="hidden" name="passport_url_id" id="passport_url_id" value="{{ $passportUrl }}">
        <input type="hidden" name="selfie_url_id" id="selfie_url_id" value="{{ $clientKYC->selfie_photo_url }}">

    </form>
    <form method="POST" name="request-client-kyc" id="request_client_kyc"
        action="{{ url('/client-management/clients/request-client-kyc') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
    </form>
    <script>
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

        function verifyClientCompliance() {

            Swal.fire({
                title: "Are you sure?",
                text: "The Client KYC will be verified.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Verify it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('verify_client').submit()
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

        document.getElementById('selfie_photo_url_kyc').addEventListener('change', function() {
            const file = this.files[0];
            if (!file) return;
            document.getElementById('uploadSelfieFormKyc').submit();
        });

        function compareFaces() {

            Swal.fire({
                title: "Are you sure?",
                text: "Client passport and the selfie will be compared automatically",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Compare it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('compare_faces').submit()
                }
            });
        }

        function sendClientSumsubKYCRequest(applicantId) {
            $('#loading-spinner').show();
            var client_id = "{{ $clientKYC->client_id }}";

            var reqSelfie = $('#client-req-selfie').is(':checked');
            var reqPassport = $('#client-req-passport').is(':checked');

            $('.client-error-message').html('').hide().removeClass('alert-danger alert-info');

            if (!reqSelfie && !reqPassport) {
                $('.client-error-message').html('Please select Either selfie or passport to send request.')
                    .addClass('alert alert-danger').show();
                $('#loading-spinner').hide();
                return false;
            }

            // Determine Sumsub Level
            var level_name = '';
            if (reqSelfie && reqPassport) {
                level_name = 'passport-n-liveness-link';
            } else if (reqSelfie) {
                level_name = 'liveness-link';
            } else if (reqPassport) {
                level_name = 'passport-link';
            }

            if (empty(client_id) || empty(level_name)) {
                $('.client-error-message').html('Invalid data. Please ensure the level is selected.')
                    .addClass('alert alert-danger').show();
                $('#loading-spinner').hide();
                return false;
            }

            $.post("{{ url('/client-management/clients/request-sumsub-kyc') }}", {
                    "_token": "{{ csrf_token() }}",
                    "client_id": client_id,
                    "applicant_id": applicantId,
                    "level_name": level_name
                },
                function(data, status) {
                    if (typeof data === 'string') {
                        data = $.parseJSON(data);
                    }

                    $('.client-error-message').show();
                    if (data.status === 'error') {
                        $('.client-error-message').text(data.message).addClass('alert alert-danger');
                        $('#loading-spinner').hide();
                        return false;
                    }

                    if (data.status === 'success') {
                        $('#loading-spinner').hide();
                        var successHtml = data.message;
                        $('.client-error-message').html(successHtml).addClass('alert alert-info');

                        // Update the button text to "Resend"
                        $('#client-req-btn').html('Resend KYC Request');
                        return false;
                    }
                }).fail(function(xhr) {
                $('#loading-spinner').hide();
                $('.client-error-message').html('Server error occurred while communicating with Sumsub.')
                    .addClass('alert alert-danger').show();
            });
        }

        function empty(str) {
            return (!str || str.length === 0);
        }
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = document.querySelectorAll('[data-toggle="tooltip"]');
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

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
@endif
