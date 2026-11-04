@extends('layouts.cp')
@section('title', 'Client Card Statement - Client Card Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Card Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Card Statement
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                {{-- <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/client/client-account-statement-generate') }}"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="card_id" value="{{ $card_id }}">
                    <div class="row mb-4 p-4">
                        <div class="col-md-3  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="from_date">From Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="from_date"
                                    value="{{ old('from_date') }}" id="from_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3  bg-secondary text-white p-3">
                            <div class="form-group">
                                <label class="form-label" for="to_date">To Date</label>
                                <input type="text" autocomplete="off" class="form-control" name="to_date"
                                    value="{{ old('to_date') }}" id="to_date" placeholder="Select date">
                            </div>
                        </div>
                        <div class="col-md-3 bg-secondary text-white p-3">
                            <button onclick="searchTrn(); return false;" class="btn btn-primary mt-4"><i
                                    class="fal fa-print-search"></i> Search</button>
                            <button onclick="resetForm(); return false;" class="btn btn-primary mt-4">Clear</button>
                        </div>
                        <div class="col-md-3  bg-secondary text-white p-3">&nbsp;</div>
                    </div>
                </form> --}}

                <div class="d-flex justify-content-center">
                    <h4 style="margin-top: 10px;">Current Card..</h4>
                    <div class="col-sm-12 col-md-4 col-lg-2 col-xl-2 col-xxl-2">
                        <div class="form-group">
                            <select onchange="getCardStatement(this.value);" name="card_id" autofocus id="card_id"
                                value="" data-select2-id="card_id" class="form-control select2 w-100" required>
                                <option value="">Choose Card</option>
                                @foreach ($linkedCards as $card)
                                    @if ($card_id !== null && $card->id == $card_id)
                                        <option value="{{ $card->id }}" selected>
                                            {{ $card->cardDetails->cardNumber }}
                                        </option>
                                    @else
                                        <option value="{{ $card->id }}">{{ $card->cardDetails->cardNumber }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('card_id')
                                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                    </div>
                </div>
                <hr />

                <div class="d-flex justify-content-between">
                    <div>
                        <h4>Card Statement</h4>
                    </div>
                    {{-- @if (isset($statements) && count($statements) > 0)
                        <div><a href="javascript:generatePDF()" class="btn btn-outline-success"><i
                                    class="fal fa-file-pdf"></i> Export to PDF</a></div>
                    @endif --}}
                    <div class="mb-2"><a href="javascript:generatePDF()" class="btn btn-outline-success"><i
                                class="fal fa-file-pdf"></i> Export to PDF</a></div>
                </div>


                <table class="table table-bordered table-hover m-0 shadow"
                    @if (isset($statements) && count($statements) > 0) id="data-grid" @endif>
                    @php
                        $currency = ['840' => 'USD'];
                        $type = ['LOAD_CARD' => 'Topup', 'card_to_card_transfer_money' => 'Card to Card Transfer'];
                        $nature = ['+' => 'Credit', '-' => 'Debit'];
                    @endphp
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.No</th>
                            @if (isset($card_type) && $card_type != 'Instant Card')
                                <th>Card Holder</th>
                            @endif
                            <th>Card Number</th>
                            <th>Currency</th>
                            <th>Type</th>
                            <th>Nature</th>
                            <th>Amount</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if (isset($statements) && count($statements) > 0)
                            @foreach ($statements as $statement)
                                <tr>
                                    <td align="center" scope="row">{{ $loop->iteration }}</td>
                                    @if (isset($card_type) && $card_type != 'Instant Card')
                                        <td>{{ trim($statement->card_holder) }}</td>
                                    @endif
                                    <td>{{ $statement->card_number }}</td>
                                    <td>{{ $currency[$statement->processing_currency] }}</td>
                                    <td>{{ trim($statement->type) }}</td>
                                    <td>{{ $nature[substr($statement->processing_amount, 0, 1)] }}</td>
                                    <td>{{ \App\Helpers\Common::formatNumber($statement->terminal_amount, false) }}
                                    </td>
                                    <td>{{ $statement->time }}
                                    </td>
                                    {{-- <td>{{ \Carbon\Carbon::createFromFormat('d/m/Y', $statement->time)->format('d m, Y') }}
                                    </td> --}}
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <form name="export_to_pdf" id="export_to_pdf" method="post"
        action="{{ url('/client/card-management/card/statement/pdf') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="card_id" id="selected_card_id" value="">
    </form>
@stop
@section('scripts')
    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {
            $('.select2').select2();

            $('#from_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls
            });

            $('#to_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                searching: true,
                paging: false,
                // dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'lB>>" +
                //     "<'row'<'col-sm-12'tr>>" +
                //     "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                // buttons: [
                //     /*{
                //     	extend:    'colvis',
                //     	text:      'Column Visibility',
                //     	titleAttr: 'Col visibility',
                //     	className: 'mr-sm-3'
                //     },*/
                //     {
                //         extend: 'pdfHtml5',
                //         text: 'PDF',
                //         titleAttr: 'Generate PDF',
                //         className: 'btn-outline-danger btn-sm mr-1'
                //     },
                //     {
                //         extend: 'excelHtml5',
                //         text: 'Excel',
                //         titleAttr: 'Generate Excel',
                //         className: 'btn-outline-success btn-sm mr-1'
                //     }
                // ]
            });
        });

        function getCardStatement(card_id) {
            document.getElementById('selected_card_id').value = card_id
            window.location.href = "{{ url('/client/card-management/card/statement') }}/" + card_id
        }

        function generatePDF() {

            const link_id = document.getElementById('card_id').value
            document.getElementById('selected_card_id').value = link_id
            // $.get("{{ url('/client/card-management/card/statement/pdf') }}/" + link_id)
            $("#export_to_pdf").submit();
        }

        function resetForm() {
            return window.location.replace("{{ url('/client/card-management/card/statement') }}");
        }

        function searchTrn() {
            if ($("#from_date").val() == "" && $("#to_date").val() == "") {
                alert("please period or date range");
                return false;
            }

            $("#search").attr("action", "{{ url('/client/card-management/card/statement-generate') }}");
            $("#search").submit();
        }
    </script>
@stop
