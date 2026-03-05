<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <strong>Modules</strong>
      <div class="text-muted small">Install, enable/disable, and remove modules.</div>
    </div>

    <form method="post" enctype="multipart/form-data" action="<?= base_url('admin/settings/modules/upload') ?>">
      <?= csrf_field() ?>
      <input type="file" name="module_zip" required>
      <button class="btn btn-primary btn-sm">Upload Module</button>
    </form>
  </div>

  <div class="card-body">

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger mb-3">
        <?= esc(session()->getFlashdata('error')) ?>
      </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success mb-3">
        <?= esc(session()->getFlashdata('success')) ?>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead>
          <tr>
            <th style="width: 30%;">Name</th>
            <th style="width: 15%;">Version</th>
            <th style="width: 15%;">Status</th>
            <th>Description</th>
            <th style="width: 20%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($modules)): ?>
            <tr>
              <td colspan="5" class="text-center text-muted">No modules installed.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($modules as $m): ?>
              <tr>
                <td>
                  <div class="fw-semibold"><?= esc($m['name'] ?? '') ?></div>
                  <div class="small text-muted">
                    Slug: <code><?= esc($m['slug'] ?? '') ?></code>
                  </div>
                </td>

                <td><?= esc($m['version'] ?? '') ?></td>

                <td>
                  <?= ((int)($m['enabled'] ?? 0) === 1)
                    ? '<span class="badge bg-success">Enabled</span>'
                    : '<span class="badge bg-secondary">Disabled</span>' ?>
                </td>

                <td><?= esc($m['description'] ?? '') ?></td>

                <td>
                  <div class="d-flex gap-2 flex-wrap">

                    <?php if ((int)($m['enabled'] ?? 0) === 1): ?>
                      <?php if ((int)($m['can_disable'] ?? 1) === 1): ?>
                        <form method="post" action="<?= base_url('admin/settings/modules/disable/' . ($m['slug'] ?? '')) ?>">
                          <?= csrf_field() ?>
                          <button class="btn btn-warning btn-sm" type="submit">Disable</button>
                        </form>
                      <?php else: ?>
                        <span class="badge bg-info">Protected</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <form method="post" action="<?= base_url('admin/settings/modules/enable/' . ($m['slug'] ?? '')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-success btn-sm" type="submit">Enable</button>
                      </form>
                    <?php endif; ?>

                    <?php if ((int)($m['can_delete'] ?? 1) === 1): ?>
                      <form method="post" action="<?= base_url('admin/settings/modules/delete/' . ($m['slug'] ?? '')) ?>"
                            onsubmit="return confirm('Remove this module? This will delete its files and DB record.');">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                      </form>
                    <?php endif; ?>

                  </div>
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