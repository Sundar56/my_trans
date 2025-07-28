<?php

namespace App\Services;

use App\Api\Common\Models\WebhookHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Project\Models\ProjectTasks;
use App\Api\Frontend\Modules\Project\Models\Invitation;
use Illuminate\Support\Facades\Crypt;
use App\Models\User;
use App\Models\Notifications;
use App\Models\ModelHasRoles;
use App\Services\UploadFileService;
use App\Jobs\ProjectStatusJob;
use Illuminate\Support\Str;
use App\Traits\sendCpsNotification;
use App\Traits\projectData;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use App\Services\ProjectQueryService;
use App\Api\Frontend\Modules\Project\Models\ProjectContract;
use App\Api\Frontend\Modules\Project\Models\ProjectDecline;
use App\Api\Frontend\Modules\Transpact\Models\Transpact;
use App\Api\Frontend\Modules\Dispute\Models\Dispute;
use Illuminate\Contracts\Encryption\DecryptException;


class ProjectService
{
    use sendCpsNotification, projectData;

    protected $uploadFileService;
    protected $projectQueryService;

    public function __construct(UploadFileService $uploadFileService, ProjectQueryService $projectQueryService)
    {
        $this->uploadFileService = $uploadFileService;
        $this->projectQueryService = $projectQueryService;
    }
    /**
     * Create New Project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createProject(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'projectname' => 'required',
            ], [
                'projectname.required' => 'Project Name is required',
            ]);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            $userId  = $request->attributes->get('decoded_token')->get('id');
            $projectData = [
                'contractor_id'   => $userId ?? '',
                'projectname'     => $request->projectname ?? null,
                'project_type'    => $request->project_type ?? null,
                'currency'        => 1,
                'customer_email'  => $request->customer_email ?? null,
                'customer_name'   => $request->customer_name ?? null,
                'projectamount'   => is_numeric($request->projectamount) ? $request->projectamount : null,
                'is_create'       => 1,
                'projectlocation' => $request->projectlocation ?? null,
                'startdate'       => $request->startdate ?? null,
                'completiondate'  => $request->completiondate ?? null,
                'projectstatus'   => '16',
                'contractor_acceptance' => 0,
            ];

            if ($request->type == 'update' && $request->project_id) {
                $encryptedId = $request->project_id;
                $projectId = Crypt::decrypt($encryptedId);
                $projectData['customer_id'] = null;

                Project::where('id', $projectId)->update($projectData);
            } else {
                $projectData['customer_id'] = $customer->id ?? null;

                $project = Project::create($projectData);
                $projectId = $project->id;
                $encryptedId = Crypt::encrypt($projectId);
            }
            if ($request->projectcontract != null) {
                $response = $this->uploadFileService->uploadProjectContract($request, $userId, $projectId);

                if (isset($response['status']) && $response['status'] === false) {

                    return $this->returnError($response['message'], 404);
                }
            }
            return $this->returnSuccess(
                ['encryptedId' => $encryptedId, 'currency' => 1],
                'Project created successfully.'
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Create New Tasks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createTasks(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'taskname'    => 'required',
                'taskamount'  => 'required|numeric',
            ], [
                'taskname.required'    => 'Task Name is required',
                'taskamount.required'  => 'Task Amount is required',
                'taskamount.numeric'   => 'Task Amount must be a number',
            ]);

            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            $projectId = $this->decryptProjectId($request->project_id);

            ProjectTasks::create([
                'project_id'  => $projectId,
                'taskname'    => $request->taskname,
                'taskamount'  => $request->taskamount ?? null,
                'tasknotes'   => $request->tasknotes ?? null,
                'taskstatus'  => '0',
            ]);

            $totalTaskAmount = ProjectTasks::where('project_id', $projectId)->sum('taskamount');
            Project::where('id', $projectId)->update(['projectamount' => $totalTaskAmount]);

            return $this->returnSuccess(null, 'Task Created successfully.');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Task List Details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskList(Request $request)
    {
        try {
            $projectId        = $this->decryptProjectId($request->project_id);
            $projectTasks     = ProjectTasks::where('project_id', $projectId)->get();
            $taskTypes        = config('custom.taskstatus');
            $project          = Project::where('id', $projectId)->select('currency', 'projectamount')->first();
            $currencySymbols  = config('custom.currencysymbol');
            $projectAmount    = '£' . number_format($project->projectamount, 2);

            $projectTasks->transform(function ($task) use ($taskTypes, $currencySymbols, $project) {
                $currencySymbol     = $currencySymbols[$project->currency] ?? '';
                $encryptedProjectId = Crypt::encrypt($project->id);

                $task->taskStatusLabel    = $taskTypes[$task->taskstatus] ?? 'Pending';
                $task->currencySymbol     = $currencySymbol;
                $task->formattedTaskFee   = $currencySymbol . number_format($task->taskamount, 2);
                $task->encryptedProjectId = $encryptedProjectId;

                return $task;
            });
            // $data = [
            //     'projectAmount' => $projectAmount,
            //     'tasks' => $projectTasks,
            // ];

            return $this->returnSuccess($projectTasks, 'Project Tasks Details.');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Task List Details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskView(Request $request)
    {
        try {
            $taskId      = $request->task_id;
            $projectTask = ProjectTasks::where('id', $taskId)->first();

            if ($projectTask) {
                $taskTypes                       = config('custom.taskstatus');
                $projectTask->taskStatusLabel    = $taskTypes[$projectTask->taskstatus] ?? 'Pending';
                $encryptedProjectId              = Crypt::encrypt($projectTask->project_id);
                $projectTask->encryptedProjectId = $encryptedProjectId;

                $project = Project::where('id', $projectTask->project_id)->select('projectname')->first();
                $projectTask->projectName = $project->projectname;
            }

            return $this->returnSuccess($projectTask, 'Tasks Details');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Update Project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProject(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'projectname'    => 'required',
                'projectamount'  => 'required',
                'customer_email' => 'required|email',
            ], [
                'projectname.required'    => 'Project Name is required',
                'projectamount.required'  => 'Project Fee is required',
                'customer_email.required' => 'Customer Email is required',
                'customer_email.required' => 'Please provide a valid email address',
            ]);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            if (
                $request->projectamount === '0' ||
                $request->projectamount === 0 ||
                $request->projectamount === null ||
                $request->projectamount === 'undefined'
            ) {
                return $this->returnError('At least one task must be created before proceeding', 400);
            }
            $userId = $request->attributes->get('decoded_token')->get('id');
            $roleId = $request->attributes->get('decoded_token')->get('roleId');
            $email  = $request->attributes->get('decoded_token')->get('email');

            if ($request->customer_email == $email) {
                return $this->returnError('Customer email and Contractor email must be different', 400);
            }
            $projectId = null;
            $projectId = $this->decryptProjectId($request->project_id);

            $customer      = User::where('email', $request->customer_email)->first();
            $projectTask   = Project::with('tasks')->find($projectId);
            $taskNames     = $projectTask ? $projectTask->tasks->pluck('taskname')->take(3)->implode(', ') : '';
            $baseCondition = "The buyer reserves the right to inspect and approve all deliverables. Project: {$request->projectname}. Key tasks include: {$taskNames}. The total project value is £{$request->projectamount} . Work must meet the agreed scope, timeline, and quality standards. Any deviations must be approved in writing. Payment will be disbursed based on milestone completion. For your reference, The Job number is CPS{$projectId}";
            $Conditions    = Str::limit($baseCondition, 400, '.');
            $projectSign   = Project::where('id', $projectId)->select('contractor_sign')->first();
            if ($customer) {
                Project::where('id', $projectId)->update([
                    'contractor_id'   => $userId ?? '',
                    'customer_email'  => $request->customer_email ?? null,
                    'customer_name'   => $request->customer_name ?? null,
                    'projectlocation' => $request->projectlocation ?? null,
                    'conditions'      => $Conditions ?? null,
                    'customer_id'     => $customer->id,
                ]);
            } else {
                Project::where('id', $projectId)->update([
                    'contractor_id'   => $userId ?? '',
                    'customer_email'  => $request->customer_email ?? null,
                    'customer_name'   => $request->customer_name ?? null,
                    'projectlocation' => $request->projectlocation ?? null,
                    'conditions'      => $Conditions ?? null,
                ]);
            }
            if ($projectSign->contractor_sign == null) {
                return $this->returnError('Need Signature', 400);
            }

            if ($customer) {
                $project = Project::where('id', $projectId)->update([
                    'contractor_id'   => $userId ?? '',
                    'projectname'     => $request->projectname ?? null,
                    'customer_id'     => $customer->id,
                    'customer_email'  => $request->customer_email ?? null,
                    'projectamount'   => is_numeric($request->projectamount) ? $request->projectamount : null,
                    'project_type'    => $request->project_type ?? null,
                    'projectlocation' => $request->projectlocation ?? null,
                    'conditions'      => $Conditions ?? null,
                    'startdate'       => $request->startdate ?? null,
                    'completiondate'  => $request->completiondate ?? null,
                    'projectstatus'   => '0',
                    'status'          => '0',
                ]);
                $response = $this->uploadFileService->uploadProjectContract($request, $userId, $projectId);

                if (isset($response['status']) && $response['status'] === false) {
                    return $this->returnError($response['message'], 404);
                }
                $projectUpdate = Project::where('id', $projectId)->select('is_create')->first();
                if ($roleId == 3 && ($projectUpdate->is_create == 1)) {
                    $this->storeInvitation($userId, $customer->id, $request->customer_email, $projectId);
                    ProjectStatusJob::dispatch($request->customer_email, $projectId, 'Invite', null, null);
                }

                Project::where('id', $projectId)->update([
                    'is_create' => 0
                ]);

                return $this->returnSuccess(null, 'Project updated successfully');
            } else {
                if ($roleId == 3) {
                    Project::where('id', $projectId)->update([
                        'contractor_id'   => $userId ?? '',
                        'projectname'     => $request->projectname ?? null,
                        'customer_id'     => null,
                        'customer_email'  => $request->customer_email ?? null,
                        'projectamount'   => $request->projectamount ?? null,
                        'project_type'    => $request->project_type ?? null,
                        'projectlocation' => $request->projectlocation ?? null,
                        'conditions'      => $Conditions ?? null,
                        'startdate'       => $request->startdate ?? null,
                        'completiondate'  => $request->completiondate ?? null,
                        'projectstatus'   => '0',
                        'status'          => '0',
                    ]);
                    $response = $this->uploadFileService->uploadProjectContract($request, $userId, $projectId);

                    if (isset($response['status']) && $response['status'] === false) {
                        return $this->returnError($response['message'], 404);
                    }
                    Invitation::create([
                        'invitefrom'   => $userId ?? '',
                        'inviteto'     => $customer->id ?? null,
                        'invitemail'   => $request->customer_email ?? null,
                        'project_id'   => $projectId,
                        'invitestatus' => '0',
                        'reinvite'     => 0,
                    ]);
                    $projectUpdate = Project::where('id', $projectId)->select('is_create')->first();

                    if ($projectUpdate->is_create == 1) {
                        ProjectStatusJob::dispatch($request->customer_email, $projectId, 'Invite', null, null);
                    }

                    Project::where('id', $projectId)->update([
                        'is_create' => 0
                    ]);

                    return $this->returnSuccess(null, 'Project updated, but Customer not found. Email sent to that email');
                }
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Update Tasks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTasks(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'taskname'    => 'required',
                'taskamount'  => 'required|numeric',
            ], [
                'taskname.required'    => 'Task Name is required',
                'taskamount.required'  => 'Task Amount is required',
                'taskamount.numeric'   => 'Task Amount must be a number',
            ]);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            ProjectTasks::where('id', $request->taskId)->update([
                'taskname'   => $request->taskname,
                'taskamount' => $request->taskamount ?? null,
                'tasknotes'  => $request->tasknotes ?? null,
            ]);

            return $this->returnSuccess(null, 'Task Updated successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Store invitation details in project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function storeInvitation($userId, $customerId, $customerMail, $projectId)
    {
        $invitation    = Invitation::where('project_id', $projectId)->first();
        $user          = User::where('id', $userId)->first();
        $projectInfo   = Project::where('id', $projectId)->first();
        $customMessage = "{$user->name} sent an invitation for the {$projectInfo->projectname} Project";

        $channel    = 'invitechannel';
        $channelData = [
            'message'    => $customMessage,
            'invitefrom' => $userId,
            'inviteto'   => $customerId,
            'projectId'  => $projectId,
        ];
        // if (isset($channelData)) {
        //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
        // }
        $this->sendCpsNotification($channel, [
            'message'    => $customMessage,
            'invitefrom' => $userId,
            'inviteto'   => $customerId,
            'projectId'  => $projectId,
        ], 0, 'broadcast');

        if ($invitation) {
            $invitation->update([
                'inviteto'   => $customerId,
                'invitemail' => $customerMail ?? null,
                'project_id' => $projectId,
                'reinvite'   => 0,
            ]);
        } else {
            $invitation = Invitation::create([
                'invitefrom'   => $userId ?? '',
                'inviteto'     => $customerId,
                'invitemail'   => $customerMail ?? null,
                'invitestatus' => '0',
                'project_id'   => $projectId,
                'reinvite'     => 0,
            ]);
        }

        $this->updateNotification($projectId, $customMessage, 'contractor');
    }
    /**
     * Update project status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function acceptInvitation(Request $request)
    {
        try {
            $decryptedId   = $request->project_id;
            $projectId     = Crypt::decrypt($decryptedId);
            $projectStatus = $request->projectstatus;

            Project::where('id', $projectId)->update([
                'projectstatus' => $projectStatus,
                'status'        => '1',
            ]);
            if ($projectStatus == '8') {
                Invitation::where('project_id', $projectId)->update([
                    'invitestatus' => '2'
                ]);
            } else {
                Invitation::where('project_id', $projectId)->update([
                    'invitestatus' => '1'
                ]);
            }

            $project         = Project::where('id', $projectId)->select('customer_id', 'contractor_id', 'projectname')->first();
            $user            = User::where('id', $project->customer_id)->select('name')->first();
            $contractorEmail = User::where('id', $project->contractor_id)->select('email')->first();

            if ($project && ($projectStatus == '8')) {
                ProjectDecline::create([
                    'project_id'    => $projectId,
                    'customer_id'   => $project->customer_id,
                    'contractor_id' => $project->contractor_id,
                    'reason'        => $request->reason,
                    'status'        => 0,
                ]);

                ProjectStatusJob::dispatch($contractorEmail->email, $projectId, 'Decline', null, null);
            }
            $projectStatusList = config('custom.projectstatus');
            $projectStatus     = $projectStatusList[$request->projectstatus];
            $customMessage     = "{$project->projectname} Project status {$projectStatus} by {$user->name}";
            $channel           = 'invitechannel';
            $channelData = [
                'message'      => $customMessage,
                'customerId'   => $project->customer_id,
                'contractorId' => $project->contractor_id,
                'projectId'    => $decryptedId,
            ];
            // if (isset($channelData)) {
            //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
            // }

            $this->sendCpsNotification($channel, [
                'message'      => $customMessage,
                'customerId'   => $project->customer_id,
                'contractorId' => $project->contractor_id,
                'projectId'    => $decryptedId,
            ], 0, 'broadcast');

            $this->updateNotification($projectId, $customMessage, 'customer');

            $message = "The project '{$project->projectname}' has been accepted by you. Kindly proceed to create a Transpact by clicking on 'Pay into Escrow'";
            $this->updateNotification($projectId, $message, 'contractor');

            ProjectStatusJob::dispatch($contractorEmail->email, $projectId, 'Accepted', null, null);

            return $this->returnSuccess(null, 'Project Status updated');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Update notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateNotification($projectId, $customMessage, $userType)
    {
        $project = Project::where('id', $projectId)->select('customer_id', 'contractor_id')->first();

        if (!$project || !in_array($userType, ['customer', 'contractor'])) {
            return;
        }
        if (is_numeric($userType)) {
            $dispute = Dispute::find($userType);
            if (!$dispute) {
                return;
            }
            $fromId = $dispute->created_by;
            $toId   = $dispute->sent_to;
        } elseif (in_array($userType, ['customer', 'contractor'])) {
            $fromId = $userType === 'customer' ? $project->customer_id : $project->contractor_id;
            $toId   = $userType === 'customer' ? $project->contractor_id : $project->customer_id;
        } else {
            return;
        }

        Notifications::create([
            'from_id'    => $fromId,
            'to_id'      => $toId,
            'message'    => $customMessage,
            'isread'     => 0,
            'project_id' => $projectId,
        ]);
    }
    /**
     * Re-invitation details in project.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reInviteProject(Request $request)
    {
        try {
            $userId      = $request->attributes->get('decoded_token')->get('id');
            $decryptedId = $request->project_id;
            $projectId   = Crypt::decrypt($decryptedId);
            $project     = Project::where('id', $projectId)->first();
            $user        = User::where('id', $project->contractor_id)->select('name')->first();

            Invitation::create([
                'invitefrom'   => $project->contractor_id ?? $userId,
                'inviteto'     => $project->customer_id ?? null,
                'invitemail'   => $project->customer_email ?? null,
                'invitestatus' => '0',
                'project_id'   => $projectId,
                'reinvite'     => 1,
            ]);
            if ($project->projectstatus == '8') {
                Project::where('id', $projectId)->update([
                    'projectstatus' => '0'
                ]);
            }
            $customMessage = "Invitation resent by {$user->name} for the {$project->projectname} Project";
            $channel       = 'invitechannel';
            $channelData = [
                'message'    => $customMessage,
                'invitefrom' => $project->contractor_id,
                'inviteto'   => $project->customer_id,
                'projectId'  => $decryptedId,
            ];
            // if (isset($channelData)) {
            //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
            // }
            $this->sendCpsNotification($channel, [
                'message'    => $customMessage,
                'invitefrom' => $project->contractor_id,
                'inviteto'   => $project->customer_id,
                'projectId'  => $decryptedId,
            ], 0, 'broadcast');

            $this->updateNotification($projectId, $customMessage, 'contractor');
            ProjectStatusJob::dispatch($project->customer_email, $projectId, 'Reinvite', null, null);

            return $this->returnSuccess(null, 'Invitaion Resent successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Update project status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskStatusUpdate(Request $request)
    {
        try {
            $userId     = $request->attributes->get('decoded_token')->get('id');
            $roleId     = $request->attributes->get('decoded_token')->get('roleId');
            if ($roleId == 3) {
                $taskId     = $request->task_id;
                $taskStatus = $request->taskstatus;
                if ($taskStatus == '2') {
                    ProjectTasks::where('id', $taskId)->update([
                        'taskstatus' => '2',
                        'is_verified' => '0',
                    ]);
                }
                $projectTask     = ProjectTasks::where('id', $taskId)->select('taskname', 'project_id')->first();
                $project         = Project::where('id', $projectTask->project_id)->select('customer_id', 'contractor_id', 'id', 'projectname', 'customer_email')->first();
                if ($taskStatus == '2') {
                    Project::where('id', $projectTask->project_id)->update([
                        'status' => '3',
                    ]);
                }
                $user            = User::where('id', $userId)->select('name')->first();
                $taskStatusList  = config('custom.taskstatus');
                $taskStatusLabel = $taskStatusList[$request->taskstatus];

                $customMessage = "{$projectTask->taskname} task marked as {$taskStatusLabel} by {$user->name} in the {$project->projectname} Project";
                $channel       = 'invitechannel';
                $channelData = [
                    'message'            => $customMessage,
                    'taskCustomerId'     => $project->customer_id,
                    'taskContractorId'   => $project->contractor_id,
                    'projectId'          => $projectTask->project_id,
                    'customerTaskStatus' => 'Verify',
                    'updatedBy'          => 'Contractor',
                ];
                // if (isset($channelData)) {
                //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
                // }
                $this->sendCpsNotification($channel, [
                    'message'            => $customMessage,
                    'taskCustomerId'     => $project->customer_id,
                    'taskContractorId'   => $project->contractor_id,
                    'projectId'          => $projectTask->project_id,
                    'customerTaskStatus' => 'Verify',
                    'updatedBy'          => 'Contractor',
                ], 0, 'broadcast');

                $this->updateNotification($project->id, $customMessage, 'contractor');

                ProjectStatusJob::dispatch($project->customer_email, $projectTask->project_id, 'TaskCompleted', $taskId, null);

                return $this->returnSuccess(null, 'Task Status updated');
            } else {

                return $this->returnError('Task can only updated by Contractor', 400);
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Project view contract.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewProjectContract(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'   => 'sometimes|integer|min:1',
                'length' => 'sometimes|integer|min:1|max:500',
                'order'  => 'sometimes|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            $page      = $request->input('page', 1);
            $perPage   = $request->input('length', env("TABLE_LIST_LENGTH", 10));
            $order     = $request->input('order', 'desc');
            // $projectId = $request->project_id;
            $projectId = Crypt::decrypt($request->project_id);
            $project   = Project::where('id', $projectId)->select('projectname')->first();

            $contractQuery = ProjectContract::where('project_id', $projectId)
                ->orderBy('created_at', $order);

            $contracts = $contractQuery->paginate($perPage, ['*'], 'page', $page);

            $contracts->getCollection()->transform(function ($contract) use ($project) {
                $contract->createdAtFormatted = \Carbon\Carbon::parse($contract->created_at)->format('d M Y, h:i A');
                $contract->projectName        = $project->projectname ?? null;
                $contract->fileExtension      = pathinfo($contract->contract, PATHINFO_EXTENSION);
                return $contract;
            });

            return $this->returnSuccess([
                'list'         => $contracts->items(),
                'currentPage'  => $contracts->currentPage(),
                'totalPages'   => $contracts->lastPage(),
                'recordsTotal' => $contracts->total()
            ], 'Project Contracts List.');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Project view contract.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function projectHistory(Request $request)
    {
        try {
            // $projectId        = $request->project_id;
            $projectId        = Crypt::decrypt($request->project_id);
            $roleId           = $request->attributes->get('decoded_token')->get('roleId');
            $project          = Project::where('id', $projectId)->select('projectstatus', 'updated_at', 'customer_id')->first();
            $projectInvite    = Invitation::where('project_id', $projectId)->where('reinvite', 0)->first();
            $projectReInvite  = Invitation::where('project_id', $projectId)->where('reinvite', 1)->first();
            $projectDecline   = ProjectDecline::where('project_id', $projectId)->first();
            $transpact        = Transpact::where('project_id', $projectId)->select('transpactnumber')->first();

            $statusList = [];

            $transpactDetails = $transpact
                ? WebhookHistory::where('transactionid', $transpact->transpactnumber)->get()
                : collect();
            $getUserName = fn($userId) => User::where('id', $userId)->value('name');
            // Initial Invite
            if ($projectInvite && $roleId) {
                $userId = $projectInvite->invitefrom;
                $statusList[] = [
                    'type'   => 'Initial Invite',
                    'user'   => 'Invitation sent by ' . $getUserName($userId),
                    'roleId' => $roleId,
                    'date'   => $projectInvite->created_at->format('d M Y, h:i A'),
                    'status' => config('custom.invitestatus')[$projectInvite->invitestatus] ?? null,
                ];
            }
            // Decline
            if ($projectDecline && $roleId) {
                $userId = $projectDecline->customer_id;
                $statusList[] = [
                    'type'   => 'Decline',
                    'user'   => 'Invitation declined by ' . $getUserName($userId),
                    'roleId' => $roleId,
                    'date'   => $projectDecline->created_at->format('d M Y, h:i A'),
                    'status' => $projectDecline->reason ?? null,
                ];
            }

            // Re-invite
            if ($projectReInvite && $roleId) {
                $userId =  $projectReInvite->invitefrom;
                $statusList[] = [
                    'type'   => 'Re-invite',
                    'user'   => 'Invitation resent by ' . $getUserName($userId),
                    'roleId' => $roleId,
                    'date'   => $projectReInvite->created_at->format('d M Y, h:i A'),
                    'status' => config('custom.invitestatus')[$projectReInvite->invitestatus] ?? null,
                ];
            }

            // Project Status
            if ($project && $project->projectstatus && $roleId) {
                $projectStatus = config('custom.projectstatus');

                if ($roleId == 2 &&  $project->projectstatus) {
                    if ($project->projectstatus == '0') {
                        $projectstatusLabel = "Waiting for your Approval";
                    }
                    if ($project->projectstatus == '1') {
                        $projectstatusLabel = "Waiting for you to deposit";
                    } elseif ($project->projectstatus == '13') {
                        $projectstatusLabel = 'Funds Fully Released' ?? null;
                    } elseif ($project->projectstatus == '12') {
                        $projectstatusLabel = 'Partial Funds Released' ?? null;
                    } else {
                        $projectstatusLabel = $projectStatus[$project->projectstatus] ?? null;
                    }
                }
                if ($roleId == 3 &&  $project->projectstatus) {
                    if ($project->projectstatus == '13') {
                        $projectstatusLabel = 'Funds Fully Released' ?? null;
                    } elseif ($project->projectstatus == '12') {
                        $projectstatusLabel = 'Partial Funds Released' ?? null;
                    } else {
                        $projectstatusLabel = $projectStatus[$project->projectstatus] ?? null;
                    }
                }
                $statusList[] = [
                    'type'   => 'Project Status',
                    'user'   => $getUserName($project->customer_id),
                    'status' => $projectstatusLabel ?? null,
                    'date'   => $project->updated_at->format('d M Y, h:i A'),
                ];
            }

            // Transactions
            foreach ($transpactDetails as $detail) {
                if ($detail->eventid == 10) {
                    $transpact = Transpact::where('transpactnumber', $detail->transactionid)->select('project_id')->first();
                    $project   = Project::where('id', $transpact->project_id)->select('customer_id', 'escrowfund', 'projectamount')->first();
                    $user      = User::where('id', $project->customer_id)->select('name')->first();
                    $userName  = $user->name ?? 'N/A';
                    $status    = "Project fund fully released by {$userName}";
                } else {
                    $userEmail = $roleId == 2 ? ($detail->contractor_email ?? $detail->customer_email)
                        : ($detail->customer_email ?? $detail->contractor_email);
                    $user     = User::where('email', $userEmail)->select('name')->first();
                    $transpact = Transpact::where('transpactnumber', $detail->transactionid)->select('project_id')->first();
                    $project   = Project::where('id', $transpact->project_id)->select('customer_id', 'escrowfund', 'projectamount')->first();
                    $userName = $user->name ?? 'N/A';
                    if ($detail->eventid == 12) {
                        $releasedAmount     = $project->projectamount - $project->escrowfund;
                        $releasedFormatted  = '£' . number_format($releasedAmount, 2);
                    } else {
                        $releasedAmount     = '£ 0.0';
                        $releasedFormatted  = '£ 0.0';
                    }

                    $eventMessages = [
                        16 => "Amount £{$project->escrowfund} paid to Transpact by {$userName}",
                        10 => "Project fund fully released by {$userName}",
                        12 => "Partial Fund {$releasedFormatted} released by {$userName}",
                        6  => "Contractor accepted the Transaction",
                    ];
                    $status = $eventMessages[$detail->eventid] ?? "Event ID: {$detail->eventid}";
                }

                $statusList[] = [
                    'type'   => 'Transaction',
                    'user'   => $userName,
                    'status' => $status,
                    'date'   => \Carbon\Carbon::parse($detail->created_at)->format('d M Y, h:i A'),
                ];
            }

            usort($statusList, function ($a, $b) {
                return strtotime($b['date']) <=> strtotime($a['date']);
            });

            return $this->returnSuccess($statusList, 'Project status details retrieved successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Remove Project .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function projectDelete(Request $request)
    {
        try {
            $projectId = $this->decryptProjectId($request->project_id);
            $project   = Project::find($projectId);

            if (!$project) {
                return $this->returnError('Project not found', 404);
            }
            $project->delete();

            return $this->returnSuccess(null, 'Project deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Remove Task .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function taskDelete(Request $request)
    {
        try {

            $taskId = $request->task_id;
            $task   = ProjectTasks::find($taskId);

            if (!$task) {
                return [
                    'status'     => false,
                    'message'    => 'Task not found.',
                    'data'       => null,
                    'statusCode' => 404,
                ];
            }
            $task->delete();

            return $this->returnSuccess(null, 'Task deleted successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Verify task by Customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyTask(Request $request)
    {
        try {
            $userId = $request->attributes->get('decoded_token')->get('id');
            $roleId = $request->attributes->get('decoded_token')->get('roleId');
            if ($roleId == 2) {
                $taskId     = $request->task_id;

                ProjectTasks::where('id', $taskId)->update([
                    'taskstatus'  => '2',
                    'is_verified' => '1',
                ]);

                $projectTask   = ProjectTasks::where('id', $taskId)->select('taskname', 'project_id')->first();
                $project       = Project::where('id', $projectTask->project_id)->select('customer_id', 'contractor_id', 'id', 'projectname', 'customer_email')->first();
                $user          = User::where('id', $userId)->select('name')->first();
                $contractor    = User::where('id', $project->contractor_id)->select('email')->first();
                $customMessage = "The '{$projectTask->taskname}' task was verified by {$user->name} and marked as completed in the {$project->projectname} project";
                if ($project) {
                    $project->status = '4';
                    $project->save();
                }
                $channel       = 'invitechannel';
                $channelData = [
                    'message'          => $customMessage,
                    'taskCustomerId'   => $project->customer_id,
                    'taskContractorId' => $project->contractor_id,
                    'projectId'        => $projectTask->project_id,
                    'updatedBy'        => 'Customer',
                ];
                // if (isset($channelData)) {
                //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
                // }
                $this->sendCpsNotification($channel, [
                    'message'          => $customMessage,
                    'taskCustomerId'   => $project->customer_id,
                    'taskContractorId' => $project->contractor_id,
                    'projectId'        => $projectTask->project_id,
                    'updatedBy'        => 'Customer',
                ], 0, 'broadcast');

                $this->updateNotification($project->id, $customMessage, 'customer');

                ProjectStatusJob::dispatch($contractor->email, $projectTask->project_id, 'TaskVerified', $taskId, null);

                if ($projectTask) {
                    $projectId      = $projectTask->project_id;
                    $totalTasks     = ProjectTasks::where('project_id', $projectId)->count();
                    $completedTasks = ProjectTasks::where('project_id', $projectId)->where('taskstatus', '2')->count();

                    if ((int)$totalTasks == (int)$completedTasks) {
                        Project::where('id', $projectId)->update(['projectstatus' => '11', 'status' => '4']);
                        $message       = "All tasks for the {$project->projectname} project have been completed";
                        $channel       = 'invitechannel';
                        $channelData = [
                            'message'          => $message,
                            'taskCustomerId'   => $project->customer_id,
                            'taskContractorId' => $project->contractor_id,
                            'projectId'        => $projectTask->project_id,
                            'updatedBy'        => 'Contractor',
                        ];
                        // if (isset($channelData)) {
                        //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
                        // }
                        $this->sendCpsNotification($channel, [
                            'message'          => $message,
                            'taskCustomerId'   => $project->customer_id,
                            'taskContractorId' => $project->contractor_id,
                            'projectId'        => $projectTask->project_id,
                            'updatedBy'        => 'Contractor',
                        ], 0, 'broadcast');

                        $this->updateNotification($project->id, $message, 'contractor');
                        ProjectStatusJob::dispatch($project->customer_email, $projectTask->project_id, 'AllTasksCompleted', null, null);
                    }
                }

                return $this->returnSuccess(null, 'The Task was verified and marked as completed');
            } else {
                return $this->returnError('Task can only Verified by Customer', 400);
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Verify task by Customer.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyProject(Request $request)
    {
        try {
            $userId = $request->attributes->get('decoded_token')->get('id');
            $roleId = $request->attributes->get('decoded_token')->get('roleId');
            $projectId = $this->decryptProjectId($request->project_id);

            if ($roleId == 2) {
                $totalTasks     = ProjectTasks::where('project_id', $projectId)->count();
                $completedTasks = ProjectTasks::where('project_id', $projectId)->where('taskstatus', '2')->count();

                if ((int)$totalTasks != (int)$completedTasks) {

                    return $this->returnError('Complete all tasks in this project', 400);
                } else {
                    Project::where('id', $projectId)->update([
                        'projectstatus' => '4',
                        'status'        => '3'
                    ]);

                    $project       = Project::where('id', $projectId)->select('customer_id', 'contractor_id', 'id', 'projectname', 'customer_email')->first();
                    $user          = User::where('id', $userId)->select('name')->first();
                    $contractor    = User::where('id', $project->contractor_id)->select('email')->first();
                    $customMessage = "The '{$project->projectname}' project was verified by {$user->name} and marked as completed";

                    $channel  = 'invitechannel';
                    $channelData = [
                        'message'          => $customMessage,
                        'taskCustomerId'   => $project->customer_id,
                        'taskContractorId' => $project->contractor_id,
                        'projectId'        => $projectId,
                        'updatedBy'        => 'Customer',
                    ];
                    // if (isset($channelData)) {
                    //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
                    // }
                    $this->sendCpsNotification($channel, [
                        'message'          => $customMessage,
                        'taskCustomerId'   => $project->customer_id,
                        'taskContractorId' => $project->contractor_id,
                        'projectId'        => $projectId,
                        'updatedBy'        => 'Customer',
                    ], 0, 'broadcast');

                    $this->updateNotification($projectId, $customMessage, 'customer');
                    ProjectStatusJob::dispatch($contractor->email, $projectId, 'ProjectVerified', null, null);

                    return $this->returnSuccess(null, 'Project was verified and marked as completed');
                }
            } else {
                return $this->returnError('Project can only verified by Customer', 400);
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Create Project Agreement.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function projectAgreement(Request $request)
    {
        try {
            $userId     = $request->attributes->get('decoded_token')->get('id');
            $roleId     = $request->attributes->get('decoded_token')->get('roleId');
            $projectId  = $this->decryptProjectId($request->project_id);
            $project    = Project::where('id', $projectId)->first();
            $customer   = User::where('id', $project->customer_id)->first();
            $contractor = User::where('id', $project->contractor_id)->first();

            if ($project->agreement != null && $project->customer_sign == null) {
                $message = ($roleId == 3) ? 'Customer signature required' : 'Signature required';

                return $this->returnSuccess($project->agreement, $message);
            } else {
                return $this->returnSuccess($project->agreement, 'You can now view the signed agreement.');
            }

            if ($project->customer_id == null && $roleId == 3) {
                ProjectStatusJob::dispatch($project->customer_email, $projectId, 'Reinvite', null, null);
                return $this->returnError('Customer not found. Invitation re-sent', 400);
            } else {
                return $this->returnError('Signature is required', 400);
            }
            if ($project->customer_sign == null && $roleId == 3) {
                ProjectStatusJob::dispatch($customer->email, $projectId, 'CustomerSignRequired', null, null);
                return $this->returnError('Customer signature is required', 400);
            } else {
                return $this->returnError('Signature is required', 400);
            }
            if ($project->agreement == null && $project->contractor_sign == null && $roleId == 3) {

                return $this->returnSuccess(null, 'You need to update the project details before viewing the agreement');
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * update user signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateUserSignature(Request $request)
    {
        try {
            $userId    = $request->attributes->get('decoded_token')->get('id');
            $roleId    = $request->attributes->get('decoded_token')->get('roleId');
            $projectId = $this->decryptProjectId($request->project_id);
            $project   = Project::where('id', $projectId)->first();

            if ($project->contractor_sign == null || $project->customer_sign == null) {
                $response = $this->uploadFileService->uploadSignature($request, $userId, $roleId, $projectId);

                if (isset($response['status']) && $response['status'] === false) {
                    return $this->returnError($response['message'], 404);
                }
            }

            return $this->returnSuccess(null, 'Signature Updated');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * update user signature.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function agreementInfo(Request $request)
    {
        try {
            $projectId   = $this->decryptProjectId($request->project_id);
            $projectinfo = Project::where('id', $projectId)->first();
            $project     = $this->getProjectWithRelations($projectId);
            $contractor  = User::leftJoin('businessprofile as contractor_profile', 'users.id', '=', 'contractor_profile.user_id')
                ->select('contractor_profile.address as address', 'users.name')
                ->where('users.id', $projectinfo->contractor_id)
                ->first();
            $tasks          = ProjectTasks::where('project_id', $projectId)->get();
            $projectTask    = Project::with('tasks')->find($projectId);
            $taskNames      = $projectTask->tasks->pluck('taskname')->take(3)->implode(', ');
            $projectAmount  = '£' . number_format($projectinfo->projectamount, 2);
            $startDate = null;
            $endDate = null;
            try {
                if (!empty($projectinfo->startdate)) {
                    $startDate      = \Carbon\Carbon::createFromFormat('Y-m-d', trim($projectinfo->startdate))
                        ->format('d F, Y');
                }

                if (!empty($projectinfo->completiondate)) {
                    $endDate        = \Carbon\Carbon::createFromFormat('Y-m-d', trim($projectinfo->completiondate))
                        ->format('d F, Y');
                }
            } catch (\Exception $e) {
                $startDate = null;
                $endDate = null;
            }
            $totalTaskAmount  = ProjectTasks::where('project_id', $projectId)->sum('taskamount');
            $formattedTotal   = '£' . number_format($totalTaskAmount, 2);

            foreach ($tasks as $task) {
                $task->taskAmount = '£' . number_format($task->taskamount, 2) ?? null;
            }
            $signatures = $this->getProjectSignatureData($projectinfo);

            $contractorSignData = $signatures['contractor_signature'];
            $customerSignData   = $signatures['customer_signature'];

            $customer = null;

            if ($request->customer_email) {
                $customer = User::where('email', $request->customer_email)
                    ->select('name', 'address')
                    ->first();
            } elseif ($projectinfo->customer_id) {
                $customer = User::where('id', $projectinfo->customer_id)
                    ->select('name', 'address')
                    ->first();
            } else {
                $customer = null;
            }
            $customerName    = $request->customer_name ?? null;
            $projectLocation = $projectinfo->projectlocation ?? null;

            $data = [
                'project'            => $project,
                'projectName'        => $projectinfo->projectname ?? null,
                'formattedCreatedAt' => \Carbon\Carbon::parse($projectinfo->created_at)->format('d F, Y'),
                'contractorName'     => $contractor->name ?? null,
                'contractorAddress'  => $contractor->address ?? null,
                'projectLocation'    => $projectLocation ?? null,
                'tasks'              => $tasks,
                'taskNames'          => $taskNames ?? null,
                'currencyType'       => config('custom.currency')[$projectinfo->currency] ?? 'GBP',
                'currencySymbol'     => config('custom.currencysymbol')[$projectinfo->currency] ?? '£',
                'projectId'          => "CPS_$projectinfo->id",
                'startDate'          => $startDate ?? null,
                'completionDate'     => $endDate ?? null,
                'projectAmount'      => $projectAmount ?? null,
                'customerName'       => $customer->name ?? $customerName,
                'customerAddress'    => $customer->address ?? $projectLocation,
                'customerSign'       => $customerSignData,
                'contractorSign'     => $contractorSignData,
                'projectStartDate'   => $startDate ?? null,
                'projectEndDate'     => $endDate ?? null,
                'totalTaskAmount'    => $formattedTotal ?? null,
                'transpactText'      => 'Working in partnership with Transpact to keep your money secure.',
            ];
            $logo = $this->getLogoData();
            if ($logo) {
                $data['cpsMainLogo']   = $logo['cpsMainLogo'];
                $data['cpsLogo']       = $logo['cpsLogo'];
                $data['transpactLogo'] = $logo['transpactLogo'];
            }

            $pdfContent = $this->generatePdfFromProjectData($data);
            $filename   = $this->getAgreementFilename($projectinfo);
            $path       = "/uploadassets/projects/{$projectinfo->id}/";
            $filePath   = $this->uploadFiles($filename, $path, $pdfContent);

            $projectinfo->agreement = $filePath;
            $projectinfo->save();

            return $this->returnSuccess($data, 'Project Agreement data');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * update project status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProjectStatus($data)
    {
        $transpactNumber    = $data['transactionID'] ?? null;
        $transactionEventID = $data['transactionEventID'] ?? null;
        $liveProtected      = $data['liveProtected'] ?? null;
        $amount             = isset($data['amount'])
            ? number_format((float)$data['amount'], 2, '.', '')
            : '0.00';

        $transpact = Transpact::where('transpactnumber', $transpactNumber)->first();
        $projectId = $transpact->project_id ?? null;

        if (!$projectId) {
            Log::warning("Project ID not found for transaction: {$transpactNumber}");
            return 1;
        }

        $project         = Project::find($projectId);
        $customer        = User::where('id', $project->customer_id)->select('email', 'name')->first();
        $customerEmail   = $customer->email ?? $project->customer_email;
        $customerName    = $customer->name ?? $project->customer_name;
        $contractor      = User::where('id', $project->contractor_id)->select('email', 'name')->first();
        $contractorEmail = $contractor->email;
        $contractorName  = $contractor->name;

        $descriptionEmail = $data['description'] ?? '';
        $user             = User::where('email', $descriptionEmail)->first();

        $updateData   = [];
        $shouldUpdate = false;
        $roleId       = null;
        $roleName     = 'Unknown Role';

        if ($user && $project) {
            $roleId   = ModelHasRoles::where('model_id', $user->id)->value('role_id');
            $roleMap  = [2 => 'Customer', 3 => 'Contractor'];
            $roleName = $roleMap[$roleId] ?? 'Unknown Role';

            if (in_array($transactionEventID, ['16', '12']) && $roleName === 'Customer') {
                if ($transactionEventID === '16') {
                    $updateData['escrowfund']  = $amount;
                    $updateData['balancefund']  = $amount;
                    $updateData['status']      = '2';
                    ProjectTasks::where('project_id', $projectId)->update(['taskstatus' => '1']);

                    if ($liveProtected == 1) {
                        $updateData['projectstatus'] = '15';
                        $updateData['contractor_acceptance'] = 1;
                        $afterDeduction = $amount - $project->admincommission;
                        $updateData['balancefund'] = $afterDeduction;
                    } else {
                        $updateData['projectstatus'] = '17';
                        $updateData['balancefund'] = $amount;
                    }
                } elseif ($transactionEventID === '12') {
                    $updateData['projectstatus'] = '12';
                    $updateData['status'] = '4';
                    $updateData['balancefund']   = $project->balancefund - $amount;
                }
                $shouldUpdate = true;
            }
            if (in_array($transactionEventID, ['16', '6']) && $roleName === 'Contractor') {
                $updateData['projectstatus'] = '15';
                $updateData['contractor_acceptance'] = 1;
                $afterDeduction              = $project->escrowfund - $project->admincommission;
                $updateData['balancefund']   = $afterDeduction;
                $shouldUpdate                = true;
            }
        } else {
            Log::warning("User not found for email: {$descriptionEmail}");
            return 1;
        }

        if (in_array($transactionEventID, ['10', '7'])) {
            switch ($transactionEventID) {
                case '10':
                    $updateData   = ['projectstatus' => '13', 'status' => '5', 'balancefund' => 0];
                    $shouldUpdate = true;
                    break;
                case '7':
                    $updateData   = ['projectstatus' => '6'];
                    $shouldUpdate = true;
                    break;
            }
        }

        if ($shouldUpdate && !empty($updateData)) {
            Project::where('id', $projectId)->update($updateData);
        }

        if ($transactionEventID == '16' && $roleId) {
            if ($roleId == 2 && $contractorEmail) {
                ProjectStatusJob::dispatch($contractorEmail, $projectId, 'PaidByCustomer', null, null);
            }
            if ($roleId == 3 && $customerEmail) {
                ProjectStatusJob::dispatch($customerEmail, $projectId, 'PaidByContractor', null, null);
            }
        }
        if ($transactionEventID == '6' && $roleId) {
            if ($roleId == 3 && $customerEmail) {
                ProjectStatusJob::dispatch($customerEmail, $projectId, 'AcceptByContractor', null, null);
            }
        }

        if ($transactionEventID == '10') {
            if ($customerEmail) {
                ProjectStatusJob::dispatch($customerEmail, $projectId, 'EscrowFullSettled', null, null);
            }
            if ($contractorEmail) {
                ProjectStatusJob::dispatch($contractorEmail, $projectId, 'EscrowFullSettled', null, null);
            }
        }

        if ($transactionEventID == '12') {
            if ($customerEmail) {
                ProjectStatusJob::dispatch($customerEmail, $projectId, 'EscrowPartialSettled', null, null);
            }
            if ($contractorEmail) {
                ProjectStatusJob::dispatch($contractorEmail, $projectId, 'EscrowFullSettled', null, null);
            }
        }

        if ($transactionEventID == '7' && $contractorEmail) {
            ProjectStatusJob::dispatch($contractorEmail, $projectId, 'VoidTransaction', null, null);
        }
        $messageData = [
            'customerName'   => $customerName,
            'contractorName' => $contractorName,
            'projectName'    => $project->projectname,
            'amount'         => $amount,
        ];
        $customMessage   = $this->getCustomMessageForStatus($transactionEventID, $roleName, $messageData);

        if ($roleId == 2) {
            $this->updateNotification($projectId, $customMessage, 'customer');
        }
        if ($roleId == 3) {
            $this->updateNotification($projectId, $customMessage, 'contractor');
        }

        $channel = 'invitechannel';
        $channelData = [
            'message'               => $customMessage,
            'projectId'             => $projectId,
            'transpactCustomerId'   => $project->customer_id ?? null,
            'transpactContractorId' => $project->contractor_id ?? null,
        ];
        // if (isset($channelData)) {
        //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'invitechannel'));
        // }
        $this->sendCpsNotification($channel, [
            'message'               => $customMessage,
            'projectId'             => $projectId,
            'transpactCustomerId'   => $project->customer_id ?? null,
            'transpactContractorId' => $project->contractor_id ?? null,
        ], 0, 'broadcast');
    }
    protected function getCustomMessageForStatus($transactionEventID, $roleName, $messageData)
    {
        $customerName   = $messageData['customerName'] ?? 'Customer';
        $contractorName = $messageData['contractorName'] ?? 'Contractor';
        $projectName    = $messageData['projectName'] ?? 'the project';
        $amount         = $messageData['amount'] ?? '0.00';

        $messages = [
            16 => $roleName === 'Customer'
                ? "$customerName has funded £$amount to escrow for $projectName."
                : "$contractorName has funded £$amount to escrow for $projectName.",

            10 => "Escrow fully settled for $projectName. All funds have been released.",

            12 => "Escrow partially settled for $projectName. Some funds have been released.",

            7  => "Transaction for $projectName has been voided by $customerName.",
            6  => "Escrow transaction is now live for $projectName. Work can commence.",
        ];

        return $messages[$transactionEventID] ?? "Project status updated for $projectName.";
    }
    /**
     * Payment Request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function requestPayment(Request $request)
    {
        try {
            $requestType =  $request->request_type;
            $userId      = $request->attributes->get('decoded_token')->get('id');
            $contractor  = User::where('id', $userId)->select('name')->first();

            $projectId = $this->decryptProjectId($request->project_id);
            $project   = Project::where('id', $projectId)
                ->select('customer_email', 'escrowfund', 'projectname', 'contractor_acceptance')->first();
            if ($project->contractor_acceptance == 0) {
                $acceptanceTerms   = config('custom.acceptterms');
                $ContractorAcceptTerm = $acceptanceTerms[0];
                return $this->returnError($ContractorAcceptTerm, 404);
            }
            $transpact = Transpact::where('project_id', $projectId)->select('transpactnumber')->first();

            $baseUrl            = config('services.transpact_login_url');
            $appName            = config('app.name');
            $transpactLoginUrl  = "{$baseUrl}?Em={$project->customer_email}&co={$appName}";

            if ($requestType == 0) {
                $requestAmount =  '£' . number_format($project->escrowfund, 2);
            } elseif ($requestType == 1) {
                $requestAmount =  '£' . number_format($request->request_amount, 2);
            } else {
                $requestAmount = '£0.00';
            }

            $paymentType = [
                'requestAmount'     => $requestAmount,
                'transpactNumber'   => $transpact->transpactnumber,
                'transpactLoginUrl' => $transpactLoginUrl,
            ];

            if ($requestType == 0) {
                ProjectStatusJob::dispatch($project->customer_email, $projectId, 'FullRequest', null, $paymentType);
                $customMessage = "Contractor {$contractor->name} Requested you to release the full fund from the escrow of Transpact for the'{$project->projectname}' project";
            }
            if ($requestType == 1) {
                ProjectStatusJob::dispatch($project->customer_email, $projectId, 'PartialRequest', null, $paymentType);
                $customMessage = "Contractor {$contractor->name} Requested you to release the partial fund {$requestAmount} from the escrow of Transpact for the'{$project->projectname}'project";
            }

            $this->updateNotification($projectId, $customMessage, 'contractor');

            return $this->returnSuccess(null, 'A payment request email has been sent to the customer');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Get base64 encoded signature images for contractor and customer.
     *
     * @param  \App\Models\Project  $project
     * @return array
     */
    public function getProjectSignatureData($project)
    {
        $contractorSignPath = $project->contractor_sign ?? null;
        $customerSignPath   = $project->customer_sign ?? null;

        $contractorSignData = null;
        $customerSignData   = null;

        if ($contractorSignPath && file_exists(public_path($contractorSignPath))) {
            $contractorSignData = 'data:image/' . pathinfo($contractorSignPath, PATHINFO_EXTENSION) . ';base64,' .
                base64_encode(file_get_contents(public_path($contractorSignPath)));
        }

        if ($customerSignPath && file_exists(public_path($customerSignPath))) {
            $customerSignData = 'data:image/' . pathinfo($customerSignPath, PATHINFO_EXTENSION) . ';base64,' .
                base64_encode(file_get_contents(public_path($customerSignPath)));
        }

        return [
            'contractor_signature' => $contractorSignData,
            'customer_signature'   => $customerSignData,
        ];
    }
    private function uploadFiles(string $filename, string $path, string $pdfContent): string
    {
        $fullPath = public_path($path . $filename);

        if (!File::exists(public_path($path))) {
            File::makeDirectory(public_path($path), 0775, true);
        }

        file_put_contents($fullPath, $pdfContent);
        return $path . $filename;
    }
    public function generatePdfFromProjectData(array $projectData): string
    {
        $html = View::make('email.newagreement', ['projectData' => $projectData])->render();

        try {
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($html);
            return $mpdf->Output('', 'S');
        } catch (\Mpdf\MpdfException $e) {
            throw $e;
        }
    }
    private function getLogoData()
    {
        $loadImage = function ($path) {
            $fullPath = public_path($path);
            if (file_exists($fullPath)) {
                $mimeType = mime_content_type($fullPath);
                $imageData = base64_encode(file_get_contents($fullPath));
                return "data:$mimeType;base64,$imageData";
            }
            return null;
        };

        return [
            'cpsMainLogo'    => $loadImage('assets/img/logo-dark.png'),
            'cpsLogo'        => $loadImage('assets/img/logo-dark.png'),
            'transpactLogo'  => $loadImage('assets/img/transpact-logo.png'),
        ];
    }
    private function getAgreementFilename($projectinfo)
    {
        $formattedDate = $projectinfo->created_at->format('Ymd_His');
        return "project_agreement_{$formattedDate}.pdf";
    }
    public function viewProjectNew(Request $request)
    {
        try {
            $roleId      = $request->attributes->get('decoded_token')->get('roleId');
            $projectId   = $request->project_id;
            $decryptedId = $this->decryptProjectId($projectId);

            $projectData = Project::where('id', $decryptedId)->first();
            if (!$projectData) {
                return $this->returnError('Project not found', 400);
            }
            $project = $this->projectQueryService->getProjectDetailsByRole($roleId, $decryptedId);

            $customerStatus   = config('custom.customer');
            $contractorStatus = config('custom.contractor');
            $customerText     = config('custom.customertext');
            $contractorText   = config('custom.contractortext');
            $status           = $project->status;

            $taskInfo = $this->getTaskStatusInfo($decryptedId);

            switch ($status) {
                case 3:
                    $projectStatus = ($roleId == 2)
                        ? $taskInfo['customer_status']
                        : 'Task Complete, Waiting For Customer Verification';
                    $contentText = ($roleId == 2)
                        ? $taskInfo['customer_text']
                        : $taskInfo['contractor_text'];
                    break;

                case 5:
                    $projectStatus = $customerStatus[$status] ?? 'Unknown';
                    $contentText = '';
                    break;

                default:
                    $statusList  = ($roleId == 2) ? $customerStatus : $contractorStatus;
                    $textList    = ($roleId == 2) ? $customerText : $contractorText;

                    $projectStatus = $statusList[$status] ?? 'Unknown';
                    $contentText   = $textList[$status] ?? '';
                    break;
            }
            $project['projectAmount']  = '£' . number_format($project->projectamount, 2);
            $project['escrowFunds']    = '£' . number_format($project->escrowfund, 2);
            $project['balanceFunds']   = '£' . number_format($project->balancefund, 2);
            $project['projectStatus']  = $projectStatus;
            $project['contentText']    = $contentText;
            if ($project->projectstatus == '17' && $project->contractor_acceptance == 0) {
                $acceptanceTerms      = config('custom.acceptterms');
                $ContractorAcceptTerm = $acceptanceTerms[0];
                $waitingForContractor = $acceptanceTerms[1];
                $acceptTermText  = ($roleId == 2) ? $waitingForContractor : $ContractorAcceptTerm;
                $project['acceptTermText']    = $acceptTermText;
            }

            $baseUrl           = config('services.transpact_login_url');
            $appName           = config('app.name');
            $transpactLoginUrl = "{$baseUrl}?Em={$projectData->customer_email}&co={$appName}";
            $project['transpactLoginUrl'] = $transpactLoginUrl;
            if ($roleId == ModelHasRoles::CONTRACTOR) {
                $contractorEmail        = $request->attributes->get('decoded_token')->get('email');
                $contractorTranspactUrl = "{$baseUrl}?Em={$contractorEmail}&co={$appName}";
                $project['contractorTranspactUrl'] = $contractorTranspactUrl;
            }
            $contracts                  = ProjectContract::where('project_id', $decryptedId)->pluck('contract')->toArray();
            $project['projectContract'] = empty($contracts) ? [] : $contracts;
            $projectTasks               = ProjectTasks::where('project_id', $projectId)->get();
            $taskTypes                  = config('custom.taskstatus');

            $projectTasks->transform(function ($task) use ($taskTypes, $decryptedId) {
                $encryptedProjectId = Crypt::encrypt($decryptedId);

                $task->taskStatusLabel    = $taskTypes[$task->taskstatus] ?? 'Pending';
                $task->formattedTaskFee   = '£' . number_format($task->taskamount, 2);
                $task->encryptedProjectId = $encryptedProjectId;

                return $task;
            });
            $project['projectTask'] = $projectTasks->isEmpty() ? [] : $projectTasks;

            return $this->returnSuccess($project, 'Project Details');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    public function getProjectListNew(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'   => 'sometimes|integer|min:1',
                'length' => 'sometimes|integer|min:1|max:500',
            ]);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }

