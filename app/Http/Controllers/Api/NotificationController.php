<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;
use Exception;
use App\Traits\sendCpsNotification;
use App\Models\Notifications;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use App\Api\Frontend\Modules\Project\Models\Project;

class NotificationController extends BaseController
{
    use sendCpsNotification;
    /**
     * Invitation Notify Message Endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function invitationMail(Request $request)
    {
        try {
            $channel  = 'invitechannel';
            $response = $this->sendCpsNotification($channel, $request->message, $request->state);
            return $this->sendResponse($response, 'Invitation sent successfully!');
        } catch (Exception $e) {
            return $this->sendError(
                'An error occurred while sending the notification.',
                ['error' => $e->getMessage(), 'line' => $e->getLine()],
                500
            );
        }
    }
    /**
     * Invitation Notify Message Endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function notificationList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'   => 'sometimes|integer|min:1',
                'length' => 'sometimes|integer|min:1|max:500',
                'order'  => 'sometimes|in:asc,desc',
            ]);

            if ($validator->fails()) {
                return [
                    'status'     => false,
                    'message'    => 'Validation Error.',
                    'errors'     => $validator->errors(),
                    'statusCode' => 400
                ];
            }

            $page    = $request->input('page', 1);
            $perPage = $request->input('length', env("TABLE_LIST_LENGTH", 10));
            $order   = $request->input('order', 'desc');
            $userId  = $request->attributes->get('decoded_token')->get('id');
            $roleId  = $request->attributes->get('decoded_token')->get('roleId');
            // $user    = User::where('id', $userId)->select('name', 'profileimage')->first();

            if ($roleId == 2) {
                $projectIds = Project::where('customer_id', $userId)->pluck('id');
            } elseif ($roleId == 3) {
                $projectIds = Project::where('contractor_id', $userId)->pluck('id');
            } else {
                return $this->sendResponse([], 'No notifications for this role');
            }

            if ($projectIds->isEmpty()) {
                return $this->sendResponse([
                    'notifications' => [],
                    'total'         => 0,
                    'currentPage'   => $page,
                    'lastPage'      => 1,
                ], 'Notification List');
            }

            $notificationQuery = Notifications::where('to_id', $userId)
                ->whereIn('project_id', $projectIds)
                ->select('message', 'project_id', 'isread', 'created_at', 'id', 'from_id')
                ->orderBy('created_at', $order);

            $notifications = $notificationQuery->paginate($perPage, ['*'], 'page', $page);
            $projectMap    = Project::whereIn('id', $notifications->pluck('project_id'))
                ->pluck('projectname', 'id');
            $fromIds = $notifications->pluck('from_id')->unique();

            $users = User::whereIn('id', $fromIds)
                ->select('id', 'name', 'profileimage')
                ->get()
                ->keyBy('id');
            $notifications->getCollection()->transform(function ($notification) use ($projectMap, $users) {
                $sender = $users[$notification->from_id] ?? null;

                $notification->projectName  = $projectMap[$notification->project_id] ?? null;
                $notification->userName     = $sender->name ?? 'Unknown';
                $notification->profileImage = $sender->profileimage ?? null;
                $notification->encrypetdId  = Crypt::encrypt($notification->project_id) ?? null;
                $notification->createdAt    = \Carbon\Carbon::parse($notification->created_at)->format('d M Y, h:i A');
                

                return $notification;
            });

            return $this->sendResponse([
                'notifications' => $notifications->items(),
                'total'         => $notifications->total(),
                'currentPage'   => $notifications->currentPage(),
                'lastPage'      => $notifications->lastPage(),
            ], 'Notification List');

            return $this->sendResponse($responseData, 'Notification List');
        } catch (Exception $e) {
            return $this->sendError(
                'An error occurred while sending the notification.',
                ['error' => $e->getMessage(), 'line' => $e->getLine()],
                500
            );
        }
    }
    /**
     * Notification Count Endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function notificationCount(Request $request)
    {
        try {
            $userId = $request->attributes->get('decoded_token')->get('id');
            $roleId = $request->attributes->get('decoded_token')->get('roleId');

            if ($roleId == 2) {
                $projectIds = Project::where('customer_id', $userId)->pluck('id');
                $notificationCount = Notifications::where('isread', 0)
                    ->where('to_id', $userId)
                    ->whereIn('project_id', $projectIds)
                    ->count();
            } elseif ($roleId == 3) {
                $projectIds = Project::where('contractor_id', $userId)->pluck('id');
                $notificationCount = Notifications::where('isread', 0)
                    ->where('to_id', $userId)
                    ->whereIn('project_id', $projectIds)
                    ->count();
            } else {
                return $this->sendResponse([], 'No notifications for this role');
            }

            return $this->sendResponse($notificationCount, 'Notification Count');
        } catch (Exception $e) {
            return $this->sendError(
                'An error occurred while sending the notification.',
                ['error' => $e->getMessage(), 'line' => $e->getLine()],
                500
            );
        }
    }
    /**
     * Notification Count Endpoint
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function readMessage(Request $request)
    {
        try {
            $notificationId = $request->notification_id;
            $isRead         = $request->isread;

            Notifications::where('id', $notificationId)->update([
                'isread'     => $isRead,
            ]);
            return $this->sendResponse(null, 'Message read');
        } catch (Exception $e) {
            return $this->sendError(
                'An error occurred while sending the notification.',
                ['error' => $e->getMessage(), 'line' => $e->getLine()],
                500
            );
        }
    }
    /**
     * Remove Notification .
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function notificationDelete(Request $request)
    {
        try {
            $notificationId = $request->notification_id;
            $notification   = Notifications::find($notificationId);

            if (!$notification) {
                return [
                    'status'     => false,
                    'message'    => 'Notification not found.',
                    'data'       => null,
                    'statusCode' => 404,
                ];
            }
            $notification->delete();

            return [
                'status'     => true,
                'message'    => 'Notification deleted successfully.',
                'data'       => null,
                'statusCode' => 200,
            ];
        } catch (\Exception $e) {
            return [
                'status'     => false,
                'message'    => 'An error occurred.',
                'errors'     => ['error' => $e->getMessage()],
                'statusCode' => 500,
            ];
        }
    }
}
