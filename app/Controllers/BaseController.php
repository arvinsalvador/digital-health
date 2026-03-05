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

        // Cache auth user in controller lifecycle
        $u = session('auth_user');
        $this->authUser = is_array($u) ? $u : [];
    }

    /**
     * Current logged-in user array (shape set by AuthController::attempt()).
     */
    protected function actor(): array
    {
        return $this->authUser;
    }

    /**
     * Friendly display name for navbar.
     */
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
}