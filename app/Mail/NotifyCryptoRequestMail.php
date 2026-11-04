<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Helpers\Common;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ClientHelper;

class NotifyCryptoRequestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    public $data;
    public $sender;
    public $manager;
    public $userName;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $manager, $userName, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->manager = $manager;
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
        $isFireblocksDepositCredited = $this->isFireblocksDepositCredited();
        $viewUrl = $this->cryptoRequestViewUrl();

        $creator_name = '';
        if (!empty($this->userName)) {
            $creator_name = $this->userName;
        } else {
            $user =  ClientHelper::getClientByUserId($this->data->created_by);
            if (!empty($user))
                $creator_name = $user->name;
        }

        $bellMessage = $isFireblocksDepositCredited
            ? 'Crypto deposit credited.'
            : "New (Crypto " . $this->data->transaction_nature . ") transaction has been created by " . $creator_name . ". Please review and approve";

        $data = [
            'row_id' => $this->data->id,
            'message' => $bellMessage,
            'url' => $viewUrl
        ];

        Common::createNotification($this->manager->id, $data);

        $view = $isFireblocksDepositCredited
            ? 'emails.system.crypto.notify-fireblocks-deposit-credited'
            : 'emails.system.crypto.notify-crypto-transaction-request';

        return $this->view($view)
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'transaction' => $this->data,
                'manager' => $this->manager,
                'url' => $viewUrl,
            ]);
    }

    protected function isFireblocksDepositCredited(): bool
    {
        $system = strtolower((string) ($this->data->crypto_system ?? ''));

        return $this->data->transaction_nature === 'Deposit'
            && strcasecmp((string) $this->data->status, 'Approved') === 0
            && $system === 'fireblocks';
    }

    protected function cryptoRequestViewUrl(): string
    {
        if ($this->isFireblocksDepositCredited() && !empty($this->data->link_tr_id)) {
            return url('crypto-management/crypto-transactions/edit/crypto-transaction/' . $this->data->link_tr_id);
        }

        return url('crypto-management/crypto-pending-transactions/view/crypto-transaction/' . $this->data->id);
    }
}
