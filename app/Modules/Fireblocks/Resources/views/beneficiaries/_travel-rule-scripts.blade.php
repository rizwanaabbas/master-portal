<script>
    function toggleSendingToMyself() {
        const isSelf = $('#is_sending_to_myself').is(':checked');
        if (isSelf) {
            $('#beneficiary_type').val('BUSINESS').prop('disabled', true);
            $('#beneficiary_legal_name').prop('readonly', true);
            toggleBeneficiaryType();
        } else {
            $('#beneficiary_type').prop('disabled', false);
            $('#beneficiary_legal_name').prop('readonly', false);
        }
    }

    function toggleBeneficiaryType() {
        const type = $('#beneficiary_type').val();
        if (type === 'INDIVIDUAL') {
            $('#individual_fields').show();
            $('#business_fields').hide();
            $('#beneficiary_first_name, #beneficiary_last_name').attr('required', true);
            $('#beneficiary_legal_name').removeAttr('required');
        } else if (type === 'BUSINESS') {
            $('#individual_fields').hide();
            $('#business_fields').show();
            $('#beneficiary_legal_name').attr('required', true);
            $('#beneficiary_first_name, #beneficiary_last_name').removeAttr('required');
        } else {
            $('#individual_fields').hide();
            $('#business_fields').hide();
        }
    }

    function toggleWalletType() {
        const type = $('#wallet_type').val();
        if (type === 'CUSTODIAL') {
            $('#vasp_section').show();
            $('#ownership_section').hide();
            $('#is_ownership_declaration').removeAttr('required');
            toggleCustomVasp();
        } else if (type === 'SELF_HOSTED') {
            $('#vasp_section').hide();
            $('#ownership_section').show();
            $('#is_ownership_declaration').attr('required', true);
            $('#beneficiary_vasp_did, #beneficiary_vasp_name').removeAttr('required');
        } else {
            $('#vasp_section').hide();
            $('#ownership_section').hide();
            $('#is_ownership_declaration').removeAttr('required');
            $('#beneficiary_vasp_did, #beneficiary_vasp_name').removeAttr('required');
        }
    }

    function toggleCustomVasp() {
        if ($('#is_not_on_vasp_list').is(':checked')) {
            $('#custom_vasp_section').show();
            $('#beneficiary_vasp_name').attr('required', true);
            $('#beneficiary_vasp_did').removeAttr('required');
        } else {
            $('#custom_vasp_section').hide();
            $('#beneficiary_vasp_name').removeAttr('required');
            $('#beneficiary_vasp_did').attr('required', true);
        }
    }

    $(document).ready(function() {
        toggleSendingToMyself();
        toggleBeneficiaryType();
        toggleWalletType();
    });
</script>
