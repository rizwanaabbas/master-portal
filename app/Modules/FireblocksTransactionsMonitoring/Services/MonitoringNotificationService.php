<?php

namespace App\Modules\FireblocksTransactionsMonitoring\Services;

use App\Models\FireblocksTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitoringNotificationService
{
    public function notifyStuck(FireblocksTransaction $record, string $triggerSource): void
    {
        $config = MonitoringConfigService::tryGet();
        if (!$config || empty($config['notify_on_stuck_detected'])) {
            return;
        }

        $emails = $config['notification_emails'];
        if (empty($emails)) {
            Log::info('Fireblocks monitoring: stuck tx detected (no notification emails configured)', [
                'fireblocks_tx_id' => $record->fireblocks_tx_id,
                'status' => $record->status,
                'trigger' => $triggerSource,
            ]);
            return;
        }

        $subject = '[Fireblocks] Stuck transaction detected: ' . $record->fireblocks_tx_id;
        $body = "A Fireblocks transaction appears stuck.\n\n"
            . "TX ID: {$record->fireblocks_tx_id}\n"
            . "Asset: {$record->asset_id}\n"
            . "Status: {$record->status}\n"
            . "Sub-status: {$record->sub_status}\n"
            . "Direction: {$record->direction}\n"
            . "Detected via: {$triggerSource}\n";

        foreach ($emails as $email) {
            try {
                Mail::raw($body, fn ($message) => $message->to($email)->subject($subject));
            } catch (\Throwable $e) {
                Log::warning('Fireblocks monitoring email failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }
    }

    public function notifyAction(FireblocksTransaction $record, string $action, string $triggerSource): void
    {
        $config = MonitoringConfigService::tryGet();
        if (!$config || empty($config['notify_on_auto_action'])) {
            return;
        }

        $emails = $config['notification_emails'];
        if (empty($emails)) {
            return;
        }

        $subject = '[Fireblocks] Monitoring action: ' . $action . ' on ' . $record->fireblocks_tx_id;
        $body = "Monitoring action executed.\n\n"
            . "Action: {$action}\n"
            . "TX ID: {$record->fireblocks_tx_id}\n"
            . "Replacement: {$record->replaced_by_fireblocks_tx_id}\n"
            . "Trigger: {$triggerSource}\n";

        foreach ($emails as $email) {
            try {
                Mail::raw($body, fn ($message) => $message->to($email)->subject($subject));
            } catch (\Throwable $e) {
                Log::warning('Fireblocks monitoring action email failed', ['email' => $email, 'error' => $e->getMessage()]);
            }
        }
    }
}
