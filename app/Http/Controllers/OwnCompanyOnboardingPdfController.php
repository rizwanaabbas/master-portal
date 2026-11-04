<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\OwnCompany;
use App\Services\OnboardingPdfOptionsService;
use Illuminate\Http\Request;
use PDF;
use Throwable;

class OwnCompanyOnboardingPdfController extends Controller
{
    /** @var OnboardingPdfOptionsService */
    protected $options;

    public function __construct(OnboardingPdfOptionsService $options)
    {
        $this->options = $options;
    }

    public function edit()
    {
        $company = OwnCompany::firstOrFail();
        $pdfOptions = $this->options->resolve($company);
        $presets = OnboardingPdfOptionsService::PRESETS;
        $presetLabels = OnboardingPdfOptionsService::PRESET_LABELS;
        $documents = OnboardingPdfOptionsService::DOCUMENTS;
        $colorKeys = OnboardingPdfOptionsService::COLOR_KEYS;
        $colorLabels = OnboardingPdfOptionsService::COLOR_LABELS;
        $presetConfigs = $this->options->allPresetDefaults();
        $bankLegal = $this->options->bankLegalName($company, $pdfOptions);

        return view('settings.own-company.onboarding-pdf', compact(
            'company',
            'pdfOptions',
            'presets',
            'presetLabels',
            'documents',
            'colorKeys',
            'colorLabels',
            'presetConfigs',
            'bankLegal'
        ));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'preset' => 'required|in:cib,monfi,cpib,faib,prudential,custom,standard,banking',
            'product_checklist' => 'required|in:wealth,banking,custom',
            'legal_entity_suffix' => 'nullable|string|max:40',
            'maker_caption' => 'nullable|string|max:255',
            'checker_caption' => 'nullable|string|max:255',
            'individual_form_title' => 'nullable|string|max:255',
            'company_form_title' => 'nullable|string|max:255',
            'individual_account_label' => 'nullable|string|max:120',
            'company_account_label' => 'nullable|string|max:120',
            'loi_title' => 'nullable|string|max:120',
            'loi_salutation' => 'nullable|string|max:80',
            'loi_letterhead_note' => 'nullable|string|max:500',
            'loi_delete_note' => 'nullable|string|max:255',
            'board_resolved_heading' => 'nullable|string|max:80',
            'board_directors_heading' => 'nullable|string|max:80',
            'board_letterhead_note' => 'nullable|string|max:500',
            'board_mia_text' => 'nullable|string|max:120',
            'account_types_left' => 'nullable|string|max:2000',
            'account_types_right' => 'nullable|string|max:2000',
            'race_options' => 'nullable|string|max:2000',
        ]);

        $company = OwnCompany::firstOrFail();
        $company->onboarding_pdf_options = $this->options->normalizeFromRequest($request->all());
        $company->save();

        return redirect('/settings/own-company/onboarding-pdf')
            ->with('success', 'Onboarding PDF options saved. All four document templates use these settings.');
    }

    public function applyPreset(Request $request)
    {
        $this->validate($request, [
            'preset' => 'required|in:cib,monfi,cpib,faib,prudential,custom,standard,banking',
        ]);

        $company = OwnCompany::firstOrFail();
        $raw = (string) $request->get('preset');
        $preset = $raw === 'custom' ? 'custom' : $this->options->normalizePresetKey($raw);
        $opts = $this->options->defaults($preset === 'custom' ? 'cib' : $preset);
        $opts['preset'] = $preset;
        $company->onboarding_pdf_options = $opts;
        $company->save();

        $label = $this->options->presetLabel($preset);

        return redirect('/settings/own-company/onboarding-pdf')
            ->with('success', 'Preset "' . $label . '" applied.');
    }

    /**
     * Live HTML preview fragment (for in-page preview panel).
     * Accepts current form fields so unsaved changes preview immediately.
     */
    public function livePreview(Request $request)
    {
        $company = OwnCompany::firstOrFail();
        $doc = $request->get('doc', 'individual');
        if (!in_array($doc, OnboardingPdfOptionsService::DOCUMENTS, true)) {
            $doc = 'individual';
        }

        $pdfOptions = $this->options->normalizeFromRequest($request->all());
        $ownCompany = Common::getOwnCompanyData() ?: $company;
        $bankLegal = $this->options->bankLegalName($ownCompany, $pdfOptions);

        [$client, $individual] = $this->sampleClient($doc === 'company' || $doc === 'board_resolution' ? 'company' : 'individual', $company);

        try {
            $view = $this->viewNameForDoc($doc);
            $html = view($view, compact('client', 'individual', 'ownCompany', 'pdfOptions', 'bankLegal'))->render();

            // Strip full HTML chrome for iframe srcdoc friendliness — keep as full document.
            return response($html)->header('Content-Type', 'text/html; charset=UTF-8');
        } catch (Throwable $e) {
            return response(
                '<html><body style="font-family:sans-serif;padding:16px;color:#b00;">Preview error: '
                . e($e->getMessage()) . '</body></html>',
                200
            )->header('Content-Type', 'text/html; charset=UTF-8');
        }
    }

    /**
     * Stream a PDF for one of the four docs (saved options, or form POST for live).
     */
    public function preview(Request $request)
    {
        $company = OwnCompany::firstOrFail();
        $doc = $request->get('doc', 'individual');
        if (!in_array($doc, OnboardingPdfOptionsService::DOCUMENTS, true)) {
            $doc = 'individual';
        }

        $pdfOptions = $request->isMethod('post')
            ? $this->options->normalizeFromRequest($request->all())
            : $this->options->resolve($company);

        $ownCompany = Common::getOwnCompanyData() ?: $company;
        $bankLegal = $this->options->bankLegalName($ownCompany, $pdfOptions);
        [$client, $individual] = $this->sampleClient($doc === 'company' || $doc === 'board_resolution' ? 'company' : 'individual', $company);

        try {
            $html = view($this->viewNameForDoc($doc), compact('client', 'individual', 'ownCompany', 'pdfOptions', 'bankLegal'))->render();
            $pdf = PDF::loadHTML($html)->setPaper('a4');

            return $pdf->stream('onboarding-' . str_replace('_', '-', $doc) . '-preview.pdf');
        } catch (Throwable $e) {
            return redirect('/settings/own-company/onboarding-pdf')
                ->with('error', 'Preview failed: ' . $e->getMessage());
        }
    }

    protected function viewNameForDoc(string $doc): string
    {
        $map = [
            'individual' => 'pdfs.on-boarding.individual',
            'company' => 'pdfs.on-boarding.company',
            'letter_of_indemnity' => 'pdfs.on-boarding.letter-of-indemnity',
            'board_resolution' => 'pdfs.on-boarding.board-resolution',
        ];

        return $map[$doc];
    }

    /**
     * @return array{0:object,1:object}
     */
    protected function sampleClient(string $type, OwnCompany $company): array
    {
        $individual = (object) [
            'dob' => now()->subYears(30)->toDateString(),
            'gender' => 'male',
            'mailing_address' => $company->address ?? 'Sample address',
            'client' => (object) ['name' => 'Sample Applicant'],
            'nationality' => (object) ['name' => 'Sample'],
        ];

        $companyEntity = (object) [
            'business_reg_number' => '123456-A',
            'contact' => (object) ['name' => 'Sample Director'],
        ];

        $client = (object) [
            'id' => 0,
            'name' => $type === 'company' ? 'Sample Company Sdn Bhd' : 'Sample Applicant',
            'type' => $type,
            'draftCurrencies' => collect([]),
            'clientKYCRecord' => null,
            'individual' => $individual,
            'company' => $companyEntity,
        ];

        return [$client, $individual];
    }
}
