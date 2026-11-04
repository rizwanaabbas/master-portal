@extends('layouts.cp')
@section('title', 'Update Client - Individual - Client Management')

@php
    $isOpenPaydEnabled = in_array('OpenPayd', $enabledModules);
    $isLorumEnabled = in_array('Lorum', $enabledModules);
    $isZenusEnabled = in_array('Zenus', $enabledModules);

    $enabled_provider_map = [
        'openpayd' => $isOpenPaydEnabled,
        'lorum' => $isLorumEnabled,
        'zenus' => $isZenusEnabled,
    ];

    // $isVirtualBanksEnabled = $isOpenPaydEnabled || $isLorumEnabled || $isZenusEnabled;

@endphp

@section('scripts')

    <script>
        var controls = {
            leftArrow: '<i class="fal fa-angle-left" style="font-size: 1.25rem"></i>',
            rightArrow: '<i class="fal fa-angle-right" style="font-size: 1.25rem"></i>'
        }

        $(document).ready(function() {

            $('.select2').select2();

            // minimum setup
            $('#dob').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                endDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });

            // minimum setup
            $('#passport_expiry_date').datepicker({
                format: 'dd/mm/yyyy',
                todayHighlight: true,
                orientation: "bottom left",
                templates: controls,
                startDate: "{{ \Carbon\Carbon::today()->format('d/m/Y') }}"
            });
        })

        function rejectClient() {

            $("#reject-modal").modal('show');
        }

        function closeClient() {

            $("#close-account-modal").modal('show');
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

        function approveClient(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Client account will be approved.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Approve it!"
            }).then(function(result) {
                if (result.value) {
                    document.getElementById('approve-individual').submit()
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

        function makeActive(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Account will be activated",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Make it Active!"
            }).then(function(result) {
                if (result.value) {
                    window.location.href =
                        "{{ url('/client-management/clients/individual/accounts/approve/') }}/" + id;
                }
            });
        }

        function makeInactive(id) {

            Swal.fire({
                title: "Are you sure?",
                text: "Account will be made inactive",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, Make it Inactive!"
            }).then(function(result) {
                if (result.value) {
                    window.location.href =
                        "{{ url('/client-management/clients/individual/accounts/reject/') }}/" +
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

        function downloadAccountConfirmation(accountId) {
            if (!accountId) {
                Swal.fire('Error', 'Account ID missing', 'error');
                return;
            }

            window.open(`/client-management/clients/account-confirmation-letter/${accountId}`, '_blank');
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


        function renderZenusAccountInfoRows(accountInfo, bankAccount) {
            let rows = '';

            const client = @json($client);
            const client_individual = client.individual;
            const client_address =
                `${client_individual.state_region} ${client_individual.city} ${client_individual.street_address}`;
            const zenusMaster = @json($zenus_master_bank_details);

            // ✅ define OUTSIDE so it's always available
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

            // ✅ ALWAYS return both safely
            return {
                rows,
                sections
            };
        }

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

        function normalizeZenusAccountInfo(accountInfo) {
            const parsed = safeParse(accountInfo, []);
            const cleanObject = (obj) => Object.fromEntries(Object.entries(obj).filter(([_, v]) => isValid(v)));

            const accounts = parsed
                .map(acc => cleanObject({
                    type: acc?.accountNumber?.type,
                    account_number: acc?.accountNumber?.value,
                    institution_type: acc?.financialInstitutionId?.type,
                    institution_value: acc?.financialInstitutionId?.value,
                    currency: acc?.defaultCurrencyCode,
                    status: acc?.statusCode,
                    start_time: acc?.validityRange?.startTime,
                    // end_time: acc?.validityRange?.endTime,
                }))
                .filter(acc => Object.keys(acc).length > 0); // remove fully empty objects

            return {
                provider: 'Zenus',
                accounts
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
            const accountInfo = safeParse(bankAccount.account_info, {});
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
        // Main Entry
        // ===============================
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
                .then(() => {
                    Swal.fire({
                        title: "Copied!",
                        text: "Account details have been copied to your clipboard.",
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                }).catch(() => {
                    Swal.fire({
                        title: "Error!",
                        text: "Failed to copy account details.",
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
                            Swal.fire("Success!", "Update request and email sent successfully.",
                                "success");
                        }
                    }, 400);
                },
                error: function(xhr) {
                    $('#reset-questionnaire-modal').modal('hide');

                    let errorMsg = "Server error occurred while communicating with Sumsub.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
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
                        Update Client - Individual <span
                            class="ml-3 badge {{ $statusBadge[$client->status] }}">{{ $client->status }}</span>
                    </div>
                    <div>{{ $client->name }}
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
                        @if (!empty($clientKYC) && !empty($clientKYC->sumsub_applicant_id))
                            <button type="button" class="btn btn-danger btn-sm shadow-sm m-2"
                                onclick="openResetQuestionnaireModal('{{ $clientKYC->sumsub_applicant_id }}', 'individual-questionnaire-only')">
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
                                aria-selected="true">Individual</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#contact-person" role="tab"
                                aria-selected="false">Employer Details</a></li>
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
                            <form method="post" action="{{ url('/client-management/clients/update/individual/') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="name">Name</label>
                                            <input type="text" autofocus id="name" name="name"
                                                value="{{ $client->name }}" class="form-control" required>
                                            @error('name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="dob">Date of Birth</label>
                                            <input type="text" autocomplete="off" class="form-control" name="dob"
                                                value="{{ $client->individual->dob ? \Carbon\Carbon::parse($client->individual->dob)->format('d/m/Y') : '' }}"
                                                id="dob" placeholder="Select date">
                                            @error('dob')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="nationality_id">Nationality</label>
                                            <select name="nationality_id" id="nationality_id"
                                                value="{{ $client->individual->nationality_id }}"
                                                data-select2-id="nationality_id" class="form-control select2 w-100"
                                                required>
                                                <option value="">Choose nationality</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->id == $client->individual->nationality_id)
                                                        <option value="{{ $nationality->id }}" selected>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}">{{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>
                                            @error('nationality_id')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="nationality_id">Country of Residence</label>
                                            <select name="residence_country_id" id="residence_country_id"
                                                data-select2-id="residence_country_id" class="form-control select2 w-100"
                                                required>
                                                <option value="">Choose country of residence</option>
                                                @foreach ($nationalities as $nationality)
                                                    @if ($nationality->id == $client->individual->residence_country_id)
                                                        <option value="{{ $nationality->id }}" selected>
                                                            {{ $nationality->name }}</option>
                                                    @else
                                                        <option value="{{ $nationality->id }}">{{ $nationality->name }}
                                                        </option>
                                                    @endif
                                                @endforeach
                                            </select>

                                            <span class="error-input" role="alert"><strong></strong></span>

                                        </div>


                                        <div class="row">

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="street_address">Address Line 1</label>
                                                <input type="text" id="street_address" name="street_address"
                                                    placeholder="123 Main Street or Apartment 4B"
                                                    value="{{ $client->individual ? $client->individual->street_address : old('street_address') }}"
                                                    class="form-control" required>
                                                @error('street_address')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="street_2_address">Address Line 2</label>
                                                <input type="text" id="street_2_address" name="street_2_address"
                                                    placeholder="Apt, Suite, Unit, Building, Floor (optional)"
                                                    value="{{ $client->individual ? $client->individual->street_2_address : old('street_2_address') }}"
                                                    class="form-control">
                                                @error('street_2_address')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="row">

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="city">City of Individual</label>
                                                <input type="text" id="city" name="city"
                                                    placeholder="e.g. New York"
                                                    value="{{ $client->individual ? $client->individual->city : old('city') }}"
                                                    class="form-control" required>
                                                @error('city')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="state_region">State/Region</label>
                                                <input type="text" id="state_region" name="state_region"
                                                    placeholder="e.g. CA (2-letter code)"
                                                    value="{{ $client->individual ? $client->individual->state_region : old('state_region') }}"
                                                    class="form-control" required>
                                                @error('state_region')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="row pb-3">

                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="zip_code">ZIP Code</label>
                                                <input type="text" id="zip_code" name="zip_code"
                                                    placeholder="e.g. 90210 or 90210-1234"
                                                    value="{{ $client->individual ? $client->individual->zip_code : old('zip_code') }}"
                                                    class="form-control" required>
                                                @error('zip_code')
                                                    <span class="invalid-feedback" role="alert">
                                                        <strong>{{ $message }}</strong>
                                                    </span>
                                                @enderror
                                            </div>

                                        </div>

                                        <div class="form-group">
                                            <label class="form-label" for="mailing_address">Mailing Address</label>
                                            <input type="text" autofocus id="mailing_address" name="mailing_address"
                                                value="{{ $client->individual->mailing_address }}" class="form-control"
                                                required>
                                            @error('mailing_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>

                                        <label class="form-label" for="gender">Gender</label><br>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="male"
                                                @if ($client->individual->gender == 'male') checked @endif value="male"
                                                name="gender">
                                            <label class="custom-control-label" for="male">Male</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline mb-3">
                                            <input type="radio" class="custom-control-input" id="female"
                                                @if ($client->individual->gender == 'female') checked @endif value="female"
                                                name="gender">
                                            <label class="custom-control-label" for="female">Female</label>
                                        </div>
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
                                                <small>Only Select for If Client Belongs to Some Organizational Unit</small>
                                            </div>
                                        @endcan
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="mobile_number">Mobile Number</label>
                                            <div class="row">
                                                @include('client-management.partials.contact-number.edit', [
                                                    'client' => $client,
                                                    'nationalities' => $nationalities,
                                                    'isIndividual' => true,
                                                ])
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="email_address">Email Address</label>
                                            <input type="email" id="email_address" name="email_address"
                                                value="{{ $client->individual->email_address }}" class="form-control"
                                                required>
                                            @error('email_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="passport_number">Passport Number</label>
                                            <input type="text" id="passport_number" name="passport_number"
                                                value="{{ $client->individual->passport_number }}" class="form-control"
                                                required>
                                            @error('passport_number')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="passport_expiry_date">Passport Expiry
                                                Date</label>
                                            <input autocomplete="off" type="text" class="form-control"
                                                name="passport_expiry_date"
                                                value="{{ $client->individual->passport_expiry_date ? \Carbon\Carbon::parse($client->individual->passport_expiry_date)->format('d/m/Y') : '' }}"
                                                id="passport_expiry_date" placeholder="Select date">
                                            @error('passport_expiry_date')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
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
                                                <label for="" class="form-label mb-2">Send Emails</label><br>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input" value="1"
                                                        id="send_welcome_email"
                                                        @if ($client->send_welcome_email) checked @endif
                                                        name="send_welcome_email">
                                                    <label class="custom-control-label" for="send_welcome_email">Welcome
                                                        Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input" value="1"
                                                        id="send_ssi" @if ($client->send_ssi) checked @endif
                                                        name="send_ssi">
                                                    <label class="custom-control-label" for="send_ssi">SSI Email</label>
                                                </div>
                                                <div class="custom-control custom-checkbox custom-control-inline">
                                                    <input type="checkbox" class="custom-control-input" value="1"
                                                        id="create_user" @if ($client->create_user) checked @endif
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
                                                <hr />
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
                                                <p><i class="fal fa-exclamation-triangle"></i> The client account has
                                                    been
                                                    rejected by
                                                    "{{ \App\Helpers\Common::getUserNameById($client->rejected_by) }}".
                                                    Please go through below reason, make the required changes and submit
                                                    it
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
                            <form method="post"
                                action="{{ url('/client-management/clients/update/individual/employer') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label" for="occupation_business">Occupation /
                                                Business</label>
                                            <input type="text" id="occupation_business" name="occupation_business"
                                                value="{{ $client->individual->employer ? $client->individual->employer->occupation_business : old('occupation_business') }}"
                                                class="form-control" required>
                                            @error('occupation_business')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="employer_business_name">Applicant's Employer
                                                Business Name</label>
                                            <input type="text" id="employer_business_name"
                                                name="employer_business_name"
                                                value="{{ $client->individual->employer ? $client->individual->employer->employer_business_name : old('employer_business_name') }}"
                                                class="form-control" required>
                                            @error('employer_business_name')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="employer_address">Employer Address</label>
                                            <input type="text" id="employer_address" name="employer_address"
                                                value="{{ $client->individual->employer ? $client->individual->employer->employer_address : old('employer_address') }}"
                                                class="form-control" required>
                                            @error('employer_address')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="row">
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="occupation_soc_code">Occupation
                                                    code</label>
                                                <select name="occupation_soc_code" id="occupation_soc_code"
                                                    data-select2-id="occupation_soc_code"
                                                    class="form-control select2 w-100 select2">
                                                    <option value="">Choose industry sector code</option>
                                                    @foreach ($soc_codes as $code)
                                                        @php
                                                            $soc_code = '';
                                                            if ($client->individual->employer) {
                                                                $soc_code = $client->individual->employer->soc_code;
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
                                            <div class="form-group col-md-6">
                                                <label class="form-label" for="job_title">Job Title</label>
                                                <input type="text" id="job_title" name="job_title"
                                                    value="{{ $client->individual->employer ? $client->individual->employer->job_title : old('job_title') }}"
                                                    class="form-control" required>
                                                @error('job_title')
                                                    <span class="invalid-feedback"
                                                        role="alert"><strong>{{ $message }}</strong></span>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label" for="$applicant_annual_income_usd">Applicant's
                                                Estimated Gross Annual Income (USD)</label>
                                            <input type="number" id="$applicant_annual_income_usd"
                                                name="applicant_annual_income_usd"
                                                value="{{ $client->individual->employer ? $client->individual->employer->applicant_annual_income_usd : old('applicant_annual_income_usd') }}"
                                                class="form-control" required>
                                            @error('$applicant_annual_income_usd')
                                                <span class="invalid-feedback"
                                                    role="alert"><strong>{{ $message }}</strong></span>
                                            @enderror
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
                                            <thead class="thead-themed">
                                                <tr>
                                                    <th width="30">S.No</th>
                                                    <th>Currency</th>
                                                    <th>Code</th>
                                                    @can('delete records')
                                                        <th width="30" align="center"></th>
                                                    @endcan
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
                                                            @can('delete records')
                                                                <td>
                                                                    <a
                                                                        href="javascript:deleteDraftAccount({{ $account->id }})">
                                                                        <i
                                                                            class="alert-danger fal fa-times-circle rounded-circle"></i>
                                                                    </a>
                                                                </td>
                                                            @endcan
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
                            @if ($client->individual->accounts->count() > 0)
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Existing Accounts</h5>
                                        <table class="table table-bordered table-hover m-0 mb-4 table-responsive-xl">
                                            <thead class="thead-themed">
                                                <tr>
                                                    <th width="30">S.No</th>
                                                    <th>Account Title</th>
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
                                                @foreach ($client->individual->accounts as $account)
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
                                                                        $override_provider =
                                                                            $account->virtual_bank_provider ??
                                                                            $account_currency->virtual_iban_provider;

                                                                        // Use override only if enabled
                                                                        $currency_virtual_provider =
                                                                            $override_provider &&
                                                                            ($enabled_provider_map[
                                                                                $override_provider
                                                                            ] ?? false)
                                                                                ? $override_provider
                                                                                : $account_currency->virtual_iban_provider;

                                                                        // Final provider enabled status
                                                                        $is_provider_enabled =
                                                                            $enabled_provider_map[
                                                                                $currency_virtual_provider
                                                                            ] ?? false;

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
                                                                                @if (
                                                                                    !empty($account->linkedClient) &&
                                                                                        $account->linkedClient->kyc_status === 'PENDING' &&
                                                                                        $account->linkedClient->provider_name === 'lorum')
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
                                                                            <span href="javascript:void(0)"
                                                                                onclick="accountSettings({{ $account->id }}, '{{ strtolower($currency_virtual_provider ?? '') }}')">
                                                                                <span class="fal fa-cog mr-1"></span>
                                                                            </span>
                                                                        @endcan

                                                                        @if (!empty($virtual_bank_account) && $virtual_bank_account->provider_name)
                                                                            - <span
                                                                                class="badge badge-success">{{ $virtual_bank_account->provider_name }}</span>
                                                                            @if (strtolower($virtual_bank_account->provider_name) === 'zenus' && ($virtual_bank_account->status ?? '') === 'BLOCKED')
                                                                                <span
                                                                                    class="badge badge-danger">{{ $virtual_bank_account->status }}</span>
                                                                            @endif
                                                                        @else
                                                                            - <span
                                                                                class="badge badge-success">{{ $currency_virtual_provider }}</span>
                                                                        @endif

                                                                        @if (!empty($virtual_bank_account) && $virtual_bank_account->provider_name === 'zenus' && !$is_vrn_exists)
                                                                            -
                                                                            <div onclick="GetZenusVRN({{ $virtual_bank_account }})"
                                                                                class="btn btn-sm btn-outline-secondary waves-effect waves-themed"
                                                                                data-bs-toggle="tooltip"
                                                                                data-bs-placement="top"
                                                                                data-bs-title="Tooltip on top">
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
                                                            <td> {{ \App\Helpers\Common::getAccountBalanceById($account->id) }}
                                                            </td>
                                                            <td> {{ $account->maintenance_fee }}</td>
                                                            <td>
                                                                @if ($account->is_active)
                                                                    <span class="badge badge-success">Yes</span>
                                                                @else
                                                                    <span class="badge badge-danger">No</span>
                                                                @endif
                                                            </td>
                                                            <td nowrap="nowrap">
                                                                @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['operations manager', 'finance manager', 'super admin']))
                                                                    @can('approve account')
                                                                        @if ($user->id != $client->created_by || !$user->manager_id > 0)
                                                                            <div class="form-group">
                                                                                <button
                                                                                    class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                    type="button" data-toggle="dropdown"
                                                                                    aria-haspopup="true"
                                                                                    aria-expanded="false"><i
                                                                                        class="fal fa-ellipsis-v"></i></button>
                                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                                    @if (!empty($account->currency->virtual_iban_provider))
                                                                                        @can('approve account')
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:accountSettings({{ $account->id }}, '{{ strtolower($currency_virtual_provider ?? '') }}')">
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
                                                                                                href="javascript:makeActive({{ $account->id }})">Make
                                                                                                Active</a>
                                                                                        @endcan
                                                                                        @if (empty($account->virtual_bank_account_id))
                                                                                            @can('delete records')
                                                                                                <div role="separator"
                                                                                                    class="dropdown-divider">
                                                                                                </div>
                                                                                                <a class="dropdown-item"
                                                                                                    href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                            @endcan
                                                                                        @endif
                                                                                    @else
                                                                                        @can('reject account')
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:makeInactive({{ $account->id }})">Make
                                                                                                Inactive</a>
                                                                                        @endcan
                                                                                        @if (empty($account->virtual_bank_account_id))
                                                                                            @can('delete records')
                                                                                                <div role="separator"
                                                                                                    class="dropdown-divider">
                                                                                                </div>
                                                                                                <a class="dropdown-item"
                                                                                                    href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                            @endcan
                                                                                        @endif
                                                                                    @endif
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                            <div class="form-group">
                                                                                <button
                                                                                    class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                    type="button" data-toggle="dropdown"
                                                                                    aria-haspopup="true"
                                                                                    aria-expanded="false"><i
                                                                                        class="fal fa-ellipsis-v"></i></button>
                                                                                @can('delete records')
                                                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                                        <a class="dropdown-item"
                                                                                            href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                    </div>
                                                                                @endcan
                                                                            </div>
                                                                        @endif
                                                                    @endcan
                                                                @elseif (!$account->is_active)
                                                                    <div class="form-group">
                                                                        <button
                                                                            class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                            type="button" data-toggle="dropdown"
                                                                            aria-haspopup="true" aria-expanded="false"><i
                                                                                class="fal fa-ellipsis-v"></i></button>
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
                                action="{{ url('/client-management/clients/update/individual/account-currency') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
                                <div class="row">
                                    <div class="col-md-4">
                                        <h5>Add New Currencies</h5>
                                        <hr>
                                        <div class="form-group">
                                            @php
                                                $existingAccounts = $client->individual->accounts
                                                    ->pluck('account_currency')
                                                    ->toArray();
                                                $existingDraftAccounts = $client->draftCurrencies
                                                    ->pluck('currency_id')
                                                    ->toArray();
                                                $existing = array_merge($existingAccounts, $existingDraftAccounts);
                                            @endphp
                                            @foreach ($currencies as $currency)
                                                @if (!in_array($currency->id, $existing))
                                                    @if (!$currency->is_crypto)
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
                                                    @else
                                                        @php $cryptoCurrencies[] = $currency; @endphp
                                                    @endif
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
                                                    <thead class="thead-themed">
                                                        <tr>
                                                            <th width="30">S.No</th>
                                                            <th>Currency</th>
                                                            <th>Code</th>
                                                            @can('delete records')
                                                                <th width="30" align="center"></th>
                                                            @endcan
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
                                                                @can('delete records')
                                                                    <td>
                                                                        <a
                                                                            href="javascript:deleteDraftAccount({{ $account->id }})">
                                                                            <i
                                                                                class="alert-danger fal fa-times-circle rounded-circle"></i>
                                                                        </a>
                                                                    </td>
                                                                @endcan
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
                                                    <thead class="thead-themed">
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
                                                                    @if (\Illuminate\Support\Facades\Auth::user()->hasRole(['operations manager', 'finance manager', 'super admin']))
                                                                        @can('approve account')
                                                                            @if ($user->id != $client->created_by || !$user->manager_id > 0)
                                                                                <div class="form-group">
                                                                                    <button
                                                                                        class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                        type="button" data-toggle="dropdown"
                                                                                        aria-haspopup="true"
                                                                                        aria-expanded="false"><i
                                                                                            class="fal fa-ellipsis-v"></i></button>
                                                                                    <div class="dropdown-menu dropdown-menu-right">
                                                                                        @if (!$account->is_active)
                                                                                            @can('approve account')
                                                                                                <a class="dropdown-item"
                                                                                                    href="javascript:makeActive({{ $account->id }})">Make
                                                                                                    Active</a>
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
                                                                                                    href="javascript:makeInactive({{ $account->id }})">Make
                                                                                                    Inactive</a>
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
                                                                            @else
                                                                                <div class="form-group">
                                                                                    <button
                                                                                        class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                        type="button" data-toggle="dropdown"
                                                                                        aria-haspopup="true"
                                                                                        aria-expanded="false"><i
                                                                                            class="fal fa-ellipsis-v"></i></button>
                                                                                    @can('delete records')
                                                                                        <div class="dropdown-menu dropdown-menu-right">
                                                                                            <a class="dropdown-item"
                                                                                                href="javascript:deleteAccount({{ $account->id }})">Delete</a>
                                                                                        </div>
                                                                                    @endcan
                                                                                </div>
                                                                            @endif
                                                                        @endcan
                                                                    @elseif (!$account->is_active)
                                                                        <div class="form-group">
                                                                            <button
                                                                                class="btn btn-outline-warning btn-sm btn-icon rounded-circle waves-effect waves-themed"
                                                                                type="button" data-toggle="dropdown"
                                                                                aria-haspopup="true" aria-expanded="false"><i
                                                                                    class="fal fa-ellipsis-v"></i></button>
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
                                        action="{{ url('/client-management/clients/update/individual/account-currency') }}"
                                        enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="client_id" value="{{ $client->id }}">
                                        <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
                                        <input type="hidden" name="is_crypto" value="true">
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
                                                                        value="{{ $cryptoCurrency->id }}" name="currency[]">
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

                                            @include('feeengine::partials.client-fee-tier-group', [
                                                'client' => $client,
                                            ])
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

                        <div class="tab-pane fade" id="related-files" role="tabpanel" aria-labelledby="related-files">
                            <form method="post"
                                action="{{ url('/client-management/clients/update/individual/files') }}"
                                enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="client_id" value="{{ $client->id }}">
                                <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
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
                                                    @php $existingFiles = $client->individual->files->where('file_id', $file->id)->where('is_deleted', false) @endphp

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

                                                        {{-- @if (!$existingFiles->count() > 0) --}}
                                                        <td><input type="file"
                                                                @if ($file->maxCount) onchange="setFileCount(this)" @endif
                                                                @if ($file->isMultiple) multiple name="file-{{ $file->id }}[]" @else name="file-{{ $file->id }}" @endif>
                                                            {{-- </td>
                                                        @else
                                                            <td> --}}
                                                            @foreach ($existingFiles as $efile)
                                                                <div class="row p-2">
                                                                    <div class="col-md-8"><a href="{{ $efile->url }}"
                                                                            target="_blank"><i
                                                                                class="fal fa-file-alt"></i> View</a>
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
                                                            {{-- @if ($file->isMultiple)
                                                                    <hr>
                                                                    <input type="file"
                                                                        @if ($file->maxCount) onchange="setFileCount(this)" @endif
                                                                        @if ($file->isMandatory) required @endif
                                                                        @if ($file->isMultiple) multiple name="file-{{ $file->id }}[]" @else name="file-{{ $file->id }}" @endif>
                                                                @endif --}}
                                                        </td>
                                                        {{-- @endif --}}
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
                                            <button type="submit" class="btn btn-primary">Save</button>
                                            @include('client-management.partials.approve-reject-button')
                                        </div>
                                    </div>
                                    <div class="col-md-2 d-flex justify-content-end">
                                        <div class="form-group">
                                            <hr>
                                            @if ($client->individual->files->count() > 0)
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
                                            <i class="fal fa-shield-check mr-2"></i>Sumsub Verification
                                        </h5>
                                        {{-- LIVE VERIFICATION --}}
                                        @if (isset($transaction_log) && count($transaction_log) > 0)
                                            @php
                                                $latestLogDetails = json_decode($transaction_log[0]->details, true);
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
                                        @if (!empty($clientKYC) && !empty($clientKYC->sumsub_applicant_id) && !empty($sumsubClientId))
                                            <a href="https://cockpit.sumsub.com/checkus/applicant/{{ $clientKYC->sumsub_applicant_id }}/basicInfo?clientId={{ $sumsubClientId }}"
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
                        </div>

                        @if ($client->type == 'individual')
                            <div class="tab-pane fade" id="client-risk-rating" role="tabpanel"
                                aria-labelledby="client-risk-rating">
                                @include('client-management.partials.individual-client-risk-rating')
                            </div>
                        @endif

                    </div>

                    @if (in_array('OnboardingFee', $enabledModules) && $client->status == 'Approved')
                        <div class="tab-pane fade" id="onboarding-fee" role="tabpanel" aria-labelledby="onboarding-fee">
                            @include('client-management.partials.onboarding-fee')
                        </div>
                    @endif

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
    <form name="approve-individual" id="approve-individual" method="post"
        action="{{ url('/client-management/clients/individual/approve') }}" enctype="multipart/form-data">
        @csrf
        <input type="hidden" name="client_id" value="{{ $client->id }}">
        <input type="hidden" name="individual_id" value="{{ $client->individual->id }}">
    </form>

    <form method="POST" name="delete-file" id="delete-file" action="{{ url('/clients/individual/files/delete') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
        <input type="hidden" name="file_id" id="file_id" value="">
    </form>

    <form method="POST" name="delete-account" id="delete-account"
        action="{{ url('/client-management/clients/individual/accounts/delete') }}">
        @csrf
        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
        <input type="hidden" name="account_id" id="account_id" value="">
    </form>

    <form method="POST" name="delete-draft-account" id="delete-draft-account"
        action="{{ url('/client-management/clients/individual/draft-accounts/delete') }}">
        @csrf
        <input type="hidden" name="draft_account_id" id="draft_account_id" value="">
    </form>

    <div class="modal fade" id="reject-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">Reject Client</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body">
                    <form action="{{ url('/client-management/clients/individual/reject') }}" method="post">
                        @csrf
                        <input type="hidden" name="client_id" id="client_id" value="{{ $client->id }}">
                        <div class="form-group">
                            <label class="form-label" for="reject_reason">Reject Reason</label>
                            <textarea required class="form-control" name="reject_reason" id="reject_reason" cols="30" rows="2"></textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-danger">Reject</button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
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
                    <button onclick="cancelCloseAccountModal();" type="button" class="close" data-dismiss="modal"
                        aria-label="Close">
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
    <div class="modal fade" id="sumsub-log-modal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title ">View Sumsub Log</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                        onclick="closeSumsubModal();">
                        <span aria-hidden="true"><i class="fal fa-times"></i></span>
                    </button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    @if (isset($transaction_log) && count($transaction_log) > 0)
                        <h4 class="mt-2">Sumsub Webhook Log</h4>
                        @foreach ($transaction_log as $log)
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
