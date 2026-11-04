<?php

namespace App\Modules\FeeEngine\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FeeEngine\Models\CurrencyFeeTier;
use App\Modules\FeeEngine\Models\FeeTierGroup;
use Illuminate\Http\Request;

class CurrencyFeeTierController extends Controller
{
    public function store(Request $request)
    {
        $data = $this->validated($request);
        FeeTierGroup::findOrFail($data['fee_tier_group_id']);

        CurrencyFeeTier::create($data);

        return back()->with('success', 'Fee rule added.');
    }

    public function update(Request $request)
    {
        $tier = CurrencyFeeTier::findOrFail($request->input('id'));
        $tier->update($this->validated($request));

        return back()->with('success', 'Fee rule updated.');
    }

    public function delete(Request $request)
    {
        $tier = CurrencyFeeTier::findOrFail($request->input('id'));
        $groupId = $tier->fee_tier_group_id;
        $tier->delete();

        return redirect('/settings/fee-engine/groups/' . $groupId . '/edit')->with('success', 'Fee rule removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'fee_tier_group_id' => 'required|integer|exists:fee_tier_groups,id',
            'currency_scope'    => 'required|in:currency,all_crypto,all_fiat',
            'currency_id'       => 'nullable|integer|exists:currencies,id|required_if:currency_scope,currency',
            'label'             => 'required|string|max:255',
            'fee_type'          => 'required|in:percentage,flat',
            'fee_value'         => 'required|numeric|min:0',
            'min_fee'           => 'nullable|numeric|min:0',
            'max_fee'           => 'nullable|numeric|min:0',
            'priority'          => 'nullable|integer',
            'is_active'         => 'nullable|boolean',
        ]);

        // min/max only meaningful for percentage fees.
        if ($data['fee_type'] === 'flat') {
            $data['min_fee'] = null;
            $data['max_fee'] = null;
        }

        // Type-wildcards do not carry a specific currency.
        $data['currency_id'] = $data['currency_scope'] === 'currency'
            ? ($request->input('currency_id') ?: null)
            : null;
        $data['priority'] = (int) $request->input('priority', 0);
        $data['is_active'] = (bool) $request->input('is_active', true);

        return $data;
    }
}
