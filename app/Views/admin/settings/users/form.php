<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
$action = ($mode === 'edit')
  ? base_url('admin/settings/users/'.$user['id'])
  : base_url('admin/settings/users');

$barangayRoles = ['brgy_captain','brgy_secretary','bhw'];
$currentType = old('user_type', $user['user_type'] ?? '');
?>

<div class="card">
  <div class="card-header fw-semibold"><?= esc($pageTitle) ?></div>
  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Username</label>
          <input class="form-control" name="username" value="<?= esc(old('username', $user['username'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input class="form-control" type="email" name="email" value="<?= esc(old('email', $user['email'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">
            Password <?= ($mode === 'edit') ? '<span class="text-muted">(leave blank to keep)</span>' : '' ?>
          </label>
          <input class="form-control" type="password" name="password" <?= ($mode === 'create') ? 'required' : '' ?>>
        </div>

        <div class="col-md-4">
          <label class="form-label">First Name</label>
          <input class="form-control" name="first_name" value="<?= esc(old('first_name', $user['first_name'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Middle Name</label>
          <input class="form-control" name="middle_name" value="<?= esc(old('middle_name', $user['middle_name'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Last Name</label>
          <input class="form-control" name="last_name" value="<?= esc(old('last_name', $user['last_name'] ?? '')) ?>" required>
        </div>

        <div class="col-md-4">
          <label class="form-label">Contact #</label>
          <input class="form-control" name="contact_no" value="<?= esc(old('contact_no', $user['contact_no'] ?? '')) ?>">
        </div>

        <div class="col-md-8">
          <label class="form-label">Address Line</label>
          <input class="form-control" name="address_line" value="<?= esc(old('address_line', $user['address_line'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Postal Code</label>
          <input class="form-control" name="postal_code" value="<?= esc(old('postal_code', $user['postal_code'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">User Type</label>
          <select class="form-select" name="user_type" id="userType" required>
            <option value="">— Select —</option>
            <?php foreach (['super_admin','admin','staff','brgy_captain','brgy_secretary','bhw'] as $t): ?>
              <option value="<?= $t ?>" <?= ($currentType === $t) ? 'selected' : '' ?>>
                <?= esc($t) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4" id="barangayWrap" style="display:none;">
          <label class="form-label">Barangay (required for barangay users)</label>
          <select class="form-select" name="barangay_pcode" id="barangaySelect">
            <option value="">— Select Barangay —</option>
          </select>
          <div class="small text-muted">Auto loaded from Del Carmen barangays.</div>
        </div>

        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary">Save</button>
          <a href="<?= base_url('admin/settings/users') ?>" class="btn btn-light">Cancel</a>
        </div>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function(){
  const barangayRoles = <?= json_encode($barangayRoles) ?>;

  const defaults = <?= json_encode($defaults ?? []) ?>;
  const municipalityPcode = defaults.municipality_pcode || null;

  const userType = document.getElementById('userType');
  const barangayWrap = document.getElementById('barangayWrap');
  const barangaySelect = document.getElementById('barangaySelect');

  const LIST_URL = "<?= base_url('admin/settings/locations/list') ?>";

  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    if (!res.ok) throw new Error('Request failed');
    return await res.json();
  }

  function toggleBarangay() {
    const t = userType.value;
    const show = barangayRoles.includes(t);
    barangayWrap.style.display = show ? '' : 'none';

    if (!show) {
      barangaySelect.value = '';
    }
  }

  async function loadBarangays() {
    if (!municipalityPcode) return;

    // Barangays are level=4; parent is municipality PCODE? (In our admin_areas model, barangay parent is ADM3_PCODE)
    const url = LIST_URL + '?level=4&parent=' + encodeURIComponent(municipalityPcode);
    const rows = await fetchJSON(url);

    barangaySelect.innerHTML = '<option value="">— Select Barangay —</option>';
    rows.forEach(r => {
      const opt = document.createElement('option');
      opt.value = r.pcode;
      opt.textContent = r.name;
      barangaySelect.appendChild(opt);
    });

    // keep old value on edit/validation error
    const oldVal = "<?= esc(old('barangay_pcode', $user['barangay_pcode'] ?? '')) ?>";
    if (oldVal) barangaySelect.value = oldVal;
  }

  userType.addEventListener('change', toggleBarangay);

  (async function init(){
    toggleBarangay();
    await loadBarangays();
  })();
})();
</script>
<?= $this->endSection() ?>