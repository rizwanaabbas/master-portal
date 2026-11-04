<?php

namespace App\Http\Controllers;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use App\Helpers\EmailNotification;
use App\Helpers\FileFirewall;
use App\Helpers\IDORHelper;
use App\Helpers\OnboardingHelper;
use App\Helpers\S3Helper;
use App\Models\Account;
use App\Models\Client;
use App\Models\User;
use App\Models\ClientCompany;
use App\Models\ClientCompanyFile;
use App\Models\ClientDraftCurrency;
use App\Models\ClientFileConfig;
use App\Models\ClientIndividualFile;
use App\Models\ClientKYC;
use App\Models\CompanyType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\KycRequest;
use App\Rules\CurrencyRule;
use App\Rules\SupportingDocumentsRule;
use Barryvdh\DomPDF\Facade as PDF;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use ZipArchive;
use Illuminate\Support\Facades\Log;

class DraftClientController extends Controller
{
    public function edit($nid = "")
    {
        // change notification status
        if ($nid > 0) {
            Common::readNotification($nid);
        }

        $client = ClientHelper::getClientByUserId(Auth::user()->id);

        if ($client->status == "Pending") {
            return redirect("/dashboard/draft-client")->with("error", "You can't modify your account at this stage, your account is under approval.");
        }

        if ($client->status == "Liveness Done") {
            session(['formnumber' => 5]);
        }

        $clientKYC = $client->is_kyc_requested;

        if ($clientKYC) {
            return view("draft-clients.client-liveness-check");
        }

        $nationalities = Country::get();
        $currencies = Currency::where("is_active", true)->get();
        if ($client->type == "individual") {
            $fileTypes = ClientFileConfig::Individual()->get();
            return view("draft-clients.individual-client", compact('nationalities', 'client', 'currencies', 'fileTypes'));
        } elseif ($client->type == "company") {
            $fileTypes = ClientFileConfig::Company()->get();
            $companyTypes = CompanyType::get();

            return view("draft-clients.company-client", compact('nationalities', 'companyTypes', 'client', 'currencies', 'fileTypes'));
        }
    }

