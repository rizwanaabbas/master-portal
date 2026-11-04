@extends('layouts.cp')
@section('title', 'Edit Fee Tier Group - Fee Engine')

@section('content')
    <div class="subheader">
        <h1 class="subheader-title"><span class='fw-300'>Fee Engine</span> &mdash; {{ $group->name }}</h1>
        <a href="{{ url('/settings/fee-engine/groups') }}" class="btn btn-primary ml-auto"><i class="fal fa-arrow-alt-left"></i> Go Back</a>
    </div>

    @include('feeengine::partials.flash')

    <div class="panel">
        <div class="panel-hdr"><h2>Group Settings</h2></div>
        <div class="panel-container show">
            <div class="panel-content">
                <form method="post" action="{{ url('/settings/fee-engine/groups/update') }}">
                    @csrf
                    <input type="hidden" name="id" value="{{ $group->id }}">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Name</label>
                            <input type="text" name="name" value="{{ $group->name }}" class="form-control" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">Code (slug)</label>
                            <input type="text" name="code" value="{{ $group->code }}" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ $group->description }}</textarea>
                    </div>
                    <div class="custom-control custom-switch mb-2">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1" {{ $group->is_default ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_default">Default group (fallback for unassigned clients)</label>
                    </div>
                    <div class="custom-control custom-switch mb-3">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ $group->is_active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="is_active">Active</label>
                    </div>
                    <hr>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="panel">
        <div class="panel-hdr">
            <h2>Currency Fee Rules</h2>
            <div class="panel-toolbar">
                <button class="btn btn-sm btn-primary" onclick="openRuleModal()"><i class="fal fa-plus-circle"></i> Add Rule</button>
            </div>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                <p class="text-muted">Multiple rules can share the same currency (they stack). A <strong>wildcard</strong> rule (currency = Any) applies to currencies not explicitly configured; exact-currency rules take priority.</p>
                <table class="table table-bordered table-hover table-striped m-0">
                    <thead class="thead-themed">
                        <tr>
                            <th>Priority</th><th>Label</th><th>Currency</th><th>Type</th>
                            <th class="text-right">Value</th><th class="text-right">Min</th><th class="text-right">Max</th>
                            <th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($group->currencyFeeTiers as $tier)
                            <tr>
                                <td>{{ $tier->priority }}</td>
                                <td>{{ $tier->label }}</td>
                                <td>
                                    {{ $tier->scopeLabel() }}
                                    @if ($tier->currency_scope !== 'currency')
                                        <span class="badge badge-info">wildcard</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst($tier->fee_type) }}</td>
                                <td class="text-right">{{ $tier->fee_value }}{{ $tier->fee_type === 'percentage' ? '%' : '' }}</td>
                                <td class="text-right">{{ $tier->min_fee ?? '—' }}</td>
                                <td class="text-right">{{ $tier->max_fee ?? '—' }}</td>
                                <td><span class="badge {{ $tier->is_active ? 'badge-success' : 'badge-secondary' }}">{{ $tier->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary"
                                        onclick='openRuleModal(@json($tier))'><i class="fal fa-edit"></i></button>
                                    <form method="post" action="{{ url('/settings/fee-engine/tiers/delete') }}" class="d-inline" onsubmit="return confirm('Remove this rule?');">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $tier->id }}">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fal fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted">No rules yet. Add one to start charging fees for this group.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Add / Edit rule modal --}}
    <div class="modal fade" id="ruleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <form class="modal-content" method="post" id="ruleForm" action="{{ url('/settings/fee-engine/tiers') }}">
                @csrf
                <input type="hidden" name="id" id="rule_id">
                <input type="hidden" name="fee_tier_group_id" value="{{ $group->id }}">
                <div class="modal-header">
                    <h5 class="modal-title" id="ruleModalTitle">Add Rule</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" id="rule_label" class="form-control" required placeholder="e.g. Network Fee">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Applies To</label>
                        <select name="currency_scope" id="rule_currency_scope" class="form-control" onchange="toggleScope()">
                            <option value="currency">Specific currency</option>
                            <option value="all_crypto">All crypto currencies (wildcard)</option>
                            <option value="all_fiat">All fiat currencies (wildcard)</option>
                        </select>
                        <small class="text-muted">A specific-currency rule overrides the matching wildcard.</small>
                    </div>
                    <div class="form-group" id="rule_currency_wrap">
                        <label class="form-label">Currency</label>
                        <select name="currency_id" id="rule_currency_id" class="form-control">
                            <option value="">Choose currency</option>
                            @foreach ($currencies as $c)
                                <option value="{{ $c->id }}">{{ $c->short }} — {{ $c->name }}{{ $c->is_crypto ? ' (crypto)' : '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Fee Type</label>
                            <select name="fee_type" id="rule_fee_type" class="form-control" onchange="toggleMinMax()">
                                <option value="percentage">Percentage</option>
                                <option value="flat">Flat</option>
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">Fee Value <small class="text-muted" id="valueHint">(% e.g. 1.5)</small></label>
                            <input type="number" step="any" min="0" name="fee_value" id="rule_fee_value" class="form-control" required>
                        </div>
                    </div>
                    <div class="row" id="minMaxRow">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Min Fee</label>
                            <input type="number" step="any" min="0" name="min_fee" id="rule_min_fee" class="form-control">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label">Max Fee</label>
                            <input type="number" step="any" min="0" name="max_fee" id="rule_max_fee" class="form-control">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Priority</label>
                            <input type="number" name="priority" id="rule_priority" class="form-control" value="0">
                        </div>
                        <div class="col-md-6 form-group d-flex align-items-end">
                            <div class="custom-control custom-switch">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" class="custom-control-input" id="rule_is_active" name="is_active" value="1" checked>
                                <label class="custom-control-label" for="rule_is_active">Active</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rule</button>
                </div>
            </form>
        </div>
    </div>
@stop

@section('scripts')
    <script>
        const ADD_URL = "{{ url('/settings/fee-engine/tiers') }}";
        const UPDATE_URL = "{{ url('/settings/fee-engine/tiers/update') }}";

        function toggleMinMax() {
            const isPct = $('#rule_fee_type').val() === 'percentage';
            $('#minMaxRow').toggle(isPct);
            $('#valueHint').text(isPct ? '(% e.g. 1.5)' : '(absolute amount)');
        }

        function toggleScope() {
            const isSpecific = $('#rule_currency_scope').val() === 'currency';
            $('#rule_currency_wrap').toggle(isSpecific);
            $('#rule_currency_id').prop('required', isSpecific);
            if (!isSpecific) $('#rule_currency_id').val('');
        }

        function openRuleModal(tier) {
            $('#ruleForm')[0].reset();
            if (tier) {
                $('#ruleModalTitle').text('Edit Rule');
                $('#ruleForm').attr('action', UPDATE_URL);
                $('#rule_id').val(tier.id);
                $('#rule_label').val(tier.label);
                $('#rule_currency_scope').val(tier.currency_scope || 'currency');
                $('#rule_currency_id').val(tier.currency_id || '');
                $('#rule_fee_type').val(tier.fee_type);
                $('#rule_fee_value').val(tier.fee_value);
                $('#rule_min_fee').val(tier.min_fee);
                $('#rule_max_fee').val(tier.max_fee);
                $('#rule_priority').val(tier.priority);
                $('#rule_is_active').prop('checked', !!tier.is_active);
            } else {
                $('#ruleModalTitle').text('Add Rule');
                $('#ruleForm').attr('action', ADD_URL);
                $('#rule_id').val('');
                $('#rule_is_active').prop('checked', true);
            }
            toggleMinMax();
            toggleScope();
            $('#ruleModal').modal('show');
        }

        $(document).ready(function () { toggleMinMax(); toggleScope(); });
    </script>
@stop
