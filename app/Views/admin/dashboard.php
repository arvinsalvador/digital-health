<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
  <?php if(in_array(($actor['user_type'] ?? ''), ['admin','staff','super_admin'])): ?>

    <div class="card mt-3">
        <div class="card-header">
            <strong>Household Profiling Review Queue</strong>
        </div>

        <div class="card-body">

            <div class="row text-center">

                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body">
                            <h2 class="text-success"><?= $createCount ?></h2>
                            <p>New Records</p>

                            <a href="<?= base_url('admin/registry/household-profiling-requests') ?>"
                              class="btn btn-sm btn-success">
                                Review
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-warning">
                        <div class="card-body">
                            <h2 class="text-warning"><?= $updateCount ?></h2>
                            <p>Update Requests</p>

                            <a href="<?= base_url('admin/registry/household-profiling-requests') ?>"
                              class="btn btn-sm btn-warning">
                                Review
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-danger">
                        <div class="card-body">
                            <h2 class="text-danger"><?= $deleteCount ?></h2>
                            <p>Delete Requests</p>

                            <a href="<?= base_url('admin/registry/household-profiling-requests') ?>"
                              class="btn btn-sm btn-danger">
                                Review
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>

  <?php endif; ?>
<?= $this->endSection() ?>