    public function uploadPassport(Request $request)
    {
        $request->validate([
            'passport-upload' => 'required',
            'passport_number' => 'required',
            'passport_expiry' => 'required',
            'dob' => 'required',
            'gender' => 'required',
            'nationality_id' => 'required',
            'residence_country_id' => 'required',
        ]);

        $hash = $request->get("hash");
        $kyc_request = KycRequest::where('hash', $hash)->first();
        $client = Client::where('id', $kyc_request->client_id)->first();
        $upload = $this->doUpload($request, $kyc_request);

        if (!$upload) {
            return response()->json([
                'success' => false,
                'message' => 'There was an error while uploading passport.'
            ], 500);
        } else {
            $kyc_request->request_passport = 2;
            $kyc_request->save();
            if ($kyc_request->request_passport == 2) {
                $passport_expiry =  $request->get('passport_expiry') ?? null;

                if ($passport_expiry)
                    $passport_expiry = Carbon::createFromFormat('d/m/Y', $passport_expiry)->format('Y-m-d');

                $dob =  $request->get('dob') ?? null;
                if ($dob)
                    $dob = Carbon::createFromFormat('d/m/Y', $dob)->format('Y-m-d');
                if (!empty($kyc_request->director)) {
                    if ($kyc_request->request_selfie ==  1)
                        $kyc_request->director->kyc_status = 1;
                    else
                        $kyc_request->director->kyc_status = 2;

                    $kyc_request->director->passport_number =  $request->get('passport_number');
                    $kyc_request->director->passport_expiry = $passport_expiry;
                    $kyc_request->director->dob = $dob;
                    $kyc_request->director->gender = strtolower($request->get('gender'));
                    $kyc_request->director->nationality_id = $request->get('nationality_id');
                    $kyc_request->director->residence_country_id = $request->get('residence_country_id');
                    $kyc_request->director->save();
                } else {
                    if ($client->type == 'company') {
                        $contact = $client->company->contact;
                        if (!empty($contact)) {
                            $contact->passport_number = $request->get('passport_number');
                            $contact->passport_expiry = $passport_expiry;
                            $contact->dob = $dob;
                            $contact->gender = strtolower($request->get('gender'));
                            $contact->nationality_id = $request->get('nationality_id');
                            $contact->residence_country_id = $request->get('residence_country_id');
                            $contact->save();
                        }
                    } else if ($client->type == 'individual') {
                        $individual = $client->individual;
                        if (!empty($individual)) {
                            $individual->passport_number = $request->get('passport_number');
                            $individual->passport_expiry_date = $passport_expiry;
                            $individual->dob = $dob;
                            $individual->gender = strtolower($request->get('gender'));
                            $individual->nationality_id = $request->get('nationality_id');
                            $individual->residence_country_id = $request->get('residence_country_id');
                            $individual->save();
                        }
                    }

                    if ($kyc_request->request_selfie ==  1) {
                        $client->is_kyc_requested = true;
                    } else {
                        $client->is_kyc_requested = false;
                    }

                    $user = User::where("email", $client->type == 'company' ? $client->company->contact->email_address : $client->individual->email_address)->first();

                    if (!auth()->user()) {
                        Auth::login($user);
                    }

                    $client->save();
                }

                if ($kyc_request->request_selfie ==  2 || $kyc_request->request_selfie ==  0) {
                    if (($user = User::where("email", $client->type == 'company' ? $client->company->contact->email_address : $client->individual->email_address)->first()) && (!$user->hasRole('API') && $user->hasRole('draft client'))) {
                        Log::info("----------------------------------------------");
                        Log::info('User dont have a Session, got the user using Client.');
                        Log::info("----------------------------------------------\n");

                        $user->removeRole('draft client');
                        $user->assignRole("client");
                    } elseif (!empty(auth()->user()) && (!auth()->user()->hasRole('API') && auth()->user()->hasRole('draft client'))) {
                        Log::info("----------------------------------------------");
                        Log::info('User have a Session.');
                        Log::info("----------------------------------------------\n");

                        auth()->user()->removeRole('draft client');
                        auth()->user()->assignRole("client");
                    }

                    return redirect("/kyc-success");
                }
            }

            if ($kyc_request->request_selfie ==  1) {
                // $client->is_kyc_requested = false;
                // $client->save();
                return redirect("/client-kyc/" . $hash);
            } else {
                $user = ClientHelper::getUserByClientId($kyc_request->client_id);

                $user->removeRole('draft client');
                $user->assignRole("client");
            }
        }
    }

    public function doUpload($request, $kyc_request)
    {
        if ($request->hasFile('passport-upload')) {
            // $clientCompany = ClientCompany::where("client_id", $client_id)->first();
            $this->validate($request, [
                'passport-upload' => [new SupportingDocumentsRule(true)]
            ]);
            // validate file
            // dd($request->file("passport-upload"));
            if (!FileFirewall::isAllowedType($request->file("passport-upload"))) {
                return FileFirewall::logBadFileException($request->file("passport-upload"));
            }

            $client_id = $kyc_request->client_id;
            $director_id = $kyc_request->director_id;
            $client = Client::find($client_id);

            if ($client->type == 'individual') {
                $modelClass = app("\\App\\Models\\ClientIndividualFile");
                $fileDirectory = 'individual';
                $config = ClientFileConfig::Individual()->where('identifier', 'passport_copy')->first();
            } elseif ($client->type == 'company') {
                $modelClass = app("\\App\\Models\\ClientCompanyFile");
                $fileDirectory = 'company';
                $config = ClientFileConfig::company()->where('identifier', 'passport_copy')->first();
            }

            $is_passport_changed = 0;
            // dd($request->all());
            // foreach ($config as $conf) {
            //echo "".  $conf->id ." => " . $conf->title. "<br>";
            //continue;
            // check if request has the file defined in config
            // if ('file-' . $conf->id == $request->get('document-type')) {

            $url = S3Helper::fileUpload($request->file("passport-upload"), "client-onboarding-files/clients/" . $fileDirectory . "/" . $request->get('client_id'));
            if ($url) {
                $is_passport_changed = 1;
                // $new_file = $client->type == 'individual' ? new ClientIndividualFile() : new ClientCompanyFile();
                $new_file = $modelClass;
                $new_file->client_id = $client_id;

                if ($client->type == 'individual') {
                    $new_file->individual_id = $client->individual->id;
                } else {
                    $new_file->company_id = $client->company->id;
                    $new_file->director_id = $director_id;
                }

                $new_file->file_id = $config->id;
                $new_file->url = $url;
                //$new_file->created_by = 'null';
                $new_file->save();
            } else {
                return false;
                // return redirect(url()->previous())->with('error', "There was an error while uploading (" . $conf->title . ")")->with("formnumber", 4);
            }
            // } // end for checking if file exists
            // } // end foreach loop over file config

            //verify client auto calling compare faces api
            if ($is_passport_changed && $url != '') {
                if (!empty($director_id))
                    $kyc = ClientKYC::where('client_id', $client->id)->where('director_id', $director_id)->first();
                else
                    $kyc = ClientKYC::where('client_id', $client->id)->whereNull('director_id')->first();
                if (empty($kyc)) {
                    $kyc = new ClientKYC();
                    $kyc->client_id = $client_id;
                    $kyc->director_id = $director_id;
                    $kyc->kyc_status = 'Unverified';
                    $kyc->compliance_status = 'Pending';
                    $kyc->selfie_photo_url = 'null';
                }

                $kyc->passport_copy_url = $url;
                $kyc->save();
                //ClientHelper::compareClientPhotos($kyc->selfie_photo_url, $kyc->passport_copy_url, $client, $director_id);

                return true;
            }
        }

        return false;
    }

