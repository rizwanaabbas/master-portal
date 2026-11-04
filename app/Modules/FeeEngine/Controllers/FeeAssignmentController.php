<?php

namespace App\Modules\FeeEngine\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Modules\FeeEngine\Models\ClientFeeTierGroupHistory;
use App\Modules\FeeEngine\Models\FeeTierGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeeAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('q');

        $clients = Client::query()
            ->where('parent_id', 0)
            ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->with('feeTierGroup')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $groups = FeeTierGroup::where('is_active', true)->orderBy('name')->get();

        return view('feeengine::assignments', compact('clients', 'groups', 'search'));
    }

    public function assign(Request $request)
    {
        $data = $request->validate([
            'client_id'         => 'required|integer|exists:clients,id',
            'fee_tier_group_id' => 'nullable|integer|exists:fee_tier_groups,id',
            'reason'            => 'nullable|string|max:255',
        ]);

        $this->applyAssignment($data);

        return back()->with('success', 'Fee tier group assignment updated.');
    }

    /**
     * Assign from the client edit screen (Digital Assets tab). Redirects back.
     */
    public function assignFromClient(Request $request)
    {
        $data = $request->validate([
            'client_id'         => 'required|integer|exists:clients,id',
            'fee_tier_group_id' => 'nullable|integer|exists:fee_tier_groups,id',
            'reason'            => 'nullable|string|max:255',
        ]);

        $changed = $this->applyAssignment($data);

        return back()->with(
            $changed ? 'success' : 'error',
            $changed ? 'Fee tier group updated for this client.' : 'Client is already on that fee tier group.'
        );
    }

    /**
     * Shared assignment logic: swap the client's group and record history.
     * Returns false if nothing changed.
     */
    private function applyAssignment(array $data): bool
    {
        $client = Client::findOrFail($data['client_id']);
        $newGroupId = $data['fee_tier_group_id'] ?: null;

        if ((int) $client->fee_tier_group_id === (int) $newGroupId) {
            return false;
        }

        DB::transaction(function () use ($client, $newGroupId, $data) {
            // Close the currently-open history row.
            ClientFeeTierGroupHistory::where('client_id', $client->id)
                ->whereNull('effective_to')
                ->update(['effective_to' => now()]);

            $client->fee_tier_group_id = $newGroupId;
            $client->save();

            if ($newGroupId) {
                ClientFeeTierGroupHistory::create([
                    'client_id'         => $client->id,
                    'fee_tier_group_id' => $newGroupId,
                    'changed_by'        => Auth::id(),
                    'effective_from'    => now(),
                    'reason'            => $data['reason'] ?? null,
                ]);
            }
        });

        return true;
    }
}
