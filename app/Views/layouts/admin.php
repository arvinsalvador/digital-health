<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Dashboard') ?> | Digital Health</title>

  <!-- Bootstrap 5 (CDN) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

  <!-- Font Awesome (CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <!-- AdminLTE (built output copied to /public/adminlte/dist/...) -->
  <link rel="stylesheet" href="<?= base_url('adminlte/dist/css/adminlte.min.css') ?>">

  <?= $this->renderSection('styles') ?>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
  <div class="app-wrapper">

    <!-- Navbar -->
    <?= $this->include('partials/admin_navbar') ?>

    <!-- Sidebar -->
    <?= $this->include('partials/admin_sidebar') ?>

    <!-- Main Content -->
    <main class="app-main">
      <div class="app-content-header">
        <div class="container-fluid">
          <div class="row">
            <div class="col-sm-6">
              <h3 class="mb-0"><?= esc($pageTitle ?? 'Dashboard') ?></h3>
            </div>
            <div class="col-sm-6">
              <ol class="breadcrumb float-sm-end mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Home</a></li>
                <?php if (!empty($breadcrumb ?? null)): ?>
                  <li class="breadcrumb-item active"><?= esc($breadcrumb) ?></li>
                <?php else: ?>
                  <li class="breadcrumb-item active"><?= esc($pageTitle ?? 'Dashboard') ?></li>
                <?php endif; ?>
              </ol>
            </div>
          </div>
        </div>
      </div>

      <div class="app-content">
        <div class="container-fluid">
          <?= $this->renderSection('content') ?>
        </div>
      </div>
    </main>

    <!-- Footer -->
    <?= $this->include('partials/admin_footer') ?>

  </div>

  <!-- Bootstrap JS (CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <!-- AdminLTE JS -->
  <script src="<?= base_url('adminlte/dist/js/adminlte.min.js') ?>"></script>

  <?= $this->renderSection('scripts') ?>
</body>
</html>
