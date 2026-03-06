<?php
  $menuService = service('moduleMenu');
  $moduleMenus = $menuService->getMenus();

  $authUser = session('auth_user') ?? [];
  $userType = (string)($authUser['user_type'] ?? '');

  $isSuperAdmin = ($userType === 'super_admin');
  $isAdmin = ($userType === 'admin');
  $isStaff = ($userType === 'staff');
  $isBhw = ($userType === 'bhw');
  $isBarangayCaptain = ($userType === 'barangay_captain');

  $canManageUsers = in_array($userType, ['super_admin', 'admin', 'staff'], true);
  $canViewMap = in_array($userType, ['super_admin', 'admin', 'staff', 'bhw', 'barangay_captain'], true);
  $canViewSettingsBlock = $isSuperAdmin || $canManageUsers;
?>

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
          <a href="<?= base_url('admin/registry/household-profiling') ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-people-roof"></i>
            <p>Household Profiling</p>
          </a>
        </li>

        <?php if ($canViewMap): ?>
          <li class="nav-item">
            <a href="<?= base_url('admin/registry/household-map') ?>" class="nav-link">
              <i class="nav-icon fa-solid fa-map-location-dot"></i>
              <p>Map</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if (!empty($moduleMenus['primary']) && $isSuperAdmin): ?>
          <?php foreach ($moduleMenus['primary'] as $menu): ?>
            <li class="nav-item">
              <a href="<?= base_url(ltrim($menu['url'], '/')) ?>" class="nav-link">
                <i class="nav-icon <?= esc($menu['icon']) ?>"></i>
                <p><?= esc($menu['label']) ?></p>
              </a>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($canViewSettingsBlock): ?>
          <li class="nav-header">SETTINGS</li>
        <?php endif; ?>

        <?php if ($canManageUsers): ?>
          <li class="nav-item">
            <a href="<?= base_url('admin/settings/users') ?>" class="nav-link">
              <i class="nav-icon fa-solid fa-users"></i>
              <p>Users</p>
            </a>
          </li>
        <?php endif; ?>

        <?php if ($isSuperAdmin): ?>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon fa-solid fa-gear"></i>
              <p>System Settings</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= base_url('admin/settings/locations') ?>" class="nav-link">
              <i class="nav-icon fa-solid fa-map"></i>
              <p>Locations</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="<?= base_url('admin/settings/modules') ?>" class="nav-link">
              <i class="nav-icon fa-solid fa-puzzle-piece"></i>
              <p>Modules</p>
            </a>
          </li>

          <?php if (!empty($moduleMenus['settings'])): ?>
            <?php foreach ($moduleMenus['settings'] as $menu): ?>
              <li class="nav-item">
                <a href="<?= base_url(ltrim($menu['url'], '/')) ?>" class="nav-link">
                  <i class="nav-icon <?= esc($menu['icon']) ?>"></i>
                  <p><?= esc($menu['label']) ?></p>
                </a>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        <?php endif; ?>

      </ul>
    </nav>
  </div>
</aside>