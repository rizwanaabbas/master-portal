<?php

namespace App\Http\Controllers;

use App\Helpers\Common;
use App\Models\ClientFileConfig;
use Illuminate\Http\Request;

class ClientFileConfigController extends Controller
{
    public function index()
    {
        $files = ClientFileConfig::all();

        return view("settings.list", compact('files'));
    }

    public function add()
    {
        $types = ["company", "individual", "bank"];
        return view("settings.new", compact('types'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'type' => 'required'
        ]);

        $file = new ClientFileConfig();

        $file->title = $request->get("title");
        $file->identifier = $request->get("identifier");
        $file->type = $request->get("type");
        $file->isMandatory = $request->get("isMandatory");
        $file->isMultiple = $request->get("isMultiple");
        $file->maxCount = $request->get("maxCount");
        $file->is_active = $request->get("is_active");
        $file->signed_pdf = $request->get("signed_pdf");

        $file->save();

        return redirect("/settings/client-file-config")->with("success", "File details added successfully");
    }

    public function delete(Request $request)
    {
        $file = ClientFileConfig::find($request->get("file_id"));

        if ($file) {
            $file->delete();
            return redirect("/settings/client-file-config")->with("success", "File details deleted successfully");
        } else {
            return redirect("/settings/client-file-config")->with("success", "File details not found");
        }
    }

    public function edit($id)
    {
        $file = ClientFileConfig::find($id);
        $types = ["company", "individual", "bank"];

         $enabledModules = Common::getEnabledModules();

        $providers = [
            'openpayd' => 'OpenPayd',
            'lorum' => 'Lorum',
            'zenus' => 'Zenus',
        ];
        
        // keep only enabled providers
        $virtual_providers_map = array_filter($providers, function ($label) use ($enabledModules) {
            return in_array($label, $enabledModules);
        });

        return view("settings.edit", compact(
            'file',
            'types',
            'virtual_providers_map'
        ));
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'type' => 'required'
        ]);

        $file = ClientFileConfig::find($request->get("id"));

        if ($file) {
            $file->title = $request->get("title");
            $file->identifier = $request->get("identifier");
            $file->type = $request->get("type");
            $file->isMandatory = $request->get("isMandatory");
            $file->isMultiple = $request->get("isMultiple");
            $file->maxCount = $request->get("maxCount");
            $file->is_active = $request->get("is_active");
            $file->signed_pdf = $request->get("signed_pdf");

            $file->required_for_providers = $request->get('required_for_providers');

            $file->save();

            return redirect("/settings/client-file-config")->with("success", "File details added successfully");
        } else {
            return redirect("/settings/client-file-config")->with("error", "File details not found");
        }
    }
}
