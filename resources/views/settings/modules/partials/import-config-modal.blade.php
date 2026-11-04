<div class="modal fade" id="import-module-config-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ url('/settings/module-configurations/import') }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="module_id" id="import-module-id" value="">
                <div class="modal-header">
                    <h5 class="modal-title">Import configuration <span id="import-module-name" class="font-weight-normal"></span></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">This replaces the existing configuration for this module only. API key values are not imported and current keys are kept.</p>
                    <input type="file" name="file" class="form-control" accept="application/json,.json" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Import and replace</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
    document.addEventListener('click', function (event) {
        var btn = event.target && event.target.closest ? event.target.closest('[data-import-module]') : null;
        if (!btn) {
            return;
        }
        var idInput = document.getElementById('import-module-id');
        var nameEl = document.getElementById('import-module-name');
        if (idInput) {
            idInput.value = btn.getAttribute('data-import-module') || '';
        }
        if (nameEl) {
            nameEl.textContent = btn.getAttribute('data-import-name') ? '— ' + btn.getAttribute('data-import-name') : '';
        }
    });
</script>
