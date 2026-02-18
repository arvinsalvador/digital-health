<nav class="app-header navbar navbar-expand bg-body">
  <div class="container-fluid">

    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
          <i class="fa-solid fa-bars"></i>
        </a>
      </li>
      <li class="nav-item d-none d-md-block">
        <a href="<?= base_url('admin/dashboard') ?>" class="nav-link">Dashboard</a>
      </li>
    </ul>

    <ul class="navbar-nav ms-auto">
      <li class="nav-item dropdown">
        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
          <i class="fa-solid fa-user"></i>
          <span class="ms-1"><?= esc($currentUserName ?? 'Admin') ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><a class="dropdown-item" href="#">Profile</a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="<?= base_url('logout') ?>">Logout</a></li>
        </ul>
      </li>
    </ul>

  </div>
</nav>
