<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Attachment;

class ProjectStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $projectData;
    public $type;

    /**
     * Create a new message instance.
     */
    public function __construct($projectData, $type)
    {
        $this->projectData  = $projectData;
        $this->type         = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $contractorName = $this->projectData['project']['contractor_name'] ?? 'Contractor';
        $projectName    = $this->projectData['project']['projectname'] ?? 'N/A';

        if ($this->type === 'FullRequest') {
            $subject = "Full fund release request from {$contractorName}";
        } elseif ($this->type === 'PartialRequest') {
            $subject = "Partial fund release request from {$contractorName}";
        } else {
            $subject = "Project Info: {$projectName}";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'email.projectstatus',
            with: [
                'projectData' => $this->projectData,
                'type'        => $this->type,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];
        
        if ($this->type === 'Accepted' || $this->type === 'Invite' || $this->type === 'Reinvite' ) {
            if (!empty($this->projectData['project']['agreement'])) {
                $filePath = public_path($this->projectData['project']['agreement']);

                if (file_exists($filePath)) {
                    $attachments[] = Attachment::fromPath($filePath);
                }
            }
        }

        return $attachments;
    }
}
