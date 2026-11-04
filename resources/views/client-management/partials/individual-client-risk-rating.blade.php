@php
    $activeRating = $client->clientriskrating;
    $hasValidScoringData = $activeRating && !empty($clientScoringData);
@endphp

{{-- Display a warning message if the active record is incomplete. --}}
@if ($activeRating && !$hasValidScoringData)
    <div class="alert alert-warning" role="alert">
        <strong>Data Inconsistency Found:</strong> An existing risk rating was found, but its detailed scoring data is
        missing. Saving this form will create a new, complete risk rating and replace the incomplete one.
    </div>
@endif

<form method="post" action="{{ url('/client-management/clients/update/client-risk-rating') }}"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="client_id" value="{{ $client->id }}">
    @php
        $hasArchived = \App\Models\ClientRiskRating::where('client_id', $client->id)
            ->where('status', 'archive')
            ->exists();
    @endphp
    @if ($hasArchived)
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary btn-shape mr-2" type="button" id="rating-history">
                        <i class="fal fa-plus-circle"></i> Rating History
                    </button>
                    <button class="btn btn-secondary btn-shape" type="button" id="back-to-active"
                        style="display: none;">
                        <i class="fal fa-arrow-left"></i> Back to Active
                    </button>
                </div>
            </div>
        </div>
    @endif
    <div class="row">
        <div class="col-md-12">
            <table class="table table-striped table-bordered table-hover table-responsive-xl">
                <thead>
                    <tr>
                        <th>Risk Factors</th>
                        <th>Risk Description</th>
                        <th>Risk Score</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="3">
                            <table class="table table-bordered mt-2">
                                <tbody>
                                    <tr class="table-secondary">
                                        <td colspan="3" class="medium-text">
                                            <b>Customer Risk</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Clients Employment status</td>
                                        <td>
                                            <textarea name="scoring_data[customer_risk][clients_employment_status][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.customer_risk.clients_employment_status.description', $clientScoringData['customer_risk']['clients_employment_status']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[customer_risk][clients_employment_status][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.customer_risk.clients_employment_status.score', $clientScoringData['customer_risk']['clients_employment_status']['score'] ?? '') }}">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Annual Income</td>
                                        <td>
                                            <textarea name="scoring_data[customer_risk][annual_income][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.customer_risk.annual_income.description', $clientScoringData['customer_risk']['annual_income']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[customer_risk][annual_income][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.customer_risk.annual_income.score', $clientScoringData['customer_risk']['annual_income']['score'] ?? '') }}">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>PEPs</td>
                                        <td>
                                            <textarea name="scoring_data[customer_risk][peps][description]" placeholder="Enter description" class="form-control">{{ old('scoring_data.customer_risk.peps.description', $clientScoringData['customer_risk']['peps']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[customer_risk][peps][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.customer_risk.peps.score', $clientScoringData['customer_risk']['peps']['score'] ?? '') }}">
                                        </td>
                                    </tr>

                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <table class="table table-bordered mt-2">
                                <tbody>
                                    <tr class="table-secondary">
                                        <td colspan="3" class="medium-text">
                                            <b>Country / Geographical Risk</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Nationality</td>
                                        <td>
                                            <textarea name="scoring_data[country_geographical_risk][nationality][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.country_geographical_risk.nationality.description', $clientScoringData['country_geographical_risk']['nationality']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[country_geographical_risk][nationality][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.country_geographical_risk.nationality.score', $clientScoringData['country_geographical_risk']['nationality']['score'] ?? '') }}">
                                        </td>

                                    </tr>

                                    <tr>
                                        <td>Country Resident</td>
                                        <td>
                                            <textarea name="scoring_data[country_geographical_risk][country_resident][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.country_geographical_risk.country_resident.description', $clientScoringData['country_geographical_risk']['country_resident']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[country_geographical_risk][country_resident][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.country_geographical_risk.country_resident.score', $clientScoringData['country_geographical_risk']['country_resident']['score'] ?? '') }}">
                                        </td>
                                    </tr>


                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <table class="table table-bordered mt-2">
                                <tbody>
                                    <tr class="table-secondary">
                                        <td colspan="3" class="medium-text">
                                            <b>Product / Services</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Product / Services use</td>
                                        <td>
                                            <textarea name="scoring_data[product_services][product_services_use][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.product_services.product_services_use.description', $clientScoringData['product_services']['product_services_use']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[product_services][product_services_use][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.product_services.product_services_use.score', $clientScoringData['product_services']['product_services_use']['score'] ?? '') }}">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Current Financial Services Provider</td>
                                        <td>
                                            <textarea name="scoring_data[product_services][current_financial_service_provider][description]"
                                                placeholder="Enter description" class="form-control">{{ old('scoring_data.product_services.current_financial_service_provider.description', $clientScoringData['product_services']['current_financial_service_provider']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[product_services][current_financial_service_provider][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.product_services.current_financial_service_provider.score', $clientScoringData['product_services']['current_financial_service_provider']['score'] ?? '') }}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <table class="table table-bordered mt-2">
                                <tbody>
                                    <tr class="table-secondary">
                                        <td colspan="3" class="medium-text">
                                            <b>Delivery Channel Risk</b>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Channel</td>
                                        <td>
                                            <textarea name="scoring_data[delivery_channel][channel][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.delivery_channel.channel.description', $clientScoringData['delivery_channel']['channel']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[delivery_channel][channel][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.delivery_channel.channel.score', $clientScoringData['delivery_channel']['channel']['score'] ?? '') }}">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Reference</td>
                                        <td>
                                            <textarea name="scoring_data[delivery_channel][reference][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.delivery_channel.reference.description', $clientScoringData['delivery_channel']['reference']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[delivery_channel][reference][score]"
                                                class="form-control
                                                w-50"
                                                required
                                                value="{{ old('scoring_data.delivery_channel.reference.score', $clientScoringData['delivery_channel']['reference']['score'] ?? '') }}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="3">
                            <table class="table table-bordered mt-2">
                                <tbody>
                                    <tr class="table-secondary">
                                        <td colspan="3" class="medium-text">
                                            <b>Transactional Risk</b>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Transaction Volume</td>
                                        <td>
                                            <textarea name="scoring_data[transactional_risk][transaction_volume][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.transactional_risk.transaction_volume.description', $clientScoringData['transactional_risk']['transaction_volume']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[transactional_risk][transaction_volume][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.transactional_risk.transaction_volume.score', $clientScoringData['transactional_risk']['transaction_volume']['score'] ?? '') }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Compliance Analysis</td>
                                        <td>
                                            <textarea name="scoring_data[transactional_risk][compliance_analysis][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.transactional_risk.compliance_analysis.description', $clientScoringData['transactional_risk']['compliance_analysis']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[transactional_risk][compliance_analysis][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.transactional_risk.compliance_analysis.score', $clientScoringData['transactional_risk']['compliance_analysis']['score'] ?? '') }}">
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>Other</td>
                                        <td>
                                            <textarea name="scoring_data[transactional_risk][other][description]" placeholder="Enter description"
                                                class="form-control">{{ old('scoring_data.transactional_risk.other.description', $clientScoringData['transactional_risk']['other']['description'] ?? '') }}</textarea>
                                        </td>
                                        <td>
                                            <input type="number" min="0" step="0.01"
                                                name="scoring_data[transactional_risk][other][score]"
                                                class="form-control w-50" required
                                                value="{{ old('scoring_data.transactional_risk.other.score', $clientScoringData['transactional_risk']['other']['score'] ?? '') }}">
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="medium-text">
                        <td colspan="2"><strong>Total Risk Score (%)</strong>

                        </td>
                        <td><strong id="risk_score">
                                @if ($hasValidScoringData)
                                    {{ $client->clientriskrating->risk_score }} %
                                @else
                                    0.00 %
                                @endif
                            </strong></td>
                    </tr>
                </tfoot>
            </table>
            <div class="form-group">
                <label for="remarks"><strong>Remarks:</strong></label>
                <textarea name="remarks" id="remarks" class="form-control" rows="3" placeholder="Enter remarks here...">{{ old('remarks', $client->clientriskrating->remarks ?? '') }}</textarea>
                @error('remarks')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                @enderror
            </div>
            <div class="form-group">



            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="float-left">
                        <div class="form-group">
                            <hr>
                            <button type="submit" class="btn btn-primary">Save</button>

                        </div>
                    </div>
                </div>
                @php
                    $rules = '';
                    $data = App\Modules\Aml\Controllers\AmlRuleController::getDataByName('Risk Score');
                    if (!empty($data)) {
                        $rules = $data->amlRules;
                    }

                @endphp
                @if (!empty($rules))
                    <div class="col-md-3">
                        <div>
                            <fieldset class="border p-2 bg-white">
                                <legend class="w-auto">Risk Rating</legend>

                                <table class="table table-bordered">
                                    <thead>
                                        <tr class="bg-secondary text-white">
                                            <th>Risk Score</th>
                                            <th>Points</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($rules as $rule)
                                            @php $values = explode(',', $rule->value); @endphp
                                            <tr>
                                                <td>{{ $rule->rule_name }}</td>
                                                <td>
                                                    @if (is_array($values))
                                                        @if ($loop->last)
                                                            {{ $values[0] }} and more
                                                        @else
                                                            {{ $values[0] }} – {{ $values[1] }}
                                                        @endif
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                            </fieldset>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div id="riskGuage"></div>
                    </div>
                @endif
                @if ($client->clientriskrating)
                    <div class="col-md-3 mt-5">
                        <div class="float-right">
                            <strong>Client Risk Rating Report:</strong>
                            <button type="button" onclick="downloadRiskRatingPDF();"
                                class="btn btn-sm btn-outline-secondary">
                                <i class="fal fa-download"></i>
                                Download Report
                            </button>
                        </div>
                    </div>
                @endif

            </div>
        </div>
</form>

<div class="modal fade" id="ratingHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Client Risk Rating History</h5>
                <button type="button" id="close-modal" class="close text-white" data-dismiss="modal"
                    aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="font-weight-bold">
                        Select an archived record to view details
                    </span>
                </div>

                <table class="table table-hover" id="history-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Date Created</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody id="history-list">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<style>
    #riskGuage {
        width: 16em;
        height: 16em;
    }
</style>
@section('scripts')
    @parent
    <script>
        @if ($hasValidScoringData && !empty($client->clientriskrating->risk_score))
            window.addEventListener('load', function() {
                jQuery(document).ready(function($) {
                    jQuery('#riskGuage').simpleGauge({
                        value: 0,
                        bars: {
                            colors: [
                                [0, '#2c8410', 0, 0],
                                [31, '#807e7e', 0, 0],
                                [61, '#f1b00d', 0, 0],
                                [91, '#c90805', 0, 0]
                            ]
                        }
                    });
                    setTimeout(function() {
                        jQuery('#riskGuage').simpleGauge({
                            value: {{ $client->clientriskrating->risk_score }},
                            //title: {text:'Percent'},
                            bars: {
                                style: '',
                                colors: [
                                    [0, '#2c8410', 0, 0],
                                    [31, '#807e7e', 0, 0],
                                    [61, '#f1b00d', 0, 0],
                                    [91, '#c90805', 0, 0]
                                ]
                            }
                        });
                        $('.simpleGauge_digital span').html(
                            parseFloat({{ $client->clientriskrating->risk_score }}).toFixed(
                                2) + '%');
                    }, 2000);
                });
            });
        @endif

        let selectedRecordId = null;

        function downloadRiskRatingPDF() {
            const clientId = @json($client->id);
            const url = selectedRecordId ?
                `/client-management/clients/download-risk-rating/${clientId}/${selectedRecordId}` :
                `/client-management/clients/download-risk-rating/${clientId}`
            window.location.href = url;
        }

        // Initialize the active scoring data and remarks
        const activeScoringData = @json($clientScoringData);
        const activeRemarks = @json($client->clientriskrating->remarks ?? '');
        const activeRiskScore = @json($client->clientriskrating->risk_score ?? 0);

        $(document).ready(function() {
            $('#close-modal').click(function() {
                $('#ratingHistoryModal').modal('hide');
            });
            $('#rating-history').click(function() {
                $.ajax({
                    url: '/client-management/clients/{{ $client->id }}/risk-rating-history',
                    type: 'GET',
                    success: function(data) {
                        $('#history-list').empty();
                        const totalRecords = data.length;
                        data.forEach(function(record, index) {
                            const recordNumber = totalRecords - index;
                            $('#history-list').append(`
                        <tr class="history-item" data-id="${record.id}" style="cursor: pointer;">
                            <td><strong>${recordNumber}</strong></td>
                            <td>
                                <a href="#" class="font-weight-bold text-primary date-link" style="text-decoration: none;">
                                    ${moment(record.created_at).format('DD MMM YYYY')}
                                </a>
                            </td>
                            <td class="text-muted">${record.created_by.name ?? ''}</td>
                        </tr>
                    `);
                        });

                        $('#ratingHistoryModal').modal('show');
                    },
                    error: function() {
                        alert('Failed to retrieve history.');
                    }
                });
            });

            // Handle back to active button
            $('#back-to-active').click(function() {
                selectedRecordId = null;
                $('textarea[name^="scoring_data"], input[name^="scoring_data"]').each(function() {
                    const name = $(this).attr('name');
                    const path = name.replace(/^scoring_data\[/, '').replace(/\]$/g, '').replace(
                        /\]\[/g, '.').replace(/\[/g, '.').replace(/\]/g, '');
                    const keys = path.split('.');
                    let value = activeScoringData;
                    for (let key of keys) {
                        if (value && key in value) {
                            value = value[key];
                        } else {
                            value = '';
                            break;
                        }
                    }
                    $(this).val(value).prop('disabled', false);
                });
                $('#remarks').val(activeRemarks).prop('disabled', false);
                $('#risk_score').text(parseFloat(activeRiskScore).toFixed(2) + '%');
                $('button[type="submit"]').prop('disabled', false);
                $('#riskGuage').empty();
                setTimeout(function() {
                    $('#riskGuage').simpleGauge({
                        value: parseFloat(activeRiskScore),
                        bars: {
                            colors: [
                                [0, '#2c8410'],
                                [31, '#807e7e'],
                                [61, '#f1b00d'],
                                [91, '#c90805']
                            ]
                        }
                    });
                    $('.simpleGauge_digital span').html(parseFloat(activeRiskScore).toFixed(2) +
                        '%');
                }, 500);
                $('#back-to-active').hide();
            });

            // Load selected history record into the form
            $('#history-list').on('click', '.history-item', function(e) {
                e.preventDefault();
                selectedRecordId = $(this).data('id');
                $('#back-to-active').show();
                var recordId = $(this).data('id');

                $.ajax({
                    url: `/client-management/clients/risk-rating/${recordId}`,
                    type: 'GET',
                    success: function(data) {
                        // Loop through each field
                        $('textarea[name^="scoring_data"], input[name^="scoring_data"]').each(
                            function() {
                                const name = $(this).attr('name');
                                const path = name
                                    .replace(/^scoring_data\[/, '') // remove prefix
                                    .replace(/\]$/g, '') // remove last ]
                                    .replace(/\]\[/g,
                                        '.') // convert array brackets to dot notation
                                    .replace(/\[/g, '.') // any remaining [ to dot
                                    .replace(/\]/g, ''); // and any ] to empty

                                const keys = path.split('.');
                                let value = data;
                                for (let key of keys) {
                                    if (value && key in value) {
                                        value = value[key];
                                    } else {
                                        value = null;
                                        break;
                                    }
                                }

                                if (value !== null) {
                                    $(this).val(value).prop('disabled', true);
                                }
                            });

                        // Remarks & risk score
                        $('#remarks').val(data.remarks).prop('disabled', true);
                        $('#risk_score').text(parseFloat(data.risk_score).toFixed(2) + '%');

                        // Clear the old gauge container
                        $('#riskGuage').empty();
                        setTimeout(function() {
                            $('#riskGuage').simpleGauge({
                                value: parseFloat(data.risk_score),
                                bars: {
                                    style: '',
                                    colors: [
                                        [0, '#2c8410', 0, 0],
                                        [31, '#807e7e', 0, 0],
                                        [61, '#f1b00d', 0, 0],
                                        [91, '#c90805', 0, 0]
                                    ]
                                }
                            });
                            $('.simpleGauge_digital span').html(parseFloat(data
                                .risk_score).toFixed(2) + '%');
                        }, 500);

                        // Disable Save button
                        $('button[type="submit"]').prop('disabled', true);
                        $('#ratingHistoryModal').modal('hide');
                    },
                    error: function() {
                        alert('Failed to load record.');
                    }
                });
            });
        });
    </script>
@stop
