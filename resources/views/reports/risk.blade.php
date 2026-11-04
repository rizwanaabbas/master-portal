@extends('layouts.cp')
@section('title', 'Clients Risk Report - Reports Management')

@section('content')

    @php $hideSubAccountColumn = true @endphp

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Reports Management</span>
        </h1>

    </div>
    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form method="GET" action="{{ url('/reports-management/client/risk')}}" class="mb-3">
                    <label for="risk_level">Filter by Risk Level:</label>
                    <select name="risk_level" id="risk_level" class="form-control" onchange="this.form.submit()">
                        <option value="all">All</option>
                        <option value="low_risk" {{ request('risk_level') == 'low_risk' ? 'selected' : '' }}>Low Risk</option>
                        <option value="medium_risk" {{ request('risk_level') == 'medium_risk' ? 'selected' : '' }}>Medium Risk</option>
                        <option value="high_risk" {{ request('risk_level') == 'high_risk' ? 'selected' : '' }}>High Risk</option>
                        <option value="prohibited" {{ request('risk_level') == 'prohibited' ? 'selected' : '' }}>Prohibited</option>
                        <option value="none" {{ request('risk_level') == 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </form>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30"></th>
                            <th>Name</th>
                            <th>Type</th>

                            <th>Risk Level</th>
                            <th>Risk Score</th>

                        </tr>
                    </thead>
                    <tbody>
                        @if ($clients)
                            @foreach ($clients as $client)
                                <tr>
                                    <td align="center" scope="row">
                                        <a
                                            href="{{ url('client-management/clients/' . strtolower($client->type) . '/edit/' . $client->id) }}">
                                            <i class="fal fa-edit"></i>
                                        </a>
                                    </td>
                                    <td>{{ $client->name }}</td>
                                    <td>
                                        {{ $client->is_bank ? 'bank' : $client->type }}
                                    </td>

                                    <td>
                                        @php
                                        //dd($client->risk_level);
                                        $risk_score = 0;
                                        if(!empty($client->clientriskrating->risk_score )){
                                            $risk_score = $client->clientriskrating->risk_score;
                                        }

                                        $aml_risk_results = [];
                                        $status = '';
                                        if(!empty($risk_score)) {
                                            $score_data = array("total_risk_score"=>$risk_score);
                                                $aml_risk_results = App\Modules\Aml\Controllers\AmlRuleController::applyRules(
                                                    $score_data,
                                                    'Risk Score',
                                                );
                                               // dd($aml_risk_results);
                                            @endphp
                                            @if (!empty($aml_risk_results))

                                                    @foreach ($aml_risk_results as $aml)
                                                        <span class="badge badge-{{ $aml['badge'] }}"> {{ $aml['rule_name'] }} </span>
                                                        <i class="fal fa-question-circle {{ $aml['badge'] }}"
                                                            data-toggle="tooltip" data-placement="auto" title=""
                                                            data-original-title="{{ $aml['rule_description'] }}"></i>
                                                            @php  $status = $aml['status']; @endphp
                                                    @endforeach

                                            @endif
                                        @php } @endphp


                                    </td>
                                    <td>
                                        <div class="main-guage-wrap">
                                        {{ $risk_score }} %
                                        <progress  class="{{$status}}" id="{{$status}}" value="{{ $risk_score  }}" max="100" style=""></progress>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="5">No records found</td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                @if (strpos(Request::route()->uri, 'reports-management/client/risk/search') !== false)
                    {{ $clients->links('global-partials.pagination-post-req', [
                        'perPage' => $clients->perPage(),
                        'currentPage' => $clients->currentPage(),
                        'total' => $clients->total(),
                        'hiddenInputs' => [
                            'per_page' => $request['per_page'],
                            'search_string' => $request['search_string'],
                        ],
                    ]) }}
                @else
                    {{ $clients->links('global-partials.pagination', [
                        'perPage' => $clients->perPage(),
                        'currentPage' => $clients->currentPage(),
                        'total' => $clients->total(),
                    ]) }}
                @endif
                <div class="row d-flex justify-content-end">
                    <div class="col-md-2 d-flex justify-content-end">
                        <div class="form-group">
                            <hr>
                            <a class="btn btn-success text-white"
                                href="{{ url('/client-management/client-profile-wise-generate-excel/') }}">
                                Export to Excel
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $search_string = isset($request) && isset($request['search_string']) ? $request['search_string'] : null;
    @endphp

    <form name="search" id="search" method="post" action="{{ url('/reports-management/client/risk/search') }}"
        enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="per_page" id="per_page"
            value="{{ isset($request->per_page) ? $request->per_page : '' }}">
        <input type="hidden" name="search_string" id="search_string"
            value="{{ isset($request->search_string) ? $request->search_string : '' }}" />
    </form>


@stop
@section('scripts')
    <script>
        var hiddenInput = document.getElementById('search_string')
        var search_string = "<?php echo $search_string; ?>"
        var perPage = "<?php echo $request['per_page']; ?>"
        var recordsCount = "<?php echo count($clients->items()); ?>"

        perPage = perPage ? perPage : 10
        document.getElementById('per_page').value = perPage


        $(document).ready(function() {
            $('#data-grid').DataTable({
                responsive: true,
                // serverSide: true,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex justify-content-end'l>>" +
                    "<'row'<'col-sm-12'tr>>"
            });

            const pageLength = document.getElementsByName('data-grid_length')[0]
            const searchInput = document.querySelectorAll('input[type="search"]')[0]

            if (pageLength) {
                const options = Array.from(pageLength.options)
                let optionToSelect = options.find(item => item.value == perPage)
                optionToSelect.setAttribute('selected', true)

                if (perPage == -1) {
                    perPage = recordsCount
                }

                $('#data-grid').DataTable().page.len(perPage).draw();

                pageLength.addEventListener('change', function() {
                    document.getElementById('per_page').value = pageLength.value
                    document.getElementById('search').submit()
                })
            }

            if (search_string && searchInput) {
                searchInput.value = search_string
            }

            if (searchInput) {
                searchInput.addEventListener('input', e => {
                    hiddenInput.value = e.target.value
                    updateDebounceText(e.target.value)
                })
            }

        });

        $('.js-thead-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
        });

        $('.js-tbody-colors a').on('click', function() {
            var theadColor = $(this).attr("data-bg");
            console.log(theadColor);
            $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
        });

        const updateDebounceText = debounce(searchString => {
            hiddenInput.value = searchString
            document.getElementById('search').submit();
        });

        function debounce(callback, delay = 1000) {
            let timeout

            return (...args) => {
                clearTimeout(timeout)
                timeout = setTimeout(() => {
                    callback(...args)
                }, delay)
            }
        }



    //     window.addEventListener('load', function() {
    //     jQuery(document).ready(function($) {

    //             setTimeout(function() {
    //                 jQuery('.gauge-wrap').simpleGauge({width:'50'});
    //             }, 2000);
    //     });
    // });
    </script>
    <style>
        /* .main-guage-wrap { margin: 150px auto; max-width: 900px; }
        .gauge-wrap { width:  100px; height: 50px; } */
        .low_risk{
            accent-color: #ffc800;
        }
        /* For Firefox */
        .medium_risk{
            accent-color: #807e7e;
        }
        .high_risk{
            accent-color: #fd3995;
        }
        .prohibited{
            accent-color: #A52A2A;
        }
        progress {
            width: 100%;
            height: 8px;
            border-radius: 5px;
            background: lightgray;
            /* Hide default styles */
            -webkit-appearance: none;
            appearance: none;
        }

        /* For Chrome, Safari */
        progress::-webkit-progress-bar {
            background-color: lightgray;
            border-radius: 5px;
        }

         /* For Webkit browsers */

        progress.low_risk::-webkit-progress-value {
            background-color: #ffc800;
        }
        progress.low_risk::-moz-progress-bar {
            background-color: #ffc800;
        }

        progress.medium_risk::-webkit-progress-value {
            background-color: #807e7e;
        }
        progress.medium_risk::-moz-progress-bar {
            background-color: #807e7e;
        }

        progress.high_risk::-webkit-progress-value {
            background-color: #fd3995;
        }
        progress.high_risk::-moz-progress-bar {
            background-color: #fd3995;
        }

        progress.prohibited::-webkit-progress-value {
            background-color: #A52A2A;
        }

        progress.prohibited::-moz-progress-bar {
            background-color: #A52A2A;
        }
    </style>
@stop
