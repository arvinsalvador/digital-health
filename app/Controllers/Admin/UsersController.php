<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;

class UsersController extends BaseController
{
    private array $validUserTypes = [
        'super_admin',
        'admin',
        'staff',
        'bhw',
        'barangay_captain',
    ];

    public function index()
    {
        $actor = $this->actor();

        if (! $this->canManageUsers()) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to access Users.');
        }

        $model = new UserModel();
        $builder = $model->orderBy('created_at', 'DESC');

        $actorType = (string)($actor['user_type'] ?? '');

        if ($actorType === 'super_admin') {
            $users = $builder->findAll();
        } elseif ($actorType === 'admin') {
            $users = $builder
                ->where('municipality_pcode', $actor['municipality_pcode'] ?? null)
                ->findAll();
        } elseif ($actorType === 'staff') {
            $users = $builder
                ->where('barangay_pcode', $actor['barangay_pcode'] ?? null)
                ->whereIn('user_type', ['bhw', 'barangay_captain'])
                ->findAll();
        } else {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to access Users.');
        }

        return view('admin/settings/users/index', [
            'pageTitle' => 'Users',
            'users' => $users,
            'actor' => $actor,
            'currentUserName' => $this->currentUserName(),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function create()
    {
        $actor = $this->actor();

        if (! $this->canManageUsers()) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to add users.');
        }

        return view('admin/settings/users/form', [
            'pageTitle' => 'Add User',
            'mode' => 'create',
            'user' => null,
            'actor' => $actor,
            'allowedUserTypes' => $this->allowedUserTypesForActor((string)($actor['user_type'] ?? '')),
            'lock' => $this->locationLockForActor($actor),
            'currentUserName' => $this->currentUserName(),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function store()
    {
        $actor = $this->actor();

        if (! $this->canManageUsers()) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to add users.');
        }

        $post = $this->request->getPost();

        $rules = [
            'username'   => 'required|min_length[4]|max_length[50]|is_unique[users.username]',
            'email'      => 'required|valid_email|max_length[120]|is_unique[users.email]',
            'password'   => 'required|min_length[4]|max_length[255]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required|in_list[' . implode(',', $this->validUserTypes) . ']',
        ];

        
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetType = (string)($post['user_type'] ?? '');
        $allowed = $this->allowedUserTypesForActor((string)($actor['user_type'] ?? ''));

        if (! in_array($targetType, $allowed, true)) {
            return redirect()->back()->withInput()->with('error', 'You are not allowed to create this user type.');
        }

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

        $this->applyLocationScopeByActorAndTarget($actor, $targetType, $post, $data);

        $scopeError = $this->validateTargetScope($targetType, $data);
        if ($scopeError) {
            return redirect()->back()->withInput()->with('error', $scopeError);
        }

        $model = new UserModel();
        $model->insert($data);

        return redirect()->to(base_url('admin/settings/users'))->with('success', 'User created successfully.');
    }

    public function edit($id)
    {
        $actor = $this->actor();

        if (! $this->canManageUsers()) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to edit users.');
        }

        $model = new UserModel();
        $user  = $model->find((int)$id);

        if (! $user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        if (! $this->canManageTargetUser($actor, $user)) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'You are not allowed to edit this user.');
        }

        return view('admin/settings/users/form', [
            'pageTitle' => 'Edit User',
            'mode' => 'edit',
            'user' => $user,
            'actor' => $actor,
            'allowedUserTypes' => $this->allowedUserTypesForActor((string)($actor['user_type'] ?? '')),
            'lock' => $this->locationLockForActor($actor),
            'currentUserName' => $this->currentUserName(),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function update($id)
    {
        $actor = $this->actor();

        if (! $this->canManageUsers()) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to update users.');
        }

        $post  = $this->request->getPost();
        $model = new UserModel();
        $user  = $model->find((int)$id);

        if (! $user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        if (! $this->canManageTargetUser($actor, $user)) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'You are not allowed to update this user.');
        }

        $rules = [
            'email'      => 'required|valid_email|max_length[120]',
            'first_name' => 'required|max_length[100]',
            'last_name'  => 'required|max_length[100]',
            'user_type'  => 'required|in_list[' . implode(',', $this->validUserTypes) . ']',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $targetType = (string)($post['user_type'] ?? '');
        $allowed = $this->allowedUserTypesForActor((string)($actor['user_type'] ?? ''));

        if (! in_array($targetType, $allowed, true)) {
            return redirect()->back()->withInput()->with('error', 'You are not allowed to assign this user type.');
        }

        $data = [
            'email'       => trim((string)$post['email']),
            'first_name'  => trim((string)$post['first_name']),
            'middle_name' => trim((string)($post['middle_name'] ?? '')) ?: null,
            'last_name'   => trim((string)$post['last_name']),
            'contact_no'  => trim((string)($post['contact_no'] ?? '')) ?: null,
            'address_line'=> trim((string)($post['address_line'] ?? '')) ?: null,
            'postal_code' => trim((string)($post['postal_code'] ?? '')) ?: null,
            'user_type'   => $targetType,
        ];

        if (! empty($post['password'])) {
            $data['password_hash'] = password_hash((string)$post['password'], PASSWORD_DEFAULT);
        }

        $this->applyLocationScopeByActorAndTarget($actor, $targetType, $post, $data);

        $scopeError = $this->validateTargetScope($targetType, $data);
        if ($scopeError) {
            return redirect()->back()->withInput()->with('error', $scopeError);
        }

        $model->update((int)$id, $data);

        return redirect()->to(base_url('admin/settings/users'))->with('success', 'User updated successfully.');
    }

    public function toggle($id)
    {
        $actor = $this->actor();

        if (! $this->canManageUsers()) {
            return redirect()->to(base_url('admin/dashboard'))
                ->with('error', 'You are not allowed to update user status.');
        }

        $model = new UserModel();
        $user  = $model->find((int)$id);

        if (! $user) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'User not found.');
        }

        if (! $this->canManageTargetUser($actor, $user)) {
            return redirect()->to(base_url('admin/settings/users'))->with('error', 'You are not allowed to update this user.');
        }

        $newStatus = ((int)$user['status'] === 1) ? 0 : 1;
        $model->update((int)$id, ['status' => $newStatus]);

        return redirect()->back()->with('success', 'User status updated.');
    }

    private function allowedUserTypesForActor(string $actorType): array
    {
        return match ($actorType) {
            'super_admin' => ['super_admin', 'admin', 'staff', 'bhw', 'barangay_captain'],
            'admin' => ['staff', 'bhw', 'barangay_captain'],
            'staff' => ['bhw', 'barangay_captain'],
            default => [],
        };
    }

    private function canManageTargetUser(array $actor, array $target): bool
    {
        $actorType = (string)($actor['user_type'] ?? '');

        if ($actorType === 'super_admin') {
            return true;
        }

        if ($actorType === 'admin') {
            return ($target['municipality_pcode'] ?? null) === ($actor['municipality_pcode'] ?? null)
                && in_array(($target['user_type'] ?? ''), ['staff', 'bhw', 'barangay_captain'], true);
        }

        if ($actorType === 'staff') {
            return ($target['barangay_pcode'] ?? null) === ($actor['barangay_pcode'] ?? null)
                && in_array(($target['user_type'] ?? ''), ['bhw', 'barangay_captain'], true);
        }

        return false;
    }

    private function validateTargetScope(string $targetType, array $data): ?string
    {
        if ($targetType === 'admin' && empty($data['municipality_pcode'])) {
            return 'Admin must be assigned to a municipality.';
        }

        if (in_array($targetType, ['staff', 'bhw', 'barangay_captain'], true) && empty($data['barangay_pcode'])) {
            return 'This user type must be assigned to a barangay.';
        }

        return null;
    }

    private function applyLocationScopeByActorAndTarget(array $actor, string $targetType, array $post, array &$data): void
    {
        $actorType = (string)($actor['user_type'] ?? '');

        if ($actorType === 'super_admin') {
            $data['region_pcode']       = $post['region_pcode'] ?? null;
            $data['province_pcode']     = $post['province_pcode'] ?? null;
            $data['municipality_pcode'] = $post['municipality_pcode'] ?? null;
            $data['barangay_pcode']     = $post['barangay_pcode'] ?? null;
            return;
        }

        if ($actorType === 'admin') {
            $data['region_pcode']       = $actor['region_pcode'] ?? null;
            $data['province_pcode']     = $actor['province_pcode'] ?? null;
            $data['municipality_pcode'] = $actor['municipality_pcode'] ?? null;
            $data['barangay_pcode']     = $post['barangay_pcode'] ?? null;
            return;
        }

        if ($actorType === 'staff') {
            $data['region_pcode']       = $actor['region_pcode'] ?? null;
            $data['province_pcode']     = $actor['province_pcode'] ?? null;
            $data['municipality_pcode'] = $actor['municipality_pcode'] ?? null;
            $data['barangay_pcode']     = $actor['barangay_pcode'] ?? null;
            return;
        }
    }

    private function locationLockForActor(array $actor): array
    {
        $type = (string)($actor['user_type'] ?? '');

        if ($type === 'super_admin') {
            return [
                'region_locked' => false,
                'province_locked' => false,
                'municipality_locked' => false,
                'barangay_locked' => false,
                'region_pcode' => null,
                'province_pcode' => null,
                'municipality_pcode' => null,
                'barangay_pcode' => null,
            ];
        }

        if ($type === 'admin') {
            return [
                'region_locked' => true,
                'province_locked' => true,
                'municipality_locked' => true,
                'barangay_locked' => false,
                'region_pcode' => $actor['region_pcode'] ?? null,
                'province_pcode' => $actor['province_pcode'] ?? null,
                'municipality_pcode' => $actor['municipality_pcode'] ?? null,
                'barangay_pcode' => null,
            ];
        }

        if ($type === 'staff') {
            return [
                'region_locked' => true,
                'province_locked' => true,
                'municipality_locked' => true,
                'barangay_locked' => true,
                'region_pcode' => $actor['region_pcode'] ?? null,
                'province_pcode' => $actor['province_pcode'] ?? null,
                'municipality_pcode' => $actor['municipality_pcode'] ?? null,
                'barangay_pcode' => $actor['barangay_pcode'] ?? null,
            ];
        }

        return [
            'region_locked' => true,
            'province_locked' => true,
            'municipality_locked' => true,
            'barangay_locked' => true,
            'region_pcode' => $actor['region_pcode'] ?? null,
            'province_pcode' => $actor['province_pcode'] ?? null,
            'municipality_pcode' => $actor['municipality_pcode'] ?? null,
            'barangay_pcode' => $actor['barangay_pcode'] ?? null,
        ];
    }
}