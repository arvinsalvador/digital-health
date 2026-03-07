<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
function requestTypeBadgeClass(string $type): string
{
    return match ($type) {
        'create' => 'bg-success',
        'update' => 'bg-warning text-dark',
        'delete' => 'bg-danger',
        default  => 'bg-secondary',
    };
}

function requestTypeLabel(string $type): string
{
    return match ($type) {
        'create' => 'New Record',
        'update' => 'Update',
        'delete' => 'Delete',
        default  => ucfirst($type),
    };
}

function reviewLevelBadgeClass(string $level): string
{
    return match ($level) {
        'staff' => 'bg-primary',
        'admin' => 'bg-dark',
        default => 'bg-secondary',
    };
}

function requestRowClass(string $type): string
{
    return match ($type) {
        'create' => 'table-success',
        'update' => 'table-warning',
        'delete' => 'table-danger',
        default  => '',
    };
}
?>

<style>
  .queue-summary {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .summary-pill {
    border: 1px solid #e5e7eb;
    border-radius: 999px;
    padding: 6px 12px;
    background: #fff;
    font-size: 13px;
    font-weight: 600;
  }
  .summary-pill .count {
    margin-left: 6px;
    font-weight: 700;
  }
  .request-summary-text {
    max-width: 520px;
    white-space: normal;
  }
</style>

<?php
$createCount = 0;
$updateCount = 0;
$deleteCount = 0;

foreach (($rows ?? []) as $r) {
    $type = (string)($r['request_type'] ?? '');
    if ($type === 'create') $createCount++;
    if ($type === 'update') $updateCount++;
    if ($type === 'delete') $deleteCount++;
}
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong><?= esc($pageTitle ?? 'Profiling Requests') ?></strong>
    <a href="<?= base_url('admin/registry/household-profiling') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
  </div>

  <div class="card-body">
    <div class="queue-summary mb-3">
      <div class="summary-pill">
        <span class="badge bg-success">New</span>
        <span class="count"><?= (int)$createCount ?></span>
      </div>
      <div class="summary-pill">
        <span class="badge bg-warning text-dark">Update</span>
        <span class="count"><?= (int)$updateCount ?></span>
      </div>
      <div class="summary-pill">
        <span class="badge bg-danger">Delete</span>
        <span class="count"><?= (int)$deleteCount ?></span>
      </div>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered table-sm align-middle">
        <thead>
          <tr>
            <th style="width:80px;">ID</th>
            <th style="width:140px;">Type</th>
            <th style="width:120px;">Review</th>
            <th>Summary</th>
            <th style="width:170px;">Created</th>
            <th style="width:120px;">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr>
              <td colspan="6" class="text-center text-muted">No pending requests.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($rows as $row): ?>
              <?php
                $type = (string)($row['request_type'] ?? '');
                $reviewLevel = (string)($row['review_level'] ?? '');
              ?>
              <tr class="<?= esc(requestRowClass($type)) ?>">
                <td><?= (int)($row['id'] ?? 0) ?></td>

                <td>
                  <span class="badge <?= esc(requestTypeBadgeClass($type)) ?>">
                    <?= esc(requestTypeLabel($type)) ?>
                  </span>
                </td>

                <td>
                  <span class="badge <?= esc(reviewLevelBadgeClass($reviewLevel)) ?>">
                    <?= esc(ucfirst($reviewLevel ?: '-')) ?>
                  </span>
                </td>

                <td class="request-summary-text">
                  <?= esc($row['summary_text'] ?? '-') ?>
                </td>

                <td><?= esc($row['created_at'] ?? '-') ?></td>

                <td>
                  <a href="<?= base_url('admin/registry/household-profiling-requests/' . (int)$row['id']) ?>"
                     class="btn btn-sm btn-outline-primary">
                    Review
                  </a>
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