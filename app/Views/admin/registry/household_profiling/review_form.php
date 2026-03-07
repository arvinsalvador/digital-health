<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Review Household Profiling Request</strong>

        <a href="<?= base_url('admin/registry/household-profiling-requests') ?>" class="btn btn-sm btn-outline-secondary">
            Back
        </a>
    </div>

    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <strong>Request Type:</strong>
                <?= esc($row['request_type'] ?? '-') ?>
            </div>

            <div class="col-md-3">
                <strong>Status:</strong>
                <?= esc($row['status'] ?? '-') ?>
            </div>

            <div class="col-md-3">
                <strong>Review Level:</strong>
                <?= esc($row['review_level'] ?? '-') ?>
            </div>

            <div class="col-md-3">
                <strong>Created:</strong>
                <?= esc($row['created_at'] ?? '-') ?>
            </div>

            <div class="col-md-12">
                <strong>Summary:</strong>
                <?= esc($row['summary_text'] ?? '-') ?>
            </div>
        </div>
    </div>
</div>

<?= view('admin/registry/household_profiling/form', [
    'pageTitle' => $pageTitle ?? 'Review Profiling Request',
    'mode' => 'review',
    'visit' => $visit ?? [],
    'groups' => $groups ?? [],
    'actor' => $actor ?? [],
    'lock' => $lock ?? [],
    'reviewDiff' => $reviewDiff ?? [],
    'currentVisit' => $currentVisit ?? null,
    'row' => $row ?? [],
    'lockedBarangayName' => $lockedBarangayName ?? '',
]) ?>

<div class="card mt-3">
    <div class="card-header">
        <strong>Reviewer Action</strong>
    </div>

    <div class="card-body">
        <form method="post" action="<?= base_url('admin/registry/household-profiling-requests/' . $row['id'] . '/approve') ?>" class="mb-4">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Reviewer Notes</label>
                <textarea class="form-control" name="reviewer_notes" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-success">
                Approve Request
            </button>
        </form>

        <hr>

        <form method="post" action="<?= base_url('admin/registry/household-profiling-requests/' . $row['id'] . '/reject') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Rejection Notes</label>
                <textarea class="form-control" name="reviewer_notes" rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-danger">
                Reject Request
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>