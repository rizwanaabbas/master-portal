<?php

namespace App\Mail;

use Illuminate\Support\Facades\Mail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SumsubKYCRequestEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $client;
    public $sender;
    public $sumsubUrl;
    public $ownCompany;
    public $reason;

    public function __construct($client, $sender, $sumsubUrl, $ownCompany, $reason = null)
    {
        $this->client = $client;
        $this->sender = $sender;
        $this->sumsubUrl = $sumsubUrl;
        $this->ownCompany = $ownCompany;
        $this->reason = $reason;

        $this->onQueue(env('QUEUE_PREFIX'));
    }

    public function build()
    {
        $isSubAccount = !empty($this->client->parent_id);

        if ($this->reason) {
            $subject = "Action Required: Update " . ($isSubAccount ? "Sub-Account Information" : "Your Information");
        } else {
            $subject = ($isSubAccount ? "Sub-Account " : "") . "Identity Verification Required";
        }
        // create bell notification
        $client = [
            'row_id' => $this->client->id,
            'message' => $this->reason ? "Action Required: Update Your Information" : "Identity Verification Required",
            'url' => "/"
        ];

        return $this->view('emails.client.sumsub-kyc-request')
            ->from($this->sender['email'], $this->sender['name'])
            ->replyTo($this->sender['email'], $this->sender['name'])
            ->subject($subject)
            ->with([
                'client' => $this->client,
                'reason' => $this->reason,
                'sumsubUrl' => $this->sumsubUrl,
                'isSubAccount' => $isSubAccount,
            ]);
    }
}
