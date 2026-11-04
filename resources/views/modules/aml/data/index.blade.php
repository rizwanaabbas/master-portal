@extends('layouts.cp')
@section('title', 'Module Management - Records')

@section('content')

<div class="subheader">
    <h1 class="subheader-title">
        <span class='fw-300'>Manage Data Sets</span>
    </h1>
    <div class="d-flex">
        <a href="{{ url('/settings/aml/data/new') }}" class="btn btn-primary ml-2 mx-md-2">
            <i class="fal fa-plus"></i> Add New Data Set
        </a>
        <a href="{{ url('/settings/aml/') }}" class="btn btn-primary ml-2 mx-md-2">
        <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>
    </div>
</div>

<div class=" mt-4">

    <!-- Flash Messages -->
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

    <div class="row">
        @foreach ($amlData as $record)
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">{{ $record->name }}</h3>
                        <div class="dropdown">
                            <button class="btn btn-link dropdown-toggle" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="fal fa-ellipsis-v"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                                <a class="dropdown-item" href="{{ url('/settings/aml/data/edit', $record->id) }}">Edit</a>
                                <!-- <a class="dropdown-item" href="{{ url('/settings/aml/data/delete', $record->id) }}">Delete</a> -->
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @php 
                            $fields = json_decode($record->fields);
                        @endphp
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Label</th>
                                    <th>Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($fields as $key=>$val)
                                    <tr>
                                        <td><strong>{{ $val }}</strong></td>
                                        <td>{{ $key }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Custom scripts if needed
    });
</script>
@endsection
