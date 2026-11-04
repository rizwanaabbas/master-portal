<?php

namespace App\Helpers;

use App\Models\CryptoTransactionLog;
use Illuminate\Support\Facades\Log;

class CryptoLogHelper
{
    /**
     * Log crypto transaction
     */
    public static function logCryptoTransaction(
        $transactionId,
        $details = [],
        $cryptoSystem = 'Fireblocks',
        $source = 'system'
    ) {
        try {
            return CryptoTransactionLog::create([
                'transaction_id' => $transactionId,
                'details'        => $details,
                'crypto_system'  => $cryptoSystem,
                'source'         => $source,
            ]);
        } catch (\Throwable $e) {
            Log::error('Crypto Transaction Log Failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return null;
        }
    }
}