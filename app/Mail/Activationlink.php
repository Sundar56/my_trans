<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class Activationlink extends Mailable
{
    use Queueable, SerializesModels;

    public $username;
    public $activationCode;
    public $projectData;
    public $type;
    public $subject;
    public $userData;

    /**
     * Create a new message instance.
     */
    public function __construct(array $data)
    {
        $this->type    = $data['type'] ?? null;
        $this->subject = $data['subject'] ?? null;

        if ($this->type == 'activate') {
            $this->username       = $data['userName'] ?? null;
            $this->activationCode = $data['activationCode'] ?? null;
        }

        if ($this->type == 'project') {
            $this->projectData = $data ?? null;
        }

        if ($this->type == 'customer' || $this->type == 'contractor') {
            $this->userData = $data ?? null;
        }
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        if ($this->type === 'activate') {
            return new Content(
                view: 'email.activationlink',
                with: [
                    'username' => $this->username,
                    'code'     => $this->activationCode,
                ],
            );
        }

        if ($this->type == 'project') {
            return new Content(
                view: 'email.followingstep',
                with: [
                    'projectData' => $this->projectData,
                ],
            );
        }
        if ($this->type == 'customer' || $this->type == 'contractor') {
            return new Content(
                view: 'email.followingstep',
                with: [
                    'userData'  => $this->userData,
                ],
            );
        }
        return new Content(
            view: 'email.activationlink',
            with: [
                'username' => $this->username,
                'code'     => $this->activationCode,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
