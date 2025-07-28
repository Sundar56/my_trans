<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\ModelHasRoles;
use Illuminate\Support\Facades\Hash;
use App\Services\UploadFileService;
use App\Api\Frontend\Modules\Dispute\Models\Dispute;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Api\Frontend\Modules\Dispute\Models\DisputeChat;
use App\Api\Frontend\Modules\Dispute\Models\ChatAttachment;
use App\Traits\sendCpsNotification;
use App\Traits\projectData;
use App\Services\ProjectService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProjectStatusJob;
use Illuminate\Contracts\Encryption\DecryptException;


class DisputeService
{
    use sendCpsNotification, projectData;

    protected $uploadFileService;
    protected $projectService;

    public function __construct(UploadFileService $uploadFileService, ProjectService $projectService)
    {
        $this->uploadFileService = $uploadFileService;
        $this->projectService    = $projectService;
    }
    /**
     * Create New Tasks.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDispute(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'required',
            ], [
                'reason.required' => 'Reason is required',
            ]);
            if ($validator->fails()) {
                return $this->handleValidationFailure($validator);
            }
            $userId = $request->attributes->get('decoded_token')->get('id');
            $roleId = $request->attributes->get('decoded_token')->get('roleId');
            $email  = $request->attributes->get('decoded_token')->get('email');
            $user   = User::where('id', $userId)->select('name')->first();

            $projectId = $this->decryptProjectId($request->project_id);
            $project   = Project::where('id', $projectId)->select('customer_id', 'contractor_id', 'projectname')->first();
            $admin     = 1;
            if ($roleId == 2) {
                $dispute = Dispute::create([
                    'project_id' => $projectId,
                    'created_by' => $userId,
                    'reason'     => $request->reason,
                    'sent_to'    => $admin,
                ]);
                $channel    = 'disputechannel';
                $channelData = [
                    'message'     => "Dispute created by {$user->name} for the {$project->projectname} project",
                    'disputefrom' => $userId,
                    'disputeto'   => $admin,
                ];
                // if (isset($channelData)) {
                //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'disputechannel'));
                // }
                $this->sendCpsNotification($channel, [
                    'message'     => "Dispute created by {$user->name} for the {$project->projectname} project",
                    'disputefrom' => $userId,
                    'disputeto'   => $admin,
                ], 0, 'broadcast');

                ProjectStatusJob::dispatch($email, $projectId, 'DisputeRaisedbyCustomer', null, null);
            }

            $this->uploadFileService->uploadDisputeFiles($request, $userId, $roleId, $dispute->id);

            $customMessage = "Dispute created by {$user->name} for the {$project->projectname} Project";
            $this->projectService->updateNotification($projectId, $customMessage, $dispute->id);

            return $this->returnSuccess(null, 'Dispute Created successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * View Dispute Details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function viewDispute(Request $request)
    {
        try {
            $userId    = $request->attributes->get('decoded_token')->get('id');
            $projectId = $request->project_id;
            $page      = $request->get('page', 1);
            $limit     = $request->get('limit', 50);
            // $projectId = Crypt::decrypt($request->project_id);

            DisputeChat::where('project_id', $projectId)->where('to_id', $userId)->update([
                'seen' => $request->is_read
            ]);

            $dispute   = Dispute::leftJoin('projects', 'dispute.project_id', '=', 'projects.id')
                ->leftJoin('users', 'dispute.created_by', '=', 'users.id')
                ->where('dispute.project_id', $projectId)
                ->select(
                    'dispute.*',
                    'projects.projectname as projectname',
                    'projects.projectchannel as projectchannel',
                    'users.name as username',
                )
                ->first();

            $chatQuery = DisputeChat::leftJoin('users as from_user', 'disputechat.from_id', '=', 'from_user.id')
                ->leftJoin('users as to_user', 'disputechat.to_id', '=', 'to_user.id')
                ->where('disputechat.project_id', $projectId)
                ->select(
                    'disputechat.*',
                    'from_user.name as from_user_name',
                    'from_user.profileimage as from_user_profile',
                    'to_user.name as to_user_name',
                    'to_user.profileimage as to_user_profile'
                )
                ->orderBy('disputechat.created_at', 'desc');

            $paginator    = $chatQuery->paginate($limit, ['*'], 'page', $page);
            $disputeChats = $paginator->items();
            foreach ($disputeChats as $chat) {
                $chat->attachments = ChatAttachment::where('disputechat_id', $chat->id)->get();
            }

            if ($dispute) {
                $dispute->chats = $disputeChats;
                $dispute->pageTotal = $paginator->total();
            }

            return $this->returnSuccess($dispute, 'Dispute Details');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Get Dispute list Details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDisputeList(Request $request)
    {
        try {
            $userId      = $request->attributes->get('decoded_token')->get('id');
            $roleId      = $request->attributes->get('decoded_token')->get('roleId');
            $disputeType = $request->disputetype ?? "0";

            if ($roleId == 1) {
                $disputes = Dispute::leftJoin('projects', 'dispute.project_id', '=', 'projects.id')
                    ->leftJoin('users', 'dispute.created_by', '=', 'users.id')
                    ->leftJoin('disputechat', function ($join) {
                        $join->on('dispute.project_id', '=', 'disputechat.project_id');
                    })
                    ->where('dispute.disputestatus', $disputeType)
                    ->select(
                        'dispute.*',
                        'projects.projectname as projectname',
                        'users.name as username',
                        DB::raw("COUNT(CASE WHEN disputechat.seen = 0 THEN 1 END) as chat_count")
                    )
                    ->groupBy(
                        'dispute.id',
                        'projects.projectname',
                        'users.name'
                    )
                    ->get();
            } else {
                $disputes = Dispute::leftJoin('projects', 'dispute.project_id', '=', 'projects.id')
                    ->leftJoin('users', 'dispute.created_by', '=', 'users.id')
                    ->leftJoin('disputechat', function ($join) use ($userId) {
                        $join->on('dispute.project_id', '=', 'disputechat.project_id')
                            ->where('disputechat.to_id', '=', $userId);
                    })
                    ->where('dispute.disputestatus', $disputeType)
                    ->where(function ($query) use ($userId) {
                        $query->where('dispute.created_by', $userId)
                            ->orWhere('dispute.sent_to', $userId);
                    })
                    ->select(
                        'dispute.*',
                        'projects.projectname as projectname',
                        'users.name as username',
                        DB::raw("COUNT(CASE WHEN disputechat.seen = 0 THEN 1 END) as chat_count")
                    )
                    ->groupBy(
                        'dispute.id',
                        'projects.projectname',
                        'users.name',
                        'projects.id',
                        'users.id'
                    )
                    ->get();
            }

            return $this->returnSuccess($disputes, 'Dispute List');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    /**
     * Get Dispute chat.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disputeChat(Request $request)
    {
        try {
            $userId    = $request->attributes->get('decoded_token')->get('id');
            $roleId    = $request->attributes->get('decoded_token')->get('roleId');
            $disputeId = $request->dispute_id;
            $message   = $request->message;
            $dispute   = Dispute::find($disputeId);
            if (!$dispute) {

                return $this->returnError('Dispute not found', 400);
            }
            $project   = Project::find($dispute->project_id);

            if ($userId == $dispute->created_by) {
                $toId  = $dispute->sent_to;
            } else {
                $toId  = $dispute->created_by;
            }

            $disputeChatId =  DisputeChat::create([
                'from_id'          => $userId,
                'to_id'            => $toId,
                'body'             => $message ?? null,
                'project_id'       => $project->id,
                'dispute_id'       => $disputeId ?? null,
                'replied_id'       => null,
                'is_edited'        => 0,
                'is_replied'       => 0,
                'is_forwarded'     => 0,
                'is_saved'         => 0,
                'no_of_attachment' => 0,
            ]);

            $response =  $this->uploadFileService->uploadChatAttachment($request, $userId, $roleId, $disputeChatId->id);
            if (isset($response['status']) && $response['status'] === false) {
                return $this->returnError($response['message'], 404);
            }

            $attachments     = ChatAttachment::where('disputechat_id', $disputeChatId->id)->select('filename')->get();
            $profileImg      = User::where('id', $userId)->select('profileimage')->first();
            $chatAttachments = $attachments->pluck('filename')->toArray();

            $notificationMessage = [
                'from_id'         => $userId,
                'to_id'           => $toId,
                'message'         => $message,
                'project'         => $project->projectname,
                'project_id'      => $project->id,
                'dispute_id'      => (int)$disputeId,
                'chatAttachments' => $chatAttachments,
                'fromUserProfile' => $profileImg->profileimage,
            ];
            // if (isset($notificationMessage)) {
            //     broadcast(new \App\Events\InviteChannelBroadcast($notificationMessage, 'disputechatchannel'));
            // }

            $projectChannel = 'disputechatchannel';
            $this->sendCpsNotification($projectChannel, $notificationMessage, 0, 'message');

            $messageCount = $this->messageCount($disputeId, $project->id, $toId);

            return $this->returnSuccess(null, 'Dispute chat sent successfully');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
    public function messageCount($disputeId, $projectId, $toId)
    {
        $count   = DisputeChat::where('dispute_id', $disputeId)->where('seen', 0)->where('to_id', $toId)->count();
        $channel = 'chatmessagechannel';
        $channelData = [
            'message'      => "chat message count",
            'disputeId'    => (int)$disputeId,
            'count'        => $count,
            'projectId'    => $projectId,
            'receiverId'   => $toId,
        ];
        // if (isset($channelData)) {
        //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'chatmessagechannel'));
        // }
        $this->sendCpsNotification($channel, [
            'message'      => "chat message count",
            'disputeId'    => (int)$disputeId,
            'count'        => $count,
            'projectId'    => $projectId,
            'receiverId'   => $toId,
        ], 0, 'broadcast');
    }
    /**
     * Dispute resolve by admin.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disputeResolve(Request $request)
    {
        try {
            $roleId      = $request->attributes->get('decoded_token')->get('roleId');
            $disputeId   = $request->dispute_id;
            $disputeType = $request->disputetype;
            $dispute     = Dispute::where('id', $disputeId)->select('project_id', 'created_by')->first();
            $projectId   = $dispute->project_id;

            if ($roleId == 1) {
                Dispute::where('id', $disputeId)->update([
                    'disputestatus' => $disputeType
                ]);
            }

            $project       = Project::where('id', $projectId)->select('projectname', 'customer_email')->first();
            $channel       = 'disputechannel';
            $customMessage = "The dispute for the project '{$project->projectname} has been resolved by the Admin";
            $channelData = [
                'message'    => $customMessage,
                'disputeId'  => $disputeId,
                'resolveTo'  => $dispute->created_by,
                'projectId'  => $projectId,
                'updatedBy'  => 'Admin',
            ];
            // if (isset($channelData)) {
            //     broadcast(new \App\Events\InviteChannelBroadcast($channelData, 'disputechannel'));
            // }

            $this->sendCpsNotification($channel, [
                'message'    => $customMessage,
                'disputeId'  => $disputeId,
                'resolveTo'  => $dispute->created_by,
                'projectId'  => $projectId,
                'updatedBy'  => 'Admin',
            ], 0, 'broadcast');

            $this->projectService->updateNotification($projectId, $customMessage, $disputeId);
            ProjectStatusJob::dispatch($project->customer_email, $projectId, 'DisputeResolved', null, null);

            return $this->returnSuccess(null, 'Dispute Resolved By Admin');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
