<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

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
}