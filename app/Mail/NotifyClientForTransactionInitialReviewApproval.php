<?php

namespace App\Mail;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyClientForTransactionInitialReviewApproval extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $title_needle;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $titleNeedle, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->title_needle = $titleNeedle;
        $this->ownCompany = $ownCompany;
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

        $baseUrl = env('REACT_APP_BASE_URL');

        $data = [
            'row_id' => $this->data->id,
            'message' => "The initial review of your transaction has been approved.",
            'url' => url($baseUrl . '/all-transactions')
        ];

        Common::createNotification($this->data->creator->id, $data);

        return $this->view('emails.system.notify-client-for-transaction-initial-review-approval')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'trn' => $this->data,
                'url' => $data['url'],
                'title_needle' => $this->title_needle,
                // 'messageBody' => $data['message']
            ]);
    }
}
