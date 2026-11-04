<?php

use App\Helpers\PushNotificationHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Helpers\EmailNotification;
use App\Modules\Aml\Controllers\AmlRuleController;

Route::get("/health", function () {
    return true;
});

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/logout', [\App\Http\Controllers\LogoutController::class, 'logout']);

Auth::routes(['verify' => true]);
Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::group(['middleware' => ['auth', 'prevent.2fa.access']], function () {
    Route::get('/user/2fa', [App\Http\Controllers\TwoFactorController::class, 'show2faForm'])->name('2fa.form');
    Route::post('/user/2fa', [App\Http\Controllers\TwoFactorController::class, 'verify2fa'])->name('2fa.verify')->middleware('prevent.duplicate');
    Route::get('/user/2fa/otp', [App\Http\Controllers\TwoFactorController::class, 'sendOtpToEmail'])->name('2fa.sendOtp');
    Route::post('/2fa', [App\Http\Controllers\TwoFAController::class, 'store'])->name('2fa.post')->middleware('throttle:5,1');
    Route::get('/2fa/cancel', [App\Http\Controllers\TwoFAController::class, 'cancel']);
});

Route::post('/register-new-client', [App\Http\Controllers\ClientController::class, 'storeClient']);

//client kyc via link
Route::get('/client-kyc/{hash}', [\App\Http\Controllers\DraftClientController::class, 'doKycNewPortal']);
Route::post('/client-kyc/liveness-check', [App\Http\Controllers\DraftClientController::class, 'kycLivenessCheck']);
Route::post('/client-kyc/upload-passport', [App\Http\Controllers\DraftClientController::class, 'uploadPassport']);
Route::get('/kyc-success', [App\Http\Controllers\DraftClientController::class, 'kycSuccess']);

