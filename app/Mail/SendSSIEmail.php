<?php

namespace App\Mail;

use App\Helpers\Common;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendSSIEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $data;
    public $account;
    public $sender;
    public $files;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $account, $sender, $files)
    {
        $this->data = $data;
        $this->account = $account;
        $this->sender = $sender;
        $this->files = $files;
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

        $view_name = "emails.client.send-ssi";

        $email = $this->view($view_name)
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with(
                [
                    'client' => $this->data,
                    'account' => $this->account
                ]
            );

        if (count($this->files) > 0) {
            foreach ($this->files as $key => $file) {
                $email->attachData($file->output(), $key . ".pdf");
            }
        }

        return $email;
    }
}
