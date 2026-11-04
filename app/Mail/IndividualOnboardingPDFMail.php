<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class IndividualOnboardingPDFMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;
    public $sender;
    public $files;
    public $ownCompany;


    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data, $sender, $files, $ownCompany)
    {
        $this->data = $data;
        $this->sender = $sender;
        $this->files = $files;
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

        $email = $this->view('emails.client.individual-onboarding')
            ->from($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with([
                'client' => $this->data,
            ]);

        if (is_array($this->files) && count($this->files) > 0) {
            foreach ($this->files['data'] as $key => $file) {
                $email->attachData($file->output(), $key);
            }

            // if ($this->files['file']){
            //     $email->attach($this->files['file']);
            // }
        }

        return $email;
    }
}
