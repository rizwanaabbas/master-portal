<div class="modal fade" id="sepa-deposit-approval-warning-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:500px;" role="document">
        <div class="modal-content border-primary">

            <!-- Modal Header -->
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Transaction Approval Warning</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body text-center px-6">
                <!-- Warning Icon -->
                <svg class="bi bi-exclamation-triangle text-primary mb-3" xmlns="http://www.w3.org/2000/svg"
                    width="90" height="90" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="lucide lucide-triangle-alert-icon lucide-triangle-alert">
                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3" />
                    <path d="M12 9v4" />
                    <path d="M12 17h.01" />
                </svg>

                <p class="px-3">
                    You are required to approve this <b id="transaction-method">SEPA / SEPA Instant</b> transaction.
                    The transaction will be processed within the <b>{{ $ownCompany ? $ownCompany->name : '' }}</b>
                    system.
                    Do you want to approve?

                </p>

                <!-- Hidden Confirmation Checkbox -->
                <input type="hidden" id="confirm-deposit-transaction-with-sepa" name="confirm-sepa-deposit"
                    value="false" />

            </div>

            <!-- Modal Footer -->
            <div class="modal-footer d-flex justify-content-center">
                <button type="button" class="btn btn-primary btn-wide" id="confirmTransactionBtn">
                    Yes, Approve it!
                </button>
                <button type="button" id="cancel-btn-trn" class="btn btn-secondary btn-wide" data-dismiss="modal">Cancel</button>
            </div>

        </div>
    </div>
</div>

<script>
    document.getElementById("confirmTransactionBtn").addEventListener("click", function() {
        let hiddenInput = document.getElementById("confirm-deposit-transaction-with-sepa");
        hiddenInput.value = "true";
        $('#sepa-deposit-approval-warning-modal').modal('hide');

        if(hiddenInput.value == "true") {
            document.getElementById('approval-form').submit();
        }
        
        // Swal.fire({
        //     title: "Are you sure?",
        //     text: "You are about to approve this transaction.",
        //     icon: "warning",
        //     showCancelButton: true,
        //     confirmButtonText: "Yes, Approve it!"
        // }).then(function(result) {
        //     if (result.value) {
        //         document.getElementById('approval-form').submit();
        //     }
        // });
    });
</script>

<style>
    .btn-wide {
        padding-left: 40px;
        padding-right: 40px;
    }
</style>
