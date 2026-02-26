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
    <div class="fw-semibold">Household Profiling</div>
    <a class="btn btn-primary btn-sm" href="<?= base_url('admin/registry/household-profiling/create') ?>">
      <i class="fa-solid fa-plus"></i> New Profiling
    </a>
  </div>

  <div class="card-body table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr>
          <th>Date of Visit</th>
          <th>Quarter</th>
          <th>Household #</th>
          <th>Barangay PCODE</th>
          <th>Respondent</th>
          <th style="width:160px;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($visits)): ?>
        <tr><td colspan="6" class="text-center text-muted">No records yet.</td></tr>
      <?php endif; ?>

      <?php foreach ($visits as $v): ?>
        <tr>
          <td><?= esc(date('m/d/Y', strtotime($v['visit_date']))) ?></td>
          <td><span class="badge bg-info">Q<?= (int)$v['visit_quarter'] ?></span></td>
          <td><?= esc($v['household_no']) ?></td>
          <td><code><?= esc($v['barangay_pcode']) ?></code></td>
          <td><?= esc($v['respondent_last_name'].', '.$v['respondent_first_name']) ?></td>
          <td class="d-flex gap-2">
            <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/registry/household-profiling/'.$v['id'].'/edit') ?>">Edit</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?= $this->endSection() ?>