    public function doKyc($hash)
    {
        $expiry = date("Y-m-d H:i:s", time());
        $client = ClientHelper::getClientByKycHash($hash);
        $kyc_request = KycRequest::where('hash', $hash)->where('expiry', '>=', $expiry)->first();
        $director_kyc = 0;
        if (!empty($kyc_request->director->kyc_status)) {
            $director_kyc =  $kyc_request->director->kyc_status;
        }

        if (empty($client))
            abort(404);

        $clientKYC = $client->is_kyc_requested;
        if ($clientKYC || $director_kyc == 1) {
            if (empty($kyc_request))
                abort(404);

            if ($kyc_request->request_passport == 1) {
                $nationalities = Country::get();
                return view("draft-clients.client-passport-upload", compact('hash', 'nationalities'));
            }

            if ($kyc_request->request_selfie == 1) {
                return view("draft-clients.client-liveness-check", compact('hash'));
            }
        } else {
            abort(404);
        }
    }

    public function doKycNewPortal($hash)
    {
        $expiry = date("Y-m-d H:i:s", time());
        $client = ClientHelper::getClientByKycHash($hash);
        $client_id = $client->id;
        $kyc_request = KycRequest::where('hash', $hash)->where('expiry', '>=', $expiry)->first();
        $director_kyc = 0;
        if (!empty($kyc_request->director->kyc_status)) {
            $director_kyc = $kyc_request->director->kyc_status;
        }

        if (empty($client)) {
            abort(404);
        }

        $clientKYC = $client->is_kyc_requested;
        if ($clientKYC || $director_kyc == 1) {
            if (empty($kyc_request))
                abort(404);

            if ($kyc_request->request_passport == 1) {
                return redirect()->away(env("REACT_APP_BASE_URL") . "/client-passport-upload?hash={$hash}&client_id={$client_id}");
            }

            if ($kyc_request->request_selfie == 1) {
                return redirect()->away(env("REACT_APP_BASE_URL") . "/client-liveness-check?hash={$hash}&client_id={$client_id}");
            }
        } else {
            abort(404);
        }
    }

