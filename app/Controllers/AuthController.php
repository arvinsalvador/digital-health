<?php

namespace App\Controllers;

use App\Models\UserModel;

class AuthController extends BaseController
{
    public function login()
    {
        // Already logged in? Go to dashboard.
        if (session()->has('auth_user')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        return view('auth/login', [
            'title' => 'Login',
        ]);
    }

    public function attempt()
    {
        $rules = [
            'identity' => 'required|min_length[3]|max_length[120]',
            'password' => 'required|min_length[4]|max_length[255]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check your login details.');
        }

        $identity = trim((string) $this->request->getPost('identity'));
        $password = (string) $this->request->getPost('password');

        $users = new UserModel();

        // Allow login via username OR email
        $user = $users->groupStart()
                ->where('username', $identity)
                ->orWhere('email', $identity)
            ->groupEnd()
            ->first();

        if (! $user) {
            return redirect()->back()->withInput()->with('error', 'Invalid username/email or password.');
        }

        if ((int)($user['status'] ?? 0) !== 1) {
            return redirect()->back()->withInput()->with('error', 'Your account is disabled. Please contact the administrator.');
        }

        if (! password_verify($password, (string)($user['password_hash'] ?? ''))) {
            return redirect()->back()->withInput()->with('error', 'Invalid username/email or password.');
        }

        // Prevent session fixation
        session()->regenerate(true);

        // Shape expected by your existing controllers (users + household profiling)
        session()->set('auth_user', [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],

            'first_name' => $user['first_name'],
            'middle_name' => $user['middle_name'],
            'last_name' => $user['last_name'],

            'user_type' => $user['user_type'],
            'status' => (int) $user['status'],

            'region_pcode' => $user['region_pcode'],
            'province_pcode' => $user['province_pcode'],
            'municipality_pcode' => $user['municipality_pcode'],
            'barangay_pcode' => $user['barangay_pcode'],
        ]);

        return redirect()->to(base_url('admin/dashboard'));
    }

    public function logout()
    {
        session()->remove('auth_user');
        session()->destroy();

        return redirect()->to(base_url('login'))->with('success', 'You have been logged out.');
    }
}