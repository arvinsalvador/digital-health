<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
// Defaults
$mode = $mode ?? 'create';
$user = $user ?? null;

$action = ($mode === 'edit' && $user)
  ? base_url('admin/settings/users/'.$user['id'])
  : base_url('admin/settings/users');

$allowedUserTypes = $allowedUserTypes ?? ['super_admin','admin','staff','brgy_captain','brgy_secretary','bhw'];

// Location lock structure (from controller)
$lock = $lock ?? [
  'region_locked' => false,
  'province_locked' => false,
  'municipality_locked' => false,
  'barangay_locked' => false,
  'region_pcode' => null,
  'province_pcode' => null,
  'municipality_pcode' => null,
  'barangay_pcode' => null,
];

// Selected values (old() first, then user record, then lock defaults)
$selUserType = old('user_type', $user['user_type'] ?? '');

$selRegion = old('region_pcode', $user['region_pcode'] ?? ($lock['region_pcode'] ?? ''));
$selProvince = old('province_pcode', $user['province_pcode'] ?? ($lock['province_pcode'] ?? ''));
$selMunicipality = old('municipality_pcode', $user['municipality_pcode'] ?? ($lock['municipality_pcode'] ?? ''));
$selBarangay = old('barangay_pcode', $user['barangay_pcode'] ?? ($lock['barangay_pcode'] ?? ''));
?>

<div class="card">
  <div class="card-header fw-semibold"><?= esc($pageTitle ?? 'User Form') ?></div>

  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>

      <div class="row g-3">

        <!-- Username / Email / Password -->
        <div class="col-md-4">
          <label class="form-label">Username</label>
          <input class="form-control"
                 name="username"
                 value="<?= esc(old('username', $user['username'] ?? '')) ?>"
                 required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input class="form-control"
                 type="email"
                 name="email"
                 value="<?= esc(old('email', $user['email'] ?? '')) ?>"
                 required>
        </div>

        <div class="col-md-4">
          <label class="form-label">
            Password
            <?php if ($mode === 'edit'): ?>
              <span class="text-muted">(leave blank to keep current)</span>
            <?php endif; ?>
          </label>
          <input class="form-control"
                 type="password"
                 name="password"
                 <?= ($mode === 'create') ? 'required' : '' ?>>
          <?php if ($mode === 'create'): ?>
            <div class="form-text">Minimum 6 characters.</div>
          <?php endif; ?>
        </div>

        <!-- Names -->
        <div class="col-md-4">
          <label class="form-label">First Name</label>
          <input class="form-control"
                 name="first_name"
                 value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>"
                 required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Middle Name <span class="text-muted">(optional)</span></label>
          <input class="form-control"
                 name="middle_name"
                 value="<?= esc(old('middle_name', $user['middle_name'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Last Name</label>
          <input class="form-control"
                 name="last_name"
                 value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>"
                 required>
        </div>

        <!-- Contact / Address -->
        <div class="col-md-4">
          <label class="form-label">Contact #</label>
          <input class="form-control"
                 name="contact_no"
                 value="<?= esc(old('contact_no', $user['contact_no'] ?? '')) ?>">
        </div>

        <div class="col-md-8">
          <label class="form-label">Address Line</label>
          <input class="form-control"
                 name="address_line"
                 value="<?= esc(old('address_line', $user['address_line'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Postal Code</label>
          <input class="form-control"
                 name="postal_code"
                 value="<?= esc(old('postal_code', $user['postal_code'] ?? '')) ?>">
        </div>

        <!-- User Type -->
        <div class="col-md-4">
          <label class="form-label">User Type</label>
          <select class="form-select" name="user_type" id="userType" required>
            <option value="">— Select User Type —</option>
            <?php foreach ($allowedUserTypes as $t): ?>
              <option value="<?= esc($t) ?>" <?= ($selUserType === $t) ? 'selected' : '' ?>>
                <?= esc($t) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            Options are limited based on your account level.
          </div>
        </div>

      </div>

      <hr class="my-4">

      <h5 class="mb-3">Location Assignment</h5>

      <div class="row g-3">

        <!-- Region -->
        <div class="col-md-3">
          <label class="form-label">Region</label>
          <select class="form-select"
                  id="region"
                  name="region_pcode"
                  <?= $lock['region_locked'] ? 'disabled' : '' ?>>
            <option value="">— Select Region —</option>
          </select>

          <?php if ($lock['region_locked']): ?>
            <input type="hidden" name="region_pcode" value="<?= esc($selRegion) ?>">
          <?php endif; ?>
        </div>

        <!-- Province -->
        <div class="col-md-3">
          <label class="form-label">Province</label>
          <select class="form-select"
                  id="province"
                  name="province_pcode"
                  <?= $lock['province_locked'] ? 'disabled' : '' ?>>
            <option value="">— Select Province —</option>
          </select>

          <?php if ($lock['province_locked']): ?>
            <input type="hidden" name="province_pcode" value="<?= esc($selProvince) ?>">
          <?php endif; ?>
        </div>

        <!-- Municipality -->
        <div class="col-md-3">
          <label class="form-label">Municipality</label>
          <select class="form-select"
                  id="municipality"
                  name="municipality_pcode"
                  <?= $lock['municipality_locked'] ? 'disabled' : '' ?>>
            <option value="">— Select Municipality —</option>
          </select>

          <?php if ($lock['municipality_locked']): ?>
            <input type="hidden" name="municipality_pcode" value="<?= esc($selMunicipality) ?>">
          <?php endif; ?>
        </div>

        <!-- Barangay -->
        <div class="col-md-3">
          <label class="form-label">Barangay</label>
          <select class="form-select"
                  id="barangay"
                  name="barangay_pcode"
                  <?= $lock['barangay_locked'] ? 'disabled' : '' ?>>
            <option value="">— Select Barangay —</option>
          </select>

          <?php if ($lock['barangay_locked']): ?>
            <input type="hidden" name="barangay_pcode" value="<?= esc($selBarangay) ?>">
          <?php endif; ?>

          <div class="form-text" id="brgyHint" style="display:none;">
            Barangay is required for barangay-level user types.
          </div>
        </div>

      </div>

      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">Save</button>
        <a href="<?= base_url('admin/settings/users') ?>" class="btn btn-light">Cancel</a>
      </div>

    </form>
  </div>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