    public function updateCurrency(Request $request)
    {
        $this->validate($request, [
            'currency' => [new CurrencyRule]
        ]);

        $client = ClientHelper::getClientByUserId(Auth::user()->id);
        try {
            $belongsTOClient = IDORHelper::isLinkedOrSubAccount(Auth::user(), $request->get("client_id"));

            if ($belongsTOClient) {
                $client = ClientHelper::getClientByUserId(Auth::user()->id);
                if ($client->id != $request->get("client_id")) {
                    return redirect(url()->previous())->with('error', "Something went wrong, please try again later.");
                }

                if ($request->get("currency") && count($request->get("currency")) > 0) {

                    // delete existing currencies
                    $client->draftCurrencies()->delete();

                    foreach ($request->get("currency") as $currency) {

                        $account = new ClientDraftCurrency();

                        $account->client_id = $client->id;
                        $account->currency_id = $currency;
                        $account->save();
                    }

                    return redirect(url()->previous())->with('success', "Currencies added successfully")->with('formnumber', 3);
                } else {
                    return redirect(url()->previous())->with('error', "Please select currency")->with('formnumber', 2);
                }
            }
        } catch (\Exception $e) {
            Log::error("---------------------- Exception ------------------------");
            Log::error($e->getMessage());
            Log::error("----------------------------------------------\n");

            $message = "Internal Server Error, please try again later or contact the Tech team.";
            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function submitProfile(Request $request)
    {
        // send existing PDF file as attachment
        $client = ClientHelper::getClientByUserId(Auth::user()->id);

        if ($client->type == "individual") {
            EmailNotification::sendIndividualOnboardingPDF($client);
        } elseif ($client->type == "company") {
            EmailNotification::sendCompanyOnboardingPDF($client);
        }

        $client->status = "In Progress";
        $client->save();

        return redirect("/dashboard/draft-client")->with("success", "Information submitted successfully");
    }


    public function uploadSignedPDF(Request $request)
    {
        try {
            $client = ClientHelper::getClientByUserId(Auth::user()->id);

            if ($client->type == "individual") {
                $configFiles = ClientFileConfig::Individual()->where("signed_pdf", 1)->get();
            } elseif ($client->type == "company") {
                $configFiles = ClientFileConfig::Company()->where("signed_pdf", 1)->get();
            }

            foreach ($configFiles as $conf) {

                if ($request->hasFile('file-' . $conf->id)) {

                    $fileSize = $request->file('file-' . $conf->id)->getSize();
                    if ($fileSize == 0) {
                        return redirect(url()->previous())->with('error', "Please upload all required files");
                    }
                    $this->validate($request, [
                        'file-' . $conf->id => ['required', new SupportingDocumentsRule(true)]
                    ]);
                    // validate file
                    if (!FileFirewall::isAllowedType($request->file("file-" . $conf->id))) {
                        return FileFirewall::logBadFileException($request->file("file-" . $conf->id));
                    }

                    if ($client->type == "individual") {
                        $this->uploadIndividualSignedPDF($client, $request->file('file-' . $conf->id), $conf->id);
                    } elseif ($client->type == "company") {
                        $this->uploadCompanySignedPDF($client, $request->file('file-' . $conf->id), $conf->id);
                    }
                } else {
                    return redirect(url()->previous())->with('error', "Please upload all required files");
                }
            }

            $client->status = "Pending";
            $client->save();

            // notify operation team
            EmailNotification::notifyDraftClientCreation($client);

            return redirect("/dashboard/draft-client")->with("success", "File has been uploaded successfully.");
        } catch (Exception $e) {
            Log::error("---------------------- Exception ------------------------");
            Log::error($e->getMessage());
            Log::error("----------------------------------------------\n");

            $message = "Internal Server Error, please try again later or contact the Tech team.";

            if ($e->getCode() == 413) {
                $message = $e->getMessage();
            }

            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function downloadFilledPDF()
    {
        $client = ClientHelper::getClientByUserId(Auth::user()->id);

        $pdfs = Common::generateOnBoardingPDF($client);

        #region Generate Zipped file for Download

        // Create Directories for temporary storage of the PDF files
        if (!File::exists(storage_path('app/onBoardingPdfs'))) {
            File::makeDirectory(storage_path('app/onBoardingPdfs'), 0755, false, false);
        }

        if (!File::exists(storage_path('app/onBoardingPdfs/' . $client->id))) {
            File::makeDirectory(storage_path('app/onBoardingPdfs/' . $client->id), 0755, false, false);
        }

        // Store the PDF files in Storage Path
        foreach ($pdfs as $pdfName => $pdfFile) {
            $pdfFile->save(storage_path('app/onBoardingPdfs/' . $client->id . '/' . $pdfName));
        }

        // Create the files array
        $files = array(
            // public_path('/assets/onboarding/cib-master-investment-agreement.pdf')
        );

        if ($client->type == "individual") {
            array_push(
                $files,
                storage_path('app/onBoardingPdfs/' . $client->id . '/individual.pdf'),
                // storage_path('app/onBoardingPdfs/'. $client->id. '/letter of indemnity.pdf')
            );
        } elseif ($client->type == "company") {
            array_push(
                $files,
                storage_path('app/onBoardingPdfs/' . $client->id . '/company.pdf'),
                // storage_path('app/onBoardingPdfs/'. $client->id. '/board resolution.pdf'),
                // storage_path('app/onBoardingPdfs/'. $client->id. '/letter of indemnity.pdf')
            );
        }

        // Zip the files and download the zip
        $path = storage_path('app/tmp/' . Str::uuid()->toString());
        $zipname = $path . "/onboarding-documents.zip";

        if (!file_exists(storage_path($path))) {
            mkdir($path, 0777, true);
        }

        $zip = new ZipArchive;

        $zip->open($zipname, ZipArchive::CREATE);

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }

        $zip->close();

        register_shutdown_function(function () use ($path) {
            File::deleteDirectory($path);
        });

        // Delete the temporarily created files and directory
        File::deleteDirectories(storage_path('app/onBoardingPdfs'));

        return response()->download($zipname);
        #endregion
    }

    public function storeCurrency(Request $request)
    {

        $this->validate($request, [
            'currency' => [new CurrencyRule]
        ]);
        $steps = session('steps');
        $steps['currency'] = $request->input('currency');
        session(['steps' => $steps]);
        return redirect(url()->previous())->with('success', "Currencies added successfully")->with('formnumber', 4);
    }

    public function livenessCheck(Request $request)
    {

        try {
            $onboardingHelper = new OnboardingHelper();
            $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
            $apiKey = $onboardingHelper->getApiKey();
            $endPointMapping = $onboardingHelper->getRegisterKYCForOldClientEndpoint();

            $data = [
                [
                    'name'     => 'selfie_photo',
                    'contents' => $request->input('photo-url')
                ],
            ];
            //Log::info("Saving KYC info: " .json_encode($data)); die();
            ClientHelper::onboardingRegisterClient($baseUrl, $endPointMapping, $apiKey, $data);
        } catch (\Exception $e) {
            Log::error("---------------------- Exception ------------------------");
            Log::error($e->getMessage());
            Log::error("----------------------------------------------\n");

            return redirect("/dashboard/draft-client")->with("error", "Internal Server Error, please try again later or contact the Tech team" . $e);
        }

        return redirect("/home")->with("success", "Liveness check has been sent successfully.");
    }

    public function kycLivenessCheck(Request $request)
    {
        //dd($request);
        try {
            $onboardingHelper = new OnboardingHelper();
            $baseUrl = $onboardingHelper->getBaseUrlOnboarding();
            $apiKey = $onboardingHelper->getApiKey();
            $endPointMapping = $onboardingHelper->getRegisterKYCForOldClientEndpoint();
            $client_id = '';
            $hash = '';
            if (!empty($request->input('hash'))) {
                $hash =  $request->input('hash');
                $client = ClientHelper::getClientByKycHash($hash);
                $client_id = $client->id;
            }

            $data = [
                [
                    'name'     => 'selfie_photo',
                    'contents' => $request->input('photo-url')
                ],
                [
                    'name'     => 'client_id',
                    'contents' => $client_id
                ],
                [
                    'name'     => 'hash',
                    'contents' => $hash
                ],
            ];

            $user = User::where('code', 'kyc-user')->first();

            //dd($user);
            ClientHelper::onboardingRegisterClient($baseUrl, $endPointMapping, $apiKey, $data, $user);
        } catch (\Exception $e) {
            Log::error("---------------------- Exception ------------------------");
            Log::error($e->getMessage());
            Log::error("----------------------------------------------\n");

            return redirect("/client-kyc/" . $request->input('hash'))->with("error", "Internal Server Error, please try again later or contact the Tech team" . $e);
        }

        return redirect("/kyc-success")->with("success", "Liveness check has been sent successfully.");
    }

    public function kycSuccess()
    {
        return view("draft-clients.kyc-success");
    }
}
