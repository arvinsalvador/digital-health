<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
  $canDelete = $canDelete ?? false;
  $q = $q ?? '';
?>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
    <div class="fw-semibold">Household Profiling</div>

    <div class="d-flex flex-column flex-md-row gap-2 w-100 justify-content-lg-end">
      <form method="get" action="<?= base_url('admin/registry/household-profiling') ?>" class="d-flex flex-grow-1 flex-column flex-sm-row gap-2" style="max-width: 620px;">
        <input
          type="text"
          name="q"
          class="form-control"
          value="<?= esc($q) ?>"
          placeholder="Search household #, respondent, sitio/purok, municipality, barangay"
        >
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-outline-primary">Search</button>
          <?php if ($q !== ''): ?>
            <a href="<?= base_url('admin/registry/household-profiling') ?>" class="btn btn-outline-secondary">Clear</a>
          <?php endif; ?>
        </div>
      </form>

      <a class="btn btn-primary btn-sm" href="<?= base_url('admin/registry/household-profiling/create') ?>">
        <i class="fa-solid fa-plus"></i> New Profiling
      </a>
    </div>
  </div>

  <div class="card-body table-responsive">
    <table class="table table-bordered align-middle">
      <thead>
        <tr>
          <th>Date of Visit</th>
          <th>Quarter</th>
          <th>Visit Count</th>
          <th>Household #</th>
          <th>Barangay PCODE</th>
          <th>Respondent</th>
          <th style="width: 220px;">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if (empty($visits)): ?>
        <tr><td colspan="7" class="text-center text-muted">No records yet.</td></tr>
      <?php endif; ?>

      <?php foreach ($visits as $v): ?>
        <tr>
          <td>
            <div><strong>First:</strong> <?= esc(date('m/d/Y', strtotime($v['visit_date']))) ?></div>
            <div class="small text-muted">
              <strong>Last:</strong>
              <?= !empty($v['last_visit_date']) ? esc(date('m/d/Y', strtotime($v['last_visit_date']))) : '-' ?>
            </div>
          </td>
          <td align="center"><span class="badge bg-info">Q<?= (int) $v['visit_quarter'] ?></span></td>
          <td align="center"><span class="badge bg-secondary"><?= (int)($v['visit_count'] ?? 1) ?></span></td>
          <td><?= esc($v['household_no']) ?></td>
          <td>
            <?= esc($v['municipality_name'] ?? '') ?> - <?= esc($v['barangay_name'] ?? '') ?>
            <span class="text-muted">(<?= esc($v['barangay_pcode']) ?>)</span>
          </td>
          <td><?= esc(($v['respondent_last_name'] ?? '') . ', ' . ($v['respondent_first_name'] ?? '')) ?></td>
          <td>
            <div class="d-flex flex-wrap gap-2">
              <a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/registry/household-profiling/' . $v['id'] . '/edit') ?>">Edit</a>

              <?php if ($canDelete): ?>
                <button
                  type="button"
                  class="btn btn-sm btn-outline-danger js-delete-btn"
                  data-id="<?= (int) $v['id'] ?>"
                  data-household="<?= esc($v['household_no'], 'attr') ?>"
                  data-respondent="<?= esc(($v['respondent_last_name'] ?? '') . ', ' . ($v['respondent_first_name'] ?? ''), 'attr') ?>"
                  data-delete-url="<?= base_url('admin/registry/household-profiling/' . $v['id'] . '/delete') ?>"
                >
                  Delete
                </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php if ($canDelete): ?>
<div class="modal fade" id="deleteVisitModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Confirm Delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Are you sure you want to delete this household profiling record?</p>
        <div class="small text-muted">
          <div><strong>Household #:</strong> <span id="deleteHouseholdNo">-</span></div>
          <div><strong>Respondent:</strong> <span id="deleteRespondent">-</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <form method="post" id="deleteVisitForm" action="">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-danger">Delete</button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const modalEl = document.getElementById('deleteVisitModal');
    if (!modalEl || typeof bootstrap === 'undefined') return;

    const modal = new bootstrap.Modal(modalEl);
    const form = document.getElementById('deleteVisitForm');
    const householdEl = document.getElementById('deleteHouseholdNo');
    const respondentEl = document.getElementById('deleteRespondent');

    document.querySelectorAll('.js-delete-btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        form.action = btn.getAttribute('data-delete-url') || '';
        householdEl.textContent = btn.getAttribute('data-household') || '-';
        respondentEl.textContent = btn.getAttribute('data-respondent') || '-';
        modal.show();
      });
    });
  });
</script>
<?php endif; ?>

<?= $this->endSection() ?>
