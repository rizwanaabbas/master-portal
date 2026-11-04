<?php

namespace App\Modules\FeeEngine\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\FeeEngine\Models\FeeTransactionCharge;
use Illuminate\Http\Request;

class FeeChargeController extends Controller
{
    public function index(Request $request)
    {
        $reference = $request->get('reference');

        $charges = FeeTransactionCharge::query()
            ->with(['currency', 'client', 'group'])
            ->when($reference, fn ($q) => $q->where('transaction_reference', 'like', "%{$reference}%"))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('feeengine::charges', compact('charges', 'reference'));
    }
}
