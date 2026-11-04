<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyClientForTransactionRejection extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $ownCompany;
    public $baseUrl;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $ownCompany, $baseUrl = null)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->ownCompany = $ownCompany;
        $this->baseUrl = $baseUrl;
        $queuePrefix = env('QUEUE_PREFIX');

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

        // $needle = "";
        // if ($this->data->crypto_amount > 0 || $this->data->transaction_nature == "Deposit") {
        //     $needle = "crypto-";
        // }
        // create bell notification

        $baseUrl = env('REACT_APP_BASE_URL');

        $data = [
            'row_id' => $this->data->id,
            'message' => "Your Transaction has been rejected.",
            'url' => url($this->baseUrl) ?? url($baseUrl)
        ];

        Common::createNotification($this->data->creator->id, $data);

        return $this->view('emails.system.notify-client-for-transaction-rejection')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['trn' => $this->data, 'url' => $data['url']]);
    }
}