            $page          = $request->input('page') ?: '1';
            $perPage       = $request->input('length') ?: env("TABLE_LIST_LENGTH");
            $userId        = $request->attributes->get('decoded_token')->get('id');
            $roleId        = $request->attributes->get('decoded_token')->get('roleId');
            $projectFilter = $request->project_filter;

            $dbdata = $this->projectQueryService->getProjectsByRole($roleId, $userId, $projectFilter);
            $projectStatusList = config('custom.projectstatus');

            if ($request->filled('search')) {
                $search = $request->input('search');
                $statusMatches = [];

                foreach ($projectStatusList as $key => $label) {
                    if (stripos($label, $search) !== false) {
                        $statusMatches[] = (string) $key;
                    }
                }
                $columns = $this->projectQueryService->getProjectColumnsByRole($roleId);

                $dbdata->where(function ($query) use ($search, $columns, $statusMatches) {
                    foreach ($columns as $key => $column) {
                        if ($key == 0) {
                            $query->where($column, 'like', '%' . $search . '%');
                        } else {
                            $query->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }

                    if (!empty($statusMatches)) {
                        $query->orWhereIn('projects.projectstatus', $statusMatches);
                    }
                });
            }

            $order          = $request->input('order_column') ?: 'projects.created_at';
            $orderDirection = $request->input('order_dir') ?: 'desc';

            if ($projectFilter !== null && $projectFilter !== '') {
                if ((string)$projectFilter !== '7') {
                    $dbdata->where('projects.projectstatus', (string)$projectFilter);
                }
            }
            $dbdata = $dbdata->orderBy($order, $orderDirection)
                ->paginate($perPage, ['*'], 'page', $page);

            $data = $this->projectQueryService->formatProjectList($dbdata, $roleId);

            return $this->returnSuccess([
                'list'         => $data,
                'currentPage'  => $dbdata->currentPage(),
                'totalPages'   => $dbdata->lastPage(),
                'recordsTotal' => $dbdata->total(),
            ], 'Project list.');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    private function getTaskStatusInfo($decryptedId)
    {
        $task = ProjectTasks::where('project_id', $decryptedId)
            ->where('taskstatus', ProjectTasks::COMPLETED)
            ->where('is_verified', ProjectTasks::PENDING)
            ->first();

        $taskIdentifier = $task
            ? (!empty($task->taskname) ? $task->taskname : "task {$task->id}")
            : 'task';

        return [
            'customer_text' => "Contractor has marked {$taskIdentifier} task as completed, please verify and provide acceptance by ticking the customer acceptance button for each relevant task.",
            'contractor_text' => "You have marked {$taskIdentifier} task as complete, we are now waiting for the customer to verify the task has been completed and you will then be able to request either a full or partial payment.",
            'customer_status' => "{$taskIdentifier} task Acceptance Required"
        ];
    }
}
