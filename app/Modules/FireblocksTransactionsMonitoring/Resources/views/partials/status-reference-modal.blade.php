@include('fireblocks-monitoring::partials.status-badge-styles')
<div class="modal fade" id="fb-status-reference-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Understand Fireblocks Statuses</h5>
                <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">
                    Primary and sub-status definitions follow the
                    <a href="https://developers.fireblocks.com/reference/statuses" target="_blank" rel="noopener">Fireblocks statuses</a>
                    and
                    <a href="https://developers.fireblocks.com/reference/sub-statuses" target="_blank" rel="noopener">sub-statuses</a>
                    reference. Retriable / non-retriable columns reflect your module configuration.
                </p>
                <p class="small mb-3">
                    <strong>Badge colors:</strong>
                    <span class="badge fb-int-pending">Pending</span> orange —
                    <span class="badge fb-int-rejected">Rejected</span> red —
                    <span class="badge fb-sub-blocked">Blocked / policy</span> same orange as pending —
                    each Fireblocks primary status has its own shade in the primary tab.
                </p>
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#fb-ref-primary" role="tab">Primary Statuses</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fb-ref-substatus" role="tab">Sub-statuses</a></li>
                    <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#fb-ref-internal" role="tab">Internal Mapping</a></li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="fb-ref-primary" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered table-striped">
                                <thead class="thead-themed">
                                    <tr>
                                        <th>API Code</th>
                                        <th>Badge</th>
                                        <th>Fireblocks Console</th>
                                        <th>Internal</th>
                                        <th>Boost</th>
                                        <th>Drop</th>
                                        <th>Resubmit</th>
                                        <th>Ops Hint</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statusCatalog['primary_statuses'] ?? [] as $row)
                                        <tr>
                                            <td><code>{{ $row['code'] }}</code></td>
                                            <td><span class="{{ $row['badge_class'] ?? 'badge badge-light' }}">{{ $row['badge_label'] ?? $row['code'] }}</span></td>
                                            <td>{{ $row['console_label'] ?? $row['label'] }}</td>
                                            <td>{{ $row['default_internal'] }}</td>
                                            <td>{{ $row['boost'] ?? '—' }}</td>
                                            <td>{{ $row['drop'] ?? '—' }}</td>
                                            <td>{{ $row['resubmit'] ?? '—' }}</td>
                                            <td><small>{{ $row['ops_action_hint'] }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="fb-ref-substatus" role="tabpanel">
                        <input type="text" class="form-control form-control-sm mb-2" id="fb-ref-substatus-search" placeholder="Search sub-statuses by code, category, or meaning...">
                        <div class="table-responsive" style="max-height:420px;overflow-y:auto;">
                            <table class="table table-sm table-bordered" id="fb-ref-substatus-table">
                                <thead class="thead-themed">
                                    <tr>
                                        <th>Sub-status</th>
                                        <th>Badge</th>
                                        <th>Category</th>
                                        <th>Meaning</th>
                                        <th>Retriable?</th>
                                        <th>Ops Hint</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($statusCatalog['substatuses'] ?? [] as $row)
                                        <tr>
                                            <td><code>{{ $row['code'] }}</code></td>
                                            <td><span class="{{ $row['badge_class'] ?? 'badge badge-light' }}">{{ $row['badge_label'] ?? $row['code'] }}</span></td>
                                            <td><small>{{ $row['category'] ?? '—' }}</small></td>
                                            <td>{{ $row['message'] }}</td>
                                            <td>{{ $row['retriable'] }}</td>
                                            <td><small>{{ $row['ops_action_hint'] }}</small></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="fb-ref-internal" role="tabpanel">
                        <p class="text-muted">Portal internal status is derived from Fireblocks primary + sub-status via <code>FireblocksStatusHelper::resolve()</code>.</p>
                        @foreach ($statusCatalog['internal_statuses'] ?? [] as $row)
                            <div class="mb-3">
                                <h6><span class="{{ $row['badge_class'] ?? 'badge badge-light' }} mr-1">{{ $row['label'] }}</span></h6>
                                <p class="mb-1">{{ $row['description'] }}</p>
                                <small class="text-muted">Sources: {{ $row['sources'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <small class="text-muted">
                    Docs:
                    <a href="https://developers.fireblocks.com/reference/statuses" target="_blank" rel="noopener">Primary statuses</a> ·
                    <a href="https://developers.fireblocks.com/reference/sub-statuses" target="_blank" rel="noopener">Sub-statuses</a>
                </small>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
