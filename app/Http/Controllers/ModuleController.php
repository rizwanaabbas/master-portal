<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use App\Services\CryptoConfigService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ModuleController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super admin', ['only' => ['index', 'update']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $modules = Module::query()
            ->with(['enabler', 'configuration'])
            ->orderBy('name')
            ->get();

        return view('settings.modules.list', compact('modules'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'module_id' => 'required|integer|exists:modules,id',
            'is_enabled' => 'required|boolean',
        ]);

        $module = Module::findOrFail((int) $request->input('module_id'));
        $enabled = $request->boolean('is_enabled');

        if ((bool) $module->is_enabled !== $enabled) {
            $module->is_enabled = $enabled;
            $module->enabled_disabled_by = Auth::user()->id;
            $module->enabled_disabled_at = Carbon::now();
            $module->save();

            if (in_array($module->name, ['Crypto', 'Bitpace'], true)) {
                CryptoConfigService::flush();
            }
        }

        $module->load('enabler');

        return response()->json([
            'success' => true,
            'message' => $enabled ? 'Module enabled.' : 'Module disabled.',
            'is_enabled' => (bool) $module->is_enabled,
            'updated_by' => $module->enabler ? $module->enabler->name : Auth::user()->name,
            'updated_at' => optional($module->updated_at ?: $module->enabled_disabled_at)->format('d M, Y H:i'),
        ]);
    }
}
