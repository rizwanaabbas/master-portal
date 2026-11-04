<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous">
</script>

@php
    $companyUpdatedAt =
        isset($sanctionReport) && isset($sanctionReport->updated_at)
            ? \Carbon\Carbon::parse($sanctionReport->updated_at)->timestamp
            : null;

    $sortedDirectors = $client->directors->sortBy(function ($director) {
        if ($director->is_owner) {
            return 0;
        } elseif ($director->type == 'director') {
            return 1;
        } else {
            return 2;
        }
    });
@endphp

@foreach ($sortedDirectors as $directorIndex => $director)
    @php
        $director_id = $director->kyc->director_id ?? 'contact';
        if ($director_id == 'contact' && isset($client->company->contact->sanction_report)) {
            $individualUpdatedAt = isset($client->company->contact->sanction_report->updated_at)
                ? \Carbon\Carbon::parse($client->company->contact->sanction_report->updated_at)->timestamp
                : null;
        } else {
            $individualUpdatedAt = isset($director->sanction_report->updated_at)
                ? \Carbon\Carbon::parse($director->sanction_report->updated_at)->timestamp
                : null;
        }
    @endphp
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var updatedAt = {{ $individualUpdatedAt ?? 'null' }};
            if (updatedAt) {
                var now = Math.floor(Date.now() / 1000);
                var diff = now - updatedAt;
                if (diff < 60) {
                    var remaining = (60 - diff) * 1000;
                    setTimeout(function() {
                        var btn = document.getElementById('getReportBtn-{{ $directorIndex }}');
                        if (btn) btn.disabled = false;
                    }, remaining);
                }
            }
        });
    </script>
@endforeach

