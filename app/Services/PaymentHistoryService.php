<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Traits\projectData;
use Illuminate\Support\Facades\Validator;
use App\Api\Frontend\Modules\Project\Models\Project;

class PaymentHistoryService
{
    use projectData;

    /**
     * Get Payment History list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistoryList(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'page'   => 'sometimes|integer|min:1',
                'length' => 'sometimes|integer|min:1|max:500',
            ]);
            if ($validator->fails()) {
              return $this->handleValidationFailure($validator);
            }

            $page    = $request->input('page') ?: '1';
            $perPage = $request->input('length') ?: env("TABLE_LIST_LENGTH");
            $dbdata  = Project::leftJoin('transpact', 'transpact.project_id', '=', 'projects.id')
                ->leftJoin('users as contractor', 'projects.contractor_id', '=', 'contractor.id')
                ->leftJoin('users as customer', 'projects.customer_id', '=', 'customer.id')
                ->where('projects.status', Project::DISPUTE)
                ->select(
                    'projects.projectname',
                    'contractor.email as contractor_email',
                    'customer.email as customer_email',
                    'projects.projectamount',
                    'transpact.transpactnumber',
                    'transpact.created_at'
                );

            if ($request->filled('search')) {
                $search = $request->input('search');
                $columns = ['transpact.created_at', 'projectname', 'projectamount', 'contractor.email', 'customer.email', 'transpactnumber'];

                $dbdata->where(function ($query) use ($search, $columns) {
                    foreach ($columns as $index => $column) {
                        if ($index === 0) {
                            $query->where($column, 'like', '%' . $search . '%');
                        } else {
                            $query->orWhere($column, 'like', '%' . $search . '%');
                        }
                    }
                });
            }

            $orderColumn    = $request->input('order_column') ?: 'transpact.created_at';
            $orderDirection = $request->input('order_dir') ?: 'desc';

            $paginatedData = $dbdata->orderBy($orderColumn, $orderDirection)
                ->paginate($perPage, ['*'], 'page', $page);

            return $this->returnSuccess([
                'list'         => $paginatedData->items(),
                'currentPage'  => $paginatedData->currentPage(),
                'totalPages'   => $paginatedData->lastPage(),
                'recordsTotal' => $paginatedData->total(),
            ], 'Payment History List.');
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
