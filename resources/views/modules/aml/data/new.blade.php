@extends('layouts.cp')
@section('title', 'Add New Data Set')

@section('content')

<div class="subheader">
    <h1 class="subheader-title">
        <span class='fw-300'>Add New Data Set</span>
    </h1>
    <div class="d-flex">
        <a href="{{ url('/settings/aml/data/') }}" class="btn btn-primary ml-2 mx-md-2">
            <i class="fal fa-arrow-alt-left"></i> Go Back
        </a>
       
    </div>
</div>

<div class="mt-4">

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

    <form action="{{ url('/settings/aml/data/store') }}" method="POST">
        @csrf
        <div class="form-group">
            <label for="name">Data Set Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>

        <div class="form-group">
            <label>Fields</label>
            <div id="fields-container">
                <div class="row field-row mb-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="fields[0][label]" placeholder="Label" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="fields[0][value]" placeholder="Value" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-field">Remove</button>
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary" id="add-field">Add More</button>
        </div>

        <button type="submit" class="btn btn-primary">Save Data Set</button>
    </form>
</div>

@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        let fieldIndex = 1;

        $('#add-field').click(function() {
            let fieldHtml = `
                <div class="row field-row mb-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="fields[${fieldIndex}][label]" placeholder="Label" required>
                    </div>
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="fields[${fieldIndex}][value]" placeholder="Value" required>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-field">Remove</button>
                    </div>
                </div>
            `;
            $('#fields-container').append(fieldHtml);
            fieldIndex++;
        });

        $(document).on('click', '.remove-field', function() {
            $(this).closest('.field-row').remove();
        });
    });
</script>
@endsection