(function(){
  const LIST_URL = "<?= base_url('admin/settings/locations/list') ?>";

  const lock = <?= json_encode($lock) ?>;

  const regionEl = document.getElementById('region');
  const provEl = document.getElementById('province');
  const munEl = document.getElementById('municipality');
  const brgyEl = document.getElementById('barangay');

  const userTypeEl = document.getElementById('userType');
  const brgyHintEl = document.getElementById('brgyHint');

  const selected = {
    region: "<?= esc($selRegion) ?>",
    province: "<?= esc($selProvince) ?>",
    municipality: "<?= esc($selMunicipality) ?>",
    barangay: "<?= esc($selBarangay) ?>"
  };

  // User types that require barangay
  const barangayTypes = ['brgy_captain','brgy_secretary','bhw'];

  function updateBarangayHint(){
    const t = (userTypeEl.value || '').trim();
    brgyHintEl.style.display = barangayTypes.includes(t) ? '' : 'none';
  }

  async function fetchJSON(url){
    const res = await fetch(url, { headers: { 'Accept':'application/json' }});
    if (!res.ok) throw new Error('Request failed');
    return await res.json();
  }

  function fillSelect(selectEl, rows, placeholder){
    selectEl.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    selectEl.appendChild(opt);

    (rows || []).forEach(r => {
      const o = document.createElement('option');
      o.value = r.pcode;
      o.textContent = r.name;
      selectEl.appendChild(o);
    });
  }

  async function loadRegions(){
    const rows = await fetchJSON(`${LIST_URL}?level=1`);
    fillSelect(regionEl, rows, '— Select Region —');
    if (selected.region) regionEl.value = selected.region;
  }

  async function loadProvinces(regionPcode){
    if (!regionPcode){
      fillSelect(provEl, [], '— Select Province —');
      fillSelect(munEl, [], '— Select Municipality —');
      fillSelect(brgyEl, [], '— Select Barangay —');
      return;
    }
    const rows = await fetchJSON(`${LIST_URL}?level=2&parent=${encodeURIComponent(regionPcode)}`);
    fillSelect(provEl, rows, '— Select Province —');
    if (selected.province) provEl.value = selected.province;
  }

  async function loadMunicipalities(provPcode){
    if (!provPcode){
      fillSelect(munEl, [], '— Select Municipality —');
      fillSelect(brgyEl, [], '— Select Barangay —');
      return;
    }
    const rows = await fetchJSON(`${LIST_URL}?level=3&parent=${encodeURIComponent(provPcode)}`);
    fillSelect(munEl, rows, '— Select Municipality —');
    if (selected.municipality) munEl.value = selected.municipality;
  }

  async function loadBarangays(munPcode){
    if (!munPcode){
      fillSelect(brgyEl, [], '— Select Barangay —');
      return;
    }
    const rows = await fetchJSON(`${LIST_URL}?level=4&parent=${encodeURIComponent(munPcode)}`);
    fillSelect(brgyEl, rows, '— Select Barangay —');
    if (selected.barangay) brgyEl.value = selected.barangay;
  }

  regionEl.addEventListener('change', async () => {
    selected.region = regionEl.value;
    selected.province = '';
    selected.municipality = '';
    selected.barangay = '';
    await loadProvinces(regionEl.value);
    fillSelect(munEl, [], '— Select Municipality —');
    fillSelect(brgyEl, [], '— Select Barangay —');
  });

  provEl.addEventListener('change', async () => {
    selected.province = provEl.value;
    selected.municipality = '';
    selected.barangay = '';
    await loadMunicipalities(provEl.value);
    fillSelect(brgyEl, [], '— Select Barangay —');
  });

  munEl.addEventListener('change', async () => {
    selected.municipality = munEl.value;
    selected.barangay = '';
    await loadBarangays(munEl.value);
  });

  userTypeEl.addEventListener('change', updateBarangayHint);

  (async function init(){
    updateBarangayHint();

    // If locked values exist, we still load full lists so the UI shows the selected label.
    await loadRegions();

    const regionVal = lock.region_locked ? (lock.region_pcode || selected.region) : regionEl.value;
    await loadProvinces(regionVal);

    const provVal = lock.province_locked ? (lock.province_pcode || selected.province) : provEl.value;
    await loadMunicipalities(provVal);

    const munVal = lock.municipality_locked ? (lock.municipality_pcode || selected.municipality) : munEl.value;
    await loadBarangays(munVal);
  })();

})();
</script>
<?= $this->endSection() ?>