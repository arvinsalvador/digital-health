<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
$diff = $row['diff_payload'] ?? [];

function changeBadgeClass(string $type): string
{
    return match ($type) {
        'added_group',
        'added_member',
        'added_medical_history' => 'bg-success',

        'updated_group',
        'updated_member',
        'updated_medical_history' => 'bg-warning text-dark',

        'removed_group',
        'removed_member',
        'removed_medical_history' => 'bg-danger',

        default => 'bg-secondary',
    };
}

function changeRowClass(string $type): string
{
    return match ($type) {
        'added_group',
        'added_member',
        'added_medical_history' => 'table-success',

        'updated_group',
        'updated_member',
        'updated_medical_history' => 'table-warning',

        'removed_group',
        'removed_member',
        'removed_medical_history' => 'table-danger',

        default => '',
    };
}

function changeLabel(string $type): string
{
    return match ($type) {
        'added_group' => 'Added Group',
        'updated_group' => 'Updated Group',
        'removed_group' => 'Removed Group',

        'added_member' => 'Added Member',
        'updated_member' => 'Updated Member',
        'removed_member' => 'Removed Member',

        'added_medical_history' => 'Added Medical History',
        'updated_medical_history' => 'Updated Medical History',
        'removed_medical_history' => 'Removed Medical History',

        default => ucwords(str_replace('_', ' ', $type)),
    };
}
?>

<style>
  .change-legend {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .change-card-label {
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 6px;
  }
  .mini-change-box {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 10px 12px;
    background: #fff;
  }
  .old-val {
    color: #6b7280;
  }
  .new-val {
    font-weight: 600;
  }
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Request Summary</strong>
        <a href="<?= base_url('admin/registry/household-profiling-requests') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <strong>Type:</strong> <?= esc($row['request_type']) ?>
            </div>
            <div class="col-md-3">
                <strong>Status:</strong> <?= esc($row['status']) ?>
            </div>
            <div class="col-md-3">
                <strong>Review Level:</strong> <?= esc($row['review_level'] ?? '-') ?>
            </div>
            <div class="col-md-3">
                <strong>Created:</strong> <?= esc($row['created_at']) ?>
            </div>
            <div class="col-md-12">
                <strong>Summary:</strong> <?= esc($row['summary_text']) ?>
            </div>
        </div>

        <div class="change-legend">
            <span class="badge bg-success">Added</span>
            <span class="badge bg-warning text-dark">Updated</span>
            <span class="badge bg-danger">Removed</span>
        </div>
    </div>
</div>

<?php if (!empty($diff['visit_fields'])): ?>
<div class="card mb-3">
    <div class="card-header">
        <strong>Visit Field Changes</strong>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th style="width:220px;">Field</th>
                    <th>Old</th>
                    <th>New</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diff['visit_fields'] as $field => $change): ?>
                    <tr class="table-warning">
                        <td><?= esc(ucwords(str_replace('_', ' ', $field))) ?></td>
                        <td class="old-val"><?= esc((string)($change['old'] ?? '')) ?></td>
                        <td class="new-val"><?= esc((string)($change['new'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($diff['groups'])): ?>
<div class="card mb-3">
    <div class="card-header">
        <strong>Family Group Changes</strong>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th style="width:180px;">Action</th>
                    <th>Group</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diff['groups'] as $g): ?>
                    <tr class="<?= esc(changeRowClass((string)($g['type'] ?? ''))) ?>">
                        <td>
                            <span class="badge <?= esc(changeBadgeClass((string)($g['type'] ?? ''))) ?>">
                                <?= esc(changeLabel((string)($g['type'] ?? ''))) ?>
                            </span>
                        </td>
                        <td><?= esc($g['group_name'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($g['changes']) && is_array($g['changes'])): ?>
                                <div class="row g-2">
                                    <?php foreach ($g['changes'] as $field => $change): ?>
                                        <div class="col-md-6">
                                            <div class="mini-change-box">
                                                <div class="change-card-label"><?= esc(ucwords(str_replace('_', ' ', $field))) ?></div>
                                                <div class="old-val">Old: <?= esc((string)($change['old'] ?? '')) ?></div>
                                                <div class="new-val">New: <?= esc((string)($change['new'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($diff['members'])): ?>
<div class="card mb-3">
    <div class="card-header">
        <strong>Member Changes</strong>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th style="width:180px;">Action</th>
                    <th style="width:260px;">Member</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diff['members'] as $m): ?>
                    <tr class="<?= esc(changeRowClass((string)($m['type'] ?? ''))) ?>">
                        <td>
                            <span class="badge <?= esc(changeBadgeClass((string)($m['type'] ?? ''))) ?>">
                                <?= esc(changeLabel((string)($m['type'] ?? ''))) ?>
                            </span>
                        </td>
                        <td><?= esc($m['member_name'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($m['changes']) && is_array($m['changes'])): ?>
                                <div class="row g-2">
                                    <?php foreach ($m['changes'] as $field => $change): ?>
                                        <div class="col-md-6">
                                            <div class="mini-change-box">
                                                <div class="change-card-label"><?= esc(ucwords(str_replace('_', ' ', $field))) ?></div>
                                                <div class="old-val">Old: <?= esc((string)($change['old'] ?? '')) ?></div>
                                                <div class="new-val">New: <?= esc((string)($change['new'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($diff['medical_histories'])): ?>
<div class="card mb-3">
    <div class="card-header">
        <strong>Medical History Changes</strong>
    </div>

    <div class="card-body">
        <table class="table table-bordered table-sm align-middle">
            <thead>
                <tr>
                    <th style="width:180px;">Action</th>
                    <th style="width:260px;">Member</th>
                    <th style="width:220px;">Condition</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($diff['medical_histories'] as $mh): ?>
                    <tr class="<?= esc(changeRowClass((string)($mh['type'] ?? ''))) ?>">
                        <td>
                            <span class="badge <?= esc(changeBadgeClass((string)($mh['type'] ?? ''))) ?>">
                                <?= esc(changeLabel((string)($mh['type'] ?? ''))) ?>
                            </span>
                        </td>
                        <td><?= esc($mh['member_name'] ?? '-') ?></td>
                        <td><?= esc($mh['condition_name'] ?? '-') ?></td>
                        <td>
                            <?php if (!empty($mh['changes']) && is_array($mh['changes'])): ?>
                                <div class="row g-2">
                                    <?php foreach ($mh['changes'] as $field => $change): ?>
                                        <div class="col-md-6">
                                            <div class="mini-change-box">
                                                <div class="change-card-label"><?= esc(ucwords(str_replace('_', ' ', $field))) ?></div>
                                                <div class="old-val">Old: <?= esc((string)($change['old'] ?? '')) ?></div>
                                                <div class="new-val">New: <?= esc((string)($change['new'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Reviewer Action</strong>
    </div>

    <div class="card-body">
        <form method="post" action="<?= base_url('admin/registry/household-profiling-requests/'.$row['id'].'/approve') ?>" class="mb-4">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Reviewer Notes</label>
                <textarea class="form-control" name="reviewer_notes" rows="3"></textarea>
            </div>

            <button class="btn btn-success">
                Approve Request
            </button>
        </form>

        <hr>

        <form method="post" action="<?= base_url('admin/registry/household-profiling-requests/'.$row['id'].'/reject') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Rejection Notes</label>
                <textarea class="form-control" name="reviewer_notes" rows="3"></textarea>
            </div>

            <button class="btn btn-danger">
                Reject Request
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>