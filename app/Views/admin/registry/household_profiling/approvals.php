<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong><?= esc($pageTitle ?? 'Profiling Approvals') ?></strong>
    <a href="<?= base_url('admin/registry/household-profiling') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Household No.</th>
            <th>Respondent</th>
            <th>Barangay</th>
            <th>Municipality</th>
            <th>Status</th>
            <th>Action</th>
            <th>Submitted By</th>
            <th style="width: 220px;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="9" class="text-center text-muted">No pending approvals.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= esc($row['household_no'] ?? '-') ?></td>
                <td><?= esc(trim(($row['respondent_last_name'] ?? '') . ', ' . ($row['respondent_first_name'] ?? ''))) ?></td>
                <td><?= esc($row['barangay_name'] ?? ($row['barangay_pcode'] ?? '-')) ?></td>
                <td><?= esc($row['municipality_name'] ?? ($row['municipality_pcode'] ?? '-')) ?></td>
                <td>
                  <span class="badge bg-warning text-dark"><?= esc($row['approval_status'] ?? '-') ?></span>
                </td>
                <td><?= esc($row['approval_action'] ?? '-') ?></td>
                <td><?= esc($row['submitted_by_name'] ?? '-') ?></td>
                <td class="d-flex gap-1 flex-wrap">
                  <a href="<?= base_url('admin/registry/household-profiling/' . (int)$row['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary">
                    View
                  </a>

                  <?php if (($row['approval_status'] ?? '') === 'pending_staff_approval'): ?>
                    <form method="post" action="<?= base_url('admin/registry/household-profiling/' . (int)$row['id'] . '/approve') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="post" action="<?= base_url('admin/registry/household-profiling/' . (int)$row['id'] . '/reject') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-danger">Reject</button>
                    </form>
                  <?php elseif (($row['approval_status'] ?? '') === 'pending_admin_delete_approval'): ?>
                    <form method="post" action="<?= base_url('admin/registry/household-profiling/' . (int)$row['id'] . '/approve-delete') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-danger">Approve Delete</button>
                    </form>
                    <form method="post" action="<?= base_url('admin/registry/household-profiling/' . (int)$row['id'] . '/reject-delete') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-secondary">Reject Delete</button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>