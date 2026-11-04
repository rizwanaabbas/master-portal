@extends('layouts.cp')
@section('title', 'Module Configurations - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
        <a href="{{ url('/settings/module-configurations/new') }}" class="btn btn-primary mr-3"><i
                class="fal fa-plus-circle"></i> Add Module Configurations </a>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Configurations List
            </h2>

        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content" style="overflow: scroll;">
                @include('common.error-message')

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid">
                    <thead class="thead-themed">
                        <tr>
                            <th width="30">Edit</th>
                            <th>Module</th>
                            <th>Creator</th>
                            <th>Created At</th>
                            <th>Updated By</th>
                            <th>Updated At</th>
                            <th>Module Status</th>
                            @can('delete records')
                                <th width="30" align="center">Delete</th>
                            @endcan
                        </tr>
                    </thead>
                    <tbody>
                        @if ($moduleConfigurations)
                            @foreach ($moduleConfigurations as $configurations)
                                <tr>
                                    <td align="center" scope="row">
                                        <a href="{{ url('/settings/module-configurations/edit/' . $configurations->id) }}">
                                            <i class="fal fa-edit"></i>
                                        </a>
                                    </td>
                                    <td width="25%">{{ $configurations->module->name }}</td>
                                    <td>{{ $configurations->creator ? $configurations->creator->name : '--' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($configurations->created_at)->format('d M, Y H:i') }}</td>
                                    <td>{{ $configurations->updater ? $configurations->updater->name : '--' }}</td>
                                    <td>{{ $configurations->updated_at ? \Carbon\Carbon::parse($configurations->updated_at)->format('d M, Y H:i') : '--' }}
                                    </td>
                                    <td>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input module-status-toggle"
                                                id="module-toggle-{{ $configurations->module_id }}"
                                                data-module-id="{{ $configurations->module_id }}"
                                                @if ($configurations->module->is_enabled) checked @endif>
                                            <label class="custom-control-label"
                                                for="module-toggle-{{ $configurations->module_id }}">
                                                <span
                                                    class="badge {{ $statusBadge[$configurations->module->is_enabled ? '1' : '0'] }} module-status-badge">
                                                    {{ $configurations->module->is_enabled ? 'Enabled' : 'Disabled' }}
                                                </span>
                                            </label>
                                        </div>
                                    </td>
                                    @can('delete records')
                                        <td><a href="javascript:deleteConfigurations({{ $configurations->id }})"><i
                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a></td>
                                    @endcan
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

    <form method="POST" name="delete-configurations" id="delete-configurations"
        action="{{ url('/settings/module-configurations/delete') }}">
        @csrf
        <input type="hidden" name="configurations_id" id="configurations_id" value="">
    </form>
@stop
@section('scripts')
    <script>
        $(document).ready(function() {
            $('#data-grid').dataTable({
                responsive: true,
                order: [
                    [5, 'desc']
                ]
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

            $(document).on('change', '.module-status-toggle', function() {
                const $toggle = $(this);
                const moduleId = $toggle.data('module-id');
                const isEnabled = $toggle.is(':checked');
                const $badge = $toggle.closest('.custom-control').find('.module-status-badge');

                $toggle.prop('disabled', true);

                fetch('{{ url('/settings/module-configurations/toggle-module') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({
                            module_id: moduleId,
                            is_enabled: isEnabled ? 1 : 0,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            $badge
                                .removeClass('badge-success badge-primary')
                                .addClass(isEnabled ? 'badge-success' : 'badge-primary')
                                .text(isEnabled ? 'Enabled' : 'Disabled');
                        } else {
                            $toggle.prop('checked', !isEnabled);
                            alert(data.message || 'Unable to update module status.');
                        }
                    })
                    .catch(function() {
                        $toggle.prop('checked', !isEnabled);
                        alert('Unable to update module status. Please try again.');
                    })
                    .finally(function() {
                        $toggle.prop('disabled', false);
                    });
            });
        });

        function deleteConfigurations(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Module Configurations will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#configurations_id").val(id);
                    document.getElementById('delete-configurations').submit()
                }
            });
        }
    </script>
@stop
