<div class="row">
    <div class="col-md-12 mt-4">
        <div class="form-group">
            <label class="form-label" for="document_0">Supporting Documents</label>
            <div class="row">
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
            @error('supporting_documents')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
            @enderror
        </div>
    </div>
</div>

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
</script>
