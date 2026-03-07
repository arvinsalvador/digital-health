<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitChangeRequestModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();

        $requestModel = new HhVisitChangeRequestModel();

        $builder = $requestModel->builder();
        $builder->where('status', 'pending');

        if (($actor['user_type'] ?? '') === 'admin') {
            $builder->where('review_level', 'admin');
            $builder->where('municipality_pcode', $actor['municipality_pcode']);
        }

        if (($actor['user_type'] ?? '') === 'staff') {
            $builder->where('review_level', 'staff');
            $builder->where('barangay_pcode', $actor['barangay_pcode']);
        }

        $pending = $builder->get()->getResultArray();

        $createCount = 0;
        $updateCount = 0;
        $deleteCount = 0;

        foreach ($pending as $p) {
            if ($p['request_type'] === 'create') $createCount++;
            if ($p['request_type'] === 'update') $updateCount++;
            if ($p['request_type'] === 'delete') $deleteCount++;
        }

        return view('admin/dashboard', [
            'title' => 'Admin',
            'pageTitle' => 'Dashboard',
            'currentUserName' => $this->currentUserName(),
            'actor' => $actor,
            'createCount' => $createCount,
            'updateCount' => $updateCount,
            'deleteCount' => $deleteCount,
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }
}