<div class="mt-5">
    <!-- <h2>Client Directors / UBO's</h2> -->
    <div id="directorsContainer-view">


        <div class="table-responsive table-bg-grey">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>KYC</th>
                        <th>&nbsp;</th>
                    </tr>
                </thead>

                @if (count($client->directors) > 0)
                    <tbody>
                        @php
                            $sortedDirectors = $client->directors->sortBy(function ($director) {
                                if ($director->is_owner) {
                                    return 0;
                                } elseif ($director->type == 'director') {
                                    return 1;
                                } else {
                                    return 2;
                                }
                            });
                        @endphp
                        @foreach ($sortedDirectors as $directorIndex => $director)
                            @php
                                $kyc = null;
                                if (!empty($director->kyc)) {
                                    $kyc = $director->kyc;
                                } elseif ($director->is_owner) {
                                    $kyc = $clientKYC;
                                }
                                $directorAppId = $kyc->sumsub_applicant_id ?? null;
                                $directorLogs = [];
                                if ($directorAppId && isset($director_transaction_log)) {
                                    $directorLogs = collect($director_transaction_log)
                                        ->where('transaction_id', $directorAppId)
                                        ->all();
                                }
                            @endphp
                            <tr data-index="{{ $directorIndex }}">
                                <td>
                                    <input type="hidden" class="form-control" name="directors[{{ $directorIndex }}][id]"
                                        id="id-{{ $directorIndex }}" value="{{ $director->id }}">
                                    {{ $director->name }}
                                    @if ($director->is_owner)
                                        - <span class="badge badge-success">
                                            Owner
                                        </span>
                                    @elseif ($director->type == 'director')
                                        - <span class="badge badge-dark">
                                            Director
                                        </span>
                                    @elseif ($director->type == 'signatory')
                                        - <span class="badge badge-info">
                                            Signatory
                                        </span>
                                    @endif

                                    @php
                                        $person = $director;
                                        if ($director->is_owner) {
                                            $person = $client->company->contact;
                                        }
                                        $aml_results = App\Modules\Aml\Controllers\AmlRuleController::applyRules(
                                            $person,
                                            'Person',
                                        );
                                    @endphp
                                    @if (count($aml_results) > 0)
                                        <div class="mt-2">
                                            @foreach ($aml_results as $aml)
                                                <span class="badge badge-{{ $aml['badge'] }}"> {{ $aml['rule_name'] }}
                                                    -
                                                    {{ $aml['status'] }}</span>
                                                <i class="fal fa-question-circle {{ $aml['badge'] }}"
                                                    data-toggle="tooltip" data-placement="auto" title=""
                                                    data-original-title="{{ $aml['rule_description'] }}"></i>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    {{ $director->email }}
                                </td>
                                <td>
                                    <div class="row">
                                        <div class="col-md-6">
                                            @if (empty($client->parent_id))
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                            name="directors[{{ $directorIndex }}][request_selfie]"
                                                            id="selfie-{{ $directorIndex }}" checked>
                                                        Request Selfie
                                                    </label>
                                                </div>
                                            @endif

                                            <div class="form-check">
                                                <label class="form-check-label">
                                                    <input class="form-check-input" type="checkbox" value="1"
                                                        name="directors[{{ $directorIndex }}][request_passport]"
                                                        id="passport-{{ $directorIndex }}" checked>
                                                    Request Passport
                                                </label>
                                            </div>

                                            @if ($director->is_owner)
                                                <div class="form-check">
                                                    <label class="form-check-label">
                                                        <input class="form-check-input" type="checkbox" value="1"
                                                            name="directors[{{ $directorIndex }}][request_signature]"
                                                            id="signature-{{ $directorIndex }}">
                                                        Request Signatory
                                                    </label>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-check">
                                                @php
                                                    if ($director->is_owner) {
                                                        $director->kyc_status = $client->is_kyc_requested;
                                                    }
                                                    $btnText =
                                                        $director->kyc_status == 0
                                                            ? 'Request KYC'
                                                            : 'Resend KYC Request';
                                                @endphp

                                                @if (in_array('Sumsub', $enabledModules))
                                                    <!-- SUMSUB BUTTON -->
                                                    <button type="button" class="btn btn-primary mt-2"
                                                        id="req-btn-{{ $directorIndex }}"
                                                        onclick="sendSumsubKYCRequest({{ $directorIndex }}, '{{ $kyc->sumsub_applicant_id ?? '' }}')">
                                                        {{ $btnText }}
                                                    </button>
                                                    @if (!empty($kyc) && !empty($kyc->sumsub_applicant_id))
                                                        <button type="button" class="btn btn-secondary mt-2"
                                                            onclick="viewDirectorSumsubLog({{ $directorIndex }}); return false;">
                                                            <i class="fal fa-history"></i> View Logs
                                                        </button>
                                                    @endif
                                                @else
                                                    <!-- DEFAULT BUTTON -->
                                                    <button type="button" class="btn btn-primary mt-2"
                                                        id="req-btn-{{ $directorIndex }}"
                                                        onclick="sendDirectorKYCRequest({{ $directorIndex }})">
                                                        {{ $btnText }}
                                                    </button>
                                                @endif

                                                <div class="director-error-message-{{ $directorIndex }} mt-4"
                                                    style="display: none;">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>


                                <td>

                                    <button class="btn btn-dark float-right panel-heading accordion-toggle collapsed"
                                        onclick="accordion(this.id)" type="button" data-bs-toggle="collapse"
                                        id="button-collapse-{{ $directorIndex }}-{{ $director->is_owner ? 'owner-' : 'director' }}"
                                        data-bs-target="#collapse{{ $directorIndex }}" aria-expanded="true"
                                        aria-controls="collapse{{ $directorIndex }}">
                                        Show More
                                    </button>


                                </td>
                            </tr>
                            <tr>
                                <td colspan="4">

                                    <div id="collapse{{ $directorIndex }}" class="accordion-collapse collapse kyc-card"
                                        aria-labelledby="heading{{ $directorIndex }}"
                                        data-bs-parent="#directorsContainer-view">
                                        <div class="accordion-body">
                                            @if (in_array('Sumsub', $enabledModules))
                                                <div
                                                    class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-3 bg-light border rounded shadow-sm">
                                                    <div class="d-flex flex-wrap align-items-center">
                                                        <h6 class="m-0 font-weight-bold text-primary mr-3">
                                                            <i class="fal fa-shield-check mr-2"></i>Sumsub Verification
                                                        </h6>
                                                        <!-- LIVE VERIFICATION STATUS BADGES -->
                                                        @if (isset($directorLogs) && count($directorLogs) > 0)
                                                            @php
                                                                $latestDirLog = json_decode(
                                                                    array_values($directorLogs)[0]->details,
                                                                    true,
                                                                );
                                                                $dirReviewStatus = ucfirst(
                                                                    str_replace(
                                                                        '_',
                                                                        ' ',
                                                                        $latestDirLog['reviewStatus'] ?? 'Pending',
                                                                    ),
                                                                );
                                                                $dirReviewAnswer = ucfirst(
                                                                    str_replace(
                                                                        '_',
                                                                        ' ',
                                                                        $latestDirLog['reviewResult']['reviewAnswer'] ??
                                                                            'Pending',
                                                                    ),
                                                                );
                                                            @endphp
                                                            <div class="d-inline-flex align-items-center my-1 my-md-0">
                                                                <span
                                                                    class="badge {{ $statusBadge[$dirReviewStatus] ?? 'badge-secondary' }} px-2 py-1 mr-2 text-uppercase font-weight-bold"
                                                                    style="font-size: 85%;">
                                                                    Status: {{ $dirReviewStatus }}
                                                                </span>
                                                                <span
                                                                    class="badge {{ $statusBadge[$dirReviewAnswer] ?? 'badge-secondary' }} px-2 py-1 text-uppercase font-weight-bold"
                                                                    style="font-size: 85%;">
                                                                    Result: {{ $dirReviewAnswer }}
                                                                </span>
                                                            </div>
                                                        @else
                                                            <span
                                                                class="badge badge-secondary px-2 py-1 text-uppercase font-weight-bold"
                                                                style="font-size: 85%;">
                                                                Uninitiated
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                            {{-- @php
                                                $kyc = '';
                                                if (!empty($director->kyc)) {
                                                    $kyc = $director->kyc;
                                                } elseif ($director->is_owner) {
                                                    $kyc = $clientKYC;
                                                }

                                            @endphp --}}
                                            @if (!empty($kyc))
                                                <div class="row mb-3">
                                                    <div class="col-md-4">
                                                        <strong>Passport Copy:</strong>

                                                        @if (!empty($kyc->passport_copy_url))
                                                            &nbsp;
                                                            <a href=" {{ $kyc->passport_copy_url }}"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                target="_blank"><i class="fal fa-file-alt"></i>
                                                                Passport</a>
                                                            <div class="mt-2">


                                                                @if (strtolower(pathinfo($kyc->passport_copy_url, PATHINFO_EXTENSION)) === 'pdf')
                                                                    <iframe src="{{ $kyc->passport_copy_url }}"
                                                                        style="width: 100%; height: 200px;"
                                                                        frameborder="0"></iframe>
                                                                @else
                                                                    <img src="{{ $kyc->passport_copy_url }}"
                                                                        alt="Passport Copy" class="img-thumbnail"
                                                                        style="max-width: 200px;">
                                                                @endif
                                                            </div>
                                                        @else
                                                            Passport not uploaded
                                                        @endif
                                                    </div>
                                                    <div class="col-md-4">
                                                        <strong>Selfie Photo:</strong>
                                                        @if (!empty($kyc->selfie_photo_url))
                                                            &nbsp;
                                                            <a href=" {{ $kyc->selfie_photo_url }}"
                                                                class="btn btn-sm btn-outline-secondary"
                                                                target="_blank"><i class="fal fa-file-alt"></i>
                                                                View Photo</a>

                                                            <div class="mt-2">
                                                                <img src="{{ $kyc->selfie_photo_url }}"
                                                                    alt="Selfie Photo" class="img-thumbnail"
                                                                    style="max-width: 200px;">
                                                            </div>
                                                        @else
                                                            Selfie not uploaded
                                                        @endif

                                                        <div class="mt-3">
                                                            <strong>Selfie Video:</strong>
                                                            @if (!empty($kyc->selfie_video_url))
                                                                <div class ="ml-2">
                                                                    &nbsp;
                                                                    <a href=" {{ $kyc->selfie_video_url }}"
                                                                        class="btn btn-sm btn-outline-secondary"
                                                                        target="_blank"><i
                                                                            class="fal fa-file-alt"></i>
                                                                        View Video</a>
                                                                </div>
                                                            @else
                                                                Selfie Video not uploaded
                                                            @endif
                                                        </div>

                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="row mb-3">

                                                            <div class="col-md-12">
                                                                <strong> Liveness Status:</strong>
                                                                <span
                                                                    class="badge {{ $statusBadge[$kyc->liveness_status] }}">
                                                                    {{ $kyc->liveness_status }}
                                                                </span>
                                                            </div>
                                                            <div class="col-md-12 mt-4">
                                                                <div class="row">
                                                                    <div class="col-md-6 mt-4">
                                                                        <strong>Photo Comparision:</strong>
                                                                        <span
                                                                            class="badge {{ $statusBadge[$kyc->kyc_status] }}">
                                                                            {{ $kyc->kyc_status }}
                                                                        </span>
                                                                        <span
                                                                            class="badge {{ $statusBadge[$kyc->kyc_status] }}"></span>
                                                                    </div>
                                                                    <div class="col-md-6 mt-4 ">
                                                                        @if ($kyc->kyc_status != 'Verified' && $kyc->passport_copy_url != '')
                                                                            <button
                                                                                onclick="compareDirectorFace('{{ $director->client_id }}', '{{ $kyc->director_id }}', '{{ $kyc->passport_copy_url }}', '{{ $kyc->selfie_photo_url }}'); return false;"
                                                                                class="btn btn-success ml-3 float-right">Compare
                                                                                Faces</button>
                                                                        @endif
                                                                        <br>
                                                                    </div>
                                                                </div>
                                                                <!-- <small>(The above Client Passport and Selfie have been compared automatically.)</small> -->
                                                            </div>


                                                            <div class="col-md-12 card p-2 mt-3">
                                                                @if ($director->is_owner)
                                                                    <div class="row mt-4">
                                                                        @php $worldCheckEnabled = \App\Services\WorldCheckConfigService::isEnabled(); @endphp
                                                                        @if (in_array('Compliance', $enabledModules) && ($worldCheckEnabled || !empty($sanctionReport->url ?? null)))
                                                                            <div class="col-md-7">
                                                                                <div class="row">
                                                                                    <div class="col-md-6">
                                                                                        <strong>Company Report:</strong>
                                                                                    </div>
                                                                                    @isset($sanctionReport)
                                                                                        @isset($sanctionReport->url)
                                                                                            <div class="col-md-6">
                                                                                                <a href="{{ $sanctionReport->url }}"
                                                                                                    class="btn btn-sm btn-outline-secondary"
                                                                                                    target="_blank">
                                                                                                    <i
                                                                                                        class="fal fa-file-alt"></i>
                                                                                                    View Report
                                                                                                </a>
                                                                                            </div>
                                                                                        @elseif ($worldCheckEnabled)
                                                                                            <div class="col-md-6">
                                                                                                <button id="getReportBtn"
                                                                                                    onclick="worldCheckReportGetStatusAndSave();  return false;"
                                                                                                    class="btn btn-sm btn-outline-secondary"
                                                                                                    @if (isset($sanctionReport) &&
                                                                                                            isset($sanctionReport->updated_at) &&
                                                                                                            \Carbon\Carbon::now()->diffInSeconds($sanctionReport->updated_at) < 60) disabled         data-toggle="tooltip"
        data-placement="top"
        title="This button is disabled for 60 seconds after requesting a report." @endif>
                                                                                                    <i
                                                                                                        class="fal fa-file-alt"></i>
                                                                                                    Get Report
                                                                                                </button>
                                                                                            </div>
                                                                                        @endisset
                                                                                    @endisset
                                                                                </div>
                                                                            </div>
                                                                            @if ($worldCheckEnabled)
                                                                                <div class="col-md-5 text-md-right">
                                                                                    @isset($sanctionReport)
                                                                                        @isset($sanctionReport->url)
                                                                                            <button
                                                                                                onclick="worldCheckReportRequest(); return false;"
                                                                                                class="btn btn-primary">
                                                                                                Request new Report
                                                                                            </button>
                                                                                        @else
                                                                                            <button
                                                                                                onclick="worldCheckReportRequest(); return false;"
                                                                                                class="btn btn-primary">
                                                                                                Request new Report
                                                                                            </button>
                                                                                        @endisset
                                                                                    @else
                                                                                        <button
                                                                                            onclick="worldCheckReportRequest(); return false;"
                                                                                            class="btn btn-primary">
                                                                                            Request new Report
                                                                                        </button>
                                                                                    @endisset
                                                                                </div>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                @endif

                                                                <div class="row mt-4">
                                                                    @php $worldCheckEnabled = $worldCheckEnabled ?? \App\Services\WorldCheckConfigService::isEnabled(); @endphp
                                                                    @if (in_array('Compliance', $enabledModules))
                                                                        @php
                                                                            // Precompute URL so we can show View Report even when WorldCheck is disabled.
                                                                            $individual_report_url_preview = '';
                                                                            if (!empty($director->sanction_report->url ?? null)) {
                                                                                $individual_report_url_preview = $director->sanction_report->url;
                                                                            } elseif (
                                                                                $director->is_owner &&
                                                                                !empty($client->company->contact->sanction_report->url ?? null)
                                                                            ) {
                                                                                $individual_report_url_preview = $client->company->contact->sanction_report->url;
                                                                            }
                                                                        @endphp
                                                                    @endif
                                                                    @if (in_array('Compliance', $enabledModules) && ($worldCheckEnabled || !empty($individual_report_url_preview)))
                                                                        <div class="col-md-7">
                                                                            <div class="row">
                                                                                <div class="col-md-6">
                                                                                    <strong>Individual Report:</strong>
                                                                                </div>
                                                                                @php
                                                                                    $individual_report_url = '';
                                                                                    if (
                                                                                        !empty(
                                                                                            $director->sanction_report
                                                                                        )
                                                                                    ) {
                                                                                        if (
                                                                                            isset(
                                                                                                $director
                                                                                                    ->sanction_report
                                                                                                    ->url,
                                                                                            )
                                                                                        ) {
                                                                                            $individual_report_url =
                                                                                                $director
                                                                                                    ->sanction_report
                                                                                                    ->url;
                                                                                        }
                                                                                    } elseif (
                                                                                        !empty(
                                                                                            $client->company->contact
                                                                                                ->sanction_report
                                                                                        ) &&
                                                                                        $director->is_owner
                                                                                    ) {
                                                                                        if (
                                                                                            isset(
                                                                                                $client->company
                                                                                                    ->contact
                                                                                                    ->sanction_report
                                                                                                    ->url,
                                                                                            )
                                                                                        ) {
                                                                                            $individual_report_url =
                                                                                                $client->company
                                                                                                    ->contact
                                                                                                    ->sanction_report
                                                                                                    ->url;
                                                                                        }
                                                                                    }
                                                                                @endphp
                                                                                @if (!empty($individual_report_url))
                                                                                    <div class="col-md-6">
                                                                                        <a href="{{ $individual_report_url }}"
                                                                                            class="btn btn-sm btn-outline-secondary"
                                                                                            target="_blank">
                                                                                            <i
                                                                                                class="fal fa-file-alt"></i>
                                                                                            View Report
                                                                                        </a>
                                                                                    </div>
                                                                                @else
                                                                                    @isset($client->company->contact->sanction_report)
                                                                                        @php
                                                                                            $director_id =
                                                                                                $kyc->director_id ?:
                                                                                                'contact';
                                                                                            $disableBtn = false;
                                                                                            if (
                                                                                                $director_id ==
                                                                                                    'contact' &&
                                                                                                isset(
                                                                                                    $client->company
                                                                                                        ->contact
                                                                                                        ->sanction_report,
                                                                                                ) &&
                                                                                                isset(
                                                                                                    $client->company
                                                                                                        ->contact
                                                                                                        ->sanction_report
                                                                                                        ->updated_at,
                                                                                                )
                                                                                            ) {
                                                                                                $disableBtn =
                                                                                                    \Carbon\Carbon::now()->diffInSeconds(
                                                                                                        $client->company
                                                                                                            ->contact
                                                                                                            ->sanction_report
                                                                                                            ->updated_at,
                                                                                                    ) < 60;
                                                                                            } elseif (
                                                                                                isset(
                                                                                                    $director->sanction_report,
                                                                                                ) &&
                                                                                                isset(
                                                                                                    $director
                                                                                                        ->sanction_report
                                                                                                        ->updated_at,
                                                                                                )
                                                                                            ) {
                                                                                                $disableBtn =
                                                                                                    \Carbon\Carbon::now()->diffInSeconds(
                                                                                                        $director
                                                                                                            ->sanction_report
                                                                                                            ->updated_at,
                                                                                                    ) < 60;
                                                                                            }
                                                                                        @endphp
                                                                                        <div class="col-md-6">
                                                                                            <button
                                                                                                id="getReportBtn-{{ $directorIndex }}"
                                                                                                onclick="directorWorldCheckReportGetStatusAndSave('{{ $director->client_id }}', '{{ $director_id }}');  return false;"
                                                                                                class="btn btn-sm btn-outline-secondary"
                                                                                                @if ($disableBtn) disabled
        data-toggle="tooltip"
        data-placement="top"
        title="This button is disabled for 60 seconds after requesting a report." @endif>
                                                                                                <i
                                                                                                    class="fal fa-file-alt"></i>
                                                                                                Get Report
                                                                                            </button>
                                                                                        </div>
                                                                                    @endisset
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        @if ($worldCheckEnabled)
                                                                            <div class="col-md-5 text-md-right">
                                                                                @php
                                                                                    $director_id =
                                                                                        $kyc->director_id ?: 'contact';
                                                                                @endphp
                                                                                <button
                                                                                    onclick="directorWorldCheckReportRequest('{{ $director->client_id }}', '{{ $director_id }}'); return false;"
                                                                                    class="btn btn-primary">
                                                                                    Request new Report
                                                                                </button>
                                                                            </div>
                                                                        @endif
                                                                </div>
                                            @endif
                                        </div>

                                        @if (!empty($worldCheckEnabled))
                                            <div class="card-footer mt-2 text-center">
                                                Powered by <span class="lseg-color">LSEG</span>
                                                World-Check One
                                            </div>
                                        @endif
                                    </div>

        </div>
    </div>
