<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div class="fw-semibold">Users</div>
    <a href="<?= base_url('admin/settings/users/create') ?>" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Add User
    </a>
  </div>

  <div class="card-body table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr>
          <th>Name</th>
          <th>Username</th>
          <th>User Type</th>
          <th>Barangay PCODE</th>
          <th>Status</th>
          <th style="width: 200px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="6" class="text-center text-muted">No users yet.</td></tr>
        <?php endif; ?>

        <?php foreach ($users as $u): ?>
          <tr>
            <td><?= esc($u['last_name'] . ', ' . $u['first_name']) ?></td>
            <td><?= esc($u['username']) ?></td>
            <td><span class="badge bg-info"><?= esc($u['user_type']) ?></span></td>
            <td><code><?= esc($u['barangay_pcode'] ?? '-') ?></code></td>
            <td>
              <?= ((int)$u['status'] === 1)
                ? '<span class="badge bg-success">Active</span>'
                : '<span class="badge bg-secondary">Disabled</span>' ?>
            </td>
            <td class="d-flex gap-2">
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/settings/users/'.$u['id'].'/edit') ?>">
                Edit
              </a>

              <form method="post" action="<?= base_url('admin/settings/users/'.$u['id'].'/toggle') ?>">
                <?= csrf_field() ?>
                <button class="btn btn-sm <?= ((int)$u['status']===1) ? 'btn-outline-secondary' : 'btn-outline-success' ?>">
                  <?= ((int)$u['status']===1) ? 'Disable' : 'Enable' ?>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>