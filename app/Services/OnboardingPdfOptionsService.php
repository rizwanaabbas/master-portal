<?php

namespace App\Services;

use App\Models\OwnCompany;

/**
 * Config-driven onboarding PDF layout for:
 * individual, company, letter-of-indemnity, board-resolution.
 * Branding (name, logo, license, address) comes from Own Company fields.
 */
class OnboardingPdfOptionsService
{
    /** Internal preset keys (bank + custom). */
    public const PRESETS = ['cib', 'monfi', 'cpib', 'faib', 'prudential', 'custom'];

    /** Display names shown in the UI. */
    public const PRESET_LABELS = [
        'cib' => 'CIB',
        'monfi' => 'Monfi',
        'cpib' => 'CPIB',
        'faib' => 'FAIB',
        'prudential' => 'Prudential',
        'custom' => 'Custom',
        // legacy keys still stored on some rows
        'standard' => 'CIB',
        'banking' => 'Banking',
    ];

    public const DOCUMENTS = [
        'individual',
        'company',
        'letter_of_indemnity',
        'board_resolution',
    ];

    /**
     * PDF palette keys — defaults match system-core onboarding blades.
     */
    public const COLOR_KEYS = [
        'section',       // section header cells rgb(205,227,190)
        'accent',        // banner / h3 bars rgb(51,124,203)
        'title',         // .blue headings #001F5F
        'title_bar',     // td.blueTitle / h3.blueTitle bg #9dc3e6
        'title_bar_text',
        'text',          // body #333
        'muted',         // #666
        'border',        // #D9D9D9
        'page_bg',       // #ffffff
        'highlight',     // company accent #cc9900 (portal)
        'alert',         // board [date] #FF0000
    ];

    public const COLOR_LABELS = [
        'section' => 'Section header (green)',
        'accent' => 'Banner / bar (blue)',
        'title' => 'Title text (.blue)',
        'title_bar' => 'Blue title bar background',
        'title_bar_text' => 'Blue title bar text',
        'text' => 'Body text',
        'muted' => 'Muted text',
        'border' => 'Borders / rules',
        'page_bg' => 'Page background',
        'highlight' => 'Highlight / gold',
        'alert' => 'Alert / date (board)',
    ];

    /**
     * Map configFiles identifiers → document keys.
     */
    public const IDENTIFIER_MAP = [
        'application_form' => null, // resolved by client type
        'letter_of_indemnity' => 'letter_of_indemnity',
        'board_of_resolution' => 'board_resolution',
    ];

