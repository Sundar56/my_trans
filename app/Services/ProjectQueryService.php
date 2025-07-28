<?php

namespace App\Services;

use App\Api\Frontend\Modules\Project\Models\ProjectContract;
use App\Api\Frontend\Modules\Project\Models\Project;
use App\Models\ModelHasRoles;
use Illuminate\Support\Facades\Crypt;

class ProjectQueryService
{
    public function getProjectsByRole($roleId, $userId, $projectFilter = null)
    {
        switch ($roleId) {
            case ModelHasRoles::ADMIN:
                return $this->getAdminProjects();

            case ModelHasRoles::CUSTOMER:
                return $this->getCustomerProjects($userId);

            case ModelHasRoles::CONTRACTOR:
                return $this->getContractorProjects($userId, $projectFilter);

            default:
                return [
                    'status'     => false,
                    'message'    => 'Unauthorized access.',
                    'statusCode' => 403
                ];
        }
    }
    protected function getAdminProjects()
    {
        return Project::leftJoin('users as customers', 'customers.id', '=', 'projects.customer_id')
            ->leftJoin('users as contractors', 'contractors.id', '=', 'projects.contractor_id')
            ->where('projects.is_create', 0)
            ->select(
                'projects.id',
                'projects.projectname',
                'projects.projectstatus',
                'projects.projectamount',
                'projects.created_at',
                'projects.customer_email',
                'projects.customer_name',
                'projects.status',
                'customers.name as customerName',
                'customers.profileimage as customerImage',
                'contractors.name as contractorName',
                'contractors.profileimage as contractorImage'
            );
    }

    protected function getCustomerProjects($userId)
    {
        return Project::leftJoin('users as contractors', 'contractors.id', '=', 'contractor_id')
            ->where('projects.is_create', 0)
            ->where('customer_id', $userId)
            ->select(
                'contractors.name as contractorName',
                'contractors.profileimage as contractorImage',
                'projectname',
                'projectstatus',
                'projectamount',
                'projects.created_at',
                'projects.id',
                'projects.status'
            );
    }

