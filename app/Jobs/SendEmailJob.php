<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\ResetPasswordMail;
use Illuminate\Support\Facades\Mail;


class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $password;
    protected $username;
    protected $subject;
    /**
     * Create a new job instance.
     */
    public function __construct($email, $password, $username,$subject)
    {    
        $this->email     = $email;
        $this->password  = $password;
        $this->username  = $username;
        $this->subject   = $subject;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $email = new ResetPasswordMail($this->password, $this->username,$this->subject);
        Mail::to($this->email)->send($email);
    }
}
