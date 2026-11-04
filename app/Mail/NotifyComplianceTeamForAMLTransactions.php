<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyComplianceTeamForAMLTransactions extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $transaction_id;
    public $aml_transaction_id;
    public $sender;
    public $ownCompany;
    public $neroDataUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($transaction_id, $aml_transaction_id, $sender, $ownCompany, $portalTransactionsUrl)
    {
        $this->transaction_id = $transaction_id;
        $this->aml_transaction_id = $aml_transaction_id;
        $this->sender = $sender;
        $this->ownCompany = $ownCompany;
        $queuePrefix = env('QUEUE_PREFIX');
        $this->neroDataUrl = $portalTransactionsUrl;
        $this->onQueue($queuePrefix);
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = $this->sender['email'];
        $subject = $this->sender['subject'];
        $name = $this->sender['name'];

        $data = [
            'row_id' => $this->transaction_id,
            'message' => "Suspicious transaction",
            'url' => "{$this->neroDataUrl}/{$this->aml_transaction_id}"
        ];

        return $this->view('emails.system.notify-compliance-for-aml-transaction')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['trn' => $this->transaction_id, 'aml_trn' => $this->aml_transaction_id, 'url' => $data['url'], 'title_needle' => "AML Transaction Requests"]);
    }
}
