<?php

namespace App\Modules\Aml\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\Aml\Models\AmlData;
use App\Modules\Aml\Models\AmlRule;

use App\Models\Country;

class AmlRuleController extends Controller
{
    /**
     * Display a listing of the AML rules.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $amlRules = AmlRule::all();

        return view('modules.aml.rule.index', compact('amlRules'));
    }
    public function edit($rule_id)
    {
        $rule = AmlRule::where('id',$rule_id)->first();
        $amlData = AmlData::all();
        //$fields = json_decode($fields->fields,true);
        
        $nationalities = Country::get();
        return view('modules.aml.rule.edit', compact('rule','amlData','nationalities'));
    }



    public function new() {
        $amlData = AmlData::all();
       // dd($amlData);
        $nationalities = Country::get();
        return view("modules.aml.rule.new-rule",compact('amlData','nationalities'));
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
        $rules = [
            'rule_name' => 'required|string|max:255',
            'rule_description' => 'required|string|max:255',
            'field' => 'required|string',
            'rule_criteria' => 'required|string',
            'status' => 'required|string',
            'badge' => 'required|string',
            'value' => 'required_if:field,!nationality_id|string',
            'country_id' => 'required_if:field,nationality_id|array',
        ];
    
        //$validatedData = $request->validate($rules);
       // dd($request->all());
        $rule = new AmlRule();
        $rule->rule_name = $request->input('rule_name');
        $rule->rule_description = $request->input('rule_description');
        $rule->field = $request->input('field');
        $rule->rule_criteria = $request->input('rule_criteria');
        $rule->value = in_array($request->input('field'), ['nationality_id', 'residence_country_id'])
            ? json_encode($request->input('country_id'))
            : $request->input('value');
        $rule->status = $request->input('status');
        $rule->badge = $request->input('badge');
        $rule->data_id = $request->input('data_id');
        $rule->save();
        return redirect('/settings/aml')->with('success', 'Rule created successfully');
        //return redirect()->route('aml.new')->with('success', 'Rules saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $rule = AmlRule::findOrFail($id);
        $rule->rule_name = $request->input('rule_name');
        $rule->rule_description = $request->input('rule_description');
        $rule->field = $request->input('field');
        $rule->rule_criteria = $request->input('rule_criteria');
        $rule->value = in_array($request->input('field'), ['nationality_id', 'residence_country_id'])
            ? json_encode($request->input('country_id'))
            : $request->input('value');
        $rule->status = $request->input('status');
        $rule->data_id = $request->input('data_id');
        $rule->badge = $request->input('badge');
        $rule->save();
        return redirect('/settings/aml')->with('success', 'Rule updated successfully');
    }
    /**
     * Apply AML rules to the given data.
     *
     * @param  array  $data
     * @return array
     */
    public static function applyRules($data,$data_set_name = 'Person')
    {
        $results = [];
        if(!is_array($data))
            $data = json_decode(json_encode($data),true);
        // Retrieve all AML rules
            
        $data_set = AmlData::where('name',$data_set_name)->first();
        if(empty($data_set))
            return [];
        if(!$data_set->id)
            return $results;

        $rules = AmlRule::where('data_id',$data_set->id)->get();
            if(empty($rules))
                return $results;
        // Initialize an array to store rule application results
       

        // Iterate through each rule
        foreach ($rules as $rule) {
            // Check if the field exists in the provided data

            if (isset($data[$rule->field])) {
                //echo $rule->field;
                //print_r($data[$rule->field]);
                $fieldValue = $data[$rule->field];
                if($rule->field == 'nationality_id' || $rule->field == 'residence_country_id') {
                    $rule->value = json_decode($rule->value,true);
                } else if (strpos($rule->value, ',') !== false && $rule->rule_criteria == 'between') {
                    $rule->value = explode(',', $rule->value); 
                }
                //dd($rule);
                //print_r($rule);
                //print_r($rule->value);
                //echo $rule->rule_criteria;
                // Apply the rule based on the criteria
                $ruleResult = false;
                switch ($rule->rule_criteria) {
                    case '=':
                        //echo $fieldValue . '- '. $rule->value;
                         $ruleResult = strtolower($fieldValue) == strtolower($rule->value); 
                        break;
                    case '>':
                        $ruleResult = $fieldValue > $rule->value;
                        break;
                    case '>=':
                        $ruleResult = $fieldValue >= $rule->value;
                        break;
                    case '<':
                        $ruleResult = $fieldValue < $rule->value;
                        break;
                    case '<=':
                        $ruleResult = $fieldValue <= $rule->value;
                        break;
                    case 'in':
                        //$values = explode(',', $rule->value);
                       // print_r($rule->value);
                        $ruleResult = in_array($fieldValue, $rule->value);
                    case 'between':
                       
                        $values = $rule->value;
                        // Ensure exactly two values (min and max) are present
                        if (count($values) === 2) {
                            $min = (float) $values[0]; // Convert to number
                            $max = (float) $values[1];

                            // Check if $fieldValue is within the range
                            $ruleResult = ($fieldValue >= $min && $fieldValue <= $max);
                        } else {
                            $ruleResult = false; // Invalid range
                        }
                        break;
                    case 'like':
                        $ruleResult = strpos($fieldValue, $rule->value) !== false;
                        break;
                    default:
                        // If the criteria is not recognized, skip this rule
                        continue 2;
                }

                // Store the result
                if($ruleResult) {
                    $results[] = [
                        'rule_id' => $rule->id,
                        'field' => $rule->field,
                        'rule_name' => $rule->rule_name,
                        'rule_description' => $rule->rule_description,
                        'criteria' => $rule->rule_criteria,
                        'value' => $rule->value,
                        'status' => $rule->status,
                        'badge' => $rule->badge,
                        'result' => $ruleResult,
                    ];
                }
            }
        }
        return $results;
    }
    
    public static  function getDataByName($name) {

        return AmlData::where('name', 'like', '%' . $name . '%')->first();
    }

    public function getFields($id)
    {
        $amlData = AmlData::findOrFail($id);
        $fields = json_decode($amlData->fields, true);

        return response()->json(['fields' => $fields]);
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