    protected function getContractorProjects($userId, $projectFilter)
    {
        $query = Project::leftJoin('users as customers', 'customers.id', '=', 'customer_id')
            ->where('contractor_id', $userId)
            ->select(
                'customers.name as customerName',
                'customers.profileimage as customerImage',
                'projectname',
                'projectstatus',
                'projectamount',
                'projects.created_at',
                'projects.id',
                'projects.customer_email',
                'projects.customer_name',
                'projects.status'
            );

        if ($projectFilter == 16) {
            $query->where('projects.is_create', 1)->where('projects.projectstatus', 16);
        } elseif ($projectFilter != 7) {
            $query->where('projects.is_create', 0);
        }

        return $query;
    }
    public function getProjectColumnsByRole(int $roleId): array
    {
        switch ($roleId) {
            case ModelHasRoles::CUSTOMER:
                return ['contractors.name', 'projectname', 'projects.created_at', 'projectamount'];

            case ModelHasRoles::CONTRACTOR:
                return ['customers.name', 'projectname', 'projects.created_at', 'projectamount'];

            case ModelHasRoles::ADMIN:
                return ['customers.name', 'projectname', 'projects.created_at', 'projectamount', 'contractors.name'];

            default:
                return ['projectname', 'projects.created_at', 'projectamount'];
        }
    }
    public function formatProjectList($projectCollection, int $roleId)
    {
        $customerStatus   = config('custom.customer');
        $contractorStatus = config('custom.contractor');

        return $projectCollection->map(function ($item) use ($roleId, $customerStatus, $contractorStatus) {
            $encryptedProjectId = Crypt::encrypt($item->id);
            $projectTotalCost   = '£' . number_format($item->projectamount, 2);

            switch ($item->status) {
                case 3:
                    $projectStatus = ($roleId == ModelHasRoles::CUSTOMER)
                        ? 'Task 1 Acceptance Required'
                        : 'Task Complete, Waiting For Customer Verification';
                    break;

                case 5:
                    $projectStatus = $customerStatus[$item->status] ?? 'Unknown';
                    break;

                default:
                    $statusList = ($roleId == ModelHasRoles::CUSTOMER) ? $customerStatus : $contractorStatus;
                    $projectStatus = $statusList[$item->status] ?? 'Unknown';
                    break;
            }

            $baseData = [
                'projectname'   => $item->projectname,
                'projectstatus' => $projectStatus,
                'projectId'     => $item->id,
                'encryptedId'   => $encryptedProjectId,
                'totalCost'     => $projectTotalCost,
                'newStatus'     => $item->status,
            ];

            if ($roleId == ModelHasRoles::CUSTOMER) {
                $baseData['contractorName']  = $item->contractorName ?? '';
                $baseData['contractorImage'] = $item->contractorImage ?? '';
            } elseif ($roleId == ModelHasRoles::CONTRACTOR) {
                $baseData['customerName']  = $item->customerName ?? $item->customer_name;
                $baseData['customerImage'] = $item->customerImage ?? '';
            } elseif ($roleId == ModelHasRoles::ADMIN) {
                $baseData['customerName']    = $item->customerName ?? $item->customer_name;
                $baseData['customerImage']   = $item->customerImage ?? '';
                $baseData['contractorName']  = $item->contractorName ?? '';
                $baseData['contractorImage'] = $item->contractorImage ?? '';
            }

            return $baseData;
        });
    }
    public function getProjectDetailsByRole(int $roleId, int $decryptedId)
    {
        $projectData = Project::find($decryptedId);

        if (!$projectData) {
            return null;
        }

        if ($projectData->customer_id === null) {
            $project = Project::leftJoin('invitation', 'projects.id', '=', 'invitation.project_id')
                ->where('projects.id', $decryptedId)
                ->select(
                    'projects.*',
                    'invitation.invitemail as invitemail'
                )
                ->first();

            $project['depositedFunds']  = "£0";
            $project['remainingFunds']  = "£0";
            $project['projectStatus']   = 'Waiting for Customer to Sign & Accept Project';

            $contracts = ProjectContract::where('project_id', $decryptedId)->pluck('contract')->toArray();
            $project['projectContract'] = empty($contracts) ? [] : $contracts;

            return $project;
        }

        switch ($roleId) {
            case ModelHasRoles::CUSTOMER:
                $project = Project::leftJoin('users', 'projects.contractor_id', '=', 'users.id')
                    ->leftJoin('businessprofile', 'projects.contractor_id', '=', 'businessprofile.user_id')
                    ->where('projects.id', $decryptedId)
                    ->select(
                        'projects.*',
                        'users.email as contractorEmail',
                        'users.name as contractorName',
                        'businessprofile.businessname as businessname',
                        'businessprofile.company_registernum as company_registernum',
                        'businessprofile.businessphone as businessphone',
                        'businessprofile.businessimage as businessimage',
                        'businessprofile.businesstype as businesstype',
                        'businessprofile.address as contractorAddress',
                        'users.profileimage as profileimage'
                    )
                    ->first();

                $businessTypes = config('custom.businesstype');
                $project['typeOfBusiness'] = $businessTypes[$project->businesstype] ?? null;
                break;

            case ModelHasRoles::CONTRACTOR:
            case ModelHasRoles::ADMIN:
                $project = Project::leftJoin('users', 'projects.customer_id', '=', 'users.id')
                    ->where('projects.id', $decryptedId)
                    ->select(
                        'projects.*',
                        'users.email as email',
                        'users.name as username',
                        'users.phonenumber as phonenumber',
                        'users.address as address',
                        'users.profileimage as profileimage'
                    )
                    ->first();
                break;

            default:
                $project = Project::find($decryptedId);
                break;
        }

        return $project;
    }
}
