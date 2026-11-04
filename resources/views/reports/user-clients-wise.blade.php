@extends('layouts.cp')
@section('title', 'Client Wise Report - Reports Management')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Reports Management</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Client Wise Report
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')
                <form name="search" id="search" method="post"
                    action="{{ url('/reports-management/client/user-client-wise-search') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="form-label" for="user_id">User</label>
                                <select name="user_id" id="user_id" data-select2-id="user_id"
                                    class="form-control select2 w-100" required>
                                    <option value="">choose user</option>
                                    @foreach ($users as $user)
                                        @if ($user->id == $user_id)
                                            <option value="{{ $user->id }}" selected>{{ $user->email }}</option>
                                        @else
                                            <option value="{{ $user->id }}">{{ $user->email }}</option>
                                        @endif
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>
                        <div class="col-md-2 mt-4">
                            <button type="submit" class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </form>
                <div class="d-flex justify-content-between">
                    <div class="mb-2"><button hidden onclick="generateExcel();" id="excelButton"
                            class="btn btn-outline-success btn-sm">Excel</button>
                        <button hidden onclick="generatePdf();" id="pdfButton"
                            class="btn btn-outline-danger btn-sm">PDF</button>
                    </div>
                </div>
                <br>
                <table class="table table-bordered table-hover m-0 shadow"
                    @if ($clients->count() > 0) id="data-grid" @endif>
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">S.NO</th>
                            <th>Client</th>
                            @if ($currencies->count() > 0)
                                @foreach ($currencies as $currency)
                                    <th>{{ $currency->short }}</th>
                                @endforeach
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if ($users && $users->count() > 0)
                            @php
                                $counter = 1;
                                $balance = 0;
                                $total = [];
                                $hideTotal = $clients->count() === 1;
                            @endphp
                            @foreach ($clients as $client)
                                <tr>
                                    <td align="center" scope="row">{{ $counter++ }}</td>
                                    <td>{{ $client->name }}</td>
                                    @if ($currencies->count() > 0)
                                        @foreach ($currencies as $currency)
                                            @php
                                                $balance = \App\Helpers\Common::getClientBalanceByCurrencyId(
                                                    $client->id,
                                                    $currency->id,
                                                );
                                                $total[$currency->id] = isset($total[$currency->id])
                                                    ? $total[$currency->id] + $balance
                                                    : $balance;
                                            @endphp
                                            <th>{{ \App\Helpers\Common::formatNumber($balance, $currency->is_crypto) }}
                                            </th>
                                        @endforeach
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="8" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                    @if (!$hideTotal)
                        <tfoot>
                            <tr>
                                <th></th>
                                <td><strong>Total</strong></td>
                                @if ($currencies->count() > 0)
                                    @foreach ($currencies as $currency)
                                        <td>
                                            <b>{{ \App\Helpers\Common::formatNumber($total[$currency->id], $currency->is_crypto) }}</b>
                                        </td>
                                    @endforeach
                                @endif
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2();

            $('#data-grid').dataTable({
                responsive: true,
                lengthChange: false,
                dom: "<'row mb-3'<'col-sm-12 col-md-6 d-flex align-items-center justify-content-start'f><'col-sm-12 col-md-6 d-flex align-items-center justify-content-end'lB>>" +
                    "<'row'<'col-sm-12'tr>>" +
                    "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
                buttons: [
                    /*{
                    	extend:    'colvis',
                    	text:      'Column Visibility',
                    	titleAttr: 'Col visibility',
                    	className: 'mr-sm-3'
                    },*/
                    {
                        extend: 'pdfHtml5',
                        text: 'PDF',
                        titleAttr: 'Generate PDF',
                        className: 'btn-outline-danger btn-sm mr-1',
                        action: function(e, dt, node, config) {
                            $('#pdfButton').trigger('click');
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: 'Excel',
                        titleAttr: 'Generate Excel',
                        className: 'btn-outline-success btn-sm mr-1',
                        action: function(e, dt, node, config) {
                            $('#excelButton').trigger('click');
                        }
                    }
                ]
            });

            $('.js-thead-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example thead').removeClassPrefix('bg-').addClass(theadColor);
            });

            $('.js-tbody-colors a').on('click', function() {
                var theadColor = $(this).attr("data-bg");
                $('#dt-basic-example').removeClassPrefix('bg-').addClass(theadColor);
            });

        });

        function generateExcel() {
            $('input[name="export_pdf"]').remove();
            $('#search').append('<input type="hidden" name="export_excel" value="excel">');
            $('#search').submit();
            $('input[name="export_excel"]').removeAttr('value');
        }

        function generatePdf() {
            $('input[name="export_excel"]').remove();
            $('#search').append('<input type="hidden" name="export_pdf" value="pdf">');
            $('#search').submit();
            $('input[name="export_pdf"]').removeAttr('value');
        }
    </script>
@stop