@else
    <p class="alert alert-warning">No Kyc data available. Please
        request Kyc by clicking request kyc button from above.
    </p>
    <form method="post" action="{{ url('/client-management/clients/upload-selfie-photo') }}"
        enctype="multipart/form-data" id="uploadSelfieForm_{{ $director->id }}" class="d-flex align-items-center">
        @csrf
        <input type="hidden" name="client_id" value="{{ $client->id }}">
        <input type="hidden" name="director_id" value="{{ $director->id }}">
        <input type="hidden" name="is_owner" value="{{ $director->is_owner ? 1 : 0 }}">

        <label for="selfie_photo_url_{{ $director->id }}" class="btn btn-primary btn-sm mr-2 mb-0">Upload
            Selfie</label>
        <span class="text-muted small mb-0">choose jpg/png, recommended size 50x50</span>

        <input type="file" name="selfie_photo_url" accept="image/*" id="selfie_photo_url_{{ $director->id }}"
            class="invisible item-file" />
    </form>
    @endif
</div>
</div>
</td>
</tr>
@if ($directorAppId)
    <div class="modal fade" id="director-sumsub-log-modal-{{ $directorIndex }}" tabindex="-1" role="dialog"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">View Sumsub Log - {{ $director->name }}
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="closeDirectorSumsubLog({{ $directorIndex }});">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    @if (count($directorLogs) > 0)
                        <h4 class="mt-2">Sumsub Webhook Log</h4>
                        @foreach ($directorLogs as $log)
                            <div class="alert alert-warning mt-4">
                                <label style="font-weight:bold;"> Date:</label>
                                {{ $log->date_created }}<br>
                                <label style="font-weight:bold;">Source:</label>
                                {{ $log->source }}<br>

                                @foreach (json_decode($log->details, true) as $key => $val)
                                    @if (is_array($val) || is_object($val))
                                        @php
                                            $val = json_encode($val, JSON_PRETTY_PRINT);
                                        @endphp
                                    @endif
                                    <label style="font-weight:bold;">
                                        {{ ucfirst(str_replace('_', ' ', $key)) }}: </label>
                                    {{ $val }}<br>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <p class="alert alert-info mt-2">No webhook logs found for this
                            director.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endif
