<form id="form2" method="post" action="{{ url('/client-profile/' . $client->type . '-files/store') }}"
    enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="client_id" value="{{ $client->id }}">

    @if ($client->type == 'individual')
        <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
    @elseif ($client->type == 'company')
        <input type="hidden" name="company_id" value="{{ $client->company->id }}">
    @endif
    <div class="main">

        <div class="text">
            <h2>Related Files</h2>
            <p>Please upload all the mandatory files.</p>
        </div>
        <div class="row">
            <div class="col-md-12">
                <table width="100%" class="table table-striped table-bordered table-hover table-responsive">
                    <thead>
                        <tr>
                            <th>S.NO</th>
                            <th>Required Documents</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $cntr = 1; @endphp
                        @foreach ($fileTypes as $file)
                            @if ($file->signed_pdf)
                                @php continue; @endphp
                            @endif
                            {{--  @php
                                if ($client->type == 'individual') {
                                    $existingFiles = $client->individual->files->where('file_id', $file->id);
                                } elseif ($client->type == 'company') {
                                    $existingFiles = $client->company->files->where('file_id', $file->id);
                                }

                            @endphp --}}
                            <tr>
                                <td>{{ $cntr++ }}</td>
                                <td>{{ $file->title }}<br>
                                    @if ($file->isMultiple)
                                        <span><i>(You can upload multiple files)</i></span>
                                    @else
                                        <span><i>(You can upload single file only)</i></span>
                                    @endif
                                    <div class="mt-2"></div>
                                    {{--  @if (!$existingFiles->count() > 0) --}}
                                    <input type="file"
                                        @if ($file->maxCount) onchange="setFileCount(this)" @endif
                                        @if ($file->isMandatory) required @endif
                                        @if ($file->isMultiple) multiple name="file-{{ $file->id }}[]" @else name="file-{{ $file->id }}" @endif>
                                    {{--  @else
                                        @foreach ($existingFiles as $efile)
                                            <div class="row p-2">
                                                <div class="col-md-8">
                                                    <a href="{{ $efile->url }}" target="_blank"><i
                                                            class="fal fa-file-alt"></i> View</a>
                                                </div>
                                                <div class="col-md-4">
                                                    <a href="javascript:deleteFile({{ $efile->id }})"><i
                                                            class="alert-danger fal fa-times-circle rounded-circle"></i></a>
                                                </div>
                                            </div>
                                        @endforeach
                                        @if ($file->isMultiple)
                                            <hr>
                                            <input type="file"
                                                @if ($file->maxCount) onchange="setFileCount(this)" @endif
                                                @if ($file->isMandatory) required @endif
                                                @if ($file->isMultiple) multiple name="file-{{ $file->id }}[]" @else name="file-{{ $file->id }}" @endif>
                                        @endif
                                    @endif --}}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <hr>
                    <button type="submit" class="btn btn-primary">Submit </i></button>
                </div>
            </div>
        </div>
        <div id="loading-spinner-form1"
            style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); height: 100px; display:none">
            <?= '<?xml version="1.0" encoding="utf-8"?>' ?>
            <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
                style="background: none; shape-rendering: auto;" width="50px" height="50px" viewBox="0 0 100 100"
                preserveAspectRatio="xMidYMid">
                <circle cx="50" cy="50" fill="none" stroke="#ffc800" stroke-width="13" r="38"
                    stroke-dasharray="179.0707812546182 61.690260418206066">
                    <animateTransform attributeName="transform" type="rotate" repeatCount="indefinite" dur="0.625s"
                        values="0 50 50;360 50 50" keyTimes="0;1"></animateTransform>
                </circle>
            </svg>
        </div>
    </div>
</form>

<script>
    document.getElementById("form2").addEventListener("submit", function() {
        var loadingSpinner = document.getElementById("loading-spinner-form1");
        var submitButton = document.querySelector("button[type='submit']");
        // Show the loading spinner
        loadingSpinner.style.display = "block";
        submitButton.disabled = true;
    });
</script>
