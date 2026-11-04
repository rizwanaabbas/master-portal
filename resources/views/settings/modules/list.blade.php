@extends('layouts.cp')
@section('title', 'Modules - System Settings')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>System Settings</span>
        </h1>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false" data-panel-color="false"
        data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                Modules
            </h2>
        </div>

        <div class="panel-container show mb-3">
            <div class="panel-content">
                @include('common.error-message')
                <div id="modules-flash" class="alert d-none mb-3" role="alert"></div>

                <div class="row mb-3">
                    <div class="col-md-3 mb-2">
                        <label class="form-label">Status</label>
                        <select id="filter-status" class="form-control form-control-sm">
                            <option value="all">All</option>
                            <option value="1">Enabled</option>
                            <option value="0">Disabled</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2 d-flex align-items-end">
                        <button type="button" id="btn-reset-module-filters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    </div>
                </div>

                <table class="table table-bordered table-hover m-0 shadow" id="data-grid" style="width:100%">
                    <thead class="thead-themed">
                        <tr>
                            <th>ID</th>
                            <th>Module Name</th>
                            <th>Status</th>
                            <th>Updated By</th>
                            <th>Updated Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($modules as $module)
                            <tr data-enabled="{{ $module->is_enabled ? '1' : '0' }}">
                                <td class="align-middle">{{ $module->id }}</td>
                                <td class="align-middle">
                                    @if (!empty($module->configuration))
                                        <a href="{{ url('/settings/module-configurations/edit/' . $module->configuration->id) }}">
                                            <i class="fal fa-cog"></i> {{ $module->name }}
                                        </a>
                                    @else
                                        {{ $module->name }}
                                    @endif
                                </td>
                                <td class="align-middle" data-order="{{ $module->is_enabled ? 1 : 0 }}">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input module-enabled"
                                            @if ($module->is_enabled) checked @endif
                                            id="module-{{ $module->id }}"
                                            data-module-id="{{ $module->id }}"
                                            value="1">
                                        <label class="custom-control-label" for="module-{{ $module->id }}">Enabled</label>
                                    </div>
                                </td>
                                <td class="align-middle module-updated-by">{{ $module->enabler ? $module->enabler->name : '—' }}</td>
                                <td class="align-middle module-updated-at" data-order="{{ optional($module->updated_at)->timestamp ?? 0 }}">
                                    {{ $module->updated_at ? $module->updated_at->format('d M, Y H:i') : ($module->enabled_disabled_at ? \Carbon\Carbon::parse($module->enabled_disabled_at)->format('d M, Y H:i') : '—') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

@stop

@section('scripts')
    <script>
        $(document).ready(function() {
            $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                if (settings.nTable.id !== 'data-grid') {
                    return true;
                }
                const status = $('#filter-status').val();
                if (status === 'all') {
                    return true;
                }
                const row = settings.aoData[dataIndex].nTr;
                return $(row).attr('data-enabled') === status;
            });

            const table = $('#data-grid').DataTable({
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'All']],
                order: [[1, 'asc']],
                columnDefs: [
                    { targets: 2, orderable: true }
                ]
            });

            $('#filter-status').on('change', function() {
                table.draw();
            });

            $('#btn-reset-module-filters').on('click', function() {
                $('#filter-status').val('all');
                table.search('').draw();
            });

            function showFlash(ok, message) {
                const $el = $('#modules-flash');
                $el.removeClass('d-none alert-success alert-danger')
                    .addClass(ok ? 'alert-success' : 'alert-danger')
                    .text(message);
            }

            $('#data-grid').on('change', '.module-enabled', function() {
                const $toggle = $(this);
                const moduleId = $toggle.data('module-id');
                const isEnabled = $toggle.is(':checked');
                const $row = $toggle.closest('tr');

                $toggle.prop('disabled', true);

                $.ajax({
                    url: '{{ url('/settings/modules/update') }}',
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        module_id: moduleId,
                        is_enabled: isEnabled ? 1 : 0,
                    },
                    dataType: 'json',
                }).done(function(data) {
                    if (!data.success) {
                        $toggle.prop('checked', !isEnabled);
                        showFlash(false, data.message || 'Unable to update module status.');
                        return;
                    }
                    const enabled = data.is_enabled ? 1 : 0;
                    $row.attr('data-enabled', enabled);
                    table.cell($row.find('td').eq(2)).invalidate();
                    $row.find('td').eq(2).attr('data-order', enabled);
                    $row.find('.module-updated-by').text(data.updated_by || '—');
                    if (data.updated_at) {
                        $row.find('.module-updated-at').text(data.updated_at);
                    }
                    showFlash(true, data.message || (isEnabled ? 'Module enabled.' : 'Module disabled.'));
                }).fail(function(xhr) {
                    $toggle.prop('checked', !isEnabled);
                    const msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error))
                        || 'Unable to update module status. Please try again.';
                    showFlash(false, msg);
                }).always(function() {
                    $toggle.prop('disabled', false);
                });
            });
        });
    </script>
@stop
