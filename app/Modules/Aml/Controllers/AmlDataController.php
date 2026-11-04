<?php

namespace App\Modules\Aml\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Aml\Models\AmlData;
#use App\Modules\Aml\Models\AmlRule;

use App\Models\Country;

class AmlDataController extends Controller
{
    /**
     * Display a listing of the AML rules.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $amlData = AmlData::all();
       // dd($amlData);
        return view('modules.aml.data.index', compact('amlData'));
    }

    public function new() {
        
        return view("modules.aml.data.new");
    }
    public function edit($id)
    {
        $amlData = AmlData::findOrFail($id);
        return view('modules.aml.data.edit', compact('amlData'));
    }
    public function delete($id) {
       if(!empty($id)) {
            AmlRule::where('id',$id)->delete();
            return redirect('/settings/aml')->with('error', 'Rule deleted successfully');
        }
       abort(404);
    }
   
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fields' => 'required|array',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.value' => 'required|string|max:255',
        ]);

        $fields = [];
        foreach ($request->input('fields') as $field) {
            //$fields[$field['label']] = $field['value'];
            $fields[$field['value']] = $field['label'];
        }

        $amlData = new AmlData();
        $amlData->name = $request->input('name');
        $amlData->fields = json_encode($fields);
        $amlData->save();

        return redirect('/settings/aml/data')->with('success', 'Data set created successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'fields' => 'required|array',
            'fields.*.label' => 'required|string|max:255',
            'fields.*.value' => 'required|string|max:255',
        ]);

        $fields = [];
        foreach ($request->input('fields') as $field) {
            $fields[$field['value']] = $field['label'];
        }

        $amlData = AmlData::findOrFail($id);
        $amlData->name = $request->input('name');
        $amlData->fields = json_encode($fields);
        $amlData->save();

        return redirect('/settings/aml/data')->with('success', 'Data set updated successfully.');
    }
    

    public function show($id)
    {
        $rule = AmlRule::findOrFail($id);
        return response()->json($rule);
    }

    

    public function destroy($id)
    {
        $rule = AmlRule::findOrFail($id);
        $rule->delete();

        return response()->json(null, 204);
    }
}

