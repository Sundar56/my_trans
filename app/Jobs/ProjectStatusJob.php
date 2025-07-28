<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\ProjectStatusMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Crypt;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Project\Models\ProjectTasks;
use App\Traits\projectData;


class ProjectStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, projectData;

    protected $customerEmail;
    protected $projectId;
    protected $type;
    protected $taskId;
    protected $paymentType;

    /**
     * Create a new job instance.
     */
    public function __construct($customerEmail, $projectId, $type, $taskId = null, $paymentType = null)
    {
        $this->customerEmail = $customerEmail;
        $this->projectId     = $projectId;
        $this->type          = $type;
        $this->taskId        = $taskId;
        $this->paymentType   = $paymentType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $project         = $this->getProjectWithRelations($this->projectId);
        $tasks           = ProjectTasks::where('project_id', $this->projectId)->get();
        $taskStatusList  = config('custom.taskstatus');
        $taskDescription = 'The project includes the following tasks: ';
        $taskCounter     = 1;

        foreach ($tasks as $task) {
            $title  = $task->taskname;
            $budget = number_format($task->budget, 2);

            $taskDescription .= "Task {$taskCounter} is titled <strong>{$title}</strong> and is budgeted at <strong>£{$budget}</strong>. ";

            $task->taskStatusLabel = $taskStatusList[$task->taskstatus] ?? null;

            $taskCounter++;
        }

        $projectTask        = Project::with('tasks')->find($this->projectId);
        $taskNames          = $projectTask->tasks->pluck('taskname')->take(3)->implode(', ');
        $taskAmounts        = $projectTask->tasks->pluck('taskamount')->take(3)->implode(', ');
        $startDate          = \Carbon\Carbon::createFromFormat('Y-m-d', trim($project->startdate))->format('d F, Y');
        $endDate            = \Carbon\Carbon::createFromFormat('Y-m-d', trim($project->completiondate))->format('d F, Y');
        $encryptedProjectId = Crypt::encrypt($project->id);
        $url                = env('SITE_URL');
        $signupUrl          = $url . '/signup?id=' . $encryptedProjectId . '&email=' . urlencode($project->customer_email) . '&type=customer&name='. $project->customer_name . '&address=' . $project->projectlocation;
        $projectViewUrl     = $url . '/waiting-fund?id=' . $encryptedProjectId;
        $cpsMainLogo        = url('assets/img/logo.png');

        $projectStatusText = $this->getProjectStatusText($project, $this->type, $taskNames, $this->paymentType);
        $dynamicEmail      = $this->getReachEmail($project, $this->type, $taskNames);

        $projectData = [
            'project'            => $project,
            'tasks'              => $tasks,
            'taskNames'          => $taskNames,
            'taskAmounts'        => $taskAmounts,
            'projectStatus'      => config('custom.projectstatus')[$project->projectstatus] ?? null,
            'natureOfWork'       => config('custom.natureoftransaction')[$project->project_type] ?? 'Building',
            'typeOfBusiness'     => config('custom.businesstype')[$project->contractor_businesstype] ?? null,
            'currencyType'       => 'GBP',
            'currencySymbol'     => '£',
            'encryptedId'        => $encryptedProjectId,
            'formattedCreatedAt' => \Carbon\Carbon::parse($project->created_at)->format('d F, Y'),
            'projectStartDate'   => $startDate ?? null,
            'projectEndDate'     => $endDate ?? null,
            'signupUrl'          => $signupUrl ?? null,
            'projectViewUrl'     => $projectViewUrl ?? null,
            'cpsMainLogo'        => $cpsMainLogo ?? null,
            'projectStatusText'  => $projectStatusText,
            'dynamicEmail'       => $dynamicEmail,
        ];

        $logo     = env('CPS_LOGO');
        $appUrl   = env('APP_URL');
        $logoPath = $appUrl . '/' . $logo;
        $projectData['logoPath'] = $logoPath;

        if (file_exists($logoPath)) {
            $imageData = base64_encode(file_get_contents($logoPath));
            $mimeType  = mime_content_type($logoPath);

            $projectData['cpsLogo'] = "data:$mimeType;base64,$imageData";
        } else {
            $projectData['cpsLogo'] = null;
        }

        if ($project && $this->customerEmail) {
            Mail::to($this->customerEmail)->send(new ProjectStatusMail($projectData, $this->type));
        }
    }
    protected function getProjectStatusText($project, $type, $taskNames, $paymentType)
    {
        $customerName   = $project->customer_name ?? 'Customer';
        $contractorName = $project->contractor_name ?? 'Contractor';
        $projectName    = $project->projectname ?? 'Project';
        $taskId         = $this->taskId;
        $taskName       = ProjectTasks::where('id', $taskId)->select('taskname')->first();
        if ($paymentType) {
            $requestAmount     = $paymentType['requestAmount'] ?? null;
            $transpactNumber   = $paymentType['transpactNumber'] ?? null;
            $transpactLoginUrl = $paymentType['transpactLoginUrl'] ?? null;
        }

        switch ($type) {
            case 'Invite':
                return "
                <p>Hello {$customerName},</p>
                <p>You have been invited by <strong>{$contractorName}</strong> to collaborate on a new project titled <strong>{$projectName}</strong>.</p>
                <p>Please log in to your account to review and accept or decline the invitation.</p>
            ";

            case 'Accepted':
                return "
                <p>Hello {$contractorName},</p>
                <p><strong>{$customerName}</strong> has accepted your invitation to join your project <strong>{$projectName}</strong>.<p>
                <p>You will receive a notification once <strong>{$customerName}</strong> has deposited the funds into Transpact, 
                once this has been confirmed you are free to start the project!</p>
            ";

            case 'Decline':
                return "
                <p>Hello {$contractorName},</p>
                <p>Unfortunately, the customer <strong>{$customerName}</strong> has declined your invitation for the project <strong>{$projectName}</strong>.</p>
                <p>You can review the details or resend the invite if needed.</p>
            ";

            case 'Reinvite':
                return "
                <p>Hello {$customerName},</p>
                <p>This is a reminder that you have been invited by <strong>{$contractorName}</strong> to join the project <strong>{$projectName}</strong>.</p>
                <p>Please respond to the invitation at your earliest convenience.</p>
            ";

            case 'TranspactCreated':
                return "
                <p>Hello {$contractorName},</p>
                <p>The customer <strong>{$customerName}</strong> has created a new transpact for the project <strong>{$projectName}</strong>.</p>
                <p>Please review and proceed with your next steps.</p>
            ";

            case 'TaskCompleted':
                return "
                <p>Hello {$customerName},</p>
                <p>A {$taskName->taskname} has been marked as completed by <strong>{$contractorName}</strong> in the project <strong>{$projectName}</strong>.</p>
                <p>Please log in to review and verify the completion.</p>
            ";
            case 'TaskVerified':
                return "
                <p>Hello {$contractorName},</p>
                <p>The {$taskName->taskname} was verified by <strong>{$customerName}</strong> and marked as completed in the project <strong>{$projectName}</strong>.</p>
                <p>Please log in to review and verify the completion.</p>
            ";

            case 'AllTasksCompleted':
                return "
                <p>Hello {$customerName},</p>
                <p>All tasks - {$taskNames} - for the project <strong>{$projectName}</strong> have been completed by <strong>{$contractorName}</strong>.</p>
                <p>Please verify the project completion in your dashboard.</p>
            ";

            case 'ProjectVerified':
                return "
                <p>Hello {$contractorName},</p>
                <p>The customer <strong>{$customerName}</strong> has verified that all tasks for project <strong>{$projectName}</strong> are completed.</p>
                <p>You can now initiate the payment settlement if applicable.</p>
            ";
            case 'PaidByCustomer':
                return "
                <p>Hello {$contractorName},</p>
                <p>The project amount for project <strong>{$projectName}</strong> was successfully paid to Transpact by the <strong>{$customerName}</strong>.</p>
                <p>Both parties can now view the settlement details in their dashboards.</p>
            ";
            case 'PaidByContractor':
                return "
                <p>Hello {$customerName},</p>
                <p>The transaction fee for project <strong>{$projectName}</strong> was successfully paid to Transpact by the <strong>{$contractorName}</strong>.</p>
                <p>Both parties can now view the settlement details in their dashboards.</p>
            ";
            case 'AcceptByContractor':
                return "
                <p>Hello {$customerName},</p>
                <p><strong>{$contractorName}</strong> has accepted the terms and agreed to proceed with the transaction for the project <strong>{$projectName}</strong>.</p>
                <p>This transaction is now <strong>live and protected</strong> by Transpact.</p>
            ";
            case 'FullRequest':
                return "
                <p>Hello {$customerName},</p>
                <p>Contractor <strong>{$contractorName}</strong> requested you to release the full fund from the escrow of Transpact for the <strong>{$projectName}</strong> project</p>
                <p>Requested amount is <strong>{$requestAmount}</strong></p>
                <p>Please <a href=\"{$transpactLoginUrl}\" target=\"_blank\">click here</a> to login to Transpact, go to transaction number <strong>{$transpactNumber}</strong>, and release the full payment.</p>
            ";
            case 'PartialRequest':
                return "
                <p>Hello {$customerName},</p>
                <p>Contractor <strong>{$contractorName}</strong> requested you to release the partial fund from the escrow of Transpact for the <strong>{$projectName}</strong> project</p>
                <p>Requested amount is <strong>{$requestAmount}</strong></p>
                <p>Please <a href=\"{$transpactLoginUrl}\" target=\"_blank\">click here</a> to login to Transpact, go to transaction number <strong>{$transpactNumber}</strong>, and release the partial payment.</p>
            ";
            case 'EscrowFullSettled':
                return "
                <p>Hello,</p>
                <p>The full escrow amount for project <strong>{$projectName}</strong> has been successfully settled and paid.</p>
                <p>Both parties can now view the settlement details in their dashboards.</p>
            ";

            case 'EscrowPartialSettled':
                return "
                <p>Hello,</p>
                <p>A partial payment has been made from the escrow for project <strong>{$projectName}</strong>.</p>
                <p>Please review the transaction details in your account.</p>
            ";

            case 'DisputeRaisedbyCustomer':
                return "
                <p>Hello {$contractorName},</p>
                <p>A dispute has been raised by <strong>{$customerName}</strong> regarding the project <strong>{$projectName}</strong>.</p>
                <p>The other party and admin have been notified. Please await further instructions or log in to provide additional details.</p>
            ";
            case 'DisputeResolved':
                return "
                <p>Hello,</p>
                <p>The dispute for project <strong>{$projectName}</strong> has been resolved by the customer <strong>{$customerName}</strong>.</p>
                <p>You may proceed as per the updated agreement.</p>
            ";
            case 'CustomerSignRequired':
                return "
                <p>Hello,</p>
                <p>The project agreement for <strong>{$projectName}</strong> requires a signature from <strong>{$customerName}</strong>.</p>
                <p>Please request the customer to sign the agreement to continue.</p>
            ";
            case 'ContractorSignRequired':
                return "
                <p>Hello,</p>
                <p>The project agreement for <strong>{$projectName}</strong> requires a signature from <strong>{$contractorName}</strong>.</p>
                <p>Please request the customer to sign the agreement to continue.</p>
            ";
            case 'VoidTransaction':
                return "
                <p>Hello {$contractorName},</p>
                <p>The transaction for <strong>{$projectName}</strong> has been void/deleted by the <strong>{$customerName}</strong>.</p>
                <p>Please review the transaction details in your account.</p>
            ";

            default:
                return '<p>Status message not available.</p>';
        }
    }
    protected function getReachEmail($project, $type)
    {
        $customerEmail   = $project->customerEmail ?? $project->customer_email;
        $contractorEmail = $project->contractor_email ?? '';

        switch ($type) {
            case 'Invite':
                return  $contractorEmail;

            case 'Accepted':
                return  $customerEmail;

            case 'Decline':
                return  $customerEmail;

            case 'Reinvite':
                return  $contractorEmail;

            case 'TranspactCreated':
                return  $customerEmail;

            case 'TaskCompleted':
                return  $contractorEmail;
            case 'TaskVerified':
                return  $customerEmail;

            case 'AllTasksCompleted':
                return  $contractorEmail;

            case 'ProjectVerified':
                return  $customerEmail;
            case 'PaidByCustomer':
                return  $customerEmail;
            case 'PaidByContractor':
                return  $contractorEmail;

            case 'EscrowFullSettled':
                return  $customerEmail;

            case 'EscrowPartialSettled':
                return  $customerEmail;

            case 'DisputeRaisedbyCustomer':
                return  $customerEmail;
            case 'DisputeRaisedbyContractor':
                return  $contractorEmail;
            case 'DisputeRaisedbyAdmin':
                return  $customerEmail;
            case 'DisputeResolved':
                return  $customerEmail;
            case 'CustomerSignRequired':
                return  $contractorEmail;
            case 'ContractorSignRequired':
                return  $customerEmail;
            case 'VoidTransaction':
                return  $customerEmail;
            case 'FullRequest':
                return  $contractorEmail;
            case 'PartialRequest':
                return  $contractorEmail;
            default:
                return '<p>Email not available.</p>';
        }
    }
}
