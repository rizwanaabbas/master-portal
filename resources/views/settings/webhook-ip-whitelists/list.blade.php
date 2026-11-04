@extends('layouts.cp')
@section('title', 'Webhook IP Whitelists - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <a href="{{ url('/settings/webhook-ip-whitelists/new') }}" class="btn btn-primary mr-3"><i class="fal fa-plus-circle"></i>
            Add New </a>

    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Webhook IP Whitelist List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed ">
                        <tr>
                            <th width="30"></th>
                            <th>Service</th>
                            <th>IP Address</th>
                            <th>Whitelisted For</th>
                            <th>Is Active</th>
                            @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin']))
                                <th width="30" align="center"></th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @if ($webhookIpWhitelists)
                            @foreach ($webhookIpWhitelists as $webhookIpWhitelist)
                                @php
                                    switch ($webhookIpWhitelist->is_active) {
                                        case '1':
                                            $class = 'badge-success';
                                            break;
                                        case '0':
                                            $class = 'badge-danger';
                                            break;
                                        default:
                                            $class = 'badge-secondary';
                                    }
                                @endphp
                                <tr>
                                    <td align="center" scope="row"><a
                                            href="{{ url('/settings/webhook-ip-whitelists/edit/' . $webhookIpWhitelist->id) }}"><i
                                                class="fal fa-edit"></i></a></td>
                                    <td width="30%">{{ $webhookIpWhitelist->service }}</td>
                                    <td>{{ $webhookIpWhitelist->ip_address }}</td>
                                    <td>{{ $webhookIpWhitelist->whitelisted_for }}</td>
                                    <td><span class="badge {{ $class }}">{{ $webhookIpWhitelist->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin']))
                                        <td><a href="javascript:deleteWebhookIpWhitelist({{ $webhookIpWhitelist->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @endif
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="6" align="center">No records found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <form method="POST" name="delete-webhook-ip-whitelist" id="delete-webhook-ip-whitelist"
        action="{{ url('/settings/webhook-ip-whitelists/delete') }}">
        @csrf
        <input type="hidden" name="id" id="whitelist_id" value="">
    </form>
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true
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
        });

        function deleteWebhookIpWhitelist(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Webhook IP Whitelist entry will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#whitelist_id").val(id);
                    document.getElementById('delete-webhook-ip-whitelist').submit()
                }
            });
        }
    </script>
@stop
