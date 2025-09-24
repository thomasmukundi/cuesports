<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminCommunicationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $emailSubject;
    public $emailMessage;
    public $actionRequired;
    public $appName;
    public $appUrl;

    /**
     * Create a new message instance.
     */
    public function __construct($name, $subject, $message, $actionRequired = false)
    {
        $this->userName = $name;
        $this->emailSubject = $subject;
        $this->emailMessage = $message;
        $this->actionRequired = $actionRequired;
        $this->appName = config('app.name', 'CueSports Kenya');
        $this->appUrl = config('app.url');
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject($this->emailSubject . ' - ' . $this->appName)
                    ->view('emails.admin-communication-new')
                    ->with([
                        'name' => $this->userName,
                        'subject' => $this->emailSubject,
                        'message' => $this->emailMessage,
                        'action_required' => $this->actionRequired,
                        'app_name' => $this->appName,
                        'app_url' => $this->appUrl,
                    ]);
    }
}
