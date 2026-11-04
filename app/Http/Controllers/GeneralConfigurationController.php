<?php

namespace App\Http\Controllers;

use App\Models\DropdownConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GeneralConfigurationController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:settings', ['only' => ['getDropdownsConfigurations', 'storeDropDownConfiguration', 'deleteDropdownConfiguration']]);
    }

    public function getDropdownsConfigurations()
    {
        $dropdownConfigurations = DropdownConfiguration::all();

        return view('settings.general.configurations.list-dropdown-configurations', compact('dropdownConfigurations'));
    }

    public function storeDropDownConfiguration(Request $request)
    {
        $this->validate($request, [
            'type' => 'required',
            'value' => 'required',
            'text' => 'required'
        ]);

        $exists = DropdownConfiguration::where('value', $request->get('value'))->where('type', $request->get('type'))->first();

        if ($exists) {
            return redirect(url()->previous())->with('error', "Configuration for " . $request->get('value') . " with type " . $request->get('value') . " already exists!");
        }

        $configuration = new DropdownConfiguration();

        try {
            $configuration->type = $request->get('type');
            $configuration->value = $request->get('value');
            $configuration->text = $request->get('text');
            $configuration->created_by = Auth::user()->id;

            $configuration->save();

            return redirect(url()->previous())->with('success', 'New Dropdown-Configuration added successfully!');
        } catch (\Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }

    public function deleteDropdownConfiguration(Request $request)
    {
        $configuration = DropdownConfiguration::find($request->get('configuration_id'));

        try {
            if ($configuration != null) {
                $configuration->delete();

                return redirect()->back()->with("success", "Dropdown-Configuration deleted successfully!");
            } else {
                return redirect()->back()->with("error", "Dropdown-Configuration not found.");
            }
        } catch (\Exception $e) {
            return redirect(url()->previous())->with('error', $e->getMessage());
        }
    }
}
