<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">
      <form method="post" enctype="multipart/form-data"
            action="<?= base_url('admin/settings/modules/upload') ?>">
          <input type="file" name="module_zip" required>
          <button class="btn btn-primary">Upload Module</button>
      </form>
  </div>

  <div class="card-body">
      <table class="table table-bordered">
          <thead>
              <tr>
                  <th>Name</th>
                  <th>Version</th>
                  <th>Status</th>
              </tr>
          </thead>
          <tbody>
          <?php foreach($modules as $m): ?>
              <tr>
                  <td><?= esc($m['name']) ?></td>
                  <td><?= esc($m['version']) ?></td>
                  <td>
                      <?= $m['enabled']
                          ? '<span class="badge bg-success">Enabled</span>'
                          : '<span class="badge bg-secondary">Disabled</span>' ?>
                  </td>
              </tr>
          <?php endforeach ?>
          </tbody>
      </table>
  </div>
</div>

<?= $this->endSection() ?>
