<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\Activationlink;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;

class ActivateEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;
    protected $username;
    protected $activationCode;
    protected $projectId;
    protected $type;
    protected $subject;

    /**
     * Create a new job instance.
     */
    public function __construct($email, $username = null, $activationCode = null, $projectId = null, $type, $subject)
    {
        $this->email          = $email;
        $this->username       = $username;
        $this->activationCode = $activationCode;
        $this->projectId      = $projectId;
        $this->type           = $type;
        $this->subject        = $subject;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->type  == "activate") {
            $userName       = $this->username ?? '';
            $userEmail      = $this->email ?? '';
            $activationCode = $this->activationCode ?? '';
            $type           = $this->type ?? 'activate';
            $subject        = $this->subject ?? 'Activation Link';
            $data = [
                'userName'       => $userName,
                'userEmail'      => $userEmail,
                'activationCode' => $activationCode,
                'type'           => $type,
                'subject'        => $subject,
            ];
            $email = new Activationlink($data);
            Mail::to($this->email)->send($email);
        }
        if ($this->type == "project") {

            $customerName       = $this->username ?? '';
            $baseUrl            = config('services.transpact_register_url');
            $appName            = config('app.name');
            $isTest             = env('IS_TEST');
            if ($isTest == true) {
                $transpactUrl       = "{$baseUrl}?RegEmail={$this->email}.test&co={$appName}";
            } else {
                $transpactUrl       = "{$baseUrl}?RegEmail={$this->email}&co={$appName}";
            }
            $baseUrl            = config('services.transpact_login_url');
            $appName            = config('app.name');
            $transpactLoginUrl  = "{$baseUrl}?Em={$this->email}&co={$appName}";
            $url                = env('SITE_URL');
            $encryptedProjectId = Crypt::encrypt($this->projectId);
            $projectViewUrl     = $url . '/waiting-fund?id=' . $encryptedProjectId;
            $type               = $this->type ?? 'project';
            $subject            = $this->subject ?? 'Project Invitation';

            $projectData = [
                'customerName'       => $customerName,
                'transpactUrl'       => $transpactUrl,
                'transpactLoginUrl'  => $transpactLoginUrl,
                'projectViewUrl'     => $projectViewUrl,
                'type'               => $type,
                'subject'            => $subject,
            ];


            $logo                    = env('CPS_LOGO');
            $appUrl                  = env('APP_URL');
            $projectData['logoPath'] = $appUrl . '/' . $logo;

            $email = new Activationlink($projectData);
            Mail::to($this->email)->send($email);
        }
        if ($this->type == "customer" || $this->type == "contractor") {

            $userName     = $this->username ?? '';
            $baseUrl      = config('services.transpact_register_url');
            $appName      = config('app.name');
            $isTest       = env('IS_TEST');
            if ($isTest == true) {
                $transpactUrl       = "{$baseUrl}?RegEmail={$this->email}.test&co={$appName}";
            } else {
                $transpactUrl       = "{$baseUrl}?RegEmail={$this->email}&co={$appName}";
            }
            $type         = $this->type ?? '';
            $subject      = $this->subject ?? 'Welcome to CPS – Your Account Has Been Successfully Created!';

            $userData = [
                'userName'     => $userName,
                'transpactUrl' => $transpactUrl,
                'type'         => $type,
                'subject'      => $subject,
            ];

            $logo                 = env('CPS_LOGO');
            $appUrl               = env('APP_URL');
            $userData['logoPath'] = $appUrl . '/' . $logo;


            $email = new Activationlink($userData);
            Mail::to($this->email)->send($email);
        }
    }
}
