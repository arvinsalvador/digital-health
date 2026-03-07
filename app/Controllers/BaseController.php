<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use App\Models\HhVisitChangeRequestModel;

abstract class BaseController extends Controller
{
    protected array $authUser = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        service('moduleBootstrap')->bootEnabled();

        $u = session('auth_user');
        $this->authUser = is_array($u) ? $u : [];
    }

    protected function actor(): array
    {
        return $this->authUser;
    }

    protected function currentUserName(): string
    {
        if (empty($this->authUser)) {
            return 'User';
        }

        $fn = trim((string)($this->authUser['first_name'] ?? ''));
        $ln = trim((string)($this->authUser['last_name'] ?? ''));

        $name = trim($fn . ' ' . $ln);
        return $name !== '' ? $name : (string)($this->authUser['username'] ?? 'User');
    }

    protected function isSuperAdmin(): bool
    {
        return (($this->authUser['user_type'] ?? '') === 'super_admin');
    }

    protected function isAdmin(): bool
    {
        return (($this->authUser['user_type'] ?? '') === 'admin');
    }

    protected function isStaff(): bool
    {
        return (($this->authUser['user_type'] ?? '') === 'staff');
    }

    protected function isBhw(): bool
    {
        return (($this->authUser['user_type'] ?? '') === 'bhw');
    }

    protected function isBarangayCaptain(): bool
    {
        return (($this->authUser['user_type'] ?? '') === 'barangay_captain');
    }

    protected function canManageModules(): bool
    {
        return $this->isSuperAdmin();
    }

    protected function canManageLocations(): bool
    {
        return $this->isSuperAdmin();
    }

    protected function canManageSystemSettings(): bool
    {
        return $this->isSuperAdmin();
    }

    protected function canManageUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isStaff();
    }

    protected function canGenerateReports(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isStaff();
    }

    protected function canViewMapPage(): bool
    {
        return $this->isSuperAdmin()
            || $this->isAdmin()
            || $this->isStaff()
            || $this->isBhw()
            || $this->isBarangayCaptain();
    }

    protected function canApproveProfilingFromBarangayUsers(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isStaff();
    }

    protected function canApproveStaffDelete(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin();
    }

    protected function profilingNeedsStaffApproval(): bool
    {
        return $this->isBhw() || $this->isBarangayCaptain();
    }

    protected function profilingDeleteNeedsAdminApproval(): bool
    {
        return $this->isStaff();
    }

    protected function pendingProfilingRequestCount(array $actor): int
    {
        $actorType = (string) ($actor['user_type'] ?? '');

        if (! in_array($actorType, ['super_admin', 'admin', 'staff'], true)) {
            return 0;
        }

        $model = new HhVisitChangeRequestModel();
        $builder = $model->builder();

        $builder->where('status', 'pending');

        if ($actorType === 'super_admin') {
            // all pending requests
        } elseif ($actorType === 'admin') {
            $builder->where('review_level', 'admin');
            $builder->where('municipality_pcode', $actor['municipality_pcode'] ?? null);
        } elseif ($actorType === 'staff') {
            $builder->where('review_level', 'staff');
            $builder->where('barangay_pcode', $actor['barangay_pcode'] ?? null);
        }

        return (int) $builder->countAllResults();
    }
}