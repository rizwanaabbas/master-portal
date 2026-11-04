$(document).on('click', '.fb-crypto-beneficiary-link', function (e) {
    e.preventDefault();
    var id = $(this).data('beneficiary-id');
    var $modal = $('#fb-crypto-beneficiary-modal');
    if (!$modal.length) {
        return;
    }
    $modal.find('.modal-body').html('<div class="text-center p-4"><i class="fal fa-spinner fa-spin fa-2x"></i></div>');
    $modal.modal('show');
    $.get('{{ url('/crypto-management/fireblocks-beneficiaries') }}/' + id + '/modal')
        .done(function (res) {
            if (res.success) {
                $modal.find('.modal-title').text(res.title || 'Beneficiary');
                $modal.find('.modal-body').html(res.html);
            } else {
                $modal.find('.modal-body').html('<div class="alert alert-danger mb-0">Unable to load beneficiary.</div>');
            }
        })
        .fail(function () {
            $modal.find('.modal-body').html('<div class="alert alert-danger mb-0">Unable to load beneficiary.</div>');
        });
});
