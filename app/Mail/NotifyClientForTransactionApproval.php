<?php

namespace App\Mail;

use App\Helpers\ClientHelper;
use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyClientForTransactionApproval extends Mailable implements ShouldQueue
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

        // create bell notification
        // $username = '';
        // if ($this->data->crypto_amount > 0 || $this->data->transaction_nature == "Deposit") {
        //     $needle = "crypto-transaction-requests";
        // } elseif ($this->data->transaction_nature == 'Top-up') {
        //     $client = ClientHelper::getClientByAccountId($this->data->account_id);
        //     $needle = "card-management/card/topups";
        //     $username = '/' . $client->cardLinks->where('mpin', $this->data->mpin)->first()->id;
        // } else {
        //     $needle = "transaction-requests";
        // }

        $baseUrl = env('REACT_APP_BASE_URL');
        // $path = $this->data->transaction_nature == 'Top-up' ? "/card-topup" : "/";

        $transactionNature = $this->data->transaction_nature;

        switch ($transactionNature) {
            case 'Top-up':
                $path = '/card-topup';
                break;
            default:
                $path = '/all-transactions';
        }


        $data = [
            'row_id' => $this->data->id,
            'message' => "Your " . $this->title_needle . " has been approved.",
            'url' => url($baseUrl . $path)
        ];

        Common::createNotification($this->data->creator->id, $data);

        $email_template = 'emails.system.notify-client-for-transaction-approval';

        if (!empty($this->data->crypto_system)) {
            $email_template = 'emails.system.notify-client-for-crypto-transaction-approval';
        }

        return $this->view($email_template)
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'trn' => $this->data,
                'url' => $data['url'],
                'title_needle' => $this->title_needle,
            ]);
    }
}