@endforeach
</tbody>
@else
<p class="alert alert-warning">No directors available. Please add by going to <a data-toggle="tab"
        href="#contact-person" role="tab" aria-selected="true">Contact Person</a> section</p>
@endif

</table>
</div>
</div>


<div class="row">
    <div class="col-md-6">
        <div class="float-left">
            <button type="button" onclick="saveClient()" class="btn btn-primary">Save</button>
            @include('client-management.partials.approve-reject-button')
        </div>
    </div>

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

    <div class="col-md-6">
        <div class="float-right">
            @if (!empty($clientKYC))
                @if (!$isSumsubGreen)
                    <span class="badge {{ $statusBadge[$clientKYC->compliance_status] }}">
                        {{ $clientKYC->compliance_status }}
                    </span>

                    @if ($clientKYC->compliance_status != 'Verified')
                        <button onclick="verifyClientCompliance(); return false;"
                            class="btn btn-success ml-3">Verify</button>
                    @endif
                @endif
            @endif
        </div>
    </div>
</div>
</div>

<form method="POST" name="compare-faces" id="d_compare_faces"
    action="{{ url('/client-management/clients/compare-faces') }}">
    @csrf
    <input type="hidden" name="client_id" id="d_client_id">
    <input type="hidden" name="director_id" id="d_director_id">
    <input type="hidden" name="passport_url_id" id="d_passport_url_id">
    <input type="hidden" name="selfie_url_id" id="d_selfie_url_id">
