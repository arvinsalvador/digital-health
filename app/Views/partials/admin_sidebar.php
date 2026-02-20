<?php
  $menuService = service('moduleMenu');
  $moduleMenus = $menuService->getMenus();
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

        <!-- ================= DASHBOARD ================= -->
        <li class="nav-item">
          <a href="<?= base_url('admin/dashboard') ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-gauge-high"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- ================= REGISTRY ================= -->
        <li class="nav-header">REGISTRY</li>

        <!-- STATIC (core) menus -->
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

        <!-- ================= MODULE PRIMARY MENUS ================= -->
        <?php if (!empty($moduleMenus['primary'])): ?>
            <?php foreach ($moduleMenus['primary'] as $menu): ?>
                <li class="nav-item">
                    <a href="<?= base_url(ltrim($menu['url'],'/')) ?>" class="nav-link">
                        <i class="nav-icon <?= esc($menu['icon']) ?>"></i>
                        <p><?= esc($menu['label']) ?></p>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>


        <!-- ================= SETTINGS ================= -->
        <li class="nav-header">SETTINGS</li>

        <!-- Core settings -->
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
          <a href="<?= base_url('admin/settings/users') ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-users"></i>
            <p>Users</p>
          </a>
        </li>

        <!-- Modules Manager -->
        <li class="nav-item">
          <a href="<?= base_url('admin/settings/modules') ?>" class="nav-link">
            <i class="nav-icon fa-solid fa-puzzle-piece"></i>
            <p>Modules</p>
          </a>
        </li>

        <!-- ================= MODULE SETTINGS MENUS ================= -->
        <?php if (!empty($moduleMenus['settings'])): ?>
            <?php foreach ($moduleMenus['settings'] as $menu): ?>
                <li class="nav-item">
                    <a href="<?= base_url(ltrim($menu['url'],'/')) ?>" class="nav-link">
                        <i class="nav-icon <?= esc($menu['icon']) ?>"></i>
                        <p><?= esc($menu['label']) ?></p>
                    </a>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

      </ul>
    </nav>
  </div>
</aside>
