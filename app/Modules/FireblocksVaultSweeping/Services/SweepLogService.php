<?php

namespace App\Modules\FireblocksVaultSweeping\Services;

use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepJob;
use App\Modules\FireblocksVaultSweeping\Models\FireblocksSweepLog;
use App\Modules\FireblocksVaultSweeping\Support\SweepFireblocksDiagnostics;
use Fireblocks\Sdk\Models\TransactionRequest;

class SweepLogService
{
    public function log(
        string $event,
        ?string $message = null,
        ?FireblocksSweepJob $job = null,
        ?string $sourceVaultId = null,
        ?string $assetId = null,
        string $level = 'info',
        ?array $context = null
    ): FireblocksSweepLog {
        return FireblocksSweepLog::create([
            'sweep_job_id' => $job?->id,
            'source_vault_id' => $sourceVaultId ?? $job?->source_vault_id,
            'asset_id' => $assetId ?? $job?->asset_id,
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context' => $context,
        ]);
    }

    /**
     * Write to Laravel log and persist a sweep audit row (job detail, run history, etc.).
     */
    public function logDiagnostic(
        string $level,
        string $event,
        string $message,
        ?FireblocksSweepJob $job = null,
        ?string $sourceVaultId = null,
        ?string $assetId = null,
        ?array $context = null
    ): FireblocksSweepLog {
        $context = $context ?? [];

        match ($level) {
            'error' => \Illuminate\Support\Facades\Log::error($message, $context),
            'warning' => \Illuminate\Support\Facades\Log::warning($message, $context),
            'debug' => \Illuminate\Support\Facades\Log::debug($message, $context),
            default => \Illuminate\Support\Facades\Log::info($message, $context),
        };

        return $this->log($event, $message, $job, $sourceVaultId, $assetId, $level, $context);
    }

    public function logFireblocksRequest(
        string $step,
        string $method,
        string $endpoint,
        ?TransactionRequest $request = null,
        ?FireblocksSweepJob $job = null,
        ?string $sourceVaultId = null,
        ?string $assetId = null,
        ?array $extra = null,
        bool $useGasless = false
    ): FireblocksSweepLog {
        $context = array_merge(
            SweepFireblocksDiagnostics::apiRequestContext($method, $endpoint, $request, $useGasless),
            ['fireblocks_step' => $step],
            $extra ?? []
        );

        return $this->logDiagnostic(
            'info',
            'fireblocks_api_request',
            SweepFireblocksDiagnostics::formatApiRequestMessage($method, $endpoint, $request, $useGasless),
            $job,
            $sourceVaultId,
            $assetId,
            $context
        );
    }
}
