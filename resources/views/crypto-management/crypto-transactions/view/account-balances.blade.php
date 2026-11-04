
<button type="button" class="btn btn-secondary mr-2 " onclick="showBalance()"><i class="fal fa-sack"></i> Show Blance</button>
<div class="modal fade" id="bitpace-account-balance-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title ">Account Balances (Bitpace)</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true"><i class="fal fa-times"></i></span>
                </button>
            </div>
            <div class="modal-body">
                    <div class="form-group" id="bitpace-account-balances">
                      
                    </div>

                    <div class="form-group">
                        
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                
            </div>

        </div>
    </div>
</div>
<script>
function showBalance() {
    $.get("{{ url('/crypto-management/crypto-transactions/bitpace/balance') }}",
    function(data, status) {
    //console.log(data.);
        data = JSON.parse(data);
        //var header = '<div class="row mb-4"><div class="col-md-3"></div><div class="col-md-3"><h5>Currency</h5></div><div class="col-md-3"><p>Balance</p></div></div>';
        //$('#bitpace-account-balances').append(header);
        $('#bitpace-account-balances').html('');
        if (data.status == 'DECLINED') {

            var cardHtml = '<div class="alert alert-warning">'+data.message+'</div>';
            $('#bitpace-account-balances').append(cardHtml);
            $("#bitpace-account-balance-modal").modal('show');
            return false;
        }
        $.each(data, function (index, crypto) {
           var  cardHtml = `
            <div class="row mb-4">
                <div class="col-md-4">
                    <img src="${crypto.logo}" class="card-img-top crypto-balance-logo" alt="${crypto.name}" >
                </div>
                <div class="col-md-4">
                    <h5>${crypto.name} - ${crypto.short}</h5>
                </div>
                <div class="col-md-4">
                    <p>${crypto.balance}</p>
                </div>
               
            </div>
        `;
            $('#bitpace-account-balances').append(cardHtml);
        });
       
        $("#bitpace-account-balance-modal").modal('show');
    });    
}

</script>