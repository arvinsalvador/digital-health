<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted">Households</div>
              <div class="fs-3 fw-semibold">0</div>
            </div>
            <i class="fa-solid fa-house fa-2x"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <div class="text-muted">Pinned Locations</div>
              <div class="fs-3 fw-semibold">0</div>
            </div>
            <i class="fa-solid fa-location-dot fa-2x"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
<?= $this->endSection() ?>
