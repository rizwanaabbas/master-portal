<?php

namespace App\Modules\LiveTransactions\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LiveTransactions\Services\LiveTransactionFeedService;
use App\Modules\LiveTransactions\Services\LiveTransactionsConfigService;
use Illuminate\Http\Request;

class LiveTransactionFeedController extends Controller
{
    public function __construct(
        protected LiveTransactionFeedService $feedService
    ) {
        $this->middleware('auth');
        $this->middleware('permission:view live transactions');
    }

    public function feed(Request $request)
    {
        if (!LiveTransactionsConfigService::isTrayActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Live transactions module is disabled.',
            ], 403);
        }

        $since = $request->query('since');
        $page = max(1, (int) $request->query('page', 1));

        $result = $this->feedService->fetchFeed($since, $page);

        return response()->json(array_merge([
            'success' => true,
            'server_time' => now()->toIso8601String(),
        ], $result));
    }
}
