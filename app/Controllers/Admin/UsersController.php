<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UsersController extends BaseController
{
    /**
     * Canonical user types (keep consistent across DB + UI)
     */
    private array $validUserTypes = [
        'super_admin',
        'admin',          // RHU Admin
        'staff',          // RHU Staff
        'brgy_captain',
        'brgy_secretary',
        'bhw',
    ];

    public function index()
    {
        $actor = $this->actor();

        $model = new UserModel();
        $builder = $model->orderBy('created_at', 'DESC');

        // Scope-filter list
        if ($actor['user_type'] === 'super_admin') {
            $users = $builder->findAll();
        } elseif (in_array($actor['user_type'], ['admin', 'staff'], true)) {
            $users = $builder
                ->where('municipality_pcode', $actor['municipality_pcode'] ?? null)
                ->findAll();
        } else {
            $users = $builder
                ->where('barangay_pcode', $actor['barangay_pcode'] ?? null)
                ->findAll();
        }

        return view('admin/settings/users/index', [
            'pageTitle' => 'Users',
            'users'     => $users,
            'actor'     => $actor,
        ]);
    }

    public function create()
    {
        $actor = $this->actor();

        return view('admin/settings/users/form', [
            'pageTitle' => 'Add User',
            'mode'      => 'create',
            'user'      => null,

            'actor'            => $actor,
            'allowedUserTypes' => $this->allowedUserTypesForActor($actor['user_type']),
            'lock'             => $this->locationLockForActor($actor),
        ]);
    }

    public function store()
    {
        $actor = $this->actor();
        $post  = $this->request->getPost();

        // Basic validation
        $rules = [
            'username'   => 'required|min_length[4]|max_length[50]|is_unique[users.username]',
            'email'      => 'required|valid_email|is_unique[users.email]',
            'password'   => 'required|min_length[6]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        // Enforce allowed user types based on actor
        $userType = trim((string)($post['user_type'] ?? ''));
        if (!in_array($userType, $this->validUserTypes, true)) {
            return back()->withInput()->with('error', 'Invalid user type.');
        }

        $allowedTypes = $this->allowedUserTypesForActor($actor['user_type']);
        if (!in_array($userType, $allowedTypes, true)) {
            return back()->withInput()->with('error', 'You are not allowed to create that user type.');
        }

        // Apply location locks
        $lock = $this->locationLockForActor($actor);

        $region       = $post['region_pcode'] ?? null;
        $province     = $post['province_pcode'] ?? null;
        $municipality = $post['municipality_pcode'] ?? null;
        $barangay     = $post['barangay_pcode'] ?? null;

        if ($lock['region_locked'])       $region       = $lock['region_pcode'];
        if ($lock['province_locked'])     $province     = $lock['province_pcode'];
        if ($lock['municipality_locked']) $municipality = $lock['municipality_pcode'];
        if ($lock['barangay_locked'])     $barangay     = $lock['barangay_pcode'];

        // Location requirements
        $barangayTypes = ['brgy_captain', 'brgy_secretary', 'bhw'];
        if (in_array($userType, $barangayTypes, true) && empty($barangay)) {
            return back()->withInput()->with('error', 'Barangay is required for barangay-level user types.');
        }

        // Non-barangay user types should not be assigned to barangay
        if (in_array($userType, ['super_admin', 'admin', 'staff'], true)) {
            $barangay = null;
        }

        // Scope enforcement for non-SA
        if ($actor['user_type'] !== 'super_admin') {
            // Admin/Staff: must be within their municipality
            if (in_array($actor['user_type'], ['admin','staff'], true)) {
                if (($actor['municipality_pcode'] ?? null) !== $municipality) {
                    return back()->withInput()->with('error', 'You can only create users within your municipality.');
                }
            }

            // Barangay roles: must be within their barangay
            if (in_array($actor['user_type'], ['brgy_captain','brgy_secretary','bhw'], true)) {
                if (($actor['barangay_pcode'] ?? null) !== $barangay) {
                    return back()->withInput()->with('error', 'You can only create users within your barangay.');
                }
            }
        }

        // Save
        (new UserModel())->insert([
            'username'      => trim($post['username']),
            'password_hash' => password_hash($post['password'], PASSWORD_DEFAULT),
            'email'         => trim($post['email']),

            'first_name'    => trim($post['first_name']),
            'middle_name'   => trim($post['middle_name'] ?? '') ?: null,
            'last_name'     => trim($post['last_name']),

            'contact_no'    => trim($post['contact_no'] ?? '') ?: null,

            'address_line'  => trim($post['address_line'] ?? '') ?: null,
            'postal_code'   => trim($post['postal_code'] ?? '') ?: null,

            'region_pcode'       => $region ?: null,
            'province_pcode'     => $province ?: null,
            'municipality_pcode' => $municipality ?: null,
            'barangay_pcode'     => $barangay ?: null,

            'user_type' => $userType,
            'status'    => 1,
        ]);

        return redirect()->to(base_url('admin/settings/users'))
            ->with('success', 'User created.');
    }

    public function edit(int $id)
    {
        $actor = $this->actor();

        $model = new UserModel();
        $user = $model->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        if (!$this->canManageTargetUser($actor, $user)) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'Not allowed.');
        }

        return view('admin/settings/users/form', [
            'pageTitle' => 'Edit User',
            'mode'      => 'edit',
            'user'      => $user,

            'actor'            => $actor,
            'allowedUserTypes' => $this->allowedUserTypesForActor($actor['user_type']),
            'lock'             => $this->locationLockForActor($actor),
        ]);
    }

    public function update(int $id)
    {
        $actor = $this->actor();
        $model = new UserModel();
        $user  = $model->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        if (!$this->canManageTargetUser($actor, $user)) {
            return back()->with('error', 'Not allowed.');
        }

        $post = $this->request->getPost();

        $rules = [
            'username'   => "required|min_length[4]|max_length[50]|is_unique[users.username,id,{$id}]",
            'email'      => "required|valid_email|is_unique[users.email,id,{$id}]",
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required',
        ];

        if (!$this->validate($rules)) {
            return back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $userType = trim((string)($post['user_type'] ?? ''));
        if (!in_array($userType, $this->validUserTypes, true)) {
            return back()->withInput()->with('error', 'Invalid user type.');
        }

        $allowedTypes = $this->allowedUserTypesForActor($actor['user_type']);
        if (!in_array($userType, $allowedTypes, true)) {
            return back()->withInput()->with('error', 'You are not allowed to set that user type.');
        }

        // Apply location locks
        $lock = $this->locationLockForActor($actor);

        $region       = $post['region_pcode'] ?? $user['region_pcode'];
        $province     = $post['province_pcode'] ?? $user['province_pcode'];
        $municipality = $post['municipality_pcode'] ?? $user['municipality_pcode'];
        $barangay     = $post['barangay_pcode'] ?? $user['barangay_pcode'];

        if ($lock['region_locked'])       $region       = $lock['region_pcode'];
        if ($lock['province_locked'])     $province     = $lock['province_pcode'];
        if ($lock['municipality_locked']) $municipality = $lock['municipality_pcode'];
        if ($lock['barangay_locked'])     $barangay     = $lock['barangay_pcode'];

        $barangayTypes = ['brgy_captain', 'brgy_secretary', 'bhw'];

        if (in_array($userType, $barangayTypes, true) && empty($barangay)) {
            return back()->withInput()->with('error', 'Barangay is required for barangay-level user types.');
        }

        if (in_array($userType, ['super_admin', 'admin', 'staff'], true)) {
            $barangay = null;
        }

        // Scope enforcement for non-SA
        if ($actor['user_type'] !== 'super_admin') {
            if (in_array($actor['user_type'], ['admin','staff'], true)) {
                if (($actor['municipality_pcode'] ?? null) !== $municipality) {
                    return back()->withInput()->with('error', 'You can only assign users within your municipality.');
                }
            }
            if (in_array($actor['user_type'], ['brgy_captain','brgy_secretary','bhw'], true)) {
                if (($actor['barangay_pcode'] ?? null) !== $barangay) {
                    return back()->withInput()->with('error', 'You can only assign users within your barangay.');
                }
            }
        }

        $payload = [
            'username'      => trim($post['username']),
            'email'         => trim($post['email']),
            'first_name'    => trim($post['first_name']),
            'middle_name'   => trim($post['middle_name'] ?? '') ?: null,
            'last_name'     => trim($post['last_name']),
            'contact_no'    => trim($post['contact_no'] ?? '') ?: null,
            'address_line'  => trim($post['address_line'] ?? '') ?: null,
            'postal_code'   => trim($post['postal_code'] ?? '') ?: null,

            'region_pcode'       => $region ?: null,
            'province_pcode'     => $province ?: null,
            'municipality_pcode' => $municipality ?: null,
            'barangay_pcode'     => $barangay ?: null,

            'user_type'     => $userType,
        ];

        // Optional password update
        $pwd = trim((string)($post['password'] ?? ''));
        if ($pwd !== '') {
            if (strlen($pwd) < 6) {
                return back()->withInput()->with('error', 'Password must be at least 6 characters.');
            }
            $payload['password_hash'] = password_hash($pwd, PASSWORD_DEFAULT);
        }

        $model->update($id, $payload);

        return redirect()->to(base_url('admin/settings/users'))
            ->with('success', 'User updated.');
    }

    public function toggle(int $id)
    {
        $actor = $this->actor();
        $model = new UserModel();
        $user  = $model->find($id);

        if (!$user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        if (!$this->canManageTargetUser($actor, $user)) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'Not allowed.');
        }

        $new = ((int)$user['status'] === 1) ? 0 : 1;
        $model->update($id, ['status' => $new]);

        return redirect()->to(base_url('admin/settings/users'))
            ->with('success', $new ? 'User enabled.' : 'User disabled.');
    }

    /* ===========================
       Permission + Scope Helpers
    =========================== */

    private function actor(): array
    {
        /**
         * Expected shape of session('auth_user'):
         * [
         *   'id' => 1,
         *   'user_type' => 'admin',
         *   'region_pcode' => '...',
         *   'province_pcode' => '...',
         *   'municipality_pcode' => '...',
         *   'barangay_pcode' => '...',
         * ]
         */
        $u = session('auth_user');

        // DEV fallback (remove when auth is ready)
        if (!$u) {
            return [
                'id' => 0,
                'user_type' => 'super_admin',
                'region_pcode' => null,
                'province_pcode' => null,
                'municipality_pcode' => null,
                'barangay_pcode' => null,
            ];
        }

        return $u;
    }

    private function allowedUserTypesForActor(string $actorType): array
    {
        return match ($actorType) {
            // SA can create all except SA (recommended to keep SA creation controlled)
            'super_admin' => ['admin','staff','brgy_captain','brgy_secretary','bhw'],

            // Admin can create below it
            'admin' => ['staff','brgy_captain','brgy_secretary','bhw'],

            // RHU Staff similar to admin but cannot create admin
            'staff' => ['brgy_captain','brgy_secretary','bhw'],

            // Barangay Captain can create below it
            'brgy_captain' => ['brgy_secretary','bhw'],

            // Barangay Secretary can create BHW only
            'brgy_secretary' => ['bhw'],

            // BHW cannot create users
            'bhw' => [],

            default => [],
        };
    }

    private function locationLockForActor(array $actor): array
    {
        // SA can choose all
        $lock = [
            'region_locked'       => false,
            'province_locked'     => false,
            'municipality_locked' => false,
            'barangay_locked'     => false,

            'region_pcode'       => $actor['region_pcode'] ?? null,
            'province_pcode'     => $actor['province_pcode'] ?? null,
            'municipality_pcode' => $actor['municipality_pcode'] ?? null,
            'barangay_pcode'     => $actor['barangay_pcode'] ?? null,
        ];

        if (($actor['user_type'] ?? '') === 'super_admin') {
            return $lock;
        }

        // Admin/Staff: locked to municipality; can select barangay
        if (in_array(($actor['user_type'] ?? ''), ['admin','staff'], true)) {
            $lock['region_locked'] = true;
            $lock['province_locked'] = true;
            $lock['municipality_locked'] = true;
            $lock['barangay_locked'] = false;
            return $lock;
        }

        // Barangay roles: all locked to their barangay
        $lock['region_locked'] = true;
        $lock['province_locked'] = true;
        $lock['municipality_locked'] = true;
        $lock['barangay_locked'] = true;
        return $lock;
    }

    private function canManageTargetUser(array $actor, array $target): bool
    {
        // SA can manage anyone
        if (($actor['user_type'] ?? '') === 'super_admin') {
            return true;
        }

        // Admin/Staff can manage within municipality
        if (in_array(($actor['user_type'] ?? ''), ['admin','staff'], true)) {
            return ($target['municipality_pcode'] ?? null) === ($actor['municipality_pcode'] ?? null);
        }

        // Barangay roles can manage within barangay
        return ($target['barangay_pcode'] ?? null) === ($actor['barangay_pcode'] ?? null);
    }
}