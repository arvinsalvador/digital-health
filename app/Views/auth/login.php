<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= esc($title ?? 'Login') ?> | Digital Health</title>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-5 col-lg-4">
      <div class="card shadow-sm">
        <div class="card-body p-4">
          <h4 class="mb-1">Digital Health</h4>
          <p class="text-muted mb-4">Please sign in</p>

          <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
          <?php endif; ?>

          <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= base_url('login') ?>">
            <div class="mb-3">
              <label class="form-label">Username or Email</label>
              <input
                type="text"
                name="identity"
                class="form-control"
                value="<?= esc(old('identity')) ?>"
                required
                autofocus
              >
            </div>

            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>

            <button class="btn btn-primary w-100" type="submit">Login</button>
          </form>

          <div class="mt-3 small text-muted">
            Tip: run the SuperAdminSeeder to create the initial account.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>