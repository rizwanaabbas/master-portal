<div class="transaction-notes-wrapper">

    <input type="hidden" form="save-note" name="transaction_request_id" value="{{ $transaction_request_id }}">

    <textarea form="save-note" name="transaction_notes" id="transaction_notes" class="form-control" rows="2" placeholder="Enter Transaction Note"></textarea>

    <div class="d-flex mt-2">
        <button form="save-note" type="submit" class="btn btn-sm btn-primary mr-2">Save Note</button>
        <button type="button" class="btn btn-sm btn-secondary" id="notes_history" style="display: none;">
            View Notes History
        </button>
    </div>

</div>

<div class="modal fade" id="transactionHistoryModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Transaction Notes</h5>
                <button type="button" id="close-modal" class="close text-white" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="font-weight-bold">Notes History</span>
                </div>
                <table class="table table-hover" id="history-table">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Note</th>
                            <th>Date Created</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody id="history-list"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const transactionRequestId = "{{ $transaction_request_id }}";
        const notesBtn = document.getElementById('notes_history');
        const modalEl = document.getElementById('transactionHistoryModal');
        const modal = new bootstrap.Modal(modalEl);

        fetch(`/finance-management/transactions/get-notes/${transactionRequestId}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    notesBtn.style.display = 'inline-block';
                }
            })
            .catch(error => console.error("Could not check notes history.", error));

        notesBtn.addEventListener('click', function() {
            fetch(`/finance-management/transactions/get-notes/${transactionRequestId}`)
                .then(response => response.json())
                .then(data => {
                    const historyList = document.getElementById('history-list');
                    historyList.innerHTML = '';

                    data.forEach((note, index) => {
                        const row = document.createElement('tr');
                        row.innerHTML = `
                        <td>${index + 1}</td>
                        <td>${note.note}</td>
                        <td>${note.created_at}</td>
                        <td>${note.creator ? note.creator.name : 'N/A'}</td>
                    `;
                        historyList.appendChild(row);
                    });

                    modal.show();
                })
                .catch(error => console.error("Could not load notes.", error));
        });

        document.getElementById('close-modal').addEventListener('click', function() {
            modal.hide();
        });
    });
</script>
