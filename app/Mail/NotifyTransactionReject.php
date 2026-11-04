<?php

namespace App\Mail;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class NotifyTransactionReject extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $title_needle;
    public $userName;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $titleNeedle, $userName, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->title_needle = $titleNeedle;
        $this->userName = $userName;
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

        // create bell notification
        if ($this->data->transaction_nature == 'Top-up') {
            $client = ClientHelper::getClientByAccountId($this->data->account_id);
            $url = "client/card-management/card/topups/edit/" . $this->data->id . "/" . $client->cardLinks->where('mpin', $this->data->receiver_account_number)->first()->id;
        } else {
            $url = "finance-management/transactions/edit/" . strtolower($this->data->transaction_nature) . "/" . $this->data->id;
        }

        $data = [
            'row_id' => $this->data->id,
            'message' => "The (" . $this->data->transaction_nature . ") transaction has been rejected by " . $this->userName,
            'url' => url($url)
        ];

        Common::createNotification($this->data->created_by, $data);

        return $this->view('emails.system.notify-transaction-rejection')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(['transaction' => $this->data, 'url' => $data['url'], 'title_needle' => $this->title_needle]);
    }
}
