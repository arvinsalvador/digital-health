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
        if (($actor['user_type'] ?? '') === 'super_admin') {
            $users = $builder->findAll();
        } elseif (in_array(($actor['user_type'] ?? ''), ['admin', 'staff'], true)) {
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
            'currentUserName' => $this->currentUserName(),
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
            'allowedUserTypes' => $this->allowedUserTypesForActor((string)($actor['user_type'] ?? '')),
            'lock'             => $this->locationLockForActor($actor),

            'currentUserName' => $this->currentUserName(),
        ]);
    }

    public function store()
    {
        $actor = $this->actor();
        $post  = $this->request->getPost();

        $rules = [
            'username'   => 'required|min_length[4]|max_length[50]|is_unique[users.username]',
            'email'      => 'required|valid_email|max_length[120]|is_unique[users.email]',
            'password'   => 'required|min_length[4]|max_length[255]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required|in_list[' . implode(',', $this->validUserTypes) . ']',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the form fields.');
        }

        // Ensure actor is allowed to create this type
        $targetType = (string)($post['user_type'] ?? '');
        $allowed = $this->allowedUserTypesForActor((string)($actor['user_type'] ?? ''));
        if (! in_array($targetType, $allowed, true)) {
            return redirect()->back()->withInput()->with('error', 'You are not allowed to create this user type.');
        }

        $lock = $this->locationLockForActor($actor);

        $data = [
            'username'      => trim((string)$post['username']),
            'email'         => trim((string)$post['email']),
            'password_hash' => password_hash((string)$post['password'], PASSWORD_DEFAULT),

            'first_name'  => trim((string)$post['first_name']),
            'middle_name' => trim((string)($post['middle_name'] ?? '')) ?: null,
            'last_name'   => trim((string)$post['last_name']),

            'contact_no'   => trim((string)($post['contact_no'] ?? '')) ?: null,
            'address_line' => trim((string)($post['address_line'] ?? '')) ?: null,
            'postal_code'  => trim((string)($post['postal_code'] ?? '')) ?: null,

            'user_type' => $targetType,
            'status'    => 1,
        ];

        // Location locking rules (actor scope)
        $data['region_pcode']       = $lock['region']       ?? ($post['region_pcode'] ?? null);
        $data['province_pcode']     = $lock['province']     ?? ($post['province_pcode'] ?? null);
        $data['municipality_pcode'] = $lock['municipality'] ?? ($post['municipality_pcode'] ?? null);
        $data['barangay_pcode']     = $lock['barangay']     ?? ($post['barangay_pcode'] ?? null);

        $model = new UserModel();
        $model->insert($data);

        return redirect()->to(base_url('admin/settings/users'))->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $actor = $this->actor();

        $model = new UserModel();
        $user  = $model->find((int)$id);

        if (! $user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        return view('admin/settings/users/form', [
            'pageTitle' => 'Edit User',
            'mode'      => 'edit',
            'user'      => $user,

            'actor'            => $actor,
            'allowedUserTypes' => $this->allowedUserTypesForActor((string)($actor['user_type'] ?? '')),
            'lock'             => $this->locationLockForActor($actor),

            'currentUserName' => $this->currentUserName(),
        ]);
    }

    public function update($id)
    {
        $actor = $this->actor();
        $post  = $this->request->getPost();

        $model = new UserModel();
        $user  = $model->find((int)$id);

        if (! $user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        $rules = [
            'email'      => 'required|valid_email|max_length[120]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required|in_list[' . implode(',', $this->validUserTypes) . ']',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please check the form fields.');
        }

        $targetType = (string)($post['user_type'] ?? '');
        $allowed = $this->allowedUserTypesForActor((string)($actor['user_type'] ?? ''));
        if (! in_array($targetType, $allowed, true)) {
            return redirect()->back()->withInput()->with('error', 'You are not allowed to assign this user type.');
        }

        $lock = $this->locationLockForActor($actor);

        $data = [
            'email'       => trim((string)$post['email']),
            'first_name'  => trim((string)$post['first_name']),
            'middle_name' => trim((string)($post['middle_name'] ?? '')) ?: null,
            'last_name'   => trim((string)$post['last_name']),
            'contact_no'  => trim((string)($post['contact_no'] ?? '')) ?: null,

            'address_line' => trim((string)($post['address_line'] ?? '')) ?: null,
            'postal_code'  => trim((string)($post['postal_code'] ?? '')) ?: null,

            'user_type' => $targetType,
        ];

        // Optional password update
        if (! empty($post['password'])) {
            $data['password_hash'] = password_hash((string)$post['password'], PASSWORD_DEFAULT);
        }

        // Location locking
        $data['region_pcode']       = $lock['region']       ?? ($post['region_pcode'] ?? null);
        $data['province_pcode']     = $lock['province']     ?? ($post['province_pcode'] ?? null);
        $data['municipality_pcode'] = $lock['municipality'] ?? ($post['municipality_pcode'] ?? null);
        $data['barangay_pcode']     = $lock['barangay']     ?? ($post['barangay_pcode'] ?? null);

        $model->update((int)$id, $data);

        return redirect()->to(base_url('admin/settings/users'))->with('success', 'User updated successfully.');
    }

    public function toggle($id)
    {
        $model = new UserModel();
        $user  = $model->find((int)$id);

        if (! $user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        $newStatus = ((int)$user['status'] === 1) ? 0 : 1;
        $model->update((int)$id, ['status' => $newStatus]);

        return redirect()->back()->with('success', 'User status updated.');
    }

    private function allowedUserTypesForActor(string $actorType): array
    {
        return match ($actorType) {
            'super_admin' => $this->validUserTypes,

            // RHU-level can create within municipality scope
            'admin' => ['staff', 'brgy_captain', 'brgy_secretary', 'bhw'],
            'staff' => ['brgy_captain', 'brgy_secretary', 'bhw'],

            // Barangay-level can create BHW only (adjust as needed)
            'brgy_captain', 'brgy_secretary' => ['bhw'],

            default => [],
        };
    }

    private function locationLockForActor(array $actor): array
    {
        $type = (string)($actor['user_type'] ?? '');

        // super_admin can assign any
        if ($type === 'super_admin') {
            return ['region'=>null,'province'=>null,'municipality'=>null,'barangay'=>null];
        }

        // RHU admin/staff locked to municipality (and above if set)
        if (in_array($type, ['admin', 'staff'], true)) {
            return [
                'region' => $actor['region_pcode'] ?? null,
                'province' => $actor['province_pcode'] ?? null,
                'municipality' => $actor['municipality_pcode'] ?? null,
                'barangay' => null,
            ];
        }

        // Barangay roles locked to their barangay
        return [
            'region' => $actor['region_pcode'] ?? null,
            'province' => $actor['province_pcode'] ?? null,
            'municipality' => $actor['municipality_pcode'] ?? null,
            'barangay' => $actor['barangay_pcode'] ?? null,
        ];
    }
}