<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
  <div class="sidebar-brand">
    <a href="<?= base_url('admin/dashboard') ?>" class="brand-link">
      <span class="brand-text fw-light">Digital Health</span>
    </a>
  </div>

  <div class="sidebar-wrapper">
    <nav class="mt-2">
      <ul class="nav sidebar-menu flex-column" role="menu">

        <li class="nav-item">
          <a href="<?= base_url('admin/dashboard') ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-gauge-high"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <li class="nav-header">REGISTRY</li>

        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fa-solid fa-house"></i>
            <p>Households</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fa-solid fa-map-location-dot"></i>
            <p>Map</p>
          </a>
        </li>

        <li class="nav-header">SETTINGS</li>

        <li class="nav-item">
          <a href="#" class="nav-link">
            <i class="nav-icon fa-solid fa-gear"></i>
            <p>System Settings</p>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</aside>
