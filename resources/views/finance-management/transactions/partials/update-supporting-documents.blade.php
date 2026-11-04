@php
    if (empty($docs) && $transactionRequest) {
        $docs = $transactionRequest->docs;
    }

    $accTrnDocs = null;

    if ($transactionRequest->link_tr_id) {
        if ($transactionRequest->linkedTrn->is_for_client && $transactionRequest->linkedTrn->docs) {
            $accTrnDocs = $transactionRequest->linkedTrn->docs;
        }
    }
@endphp

@if ($status != 'Rejected')

    <tr>
        <th>Supporting Documents</th>
        <td>
            @if (count($docs))
                @foreach ($docs as $doc)
                    @if (!$doc->is_payment)
                        <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary mt-2 mr-2" target="_blank"><i
                                class="fal fa-file-alt"></i>
                            View</a>
                        @can('delete records')
                            <a href="javascript:deleteFile({{ $doc->id }})"><i
                                    class="alert-danger fal fa-times-circle rounded-circle mr-2"></i>
                            </a>
                        @endcan
                    @endif
                @endforeach
            @endif

            @if ($accTrnDocs)
                @foreach ($accTrnDocs as $doc)
                    @if (!$doc->is_payment)
                        <a href="{{ $doc->url }}" class="btn btn-sm btn-outline-secondary mt-2 mr-2" target="_blank"><i
                                class="fal fa-file-alt"></i>
                            View</a>
                        @can('delete records')
                            <a href="javascript:deleteFile({{ $doc->id }})"><i
                                    class="alert-danger fal fa-times-circle rounded-circle mr-2"></i>
                            </a>
                        @endcan
                    @endif
                @endforeach
            @endif

            <div class="form-group">
                <div class="row mt-2">
                    <div class="col-md-9">
                        <input type="file" class="form-control" onchange="handleChangeDocument(this.value, this)"
                            name="supporting_documents[]" id="document_0" value="">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)"
                            type="button"><i class="fal fa-plus"></i></button>
                    </div>
                    <div class="col-md-1 ml-2">
                        <button class="btn btn-danger btn-remove-document d-none"
                            onclick="handleRemoveDocument('document_0', false, this)" type="button"><i
                                class="fal fa-times"></i></button>
                    </div>
                </div>
            </div>
            @error('supporting_documents')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </td>
    </tr>

    <script>
        function handleChangeDocument(value, e) {
            if (value) {
                e.closest('.row').querySelector('.btn-add-document').classList.remove('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.remove('d-none')
            } else {
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function handleAddDocument(e) {
            e.closest('.form-group').insertAdjacentHTML(
                'beforeend',
                '<div class="row mt-2"> <div class="col-md-9"> <input type="file" required class="form-control" onchange="handleChangeDocument(this.value, this)" name="supporting_documents[]" value=""> </div> <div class="col-md-1"> <button class="btn btn-primary btn-add-document d-none" onclick="handleAddDocument(this)" type="button"><i class="fal fa-plus"></i></button> </div> <div class="col-md-1 ml-2"> <button class="btn btn-danger btn-remove-document" onclick="handleRemoveDocument(null, true, this)" type="button"><i class="fal fa-times"></i></button> </div> </div>'
            )
        }

        function handleRemoveDocument(inputId, deleteContainer, e) {
            if (deleteContainer) {
                e.closest('.row').remove()
            } else {
                document.getElementById(inputId).value = ""
                e.closest('.row').querySelector('.btn-add-document').classList.add('d-none')
                e.closest('.row').querySelector('.btn-remove-document').classList.add('d-none')
            }
        }

        function deleteFile(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "File will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#file_id").val(id);
                    document.getElementById('delete-file').submit()
                }
            });
        }
    </script>
@endif