</form>
<form method="POST" name="worldCheck-report-get-status" id="dwc_worldCheck_report"
    action="{{ url('/client-management/clients/worldCheck-report-request') }}">
    @csrf
    <input type="hidden" name="client_id" id="dwc_client_id">
    <input type="hidden" name="director_id" id="dwc_director_id">
</form>
<form method="POST" name="worldCheck-report-get-status-and-save" id="dwc_worldCheck_report_get_status_and_save"
    action="{{ url('/client-management/clients/worldCheck-report-get-status-and-save') }}">
    @csrf
    <input type="hidden" name="client_id" id="dwcs_client_id">
    <input type="hidden" name="director_id" id="dwcs_director_id">
</form>

@if ($clientKYC)
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
@endif
<script>
    let accordionCalledOnLoad = false;

    function accordion(id) {
        if (!accordionCalledOnLoad || id !== '') {
            if (id !== '') {
                if ($('#' + id).hasClass('collapsed')) {
                    id = 'none-selected';
                }
            }
            $.get("{{ url('/client-management/clients/company/accordion/') }}/" + id,
                function(data, status) {
                    if (data && typeof data === 'string' && data.trim() !== '' && data !== 'none-selected') {
                        let targetId = data.trim();

                        if ($('#' + targetId).length > 0 && $('#' + targetId).hasClass('collapsed')) {
                            $('#' + targetId).removeClass('collapsed');
                            var content = targetId.replace('button-', '');
                            $('#' + content).addClass('show');
                        }
                    }
                }
            );
            accordionCalledOnLoad = true;
        }
    }

    window.addEventListener("load", (event) => {
        accordion('');
    });


    function saveClient() {
        $('#update-company-form').submit();
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

    function directorWorldCheckReportRequest(client_id, director_id) {
        $('#dwc_client_id').val(client_id);
        $('#dwc_director_id').val(director_id);
        Swal.fire({
            title: "Are you sure?",
            text: "The Client Report will be requested.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, request it!"
        }).then(function(result) {
            if (result.value) {
                $('#dwc_worldCheck_report').submit();
            }
        });
    }


    function directorWorldCheckReportGetStatusAndSave(client_id, director_id) {
        $('#dwcs_client_id').val(client_id);
        $('#dwcs_director_id').val(director_id);
        Swal.fire({
            title: "Are you sure?",
            text: "The Client Report will be checked.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, request it!"
        }).then(function(result) {
            if (result.value) {
                $('#dwc_worldCheck_report_get_status_and_save').submit();
            }
        });
    }

    function compareDirectorFace(client_id, director_id, passport_url, selfie_url) {
        // alert(passport_url);
        //console.log(client_id + ' = ' + director_id+ ' = ' + passport_url+ ' = ' + selfie_url);
        $('#d_client_id').val(client_id);
        $('#d_director_id').val(director_id);
        $('#d_passport_url_id').val(passport_url);
        $('#d_selfie_url_id').val(selfie_url);
        Swal.fire({
            title: "Are you sure?",
            text: "The Client KYC will be verified.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, Verify it!"
        }).then(function(result) {
            if (result.value) {
                $('#d_compare_faces').submit();
            }
        });

    }

    document.querySelectorAll('input[name="selfie_photo_url"]').forEach(input => {
        input.addEventListener('change', function() {
            if (!this.files[0]) return;
            this.closest('form').submit();
        });
    });

    function sendDirectorKYCRequest(index) {
        $('#loading-spinner').show();
        var client_id = "{{ $client->id }}";
        var director_id = $('#id-' + index).val();
        var request_passport = $('#passport-' + index).is(':checked') ? $('#passport-' + index).val() : '0';
        var request_selfie = $('#selfie-' + index).is(':checked') ? $('#selfie-' + index).val() : '0';
        var request_signature = $('#signature-' + index).is(':checked') ? $('#signature-' + index).val() : '0';
        $('.director-error-message-' + index).html('').hide().removeClass('alert-danger alert-info');
        if ((empty(client_id) || empty(director_id)) || (empty(request_passport) && empty(request_selfie))) {
            $('.director-error-message-' + index).html('Please select Either selfie or passport to send request.')
                .addClass('alert alert-danger').show();
            $('#loading-spinner').hide();
            return false;
        }

        $.post("{{ url('/client-management/clients/company/director/request-kyc') }}", {
                "client_id": client_id,
                "director_id": director_id,
                "request_passport": request_passport,
                "request_selfie": request_selfie,
                "request_signature": request_signature
            },
            function(data, status) {
                var data = $.parseJSON(data);

                if (data) {
                    console.log(data.status);

                    $('.director-error-message-' + index).show();
                    if (data.status == 'error') {
                        $('.director-error-message-' + index).text(data.message).addClass('alert alert-danger');
                        $('#loading-spinner').hide();
                        return false;
                    }
                    if (data.status == 'success') {
                        console.log(index);
                        $('#loading-spinner').hide();
                        $('.director-error-message-' + index).text(data.message).addClass('alert alert-info');
                        // director-error-message-0
                        $('#req-btn-' + index).html('Resend Kyc Request');
                        return false;
                    }
                }
            });
    }

    function sendSumsubKYCRequest(index, applicantId) {
        $('#loading-spinner').show();
        var client_id = "{{ $client->id }}";
        var director_id = $('#id-' + index).length ? $('#id-' + index).val() : '';

        var reqSelfie = $('#selfie-' + index).is(':checked');
        var reqPassport = $('#passport-' + index).is(':checked');

        $('.director-error-message-' + index).html('').hide().removeClass('alert-danger alert-info');

        if (!reqSelfie && !reqPassport) {
            $('.director-error-message-' + index).html('Please select Either selfie or passport to send request.')
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

        if (empty(client_id) || empty(level_name) || empty(applicantId)) {
            $('.director-error-message-' + index).html('Invalid data. Please ensure the level is selected.')
                .addClass('alert alert-danger').show();
            $('#loading-spinner').hide();
            return false;
        }

        $.post("{{ url('/client-management/clients/request-sumsub-kyc') }}", {
                "_token": "{{ csrf_token() }}",
                "client_id": client_id,
                "director_id": director_id,
                "applicant_id": applicantId,
                "level_name": level_name
            },
            function(data, status) {
                if (typeof data === 'string') {
                    data = $.parseJSON(data);
                }

                $('.director-error-message-' + index).show();
                if (data.status === 'error') {
                    $('.director-error-message-' + index).text(data.message).addClass('alert alert-danger');
                    $('#loading-spinner').hide();
                    return false;
                }

                if (data.status === 'success') {
                    $('#loading-spinner').hide();
                    var successHtml = data.message;
                    $('.director-error-message-' + index).html(successHtml).addClass('alert alert-info');

                    // Update the button text to "Resend"
                    $('#req-btn-' + index).html('Resend KYC Request');
                    return false;
                }
            }).fail(function(xhr) {
            $('#loading-spinner').hide();
            $('.director-error-message-' + index).html('Server error occurred while communicating with Sumsub.')
                .addClass('alert alert-danger').show();
        });
    }


    function empty(str) {
        return (!str || str.length === 0);
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

    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = document.querySelectorAll('[data-toggle="tooltip"]');
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var tooltipTriggerList = document.querySelectorAll('[data-toggle="tooltip"]');
        tooltipTriggerList.forEach(function(tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });

        var updatedAt = {{ $companyUpdatedAt ?? 'null' }};
        if (updatedAt) {
            var now = Math.floor(Date.now() / 1000);
            var diff = now - updatedAt;
            if (diff < 60) {
                var remaining = (60 - diff) * 1000;
                setTimeout(function() {
                    var btn = document.getElementById('getReportBtn');
                    if (btn) btn.disabled = false;
                }, remaining);
            }
        }
    });

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

    function viewDirectorSumsubLog(index) {
        $("#director-sumsub-log-modal-" + index).modal('show');
    }

    function closeDirectorSumsubLog(index) {
        $("#director-sumsub-log-modal-" + index).modal('hide');
    }
</script>

<style>
    .panel-heading.accordion-toggle:after {
        /* symbol for "opening" panels */
        font-family: 'Font Awesome 5 Pro';
        content: "\f105";
        /* right arrow, FontAwesome Unicode */
        float: right;
        /* adjust as needed */
        color: grey;
        /* adjust as needed */
        margin-left: 5px;
        font-weight: bold;
        font-size: 16px;
    }

    .panel-heading.accordion-toggle.collapsed:after {
        /* symbol for "collapsed" panels */
        content: "\f107";
        /* down arrow, FontAwesome Unicode */
    }

    .kyc-card {
        transition: transform 1s ease 0s;
        border-radius: 4px;
        box-shadow:
            rgba(0, 0, 0, 0.024) 0px 0px 0px 1px,
            rgba(0, 0, 0, 0.05) 0px 1px 0px 0px,
            rgba(0, 0, 0, 0.03) 0px 0px 8px 0px,
            rgba(0, 0, 0, 0.1) 0px 20px 30px 0px;
        background: #fff;

        padding: 20px;
    }

    .collapsing {
        -webkit-transition-delay: 0.1s;
        transition-delay: 0.1s;
        transition: height 0.1s ease;
    }
</style>