    /**
     * Exact system-core PDF palette (shared across cib/monfi/cpib/faib/prudential).
     *
     * @return array<string,string>
     */
    public function systemCoreColors(): array
    {
        return [
            'section' => '#CDE3BE',
            'accent' => '#337CCB',
            'title' => '#001F5F',
            'title_bar' => '#9DC3E6',
            'title_bar_text' => '#FFFFFF',
            'text' => '#333333',
            'muted' => '#666666',
            'border' => '#D9D9D9',
            'page_bg' => '#FFFFFF',
            'highlight' => '#CC9900',
            'alert' => '#FF0000',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function presetLabel(string $preset): string
    {
        $preset = $this->normalizePresetKey($preset);

        return self::PRESET_LABELS[$preset] ?? ucfirst($preset);
    }

    /**
     * Map legacy preset keys to current bank keys.
     */
    public function normalizePresetKey(string $preset): string
    {
        $map = [
            'standard' => 'cib',
            'banking' => 'cib',
        ];
        $preset = $map[$preset] ?? $preset;

        return in_array($preset, self::PRESETS, true) ? $preset : 'cib';
    }

    /**
     * Full defaults for every bank preset (for editor load / live preview).
     *
     * @return array<string,array<string,mixed>>
     */
    public function allPresetDefaults(): array
    {
        $out = [];
        foreach (self::PRESETS as $key) {
            if ($key === 'custom') {
                // Custom starts from CIB/system-core base; user edits freely.
                $out[$key] = $this->defaults('cib');
                $out[$key]['preset'] = 'custom';
                continue;
            }
            $out[$key] = $this->defaults($key);
        }

        return $out;
    }

    public function defaults(string $preset = 'cib'): array
    {
        $preset = $this->normalizePresetKey($preset);

        $base = [
            'preset' => $preset,
            'documents' => [
                'individual' => true,
                'company' => true,
                'letter_of_indemnity' => true,
                'board_resolution' => true,
            ],
            'features' => [
                'show_race' => false,
                'show_maker_checker_labels' => false,
                'show_license_in_title' => true,
                'product_checklist' => 'wealth', // wealth | banking | custom
                'show_loi_witness' => true,
                'show_loi_company_position' => true,
                'show_loi_letterhead_note' => true,
                'show_board_signatory_clause' => true,
                'show_board_mia_clause' => true,
                'show_board_letterhead_note' => true,
            ],
            'labels' => [
                'legal_entity_suffix' => 'Ltd.',
                'maker_caption' => 'Attended by: Sales Representative (Maker)',
                'checker_caption' => 'Checked by: Sales Leader (Checker)',
                'individual_form_title' => 'INDIVIDUAL ACCOUNT OPENING APPLICATION FORM',
                'company_form_title' => 'COMPANY ACCOUNT OPENING APPLICATION FORM',
                'individual_account_label' => 'Individual Investment Account',
                'company_account_label' => 'Corporate',
                'loi_title' => 'LETTER OF INDEMNITY',
                'loi_salutation' => 'Dear Sir,',
                'loi_letterhead_note' => "*For Corporate Investment Account, letter should be printed on Company's letter head",
                'loi_delete_note' => '*Delete which not applicable.',
                'board_resolved_heading' => 'RESOLVED:',
                'board_directors_heading' => 'BOARD OF DIRECTORS',
                'board_letterhead_note' => '*Please prepare on Company Letterhead & attach with the Company stamp.',
                'board_mia_text' => 'Master Investment Agreement',
            ],
            'account_types_left' => [
                'Customer Term Loan',
                'Wealth Management',
                'Customer Reserve Funds',
            ],
            'account_types_right' => [
                'Customer Investment',
                'Financial Advisory',
                'Customer Guarantee Funds',
            ],
            'race_options' => [
                'Malay',
                'Indian',
                'Chinese',
                'Others',
            ],
            // Colors match system-core PDF CSS (all banks used the same palette).
            'colors' => $this->systemCoreColors(),
        ];

        // --- Bank presets mirror system-core layout differences ---

        // Monfi: Race + Maker/Checker captions + wealth account types (same as core monfi blades)
        if ($preset === 'monfi') {
            $base['features']['show_race'] = true;
            $base['features']['show_maker_checker_labels'] = true;
            $base['features']['product_checklist'] = 'wealth';
            $base['labels']['maker_caption'] = 'Attended by: Sales Representative (Maker)';
            $base['labels']['checker_caption'] = 'Checked by: Sales Leader (Checker)';
            $base['labels']['legal_entity_suffix'] = 'Ltd.';
        }

        // CIB / CPIB / FAIB: no Race, no Maker/Checker on cover signatures (system-core cib)
        if (in_array($preset, ['cib', 'cpib', 'faib'], true)) {
            $base['features']['show_race'] = false;
            $base['features']['show_maker_checker_labels'] = false;
            $base['features']['product_checklist'] = 'wealth';
            $base['labels']['legal_entity_suffix'] = 'Ltd.';
        }

        // Prudential: same section colors; no Race; MIA clause on; empty legal suffix (name standalone in core)
        if ($preset === 'prudential') {
            $base['features']['show_race'] = false;
            $base['features']['show_maker_checker_labels'] = false;
            $base['features']['show_board_mia_clause'] = true;
            $base['features']['product_checklist'] = 'wealth';
            $base['labels']['legal_entity_suffix'] = '';
        }

        return $base;
    }

    /**
     * @return array<string,string>
     */
    public function colors(array $options): array
    {
        $defaults = $this->systemCoreColors();
        $saved = $options['colors'] ?? [];
        if (!is_array($saved)) {
            return $defaults;
        }
        $out = $defaults;
        foreach (self::COLOR_KEYS as $key) {
            $hex = $saved[$key] ?? null;
            if (is_string($hex) && preg_match('/^#?[0-9A-Fa-f]{6}$/', $hex)) {
                $out[$key] = '#' . strtoupper(ltrim($hex, '#'));
            }
        }

        return $out;
    }

    /**
     * @return array<string,mixed>
     */
    public function resolve(?OwnCompany $company = null): array
    {
        $company = $company ?: OwnCompany::first();
        $saved = [];
        if ($company && !empty($company->onboarding_pdf_options)) {
            $saved = is_array($company->onboarding_pdf_options)
                ? $company->onboarding_pdf_options
                : (json_decode((string) $company->onboarding_pdf_options, true) ?: []);
        }

        $preset = $this->normalizePresetKey($saved['preset'] ?? 'cib');

        $defaults = $this->defaults($preset === 'custom' ? 'cib' : $preset);
        $defaults['preset'] = $preset;

        return $this->deepMerge($defaults, $saved);
    }

    /**
     * @param  array<string,mixed>  $input
     * @return array<string,mixed>
     */
    public function normalizeFromRequest(array $input): array
    {
        $rawPreset = (string) ($input['preset'] ?? 'custom');
        $preset = ($rawPreset === 'custom')
            ? 'custom'
            : $this->normalizePresetKey($rawPreset);

        $base = $this->defaults($preset === 'custom' ? 'cib' : $preset);
        $base['preset'] = $preset;

        foreach (self::DOCUMENTS as $doc) {
            $base['documents'][$doc] = $this->bool($input['doc_' . $doc] ?? false);
        }

        $base['features']['show_race'] = $this->bool($input['show_race'] ?? false);
        $base['features']['show_maker_checker_labels'] = $this->bool($input['show_maker_checker_labels'] ?? false);
        $base['features']['show_license_in_title'] = $this->bool($input['show_license_in_title'] ?? true);
        $base['features']['show_loi_witness'] = $this->bool($input['show_loi_witness'] ?? true);
        $base['features']['show_loi_company_position'] = $this->bool($input['show_loi_company_position'] ?? true);
        $base['features']['show_loi_letterhead_note'] = $this->bool($input['show_loi_letterhead_note'] ?? true);
        $base['features']['show_board_signatory_clause'] = $this->bool($input['show_board_signatory_clause'] ?? true);
        $base['features']['show_board_mia_clause'] = $this->bool($input['show_board_mia_clause'] ?? true);
        $base['features']['show_board_letterhead_note'] = $this->bool($input['show_board_letterhead_note'] ?? true);

        $checklist = $input['product_checklist'] ?? $base['features']['product_checklist'];
        if (!in_array($checklist, ['wealth', 'banking', 'custom'], true)) {
            $checklist = 'wealth';
        }
        $base['features']['product_checklist'] = $checklist;

        if ($checklist === 'banking') {
            $base['account_types_left'] = [
                'Current account',
                'Saving account',
            ];
            $base['account_types_right'] = [
                'Fixed deposit',
                'Other',
            ];
        } elseif ($checklist === 'wealth') {
            $wealth = $this->defaults('cib');
            $base['account_types_left'] = $wealth['account_types_left'];
            $base['account_types_right'] = $wealth['account_types_right'];
        } else {
            $base['account_types_left'] = $this->linesToList($input['account_types_left'] ?? '');
            $base['account_types_right'] = $this->linesToList($input['account_types_right'] ?? '');
            if (empty($base['account_types_left'])) {
                $base['account_types_left'] = $this->defaults('cib')['account_types_left'];
            }
            if (empty($base['account_types_right'])) {
                $base['account_types_right'] = $this->defaults('cib')['account_types_right'];
            }
        }

        foreach ([
            'legal_entity_suffix',
            'maker_caption',
            'checker_caption',
            'individual_form_title',
            'company_form_title',
            'individual_account_label',
            'company_account_label',
            'loi_title',
            'loi_salutation',
            'loi_letterhead_note',
            'loi_delete_note',
            'board_resolved_heading',
            'board_directors_heading',
            'board_letterhead_note',
            'board_mia_text',
        ] as $labelKey) {
            if (array_key_exists($labelKey, $input)) {
                // Allow empty legal_entity_suffix
                $base['labels'][$labelKey] = trim((string) $input[$labelKey]);
            }
        }

        $race = $this->linesToList($input['race_options'] ?? '');
        if (!empty($race)) {
            $base['race_options'] = $race;
        }

        $colorInput = [];
        foreach (self::COLOR_KEYS as $key) {
            if (!empty($input['color_' . $key])) {
                $colorInput[$key] = $input['color_' . $key];
            }
        }
        $base['colors'] = $this->colors(['colors' => array_merge($base['colors'], $colorInput)]);

        return $base;
    }

    public function isDocumentEnabled(array $options, string $docKey): bool
    {
        return !empty($options['documents'][$docKey]);
    }

    /**
     * Whether a configFiles identifier should generate for this client type.
     */
    public function shouldGenerate(array $options, string $identifier, string $clientType): bool
    {
        if ($identifier === 'application_form') {
            $key = $clientType === 'company' ? 'company' : 'individual';

            return $this->isDocumentEnabled($options, $key);
        }
        if ($identifier === 'letter_of_indemnity') {
            return $this->isDocumentEnabled($options, 'letter_of_indemnity');
        }
        if ($identifier === 'board_of_resolution') {
            return $this->isDocumentEnabled($options, 'board_resolution');
        }

        return true;
    }

    public function bankLegalName(?OwnCompany $company, array $options): string
    {
        $name = $company && $company->name ? $company->name : 'Bank';
        $suffix = trim((string) ($options['labels']['legal_entity_suffix'] ?? 'Ltd.'));

        return trim($name . ($suffix !== '' ? ' ' . $suffix : ''));
    }

    public function feature(array $options, string $key, $default = false)
    {
        return $options['features'][$key] ?? $default;
    }

    public function label(array $options, string $key, string $default = ''): string
    {
        return (string) ($options['labels'][$key] ?? $default);
    }

    /**
     * @param  array<string,mixed>  $base
     * @param  array<string,mixed>  $over
     * @return array<string,mixed>
     */
    protected function deepMerge(array $base, array $over): array
    {
        foreach ($over as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key]) && $this->isAssoc($base[$key])) {
                $base[$key] = $this->deepMerge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }

    protected function isAssoc(array $arr): bool
    {
        if ($arr === []) {
            return false;
        }

        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    protected function bool($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) || $value === 1 || $value === '1' || $value === 'on';
    }

    /**
     * @return string[]
     */
    protected function linesToList(string $text): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text) ?: [];
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                $out[] = $line;
            }
        }

        return $out;
    }
}
