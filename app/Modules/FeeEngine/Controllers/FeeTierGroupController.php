<?php

namespace App\Modules\FeeEngine\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Modules\FeeEngine\Models\FeeTierGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FeeTierGroupController extends Controller
{
    public function index()
    {
        $groups = FeeTierGroup::withCount('currencyFeeTiers')->orderBy('name')->get();

        return view('feeengine::groups.list', compact('groups'));
    }

    public function create()
    {
        return view('feeengine::groups.new');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:255|unique:fee_tier_groups,code',
            'description' => 'nullable|string',
            'is_default'  => 'nullable|boolean',
            'is_active'   => 'nullable|boolean',
        ]);

        $data['code'] = Str::slug($data['code']);
        $data['is_default'] = (bool) $request->input('is_default');
        $data['is_active'] = (bool) $request->input('is_active', true);

        DB::transaction(function () use ($data) {
            if ($data['is_default']) {
                FeeTierGroup::where('is_default', true)->update(['is_default' => false]);
            }
            FeeTierGroup::create($data);
        });

        return redirect('/settings/fee-engine/groups')->with('success', 'Fee tier group created.');
    }

    public function edit($id)
    {
        $group = FeeTierGroup::with(['currencyFeeTiers' => fn ($q) => $q->orderBy('priority')])->findOrFail($id);
        $currencies = Currency::where('is_active', true)->orderBy('short')->get();

        return view('feeengine::groups.edit', compact('group', 'currencies'));
    }

    public function update(Request $request)
    {
        $group = FeeTierGroup::findOrFail($request->input('id'));

        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:255|unique:fee_tier_groups,code,' . $group->id,
            'description' => 'nullable|string',
            'is_default'  => 'nullable|boolean',
            'is_active'   => 'nullable|boolean',
        ]);

        $data['code'] = Str::slug($data['code']);
        $data['is_default'] = (bool) $request->input('is_default');
        $data['is_active'] = (bool) $request->input('is_active');

        DB::transaction(function () use ($group, $data) {
            if ($data['is_default']) {
                FeeTierGroup::where('is_default', true)->where('id', '!=', $group->id)->update(['is_default' => false]);
            }
            $group->update($data);
        });

        return redirect('/settings/fee-engine/groups/' . $group->id . '/edit')->with('success', 'Fee tier group updated.');
    }

    public function delete(Request $request)
    {
        $group = FeeTierGroup::findOrFail($request->input('id'));

        if ($group->clients()->exists()) {
            return back()->with('error', 'Cannot delete: clients are still assigned to this group.');
        }

        $group->delete(); // soft delete keeps charge-log references resolvable

        return redirect('/settings/fee-engine/groups')->with('success', 'Fee tier group deleted.');
    }
}