Route::group(['middleware' => ['auth', '2fa']], function () {

    // User management
    Route::group(['middleware' => ['can:manage users']], function () {

        Route::get('/user-management/users', [App\Http\Controllers\UserController::class, 'index']);
        Route::post('/user-management/users/search', [App\Http\Controllers\UserController::class, 'index']);
        Route::get('/user-management/back-office-users', [App\Http\Controllers\UserController::class, 'backOfficeUsers']);
        Route::post('/user-management/back-office-users/search', [App\Http\Controllers\UserController::class, 'backOfficeUsers']);
        Route::get('/user-management/users/new', [App\Http\Controllers\UserController::class, 'add']);
        Route::post('/user-management/users/store', [App\Http\Controllers\UserController::class, 'store']);
        Route::get('/user-management/users/edit/{id}', [App\Http\Controllers\UserController::class, 'edit']);
        Route::post('/user-management/users/update', [App\Http\Controllers\UserController::class, 'update']);
        Route::post('/user-management/users/delete', [App\Http\Controllers\UserController::class, 'delete']);
        Route::post('/user-management/users/change-credentials', [App\Http\Controllers\UserController::class, 'changeCredentials']);
        Route::post('/user-management/users/lock-account', [App\Http\Controllers\UserController::class, 'lockuserAccount']);
        Route::post('/user-management/users/unlock-account', [App\Http\Controllers\UserController::class, 'unLockuserAccount']);
        Route::post('/user-management/users/generate-password', [App\Http\Controllers\UserController::class, 'generatePassword']);
        Route::post('/user-management/users/verify-user', [App\Http\Controllers\UserController::class, 'verifyUser']);
        Route::get('/user-management/users/impersonate/{id}', [App\Http\Controllers\UserController::class, 'impersonate']);
        Route::get('/user-management/link-user', [App\Http\Controllers\UserController::class, 'userClients']);
        Route::post('/user-management/link-user/search', [App\Http\Controllers\UserController::class, 'userClients']);
        Route::post('/user-management/link-user/store', [App\Http\Controllers\UserController::class, 'linkUserStore']);
        Route::post('/user-management/link-user/delete', [App\Http\Controllers\UserController::class, 'linkUserDelete']);

        // User management - roles
        Route::get('/user-management/roles', [App\Http\Controllers\RoleController::class, 'index']);
        Route::get('/user-management/roles/new', [App\Http\Controllers\RoleController::class, 'add']);
        Route::post('/user-management/roles/store', [App\Http\Controllers\RoleController::class, 'store']);
        Route::get('/user-management/roles/edit/{id}', [App\Http\Controllers\RoleController::class, 'edit']);
        Route::post('/user-management/roles/update', [App\Http\Controllers\RoleController::class, 'update']);
        Route::post('/user-management/roles/delete', [App\Http\Controllers\RoleController::class, 'delete']);
        Route::get('/user-management/roles/permissions/{id}', [App\Http\Controllers\RoleController::class, 'permissions']);
        Route::post('/user-management/roles/permissions/storePermission', [App\Http\Controllers\RoleController::class, 'storePermission']);

        // User management - permissions
        Route::get('/user-management/permissions', [App\Http\Controllers\PermissionController::class, 'index']);
        Route::get('/user-management/permissions/new', [App\Http\Controllers\PermissionController::class, 'add']);
        Route::post('/user-management/permissions/store', [App\Http\Controllers\PermissionController::class, 'store']);
        Route::get('/user-management/permissions/edit/{id}', [App\Http\Controllers\PermissionController::class, 'edit']);
        Route::post('/user-management/permissions/update', [App\Http\Controllers\PermissionController::class, 'update']);
        Route::post('/user-management/permissions/groups/store', [App\Http\Controllers\PermissionController::class, 'storeGroup']);
        Route::post('/user-management/permissions/groups/update', [App\Http\Controllers\PermissionController::class, 'updateGroup']);

        // User trace - Active Users
        Route::get('/user-trace/active-users', [App\Http\Controllers\UserTraceController::class, 'getActiveUsers']);
        // User trace - User Access Logs
        Route::get('/user-trace/user-access-logs', [App\Http\Controllers\UserTraceController::class, 'getUserAccessLogs']);
        Route::post('/user-trace/user-access-logs/search', [App\Http\Controllers\UserTraceController::class, 'getUserAccessLogs']);
        // User trace - User Journey Logs
        Route::get('/user-trace/user-journey-logs', [App\Http\Controllers\UserTraceController::class, 'getUserJourneyLogs']);
        Route::match(['get', 'post'], '/user-trace/user-journey-logs/search', [App\Http\Controllers\UserTraceController::class, 'getUserJourneyLogs']);
        Route::get('/user-trace/journey-tab/{tab}', [App\Http\Controllers\UserTraceController::class, 'getUserJourneyTab']);
        Route::get('/user-trace/user-journey-visit/{id}', [App\Http\Controllers\UserTraceController::class, 'viewUserJourneyVisit']);
        Route::post('/user-trace/journey-beacon', [App\Http\Controllers\UserTraceController::class, 'storeJourneyBeacon']);
        // User trace - User Audit Logs
        Route::get('/user-trace/user-audits', [App\Http\Controllers\UserTraceController::class, 'getUserAudits']);
        Route::post('/user-trace/user-audits/search', [App\Http\Controllers\UserTraceController::class, 'getUserAudits']);
        Route::get('/user-trace/user-audits/view/{id}', [\App\Http\Controllers\UserTraceController::class, 'viewAudit']);
    });

    # User profile management
    Route::get('/profile', [App\Http\Controllers\UserController::class, 'profile']);
    Route::post('/profile-update', [App\Http\Controllers\UserController::class, 'profileUpdate']);

    # User change password
    Route::get('/change-password', [App\Http\Controllers\UserController::class, 'changePassword']);
    Route::post('/update-password', [App\Http\Controllers\UserController::class, 'updatePassword']);

    # Client management
    Route::group(['middleware' => ['can:manage clients']], function () {

        Route::get('/client-management/clients', [App\Http\Controllers\ClientController::class, 'index']);
        Route::post('/client-management/clients/search', [App\Http\Controllers\ClientController::class, 'index']);
        Route::get('/client-management/client-profile-wise-generate-excel', [App\Http\Controllers\ClientController::class, 'generateClientProfileWiseExcel']);
        Route::get('/client-management/pending-clients', [App\Http\Controllers\ClientController::class, 'pendingClients']);
        Route::get('/client-management/rejected-clients', [App\Http\Controllers\ClientController::class, 'rejectedClients']);
        Route::get('/client-management/closed-clients', [App\Http\Controllers\ClientController::class, 'closedClients']);
        Route::get('/client-management/draft-clients', [App\Http\Controllers\ClientController::class, 'draftClients']);
        Route::post('/client-management/clients/submit-for-approval', [App\Http\Controllers\ClientController::class, 'submitForApproval']);
        Route::get('/client-management/clients/new/company/{is_bank}', [App\Http\Controllers\ClientController::class, 'addCompany']);
        Route::get('/client-management/clients/new/company/{is_bank}/{parent_client_id}', [App\Http\Controllers\ClientController::class, 'addCompany']);
        Route::post('/client-management/clients/store/company', [App\Http\Controllers\ClientController::class, 'storeCompany']);
        Route::get('/client-management/client-users', [App\Http\Controllers\ClientController::class, 'userClients']);
        // Disabling Delete-Client option on CIB's request
        Route::post('/client-management/clients/delete', [App\Http\Controllers\ClientController::class, 'deleteClient']);
        Route::post('/client-management/clients/company/director/request-kyc', [App\Http\Controllers\ClientDirectorController::class, 'requestKYC']);
        Route::get('/client-management/clients/company/edit/{client_id}', [App\Http\Controllers\ClientController::class, 'editCompany']);
        Route::get('/client-management/clients/company/accordion/{accordion_id?}', [App\Http\Controllers\ClientController::class, 'accordion']);
        Route::get('/client-management/closed-clients/company/view/{client_id}', [App\Http\Controllers\ClientController::class, 'editCompany']);
        Route::get('/client-management/clients/company/edit/{client_id}/{nid}', [App\Http\Controllers\ClientController::class, 'editCompany']);
        Route::post('/client-management/clients/update/company', [App\Http\Controllers\ClientController::class, 'updateCompany']);
        Route::post('/client-management/clients/update/company/contact', [App\Http\Controllers\ClientController::class, 'updateCompanyContact']);
        Route::post('/client-management/clients/update/company/account-currency', [App\Http\Controllers\ClientController::class, 'updateCompanyCurrency']);
        Route::post('/client-management/clients/update/company/files', [App\Http\Controllers\ClientController::class, 'updateCompanyFiles']);
        Route::post('/client-management/clients/company/accounts/delete', [App\Http\Controllers\ClientController::class, 'deleteCompanyAccount']);
        Route::post('/client-management/clients/company/draft-accounts/delete', [App\Http\Controllers\ClientController::class, 'deleteDraftAccount']);
        Route::get('/client-management/clients/company/accounts/approve/{id}', [App\Http\Controllers\ClientController::class, 'approveCompanyAccount']);
        Route::get('/client-management/clients/company/accounts/reject/{id}', [App\Http\Controllers\ClientController::class, 'rejectCompanyAccount']);
        Route::get('/client-management/clients/accounts/maintenance-fee/{id}/{maintenance_fee_amount}', [App\Http\Controllers\ClientController::class, 'updateMaintenaceFee']);


        Route::get('/client-management/clients/new/individual', [App\Http\Controllers\ClientController::class, 'addIndividual']);
        Route::get('/client-management/clients/new/individual/{parent_client_id}', [App\Http\Controllers\ClientController::class, 'addIndividual']);
        Route::post('/client-management/clients/store/individual', [App\Http\Controllers\ClientController::class, 'storeIndividual']);
        Route::get('/client-management/clients/individual/edit/{client_id}/', [App\Http\Controllers\ClientController::class, 'editIndividual']);
        Route::get('/client-management/closed-clients/individual/view/{client_id}/', [App\Http\Controllers\ClientController::class, 'editIndividual']);
        Route::get('/client-management/clients/individual/edit/{client_id}/{nid}', [App\Http\Controllers\ClientController::class, 'editIndividual']);
        Route::post('/client-management/clients/update/individual', [App\Http\Controllers\ClientController::class, 'updateIndividual']);
        Route::post('/client-management/clients/upload-selfie-photo', [App\Http\Controllers\ClientController::class, 'uploadSelfiePhoto']);
        Route::post('/client-management/clients/update/individual/employer', [App\Http\Controllers\ClientController::class, 'updateIndividualEmployer']);
        Route::post('/client-management/clients/update/individual/account-currency', [App\Http\Controllers\ClientController::class, 'updateIndividualCurrency']);
        Route::post('/client-management/clients/update/individual/files', [App\Http\Controllers\ClientController::class, 'updateIndividualFiles']);
        Route::post('/client-management/clients/update/fee-tier-group', [App\Modules\FeeEngine\Controllers\FeeAssignmentController::class, 'assignFromClient']);
        Route::post('/client-management/clients/link-client', [App\Http\Controllers\ClientController::class, 'linkClient']);
        Route::post('/client-management/clients/unlink-client', [App\Http\Controllers\ClientController::class, 'unlinkClient']);
        Route::post('/client-management/clients/individual/accounts/delete', [App\Http\Controllers\ClientController::class, 'deleteIndividualAccount']);
        Route::post('/client-management/clients/individual/draft-accounts/delete', [App\Http\Controllers\ClientController::class, 'deleteDraftAccount']);
        Route::get('/client-management/clients/individual/accounts/approve/{id}', [App\Http\Controllers\ClientController::class, 'approveIndividualAccount']);
        Route::get('/client-management/clients/individual/accounts/reject/{id}', [App\Http\Controllers\ClientController::class, 'rejectIndividualAccount']);
        Route::get('/client-management/client-notifications', [App\Http\Controllers\ClientController::class, 'clientNotifications']);
        Route::post('/client-management/send-client-notifications', [App\Http\Controllers\ClientController::class, 'sendClientNotifications']);
        Route::get('/client-management/clients/download-files/{id}', [App\Http\Controllers\ClientController::class, 'downloadFiles']);
        Route::post('/client-management/clients/update/client-risk-rating', [App\Http\Controllers\ClientController::class, 'updateClientRiskRating']);
        Route::get('/client-management/clients/download-risk-rating/{clientId}', [App\Http\Controllers\ClientController::class, 'downloadRiskRating']);
        Route::post('/client-management/clients/create-iban', [App\Http\Controllers\IBANController::class, 'create']);
        Route::get('/client-management/clients/zenus-account-wizard', [App\Http\Controllers\ZenusAccountWizardController::class, 'show']);
        Route::post('/client-management/clients/zenus-account-wizard', [App\Http\Controllers\ZenusAccountWizardController::class, 'run']);
        Route::post('/client-management/clients/zenus-account-wizard/clear', [App\Http\Controllers\ZenusAccountWizardController::class, 'clear']);
        Route::get('/client-management/clients/zenus-account-wizard/fix', [App\Http\Controllers\ZenusAccountWizardController::class, 'fixForm']);
        Route::post('/client-management/clients/zenus-account-wizard/fix', [App\Http\Controllers\ZenusAccountWizardController::class, 'saveFix']);
        Route::post('/client-management/clients/get-zenus-vrn', [App\Http\Controllers\IBANController::class, 'getZenusAccountVRN']);
        Route::get('/client-management/clients/account-confirmation-letter/{account_id}', [App\Http\Controllers\LorumController::class, 'accountConfirmationLetter']);
        Route::get('/client-management/clients/synch-iban/{account_id}', [App\Http\Controllers\OpenPaydController::class, 'synchIBAN']);
        Route::get('/client-management/clients/download-risk-rating/{clientId}/{recordId?}', [App\Http\Controllers\ClientController::class, 'downloadRiskRating']);
        Route::get('/client-management/clients/{clientId}/risk-rating-history', [App\Http\Controllers\ClientController::class, 'getRiskRatingHistory']);
        Route::get('/client-management/clients/risk-rating/{recordId}', [App\Http\Controllers\ClientController::class, 'getRiskRatingRecord']);
        Route::post('/client-management/clients/request-sumsub-kyc', [App\Http\Controllers\ClientController::class, 'requestSumsubKyc']);

        Route::post('/client-management/accounts/update/virtual-bank-provider', [App\Http\Controllers\ClientController::class, 'overrideVirtualBankProvider']);

        Route::post('/client-management/clients/close', [\App\Http\Controllers\ClientController::class, 'closeClient']);
        Route::post('/client-management/clients/restore', [\App\Http\Controllers\ClientController::class, 'restoreClient']);

        # Client approve / reject
        Route::post('/client-management/clients/individual/approve', [\App\Http\Controllers\ClientController::class, 'approveClient']);
        Route::post('/client-management/clients/individual/reject', [\App\Http\Controllers\ClientController::class, 'rejectClient']);
        Route::post('/client-management/clients/company/approve', [\App\Http\Controllers\ClientController::class, 'approveClient']);
        Route::post('/client-management/clients/company/reject', [\App\Http\Controllers\ClientController::class, 'rejectClient']);
        Route::get('/client-management/pending-changes', [\App\Http\Controllers\ClientController::class, 'getModelChanges']);
        Route::post('/client-management/pending-changes/search', [\App\Http\Controllers\ClientController::class, 'getModelChanges']);

        Route::post('/client-management/clients/verify-client-kyc', [\App\Http\Controllers\ClientController::class, 'verifyClientKYC']);
        Route::post('/client-management/clients/worldCheck-report-request', [\App\Http\Controllers\ClientController::class, 'worldCheckReportRequest']);
        Route::post('/client-management/clients/worldCheck-report-get-status-and-save', [\App\Http\Controllers\ClientController::class, 'worldCheckReportGetStatusAndSave']);

        # Client Compare passport and selfie
        Route::post('/client-management/clients/compare-faces', [\App\Http\Controllers\ClientController::class, 'compareFaces']);

        # KYC request for already approved clients
        Route::post('/client-management/clients/request-client-kyc', [\App\Http\Controllers\ClientController::class, 'requestClientKYC']);

        # Onboarding fee request
        Route::post('/client-management/clients/onboarding-fee-create-payment-link', [\App\Http\Controllers\ClientController::class, 'onboardingFeeCreatePaymentLink']);

        # Onboarding fee find transaction
        Route::post('/client-management/clients/request-onboarding-fee-find-transaction', [\App\Http\Controllers\ClientController::class, 'onboardingFeeFindTransaction']);

        # Onboarding fee refund transaction
        Route::post('/client-management/clients/request-onboarding-fee-refund-payment', [\App\Http\Controllers\ClientController::class, 'onboardingFeeRefundPayment']);

        # fingate card create
        Route::post('/client-management/clients/fingate-card-create', [\App\Http\Controllers\ClientController::class, 'fingateCardCreate']);
        Route::post('/client-management/clients/fingate-find-card', [\App\Http\Controllers\ClientController::class, 'fingateFindCard']);
    });

    # client beneficiary management
    Route::group(['middleware' => ['can:manage finance']], function () {

        Route::get('/finance-management/beneficiaries/', [\App\Http\Controllers\ClientBeneficiaryController::class, 'getAllBeneficiaries']);
        Route::get('/finance-management/beneficiaries/view/{id}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'viewBeneficiary']);
        Route::get('/finance-management/beneficiaries/view/{id}/{nid}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'viewBeneficiary']);
        Route::get('/finance-management/beneficiaries/edit/{id}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'editBeneficiary']);
        Route::post('/finance-management/beneficiaries/update/{id}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'updateBeneficiary']);
        Route::post('/finance-management/beneficiaries/approve/', [\App\Http\Controllers\ClientBeneficiaryController::class, 'approveBeneficiary']);
        Route::post('/finance-management/beneficiaries/reject/', [\App\Http\Controllers\ClientBeneficiaryController::class, 'rejectBeneficiary']);
        Route::post('/finance-management/beneficiaries/delete/', [\App\Http\Controllers\ClientBeneficiaryController::class, 'deleteBeneficiary']);
        Route::get('/finance-management/beneficiaries/link-to-virtual-bank/{id}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'linkToVirtualBank']);

        # Finance management
        Route::get('/finance-management/transactions', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::post('/finance-management/transactions/search', [App\Http\Controllers\TransactionController::class, 'index']);
        Route::get('/finance-management/transaction/musd-transactions', [App\Http\Controllers\TransactionController::class, 'musdTransactionsList']);
        Route::get('/finance-management/transaction/musd-transactions/search', [App\Http\Controllers\TransactionController::class, 'musdTransactionsList']);

        Route::get('/finance-management/transaction/maintenance-fees', [App\Http\Controllers\TransactionController::class, 'maintenanceFeesList']);
        Route::post('/finance-management/transaction/maintenance-fees/search', [App\Http\Controllers\TransactionController::class, 'maintenanceFeesList']);
        Route::get('/finance-management/back-office-pending-transactions', [App\Http\Controllers\TransactionController::class, 'backOfficePendingTransactions']);
        Route::get('/finance-management/back-office-rejected-transactions', [App\Http\Controllers\TransactionController::class, 'backOfficeRejectedTransactions']);
        Route::get('/finance-management/transactions/new/cheque', [App\Http\Controllers\TransactionController::class, 'addCheque']);
        Route::get('/finance-management/transactions/new/tt', [App\Http\Controllers\TransactionController::class, 'addTT']);
        Route::post('/finance-management/transactions/new/beneficiary', [App\Http\Controllers\TransactionController::class, 'createNewBeneficiary']);
        Route::get('/finance-management/transactions/get-client-beneficiaries/{client_id}/{currency_id}', [App\Http\Controllers\TransactionController::class, 'getClientBeneficiaries']);
        Route::get('/finance-management/transactions/get-iban-info/{account_id}', [\App\Http\Controllers\IBANController::class, 'ibanInfo']);
        Route::get('/finance-management/transactions/get-baneficiary-info/{beneficiary_id}', [\App\Http\Controllers\OpenPaydController::class, 'baneficiaryInfo']);
        Route::get('/finance-management/transactions/new/internal', [App\Http\Controllers\TransactionController::class, 'addInternal']);

        Route::post('/finance-management/transactions/store/cheque', [App\Http\Controllers\TransactionController::class, 'storeCheque']);
        Route::get('/finance-management/transactions/edit/cheque/{id}', [App\Http\Controllers\TransactionController::class, 'editCheque']);
        Route::post('/finance-management/transactions/update/cheque', [App\Http\Controllers\TransactionController::class, 'updateCheque']);
        Route::post('/finance-management/transactions/delete', [App\Http\Controllers\TransactionController::class, 'deleteTransaction']);
        Route::post('/finance-management/transaction/maintenance-fees/delete', [App\Http\Controllers\TransactionController::class, 'deleteMaintenanceFeeTransactions']);

        Route::post('/finance-management/transactions/store/tt', [App\Http\Controllers\TransactionController::class, 'storeTT']);
        Route::get('/finance-management/transactions/edit/tt/{id}', [App\Http\Controllers\TransactionController::class, 'editTT']);
        Route::get('/finance-management/transaction/maintenance-fees/edit/tt/{id}', [App\Http\Controllers\TransactionController::class, 'editTT']);
        Route::post('/finance-management/transactions/update/tt', [App\Http\Controllers\TransactionController::class, 'updateTT']);
        Route::post('/finance-management/transactions/update/tt-with-fees', [App\Http\Controllers\TransactionController::class, 'updateTTWithFees']);
        Route::post('/finance-management/transactions/files/delete', [App\Http\Controllers\TransactionController::class, 'deletePaymentFile']);


        Route::post('/finance-management/transactions/store/internal', [App\Http\Controllers\TransactionController::class, 'storeInternal']);
        Route::get('/finance-management/transactions/edit/internal/{id}', [App\Http\Controllers\TransactionController::class, 'editInternal']);
        Route::get('/finance-management/transactions/edit/internal/{id}/{nid}', [App\Http\Controllers\TransactionController::class, 'editInternal']);
        Route::post('/finance-management/transactions/update/internal', [App\Http\Controllers\TransactionController::class, 'updateInternal']);
        Route::post('/finance-management/transactions/update/internal-with-fees', [App\Http\Controllers\TransactionController::class, 'updateInternalWithFees']);

        Route::post('/finance-management/transactions/save-changes', [App\Http\Controllers\TransactionController::class, 'saveTransactionChanges']);
        Route::post('/finance-management/transactions/upload-documents', [App\Http\Controllers\TransactionController::class, 'uploadDocuments']);

        Route::post('/finance-management/transactions/approve', [App\Http\Controllers\TransactionController::class, 'approveTransaction']);
        Route::post('/finance-management/transactions/approve-cheque', [App\Http\Controllers\TransactionController::class, 'approveChequeTransaction']);
        Route::post('/finance-management/transactions/reject', [App\Http\Controllers\TransactionController::class, 'rejectTransaction']);

        Route::get('/finance-management/reviewed-transactions', [App\Http\Controllers\TransactionController::class, 'getReviewedTransactionRequests']);
        Route::get('/finance-management/pending-transactions', [App\Http\Controllers\TransactionController::class, 'getTransactionRequests']);
        Route::get('/finance-management/rejected-transactions', [App\Http\Controllers\TransactionController::class, 'getRejectedTransaction']);
        Route::get('/finance-management/pending-transactions/view/internal/{id}', [App\Http\Controllers\TransactionController::class, 'viewInternalPendingTransaction']);
        Route::get('/finance-management/pending-transactions/view/tt/{id}', [App\Http\Controllers\TransactionController::class, 'viewTTPendingTransaction']);
        Route::post('/finance-management/pending-transaction/reject/tt', [App\Http\Controllers\TransactionController::class, 'rejectTTPendingTransaction']);
        Route::post('/finance-management/pending-transaction/reject/internal', [App\Http\Controllers\TransactionController::class, 'rejectInternalPendingTransaction']);
        Route::post('/finance-management/pending-transaction/approve/tt', [App\Http\Controllers\TransactionController::class, 'approveTTPendingTransaction']);
        Route::post('/finance-management/pending-transaction/approve/internal', [App\Http\Controllers\TransactionController::class, 'approveInternalPendingTransaction']);
        Route::post('/finance-management/pending-transaction/delete', [App\Http\Controllers\TransactionController::class, 'deletePendingTransaction']);
        Route::get('/finance-management/pending-transactions/print/{id}', [App\Http\Controllers\TransactionController::class, 'printTransactionRequest']);
        Route::get('/finance-management/transactions/print/{id}', [App\Http\Controllers\TransactionController::class, 'printTransaction']);

        Route::get('/finance-management/initial-review-transactions', [App\Http\Controllers\TransactionController::class, 'getInitialReviewRequests']);
        Route::get('/finance-management/initial-review-transactions/view/{id}', [App\Http\Controllers\TransactionController::class, 'viewInitialReviewTransaction']);
        Route::post('/finance-management/initial-review-transactions/approve-review', [App\Http\Controllers\TransactionController::class, 'approveReviewForTransaction']);
        Route::post('/finance-management/transactions/save-note', [App\Http\Controllers\TransactionController::class, 'saveTransactionNote']);
        Route::get('/finance-management/transactions/get-notes/{transaction_id}', [App\Http\Controllers\TransactionController::class, 'getTransactionNotes']);

        # Banks management
        Route::get('/finance-management/banks', [\App\Http\Controllers\BankController::class, 'index']);
        Route::get('/finance-management/banks/new', [\App\Http\Controllers\BankController::class, 'addBank']);
        Route::post('/finance-management/banks/store', [\App\Http\Controllers\BankController::class, 'storeBank']);
        Route::get('/finance-management/banks/edit/{id}', [\App\Http\Controllers\BankController::class, 'editBank']);
        Route::post('/finance-management/banks/update', [\App\Http\Controllers\BankController::class, 'updateBank']);
        Route::post('/finance-management/banks/delete', [\App\Http\Controllers\BankController::class, 'deleteBank']);
        Route::post('/finance-management/banks/account/store', [\App\Http\Controllers\BankController::class, 'storeBankAccount']);
        Route::post('/finance-management/banks/account/delete', [\App\Http\Controllers\BankController::class, 'deleteBankAccount']);

        # Bank SSI management
        Route::get('/finance-management/bank-ssi', [\App\Http\Controllers\BankController::class, 'indexBankSSI']);
        Route::get('/finance-management/bank-ssi/new', [\App\Http\Controllers\BankController::class, 'addBankSSI']);
        Route::post('/finance-management/bank-ssi/store', [\App\Http\Controllers\BankController::class, 'storeBankSSI']);
        Route::get('/finance-management/bank-ssi/edit/{id}/{ssi_id}', [\App\Http\Controllers\BankController::class, 'editBankSSI']);
        Route::post('/finance-management/bank-ssi/update', [\App\Http\Controllers\BankController::class, 'updateBankSSI']);
        Route::post('/finance-management/bank-ssi/delete', [\App\Http\Controllers\BankController::class, 'deleteBankSSI']);

        # currency management
        Route::get('/finance-management/currencies', [\App\Http\Controllers\CurrencyController::class, 'index']);
        Route::get('/finance-management/currencies/new', [\App\Http\Controllers\CurrencyController::class, 'addCurrency']);
        Route::post('/finance-management/currencies/store', [\App\Http\Controllers\CurrencyController::class, 'storeCurrency']);
        Route::get('/finance-management/currencies/edit/{id}', [\App\Http\Controllers\CurrencyController::class, 'editCurrency']);
        Route::post('/finance-management/currencies/update', [\App\Http\Controllers\CurrencyController::class, 'updateCurrency']);
        Route::post('/finance-management/currencies/delete', [\App\Http\Controllers\CurrencyController::class, 'deleteCurrency']);
    });

    # Reports management

    #super admin
    Route::group(['middleware' => ['can:reports management']], function () {
        Route::get('/reports-management/client/risk', [App\Http\Controllers\ReportController::class, 'clientRisk']);
        Route::post('/reports-management/client/risk/search', [App\Http\Controllers\ReportController::class, 'clientRisk']);
        Route::get('/reports-management/client/risk/search', [App\Http\Controllers\ReportController::class, 'clientRisk']);
        Route::get('/reports-management/client/currency-wise', [\App\Http\Controllers\ReportController::class, 'currencyWiseV2']);
        Route::post('/reports-management/client/currency-wise-generate', [\App\Http\Controllers\ReportController::class, 'currencyWiseSearchV2']);
        Route::get('/reports-management/client/daily-currency-wise', [\App\Http\Controllers\ReportController::class, 'dailyCurrencyWise']);
        Route::post('/reports-management/client/daily-currency-wise', [\App\Http\Controllers\ReportController::class, 'dailyCurrencyWise']);

        Route::get('/reports-management/client/account-wise', [\App\Http\Controllers\ReportController::class, 'accountWise']);
        Route::post('/reports-management/client/account-wise-generate', [\App\Http\Controllers\ReportController::class, 'accountWiseSearch']);
        Route::get('/reports-management/client/all-clients', [\App\Http\Controllers\ReportController::class, 'allClientsWise']);
        Route::get('/reports-management/client/daily-all-clients', [\App\Http\Controllers\ReportController::class, 'dailyAllClientsWiseV2']);
        Route::post('/reports-management/client/daily-all-clients', [\App\Http\Controllers\ReportController::class, 'dailyAllClientsWiseV2']);

        Route::post('/reports-management/client/charge-maintenance-fee', [\App\Http\Controllers\ReportController::class, 'chargePendingAccountsForMaintenanceFee']);

        Route::get('/reports-management/client/currency-wise-excel', [\App\Http\Controllers\ReportController::class, 'currencyWiseV2Excel']);
        Route::get('/reports-management/client/currency-wise-excel/{filterApplied}', [\App\Http\Controllers\ReportController::class, 'currencyWiseV2Excel']);
        Route::get('/reports-management/client/all-clients-report', [\App\Http\Controllers\ReportController::class, 'getClientsReport']);
        Route::get('/reports-management/client/all-clients-report/{filterApplied}', [\App\Http\Controllers\ReportController::class, 'getClientsReport']);
        Route::get('/reports-management/client/account-wise-documents', [\App\Http\Controllers\ReportController::class, 'accountWiseReport']);
        Route::get('/reports-management/client-bank-reports/all-banks-report-documents', [\App\Http\Controllers\ReportController::class, 'allBankWiseReport']);
        Route::get('/reports-management/client-bank-reports/all-banks-report-documents/{filterApplied}', [\App\Http\Controllers\ReportController::class, 'allBankWiseReport']);
        Route::get('/reports-management/client/account-transactions-documents-6-months', [\App\Http\Controllers\ReportController::class, 'accountTransactionsReport6Months']);
        Route::get('/reports-management/client/account-transactions-documents-6-months/{filterApplied}', [\App\Http\Controllers\ReportController::class, 'accountTransactionsReport6Months']);
        Route::get('/reports-management/client/account-transactions-documents', [\App\Http\Controllers\ReportController::class, 'accountTransactionsReport']);
        Route::get('/reports-management/client/account-transactions-documents/{filterApplied}', [\App\Http\Controllers\ReportController::class, 'accountTransactionsReport']);

        Route::post('/reports-management/client/all-clients-generate', [\App\Http\Controllers\ReportController::class, 'allClientsWiseGenerate']);
        Route::get('/reports-management/client/user-client-wise', [\App\Http\Controllers\ReportController::class, 'userClientWise']);
        Route::post('/reports-management/client/user-client-wise-search', [\App\Http\Controllers\ReportController::class, 'userClientWiseSearch']);
        Route::get('/reports-management/client/account-transactions-6-months', [\App\Http\Controllers\ReportController::class, 'accountTransactions6Months']);
        Route::post('/reports-management/client/account-transactions-search-6-months', [\App\Http\Controllers\ReportController::class, 'accountTransactionsSearch6Months']);
        Route::get('/reports-management/client/account-transactions', [\App\Http\Controllers\ReportController::class, 'accountTransactions']);
        Route::get('/reports-management/client/account-transactions/{account_id}', [\App\Http\Controllers\ReportController::class, 'accountTransactions']);
        Route::post('/reports-management/client/account-transactions-search', [\App\Http\Controllers\ReportController::class, 'accountTransactionsSearch']);
        Route::get('/reports-management/client/client-account-statement', [\App\Http\Controllers\ReportController::class, 'clientAccountStatement']);
        Route::get('/reports-management/client/closed-client-account-statement', [\App\Http\Controllers\ReportController::class, 'closedClientsAccountStatement']);
        Route::post('/reports-management/client/client-account-statement-generate', [\App\Http\Controllers\ReportController::class, 'accountStatementGenerate']);
        Route::post('/reports-management/client/closed-client-account-statement-generate', [\App\Http\Controllers\ReportController::class, 'closedClientsAccountStatementGenerate']);
        Route::post('/reports-management/client/client-account-statement-pdf', [\App\Http\Controllers\ReportController::class, 'accountStatementPDF']);
        Route::get('/reports-management/client-bank-reports/bank-account-statement', [\App\Http\Controllers\ReportController::class, 'clientBankAccountStatement']);
        Route::post('/reports-management/client-bank-reports/bank-account-statement-generate', [\App\Http\Controllers\ReportController::class, 'clientBankAccountStatementGenerate']);
        Route::post('/reports-management/client-bank-reports/bank-account-statement-pdf', [\App\Http\Controllers\ReportController::class, 'accountStatementPDF']);
        Route::get('/reports-management/client-bank-reports/closed-bank-account-statement', [\App\Http\Controllers\ReportController::class, 'closedClientBanksAccountStatement']);
        Route::post('/reports-management/client-bank-reports/closed-bank-account-statement-generate', [\App\Http\Controllers\ReportController::class, 'closedClientBanksAccountStatementGenerate']);
        Route::get('/reports-management/client-bank-reports/all-banks-report', [\App\Http\Controllers\ReportController::class, 'allBanksWise']);
        Route::post('/reports-management/client-bank-reports/all-banks-report-generate', [\App\Http\Controllers\ReportController::class, 'allBanksWiseGenerate']);
        Route::get('/reports-management/client-bank-reports/daily-all-banks-report', [\App\Http\Controllers\ReportController::class, 'dailyAllBanksWise']);
        Route::post('/reports-management/client-bank-reports/daily-all-banks-report', [\App\Http\Controllers\ReportController::class, 'dailyAllBanksWise']);
        Route::get('/reports-management/client-bank-reports/daily-all-banks-report-documents/{filterApplied}', [\App\Http\Controllers\ReportController::class, 'dailyAllBanksWise']);
        Route::get('/reports-management/vostro-banks-reports/daily-banks-balances', [\App\Http\Controllers\ReportController::class, 'listVostroBanksReports']);
        Route::post('/reports-management/vostro-banks-reports/daily-banks-balances/search', [\App\Http\Controllers\ReportController::class, 'listVostroBanksReports']);
        Route::post('/reports-management/vostro-banks-reports/daily-banks-balances/store', [\App\Http\Controllers\ReportController::class, 'storeVostroBanksReport']);
        Route::post('/reports-management/vostro-banks-reports/daily-banks-balances/delete', [\App\Http\Controllers\ReportController::class, 'deleteVostroBanksReport']);

        # Own Banks Report
        Route::post('/reports-management/bank-account-statement-generate', [\App\Http\Controllers\ReportController::class, 'bankAccountStatementGenerate']);
        Route::post('/reports-management/bank-account-statement-pdf', [\App\Http\Controllers\ReportController::class, 'bankAccountStatementPDF']);
        Route::get('/reports-management/bank-account-statement', [\App\Http\Controllers\ReportController::class, 'bankAccountStatement']);

        # Maintenance Fee Report
        Route::get('/reports-management/maintenance-fee/revenue', [\App\Http\Controllers\ReportController::class, 'bankAccountStatement']);
        Route::post('/reports-management/maintenance-fee/revenue-generate', [\App\Http\Controllers\ReportController::class, 'bankAccountStatementGenerate']);
        Route::get('/reports-management/maintenance-fee-report', [\App\Http\Controllers\ReportController::class, 'getMaintenanceFeeReport']);
        Route::post('/reports-management/maintenance-fee-report', [\App\Http\Controllers\ReportController::class, 'getMaintenanceFeeReport']);
        Route::get('/reports-management/pending-maintenance-fee-report', [\App\Http\Controllers\ReportController::class, 'getPendingMaintenanceFeeReport']);

        #onboarding fee report
        Route::get('/reports-management/client/onboarding-fee-report', [\App\Http\Controllers\ReportController::class, 'getOnboardingFeeReport']);
        Route::post('/reports-management/client/onboarding-fee-report', [\App\Http\Controllers\ReportController::class, 'getOnboardingFeeReport']);
        Route::get('/reports-management/client/onboarding-fee-report/{id}', [\App\Http\Controllers\ReportController::class, 'showOnboardingFeeReport']);
    });


    # Client Dashboard management

    // Route::get('/client/dashboard', [\App\Http\Controllers\ClientChartController::class, "index"]);
    // Route::get('/client/charts/transaction-by-nature', [\App\Http\Controllers\ClientChartController::class, "transactionByNature"]);
    // Route::get('/client/charts/credit-vs-debit', [\App\Http\Controllers\ClientChartController::class, "creditVsDebit"]);
    // Route::get('/client/charts/amount-by-currency', [\App\Http\Controllers\ClientChartController::class, "amountByCurrency"]);

    // Route::get('/client/dashboard/transactions', [\App\Http\Controllers\ClientDashboardController::class, 'transactionsList']);
    // Route::get('/client/dashboard/account-statement', [\App\Http\Controllers\ClientDashboardController::class, 'accountStatement']);
    // Route::post('/client/dashboard/account-statement-generate', [\App\Http\Controllers\ClientDashboardController::class, 'accountStatementGenerate']);
    // Route::post('/client/dashboard/account-statement-pdf', [\App\Http\Controllers\ClientDashboardController::class, 'accountStatementPDF']);
    // Route::get('/client/dashboard/transactions/view/{id}', [\App\Http\Controllers\ClientDashboardController::class, 'transactionsView']);

    // Route::get('/client/transaction-requests/list', [\App\Http\Controllers\TRRequestController::class, 'index']);

    // Route::get('/client/transaction-requests/get-iban-info/{account_id}', [\App\Http\Controllers\OpenPaydController::class, 'ibanInfo']);
    // Route::get('/client/transaction-requests/get-baneficiary-info/{beneficiary_id}', [\App\Http\Controllers\OpenPaydController::class, 'baneficiaryInfo']);

    //openPayd
    //Route::get('/client/transaction-requests/internal/instant-transfer', [\App\Http\Controllers\OpenPaydController::class, 'internalTransfer']);

    // Route::get('/client/transaction-requests/list/{nid}', [\App\Http\Controllers\TRRequestController::class, 'index']);

    // Route::get('/client/transaction-requests/edit/{id}', [\App\Http\Controllers\TRRequestController::class, 'editTransaction']);
    // Route::post('/client/transaction-requests/deposit/update', [\App\Http\Controllers\TRRequestController::class, 'updateDepositTransaction']);
    // Route::post('/client/transaction-requests/internal/update', [\App\Http\Controllers\TRRequestController::class, 'updateInternalTransaction']);
    // Route::post('/client/transaction-requests/transfer/update', [\App\Http\Controllers\TRRequestController::class, 'updateTransferTransaction']);
    // Route::get('/client/transaction-requests/view/{id}', [App\Http\Controllers\TRRequestController::class, 'viewTransaction']);

    // Route::get('/client/transaction-requests/deposit/new', [\App\Http\Controllers\TRRequestController::class, 'deposit']);
    // Route::post('/client/transaction-requests/deposit/store', [\App\Http\Controllers\TRRequestController::class, 'depositStore']);

    // Route::get('/client/transaction-requests/internal/new', [\App\Http\Controllers\TRRequestController::class, 'internalTransfer']);


    // Route::get('/client/transaction-requests/exchange/new', [\App\Http\Controllers\TRRequestController::class, 'exchange']);
    // Route::post('/client/transaction-requests/internal/store', [\App\Http\Controllers\TRRequestController::class, 'internalTransferStore']);
    // Route::post('/client/transaction-requests/account-holder-name', [\App\Http\Controllers\TRRequestController::class, 'getAccountHolderName']);

    // Route::get('/client/transaction-requests/transfer/new', [\App\Http\Controllers\TRRequestController::class, 'newTransfer']);
    // Route::post('/client/transaction-requests/transfer/store', [\App\Http\Controllers\TRRequestController::class, 'storeTransfer']);

    // Route::get('/client/transaction-requests/otp/{id}', [\App\Http\Controllers\TROTPController::class, 'showOTP']);
    // Route::post('/client/transaction-requests/otp/validate', [\App\Http\Controllers\TROTPController::class, 'validateOTP']);
    // Route::post('/client/transaction-requests/generate/otp', [\App\Http\Controllers\TROTPController::class, 'generateOTP']);

    // Route::get('/client/transaction-requests/beneficiaries', [\App\Http\Controllers\ClientBeneficiaryController::class, 'myBeneficiaries']);
    // Route::get('/client/transaction-requests/beneficiaries/new', [\App\Http\Controllers\ClientBeneficiaryController::class, 'add']);
    // Route::get('/client/transaction-requests/beneficiaries/{nid}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'myBeneficiaries']);
    // Route::get('/client/transaction-requests/beneficiaries/view/{id}', [\App\Http\Controllers\ClientBeneficiaryController::class, 'viewMyBeneficiary']);
    // Route::post('/client/transaction-requests/beneficiaries/store', [\App\Http\Controllers\ClientBeneficiaryController::class, 'store']);

    # Admin Dashboard management
    Route::group(['middleware' => ['can:admin dashboard']], function () {

        Route::get('/dashboards/admin', [\App\Http\Controllers\AdminDashboardController::class, 'index']);
        Route::get('/dashboard/admin/charts/balance-by-currency', [\App\Http\Controllers\AdminDashboardController::class, 'balanceByCurrency']);
        Route::get('/dashboard/admin/charts/clients-by-status', [\App\Http\Controllers\AdminDashboardController::class, 'clientsByStatus']);
        Route::get('/dashboard/admin/charts/accounts-by-currency', [\App\Http\Controllers\AdminDashboardController::class, 'accountsByCurrency']);
        Route::get('/dashboard/admin/charts/accounts-by-crypto-currency', [\App\Http\Controllers\AdminDashboardController::class, 'accountsByCryptoCurrency']);
        Route::get('/dashboard/admin/charts/transactions-by-nature', [\App\Http\Controllers\AdminDashboardController::class, 'transactionsByNature']);
        Route::get('/dashboard/admin/charts/transactions-by-status', [\App\Http\Controllers\AdminDashboardController::class, 'transactionsByStatus']);
        Route::get('/dashboard/admin/charts/transactions-by-date', [\App\Http\Controllers\AdminDashboardController::class, 'transactionsbyDate']);

        // Bitpace / deposit-address routes moved to can:crypto management (below).
        // Commented so admin dashboard is no longer required for these URLs.
        // Route::get('/crypto-management/crypto-transactions/deposit-addresses', [\App\Http\Controllers\CryptoController::class, 'getDepositAddresses']);
        // Route::get('/crypto-management/crypto-transactions/bitpace/new-address', [\App\Http\Controllers\BitpaceController::class, 'newAddress']);
        // Route::post('/crypto-management/crypto-transactions/bitpace/store-address', [\App\Http\Controllers\BitpaceController::class, 'storeAddress']);
        // Route::post('/crypto-management/crypto-transactions/get-address-data', [\App\Http\Controllers\CryptoController::class, 'getAddressData']);
        // Route::get('/crypto-management/crypto-transactions/bitpace/new-address', [\App\Http\Controllers\BitpaceController::class, 'newAddress']);
        // Route::get('/crypto-management/crypto-transactions/bitpace/buy', [\App\Http\Controllers\BitpaceController::class, 'buy']);
        // Route::post('/crypto-management/crypto-transactions/bitpace/buy-save', [\App\Http\Controllers\BitpaceController::class, 'buySave']);
        // Route::post('/crypto-management/crypto-transactions/bitpace/price-quote', [\App\Http\Controllers\BitpaceController::class, 'getPrice']);
        // Route::post('/crypto-management/crypto-transactions/bitpace/get-currency-networks', [\App\Http\Controllers\BitpaceController::class, 'getCurrencyNetworks']);
        // Route::get('/crypto-management/crypto-transactions/bitpace/sell', [\App\Http\Controllers\BitpaceController::class, 'sell']);
        // Route::get('/crypto-management/crypto-transactions/bitpace/withdraw', [\App\Http\Controllers\BitpaceController::class, 'withdraw']);
        // Route::post('/crypto-management/crypto-transactions/bitpace/withdraw-save', [App\Http\Controllers\BitpaceController::class, 'withdrawSave']);
    });


    # Compliance Dashboard
    Route::group(['middleware' => ['can:compliance dashboard']], function () {

        Route::get('/dashboards/compliance', [\App\Http\Controllers\ComplianceDashboardController::class, 'index']);
    });

    #compliance Management
    Route::group(['middleware' => ['can:compliance management']], function () {
        Route::get('/dashboards/compliance-management/cases/new', [App\Http\Controllers\SanctionsController::class, 'index']);
        Route::post('/dashboards/compliance-management/cases/new', [App\Http\Controllers\SanctionsController::class, 'store']);
        Route::get('/dashboards/compliance-management/cases', [App\Http\Controllers\SanctionsController::class, 'showAllCases']);
        Route::get('/dashboards/compliance-management/flagged-transactions', [App\Http\Controllers\SanctionsController::class, 'flaggedTransactions'])->name('flagged.transactions');;
        Route::post('/dashboards/compliance-management/cases/search', [App\Http\Controllers\SanctionsController::class, 'showAllCases']);
        Route::get('/dashboards/compliance-management/detailed-case/{id}', [App\Http\Controllers\SanctionsController::class, 'showCase']);
        Route::post('/dashboards/compliance-management/cases/resolve/{id}', [App\Http\Controllers\SanctionsController::class, 'resolveCase']);
        Route::post('/dashboards/compliance-management/cases/block/{id}', [App\Http\Controllers\SanctionsController::class, 'blockCase']);
        Route::get('/dashboards/compliance-management/transactions-cases', [App\Http\Controllers\SanctionsController::class, 'showTransactionsCases']);
        Route::post('/dashboards/compliance-management/transactions-cases/search', [App\Http\Controllers\SanctionsController::class, 'showTransactionsCases']);
        Route::get('/dashboards/compliance-management/transactions-detailed-case/{id}', [App\Http\Controllers\SanctionsController::class, 'showTransactionCase']);
        Route::get('/dashboards/compliance-management/transactions/pdf/{id}', [App\Http\Controllers\SanctionsController::class, 'transactionPDF']);
        Route::post('dashboards/compliance-management/transactions-detailed-case/reslove/{id}', [App\Http\Controllers\SanctionsController::class, 'resolveTransactionCase']);
        Route::post('dashboards/compliance-management/transactions-detailed-case/block/{id}', [App\Http\Controllers\SanctionsController::class, 'blockTransactionCase']);
        Route::get('/redirect-to-deep-linking-case', [App\Http\Controllers\SanctionsController::class, 'redirectToDeepLinkingWorlCheck'])->name('redirect.deep.link.case');
        Route::post('/client-management/clients/worldCheck-screening-report-get-status-and-save', [\App\Http\Controllers\ClientController::class, 'worldCheckPassportScreeningReportGetStatusAndSave']);

        Route::group(['middleware' => ['can:view worldcheck api logs']], function () {
            Route::get('/dashboards/compliance-management/worldcheck-api-logs', [\App\Http\Controllers\WorldCheckApiLogController::class, 'index']);
            Route::get('/dashboards/compliance-management/worldcheck-api-logs/{id}', [\App\Http\Controllers\WorldCheckApiLogController::class, 'show'])
                ->middleware('can:view worldcheck api log detail');
        });
    });
    # Finance Dashboard
    Route::group(['middleware' => ['can:finance dashboard']], function () {

        Route::get("/dashboards/finance", [\App\Http\Controllers\FinanceDashboardController::class, 'index']);
        Route::get("/dashboards/finance/charts/balance-by-currency", [\App\Http\Controllers\FinanceDashboardController::class, 'balanceByCurrency']);
        Route::get('/dashboards/finance/charts/accounts-by-currency', [\App\Http\Controllers\FinanceDashboardController::class, 'accountsByCurrency']);
        Route::get('/dashboards/finance/charts/accounts-by-crypto-currency', [\App\Http\Controllers\FinanceDashboardController::class, 'accountsByCryptoCurrency']);
        Route::get('/dashboards/finance/charts/transactions-by-nature', [\App\Http\Controllers\FinanceDashboardController::class, 'transactionsByNature']);
        Route::get('/dashboards/finance/charts/transactions-by-status', [\App\Http\Controllers\FinanceDashboardController::class, 'transactionsByStatus']);
        Route::get('/dashboards/finance/charts/transactions-by-date', [\App\Http\Controllers\FinanceDashboardController::class, 'transactionsbyDate']);
    });

    # Operations Dashboard
    Route::group(['middleware' => ['can:operations dashboard']], function () {

        Route::get("/dashboards/operations", [\App\Http\Controllers\OperationsDashboardController::class, 'index']);
        Route::get("/dashboards/operations/charts/clients-by-type", [\App\Http\Controllers\OperationsDashboardController::class, 'clientsByType']);
        Route::get('/dashboards/operations/charts/clients-by-status', [\App\Http\Controllers\OperationsDashboardController::class, 'clientsByStatus']);
        Route::get('/dashboards/operations/charts/transactions-by-nature', [\App\Http\Controllers\OperationsDashboardController::class, 'transactionsByNature']);
        Route::get('/dashboards/operations/charts/transactions-by-status', [\App\Http\Controllers\OperationsDashboardController::class, 'transactionsByStatus']);
        Route::get('/dashboards/operations/charts/transactions-by-date', [\App\Http\Controllers\OperationsDashboardController::class, 'transactionsbyDate']);
    });

    # 2FA Routes
    Route::get('/2fa', [App\Http\Controllers\TwoFAController::class, 'index'])->name('2fa.index');
    // Route::post('/2fa', [App\Http\Controllers\TwoFAController::class, 'store'])->name('2fa.post');
    Route::get('/2fa/reset', [App\Http\Controllers\TwoFAController::class, 'resend'])->name('2fa.resend');
    // Route::get('/2fa/cancel', [App\Http\Controllers\TwoFAController::class, 'cancel']);

    # Change approve
    Route::post("/changes/approve", [\App\Http\Controllers\ApproveChangeController::class, 'approve']);
    Route::post("/changes/reject", [\App\Http\Controllers\ApproveChangeController::class, 'reject']);

    // Bell notifications
    Route::post('/notification/delete', [\App\Http\Controllers\BellNotificationController::class, 'delete']);
    Route::post('/notification/delete/all', [\App\Http\Controllers\BellNotificationController::class, 'deleteAll']);

    // backup & restore
    Route::group(['middleware' => ['can:manage data']], function () {

        Route::get('/data-management/backups', [\App\Http\Controllers\DBBackupController::class, 'listBackups']);
        Route::get('/data-management/backups/create', [\App\Http\Controllers\DBBackupController::class, 'create']);
    });

    // settings
    Route::group(['middleware' => ['can:settings']], function () {


        Route::get('/settings/client-file-config', [\App\Http\Controllers\ClientFileConfigController::class, 'index']);
        Route::get('/settings/client-file-config/new', [\App\Http\Controllers\ClientFileConfigController::class, 'add']);
        Route::post('/settings/client-file-config/store', [\App\Http\Controllers\ClientFileConfigController::class, 'store']);
        Route::get('/settings/client-file-config/edit/{id}', [\App\Http\Controllers\ClientFileConfigController::class, 'edit']);
        Route::post('/settings/client-file-config/update', [\App\Http\Controllers\ClientFileConfigController::class, 'update']);
        Route::post('/settings/client-file-config/delete', [\App\Http\Controllers\ClientFileConfigController::class, 'delete']);

        Route::get('/settings/webhook-ip-whitelists', [\App\Http\Controllers\WebhookIpWhitelistController::class, 'index']);
        Route::get('/settings/webhook-ip-whitelists/new', [\App\Http\Controllers\WebhookIpWhitelistController::class, 'add']);
        Route::post('/settings/webhook-ip-whitelists/store', [\App\Http\Controllers\WebhookIpWhitelistController::class, 'store']);
        Route::get('/settings/webhook-ip-whitelists/edit/{id}', [\App\Http\Controllers\WebhookIpWhitelistController::class, 'edit']);
        Route::post('/settings/webhook-ip-whitelists/update', [\App\Http\Controllers\WebhookIpWhitelistController::class, 'update']);
        Route::post('/settings/webhook-ip-whitelists/delete', [\App\Http\Controllers\WebhookIpWhitelistController::class, 'delete']);

        Route::get('/settings/modules', [\App\Http\Controllers\ModuleController::class, 'index']);
        Route::post('/settings/modules/update', [\App\Http\Controllers\ModuleController::class, 'update']);
        Route::get('/settings/module-configurations', [\App\Http\Controllers\ModuleConfigurationController::class, 'index']);
        Route::get('/settings/module-configurations/export/{module}', [\App\Http\Controllers\ModuleConfigurationController::class, 'export']);
        Route::post('/settings/module-configurations/import', [\App\Http\Controllers\ModuleConfigurationController::class, 'import']);
        Route::get('/settings/module-configurations/new', [\App\Http\Controllers\ModuleConfigurationController::class, 'new']);
        Route::post('/settings/module-configurations/store', [\App\Http\Controllers\ModuleConfigurationController::class, 'store']);
        Route::get('/settings/module-configurations/edit/{id}', [\App\Http\Controllers\ModuleConfigurationController::class, 'edit']);
        Route::post('/settings/module-configurations/update', [\App\Http\Controllers\ModuleConfigurationController::class, 'update']);
        Route::post('/settings/module-configurations/reveal-secrets', [\App\Http\Controllers\ModuleConfigurationController::class, 'revealSecrets'])->name('module-configurations.reveal-secrets');
        Route::post('/settings/module-configurations/toggle-module', [\App\Http\Controllers\ModuleConfigurationController::class, 'toggleModule']);
        Route::post('/settings/module-configurations/delete', [\App\Http\Controllers\ModuleConfigurationController::class, 'delete']);

        # Fee Engine — fee tier groups, currency fee tiers, client assignments, charge log
        Route::get('/settings/fee-engine/groups',           [\App\Modules\FeeEngine\Controllers\FeeTierGroupController::class, 'index']);
        Route::get('/settings/fee-engine/groups/new',       [\App\Modules\FeeEngine\Controllers\FeeTierGroupController::class, 'create']);
        Route::post('/settings/fee-engine/groups',          [\App\Modules\FeeEngine\Controllers\FeeTierGroupController::class, 'store']);
        Route::get('/settings/fee-engine/groups/{id}/edit', [\App\Modules\FeeEngine\Controllers\FeeTierGroupController::class, 'edit']);
        Route::post('/settings/fee-engine/groups/update',   [\App\Modules\FeeEngine\Controllers\FeeTierGroupController::class, 'update']);
        Route::post('/settings/fee-engine/groups/delete',   [\App\Modules\FeeEngine\Controllers\FeeTierGroupController::class, 'delete']);

        Route::post('/settings/fee-engine/tiers',           [\App\Modules\FeeEngine\Controllers\CurrencyFeeTierController::class, 'store']);
        Route::post('/settings/fee-engine/tiers/update',    [\App\Modules\FeeEngine\Controllers\CurrencyFeeTierController::class, 'update']);
        Route::post('/settings/fee-engine/tiers/delete',    [\App\Modules\FeeEngine\Controllers\CurrencyFeeTierController::class, 'delete']);

        Route::get('/settings/fee-engine/assignments',      [\App\Modules\FeeEngine\Controllers\FeeAssignmentController::class, 'index']);
        Route::post('/settings/fee-engine/assignments',     [\App\Modules\FeeEngine\Controllers\FeeAssignmentController::class, 'assign']);

        Route::get('/settings/fee-engine/charges',          [\App\Modules\FeeEngine\Controllers\FeeChargeController::class, 'index']);

        Route::get('/settings/queue-management', [\App\Http\Controllers\Settings\QueueManagementController::class, 'index']);
        Route::get('/settings/queue-management/pending-data', [\App\Http\Controllers\Settings\QueueManagementController::class, 'pendingData']);
        Route::get('/settings/queue-management/failed-data', [\App\Http\Controllers\Settings\QueueManagementController::class, 'failedData']);
        Route::get('/settings/queue-management/failed/{id}', [\App\Http\Controllers\Settings\QueueManagementController::class, 'showFailed']);
        Route::post('/settings/queue-management/retry-failed', [\App\Http\Controllers\Settings\QueueManagementController::class, 'retryFailed']);
        Route::post('/settings/queue-management/forget-failed', [\App\Http\Controllers\Settings\QueueManagementController::class, 'forgetFailed']);
        Route::post('/settings/queue-management/delete-pending', [\App\Http\Controllers\Settings\QueueManagementController::class, 'deletePending']);
        Route::post('/settings/queue-management/retry-all-failed', [\App\Http\Controllers\Settings\QueueManagementController::class, 'retryAllFailed']);

        Route::get('/settings/scheduler-management', [\App\Http\Controllers\Settings\SchedulerManagementController::class, 'index']);
        Route::get('/settings/scheduler-management/logs-data', [\App\Http\Controllers\Settings\SchedulerManagementController::class, 'logsData']);
        Route::get('/settings/scheduler-management/logs/{id}', [\App\Http\Controllers\Settings\SchedulerManagementController::class, 'showLog']);
        Route::post('/settings/scheduler-management/run-now', [\App\Http\Controllers\Settings\SchedulerManagementController::class, 'runNow']);

        #Fingate
        Route::post('/settings/module-configurations/simulate-card-transaction', [\App\Http\Controllers\ModuleConfigurationController::class, 'simulateCardTransaction']);
        Route::post('/fingate-run-transaction', [\App\Http\Controllers\FingateController::class, 'runTransactionFingate']);
        Route::get('/fingate-card-providers', [\App\Http\Controllers\FingateController::class, 'listCardProviders']);


        Route::get('/settings/api-clients', [\App\Http\Controllers\ApiClientController::class, 'index']);
        Route::get('/settings/api-client/view/{id}', [\App\Http\Controllers\ApiClientController::class, 'viewClientApi']);
        Route::post('/settings/api-clients/store', [\App\Http\Controllers\ApiClientController::class, 'store']);
        Route::post('/settings/api-clients/regenerate-api-key', [\App\Http\Controllers\ApiClientController::class, 'regenerateApiKey']);
        Route::post('/settings/api-clients/delete', [\App\Http\Controllers\ApiClientController::class, 'delete']);
        Route::post('/settings/api-clients/toggle-access', [\App\Http\Controllers\ApiClientController::class, 'toggleAccess']);


        Route::get('/settings/own-company-details', [\App\Http\Controllers\OwnCompanyController::class, 'index']);
        Route::get('/settings/own-company/edit/{id}', [\App\Http\Controllers\OwnCompanyController::class, 'edit']);
        Route::post('/settings/own-company/update', [\App\Http\Controllers\OwnCompanyController::class, 'update']);
        Route::post('/settings/own-company/store', [\App\Http\Controllers\OwnCompanyController::class, 'store']);

        Route::get('/settings/general-configurations/dropdown-configurations', [\App\Http\Controllers\GeneralConfigurationController::class, 'getDropdownsConfigurations']);
        Route::post('/settings/general-configurations/dropdown-configurations/store', [\App\Http\Controllers\GeneralConfigurationController::class, 'storeDropDownConfiguration']);
        Route::post('/settings/general-configurations/dropdown-configurations/delete', [\App\Http\Controllers\GeneralConfigurationController::class, 'deleteDropdownConfiguration']);

        Route::post('/settings/sync-beneficiary-fields-with-openpayd', [App\Http\Controllers\OpenPaydController::class, 'syncBeneficiaryRequiredDetails']);
        Route::post('/settings/sync-all-beneficiary-fields-with-openpayd', [App\Http\Controllers\OpenPaydController::class, 'syncAllBeneficiaryRequiredDetails']);

        Route::get('/settings/openpayd-sync-data', [App\Http\Controllers\OpenPaydController::class, 'openpaydSyncData']);
        Route::get('/settings/openpayd-sync-data/{id}', [App\Http\Controllers\OpenPaydController::class, 'getOpenPaydClient']);
        Route::post('/settings/sync-openpayd-client', [App\Http\Controllers\OpenPaydController::class, 'syncOpenpaydClient']);

        Route::get('/settings/lorum-sync-data', [App\Http\Controllers\LorumController::class, 'lorumSyncData']);
        Route::get('/settings/lorum-sync/{lorumCustomerId}', [App\Http\Controllers\LorumController::class, 'showClientDetails']);
        Route::post('/settings/sync-lorum-client', [App\Http\Controllers\LorumController::class, 'syncLorumClient']);
        Route::post('/settings/sync-lorum-accounts', [App\Http\Controllers\LorumController::class, 'syncLorumAccounts']);

        Route::post('/finance-management/transactions/main-bank-accounts', [App\Http\Controllers\OpenPaydController::class, 'getMainBankAccounts']);

        Route::prefix('/settings/aml')->group(function () {

            Route::get('/data', [App\Modules\Aml\Controllers\AmlDataController::class, 'index']);
            Route::get('/data/new', [App\Modules\Aml\Controllers\AmlDataController::class, 'new']);
            Route::get('/data/edit/{id}', [App\Modules\Aml\Controllers\AmlDataController::class, 'edit']);
            Route::post('/data/update/{id}', [App\Modules\Aml\Controllers\AmlDataController::class, 'update']);
            Route::post('/data/store', [App\Modules\Aml\Controllers\AmlDataController::class, 'store']);
            //Route::get('rules', [AmlRuleController::class, 'index']);

            Route::get('/new', [App\Modules\Aml\Controllers\AmlRuleController::class, 'new']);
            Route::get('/', [App\Modules\Aml\Controllers\AmlRuleController::class, 'index']);
            Route::get('/edit/{id}', [App\Modules\Aml\Controllers\AmlRuleController::class, 'edit']);
            Route::get('/delete/{id}', [App\Modules\Aml\Controllers\AmlRuleController::class, 'delete']);
            Route::post('/store', [App\Modules\Aml\Controllers\AmlRuleController::class, 'store']);
            Route::post('/update/{id}', [App\Modules\Aml\Controllers\AmlRuleController::class, 'update']);
            Route::get('/data/{id}/fields',  [App\Modules\Aml\Controllers\AmlRuleController::class, 'getFields']);

            //Route::get('rules/{id}', [AmlRuleController::class, 'show']);
            //Route::put('rules/{id}', [AmlRuleController::class, 'update']);
            //Route::delete('rules/{id}', [AmlRuleController::class, 'destroy']);
        });
    });

    // draft client
    // Route::group(['middleware' => ['can:draft registration']], function () {
    //     Route::get('/client-profile/download-pdf', [App\Http\Controllers\DraftClientController::class, 'downloadFilledPDF']);
    //     Route::get('/dashboard/draft-client', [\App\Http\Controllers\DraftClientDashboardController::class, 'index']);
    //     Route::get('/client-profile', [\App\Http\Controllers\DraftClientController::class, 'edit']);
    //     Route::get('/client-profile/{nid}', [\App\Http\Controllers\DraftClientController::class, 'edit']);
    //     Route::post('/client-profile/currency/update', [App\Http\Controllers\DraftClientController::class, 'updateCurrency']);
    //     Route::post('/client-profile/submit', [App\Http\Controllers\DraftClientController::class, 'submitProfile']);
    //     Route::post('/client-profile/upload-pdf', [App\Http\Controllers\DraftClientController::class, 'uploadSignedPDF']);
    //     Route::post('/client-profile/liveness-check', [App\Http\Controllers\DraftClientController::class, 'livenessCheck']);
    // });

    // Route::post('/client-profile/individual/update', [App\Http\Controllers\ClientController::class, 'updateIndividual']);
    // Route::post('/client-profile/employer/update', [App\Http\Controllers\ClientController::class, 'updateIndividualEmployer']);
    // Route::post('/client-profile/individual/store', [App\Http\Controllers\ClientController::class, 'StoreClientIndividual']);
    // Route::post('/client-profile/company/store', [App\Http\Controllers\ClientController::class, 'StoreClientCompany']);

    // Route::post('/client-profile/company/update', [App\Http\Controllers\ClientController::class, 'updateCompany']);
    // Route::post('/client-profile/company-contact/update', [App\Http\Controllers\ClientController::class, 'updateCompanyContact']);
    // Route::post('/client-profile/individual-files/update', [App\Http\Controllers\ClientController::class, 'updateIndividualFiles']);
    // Route::post('/client-profile/company-files/update', [App\Http\Controllers\ClientController::class, 'updateCompanyFiles']);
    // Route::post('/client-profile/individual-files/store', [App\Http\Controllers\ClientController::class, 'storeIndividualFiles']);
    // Route::post('/client-profile/company-files/store', [App\Http\Controllers\ClientController::class, 'storeCompanyFiles']);
    Route::post('/clients/individual/files/delete', [App\Http\Controllers\ClientController::class, 'deleteIndividualFile']);
    Route::post('/clients/company/files/delete', [App\Http\Controllers\ClientController::class, 'deleteCompanyFile']);

    // temporary routes for account number correction
    Route::get("/accounts-correction", [\App\Http\Controllers\AccountCorrectionController::class, 'index']);
    //Route::post("/accounts-correction/store", [\App\Http\Controllers\AccountCorrectionController::class, 'store']);

    # Client Crypto Transaction Requests
    // Route::get('/client/crypto-transaction-requests/list', [App\Http\Controllers\CryptoTRRequestController::class, 'index']);
    // Route::get('/client/crypto-transaction-requests/list/{nid}', [\App\Http\Controllers\CryptoTRRequestController::class, 'index']);
    // Route::get('/client/crypto-transaction-requests/buy/new', [App\Http\Controllers\CryptoTRRequestController::class, 'newBuy']);
    // Route::post('/client/crypto-transaction-requests/buy', [\App\Http\Controllers\CryptoTRRequestController::class, 'buy']);
    // Route::get('/client/crypto-transaction-requests/deposit/new', [App\Http\Controllers\CryptoTRRequestController::class, 'newDeposit']);

    //GED
    // Route::get('/client/crypto-transaction-requests/ged/buy/new', [App\Http\Controllers\GedTRRequestController::class, 'newBuy']);
    // Route::post('/client/crypto-transaction-requests/ged/buy', [\App\Http\Controllers\GedTRRequestController::class, 'buy']);
    // Route::get('/client/crypto-transaction-requests/ged/sell/new', [App\Http\Controllers\GEDTRRequestController::class, 'newSell']);
    // Route::post('/client/crypto-transaction-requests/ged/sell', [\App\Http\Controllers\GEDTRRequestController::class, 'sell']);
    // Route::post('/client/crypto-transaction-requests/ged/wallet-address', [App\Http\Controllers\GedTRRequestController::class, 'getWalletAddress']);

    //bitpace
    // Route::post('/client/crypto-transaction-requests/bitpace/price-quote', [\App\Http\Controllers\BitpaceController::class, 'getPrice']);
    // Route::post('/client/crypto-transaction-requests/bitpace/store-address', [\App\Http\Controllers\BitpaceController::class, 'storeAddress']);
    // Route::post('/client/crypto-transaction-requests/bitpace/get-address-data', [\App\Http\Controllers\BitpaceController::class, 'getAddressData']);
    // Route::post('/client/crypto-transaction-requests/bitpace/get-address-data-qrcode', [\App\Http\Controllers\BitpaceController::class, 'getAddressDataQRCode']);
    // Route::post('/client/crypto-transaction-requests/bitpace/get-currency-networks', [\App\Http\Controllers\BitpaceController::class, 'getCurrencyNetworks']);


    // Route::post('/client/crypto-transaction-requests/deposit', [\App\Http\Controllers\CryptoTRRequestController::class, 'deposit']);
    // Route::get('/client/crypto-transaction-requests/sell/new', [App\Http\Controllers\CryptoTRRequestController::class, 'newSell']);
    // Route::post('/client/crypto-transaction-requests/sell', [\App\Http\Controllers\CryptoTRRequestController::class, 'sell']);
    // Route::post('/client/crypto-transaction-requests/delete', [App\Http\Controllers\CryptoTRRequestController::class, 'deleteRequest']);
    // Route::get('/client/crypto-transaction-requests/view/{id}', [App\Http\Controllers\CryptoTRRequestController::class, 'viewCryptoTransaction']);
    // Route::get('/client/crypto-transaction-requests/edit/{id}', [App\Http\Controllers\CryptoTRRequestController::class, 'editCryptoTransaction']);
    // Route::post('/client/crypto-transaction-requests/buy/update', [App\Http\Controllers\CryptoTRRequestController::class, 'updateBuy']);
    // Route::post('/client/crypto-transaction-requests/deposit/update', [App\Http\Controllers\CryptoTRRequestController::class, 'updateDeposit']);
    // Route::post('/client/crypto-transaction-requests/sell/update', [App\Http\Controllers\CryptoTRRequestController::class, 'updateSell']);
    // Route::post('/client/crypto-transaction-requests/withdraw/update', [App\Http\Controllers\CryptoTRRequestController::class, 'updateWithdraw']);
    // Route::get('/client/crypto-transaction-requests/withdraw/new', [App\Http\Controllers\CryptoTRRequestController::class, 'newWithdraw']);
    // Route::post('/client/crypto-transaction-requests/withdraw', [\App\Http\Controllers\CryptoTRRequestController::class, 'withdraw']);
    // Route::get('/client/crypto-transaction-requests/otp/{id}', [\App\Http\Controllers\TROTPController::class, 'showOTP']);

    # Crypto Management - Crypto Transactions
    Route::group(['middleware' => ['can:crypto management']], function () {

        Route::get('/crypto-management/crypto-transactions', [App\Http\Controllers\CryptoTransactionController::class, 'index']);
        Route::get('/crypto-management/crypto-transactions/edit/crypto-transaction/{id}', [App\Http\Controllers\CryptoTransactionController::class, 'editCryptoTransaction']);
        Route::post('/crypto-management/crypto-transactions/update', [App\Http\Controllers\CryptoTransactionController::class, 'updateCryptoTransaction']);
        Route::post('/crypto-management/crypto-transactions/update/deposit', [App\Http\Controllers\CryptoTransactionController::class, 'updateDepositCryptoTransaction']);

        Route::get('/crypto-management/crypto-transactions/new/buy', [App\Http\Controllers\CryptoTRRequestController::class, 'newAdminBuy']);
        Route::get('/crypto-management/crypto-transactions/new/deposit', [App\Http\Controllers\CryptoTRRequestController::class, 'newAdminDeposit']);
        //new confirmation middle page added
        Route::get('/crypto-management/crypto-transactions/select-method', [App\Http\Controllers\CryptoTRRequestController::class, 'confirmMethod']);

        Route::post('/crypto-management/crypto-transactions/buy', [App\Http\Controllers\CryptoTRRequestController::class, 'adminBuy']);
        Route::post('/crypto-management/crypto-transactions/deposit', [App\Http\Controllers\CryptoTRRequestController::class, 'adminDeposit']);
        Route::get('/crypto-management/crypto-transactions/new/sell', [App\Http\Controllers\CryptoTRRequestController::class, 'newAdminSell']);
        Route::post('/crypto-management/crypto-transactions/sell', [App\Http\Controllers\CryptoTRRequestController::class, 'adminSell']);
        Route::get('/crypto-management/crypto-transactions/new/withdraw', [App\Http\Controllers\CryptoTRRequestController::class, 'newAdminWithdraw']);
        Route::post('/crypto-management/crypto-transactions/withdraw', [App\Http\Controllers\CryptoTRRequestController::class, 'adminWithdraw']);

        Route::get('/crypto-management/crypto-pending-transactions', [App\Http\Controllers\CryptoTransactionController::class, 'getPendingTransactionRequests']);
        Route::get('/crypto-management/crypto-rejected-transactions', [App\Http\Controllers\CryptoTransactionController::class, 'getRejectedTransactionRequests']);

        Route::get('/crypto-management/crypto-pending-transactions/view/crypto-transaction/{id}', [App\Http\Controllers\CryptoTransactionController::class, 'viewCryptoTransactionRequest']);
        Route::get('/crypto-management/crypto-rejected-transactions/view/crypto-transaction/{id}', [App\Http\Controllers\CryptoTransactionController::class, 'viewCryptoTransactionRequest']);

        Route::get('/crypto-management/crypto-pending-transactions/view/crypto-transaction/{id}/{nid}', [App\Http\Controllers\CryptoTransactionController::class, 'viewCryptoTransactionRequest']);
        Route::get('/crypto-management/fireblocks-beneficiaries/{id}/modal', [App\Http\Controllers\CryptoTransactionController::class, 'fireblocksBeneficiaryModal']);

        Route::post('/crypto-management/crypto-pending-transaction/approve/buy', [App\Http\Controllers\CryptoTransactionController::class, 'approveCryptoTransactionRequest']);
        Route::post('/crypto-management/crypto-pending-transaction/approve/deposit', [App\Http\Controllers\CryptoTransactionController::class, 'approveDepositeCryptoTransactionRequest']);
        Route::post('/crypto-management/crypto-pending-transaction/approve/sell', [App\Http\Controllers\CryptoTransactionController::class, 'approveCryptoTransactionRequest']);
        Route::post('/crypto-management/crypto-pending-transaction/approve/withdraw', [App\Http\Controllers\CryptoTransactionController::class, 'approveWithdrawCryptoTransactionRequest']);
        Route::post('/crypto-management/crypto-pending-transaction/reject/crypto-transaction', [App\Http\Controllers\CryptoTransactionController::class, 'rejectCryptoTransaction']);
        Route::get('/crypto-management/crypto-pending-transaction/approve/withdraw-otp', [App\Http\Controllers\CryptoTransactionController::class, 'approveWithdrawOtp']);
        Route::post('/crypto-management/crypto-pending-transaction/reject/crypto-transaction', [App\Http\Controllers\CryptoTransactionController::class, 'rejectCryptoTransaction']);
        Route::post('/crypto-management/crypto-pending-transaction/delete', [App\Http\Controllers\CryptoTransactionController::class, 'deleteCryptoPendingTransaction']);
        Route::post('/crypto-management/crypto-transactions/delete', [App\Http\Controllers\CryptoTransactionController::class, 'deleteCryptoApprovedTransaction']);

        Route::post('/crypto-management/crypto-pending-transaction/save-changes', [App\Http\Controllers\CryptoTransactionController::class, 'saveCryptoTransactionChanges']);
        Route::get('/crypto-management/crypto-pending-transactions/print/{id}', [App\Http\Controllers\CryptoTransactionController::class, 'printTransactionRequest']);

        Route::get('/crypto-management/crypto-transactions/print/{id}', [App\Http\Controllers\CryptoTransactionController::class, 'printTransaction']);

        # Crypto Currency
        Route::get('/crypto-management/crypto-currencies', [App\Http\Controllers\CryptoCurrencyController::class, 'index']);
        Route::get('/crypto-management/crypto-currencies/new', [App\Http\Controllers\CryptoCurrencyController::class, 'addCurrency']);
        Route::post('/crypto-management/crypto-currencies/store', [App\Http\Controllers\CryptoCurrencyController::class, 'storeCurrency']);
        Route::get('/crypto-management/crypto-currencies/edit/{id}', [App\Http\Controllers\CryptoCurrencyController::class, 'editCurrency']);
        Route::post('/crypto-management/crypto-currencies/update', [App\Http\Controllers\CryptoCurrencyController::class, 'updateCurrency']);
        Route::post('/crypto-management/crypto-currencies/delete', [App\Http\Controllers\CryptoCurrencyController::class, 'deleteCurrency']);

        Route::post('/crypto-management/crypto-currencies/networks/store', [App\Http\Controllers\CryptoCurrencyController::class, 'storeNetwork']);
        Route::post('/crypto-management/crypto-currencies/networks/update', [App\Http\Controllers\CryptoCurrencyController::class, 'updateNetwork']);
        Route::post('/crypto-management/crypto-currencies/networks/delete', [App\Http\Controllers\CryptoCurrencyController::class, 'deleteNetwork']);
        Route::get('/crypto-management/crypto-networks/networks-data', [App\Http\Controllers\CryptoCurrencyController::class, 'getNetworksData']);
        Route::get('/crypto-management/crypto-networks/assets-data', [App\Http\Controllers\CryptoCurrencyController::class, 'getAssetsData']);
        Route::post('/crypto-management/crypto-currencies/assets/store', [App\Http\Controllers\CryptoCurrencyController::class, 'storeAsset']);
        Route::post('/crypto-management/crypto-currencies/assets/update', [App\Http\Controllers\CryptoCurrencyController::class, 'updateAsset']);
        Route::post('/crypto-management/crypto-currencies/assets/delete', [App\Http\Controllers\CryptoCurrencyController::class, 'deleteAsset']);
        Route::get('/crypto-management/crypto-currencies/assets/{id}', [App\Http\Controllers\CryptoCurrencyController::class, 'getAsset']);

        # Deposit addresses + Bitpace (gated by crypto management; was duplicated under admin dashboard)
        Route::get('/crypto-management/crypto-transactions/deposit-addresses', [\App\Http\Controllers\CryptoController::class, 'getDepositAddresses']);
        Route::post('/crypto-management/crypto-transactions/get-address-data', [\App\Http\Controllers\CryptoController::class, 'getAddressData']);

        Route::get('/crypto-management/crypto-transactions/bitpace/deposit-addresses', [\App\Http\Controllers\BitpaceController::class, 'getDepositAddresses']);
        Route::get('/crypto-management/crypto-transactions/bitpace/new-address', [\App\Http\Controllers\BitpaceController::class, 'newAddress']);
        Route::post('/crypto-management/crypto-transactions/bitpace/store-address', [\App\Http\Controllers\BitpaceController::class, 'storeAddress']);
        Route::post('/crypto-management/crypto-transactions/bitpace/get-address-data', [\App\Http\Controllers\BitpaceController::class, 'getAddressData']);
        Route::get('/crypto-management/crypto-transactions/bitpace/buy', [\App\Http\Controllers\BitpaceController::class, 'buy']);
        Route::post('/crypto-management/crypto-transactions/bitpace/buy-save', [\App\Http\Controllers\BitpaceController::class, 'buySave']);
        Route::post('/crypto-management/crypto-transactions/bitpace/price-quote', [\App\Http\Controllers\BitpaceController::class, 'getPrice']);
        Route::post('/crypto-management/crypto-transactions/bitpace/get-currency-networks', [\App\Http\Controllers\BitpaceController::class, 'getCurrencyNetworks']);
        Route::get('/crypto-management/crypto-transactions/bitpace/sell', [\App\Http\Controllers\BitpaceController::class, 'sell']);
        Route::get('/crypto-management/crypto-transactions/bitpace/withdraw', [\App\Http\Controllers\BitpaceController::class, 'withdraw']);
        Route::post('/crypto-management/crypto-transactions/bitpace/withdraw-save', [App\Http\Controllers\BitpaceController::class, 'withdrawSave']);
        Route::get('/crypto-management/crypto-transactions/bitpace/balance', [\App\Http\Controllers\BitpaceController::class, 'balance']);

        # QR-Code
        Route::post('/client/crypto-transactions/generate-address-qr', [\App\Http\Controllers\CryptoController::class, 'generateAddressQrCode']);
    });

    # Admin Card Management
    Route::group(['middleware' => ['can:manage cards']], function () { 

        Route::get('/card-management/cards/get-total-cards', [App\Http\Controllers\CardManagementController::class, 'getTotalCards']);
        Route::get('/card-management/cards/list', [App\Http\Controllers\CardManagementController::class, 'listCards']);
        Route::get('/card-management/card/get-balance/{id}', [App\Http\Controllers\CardManagementController::class, 'getBalance']);
        Route::get('/card-management/cards/wallet/balance/{provider}', [App\Http\Controllers\FingateController::class, 'getWalletBalance']);

        Route::post('/card-management/cards/block', [App\Http\Controllers\CardManagementController::class, 'blockCard']);
        Route::post('/card-management/cards/unblock', [App\Http\Controllers\CardManagementController::class, 'unblockCard']);
        Route::get('/card-management/cards/download-statement/{id}', [App\Http\Controllers\CardManagementController::class, 'downloadStatement']);
        Route::get('/card-management/cards/view/{id}', [App\Http\Controllers\CardManagementController::class, 'viewCard']);
        Route::get('/card-management/cards/unassigned/list', [App\Http\Controllers\CardManagementController::class, 'listUnassignedCards']);
        Route::get('/card-management/cards/sync-unassigned-cards', [App\Http\Controllers\CardManagementController::class, 'syncUnassignedCards']);
        Route::get('/card-management/link-users/list', [App\Http\Controllers\CardManagementController::class, 'listLinks']);
        Route::get('/card-management/link-users/new', [App\Http\Controllers\CardManagementController::class, 'newLink']);
        Route::get('/card-management/link-users/new/{assigned_card_id}', [App\Http\Controllers\CardManagementController::class, 'newLink']);
        Route::post('/card-management/link-users/store', [App\Http\Controllers\CardManagementController::class, 'storeLink']);
        Route::post('/card-management/link-users/assign-and-link', [App\Http\Controllers\CardManagementController::class, 'assignAndLinkCard']);
        Route::get('/card-management/link-users/edit/{client_id}', [App\Http\Controllers\CardManagementController::class, 'editLink']);
        Route::post('/card-management/link-users/update', [App\Http\Controllers\CardManagementController::class, 'updateLink']);

        Route::get('/card-management/card/topup-requests/list', [App\Http\Controllers\CardManagementController::class, 'topupRequestsList']);
        // Route::get('/card-management/card/topup-requests/list', [App\Http\Controllers\CardManagementController::class, 'topupPendingRequestsList']);
        Route::get('/card-management/card/topup-pending-requests/list', [App\Http\Controllers\CardManagementController::class, 'topupPendingRequestsList']);
        Route::get('/card-management/card/topup-pending-requests/list/{nid}', [App\Http\Controllers\CardManagementController::class, 'topupPendingRequestsList']);
        Route::get('/card-management/card/topup-rejected-requests/list', [App\Http\Controllers\CardManagementController::class, 'topupRejectedRequestsList']);
        Route::get('/card-management/card/topup-rejected-requests/list/{nid}', [App\Http\Controllers\CardManagementController::class, 'topupRejectedRequestsList']);
        Route::get('/card-management/card/topup-pending-requests/view/{id}', [App\Http\Controllers\CardManagementController::class, 'viewTopupPendingRequest']);
        Route::get('/card-management/card/topup-pending-requests/view/{id}/{nid}', [App\Http\Controllers\CardManagementController::class, 'viewTopupPendingRequest']);
        Route::get('/card-management/card/topup-rejected-requests/view/{id}', [App\Http\Controllers\CardManagementController::class, 'viewTopupPendingRequest']);
        Route::get('/card-management/card/topup-rejected-requests/view/{id}/{nid}', [App\Http\Controllers\CardManagementController::class, 'viewTopupPendingRequest']);
        Route::get('/card-management/card/topup-requests/view/{id}', [App\Http\Controllers\CardManagementController::class, 'viewTopupRequest']);
        Route::get('/card-management/card/topup-pending-requests/edit/{id}', [App\Http\Controllers\CardManagementController::class, 'editTopupTransaction']);
        Route::post('/card-management/card/topup-requests/update', [App\Http\Controllers\CardManagementController::class, 'updateTopup']);
        Route::post('/card-management/card/topup-pending-requests/approve', [App\Http\Controllers\CardManagementController::class, 'approveTopupTransaction']);
        Route::post('/card-management/card/topup-update', [App\Http\Controllers\CardManagementController::class, 'updateTopupTransaction']);
        Route::post('/card-management/card/topup-pending-requests/reject', [App\Http\Controllers\CardManagementController::class, 'rejectTopupTransaction']);
        Route::get('/card-management/card/back-office-pending-topup-requests', [App\Http\Controllers\CardManagementController::class, 'backOfficePendingTopupRequests']);
        Route::get('/card-management/card/back-office-rejected-topup-requests', [App\Http\Controllers\CardManagementController::class, 'backOfficeRejectedTopupRequests']);

        Route::post('/card-management/card/back-office-topup/approve', [App\Http\Controllers\CardManagementController::class, 'approveBackOfficeTransaction']);
        Route::get('/card-management/card/topup-requests/topup', [App\Http\Controllers\CardManagementController::class, 'topUpCardBackOffice']);
        Route::post('/card-management/card/back-office-topup-pending-requests/reject', [App\Http\Controllers\CardManagementController::class, 'rejectTopupTransactionBackOffice']);


        Route::get('/card-management/card/unblock-requests/list', [App\Http\Controllers\CardManagementController::class, 'unblockRequestsList']);
        Route::get('/card-management/card/unblock-requests/view/{id}', [App\Http\Controllers\CardManagementController::class, 'viewUnblockRequest']);
        // Route::get('/card-management/card/unblock-requests/view/{id}', [App\Http\Controllers\CardManagementController::class, 'viewRequest']);
        Route::post('/card-management/card/unblock-requests/approve', [App\Http\Controllers\CardManagementController::class, 'approveUnblockRequest']);
        Route::post('/card-management/card/unblock-requests/reject', [App\Http\Controllers\CardManagementController::class, 'rejectUnblockRequest']);
        Route::get('/card-management/cards/verify/documents/{detail_id}/{customer_id}/{client_id}', [App\Http\Controllers\CardManagementController::class, 'verifyFingateDocuments']);
        Route::get('/card-management/cards/documents/status/{detail_id}/{client_id}', [App\Http\Controllers\CardManagementController::class, 'verifyFingateDocumentsStatus']);
        Route::post('/card-management/card/topups/storeTopupBackOffice', [App\Http\Controllers\CardManagementController::class, 'storeTopupBackOffice']);
    });

    # Admin Card Management
    Route::group(['middleware' => ['can:mobile requests list']], function () {

        Route::get('/mobile-client-requests/approved-list', [App\Http\Controllers\ServiceRequestsManagementController::class, 'index']);
        Route::get('/mobile-client-requests/pending-list', [App\Http\Controllers\ServiceRequestsManagementController::class, 'listPendingRequests']);
        Route::get('/mobile-client-requests/otp-pending-list', [App\Http\Controllers\ServiceRequestsManagementController::class, 'listOtpPendingRequests']);
        Route::get('/mobile-client-requests/rejected-list', [App\Http\Controllers\ServiceRequestsManagementController::class, 'listRejectedRequests']);
        Route::get('/mobile-client-requests/view/{id}', [App\Http\Controllers\ServiceRequestsManagementController::class, 'viewRequest']);
        Route::get('/mobile-client-requests/view/{id}/{nid}', [App\Http\Controllers\ServiceRequestsManagementController::class, 'viewRequest']);
        Route::post('/mobile-client-requests/approve', [App\Http\Controllers\ServiceRequestsManagementController::class, 'approveRequest']);
        Route::post('/mobile-client-requests/reject', [App\Http\Controllers\ServiceRequestsManagementController::class, 'rejectRequest']);
    });

    # Client Card Management
    // Route::get('/client/card-management/card/list', [App\Http\Controllers\ClientCardManagementController::class, 'listCards']);
    Route::get('/client/card-management/card/get-balance', [App\Http\Controllers\ClientCardManagementController::class, 'getCardBalance']);

    // Route::get('/client/card-management/card/get-balance/{id}', [App\Http\Controllers\ClientCardManagementController::class, 'getCardBalance']);
    // Route::get('/client/card-management/request-instant-card', [App\Http\Controllers\ClientCardManagementController::class, 'requestInstantCard']);
    // Route::post('/client/card-management/request-card', [App\Http\Controllers\ClientCardManagementController::class, 'requestCard']);
    Route::get('/client/card-management/card/view', [App\Http\Controllers\ClientCardManagementController::class, 'viewCard']);
    Route::get('/client/card-management/card/view/{id}', [App\Http\Controllers\ClientCardManagementController::class, 'viewCard']);
    Route::get('/client/card-management/card/view/{id}/{nid}', [App\Http\Controllers\ClientCardManagementController::class, 'viewCard']);
    // Route::post('/client/card-management/card/unblock', [App\Http\Controllers\ClientCardManagementController::class, 'unblockCard']);
    // Route::post('/client/card-management/card/block', [App\Http\Controllers\ClientCardManagementController::class, 'blockCard']);
    // Route::post('/client/card-management/card/change-password', [App\Http\Controllers\ClientCardManagementController::class, 'changePassword']);
    // Route::post('/client/card-management/card/change-pin', [App\Http\Controllers\ClientCardManagementController::class, 'changePin']);
    // Route::get('/client/card-management/card/topups/list', [App\Http\Controllers\ClientCardManagementController::class, 'listTopups']);
    // Route::get('/client/card-management/card/topups/list/{card_id}', [App\Http\Controllers\ClientCardManagementController::class, 'listTopups']);
    // Route::get('/client/card-management/card/topups/list/{card_id}/{nid}', [App\Http\Controllers\ClientCardManagementController::class, 'listTopups']);
    // Route::get('/client/card-management/card/topups/new/{card_id}', [App\Http\Controllers\ClientCardManagementController::class, 'newTopup']);
    // Route::post('/client/card-management/card/topups/store', [App\Http\Controllers\ClientCardManagementController::class, 'storeTopup']);
    // Route::get('/client/card-management/card/topups/otp/{id}', [\App\Http\Controllers\TROTPController::class, 'showOTP']);
    // Route::get('/client/card-management/card/topups/view/{id}/{card_id}', [App\Http\Controllers\ClientCardManagementController::class, 'viewTopup']);
    // Route::get('/client/card-management/card/topups/edit/{id}/{card_id}', [App\Http\Controllers\ClientCardManagementController::class, 'editTopup']);
    // Route::get('/client/card-management/card/topups/edit/{id}/{card_id}/{nid}', [App\Http\Controllers\ClientCardManagementController::class, 'editTopup']);
    // Route::post('/client/card-management/card/topups/update', [App\Http\Controllers\ClientCardManagementController::class, 'updateTopup']);
    // Route::get('/client/card-management/card/statement', [App\Http\Controllers\ClientCardManagementController::class, 'getCardStatement']);
    // Route::get('/client/card-management/card/statement/{card_id}', [App\Http\Controllers\ClientCardManagementController::class, 'getCardStatement']);
    // Route::post('/client/card-management/card/statement-generate', [App\Http\Controllers\ClientCardManagementController::class, 'generateCardStatement']);
    // Route::post('/client/card-management/card/statement/pdf', [App\Http\Controllers\ClientCardManagementController::class, 'clientCardStatementPDF']);

    // Route::get('/client/card-management/unblock-requests/list', [App\Http\Controllers\ClientCardManagementController::class, 'unblockRequestsList']);
    // Route::get('/client/card-management/unblock-requests/view/{id}', [App\Http\Controllers\ClientCardManagementController::class, 'viewUnblockRequest']);
    // Route::get('/client/card-management/unblock-requests/new', [App\Http\Controllers\ClientCardManagementController::class, 'newTopupRequest']);
    // Route::get('/client/card-management/card/view/{id}', [App\Http\Controllers\ClientCardManagementController::class, 'viewCard']);

    # These routes are used in multiple roles, instead of duplicating the routes we are keeping it outside so all logged in users can access it
    Route::post('/finance-management/get-balance', [App\Http\Controllers\TransactionController::class, 'getAccountBalance']);
    Route::get('/user-management/user/switch', [App\Http\Controllers\UserController::class, 'switchUser']);
    Route::get('/user-management/user/switch-user-from-new-portal', [App\Http\Controllers\UserController::class, 'switchUserFromNewPortal']);

    # Client Service Management
    // Route::get('/client/services/requests/list', [App\Http\Controllers\ServiceRequestsManagementController::class, 'listServiceRequestsForClient']);
    // Route::get('/client/serviceRequests/requests/list/{nid}', [App\Http\Controllers\ServiceRequestsManagementController::class, 'listServiceRequestsForClient']);
    // Route::get('/client/services/requests/new', [App\Http\Controllers\ServiceRequestsManagementController::class, 'newServiceRequest']);
    // Route::get('/client/services/requests/new/{sub_account_id}', [App\Http\Controllers\ServiceRequestsManagementController::class, 'newServiceRequest']);
    // Route::post('/client/services/requests/store', [App\Http\Controllers\ServiceRequestsManagementController::class, 'storeServiceRequest']);
    // Route::get('/client/services/requests/view/{id}', [App\Http\Controllers\ServiceRequestsManagementController::class, 'viewServiceRequest']);
    // Route::get('/client/services/requests/view/{id}/{nid}', [App\Http\Controllers\ServiceRequestsManagementController::class, 'viewServiceRequest']);
    // Route::post('/client/services/requests/delete', [App\Http\Controllers\ServiceRequestsManagementController::class, 'deleteServiceRequest']);

    // Route::get('/client/services/requests/otp/{id}', [\App\Http\Controllers\ServiceOTPController::class, 'showOTP']);
    // Route::post('/client/services/requests/otp/validate', [\App\Http\Controllers\ServiceOTPController::class, 'validateOTP']);
    // Route::post('/client/services/requests/generate/otp', [\App\Http\Controllers\ServiceOTPController::class, 'generateOTP']);

    Route::get('/test', function () {

        dd('NOW:: ' . \Carbon\Carbon::now() . ' | ADD 1 MONTH: ' . \Carbon\Carbon::now()->addMonth(1));
        dd("This Month: " . \Carbon\Carbon::now()->floorMonth() . " | October 2022: " . \Carbon\Carbon::parse("2022-10-01") . " | Difference: " . \Carbon\Carbon::now()->floorMonth()->diffInMonths(\Carbon\Carbon::parse("2022-10-01")));
    });
    #basic admin
    Route::group(['middleware' => ['can:client account statement report']], function () {

        Route::get('/reports-management/client/client-account-statement', [\App\Http\Controllers\ReportController::class, 'clientAccountStatement']);
        Route::post('/reports-management/client/client-account-statement-generate', [\App\Http\Controllers\ReportController::class, 'accountStatementGenerate']);
        Route::post('/reports-management/client/client-account-statement-pdf', [\App\Http\Controllers\ReportController::class, 'accountStatementPDF']);
    });
});

// temp routes for push notifications broadcasting
Route::get("/ta", function () {

    $payload = [
        'notification' => [
            'title' => "Broadcast",
            'body' => "Testing broadcasting service for push notifications.",
            'sound' => "default"
        ],
        'data' => null
    ];

    return PushNotificationHelper::sendToTopic("broadcast", $payload);
});

Route::get("/subscribe/{token}", function ($token) {

    $token = "ej5xOAM1Mk6Wo6OK9NmO5S:APA91bGfqarrIRuIxTQv_Urjl8MMsiKPiSKrTfZNQRZgFOTfYFV5Njyo3hJRhM7FdoS-7oplWqCW3wmv03ngikPYlcXdF3N5RMNo-4PT8jvsmL42p337ph3MUvxUtOY2TSbWHDsw9bbX";
    $token = "ej5xOAM1Mk6Wo6OK9NmO5S:APA91bGfqarrIRuIxTQv_Urjl8MMsiKPiSKrTfZNQRZgFOTfYFV5Njyo3hJRhM7FdoS-7oplWqCW3wmv03ngikPYlcXdF3N5RMNo-4PT8jvsmL42p337ph3MUvxUtOY2TSbWHDsw9bbX";
    $token = "dkbHamAMwEanoHpMtsFXzC:APA91bGoMe2-2kUu9FEU0LY4whGjXZ-lQZmrpQh-FQvIHLdSqYtv9AwLaiae0I_X9rWTauMCrw2_ZTcJOom6Pva1iCG_qGk1kpWkckCcp61zkCnFf9Kc-c7h1uJl2paYZgt-jYxghZlB";
    $token = "ej5xOAM1Mk6Wo6OK9NmO5S:APA91bGfqarrIRuIxTQv_Urjl8MMsiKPiSKrTfZNQRZgFOTfYFV5Njyo3hJRhM7FdoS-7oplWqCW3wmv03ngikPYlcXdF3N5RMNo-4PT8jvsmL42p337ph3MUvxUtOY2TSbWHDsw9bbX";
    return PushNotificationHelper::subscribe($token, "broadcast");
});


Route::post('/bitpace/callback', [\App\Http\Controllers\WebhookController::class, 'bitPaceCallback']);
Route::post('/openpayd/callback', [\App\Http\Controllers\WebhookController::class, 'openPaydCallback']);
Route::post('/lorum/callback', [\App\Http\Controllers\WebhookController::class, 'lorumCallback']);
Route::post('/nerodata/callback', [\App\Http\Controllers\WebhookController::class, 'neroDataCallback']);
Route::post('/fingate/documents/status/callback', [\App\Http\Controllers\WebhookController::class, 'fingateDocumentsStatusUpdate']);
Route::post('/fingate/callback', [\App\Http\Controllers\WebhookController::class, 'fingateCallback']);
Route::post('/zenus/callback', [\App\Http\Controllers\WebhookController::class, 'zenusCallback']);

# Sumsub callback routes
Route::post('/sumsub/callback', [\App\Http\Controllers\WebhookController::class, 'sumsubCallback']);

/**
 * POST /api/musd/buy-completed/callback HTTP/1.1
 * Host: your-api.com
 * Content-Type: application/json
 * X-MUSD-Timestamp: 1756100000
 * X-MUSD-Signature: 4e8c...
 *
 * {
 *     "transaction_id": "TX12345",
 *     "status": "completed",
 *     "amount": "100.00"
 * }
 */

Route::group(['middleware' => ['whitelist.webhook.ip', 'musd.signature', 'idempotency']], function () {
    Route::post('/musd/buy-completed/callback', [\App\Http\Controllers\WebhookController::class, 'musdBuyCallback']);
    Route::post('/musd/sell-completed/callback', [\App\Http\Controllers\WebhookController::class, 'musdSellCallback']);
});
