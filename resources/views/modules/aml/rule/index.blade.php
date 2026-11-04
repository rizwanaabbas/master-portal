@extends('layouts.cp')
@section('title', 'AML Management - All Rules')

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>AML Rules</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('/settings/aml/new') }}" class="btn btn-primary ml-2 mx-md-2">
                <i class="fal fa-plus"></i> Add New Rule
            </a>

            <a href="{{ url('/settings/aml/data') }}" class="btn btn-primary ml-2 mx-md-2">
                <i class="fal fa-database"></i> Manage Data
            </a>
        </div>
    </div>
    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <div class=" mt-4">
        <table id="aml-rules-table" class="table table-striped table-bordered">
            <thead class="thead-themed">
                <tr>
                    <th>ID</th>
                    <th>Data Set</th>
                    <th>Rule Name</th>
                    <th>Description</th>
                    <th>Field</th>
                    <th>Criteria</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Badge</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($amlRules as $rule)
                    <tr>
                        <td>{{ $rule->id }}</td>
                        <td>{{ $rule->amlData->name }}</td>
                        <td>{{ $rule->rule_name }}</td>
                        <td>{{ $rule->rule_description }}</td>
                        <td>{{ $rule->field }}</td>
                        <td>{{ $rule->rule_criteria }}</td>
                        <td>
                            @if (in_array($rule->field, ['nationality_id', 'residence_country_id']))
                                @php
                                    $countryIds = json_decode($rule->value, true);
                                    $countryNames = App\Models\Country::whereIn('id', $countryIds)
                                        ->pluck('name')
                                        ->toArray();
                                @endphp

                                {{ $rule->value }} => {{ implode(', ', $countryNames) }}
                            @else
                                {{ $rule->value }}
                            @endif
                        </td>
                        <td>{{ $rule->status }}</td>
                        <td><span class='badge badge-{{ $rule->badge }}'>{{ $rule->badge }}</span></td>
                        <td>
                            <a href="{{ url('/settings/aml/edit', $rule->id) }}" class="">
                                <i class="fal fa-edit"> </i>
                            </a>
                            @can('delete records')
                                <a href="javascript:void(0)" onclick="deleteRule({{ $rule->id }})" class="">
                                    <i class="fal fa-trash"> </i>
                                </a>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#aml-rules-table').DataTable();
        });

        function deleteRule(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "The AML rule will be deleted permanently.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Delete it!"
            }).then(function(result) {
                if (result.value) {
                    window.location = "{{ url('/settings/aml/delete') }}/" + id;
                    //document.getElementById('verify_client').submit()
                }
            });
        }
    </script>
@endsection
