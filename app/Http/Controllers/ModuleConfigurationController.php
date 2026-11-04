<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Helpers\FingateHelper;
use App\Helpers\ModuleConfigurationSecretField;
use App\Services\CryptoConfigService;
use App\Models\CardDetail;
use App\Models\Country;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\ModuleConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class ModuleConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super admin', ['only' => ['index', 'update', 'toggleModule', 'export', 'import']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $moduleConfigurations = ModuleConfiguration::with(['module', 'creator', 'updater'])->get();

        return view('settings.modules.configurations.list', compact('moduleConfigurations'));
    }

    public function new()
    {
        $modules = Module::select('id', 'name')->get();

        return view('settings.modules.configurations.new', compact('modules'));
    }

    public function store(Request $request)
    {
        $configurations = new ModuleConfiguration();

        try {
            $configurations->module_id = $request->get('module_id');

            $configArray = [];
            $metaSuffixes = ['_type', '_label', '_hint', '_doc_url', '_sample', '_placeholder', '_options'];

            foreach ($request->except(['_token', 'module_id']) as $key => $value) {
                foreach ($metaSuffixes as $suffix) {
                    if (str_ends_with($key, $suffix)) {
                        continue 2;
                    }
                }

                if (isset($configArray[$key])) {
                    continue;
                }

                $configArray[$key] = [
                    'value' => $value,
                    'type' => $request->input($key . '_type', 'text'),
                    'label' => $request->input($key . '_label', $key),
                ];
                $this->applyNewFieldExtras($configArray[$key], $request, $key);
            }

            $configurations->configurations = serialize($configArray);
            $configurations->created_by = Auth::user()->id;

            $configurations->save();

            return redirect('/settings/module-configurations')->with('success', 'New Module Configurations added successfully!');
        } catch (\Exception $e) {
            if (strpos($e->getMessage(), 'Duplicate')) {
                $message = 'Already exists!';
            } else {
                $message = 'Internal Server Error, please try again later or contact the Tech team.';
            }

            return redirect(url()->previous())->with('error', $message);
        }
    }

    public function edit($id)
    {
        $moduleConfig = ModuleConfiguration::find($id);
        if ($moduleConfig != null) {
            $modules = Module::select('id', 'name')->get();
            $module_id = $moduleConfig->module_id;
            $selectedModule = Module::find($module_id);
            $configurations = unserialize($moduleConfig->configurations);
            $configurations = is_array($configurations) ? $configurations : [];
            if ($selectedModule->name === 'Crypto') {
                $configurations = CryptoConfigService::applyBitpaceOverrideToConfigArray($configurations);
            }
            if ($selectedModule->name === 'FireblocksTransactionsMonitoring') {
                $configurations = \App\Modules\FireblocksTransactionsMonitoring\Support\MonitoringModuleConfigDefinitions::refreshDynamicOptions($configurations);
            }
            if ($selectedModule->name === 'ChainAnalysis') {
                $configurations = \App\Modules\Chainalysis\Support\ChainAnalysisModuleConfigDefinitions::mergeInto($configurations);
            }
            $configurations = ModuleConfigurationSecretField::maskForDisplay($configurations);
            $cards = CardDetail::where('provider_type', 'match_move')->select('cardNumber', 'cardHolderName', 'card_id')->get();
            $currencies = Currency::all();
            $countries = Country::all();
            $beneficiaryTypes = [];

            if ($selectedModule->name === 'OpenPayd') {
                $currencies = $currencies->where('virtual_iban_provider', 'openpayd');
                $beneficiaryTypes = ['RETAIL', 'CORPORATE'];
            }

            $bitpaceModuleEnabled = Common::isModuleEnabled('Bitpace');

            return view('settings.modules.configurations.edit', compact('id', 'modules', 'module_id', 'selectedModule', 'configurations', 'cards', 'countries', 'currencies', 'beneficiaryTypes', 'bitpaceModuleEnabled'));
        } else {
            return redirect()->back()->with('error', 'Module Configurations not found.');
        }
    }

    public function update(Request $request)
    {
        $moduleConfig = ModuleConfiguration::find($request->get('id'));

        if ($moduleConfig != null) {
            $moduleConfig->module_id = $request->get('module_id');

            $configurations = unserialize($moduleConfig->configurations);
            $configArray = is_array($configurations) ? $configurations : [];
            $submitted = $request->input('configurations', []);

            foreach ($submitted as $key => $value) {
                if ($key === '_token' || $key === 'module_id') {
                    continue;
                }

                if (is_array($value)) {
                    $value = implode(',', array_values(array_filter(array_map('trim', $value), fn($item) => $item !== '')));
                }

                if (isset($configArray[$key])) {
                    if (ModuleConfigurationSecretField::shouldPreserveExisting($key, $configArray[$key], $value)) {
                        continue;
                    }

                    $configArray[$key]['value'] = $value;
                } else {
                    $configArray[$key] = [
                        'value' => $value,
                        'type' => $request[$key . '_type'],
                        'label' => $request[$key . '_label'],
                    ];
                    $this->applyNewFieldExtras($configArray[$key], $request, $key);
                }
            }

            foreach ($configArray as $key => $field) {
                if (($field['type'] ?? '') === 'multiselect' && !array_key_exists($key, $submitted)) {
                    $configArray[$key]['value'] = '';
                }
            }

            $this->applyFieldMeta($configArray, $request->input('field_meta', []));

            $fieldOrder = $request->input('field_order', []);
            if (!empty($fieldOrder)) {
                $ordered = [];
                foreach ($fieldOrder as $orderedKey) {
                    if (isset($configArray[$orderedKey])) {
                        $ordered[$orderedKey] = $configArray[$orderedKey];
                    }
                }
                $configArray = $ordered + $configArray;
            }

            if ($moduleConfig->module && $moduleConfig->module->name === 'Crypto') {
                $configArray = CryptoConfigService::applyBitpaceOverrideToConfigArray($configArray);
            }

            $moduleConfig->configurations = serialize($configArray);
            $moduleConfig->updated_by = Auth::user()->id;
            $moduleConfig->save();

            if ($moduleConfig->module && $moduleConfig->module->name === 'Crypto') {
                CryptoConfigService::flush();
            }

            if ($request->has('module_is_enabled')) {
                $module = Module::find($request->get('module_id'));
                if ($module) {
                    $module->is_enabled = $request->boolean('module_is_enabled');
                    $module->enabled_disabled_by = Auth::user()->id;
                    $module->enabled_disabled_at = Carbon::now();
                    $module->save();

                    if (in_array($module->name, ['Crypto', 'Bitpace'], true)) {
                        CryptoConfigService::flush();
                    }
                }
            }

            if ($moduleConfig->module && $moduleConfig->module->name === 'FireblocksTransactionsMonitoring') {
                \App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService::flush();
            }

            if ($moduleConfig->module && $moduleConfig->module->name === 'LiveTransactions') {
                \App\Modules\LiveTransactions\Services\LiveTransactionsConfigService::flush();
            }

            if ($moduleConfig->module && $moduleConfig->module->name === 'ChainAnalysis') {
                \App\Modules\Chainalysis\Services\ChainAnalysisConfigService::flush();
            }

            if ($moduleConfig->module && $moduleConfig->module->name === 'Security') {
                \App\Modules\Security\Services\SecurityConfigService::flush();
                \App\Modules\Security\Services\SecurityConfigService::applyRuntimeConfig();
            }

            $moduleName = strtolower($moduleConfig->module->name);

            $sessionKey = match ($moduleName) {
                'openpayd' => 'OpenpaydConfig',
                'lorum' => 'LorumConfig',
                'zenus' => 'ZenusConfig',
                default => null,
            };

            if ($sessionKey) {
                session()->forget($sessionKey);
                session()->put($sessionKey, unserialize($moduleConfig->configurations));
            }

            return redirect('/settings/module-configurations')->with('success', 'Module Configurations updated successfully!');
        } else {
            return redirect('/settings/module-configurations')->with('error', 'Module Configurations not found.');
        }
    }

    public function export(int $module)
    {
        $moduleModel = Module::with('configuration')->findOrFail($module);
        $row = $moduleModel->configuration;
        if (!$row) {
            return redirect()->back()->with('error', 'No configuration exists for ' . $moduleModel->name . '.');
        }

        $configurations = unserialize($row->configurations);
        $configurations = is_array($configurations) ? $configurations : [];

        $payload = [
            'exported_at' => Carbon::now()->toIso8601String(),
            'module' => [
                'name' => $moduleModel->name,
                'is_enabled' => (bool) $moduleModel->is_enabled,
                'configurations' => ModuleConfigurationSecretField::stripValuesForExport($configurations),
            ],
        ];

        $filename = 'module-config-' . preg_replace('/[^A-Za-z0-9_-]/', '-', $moduleModel->name) . '-' . Carbon::now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }, $filename, ['Content-Type' => 'application/json']);
    }

    public function import(Request $request)
    {
        $request->validate([
            'module_id' => 'required|integer|exists:modules,id',
            'file' => 'required|file',
        ]);

        $module = Module::with('configuration')->findOrFail((int) $request->input('module_id'));
        $row = $module->configuration;
        if (!$row) {
            return redirect()->back()->with('error', 'No configuration exists for ' . $module->name . '.');
        }

        $raw = @file_get_contents($request->file('file')->getRealPath());
        $payload = json_decode((string) $raw, true);
        $item = $this->importPayloadForModule($payload, $module->name);

        if ($item === null) {
            return redirect()->back()->with('error', 'This file is not a configuration export for ' . $module->name . '.');
        }

        $incoming = $item['configurations'] ?? [];
        if (!is_array($incoming)) {
            $incoming = [];
        }

        try {
            $decoded = unserialize($row->configurations);
            $existing = is_array($decoded) ? $decoded : [];
            $row->configurations = serialize(
                ModuleConfigurationSecretField::replaceSkippingSecrets($incoming, $existing)
            );
            $row->updated_by = Auth::user()->id;
            $row->save();

            if (array_key_exists('is_enabled', $item)) {
                $module->is_enabled = !empty($item['is_enabled']) ? 1 : 0;
                $module->enabled_disabled_by = Auth::user()->id;
                $module->enabled_disabled_at = Carbon::now();
                $module->save();
            }

            $this->flushModuleCaches($module);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        return redirect()->back()->with('success', $module->name . ' configuration replaced. API key values were left unchanged.');
    }

    private function importPayloadForModule($payload, string $moduleName): ?array
    {
        if (!is_array($payload)) {
            return null;
        }

        if (!empty($payload['module']) && is_array($payload['module'])) {
            if (($payload['module']['name'] ?? null) !== $moduleName) {
                return null;
            }

            return $payload['module'];
        }

        if (!empty($payload['modules']) && is_array($payload['modules'])) {
            foreach ($payload['modules'] as $item) {
                if (is_array($item) && ($item['name'] ?? null) === $moduleName) {
                    return $item;
                }
            }
        }

        return null;
    }

    public function toggleModule(Request $request)
    {
        $request->validate([
            'module_id' => 'required|integer|exists:modules,id',
            'is_enabled' => 'required|boolean',
        ]);

        $module = Module::findOrFail($request->module_id);
        $module->is_enabled = $request->boolean('is_enabled');
        $module->enabled_disabled_by = Auth::user()->id;
        $module->enabled_disabled_at = Carbon::now();
        $module->save();

        if (in_array($module->name, ['Crypto', 'Bitpace'], true)) {
            CryptoConfigService::flush();
        }

        return response()->json([
            'success' => true,
            'message' => 'Module status updated successfully.',
            'is_enabled' => $module->is_enabled,
        ]);
    }

    public function delete(Request $request)
    {
        $moduleConfig = ModuleConfiguration::find($request->get('configurations_id'));

        if ($moduleConfig != null) {
            $moduleConfig->delete();

            return redirect()->back()->with('success', 'Module Configurations deleted successfully!');
        } else {
            return redirect()->back()->with('error', 'Module Configurations not found.');
        }
    }

    public function simulateCardTransaction(Request $request)
    {
        if (env('APP_ENV') === 'production') {
            return redirect(url()->previous())->with('error', 'Something went wrong. Please try again later.');
        }

        try {
            $request->validate([
                'card_id' => 'required|integer',
                'amount' => 'required|numeric',
                'description' => 'required|string',
            ]);

            $payload = [
                'CardId' => $request->get('card_id'),
                'TerminalType' => 'V',
                'TransactionCode' => '1000',
                'BillingTransactionAmount' => $request->get('amount'),
                'BillingCurrencyCode' => 'USD',
                'LocalTransactionAmount' => $request->get('amount'),
                'LocalCurrencyCode' => 'USD',
                'TransactionEntryType' => 'E',
                'Description' => $request->get('description'),
                'Mcc' => '1740',
                'TerminalId' => '00000001',
                'MerchantId' => '213232',
                'CountryCode' => 'USA',
            ];

            $fingateHelper = new FingateHelper();

            $response = $fingateHelper->simulateCardTransactionAPI($payload);

            if (is_string($response)) {
                $response = json_decode($response);
            }

            if (!empty($response->IsSucceeded) && $response->IsSucceeded === true) {
                return redirect(url()->previous())->with('success', 'Transaction has been simulated successfully!');
            } else {
                $errorMessage = $response->ErrorDescription ?? 'An error occurred while simulating the transaction.';

                return redirect(url()->previous())->with('error', $errorMessage);
            }
        } catch (Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function revealSecrets(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'otp' => 'required|string',
        ]);

        if (env("GOOGLE_AUTHENTICATOR") == true) {
            $google2fa = app('pragmarx.google2fa');
            $user = $request->user();

            $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp);

            if (!$valid) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid OTP, please try again.'
                ]);
            }
        }

        $moduleConfig = ModuleConfiguration::findOrFail($request->id);
        $configurations = unserialize($moduleConfig->configurations);
        $configurations = is_array($configurations) ? $configurations : [];

        $secrets = [];
        foreach ($configurations as $key => $field) {
            if (ModuleConfigurationSecretField::isSensitive($key, $field)) {
                $secrets[$key] = $field['value'] ?? '';
            }
        }

        return response()->json([
            'success' => true,
            'secrets' => $secrets,
        ]);
    }

    private function flushModuleCaches(Module $module): void
    {
        if (in_array($module->name, ['Crypto', 'Bitpace'], true)) {
            CryptoConfigService::flush();
        }

        if ($module->name === 'FireblocksTransactionsMonitoring') {
            \App\Modules\FireblocksTransactionsMonitoring\Services\MonitoringConfigService::flush();
        }

        if ($module->name === 'LiveTransactions') {
            \App\Modules\LiveTransactions\Services\LiveTransactionsConfigService::flush();
        }

        if ($module->name === 'ChainAnalysis') {
            \App\Modules\Chainalysis\Services\ChainAnalysisConfigService::flush();
        }

        if ($module->name === 'Security') {
            \App\Modules\Security\Services\SecurityConfigService::flush();
            \App\Modules\Security\Services\SecurityConfigService::applyRuntimeConfig();
        }

        $moduleName = strtolower((string) $module->name);
        $sessionKey = $moduleName === 'openpayd' ? 'OpenpaydConfig' : ($moduleName === 'lorum' ? 'LorumConfig' : ($moduleName === 'zenus' ? 'ZenusConfig' : null));
        if ($sessionKey) {
            session()->forget($sessionKey);
        }
    }

    private function applyFieldMeta(array &$configArray, array $fieldMeta): void
    {
        foreach ($fieldMeta as $key => $meta) {
            if (!isset($configArray[$key]) || !is_array($meta)) {
                continue;
            }

            foreach (['label', 'type', 'hint', 'doc_url', 'sample', 'placeholder'] as $prop) {
                if (!array_key_exists($prop, $meta)) {
                    continue;
                }

                $val = is_string($meta[$prop]) ? trim($meta[$prop]) : $meta[$prop];
                if ($val === '' || $val === null) {
                    unset($configArray[$key][$prop]);
                } else {
                    $configArray[$key][$prop] = $val;
                }
            }

            if (array_key_exists('options_text', $meta)) {
                $options = $this->parseOptionsText($meta['options_text']);
                if (!empty($options)) {
                    $configArray[$key]['options'] = $options;
                } else {
                    unset($configArray[$key]['options']);
                }
            }
        }
    }

    private function applyNewFieldExtras(array &$field, Request $request, string $key): void
    {
        foreach (['hint', 'doc_url', 'sample', 'placeholder'] as $extra) {
            if ($request->filled($key . "_{$extra}")) {
                $field[$extra] = trim((string) $request->input($key . "_{$extra}"));
            }
        }

        if ($request->filled($key . '_options')) {
            $options = $this->parseOptionsText($request->input($key . '_options'));
            if (!empty($options)) {
                $field['options'] = $options;
            }
        }
    }

    private function parseOptionsText(?string $text): array
    {
        $options = [];

        foreach (preg_split('/\r\n|\r|\n/', trim((string) $text)) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_contains($line, '=')) {
                [$optionKey, $optionLabel] = array_map('trim', explode('=', $line, 2));
                $options[$optionKey] = $optionLabel;
            } else {
                $options[$line] = $line;
            }
        }

        return $options;
    }
}
