<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();
        return view('admin/dashboard', [
            'title' => 'Admin',
            'pageTitle' => 'Dashboard',
            'currentUserName' => $this->currentUserName(),
            'actor' => $this->actor(),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }
}