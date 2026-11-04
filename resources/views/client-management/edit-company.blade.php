@extends('layouts.cp')
@section('title', 'Update Client - Company - Client Management')

@php
    $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules);
    $isLorumEnabled = in_array('Lorum', $enabledModules);
    $isZenusEnabled = in_array('Zenus', $enabledModules);
    // $isVirtualBanksEnabled = $isOpenPaydEnabled || $isLorumEnabled || $isZenusEnabled;

    $enabled_provider_map = [
        'openpayd' => $isOpenPaydEnabled,
        'lorum' => $isLorumEnabled,
        'zenus' => $isZenusEnabled,
    ];

    $signatoryIndex = 0;
    $client_signatories = $client->directors->where('type', 'signatory');
    $directorIndex = 0;
    $directors = $client->directors->where('type', 'director');
@endphp

@section('scripts')

    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {
            $('.select2').select2();
            $('body').on('focus', ".director-passport-expiry , .signatory-passport-expiry, #passport_expiry",
                function() {

                    if ($(this).hasClass('hasDatepicker') === false) {
                        $(this).datepicker({
                            format: 'dd/mm/yyyy',
                            todayHighlight: true,
                            orientation: "bottom left",
                            templates: controls,
                            autoclose: true,
                            startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}",
                            maxDate: "+10Y"

                        });

                    }
                });
            $('body').on('focus', ".director-dob, .signatory-dob, #dob", function() {
                if (!$(this).hasClass('hasDatepicker')) {
                    $(this).datepicker({
                        format: 'dd/mm/yyyy',
                        todayHighlight: true,
                        templates: controls,
                        autoclose: true,
                        orientation: "bottom left",
                        endDate: "{{ \Carbon\Carbon::today()->format('dd/mm/yyyy') }}"

                    });
                }
            });

            $("#upload-passport").click(function() {
                $("#upload-passport-modal").modal("show");
            });

            $("#close-passport-upload-modal").click(function() {
                $("#upload-passport-modal").modal("hide");
            });

            $(document).ready(function() {
                $('#upload-passport-modal').on('shown.bs.modal', function() {
                    $('#passport_for_client_id').select2({
                        dropdownParent: $(
                            '#upload-passport-modal') // Ensure dropdown is inside modal
                    });
                });
            });

            // $('body').on('focus', ".select2", function() {

            //});

            // $('.director-passport-expiry').datepicker({
            //     format: 'dd/mm/yyyy',
            //     todayHighlight: true,
            //     orientation: "bottom left",
            //     templates: controls,

            //     startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}",
            //     maxDate: "+10Y"

            // });
            $('#registration_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            $('#expiry_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            //$('.select2').select2();

        })

        function rejectClient() {

            $("#reject-modal").modal('show');
        }

        function closeClient() {

            $("#close-account-modal").modal('show');
        }

        function createIBAN(account_id, provider) {
            if ((provider || '').toString().toLowerCase() === 'zenus') {
                if (typeof openZenusAccountWizard === 'function') {
                    openZenusAccountWizard(account_id);
                    return;
                }
            }
            Swal.fire({
                title: "Are you sure?",
                text: 'This action will create IBAN using "' + provider + '" for the select account currency.',
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('create-iban-account-id').value = account_id;
                    document.getElementById('create-iban-form').submit()
                }
            });
        }

        function submitForApproval() {

            Swal.fire({
                title: "Are you sure?",
                text: "You manager will be notified for client approval.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Submit it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('submit-for-approval').submit()
                }
            });
        }

        function approveClient() {

            Swal.fire({
                title: "Are you sure?",
                text: "Client account will be approved.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approve-company').submit()
                }
            });
        }


        function deleteAccount(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Account will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#account_id").val(id);
                    document.getElementById('delete-account').submit()
                }
            });
        }

        function deleteDraftAccount(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Account will be deleted permanently",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    $("#draft_account_id").val(id);
                    document.getElementById('delete-draft-account').submit()
                }
            })
        }

        function maintenanceFee(id, maintenanceFeeAmount) {
            Swal.fire({
                title: "Maintenance Fee",
                text: "Maintenance Fee will be updated",
                icon: "warning",
                html: `
            <input
                id="maintenanceFeeInput"
                type="number"
                class="form-control"
                min="0"
                step="any"
                placeholder="Enter Maintenance Fee"
                value="${maintenanceFeeAmount}"
            >
        `,
                showCancelButton: true,
                confirmButtonText: "Save",
                preConfirm: () => {
                    const inputValue = document.getElementById('maintenanceFeeInput').value;

                    return inputValue;
                }
            }).then(function(result) {
                const updatedMaintenanceFee = result.value;
                console.log("Updated Maintenance Fee:", updatedMaintenanceFee);
                if (result.value) {
                    window.location.href =
                        "{{ url('/client-management/clients/accounts/maintenance-fee/') }}/" +
                        id + "/" + updatedMaintenanceFee;
                }
            });
        }

        function approveAccount(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Account will be approved",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    window.location.href = "{{ url('/client-management/clients/company/accounts/approve/') }}/" +
                        id;
                }
            });
        }

        function rejectAccount(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Account will be rejected.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Reject it!"
            }).then(function(result) {
                if (result.value) {
                    window.location.href = "{{ url('/client-management/clients/company/accounts/reject/') }}/" +
                        id;
                }
            });
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

        function setFileCount(obj, maxcount) {

            var file_count = $(obj)[0].files.length;

            console.log($(obj)[0]);

            if (file_count > maxcount) {

                Swal.fire({
                    title: "Too much files!",
                    text: "You can upload up to " + maxcount + " files maximum",
                    icon: "error",
                    showCancelButton: false,
                    confirmButtonText: "ok"
                }).then(function(result) {
                    $(obj).val('');
                    return;
                });
            }
        }

        function setCompanyType(type) {

            if (type == "Other") {
                $("#other-company-type").removeClass("disabled")
            } else {
                $("#other-company-type:not([class*='disabled'])").addClass("disabled");
            }
        }

        function setFile(obj) {

            var file = obj.files[0];
            var filename = file.name;
            document.getElementById("file-name-display").innerHTML = filename;
        }

        function addForm(formIndex, type) {
            const formRow = `
                    <div class="form-group row ${type === 'directors' ? 'director' : 'signatory'}-row-${formIndex} mb-4" data-index="${formIndex}">
                        <div class="col-md-2">
                            <input type="hidden" class="form-control" name="${type}[${formIndex}][id]" value="">
                            <input type="hidden" class="form-control" name="${type}[${formIndex}][delete]" id="delete-${formIndex}" value="0">
                            <input type="text" class="form-control ${type === 'directors' ? 'director' : 'signatory'}-name" name="${type}[${formIndex}][name]" placeholder="Full Name">
                            <div class="invalid-feedback" id="invalid-feedback-name-${formIndex}">Please enter a name.</div>
                        </div>
                        <div class="col-md-2">
                            <input type="email" class="form-control ${type === 'directors' ? 'director' : 'signatory'}-email" name="${type}[${formIndex}][email]" placeholder="Email" required>
                            <div class="invalid-feedback" id="invalid-feedback-email-${formIndex}">Please enter a valid email address.</div>
                        </div>
                        <div class="col-md-1">
                            <input type="text" class="form-control ${type === 'directors' ? 'director' : 'signatory'}-passport-number" name="${type}[${formIndex}][passport_number]" placeholder="Passport No">
                            <div class="invalid-feedback" id="invalid-feedback-passport-number-${formIndex}">Please enter a valid Passport Number.</div>
                        </div>
                        <div class="col-md-1">
                            <input type="text" class="form-control ${type === 'directors' ? 'director' : 'signatory'}-passport-expiry" name="${type}[${formIndex}][passport_expiry]" placeholder="Passport Expiry">
                            <div class="invalid-feedback" id="invalid-feedback-passport-expiry-${formIndex}">Please enter a valid Passport Expiry.</div>
                        </div>
                        <div class="col-md-1">
                            <input type="text" class="form-control ${type === 'directors' ? 'director' : 'signatory'}-dob" name="${type}[${formIndex}][dob]" placeholder="Date of birth">
                            <div class="invalid-feedback" id="invalid-feedback-dob-${formIndex}">Please enter Date of birth.</div>
                        </div>
                        <div class="col-md-1">
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="male-${type}-${formIndex}"
                                    value="male" name="${type}[${formIndex}][gender]">
                                <label class="custom-control-label" for="male-${type}-${formIndex}">Male</label>
                            </div>
                            <div class="custom-control custom-radio custom-control-inline mb-3">
                                <input type="radio" class="custom-control-input" id="female-${type}-${formIndex}"
                                    value="female" name="${type}[${formIndex}][gender]">
                                <label class="custom-control-label" for="female-${type}-${formIndex}">Female</label>
                                <div class="invalid-feedback" id="invalid-feedback-gender-${formIndex}">Please select gender.</div>
                            </div>
                        </div>
                        <div class="col-md-1">
                            <select name="${type}[${formIndex}][nationality_id]" id="${type}-nationality_id-${formIndex}"
                                data-select2-id="nationality_id-${formIndex}" class="form-control select2 w-100 select2"
                               >
                                <option value="">Nationality</option>
                                @foreach ($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}" data-iso3="{{ $nationality->iso3 }}">
                                        {{ $nationality->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="invalid-feedback-nationality-${formIndex}">Please choose nationality.</div>
                        </div>
                        <div class="col-md-1">
                            <select name="${type}[${formIndex}][residence_country_id]" id="${type}-residence_country_id-${formIndex}"
                                data-select2-id="residence_country_id-${formIndex}" class="form-control select2 w-100 select2"
                                >
                                <option value="">Residence</option>
                                @foreach ($nationalities as $nationality)
                                    <option value="{{ $nationality->id }}" data-iso3="{{ $nationality->iso3 }}">
                                        {{ $nationality->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback" id="invalid-feedback-nationality-${formIndex}">Please choose nationality.</div>
                        </div>

                        <div class="col-md-1">
                            <select name="${type}[${formIndex}][role]" id="${type}-role-${formIndex}"
                                data-select2-id="${type}-role-${formIndex}" class="form-control select2 w-100 select2"
                                >
                                <option value="">Select access</option>
                                <option value="Full access">Full access</option>
                                <option value="View access">View access</option>
                            </select>
                            <div class="invalid-feedback" id="invalid-feedback-nationality-${formIndex}">Please choose nationality.</div>
                        </div>


                        <div class="col-md-1">
                            ${
                                type === 'directors' ?
                                    `
                                                                                <div class="custom-control custom-checkbox">
                                                                                    <input type="checkbox" class="custom-control-input" id="${type}[${formIndex}][create-user]" name="${type}[${formIndex}][create-user]">
                                                                                    <label class="custom-control-label" for="${type}[${formIndex}][create-user]">
                                                                                        Create login access
                                                                                    </label>
                                                                                </div>

                                                                                <a href="javascript:void(0)" onclick="deleteDirectorRow(${formIndex})" title="Delete director">
                                                                                    <i class="fal fa-trash btn-danger btn p-2 mt-1" aria-hidden="true"></i>
                                                                                </a>
                                                                            `
                                    :
                                    `
                                                                                <div class="custom-control custom-checkbox">
                                                                                    <input type="checkbox" class="custom-control-input" id="${type}[${formIndex}][create-user]" name="${type}[${formIndex}][create-user]">
                                                                                    <label class="custom-control-label" for="${type}[${formIndex}][create-user]">
                                                                                        Create login access
                                                                                    </label>
                                                                                </div>

                                                                                <a href="javascript:void(0)" onclick="deleteSignatoryRow(${formIndex})" title="Delete signatory">
                                                                                    <i class="fal fa-trash btn-danger btn p-2 mt-1" aria-hidden="true"></i>
                                                                                </a>
                                                                            `
                                }
                        </div>
                    </div>`;
            return formRow
        }

        $(document).ready(function() {
            //$('#nationality_id').select2();
            //$('#residence_country_id').select2();
            let directorIndex = "{{ $directorIndex }}";
            let signatoryIndex = "{{ $signatoryIndex }}";


            $('#addDirectorBtn').click(function() {
                directorIndex++;
                $('#no-directors-message').hide();
                let directorRow = addForm(directorIndex, 'directors');
                $('#directorsContainer').append(directorRow);

                // Reinitialize select2 for the newly added select elements
                $(`.select2`).select2();
            });

            $('#addSignatoryBtn').click(function() {
                signatoryIndex++;
                $('#no-signatory-message').hide();
                let signatoryRow = addForm(signatoryIndex, 'signatories');
                $('#SignatoryContainer').append(signatoryRow);

                // Reinitialize select2 for the newly added select elements
                $(`.select2`).select2();
            });
        });

        function deleteDirectorRow(index) {
            $('.director-row-' + index).remove();
        }

        function deleteSignatoryRow(index) {
            $('.signatory-row-' + index).remove();
        }

        function confirmDeletion(entity, index, type) {
            Swal.fire({
                title: `Are you sure?`,
                text: `${entity} will be deleted permanently`,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!"
            }).then(function(result) {
                if (result.value) {
                    markToDelete(index, entity.toLowerCase());
                }
            });
        }

        function markToDelete(index, type) {
            const rowId = `#${type}-wrap-${index}`;
            const deleteInputId = `#delete-${type}-${index}`;
            $(rowId).addClass('deleted-row');
            $(deleteInputId).val(1);

            $('#update-company-contact-form').submit();
        }

        document.addEventListener('DOMContentLoaded', function() {

            $('#update-company-contact-form').on('submit', function(event) {
                event.preventDefault();

                let valid = true;
                const emailInputs = $('.director-email .signatory-email');
                const nameInputs = $('.director-name .signatory-name');

                emailInputs.each(function(index) {
                    const input = $(this);
                    console.log(input);
                    const feedback = $(`#invalid-feedback-email-${index}`);
                    if (!validateEmail(input.val())) {
                        console.log('Invalid Email ' + feedback);
                        input.addClass('is-invalid');

                        feedback.show();
                        valid = false;
                    } else {
                        input.removeClass('is-invalid');
                        feedback.hide();
                    }
                });
                if (valid) {
                    this.submit();
                }
            });

            function validateEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(String(email).toLowerCase());
            }
        });

        // Function to copy IBAN to clipboard
        function copyIBAN(iban) {
            navigator.clipboard.writeText(iban).then(() => {
                Swal.fire({
                    title: "Copied!",
                    text: "IBAN has been copied to your clipboard.",
                    icon: "success",
                    timer: 2000,
                    showConfirmButton: false
                });
            }).catch(() => {
                Swal.fire({
                    title: "Error!",
                    text: "Failed to copy IBAN.",
                    icon: "error",
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        }

        function approveIBAN(account_id) {

            Swal.fire({
                title: "Are you sure?",
                text: "You want to create IBAN for this account.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Want it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approve-iban-' + account_id).submit()
                }
            });
        }

        function showIBANCheckbox(currency_id) {
            if ($('#currency-' + currency_id).is(':checked')) {
                $('#iban-container-' + currency_id).show();
            } else {
                $('#iban-container-' + currency_id).hide();
            }
        }

        function downloadAccountConfirmation(accountId) {
            if (!accountId) {
                Swal.fire('Error', 'Account ID missing', 'error');
                return;
            }

            window.open(`/client-management/clients/account-confirmation-letter/${accountId}`, '_blank');
        }

        function synchIBAN(openpayd_account_id) {
            $('#loading-spinner').show();
            $('#iban-error').html('');
            $.ajax({
                url: '/client-management/clients/synch-iban/' + openpayd_account_id,
                method: 'GET',
                // data: { account_id: openpayd_account_id },
                success: function(response) {
                    if (response.errorCode === "success") {
                        $('#iban-account-number').html(response.data.account_number);
                        $('#iban-iban').html(response.data.iban);
                        $('#iban-bic').html(response.data.bic);
                    } else {
                        $('#iban-error').html(
                            '<div class="alert alert-danger">Oops: No IBAN information  available at the moment "' +
                            response.errorCode + '". </div>');
                    }
                    $('#loading-spinner').hide();
                },
                error: function(xhr, status, error) {
                    // alert("Error syncing IBAN: " + error);
                    $('#iban-error').html(
                        '<div class="alert alert-danger">Oops: Some error occured while synching the account. </div>'
                    );
                    $('#loading-spinner').hide();
                }
            });
        }

        function showIBAN(bankAccount) {

            const provider = bankAccount.provider_name?.toLowerCase();

            if (provider === 'zenus') {
                return renderZenusAccountDetails(bankAccount);
            }

            if (provider === 'lorum') {
                return renderLorumAccountDetails(bankAccount);
            }

            const routingCodes = safeParse(bankAccount.routing_code_entries);

            const routing = routingCodes[0] || {};
            const routingLabel =
                routing.routingCodeKey === "SORT_CODE" ? "Sort Code" : "Routing Number";

            const extra = row(routingLabel, routing.routingCodeValue);

            showAccountDetails(bankAccount, bankAccount, extra);
        }

        // ===============================
        // Helpers (GLOBAL - define once)
        // ===============================
        const isValid = (val) => val !== null && val !== undefined && val !== '' && val !== 'N/A';
        const formatLabel = (key) => key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());

        const safeParse = (json, fallback = []) => {
            try {
                return JSON.parse(json || JSON.stringify(fallback));
            } catch {
                return fallback;
            }
        };

        const row = (label, value, id = null) => {
            if (!isValid(label) || !isValid(value)) return '';
            return `
                <tr>
                    <td>${label}</td>
                    <td>
                        <span style="font-weight: 500;" ${id ? `id="${id.toLowerCase()}"` : ''}>
                            ${value}
                        </span>
                    </td>
                </tr>
            `;
        };

        function renderAccountInfoRows(accountInfo) {
            let rows = '';

            Object.entries(accountInfo).forEach(([key, value]) => {
                if (['date', 'account_opened_date'].includes(key)) return;
                if (key === 'accounts' && Array.isArray(value)) {
                    value.forEach(acc => {
                        // Section header (same as Lorum)
                        rows += `
                            <tr style="background:#f5e4a8;">
                                <td colspan="2">${acc.type || ''}</td>
                            </tr>
                        `;

                        Object.entries(acc).forEach(([k, v]) => {
                            if (k !== 'type' && isValid(v)) {
                                rows += row(formatLabel(k), v);
                            }
                        });
                    });
                } else {
                    rows += row(formatLabel(key), value);
                }
            });
            return rows;
        }

        function renderZenusAccountInfoRows(accountInfo, bankAccount) {
            let rows = '';

            const client = @json($client);
            const client_company = client.company;
            const client_address =
                `${client_company.company_region} ${client_company.company_city} ${client_company.business_reg_street_address}`;
            const zenusMaster = @json($zenus_master_bank_details);

            // define OUTSIDE so it's always available
            const sections = {
                Swift: {
                    main: [],
                    field57: [],
                    field56: []
                },
                "Fed Wire": [],
                ACH: []
            };

            if (Array.isArray(accountInfo.accounts)) {

                accountInfo.accounts.forEach(acc => {
                    const type = acc?.institution_type ?? acc.type;
                    const institutionValue = acc?.institution_value;
                    const vrn = acc.type === "ALIAS" ? acc?.account_number : null;

                    // ---------- SWIFT ----------
                    if (type === "BIC") {
                        sections.Swift.main.push({
                            "Name": client.name,
                            "Address": client_address,
                            "Account Number": bankAccount.account_number,
                        });

                        sections.Swift.field57.push({
                            "BIC/Swift Code": institutionValue,
                            "Bank Name": zenusMaster.bank_name,
                            "Bank Address": zenusMaster.bank_address,
                        });

                        sections.Swift.field56.push({
                            "Intermediary Bank Name": zenusMaster.master_bank_intermediatry_bank_name,
                            "Intermediary Routing Number": zenusMaster
                                .master_bank_intermediatry_routing_number,
                            "Intermediary BIC/Swift Code": zenusMaster.master_bank_intermediatry_bic,
                        });
                    }

                    // ---------- ACH + FEDWIRE ----------
                    else if (vrn) {
                        const base = {
                            "Name": client.name,
                            "Address": client_address,
                            "Virtual Reference Number (VRN)": vrn,
                            "Bank Name": zenusMaster.bank_name,
                            "Bank Address": zenusMaster.bank_address,
                        };

                        sections.ACH.push({
                            ...base,
                            "ABA/Routing Number": zenusMaster?.master_ach_routing_number,
                        });

                        sections["Fed Wire"].push({
                            ...base,
                            "ABA/Routing Number": zenusMaster?.master_fed_wire_routing_number,
                        });
                    }
                });
            }

            // ---------- RENDER ----------
            Object.entries(sections).forEach(([section, data]) => {

                // ---------- SWIFT ----------
                if (section === "Swift") {
                    const {
                        main,
                        field57,
                        field56
                    } = data;

                    if (!main.length && !field57.length && !field56.length) return;

                    rows += `
                        <tr style="background:#f5e4a8;">
                            <td colspan="2">Swift</td>
                        </tr>
                    `;

                    const renderGroup = (title, arr) => {
                        if (!arr.length) return;

                        rows += `
                            <tr>
                                <td colspan="2">
                                    <strong>
                                        Beneficiary Bank ${title !== "Beneficiary" ? `(${title})` : ""}
                                    </strong>
                                </td>
                            </tr>
                        `;

                        arr.forEach(item => {
                            Object.entries(item).forEach(([k, v]) => {
                                if (isValid(v)) rows += row(k, v);
                            });
                        });
                    };

                    renderGroup("Beneficiary", main);
                    renderGroup("Swift Field 57", field57);
                    renderGroup("Swift Field 56", field56);
                }

                // ---------- NORMAL ----------
                else {
                    if (!data.length) return;

                    rows += `
                        <tr style="background:#f5e4a8;">
                            <td colspan="2">${section}</td>
                        </tr>
                    `;

                    data.forEach(item => {
                        Object.entries(item).forEach(([k, v]) => {
                            if (isValid(v)) rows += row(k, v);
                        });
                    });
                }
            });

            // ALWAYS return both safely
            return {
                rows,
                sections
            };
        }


        function normalizeZenusAccountInfo(accountInfo) {
            const parsed = safeParse(accountInfo, []);

            return {
                provider: 'Zenus',
                accounts: parsed.map(acc => ({
                    type: acc?.accountNumber?.type || 'UNKNOWN',
                    account_number: acc?.accountNumber?.value || '-',
                    institution_type: acc?.financialInstitutionId?.type || '-',
                    institution_value: acc?.financialInstitutionId?.value || '-',
                    currency: acc?.defaultCurrencyCode || '-',
                    status: acc?.statusCode || '-',
                    start_time: acc?.validityRange?.startTime || '-',
                    // end_time: acc?.validityRange?.endTime || '-',
                }))
            };
        }

        // ===============================
        // Zenus Parser (CLEAN)
        // ===============================
        function parseZenusAccount(accountInfo) {
            const accountNumbers = safeParse(accountInfo);

            let result = {
                accountNumber: '-',
                BBAN: '-',
                ABA: '-',
                BIC: '-',
            };

            accountNumbers.forEach(acc => {
                const type = acc?.accountNumber?.type;
                const value = acc?.accountNumber?.value;

                var details = '';
                try {
                    const accountInfo = JSON.parse(bankAccount.account_info);
                    var account_details = '';
                    for (const [key, value] of Object.entries(accountInfo)) {
                        if (key === 'date' || key === 'account_opened_date') continue; // skip unwanted fields

                        if (key === 'accounts' && Array.isArray(value)) {
                            value.forEach((acc, index) => {

                                //account_details += `Accounts ${value.length > 1 ? '' : ''}:\n`;

                                account_details += '------------------------------------\n';
                                for (const [accKey, accValue] of Object.entries(acc)) {

                                    if (accValue === null || accValue === undefined || accValue === '')
                                        continue; // skip empty values
                                    account_details +=
                                        `  ${accKey.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())}: ${accValue}\n`;
                                }
                                // account_details += '------------------------------------\n';
                            });
                        } else {
                            const label = key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
                            details += `${label}: ${value}\n`;
                        }
                    }
                    details += account_details;
                } catch (e) {
                    console.error("Failed to parse account_info JSON", e);
                }

                if (type === 'BBAN') {
                    result.BBAN = value;
                    result.accountNumber = value;
                }

                if (type === 'ALIAS' && result.accountNumber === '-') {
                    result.accountNumber = value;
                }

                const finType = acc?.financialInstitutionId?.type;
                const finValue = acc?.financialInstitutionId?.value;

                if (finType === 'ABA_CODE') result.ABA = finValue;
                if (finType === 'BIC') result.BIC = finValue;
            });

            return result;
        }

        // ===============================
        // Zenus Display
        // ===============================
        function renderZenusAccountDetails(bankAccount) {
            const normalized = normalizeZenusAccountInfo(bankAccount.account_info);
            const {
                rows,
                sections
            } = renderZenusAccountInfoRows(normalized, bankAccount);
            const zenus_master_account = @json($zenus_master_bank_details);
            const bank_account_details = {
                ...sections,
                ...bankAccount,
                currency: bankAccount.currency,
                status: bankAccount.status,
            };

            const html = `
                <table class="table table-bordered m-0 mb-4 table-responsive-xxl">
                    <tbody class="text-left">
                        ${row("Bank Account Holder Name", bank_account_details.bank_account_holder_name)}
                        ${row("Currency", bank_account_details.currency)}
                        ${row("Status", bank_account_details.status)}
                        ${row("Provider Name", bank_account_details.provider_name)}
                        ${rows}
                    </tbody>
                </table>
            `;

            Swal.fire({
                title: "Your Account Details",
                html,
                showCancelButton: true,
                confirmButtonText: "Copy details",
                focusConfirm: false
            }).then(res => {
                if (res.value) copyZenusDetails(sections);
            });
        }


        // ===============================
        // Generic Modal Renderer
        // ===============================
        function showAccountDetails(bankAccount, originalAccount, extraRows = '') {
            const html = `
                <table class="table table-bordered m-0 mb-4 table-responsive-xxl">
                    <tbody class="text-left">
                        ${row("Bank Account Holder Name", bankAccount.bank_account_holder_name)}
                        ${row("Bank Name", bankAccount.bank_name ?? bankAccount.provider_name)}
                        ${row("Account Number", bankAccount.account_number, "iban-account-number")}
                        ${row("IBAN", bankAccount.iban, "iban-iban")}
                        ${row("BIC/Swift Code", bankAccount.bic, "iban-bic")}
                        ${row("BBAN", bankAccount.BBAN)}
                        ${row("ABA Code", bankAccount.ABA_CODE)}
                        ${row("Bank Address", bankAccount.bank_address)}
                        ${row("Currency", bankAccount.currency)}
                        ${row("Status", bankAccount.status)}
                        ${row("Provider Name", bankAccount.provider_name)}
                        ${extraRows}
                    </tbody>
                </table>
            `;

            Swal.fire({
                title: "Your Account Details",
                html,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText: "Copy details"
            }).then(res => {
                if (res.value) copyIBANDetails(originalAccount);
            });
        }

        // ===============================
        // Lorum Renderer (CLEANED)
        // ===============================
        function renderLorumAccountDetails(bankAccount) {
            let accountInfo = safeParse(bankAccount.account_info, {});
            let rows = '';

            Object.entries(accountInfo).forEach(([key, value]) => {
                if (['date', 'account_opened_date'].includes(key)) return;

                if (key === 'accounts' && Array.isArray(value)) {
                    value.forEach(acc => {
                        rows +=
                            `<tr style="background:#f5e4a8;"><td colspan="2">${acc.type || ''}</td></tr>`;
                        Object.entries(acc).forEach(([k, v]) => {
                            if (k !== 'type' && isValid(v)) {
                                rows += row(formatLabel(k), v);
                            }
                        });
                    });
                } else {
                    rows += row(formatLabel(key), value);
                }
            });

            rows += `
                <tr>
                    <td>Account Confirmation</td>
                    <td>
                        <button class="btn btn-sm btn-primary"
                            onclick="downloadAccountConfirmation('${bankAccount.virtual_bank_account_id}')">
                            📄 Download Account Confirmation Letter
                        </button>
                    </td>
                </tr>
            `;

            showAccountDetails(bankAccount, bankAccount, rows);
        }

        // ===============================
        // Copy Function (CLEAN)
        // ===============================
        function copyIBANDetails(bankAccount) {

            let details = `
                Bank Account Holder Name: ${bankAccount.bank_account_holder_name || '-'}
                Bank Name: ${bankAccount.bank_name || '-'}
                Currency: ${bankAccount.currency || '-'}
                Bank Address: ${bankAccount.bank_address || '-'}
            `;

            const accountInfo = safeParse(bankAccount.account_info, null);

            // ===============================
            // 🔹 Zenus (ARRAY)
            // ===============================
            if (Array.isArray(accountInfo)) {

                details += `\n--- ZENUS ACCOUNT DETAILS ---\n`;

                accountInfo.forEach((acc, index) => {

                    const accNumber = acc?.accountNumber?.value;
                    const accType = acc?.accountNumber?.type;

                    const finType = acc?.financialInstitutionId?.type;
                    const finValue = acc?.financialInstitutionId?.value;

                    details += `\n[Account ${index + 1}]\n`;

                    if (isValid(accType)) details += `Type: ${accType}\n`;
                    if (isValid(accNumber)) details += `Account Number: ${accNumber}\n`;
                    if (isValid(finType) && isValid(finValue)) {
                        details += `${finType}: ${finValue}\n`;
                    }
                });
            }

            // ===============================
            // 🔹 Lorum (OBJECT)
            // ===============================
            else if (accountInfo?.accounts && Array.isArray(accountInfo.accounts)) {

                details += `\n--- LORUM ACCOUNT DETAILS ---\n`;

                accountInfo.accounts.forEach((acc, index) => {

                    details += `\n[Account ${index + 1}] ${acc.type || ''}\n`;

                    Object.entries(acc).forEach(([key, value]) => {
                        if (key !== 'type' && isValid(value)) {
                            details += `${key.replace(/_/g, ' ').toUpperCase()}: ${value}\n`;
                        }
                    });
                });
            }

            // ===============================
            // 🔹 FALLBACK (IMPORTANT FIX)
            // ===============================
            else {

                details += `\n--- BANK ACCOUNT DETAILS (FALLBACK) ---\n`;

                details += `Account Number: ${bankAccount.account_number || '-'}\n`;
                details += `IBAN: ${bankAccount.iban || '-'}\n`;
                details += `BIC: ${bankAccount.bic || '-'}\n`;
                details += `Currency: ${bankAccount.currency || '-'}\n`;
                details += `Bank Name: ${bankAccount.bank_name || '-'}\n`;
                details += `Bank Address: ${bankAccount.bank_address || '-'}\n`;
            }

            // ===============================
            // 🔹 Copy
            // ===============================
            navigator.clipboard.writeText(details.trim())
                .then(() => Swal.fire("Copied!", "", "success"))
                .catch(() => Swal.fire("Error copying", "", "error"));
        }


        function copyZenusDetails(sections) {

            if (!sections) return;

            let details = `--- ZENUS ACCOUNT DETAILS ---\n`;

            // Helper to format key-values
            const formatItems = (items) => {
                let text = '';
                items.forEach(item => {
                    Object.entries(item).forEach(([k, v]) => {
                        if (isValid(v)) text += `${k}: ${v}\n`;
                    });
                    text += '\n';
                });
                return text;
            };

            // ===============================
            // 🔹 SWIFT
            // ===============================
            if (sections.Swift) {
                const {
                    main,
                    field57,
                    field56
                } = sections.Swift;

                if (main.length || field57.length || field56.length) {
                    details += `\n[SWIFT]\n`;

                    if (main.length) {
                        details += `\nBeneficiary:\n`;
                        details += formatItems(main);
                    }

                    if (field57.length) {
                        details += `\nSwift Field 57:\n`;
                        details += formatItems(field57);
                    }

                    if (field56.length) {
                        details += `\nSwift Field 56:\n`;
                        details += formatItems(field56);
                    }
                }
            }

            // ===============================
            // 🔹 ACH
            // ===============================
            if (sections.ACH?.length) {
                details += `\n[ACH]\n`;
                details += formatItems(sections.ACH);
            }

            // ===============================
            // 🔹 FED WIRE
            // ===============================
            if (sections["Fed Wire"]?.length) {
                details += `\n[FED WIRE]\n`;
                details += formatItems(sections["Fed Wire"]);
            }

            // ===============================
            // 🔹 COPY
            // ===============================
            navigator.clipboard.writeText(details.trim())
                .then(() => {
                    Swal.fire({
                        title: "Copied!",
                        text: "Zenus account details copied.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                })
                .catch(() => {
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to copy Zenus details.",
                        icon: "error",
                        timer: 2000,
                        showConfirmButton: false
                    });
                });
        }

        function viewSumsubLogs() {
            $("#sumsub-log-modal").modal('show');
        }

        function closeSumsubModal() {
            $("#sumsub-log-modal").modal('hide');
        }

        function GetZenusVRN(bankAccount) {
            Swal.fire({
                title: "Getting Zenus Bank Account VRN.",
                text: "This will get the account VRN from Zenus, If VRN is created by zenus.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Get VRN"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('virtual_account_id').setAttribute('value', bankAccount
                        .virtual_bank_account_id);
                    document.getElementById('get-zenus-vrn').submit()
                }
            });
        }

        let selectedAccountProvider = null;

        function accountSettings(accountId, currentProvider = null) {

            // store account
            $("#modal_account_id").val(accountId);

            // store current provider
            selectedAccountProvider = currentProvider;

            // reset UI every time modal opens
            $('.provider-card').removeClass('active');
            $('.provider-radio').prop('checked', false);

            // preselect current provider if exists
            if (currentProvider) {
                changeVirtualProvider(currentProvider);
            }

            $("#virtual-bank-provider-modal").modal("show");
        }
        function openResetQuestionnaireModal(applicantId, levelName) {
            console.log("Opening Modal for Applicant:", applicantId, "Level:", levelName);
            
            // Assign values to the hidden inputs
            $('#reset_applicant_id').val(applicantId);
            $('#reset_level_name').val(levelName);
            $('#reset_reason').val(''); // Clear previous reason
            
            $('#reset-questionnaire-modal').modal('show');
        }

        function submitResetQuestionnaire(e) {
            e.preventDefault();

            let applicantId = $('#reset_applicant_id').val();
            let levelName = $('#reset_level_name').val();
            let reason = $('#reset_reason').val();
            let clientId = "{{ $client->id }}";

            let btn = $('#reset-submit-btn');
            btn.prop('disabled', true);
            btn.html('<i class="fal fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                url: "{{ url('/client-management/clients/request-sumsub-kyc') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    client_id: clientId,
                    applicant_id: applicantId,
                    level_name: levelName,
                    reason: reason
                },
                success: function(data) {
                    if (typeof data === 'string') {
                        data = $.parseJSON(data);
                    }

                    $('#reset-questionnaire-modal').modal('hide');

                    setTimeout(function() {
                        if (data.status === 'error') {
                            Swal.fire("Error!", data.message || "An error occurred.", "error");
                        } else {
                            Swal.fire("Success!", "Update request and email sent successfully.", "success");
                        }
                    }, 400); 
                },
                error: function(xhr) {
                    $('#reset-questionnaire-modal').modal('hide');
                    
                    let errorMsg = "Server error occurred while communicating with Sumsub.";
                    if(xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }

                    setTimeout(function() {
                        Swal.fire("Error!", errorMsg, "error");
                    }, 400);
                },
                complete: function() {
                    btn.prop('disabled', false);
                    btn.html('Send Request');
                }
            });
        }
    </script>
    <style>
        .deleted-row {
            height: 1px;
            display: none;
        }

        fieldset.border.p-2,
        .table-bg-grey {
            background: #f9f9f9;
        }

        .directors-data .select2-container--open {
            width: 200px !important;
            position: relative;
        }

        .directors-data .select2-container--open .select2-dropdown--below {
            width: 200px;
            ;
        }
    </style>
    {{-- @stop --}}

@section('content')

    <div class="subheader">
        <h1 class="subheader-title">
            <span class='fw-300'>Client Management</span>
        </h1>
        <div class="d-flex">
            <a href="{{ url('/client-management/clients') }}" class="btn btn-primary ml-2 mx-md-2"><i
                    class="fal fa-arrow-alt-left"></i> Go
                Back </a>
            @php $user = \Illuminate\Support\Facades\Auth::user(); @endphp
            @can('approve client')
                @if ($user->id != $client->created_by || !$user->manager_id > 0)
                    @if ($client->status == 'Approved')
                        <button onclick="closeClient(); return false;" class="btn btn-danger ml-2 mx-md-2"> Close Account
                        </button>
                    @endif
                @endif
            @endcan
        </div>
    </div>

    <div class="panel" data-panel-lock="false" data-panel-close="false" data-panel-collapsed="false"
        data-panel-color="false" data-panel-locked="false" data-panel-refresh="false" data-panel-reset="false">
        <div class="panel-hdr">
            <h2>
                <div class="w-100 d-flex justify-content-between">
                    <div>
                        Update Client - Company <span
                            class="ml-3 badge {{ $statusBadge[$client->status] }}">{{ $client->status }}</span>
                    </div>
                    <div>{{ $client->name }}

                        @php
                            $aml_risk_results = [];
                        @endphp
                        @if (!empty($client->clientriskrating->risk_score)) {
                            @php
                                $score_data = ['total_risk_score' => $client->clientriskrating->risk_score];
                                $aml_risk_results = App\Modules\Aml\Controllers\AmlRuleController::applyRules(
                                    $score_data,
                                    'Risk Score',
                                );
                            @endphp
                            @if (count($aml_risk_results) > 0)

                                @foreach ($aml_risk_results as $aml)
                                    <span class="badge badge-{{ $aml['badge'] }}"> {{ $aml['rule_name'] }} </span>
                                    <i class="fal fa-question-circle {{ $aml['badge'] }}" data-toggle="tooltip"
                                        data-placement="auto" title=""
                                        data-original-title="{{ $aml['rule_description'] }}"></i>
                                @endforeach

                            @endif
                        @endif
                        @php
                            $companyAppId =
                                $clientKYC->company_applicant_id ?? ($clientKYC->sumsub_applicant_id ?? null);
                        @endphp
                        @if (!empty($companyAppId))
                            <button type="button" class="btn btn-danger btn-sm shadow-sm m-2"
                                onclick="openResetQuestionnaireModal('{{ $companyAppId }}', 'company-questionnaire-only')">
                                <i class="fal fa-undo"></i> Request Info Update
                            </button>
                        @endif
                    </div>
                </div>
            </h2>
        </div>
        <div class="panel-container show">
            <div class="panel-content">
                @include('common.error-message')

                @if (!in_array($client->status, ['Pending', 'Approved']))
                    @if ($user->id == $client->created_by && $user->manager_id > 0)
                        <form name="submit-for-approval" id="submit-for-approval" method="post"
                            action="{{ url('/client-management/clients/submit-for-approval') }}"
                            enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="client_id" value="{{ $client->id }}">
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="alert alert-info">Once you are done with all the details for the client
                                        account definition, you can submit it for manager approval <button
                                            onclick="submitForApproval(); return false;" class="btn btn-info">Submit For
                                            Approval</button></p>
                                </div>
                            </div>
                        </form>
                    @endif
                @endif

                <div id="tabs">
                    <ul class="nav nav-tabs nav-tabs-clean" role="tablist">
                        <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-general" role="tab"
                                aria-selected="true">Company</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#contact-person" role="tab"
                                aria-selected="false">Contact Person</a></li>
                        <li class="nav-item"><a class="nav-link"data-toggle="tab" href="#account-currency" role="tab"
                                aria-selected="false">Accounts</a></li>

                        @if ($currencies->where('is_crypto', true)->count() > 0 && in_array('Crypto', $enabledModules))
                            @can('client digital assets')
                                <li class="nav-item"><a class="nav-link"data-toggle="tab" href="#crypto-account-currency"
                                        role="tab" aria-selected="false">Digital Assets</a></li>
                            @endcan
                        @endif
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#related-files" role="tab"
                                aria-selected="false">Related Files</a></li>
                        @if (count($changes) > 0)
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#changes" role="tab"
                                    aria-selected="false">Changes</a></li>
                        @endif

                        @if ($client->status == 'Approved' && !$client->parent_id && in_array('SubAccounts', $enabledModules))
                            @can('client sub accounts')
                                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#sub-accounts" role="tab"
                                        aria-selected="false">Sub Accounts</a></li>
                            @endcan
                        @endif

                        @if ($client->status == 'Pending' || $clientKYC || $client->status == 'Approved')
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#client-kyc" role="tab"
                                    aria-selected="false">Compliance</a></li>
                        @endif

                        @if ($client->status == 'Pending' || $client->status == 'Approved')
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#client-risk-rating"
                                    role="tab" aria-selected="false">Risk Scoring</a></li>
                        @endif

                        @if (in_array('OnboardingFee', $enabledModules) && $client->status == 'Approved')
                            <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#onboarding-fee"
                                    role="tab" aria-selected="false">Onboarding Fee</a></li>
                        @endif

                    </ul>

                    <div class="tab-content tab-content-client p-3 mt-3">
                        <div class="tab-pane fade active show" id="tab-general" role="tabpanel"
                            aria-labelledby="tab-general">
                            <form method="post" action="{{ url('/client-management/clients/update/company') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Company Name</label>
                                            <input type="text" autofocus id="name" name="name"
                                                value="{{ $client->name }}" class="form-control" required>
                                            @error('name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="registration_date">Date of
                                                Incorporation</label>
                                            <input type="text" class="form-control" name="registration_date"
                                                id="registration_date"
                                                value="{{ $client->company->registration_date ? \Carbon\Carbon::parse($client->company->registration_date)->format('d/m/Y') : '' }}"
                                                placeholder="Select date">
                                            @error('registration_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="expiry_date">License Expiry Date</label>
                                            <input type="text" class="form-control" name="expiry_date"
                                                id="expiry_date"
                                                value="{{ $client->company->expiry_date ? \Carbon\Carbon::parse($client->company->expiry_date)->format('d/m/Y') : '' }}"
                                                placeholder="Select date">
                                            @error('expiry_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="contact_person_naics_code">Industry sector
                                                code</label>
                                            <select name="naics_code" id="naics_code"
                                                data-select2-id="contact_person_naics_code"
                                                class="form-control select2 w-100 select2">
                                                <option value="">Choose industry sector code</option>
                                                @foreach ($naics_codes as $code)
                                                    @php
                                                        $naics_code = '';
                                                        if ($client->company->contact) {
                                                            $naics_code = $client->company->naics_code;
                                                        }
                                                    @endphp
                                                    @if ($code->naics_code == $naics_code)
                                                        <option value="{{ $code->naics_code }}" selected
                                                            data-iso3={{ $code->naics_code }}>
                                                            {{ $code->naics_title }} ({{ $code->naics_code }})
                                                        </option>
                                                    @else
                                                        <option value="{{ $code->naics_code }}"
                                                            data-iso3={{ $code->id }}>
                                                            {{ $code->naics_title }} ({{ $code->naics_code }})
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="invalid-feedback-nationality">Please select
                                                industry sector code.</div>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="tax_number">Tax number</label>
                                            <input type="text" id="tax_number" name="tax_number"
                                                value="{{ $client->company->tax_number }}" class="form-control"required>
                                            @error('tax_number')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="company_place">Place of Registration of
                                                Company</label>
                                            <input type="text" id="company_place" name="company_place"
                                                value="{{ $client->company->company_place }}" class="form-control"
                                                required>
                                            @error('company_place')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="row">

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="business_reg_street_address">Business Address Line 1</label>
                                                <input 
                                                    type="text" 
                                                    id="business_reg_street_address" 
                                                    name="business_reg_street_address"
                                                    placeholder="123 Corporate Blvd or Suite 500"
                                                    value="{{ $client->company->business_reg_street_address }}"
                                                    class="form-control"
                                                    required
                                                >
                                                @error('business_reg_street_address')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="business_street_2_address">Business Address Line 2 (Optional)</label>
                                                <input 
                                                    type="text" 
                                                    id="business_street_2_address" 
                                                    name="business_street_2_address"
                                                    placeholder="Suite, Floor, Building, Unit (optional)"
                                                    value="{{ $client->company->business_street_2_address }}"
                                                    class="form-control"
                                                >
                                                @error('business_street_2_address')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="row">

                                            <div class="form-group col-md-4">
                                                <label class="form-label" for="company_city">City of Company</label>
                                                <input 
                                                    type="text" 
                                                    id="company_city" 
                                                    name="company_city"
                                                    placeholder="e.g. New York"
                                                    value="{{ $client->company->company_city }}"
                                                    class="form-control"
                                                    required
                                                >
                                                @error('company_city')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-4">
                                                <label class="form-label" for="company_region">State/Region</label>
                                                <input 
                                                    type="text" 
                                                    id="company_region" 
                                                    name="company_region"
                                                    placeholder="e.g. NY (2-letter code)"
                                                    value="{{ $client->company->company_region }}"
                                                    class="form-control"
                                                    required
                                                >
                                                @error('company_region')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-4">
                                                <label class="form-label" for="zip_code">ZIP Code</label>
                                                <input 
                                                    type="text" 
                                                    id="zip_code" 
                                                    name="zip_code"
                                                    placeholder="e.g. 10001 or 10001-1234"
                                                    value="{{ $client->company->zip_code }}"
                                                    class="form-control"
                                                    required
                                                >
                                                @error('zip_code')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                        </div>


                                        <label class="form-label">Type of Company</label>
                                        <hr>
                                        @foreach ($companyTypes as $type)
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" @if ($type->id == $client->company->company_type) checked @endif
                                                    class="custom-control-input" id="{{ $type->id }}"
                                                    value="{{ $type->id }}" name="company_type">
                                                <label class="custom-control-label"
                                                    for="{{ $type->id }}">{{ $type->name }}</label>
                                            </div>
                                        @endforeach
                                        @can('update client unit')
                                            <div class="form-group" style="display:none;">
                                                <label class="form-label" for="gender">Client Unit</label><br>

                                                <select name="unit_id" id="unit_id" class="form-control">
                                                    <option value="">Choose Client Unit</option>
                                                    @foreach ($units as $unit)
                                                        @if ($unit->id == $client->unit_id)
                                                            <option selected value="{{ $unit->id }}">{{ $unit->name }}
                                                            </option>
                                                        @else
                                                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                                        @endif
                                                    @endforeach

                                                </select>
                                                @error('unit_id')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                                <small>Only Select for If Client Belongs to Some Organizational Unit </small>
                                            </div>
                                        @endcan
                                    </div>


                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="business_reg_number">Business Registration
                                                Number</label>
                                            <input type="text" id="business_reg_number" name="business_reg_number"
                                                value="{{ $client->company->business_reg_number }}" class="form-control"
                                                required>
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="business_reg_address">Business Registration Address</label>
                                            <input type="text" id="business_reg_address" name="business_reg_address"
                                                value="{{ $client->company->business_reg_address }}" class="form-control"
                                                required>
                                            @error('business_reg_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="nature_of_business">Nature of Business</label>
                                            <select name="nature_of_business" id="nature_of_business"
                                                data-select2-id="nature_of_business" class="form-control select2 w-100"
                                                required>
                                                <option value="">Select Nature of Business</option>
                                                @foreach ($industries as $industry)
                                                    @if ($industry->title == $client->company->nature_of_business)
                                                        )
                                                        <option value="{{ $industry->title }}" selected>
                                                            {{ $industry->title }}</option>
                                                    @else
                                                        <option value="{{ $industry->title }}">{{ $industry->title }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                                <option value="other" @if ($client->company->nature_of_business == 'other') selected @endif>
                                                    Other</option>
                                            </select>

                                            @error('nature_of_business')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="company_country">Choose country of
                                                company</label>
                                            <select name="company_country" id="company_country"
                                                data-select2-id="company_country" class="form-control select2 w-100"
                                                required>
                                                <option value="">Choose country of company</option>
                                                @php
                                                    $company_country = $client->company->company_country;
                                                @endphp
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->id == $company_country)
                                                        <option value="{{ $nationality->id }}" selected>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}">
                                                            {{ $nationality->name }}</option>
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>

                                        @if (!$client->parent_id)
                                            @role('super admin')
                                                <div class="form-group">
                                                    <label class="form-label" for="sub_accounts_prefix">Sub-Account
                                                        Prefix</label>
                                                    <input type="text" id="sub_accounts_prefix" name="sub_accounts_prefix"
                                                        value="{{ $client->sub_accounts_prefix }}" minlength="3"
                                                        maxlength="5" class="form-control">
                                                    @error('sub_accounts_prefix')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                            @endrole

                                            <div class="form-group">
                                                <label class="form-label" for="introducer">Introducer</label>
                                                <input type="text" id="introducer" name="introducer"
                                                    value="{{ $client->introducer }}" class="form-control"
                                                    @if (!empty($client->introducer)) disabled @endif>
                                                @error('introducer')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>

                                            <div class="form-group">
                                                <label for="" class="form-label">Send Emails (below actions will
                                                    be
                                                    done on account approval)</label>
                                                <hr>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="send_welcome_email"
                                                        @if ($client->send_welcome_email) checked @endif
                                                        name="send_welcome_email">
                                                    <label class="custom-control-label" for="send_welcome_email">Welcome
                                                        Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mt-3">
                                                    <input type="checkbox" class="custom-control-input" id="send_ssi"
                                                        @if ($client->send_ssi) checked @endif name="send_ssi">
                                                    <label class="custom-control-label" for="send_ssi">SSI Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox mt-3">
                                                    <input type="checkbox" class="custom-control-input" id="create_user"
                                                        @if ($client->create_user) checked @endif
                                                        name="create_user">
                                                    <label class="custom-control-label" for="create_user">Online Log-in
                                                        Details
                                                        Email</label>
                                                </div>
                                            </div>
                                        @endif

                                        <div class="form-group">
                                            <hr>
                                            <label for="" class="form-label">Maintenance Fee</label>
                                            <div class="custom-control custom-checkbox mt-3">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="charge_maintenance_fee"
                                                    @if ($client->charge_maintenance_fee) checked @endif
                                                    name="charge_maintenance_fee">
                                                <label class="custom-control-label" for="charge_maintenance_fee">Charge
                                                    Maintenance Fee</label>
                                            </div>
                                        </div>
                                    </div>
                                    @role('super admin')
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <hr>
                                                <label for="" class="form-label">Internal Transfers</label>
                                                <div class="custom-control custom-checkbox mt-3">
                                                    <input type="checkbox" class="custom-control-input"
                                                        id="is_b2b_internal_transfer_allowed"
                                                        @if ($client->is_b2b_internal_transfer_allowed) checked @endif
                                                        name="is_b2b_internal_transfer_allowed">
                                                    <label class="custom-control-label"
                                                        for="is_b2b_internal_transfer_allowed">Allow Internal
                                                        Transfers</label><br>
                                                    <small>Allow internal transfers from clients been onboarded via
                                                        B2B-API/Merchant-API to this client.</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endrole
                                </div>
                                @if ($client->status == 'Rejected')
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <div class="alert alert-warning">
                                                <p><i class="fal fa-exclamation-triangle"></i> The client account has been
                                                    rejected by
                                                    "{{ \App\Helpers\Common::getUserNameById($client->rejected_by) }}".
                                                    Please go through below reason, make the required changes and submit it
                                                    again.</p>
                                                <label for="" class="form-label">Reject Reason</label>
                                                <p>{{ $client->reject_reason }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <hr>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                            @include('client-management.partials.approve-reject-button')
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="contact-person" role="tabpanel" aria-labelledby="contact-person">
                            <form method="post" action="{{ url('/client-management/clients/update/company/contact') }}"
                                enctype="multipart/form-data" id="update-company-contact-form">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <input type="hidden" name="company_id" value="{{ $client->company->id }}">
                                <input type="hidden" name="parent_client_id" value="{{ session('parent_client_id') }}">

                                <fieldset class="border p-2">
                                    <legend class="w-auto">Owner info</legend>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="name">Name</label>
                                                <input type="text" id="name" name="name"
                                                    value="{{ $client->company->contact ? $client->company->contact->name : old('name') }}" class="form-control" required>
                                                @error('name')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                            <div class="row">
                                                <div class="form-group col-md-6">
                                                    <label class="form-label" for="designation">Designation</label>
                                                    <input type="text" id="designation" name="designation"
                                                        value="{{ $client->company->contact ? $client->company->contact->designation : old('designation') }}"
                                                        class="form-control" required>
                                                    @error('designation')
                                                        <span class="invalid-feedback"
                                                            role="alert"><strong>{{ $message }}</strong></span>
                                                    @enderror
                                                </div>
                                                <div class="form-group col-md-6">
                                                    <label class="form-label"
                                                        for="contact_person_occupation_code">Occupation code</label>
                                                    <select name="soc_code" id="soc_code"
                                                        data-select2-id="contact_person_occupation_code"
                                                        class="form-control select2 w-100 select2">
                                                        <option value="">Choose occupation code</option>
                                                        @foreach ($soc_codes as $code)
                                                            @php
                                                                $soc_code = '';
                                                                if ($client->company->contact) {
                                                                    $soc_code = $client->company->contact->soc_code;
                                                                }
                                                            @endphp
                                                            @if ($code->soc_code == $soc_code)
                                                                <option value="{{ $code->soc_code }}" selected
                                                                    data-iso3={{ $code->soc_code }}>
                                                                    {{ $code->job_title }} ({{ $code->soc_code }})
                                                                </option>
                                                            @else
                                                                <option value="{{ $code->soc_code }}"
                                                                    data-iso3={{ $code->id }}>
                                                                    {{ $code->job_title }} ({{ $code->soc_code }})
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                    <div class="invalid-feedback" id="invalid-feedback-nationality">Please
                                                        select industry sector code.</div>
                                                </div>
                                            </div>

                                            <div class="row">

                                                <div class="form-group col-md-6">
                                                    <label class="form-label" for="street_address">Contact Person Address Line 1</label>
                                                    <input 
                                                        type="text" 
                                                        id="street_address" 
                                                        name="street_address"
                                                        placeholder="123 Main Street"
                                                        value="{{ $client->company->contact ? $client->company->contact->street_address : old('street_address') }}"
                                                        class="form-control"
                                                        required
                                                    >
                                                    @error('street_address')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="form-group col-md-6">
                                                    <label class="form-label" for="street_2_address">Contact Person Address Line 2 (Optional)</label>
                                                    <input 
                                                        type="text" 
                                                        id="street_2_address" 
                                                        name="street_2_address"
                                                        placeholder="Apt, Suite, Unit, Floor (optional)"
                                                        value="{{ $client->company->contact ? $client->company->contact->street_2_address : old('street_2_address') }}"
                                                        class="form-control"
                                                    >
                                                    @error('street_2_address')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label class="form-label" for="email_address">Email Address</label>
                                                <input type="email" id="email_address" name="email_address"
                                                    value="{{ $client->company->contact ? $client->company->contact->email_address : old('email_address') }}"
                                                    class="form-control" required>
                                                @error('email_address')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                            <div class="form-group">
                                                <label class="form-label" for="mobile_number">Mobile Number</label>
                                                <div class="row">
                                                    @include(
                                                        'client-management.partials.contact-number.edit',
                                                        [
                                                            'client' => $client,
                                                            'nationalities' => $nationalities,
                                                            'isIndividual' => false,
                                                        ]
                                                    )
                                                </div>
                                            </div>

                                            <div class="row">

                                                <div class="form-group col-md-4">
                                                    <label class="form-label" for="contact_city">Contact Person City</label>
                                                    <input 
                                                        type="text" 
                                                        id="contact_city" 
                                                        name="contact_city"
                                                        placeholder="e.g. New York"
                                                        value="{{ $client->company->contact ? $client->company->contact->contact_city : old('contact_city') }}"
                                                        class="form-control"
                                                        required
                                                    >
                                                    @error('contact_city')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="form-group col-md-4">
                                                    <label class="form-label" for="state_region">State/Region</label>
                                                    <input 
                                                        type="text" 
                                                        id="state_region" 
                                                        name="state_region"
                                                        placeholder="e.g. CA (2-letter state code)"
                                                        value="{{ $client->company->contact ? $client->company->contact->state_region : old('state_region') }}"
                                                        class="form-control"
                                                        required
                                                    >
                                                    @error('state_region')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>

                                                <div class="form-group col-md-4">
                                                    <label class="form-label" for="zip_code">ZIP Code</label>
                                                    <input 
                                                        type="text" 
                                                        id="zip_code" 
                                                        name="zip_code"
                                                        placeholder="e.g. 90210 or 90210-1234"
                                                        value="{{ $client->company->contact ? $client->company->contact->zip_code : old('zip_code') }}"
                                                        class="form-control"
                                                        required
                                                    >
                                                    @error('zip_code')
                                                        <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-md-4">
                                            <label class="form-label" for="passport-number">Passport No</label>
                                            <input type="text" id="passport_number" name="passport_number"
                                                value="{{ $client->company->contact ? $client->company->contact->passport_number : old('passport_number') }}"
                                                class="form-control" required>
                                            @error('passport_no')
                                                <span class="invalid-feedback-passport-number"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="email_address">Passport Expiry</label>

                                            <input type="text" id="passport_expiry" name="passport_expiry"
                                                value="{{ $client->company->contact ? \Carbon\Carbon::parse($client->company->contact->passport_expiry)->format('d/m/Y') : '' }}"
                                                class="form-control" required autocomplete="false">
                                            @error('passport_expiry')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="dob">Date of birth</label>
                                            <input type="text" id="dob" name="dob"
                                                value="{{ $client->company->contact ? \Carbon\Carbon::parse($client->company->contact->dob)->format('d/m/Y') : '' }}"
                                                class="form-control" required autocomplete="false">
                                            @error('dob')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row mt-4">

                                        <div class="col-md-4">
                                            <label class="form-label" for="nationality">Nationality</label>


                                            <select name="nationality_id" id="nationality_id"
                                                data-select2-id="contact_person_nationality_id"
                                                class="form-control select2 w-100 select2">
                                                <option value="">Choose nationality</option>
                                                @foreach ($nationalities as $nationality)
                                                    @php
                                                        $contact_nationality = '';
                                                        if ($client->company->contact) {
                                                            $contact_nationality =
                                                                $client->company->contact->nationality_id;
                                                        }
                                                    @endphp
                                                    @if ($nationality->id == $contact_nationality)
                                                        <option value="{{ $nationality->id }}" selected
                                                            data-iso3={{ $nationality->id }}>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}"
                                                            data-iso3={{ $nationality->id }}>
                                                            {{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            <div class="invalid-feedback" id="invalid-feedback-nationality">Please Select
                                                Nationality.</div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label class="form-label" for="nationality_id">Country of
                                                    Residence</label>

                                                <select name="residence_country_id" id="residence_country_id"
                                                    data-select2-id="contact_person_residence_country_id"
                                                    class="form-control select2 w-100" required>
                                                    <option value="">Choose country of residence</option>
                                                    @php
                                                        $residence_country_id = '';
                                                        if ($client->company->contact) {
                                                            $residence_country_id =
                                                                $client->company->contact->residence_country_id;
                                                        }
                                                    @endphp
                                                    @foreach ($nationalities as $nationality)
                                                        @if ($nationality->id == $residence_country_id)
                                                            <option value="{{ $nationality->id }}" selected>
                                                                {{ $nationality->name }}</option>
                                                        @else
                                                            <option value="{{ $nationality->id }}">
                                                                {{ $nationality->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>

                                                <span class="error-input" role="alert"><strong></strong></span>

                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="gender">Gender</label>
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <div class="custom-control custom-radio custom-control-inline mb-3">

                                                        <input type="radio" class="custom-control-input" id="male"
                                                            name="gender" value="male"
                                                            @if ($client->company->contact) @if (strtolower($client->company->contact->gender) == 'male') checked @endif
                                                            @endif>
                                                        <label class="custom-control-label" for="male">Male</label>
                                                    </div>
                                                    <div class="custom-control custom-radio custom-control-inline mb-3">
                                                        <input type="radio" class="custom-control-input" id="female"
                                                            name="gender"
                                                            @if ($client->company->contact) @if (strtolower($client->company->contact->gender) == 'female') checked @endif
                                                            @endif value="female">
                                                        <label class="custom-control-label" for="female">Female</label>
                                                        <div class="invalid-feedback" id="invalid-feedback-gender">Please
                                                            select gender.</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </fieldset>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mt-5 directors-data">
                                            <fieldset class="border p-2">
                                                <legend class="w-auto">Client Directors / UBO's</legend>

                                                <div id="directorsContainer">

                                                    <div class="form-group row mb-2">
                                                        <div class="col-md-2">
                                                            <label class="form-label" for="director-name">Full
                                                                Name</label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label" for="director-email">Email</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="director-passport-number">Passport No</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="director-passport-expiry">Passport Expiry</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label" for="director-dob">Date of
                                                                Birth</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label">Gender</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="nationality_id">Nationality</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="residence_country_id">Residence</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label" for="client_roles">Director
                                                                Access</label>
                                                        </div>
                                                        <div class="col-md-1">

                                                        </div>
                                                    </div>
                                                    @if (!empty($directors) && count($directors) > 0)


                                                        @foreach ($directors as $director)
                                                            @if (!$director->is_owner)
                                                                <div class="form-group row director-row-{{ $directorIndex }} mb-4"
                                                                    id="director-wrap-{{ $directorIndex }}"
                                                                    data-index="{{ $directorIndex }}">
                                                                    <div class="col-md-2">
                                                                        <input type="hidden" class="form-control"
                                                                            name="directors[{{ $directorIndex }}][id]"
                                                                            value="{{ $director->id }}">
                                                                        <input type="hidden" class="form-control"
                                                                            name="directors[{{ $directorIndex }}][delete]"
                                                                            id="delete-directors-{{ $directorIndex }}"
                                                                            value="0">
                                                                        <input type="text"
                                                                            class="form-control director-name"
                                                                            name="directors[{{ $directorIndex }}][name]"
                                                                            value="{{ $director->name }}"
                                                                            placeholder="Full Name">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-name-{{ $directorIndex }}">
                                                                            Please enter a name.</div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <input type="email"
                                                                            class="form-control director-email"
                                                                            name="directors[{{ $directorIndex }}][email]"
                                                                            value="{{ $director->email }}"
                                                                            placeholder="Email" required>
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-email-{{ $directorIndex }}">
                                                                            Please enter a valid email address.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <input type="text"
                                                                            class="form-control director-passport-number"
                                                                            name="directors[{{ $directorIndex }}][passport_number]"
                                                                            value="{{ $director->passport_number }}"
                                                                            placeholder="Passport No">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-passport-number-{{ $directorIndex }}">
                                                                            Please enter a valid Passport Number.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <input type="text"
                                                                            class="form-control director-passport-expiry"
                                                                            name="directors[{{ $directorIndex }}][passport_expiry]"
                                                                            value="@if (!empty($director->passport_expiry)) {{ \Carbon\Carbon::createFromFormat('Y-m-d', $director->passport_expiry)->format('d/m/Y') }} @endif"
                                                                            placeholder="Passport Expiry">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-passport-expiry-{{ $directorIndex }}">
                                                                            Please enter a valid Passport Expiry.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <input type="text"
                                                                            class="form-control director-dob"
                                                                            name="directors[{{ $directorIndex }}][dob]"
                                                                            value="@if (!empty($director->dob)) {{ \Carbon\Carbon::createFromFormat('Y-m-d', $director->dob)->format('d/m/Y') }} @endif"
                                                                            placeholder="Date of birth">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-dob-{{ $directorIndex }}">
                                                                            Please enter Date of birth.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <div
                                                                            class="custom-control custom-radio custom-control-inline mb-3">
                                                                            <input type="radio"
                                                                                class="custom-control-input"
                                                                                id="male-directors-{{ $directorIndex }}"
                                                                                name="directors[{{ $directorIndex }}][gender]"
                                                                                value="male"
                                                                                @if (strtolower($director->gender) == 'male') checked @endif>

                                                                            <label class="custom-control-label"
                                                                                for="male-directors-{{ $directorIndex }}">Male</label>
                                                                        </div>
                                                                        <div
                                                                            class="custom-control custom-radio custom-control-inline mb-3">
                                                                            <input type="radio"
                                                                                class="custom-control-input"
                                                                                id="female-directors-{{ $directorIndex }}"
                                                                                name="directors[{{ $directorIndex }}][gender]"
                                                                                @if (strtolower($director->gender) == 'female') checked @endif
                                                                                value="female">
                                                                            <label class="custom-control-label"
                                                                                for="female-directors-{{ $directorIndex }}">Female</label>
                                                                            <div class="invalid-feedback"
                                                                                id="invalid-feedback-gender-{{ $directorIndex }}">
                                                                                Please select gender.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-1">

                                                                        <select
                                                                            name="directors[{{ $directorIndex }}][nationality_id]"
                                                                            id="directors-nationality_id-{{ $directorIndex }}"
                                                                            data-select2-id="nationality_id-{{ $directorIndex }}"
                                                                            class="form-control select2 w-100 select2">
                                                                            <option value="">Choose nationality
                                                                            </option>
                                                                            @foreach ($nationalities as $nationality)
                                                                                @if ($nationality->id == $director->nationality_id)
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        selected
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}</option>
                                                                                @else
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-dob-{{ $directorIndex }}">
                                                                            Please Select Select Nationality.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <select
                                                                            name="directors[{{ $directorIndex }}][residence_country_id]"
                                                                            id="directors-residence_country_id-{{ $directorIndex }}"
                                                                            data-select2-id="residence_country_id-{{ $directorIndex }}"
                                                                            class="form-control select2 w-100">
                                                                            <option value="">Choose Residence
                                                                            </option>
                                                                            @foreach ($nationalities as $nationality)
                                                                                @if ($nationality->id == $director->residence_country_id)
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        selected
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}</option>
                                                                                @else
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-dob-{{ $directorIndex }}">
                                                                            Please Select Nationality.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        @role('super admin')
                                                                            <select
                                                                                name="directors[{{ $directorIndex }}][role]"
                                                                                id="directors-role-{{ $directorIndex }}"
                                                                                data-select2-id="directors-role-{{ $directorIndex }}"
                                                                                class="form-control select2 w-100 select2">
                                                                                <option value="">Select access</option>
                                                                                @foreach (['Full access', 'View access'] as $role)
                                                                                    @if (!empty($director->user_id))
                                                                                        @if ($role == 'Full access' && $director->user->hasRole('client'))
                                                                                            <option
                                                                                                value="{{ $role }}"
                                                                                                selected>{{ $role }}
                                                                                            </option>
                                                                                        @elseif($role == 'View access' && $director->user->hasRole('basic client'))
                                                                                            <option
                                                                                                value="{{ $role }}"
                                                                                                selected>{{ $role }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value="{{ $role }}">
                                                                                                {{ $role }}</option>
                                                                                        @endif
                                                                                    @else
                                                                                        <option value="{{ $role }}">
                                                                                            {{ $role }}</option>
                                                                                    @endif
                                                                                @endforeach
                                                                            </select>
                                                                        @endrole

                                                                        @unlessrole('super admin')
                                                                            @if (!empty($director->user_id))
                                                                                <input type="text" class="form-control"
                                                                                    value="{{ $director->user->roles->first()->name === 'client' ? 'Full access' : ($director->user->roles->first()->name === 'basic client' ? 'View access' : 'No access') }}"
                                                                                    readonly>
                                                                            @else
                                                                                <input type="text" class="form-control"
                                                                                    value="No access" readonly>
                                                                            @endif
                                                                        @endunlessrole


                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-nationality-{{ $directorIndex }}">
                                                                            Please choose nationality.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <div class="custom-control custom-checkbox">
                                                                            <input type="checkbox"
                                                                                class="custom-control-input"
                                                                                id="directors[{{ $directorIndex }}][create-user]"
                                                                                name="directors[{{ $directorIndex }}][create-user]"
                                                                                @if ($director->user_id) checked @endif>
                                                                            <label class="custom-control-label"
                                                                                for="directors[{{ $directorIndex }}][create-user]">
                                                                                Create login access
                                                                            </label>
                                                                        </div>

                                                                        @can('delete records')
                                                                            <a href="javascript:void(0)"
                                                                                onclick="confirmDeletion('Directors',{{ $directorIndex }})"
                                                                                title="Delete director">
                                                                                <i class="fal fa-trash btn-danger btn p-2 mt-1"
                                                                                    aria-hidden="true"></i>
                                                                            </a>
                                                                        @endcan
                                                                    </div>
                                                                </div>
                                                                @php
                                                                    $directorIndex++;
                                                                @endphp
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <div class="alert alert-warning" id="no-directors-message"> No
                                                            Directors found.</div>
                                                    @endif



                                                </div>

                                                <button type="button" class="btn btn-primary mb-3" id="addDirectorBtn">
                                                    <i class="fal fa-plus"></i> Add Director
                                                </button>
                                            </fieldset>
                                        </div>
                                        {{-- <div class="form-group">
                                            <hr>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                            @include('client-management.partials.approve-reject-button')
                                        </div> --}}
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mt-5 directors-data">
                                            <fieldset class="border p-2">
                                                <legend class="w-auto">Authorized Signatories</legend>

                                                <div id="SignatoryContainer">

                                                    <div class="form-group row mb-2">
                                                        <div class="col-md-2">
                                                            <label class="form-label" for="signatory-name">Full
                                                                Name</label>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <label class="form-label" for="signatory-email">Email</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="signatory-passport-number">Passport No</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="signatory-passport-expiry">Passport Expiry</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label" for="signatory-dob">Date of
                                                                Birth</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label">Gender</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="nationality_id">Nationality</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label"
                                                                for="residence_country_id">Residence</label>
                                                        </div>
                                                        <div class="col-md-1">
                                                            <label class="form-label" for="client_roles">Signatory
                                                                access</label>
                                                        </div>
                                                        <div class="col-md-1">

                                                        </div>
                                                    </div>
                                                    @if (!empty($client_signatories) && count($client_signatories) > 0)


                                                        @foreach ($client_signatories as $signatories)
                                                            @if (!$signatories->is_owner)
                                                                <div class="form-group row signatory-row-{{ $signatoryIndex }} mb-4"
                                                                    id="signatory-wrap-{{ $signatoryIndex }}"
                                                                    data-index="{{ $signatoryIndex }}">
                                                                    <div class="col-md-2">
                                                                        <input type="hidden" class="form-control"
                                                                            name="signatories[{{ $signatoryIndex }}][id]"
                                                                            value="{{ $signatories->id }}">
                                                                        <input type="hidden" class="form-control"
                                                                            name="signatories[{{ $signatoryIndex }}][delete]"
                                                                            id="delete-signatories-{{ $signatoryIndex }}"
                                                                            value="0">
                                                                        <input type="text"
                                                                            class="form-control signatory-name"
                                                                            name="signatories[{{ $signatoryIndex }}][name]"
                                                                            value="{{ $signatories->name }}"
                                                                            placeholder="Full Name">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-name-{{ $signatoryIndex }}">
                                                                            Please enter a name.</div>
                                                                    </div>
                                                                    <div class="col-md-2">
                                                                        <input type="email"
                                                                            class="form-control signatory-email"
                                                                            name="signatories[{{ $signatoryIndex }}][email]"
                                                                            value="{{ $signatories->email }}"
                                                                            placeholder="Email" required>
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-email-{{ $signatoryIndex }}">
                                                                            Please enter a valid email address.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <input type="text"
                                                                            class="form-control signatory-passport-number"
                                                                            name="signatories[{{ $signatoryIndex }}][passport_number]"
                                                                            value="{{ $signatories->passport_number }}"
                                                                            placeholder="Passport No">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-passport-number-{{ $signatoryIndex }}">
                                                                            Please enter a valid Passport Number.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <input type="text"
                                                                            class="form-control signatory-passport-expiry"
                                                                            name="signatories[{{ $signatoryIndex }}][passport_expiry]"
                                                                            value="@if (!empty($signatories->passport_expiry)) {{ \Carbon\Carbon::createFromFormat('Y-m-d', $signatories->passport_expiry)->format('d/m/Y') }} @endif"
                                                                            placeholder="Passport Expiry">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-passport-expiry-{{ $signatoryIndex }}">
                                                                            Please enter a valid Passport Expiry.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <input type="text"
                                                                            class="form-control signatory-dob"
                                                                            name="signatories[{{ $signatoryIndex }}][dob]"
                                                                            value="@if (!empty($signatories->dob)) {{ \Carbon\Carbon::createFromFormat('Y-m-d', $signatories->dob)->format('d/m/Y') }} @endif"
                                                                            placeholder="Date of birth">
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-dob-{{ $signatoryIndex }}">
                                                                            Please enter Date of birth.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <div
                                                                            class="custom-control custom-radio custom-control-inline mb-3">
                                                                            <input type="radio"
                                                                                class="custom-control-input"
                                                                                id="male-signatory{{ $signatoryIndex }}"
                                                                                name="signatories[{{ $signatoryIndex }}][gender]"
                                                                                value="male"
                                                                                @if (strtolower($signatories->gender) == 'male') checked @endif>

                                                                            <label class="custom-control-label"
                                                                                for="male-signatory{{ $signatoryIndex }}">Male</label>
                                                                        </div>
                                                                        <div
                                                                            class="custom-control custom-radio custom-control-inline mb-3">
                                                                            <input type="radio"
                                                                                class="custom-control-input"
                                                                                id="female-signatory{{ $signatoryIndex }}"
                                                                                name="signatories[{{ $signatoryIndex }}][gender]"
                                                                                @if (strtolower($signatories->gender) == 'female') checked @endif
                                                                                value="female">
                                                                            <label class="custom-control-label"
                                                                                for="female-signatory{{ $signatoryIndex }}">Female</label>
                                                                            <div class="invalid-feedback"
                                                                                id="invalid-feedback-gender-{{ $signatoryIndex }}">
                                                                                Please select gender.</div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="col-md-1">

                                                                        <select
                                                                            name="signatories[{{ $signatoryIndex }}][nationality_id]"
                                                                            id="signatories-nationality_id-{{ $signatoryIndex }}"
                                                                            data-select2-id="nationality_id-{{ $signatoryIndex }}"
                                                                            class="form-control select2 w-100 select2">
                                                                            <option value="">Choose nationality
                                                                            </option>
                                                                            @foreach ($nationalities as $nationality)
                                                                                @if ($nationality->id == $signatories->nationality_id)
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        selected
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}</option>
                                                                                @else
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-dob-{{ $signatoryIndex }}">
                                                                            Please Select Select Nationality.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <select
                                                                            name="signatories[{{ $signatoryIndex }}][residence_country_id]"
                                                                            id="signatories-residence_country_id-{{ $signatoryIndex }}"
                                                                            data-select2-id="residence_country_id-{{ $signatoryIndex }}"
                                                                            class="form-control select2 w-100 select2">
                                                                            <option value="">Choose Residence
                                                                            </option>
                                                                            @foreach ($nationalities as $nationality)
                                                                                @if ($nationality->id == $signatories->residence_country_id)
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        selected
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}</option>
                                                                                @else
                                                                                    <option
                                                                                        value="{{ $nationality->id }}"
                                                                                        data-iso3={{ $nationality->id }}>
                                                                                        {{ $nationality->name }}
                                                                                    </option>
                                                                                @endif
                                                                            @endforeach
                                                                        </select>
                                                                        <div class="invalid-feedback"
                                                                            id="invalid-feedback-dob-{{ $signatoryIndex }}">
                                                                            Please Select Nationality.</div>
                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        @role('super admin')
                                                                            <select
                                                                                name="signatories[{{ $signatoryIndex }}][role]"
                                                                                id="signatories-role-{{ $signatoryIndex }}"
                                                                                data-select2-id="signatories-role-{{ $signatoryIndex }}"
                                                                                class="form-control select2 w-100 select2">
                                                                                <option value="">Select access</option>
                                                                                @foreach (['Full access', 'View access'] as $role)
                                                                                    @if (!empty($signatories->user_id))
                                                                                        @if ($role == 'Full access' && $signatories->user->hasRole('client'))
                                                                                            <option
                                                                                                value="{{ $role }}"
                                                                                                selected>{{ $role }}
                                                                                            </option>
                                                                                        @elseif($role == 'View access' && $signatories->user->hasRole('basic client'))
                                                                                            <option
                                                                                                value="{{ $role }}"
                                                                                                selected>{{ $role }}
                                                                                            </option>
                                                                                        @else
                                                                                            <option
                                                                                                value="{{ $role }}">
                                                                                                {{ $role }}</option>
                                                                                        @endif
                                                                                    @else
                                                                                        <option value="{{ $role }}">
                                                                                            {{ $role }}</option>
                                                                                    @endif
                                                                                @endforeach
                                                                            </select>
                                                                            <div class="invalid-feedback"
                                                                                id="invalid-feedback-nationality-{{ $signatoryIndex }}">
                                                                                Please choose nationality.</div>
                                                                        @endrole

                                                                        @if (!empty($signatories->user_id))
                                                                            @unlessrole('super admin')
                                                                                @if (!empty($signatories->user_id))
                                                                                    <input type="text" class="form-control"
                                                                                        value="{{ $signatories->user->roles->first()->name === 'client' ? 'Full access' : ($signatories->user->roles->first()->name === 'basic client' ? 'View access' : 'No access') }}"
                                                                                        readonly>
                                                                                @else
                                                                                    <input type="text" class="form-control"
                                                                                        value="No access" readonly>
                                                                                @endif
                                                                            @endunlessrole
                                                                        @endif

                                                                    </div>
                                                                    <div class="col-md-1">
                                                                        <div class="custom-control custom-checkbox">
                                                                            <input type="checkbox"
                                                                                class="custom-control-input"
                                                                                id="signatories[{{ $signatoryIndex }}][create-user]"
                                                                                name="signatories[{{ $signatoryIndex }}][create-user]"
                                                                                @if ($signatories->user_id) checked @endif>
                                                                            <label class="custom-control-label"
                                                                                for="signatories[{{ $signatoryIndex }}][create-user]">
                                                                                Create login access
                                                                            </label>
                                                                            @can('delete records')
                                                                                <a href="javascript:void(0)"
                                                                                    onclick="confirmDeletion('Signatories',{{ $signatoryIndex }})"
                                                                                    title="Delete signatory">
                                                                                    <i class="fal fa-trash btn-danger btn p-2 mt-1"
                                                                                        aria-hidden="true"></i>
                                                                                </a>
                                                                            @endcan
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                @php
                                                                    $signatoryIndex++;
                                                                @endphp
                                                            @endif
                                                        @endforeach
                                                    @else
                                                        <div class="alert alert-warning" id="no-signatory-message"> No
                                                            authorized signatory found.</div>
                                                    @endif



                                                </div>

                                                <button type="button" class="btn btn-primary mb-3" id="addSignatoryBtn">
                                                    <i class="fal fa-plus"></i> Add Authorized Signatory
                                                </button>
                                            </fieldset>
                                        </div>
                                        <div class="form-group">
                                            <hr>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                            @include('client-management.partials.approve-reject-button')
                                        </div>
                                    </div>
                                </div>
                            </form>


                        </div>
                        <div class="tab-pane fade" id="account-currency" role="tabpanel"
                            aria-labelledby="account-currency">
                            @php
                                $cryptoAccounts = [];
                                $cryptoCurrencies = [];
                                $cryptoDraftAccounts = [];
                            @endphp
                            @if ($isDraft && $client->draftCurrencies->count() > 0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Requested Accounts</h5>
                                        <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                                            <thead class="thead-themed ">
                                                <tr>
                                                    <th width="30">S.No</th>
                                                    <th>Currency</th>
                                                    <th>Code</th>
                                                    <th width="30" align="center"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($client->draftCurrencies as $account)
                                                    @if (!$account->currency->is_crypto)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>
                                                                @if ($account->currency->logo)
                                                                    <img src="{{ $account->currency->logo }}"
                                                                        alt="{{ $account->currency->name }}"
                                                                        height="17px" />&nbsp;
                                                                @endif
                                                                {{ $account->currency->name }}
                                                            </td>
                                                            <td nowrap="nowrap">{{ $account->currency->short }}</td>
                                                            <td>
                                                                <a
                                                                    href="javascript:deleteDraftAccount({{ $account->id }})">
                                                                    <i
                                                                        class="alert-danger fal fa-times-circle rounded-circle"></i>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @else
                                                        @php $cryptoDraftAccounts[] = $account; @endphp
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            @if ($client->company->accounts->count() > 0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Existing Accounts</h5>
                                        <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                                            <thead class="thead-themed ">
                                                <tr>
                                                    <th width="30">S.No</th>
                                                    <th>Account title</th>
                                                    <th>Account Number</th>
                                                    @if ($client->status == 'Approved')
                                                        <th>Virtual Accounts/IBAN</th>
                                                    @endif
                                                    <th>Currency</th>
                                                    <th>Account Balance</th>
                                                    <th>Maintenance Fee</th>
                                                    <th>Is Active?</th>
                                                    <th width="30" align="center"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($client->company->accounts as $account)
                                                    @if (!$account->currency->is_crypto)
                                                        <tr>
                                                            <td>{{ $loop->iteration }}</td>
                                                            <td>{{ $account->title }}</td>
                                                            <td>{{ $account->account_number }}</td>

                                                            {{-- Creating IBAN --}}
                                                            @if ($client->status == 'Approved')
                                                                <td>
                                                                    @php
                                                                        $account_currency = $account->currency;
                                                                        $is_vrn_exists = null;
                                                                        
                                                                        // Resolve provider (account override takes priority)
                                                                        $override_provider = $account->virtual_bank_provider ?? $account_currency->virtual_iban_provider;

                                                                        // Use override only if enabled
                                                                        $currency_virtual_provider = ($override_provider && ($enabled_provider_map[$override_provider] ?? false))
                                                                            ? $override_provider
                                                                            : $account_currency->virtual_iban_provider;
                                                                        
                                                                        $is_provider_enabled = $enabled_provider_map[$currency_virtual_provider] ?? false;

                                                                        $is_virtual_bank_enabled =
                                                                            is_array(
                                                                                $virtual_banks_allowed_currencies,
                                                                            ) &&
                                                                            in_array(
                                                                                $account_currency->short,
                                                                                $virtual_banks_allowed_currencies,
                                                                            ) &&
                                                                            $is_provider_enabled;

                                                                        $virtual_bank_account = $account?->bankAccount;

                                                                        // if zenus account then check the VRN exists in account_info
                                                                        if (
                                                                            !empty($virtual_bank_account) &&
                                                                            $virtual_bank_account->provider_name ===
                                                                                'zenus'
                                                                        ) {
                                                                            $zenus_account_info = json_decode(
                                                                                $virtual_bank_account->account_info,
                                                                            );
                                                                            $is_vrn_exists = collect(
                                                                                $zenus_account_info,
                                                                            )->firstWhere(
                                                                                'accountNumberSubtype',
                                                                                'VRN',
                                                                            );
                                                                        }
                                                                    @endphp

                                                                    @if ($is_virtual_bank_enabled)
                                                                        @if (!empty($virtual_bank_account))
                                                                            <div onclick="showIBAN({{ $virtual_bank_account }})"
                                                                                class="btn btn-sm btn-outline-info waves-effect waves-themed">
                                                                                <i class="fal fa-list-alt mr-1"></i>
                                                                                Account Details
                                                                            </div>
                                                                        @else
                                                                            <span id="iban-text">
                                                                                @if (!empty($account->linkedClient) && $account->linkedClient->kyc_status === "PENDING" && $account->linkedClient->provider_name === "lorum")
                                                                                    <button href="javascript:void(0)"
                                                                                        onclick="createIBAN({{ $account->id }}, '{{ $currency_virtual_provider }}')"
                                                                                        disabled="true"
                                                                                        class="btn btn-secondary">
                                                                                        <span
                                                                                            class="fal fa-wallet mr-1"></span>
                                                                                        Create Account
                                                                                    </button>
                                                                                    <br><small
                                                                                        class="text-muted text-warning">Waiting
                                                                                        for client onboarding to
                                                                                        complete</small>
                                                                                @else
                                                                                    <button href="javascript:void(0)"
                                                                                        onclick="createIBAN({{ $account->id }}, '{{ $currency_virtual_provider }}')"
                                                                                        class="btn btn-secondary">
                                                                                        <span
                                                                                            class="fal fa-wallet mr-1"></span>
                                                                                        Create Account
                                                                                    </button>
                                                                                @endif
                                                                            </span>
                                                                        @endif
                                                                            @can('approve account')
                                                                                -
                                                                                <span 
                                                                                    href="javascript:void(0)"
                                                                                    onclick="accountSettings({{ $account->id }}, '{{ strtolower($currency_virtual_provider ?? '') }}')"
                                                                                >
                                                                                    <span class="fal fa-cog mr-1"></span>
                                                                                </span>
                                                                            @endcan
                                                                          
                                                                            @if(!empty($virtual_bank_account) && $virtual_bank_account->provider_name)
                                                                                - <span class="badge badge-success">{{ $virtual_bank_account->provider_name }}</span>
                                                                                @if(strtolower($virtual_bank_account->provider_name) === 'zenus' && ($virtual_bank_account->status ?? '') === 'BLOCKED')
                                                                                        <span class="badge badge-danger">{{ $virtual_bank_account->status }}</span>
                                                                                @endif
                                                                            @else
                                                                                - <span class="badge badge-success">{{ $currency_virtual_provider }}</span>
                                                                            @endif
                                                                        
                                                                            @if(!empty($virtual_bank_account) && $virtual_bank_account->provider_name === "zenus" && !$is_vrn_exists)
                                                                                - 
                                                                                <div 
                                                                                    onclick="GetZenusVRN({{ $virtual_bank_account }})"
                                                                                    class="btn btn-sm btn-outline-secondary waves-effect waves-themed"
                                                                                    data-bs-toggle="tooltip"
                                                                                    data-bs-placement="top" 
                                                                                    data-bs-title="Tooltip on top"
                                                                                >
                                                                                    Get VRN
                                                                                </div>
                                                                            @endif
                                                                    @else
                                                                        <span id="iban-text">
                                                                            N/A
                                                                        </span>
                                                                    @endif
                                                                </td>
                                                            @endif


                                                            <td>
                                                                @if ($account->currency->logo)
                                                                    <img src="{{ $account->currency->logo }}"
                                                                        alt="{{ $account->currency->name }}"
                                                                        height="17px" />&nbsp;
                                                                @endif
                                                                {{ $account->currency->name . ' (' . $account->currency->short . ')' }}

                                                            </td>
                                                            <td> {{ \App\Helpers\Common::getAccountBalanceById($account->id) }}</td>
                                                            <td> {{ $account->maintenance_fee }}</td>
                                                            <td>
                                                                @if ($account->is_active)
                                                                    <span class="badge badge-success">Yes</span>
                                                                @else
                                                                    <span class="badge badge-danger">No</span>
                                                                @endif
                                                            </td>
                                                            <td nowrap="nowrap">
                                                                @if ($user->id != $client->created_by || !$user->manager_id > 0)
                                                                    @can('approve account')
                                                                        <div class="form-group">
                                                                            <button
                                                                                class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                type="button" data-toggle="dropdown"
                                                                                aria-haspopup="true" aria-expanded="false"><i
                                                                                    class="fal fa-ellipsis-v"></i>
                                                                            </button>
                                                                            <div class="dropdown-menu dropdown-menu-right">

                                                                                @if (!empty($account->currency->virtual_iban_provider))
                                                                                    @can('approve account')
                                                                                        <a 
                                                                                            class="dropdown-item"
                                                                                            href="javascript:accountSettings({{ $account->id }}, '{{ strtolower($currency_virtual_provider ?? '') }}')"
                                                                                        >
                                                                                            Override Virtual Bank Provider
                                                                                        </a>
                                                                                    @endcan
                                                                                @endif

                                                                                <a class="dropdown-item"
                                                                                    href="javascript:maintenanceFee({{ $account->id }}, {{ $account->maintenance_fee ?? 0 }})">
                                                                                    Edit Maintenance Fee
                                                                                </a>
                                                                                @if (!$account->is_active)
                                                                                    @can('approve account')
                                                                                        <div role="separator"
                                                                                            class="dropdown-divider"></div>
                                                                                        <a class="dropdown-item"
                                                                                            href="javascript:approveAccount({{ $account->id }})">Approve</a>
                                                                                    @endcan
                                                                                    @if (empty($account->openpayd_account_id))
                                                                                        @can('delete records')
                                                                                            <div role="separator"
                                                                                                class="dropdown-divider"></div>
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                        @endcan
                                                                                    @endif
                                                                                @else
                                                                                    @can('reject account')
                                                                                        <a class="dropdown-item"
                                                                                            href="javascript:rejectAccount({{ $account->id }})">Reject</a>
                                                                                    @endcan
                                                                                    @if (empty($account->openpayd_account_id))
                                                                                        @can('delete records')
                                                                                            <div role="separator"
                                                                                                class="dropdown-divider"></div>
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                        @endcan
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                    @endcan
                                                                @elseif (!$account->is_active)
                                                                    <div class="form-group">
                                                                        <button
                                                                            class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                            type="button" data-toggle="dropdown"
                                                                            aria-haspopup="true" aria-expanded="false"><i
                                                                                class="fal fa-ellipsis-v"></i>
                                                                        </button>
                                                                        @can('delete records')
                                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                                <a class="dropdown-item"
                                                                                    href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                            </div>
                                                                        @endcan
                                                                    </div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @else
                                                        @php $cryptoAccounts[] = $account; @endphp
                                                    @endif
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            <form method="post"
                                action="{{ url('/client-management/clients/update/company/account-currency') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <input type="hidden" name="company_id" value="{{ $client->company->id }}">
                                <input type="hidden" name="parent_client_id"
                                    value="{{ session('parent_client_id') }}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Add New Currencies</h5>
                                        <hr>
                                        <div class="form-group">
                                            @php
                                                $existingAccounts = $client->company->accounts
                                                    ->pluck('account_currency')
                                                    ->toArray();
                                                $existingDraftAccounts = $client->draftCurrencies
                                                    ->pluck('currency_id')
                                                    ->toArray();
                                                $existing = array_merge($existingAccounts, $existingDraftAccounts);
                                            @endphp
                                            @foreach ($currencies as $currency)
                                                @if (!$currency->is_crypto)
                                                    @if (!in_array($currency->id, $existing))
                                                        <div class="custom-control mt-3">
                                                            <div class="custom-switch">
                                                                <div class="d-flex align-items-center">
                                                                    <input type="checkbox"
                                                                        class="custom-control-input custom-switch"
                                                                        id="currency-{{ $currency->id }}"
                                                                        value="{{ $currency->id }}" name="currency[]">
                                                                    <label class="custom-control-label"
                                                                        for="currency-{{ $currency->id }}">
                                                                        <img src="{{ $currency->logo ? $currency->logo : '' }}"
                                                                            alt="{{ $currency->name }}"
                                                                            height="17px" />
                                                                        &nbsp;{{ $currency->name . ' (' . $currency->short . ')' }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @else
                                                    @php $cryptoCurrencies[] = $currency; @endphp
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group">
                                            <hr>
                                            <button type="submit" class="btn btn-primary">Save</button>
                                            @include('client-management.partials.approve-reject-button')
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        {{-- Crypto Currency Accounts --}}
                        @if (in_array('Crypto', $enabledModules))
                            @can('client digital assets')
                                <div class="tab-pane fade" id="crypto-account-currency" role="tabpanel"
                                    aria-labelledby="crypto-account-currency">
                                    @if ($isDraft && count($cryptoDraftAccounts) > 0)
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5>Requested Crypto Accounts</h5>
                                                <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                                                    <thead class="thead-themed ">
                                                        <tr>
                                                            <th width="30">S.No</th>
                                                            <th>Currency</th>
                                                            <th>Code</th>
                                                            <th width="30" align="center"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($cryptoDraftAccounts as $account)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>
                                                                    @if ($account->currency->logo)
                                                                        <img src="{{ $account->currency->logo }}"
                                                                            alt="{{ $account->currency->name }}"
                                                                            height="17px" />&nbsp;
                                                                    @endif
                                                                    {{ $account->currency->name }}
                                                                </td>
                                                                <td nowrap="nowrap">{{ $account->currency->short }}</td>
                                                                <td>
                                                                    <a
                                                                        href="javascript:deleteDraftAccount({{ $account->id }})">
                                                                        <i
                                                                            class="alert-danger fal fa-times-circle rounded-circle"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                    @if (count($cryptoAccounts) > 0)
                                        <div class="row">
                                            <div class="col-md-12">
                                                <h5>Existing Crypto Accounts</h5>
                                                <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                                                    <thead class="thead-themed ">
                                                        <tr>
                                                            <th width="30">S.No</th>
                                                            <th>Account title</th>
                                                            <th>Account Number</th>
                                                            <th>Currency</th>
                                                            <th>Is Active?</th>
                                                            <th width="30" align="center"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($cryptoAccounts as $account)
                                                            <tr>
                                                                <td>{{ $loop->iteration }}</td>
                                                                <td>{{ $account->title }}</td>
                                                                <td>{{ $account->account_number }}</td>
                                                                <td>
                                                                    @if ($account->currency->logo)
                                                                        <img src="{{ $account->currency->logo }}"
                                                                            alt="{{ $account->currency->name }}"
                                                                            height="17px" />&nbsp;
                                                                    @endif
                                                                    {{ $account->currency->name . ' (' . $account->currency->short . ')' }}
                                                                </td>
                                                                <td>
                                                                    @if ($account->is_active)
                                                                        <span class="badge badge-success">Yes</span>
                                                                    @else
                                                                        <span class="badge badge-danger">No</span>
                                                                    @endif
                                                                </td>
                                                                <td nowrap="nowrap">
                                                                    @if ($user->id != $client->created_by || !$user->manager_id > 0)
                                                                        @can('approve account')
                                                                            <div class="form-group">
                                                                                <button
                                                                                    class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                    type="button" data-toggle="dropdown"
                                                                                    aria-haspopup="true" aria-expanded="false">
                                                                                    <i class="fal fa-ellipsis-v"></i>
                                                                                </button>
                                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                                    @if (!$account->is_active)
                                                                                        @can('approve account')
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:approveAccount({{ $account->id }})">Approve</a>
                                                                                        @endcan
                                                                                        @can('delete records')
                                                                                            <div role="separator"
                                                                                                class="dropdown-divider"></div>
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                        @endcan
                                                                                    @else
                                                                                        @can('reject account')
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:rejectAccount({{ $account->id }})">Reject</a>
                                                                                        @endcan
                                                                                        @can('delete records')
                                                                                            <div role="separator"
                                                                                                class="dropdown-divider"></div>
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                        @endcan
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @endcan
                                                                    @elseif (!$account->is_active)
                                                                        <div class="form-group">
                                                                            <button
                                                                                class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                type="button" data-toggle="dropdown"
                                                                                aria-haspopup="true"
                                                                                aria-expanded="false"><i
                                                                                    class="fal fa-ellipsis-v"></i>
                                                                            </button>
                                                                            @can('delete records')
                                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                                    <a class="dropdown-item"
                                                                                        href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                </div>
                                                                            @endcan
                                                                        </div>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    @endif
                                    <form method="post"
                                        action="{{ url('/client-management/clients/update/company/account-currency') }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                                        <input type="hidden" name="company_id" value="{{ $client->company->id }}">
                                        <input type="hidden" name="is_crypto" value="true">
                                        <input type="hidden" name="parent_client_id"
                                            value="{{ session('parent_client_id') }}">
                                        <div class="row">
                                            @if (count($cryptoCurrencies) > 0)
                                                <div class="col-md-6">
                                                    <h5>Add New Crypto Currencies</h5>
                                                    <hr>
                                                    <div class="form-group">
                                                        @foreach ($cryptoCurrencies as $cryptoCurrency)
                                                            @if (!in_array($cryptoCurrency->id, $existing))
                                                                <div class="custom-control custom-switch mt-3">
                                                                    <input type="checkbox" class="custom-control-input"
                                                                        id="currency-{{ $cryptoCurrency->id }}"
                                                                        value="{{ $cryptoCurrency->id }}"
                                                                        name="currency[]">
                                                                    <label class="custom-control-label"
                                                                        for="currency-{{ $cryptoCurrency->id }}"><img
                                                                            src="{{ $cryptoCurrency->logo ? $cryptoCurrency->logo : '' }}"
                                                                            alt="{{ $cryptoCurrency->name }}"
                                                                            height="17px" />&nbsp;{{ $cryptoCurrency->name . ' (' . $cryptoCurrency->short . ')' }}</label>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @include('feeengine::partials.client-fee-tier-group', ['client' => $client])
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <hr>
                                                    <button type="submit" class="btn btn-primary">Save</button>
                                                    @include('client-management.partials.approve-reject-button')
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endcan
                        @endif
                        {{-- Crypto Currency Accounts --}}

                        <div class="tab-pane fade" id="related-files" role="tabpanel"
                            aria-labelledby="related-files">
                            <form method="post" action="{{ url('/client-management/clients/update/company/files') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <input type="hidden" name="company_id" value="{{ $client->company->id }}">
                                <div class="row">
                                    <div class="col-md-12">
                                        <table class="table table-striped table-bordered table-hover table-responsive-xl">
                                            <thead>
                                                <tr>
                                                    <th>S.NO</th>
                                                    <th>Required Documents</th>
                                                    <th>Upload</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($fileTypes as $file)
                                                    @php $existingFiles = $client->company->files->where("file_id", $file->id)->where("is_deleted", false) @endphp
                                                    <tr>
                                                        <td>{{ $loop->iteration }}</td>
                                                        <td>{{ $file->title }}<br>
                                                            @if ($file->isMultiple)
                                                                <small><i>(You can upload multiple files)</i></small>
                                                            @else
                                                                <small><i>(You can upload single file only)</i></small>
                                                            @endif

                                                            @php
                                                                $provider_labels = [
                                                                    'openpayd' => 'OpenPayd',
                                                                    'lorum' => 'Lorum',
                                                                    'zenus' => 'Zenus',
                                                                ];

                                                                $formattedProviders = collect(
                                                                    json_decode($file->required_for_providers),
                                                                )
                                                                    ->map(fn($p) => $provider_labels[$p] ?? ucfirst($p))
                                                                    ->implode(', ');
                                                            @endphp

                                                            <br>
                                                            @if ($file->required_for_providers)
                                                                <small class="text-danger">
                                                                    <i>
                                                                        This file required for <b>
                                                                            {{ $formattedProviders }} </b>
                                                                    </i>
                                                                </small>
                                                            @endif
                                                        </td>

                                                        <td>
                                                            @if ($file->identifier === 'passport_copy')
                                                                <hr>
                                                                <button class="btn btn-primary" type="button"
                                                                    id="upload-passport">
                                                                    Upload Passport Copy
                                                                </button>
                                                            @else
                                                                <input type="file"
                                                                    @if ($file->maxCount) onchange="setFileCount(this)" @endif
                                                                    @if ($file->isMultiple) multiple name="file-{{ $file->id }}[]" @else name="file-{{ $file->id }}" @endif>
                                                            @endif
                                                            @foreach ($existingFiles as $efile)
                                                                <div class="row p-2">
                                                                    <div class="col-md-8">
                                                                        <a href="{{ $efile->url }}" target="_blank"
                                                                            class="btn btn-sm btn-outline-secondary waves-effect waves-themed mr-1">
                                                                            <i class="fal fa-file-alt"></i>
                                                                            View
                                                                        </a>
                                                                        {{-- {{ dd($efile->file_name) }} --}}
                                                                        @if ($file->identifier == 'passport_copy')
                                                                            @if (!empty($efile->director_id) && $efile->director->type === 'director')
                                                                                @if ($efile->file_name)
                                                                                    <b> {{ $efile->file_name }} | </b>
                                                                                @endif
                                                                                {{ $efile->director->name }}
                                                                                <span
                                                                                    class="badge badge-dark">Director</span>
                                                                            @elseif (!empty($efile->director_id) && $efile->director->type === 'signatory')
                                                                                @if ($efile->file_name)
                                                                                    <b> {{ $efile->file_name }} | </b>
                                                                                @endif
                                                                                {{ $efile->director->name }}
                                                                                <span
                                                                                    class="badge badge-info">Signatory</span>
                                                                            @else
                                                                                @if (!empty($efile->client->company->contact->name))
                                                                                    @if ($efile->file_name)
                                                                                        <b> {{ $efile->file_name }} |
                                                                                        </b>
                                                                                    @endif
                                                                                    {{ $efile->client->company->contact->name }}
                                                                                @endif
                                                                                <span
                                                                                    class="badge badge-success">Owner</span>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['super admin']))
                                                                        <div class="col-md-4">
                                                                            <a
                                                                                href="javascript:deleteFile({{ $efile->id }})"><i
                                                                                    class="alert-danger fal fa-times-circle rounded-circle"></i></a>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </td>
                                                    </tr>

                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-10">
                                        <div class="form-group">
                                            <hr>
                                            <button type="submit" class="btn btn-primary">Submit</button>
                                            @include('client-management.partials.approve-reject-button')
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex justify-content-end">
                                        <div class="form-group">
                                            <hr>
                                            @if ($client->company->files->count() > 0)
                                                <a class="btn btn-success text-white"
                                                    href="{{ url('/client-management/clients/download-files/' . $client->id) }}">Download
                                                    All files</a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        @if (count($changes) > 0)
                            <div class="tab-pane fade" id="changes" role="tabpanel" aria-labelledby="changes">
                                @if (\Illuminate\Support\Facades\Auth::user()->can('approve client'))
                                    @include('common.model-changes-approve')
                                @else
                                    @include('common.model-changes')
                                @endif
                            </div>
                        @endif

                        @if ($client->status == 'Approved' && !$client->parent_id && in_array('SubAccounts', $enabledModules))
                            @can('client sub accounts')
                                @include('client-management.partials.sub-accounts')
                            @endcan
                        @endif
                        <div class="tab-pane fade" id="client-kyc" role="tabpanel" aria-labelledby="client-kyc">
                            @if (in_array('Sumsub', $enabledModules))
                                <div
                                    class="d-flex flex-wrap justify-content-between align-items-center mb-4 p-3 bg-light border rounded shadow-sm">
                                    <div class="d-flex flex-wrap align-items-center">
                                        <h5 class="m-0 font-weight-bold text-primary mr-3">
                                            <i class="fal fa-shield-check mr-2"></i>Sumsub Company Verification
                                        </h5>

                                        <!-- LIVE VERIFICATION -->
                                        @if (isset($company_transaction_log) && count($company_transaction_log) > 0)
                                            @php
                                                $latestLogDetails = json_decode(
                                                    $company_transaction_log[0]->details,
                                                    true,
                                                );
                                                $reviewStatus = ucfirst(
                                                    str_replace('_', ' ', $latestLogDetails['reviewStatus'] ?? 'N/A'),
                                                );
                                                $reviewAnswer = ucfirst(
                                                    str_replace(
                                                        '_',
                                                        ' ',
                                                        $latestLogDetails['reviewResult']['reviewAnswer'] ?? 'N/A',
                                                    ),
                                                );
                                            @endphp
                                            <div class="d-inline-flex align-items-center my-1 my-md-0">
                                                <span
                                                    class="badge {{ $statusBadge[$reviewStatus] ?? 'badge-secondary' }} px-2 py-1 mr-2 text-uppercase font-weight-bold"
                                                    style="font-size: 85%;">
                                                    Status: {{ $reviewStatus }}
                                                </span>
                                                <span
                                                    class="badge {{ $statusBadge[$reviewAnswer] ?? 'badge-secondary' }} px-2 py-1 text-uppercase font-weight-bold"
                                                    style="font-size: 85%;">
                                                    Result: {{ $reviewAnswer }}
                                                </span>
                                            </div>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1 text-uppercase font-weight-bold"
                                                style="font-size: 85%;">
                                                Uninitiated
                                            </span>
                                        @endif
                                    </div>

                                    <div class="mt-2 mt-md-0">
                                        @if (!empty($clientKYC) && !empty($clientKYC->company_applicant_id) && !empty($sumsubClientId))
                                            <a href="https://cockpit.sumsub.com/checkus/applicant/{{ $clientKYC->company_applicant_id }}/basicInfo?clientId={{ $sumsubClientId }}"
                                                target="_blank" class="btn btn-info btn-sm shadow-sm">
                                                <i class="fal fa-external-link mr-1"></i> Open Sumsub Profile
                                            </a>
                                        @endif
                                        @if (!empty($clientKYC))
                                            <button onclick="viewSumsubLogs(); return false;"
                                                class="btn btn-secondary btn-sm ml-2 shadow-sm">
                                                <i class="fal fa-history mr-1"></i> Verification History
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if ($clientKYC)
                                @include('client-management.partials.client-kyc')
                            @elseif ($client->status == 'Approved' || $client->status == 'Pending')
                                @include('client-management.partials.client-approved-kyc')
                            @endif
                            @include('client-management.partials.client-directors-kyc')
                        </div>

                        @if ($client->type == 'company')
                            <div class="tab-pane fade" id="client-risk-rating" role="tabpanel"
                                aria-labelledby="client-risk-rating">
                                @include('client-management.partials.company-client-risk-rating')
                            </div>
                        @endif
                    </div>

                    @if (in_array('OnboardingFee', $enabledModules) && $client->status == 'Approved')
                        <div class="tab-pane fade" id="onboarding-fee" role="tabpanel"
                            aria-labelledby="onboarding-fee">
                            @include('client-management.partials.onboarding-fee')
                        </div>
                    @endif
                </div>
            </div>

        </div>

        <div class="modal fade" id="upload-passport-modal" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Upload passport copy</h5>

                        {{-- <button type="button" id="close-passport-upload-modal" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button> --}}

                    </div>

                    <form method="post" action="{{ url('/client-management/clients/update/company/files') }}"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="passport_for_client_id" class="form-label">Select client</label>

                                        @php
                                            $sortedDirectors = $client->directors->sortBy(function ($director) {
                                                if ($director->is_owner) {
                                                    return 0;
                                                } elseif ($director->type == 'director') {
                                                    return 1;
                                                } else {
                                                    return 2;
                                                }
                                            });
                                        @endphp
                                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                                        <input type="hidden" name="company_id" value="{{ $client->company->id }}">
                                        <select name="passport_for_client_id" id="passport_for_client_id"
                                            data-select2-id="list-of-clients" class="form-control select2">
                                            <option value="">Select client</option>
                                            @foreach ($sortedDirectors as $sortedDirector)
                                                @if ($sortedDirector->is_owner)
                                                    <option value={{ null }}>
                                                        Owner | {{ $sortedDirector->name }}
                                                    </option>
                                                @elseif ($sortedDirector->type == 'director')
                                                    <option value="{{ $sortedDirector->id }}">
                                                        Director | {{ $sortedDirector->name }}
                                                    </option>
                                                @elseif ($sortedDirector->type == 'signatory')
                                                    <option value="{{ $sortedDirector->id }}">
                                                        Signatory | {{ $sortedDirector->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="file-name" class="form-label">File name</label>
                                        <input class="form-control" name="passport-file-name" type="text"
                                            id="file-name">
                                    </div>
                                </div>
                            </div>

                            @php
                                $passportFile = $fileTypes
                                    ->where('identifier', 'passport_copy')
                                    ->whereIn('type', $client->is_bank == 1 ? 'bank' : 'company')
                                    ->first();
                            @endphp

                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="uploaded-passport" class="form-label">Upload passport</label>
                                        <input class="form-control" type="file" required
                                            @if ($passportFile->maxCount) onchange="setFileCount(this)" @endif
                                            @if ($passportFile->isMandatory) required @endif
                                            @if ($passportFile->isMultiple) multiple name="client-passport[]" @else name="client-passport" @endif>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary" id="modal-submit">Upload</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <form method="POST" name="create-iban-form" id="create-iban-form"
            action="{{ url('/client-management/clients/create-iban') }}">
            @csrf
            <input type="hidden" name="account_id" id="create-iban-account-id" value="">

        </form>

        <form method="POST" name="delete-file" id="delete-file"
            action="{{ url('/clients/company/files/delete') }}">
            @csrf
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
            <input type="hidden" name="file_id" id="file_id" value="">
        </form>

        <form method="POST" name="delete-account" id="delete-account"
            action="{{ url('/client-management/clients/company/accounts/delete') }}">
            @csrf
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
            <input type="hidden" name="account_id" id="account_id" value="">
        </form>

        <form method="POST" name="delete-draft-account" id="delete-draft-account"
            action="{{ url('/client-management/clients/company/draft-accounts/delete') }}">
            @csrf
            <input type="hidden" name="draft_account_id" id="draft_account_id" value="">
        </form>

        <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Reject Client</h5>
                        <button onclick="closeRejectClientModal();" type="button" class="close"
                            data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/client-management/clients/company/reject') }}" method="post">
                            @csrf
                            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                            <div class="form-group">
                                <label class="form-label" for="reject_reason">Reject Reason</label>
                                <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Reject</button>
                                {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> --}}
                                <button onclick="closeRejectClientModal();" type="button"
                                    class="btn btn-secondary">Close</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="close-account-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Close Client Profile</h5>
                        <button onclick="cancelCloseAccountModal();" type="button" class="close"
                            data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/client-management/clients/close') }}" method="post">
                            @csrf
                            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                            <div class="form-group">
                                <label class="form-label" for="close_reason">Closing Reason</label>
                                <textarea required class="form-control" name="close_reason" id="close_reason" cols="30" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Close</button>
                                <button onclick="cancelCloseAccountModal();" type="button" class="btn btn-secondary"
                                    data-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <form method="POST" name="get-zenus-vrn" id="get-zenus-vrn"
            action="{{ url('/client-management/clients/get-zenus-vrn') }}">
            @csrf
            <input type="hidden" name="virtual_account_id" id="virtual_account_id" value="">

        </form>
        <form method="POST" name="create-iban-form" id="create-iban-form"
            action="{{ url('/client-management/clients/create-iban') }}">
            @csrf
            <input type="hidden" name="account_id" id="create-iban-account-id" value="">

        </form>

        <form method="POST" name="delete-file" id="delete-file"
            action="{{ url('/clients/company/files/delete') }}">
            @csrf
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
            <input type="hidden" name="file_id" id="file_id" value="">
        </form>

        <form method="POST" name="delete-account" id="delete-account"
            action="{{ url('/client-management/clients/company/accounts/delete') }}">
            @csrf
            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
            <input type="hidden" name="account_id" id="account_id" value="">
        </form>

        <form method="POST" name="delete-draft-account" id="delete-draft-account"
            action="{{ url('/client-management/clients/company/draft-accounts/delete') }}">
            @csrf
            <input type="hidden" name="draft_account_id" id="draft_account_id" value="">
        </form>

        <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Reject Client</h5>
                        <button onclick="closeRejectClientModal();" type="button" class="close"
                            data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/client-management/clients/company/reject') }}" method="post">
                            @csrf
                            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                            <div class="form-group">
                                <label class="form-label" for="reject_reason">Reject Reason</label>
                                <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Reject</button>
                                {{-- <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> --}}
                                <button onclick="closeRejectClientModal();" type="button"
                                    class="btn btn-secondary">Close</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <div class="modal fade" id="close-account-modal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title ">Close Client Profile</h5>
                        <button onclick="cancelCloseAccountModal();" type="button" class="close"
                            data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true"><i class="fal fa-times"></i></span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/client-management/clients/close') }}" method="post">
                            @csrf
                            <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                            <div class="form-group">
                                <label class="form-label" for="close_reason">Closing Reason</label>
                                <textarea required class="form-control" name="close_reason" id="close_reason" cols="30" rows="2"></textarea>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-danger">Close</button>
                                <button onclick="cancelCloseAccountModal();" type="button" class="btn btn-secondary"
                                    data-dismiss="modal">Cancel</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>

        <form name="approve-company" id="approve-company" method="post"
            action="{{ url('/client-management/clients/company/approve') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="client_id" value="{{ $client->id }}">
        </form>

    <div class="modal fade" id="sumsub-log-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">0
                    <h5 class="modal-title ">View Sumsub Log</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="closeSumsubModal();">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    @if (isset($company_transaction_log) && count($company_transaction_log) > 0)
                        <h4 class="mt-2">Sumsub Webhook Log</h4>
                        @foreach ($company_transaction_log as $log)
                            <div class="alert alert-warning mt-4">
                                <label style="font-weight:bold;"> Date:</label> {{ $log->date_created }}<br>
                                <label style="font-weight:bold;">Source:</label> {{ $log->source }}<br>

                                @foreach (json_decode($log->details, true) as $key => $val)
                                    @if (is_array($val) || is_object($val))
                                        @php
                                            $val = json_encode($val, JSON_PRETTY_PRINT);
                                        @endphp
                                    @endif
                                    <label style="font-weight:bold;"> {{ ucfirst(str_replace('_', ' ', $key)) }}:
                                    </label>
                                    {{ $val }}<br>
                                @endforeach
                            </div>
                        @endforeach
                    @else
                        <p class="alert alert-info mt-2">No webhook logs found for this client.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @include('client-management.partials.change-virtual-bank-provider')

    <!-- NEW: Reset Questionnaire Modal -->
    <div class="modal fade" id="reset-questionnaire-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fal fa-undo mr-2"></i> Request Information Update</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Note to Compliance:</strong> This is <u>not</u> a final account rejection. This action will
                        reset the client's questionnaire and email them a secure link to update their details (like related
                        files, job title, income, etc). <strong>Their ID and Selfie will remain intact and will not need to
                            be re-uploaded.</strong>
                    </div>
                    <form id="reset-questionnaire-form" onsubmit="submitResetQuestionnaire(event)">
                        <input type="hidden" id="reset_applicant_id" value="">
                        <input type="hidden" id="reset_level_name" value="">

                        <div class="form-group">
                            <label class="form-label" style="font-weight: bold;" for="reset_reason">Reason for Update
                                (This will be emailed to the client)</label>
                            <textarea required class="form-control" id="reset_reason" rows="4"
                                placeholder="e.g., Please upload a clearer utility bill for proof of address, or please clarify your business nature..."></textarea>
                        </div>

                        <div class="form-group text-right mt-4 mb-0">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger" id="reset-submit-btn">Send Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- END NEW MODAL -->
@stop
@stop
