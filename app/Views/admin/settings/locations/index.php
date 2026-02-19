<?= $this->extend('layouts/admin') ?>

<?= $this->section('content') ?>

<div class="card">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs" id="locTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" data-level="1" id="tab-regions" data-bs-toggle="tab" data-bs-target="#pane-regions" type="button" role="tab">
          Regions
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-level="2" id="tab-provinces" data-bs-toggle="tab" data-bs-target="#pane-provinces" type="button" role="tab">
          Provinces
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-level="3" id="tab-municipalities" data-bs-toggle="tab" data-bs-target="#pane-municipalities" type="button" role="tab">
          Municipalities
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" data-level="4" id="tab-barangays" data-bs-toggle="tab" data-bs-target="#pane-barangays" type="button" role="tab">
          Barangays
        </button>
      </li>
    </ul>
  </div>

  <div class="card-body">
    <!-- Filters -->
    <div class="row g-2 align-items-end mb-3">
      <div class="col-md-4">
        <label class="form-label mb-1">Region</label>
        <select id="filterRegion" class="form-select">
          <option value="">— Select Region —</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1">Province</label>
        <select id="filterProvince" class="form-select" disabled>
          <option value="">— Select Province —</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label mb-1">Municipality</label>
        <select id="filterMunicipality" class="form-select" disabled>
          <option value="">— Select Municipality —</option>
        </select>
      </div>

      <div class="col-12">
        <small class="text-muted">
          Tip: Filters depend on the active tab. (Provinces require Region, Municipalities require Province, Barangays require Municipality)
        </small>
      </div>
    </div>

    <!-- Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-responsive">
      <table class="table table-bordered align-middle" id="locTable">
        <thead>
          <tr>
            <th style="width: 40%;">Name</th>
            <th style="width: 20%;">PCODE</th>
            <th style="width: 15%;">Status</th>
            <th style="width: 25%;">Actions</th>
          </tr>
        </thead>
        <tbody id="locTbody">
          <tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" id="renameForm">
      <div class="modal-header">
        <h5 class="modal-title">Rename Location</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" id="renamePcode" value="">
        <div class="mb-2">
          <label class="form-label">New name</label>
          <input type="text" class="form-control" id="renameName" required>
        </div>
        <div class="small text-muted">
          PCODE is permanent. Only the display name changes.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
(function() {
  const LIST_URL   = "<?= base_url('admin/settings/locations/list') ?>";
  const TOGGLE_URL = "<?= base_url('admin/settings/locations/toggle') ?>";
  const RENAME_URL = "<?= base_url('admin/settings/locations/rename') ?>";
  const CSRF_NAME  = "<?= csrf_token() ?>";
  const CSRF_HASH  = "<?= csrf_hash() ?>";

  let activeLevel = 1;

  const elRegion = document.getElementById('filterRegion');
  const elProv   = document.getElementById('filterProvince');
  const elMun    = document.getElementById('filterMunicipality');
  const tbody    = document.getElementById('locTbody');

  const renameModalEl = document.getElementById('renameModal');
  const renameModal = new bootstrap.Modal(renameModalEl);
  const renameForm = document.getElementById('renameForm');
  const renamePcode = document.getElementById('renamePcode');
  const renameName = document.getElementById('renameName');

  function qs(params) {
    const p = new URLSearchParams(params);
    return p.toString();
  }

  async function fetchJSON(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' }});
    if (!res.ok) throw new Error('Request failed');
    return await res.json();
  }

  async function postJSON(url, bodyObj) {
    const form = new URLSearchParams();
    Object.keys(bodyObj || {}).forEach(k => form.append(k, bodyObj[k]));
    // CSRF (for CI4)
    form.append(CSRF_NAME, CSRF_HASH);

    const res = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: form.toString()
    });
    if (!res.ok) throw new Error('POST failed');
    return await res.json();
  }

  function setFilterAvailability() {
    // Regions tab: no filters needed
    if (activeLevel === 1) {
      elRegion.disabled = true;
      elProv.disabled = true;
      elMun.disabled = true;
      return;
    }

    // Provinces tab: needs Region
    if (activeLevel === 2) {
      elRegion.disabled = false;
      elProv.disabled = true;
      elMun.disabled = true;
      return;
    }

    // Municipalities tab: needs Province (and Region for cascading)
    if (activeLevel === 3) {
      elRegion.disabled = false;
      elProv.disabled = false;
      elMun.disabled = true;
      return;
    }

    // Barangays tab: needs Municipality (and higher for cascading)
    if (activeLevel === 4) {
      elRegion.disabled = false;
      elProv.disabled = false;
      elMun.disabled = false;
      return;
    }
  }

  function clearSelect(selectEl, placeholder) {
    selectEl.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = placeholder;
    selectEl.appendChild(opt);
  }

  function fillSelect(selectEl, items) {
    items.forEach(row => {
      const opt = document.createElement('option');
      opt.value = row.pcode;
      opt.textContent = row.name;
      selectEl.appendChild(opt);
    });
  }

  async function loadRegions() {
    clearSelect(elRegion, '— Select Region —');
    const data = await fetchJSON(LIST_URL + '?' + qs({ level: 1 }));
    fillSelect(elRegion, data);
  }

  async function loadProvinces(regionPcode) {
    clearSelect(elProv, '— Select Province —');
    clearSelect(elMun, '— Select Municipality —');
    elProv.disabled = true;
    elMun.disabled = true;

    if (!regionPcode) return;

    const data = await fetchJSON(LIST_URL + '?' + qs({ level: 2, parent: regionPcode }));
    fillSelect(elProv, data);
    elProv.disabled = false;
  }

  async function loadMunicipalities(provincePcode) {
    clearSelect(elMun, '— Select Municipality —');
    elMun.disabled = true;

    if (!provincePcode) return;

    const data = await fetchJSON(LIST_URL + '?' + qs({ level: 3, parent: provincePcode }));
    fillSelect(elMun, data);
    elMun.disabled = false;
  }

  function badge(isActive) {
    return isActive
      ? '<span class="badge bg-success">Enabled</span>'
      : '<span class="badge bg-secondary">Disabled</span>';
  }

  function renderRows(rows) {
    if (!rows || rows.length === 0) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No records found.</td></tr>';
      return;
    }

    tbody.innerHTML = rows.map(r => {
      const toggleLabel = r.is_active ? 'Disable' : 'Enable';
      const toggleClass = r.is_active ? 'btn-outline-secondary' : 'btn-outline-success';

      return `
        <tr>
          <td>${escapeHtml(r.name)}</td>
          <td><code>${escapeHtml(r.pcode)}</code></td>
          <td>${badge(r.is_active)}</td>
          <td class="d-flex gap-2">
            <button class="btn btn-sm ${toggleClass}" data-action="toggle" data-pcode="${escapeHtml(r.pcode)}">
              ${toggleLabel}
            </button>
            <button class="btn btn-sm btn-outline-primary" data-action="rename" data-pcode="${escapeHtml(r.pcode)}" data-name="${escapeHtml(r.name)}">
              Rename
            </button>
          </td>
        </tr>
      `;
    }).join('');
  }

  async function loadTable() {
    tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Loading…</td></tr>';

    // Determine parent based on tab
    let parent = '';

    if (activeLevel === 2) parent = elRegion.value;
    if (activeLevel === 3) parent = elProv.value;
    if (activeLevel === 4) parent = elMun.value;

    // Validate required filters
    if (activeLevel === 2 && !parent) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Select a Region to view Provinces.</td></tr>';
      return;
    }
    if (activeLevel === 3 && !parent) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Select a Province to view Municipalities.</td></tr>';
      return;
    }
    if (activeLevel === 4 && !parent) {
      tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Select a Municipality to view Barangays.</td></tr>';
      return;
    }

    const params = { level: activeLevel };
    if (parent) params.parent = parent;

    const rows = await fetchJSON(LIST_URL + '?' + qs(params));
    renderRows(rows);
  }

  // Actions (toggle/rename)
  document.getElementById('locTable').addEventListener('click', async (e) => {
    const btn = e.target.closest('button[data-action]');
    if (!btn) return;

    const action = btn.dataset.action;
    const pcode  = btn.dataset.pcode;

    try {
      if (action === 'toggle') {
        btn.disabled = true;
        await postJSON(`${TOGGLE_URL}/${encodeURIComponent(pcode)}`, {});
        await loadTable();
      }

      if (action === 'rename') {
        renamePcode.value = pcode;
        renameName.value = btn.dataset.name || '';
        renameModal.show();
      }
    } catch (err) {
      alert('Action failed. Please try again.');
    } finally {
      btn.disabled = false;
    }
  });

  renameForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const pcode = renamePcode.value;
    const name  = renameName.value.trim();
    if (!name) return;

    try {
      await postJSON(`${RENAME_URL}/${encodeURIComponent(pcode)}`, { name });
      renameModal.hide();
      await loadTable();
    } catch (err) {
      alert('Rename failed. Please try again.');
    }
  });

  // Tab change
  document.querySelectorAll('#locTabs button[data-level]').forEach(btn => {
    btn.addEventListener('click', async () => {
      activeLevel = parseInt(btn.dataset.level, 10);
      setFilterAvailability();
      await loadTable();
    });
  });

  // Filter chain
  elRegion.addEventListener('change', async () => {
    await loadProvinces(elRegion.value);

    // If we are on Municipalities/Barangays tabs, table depends on deeper selection
    if (activeLevel === 2) await loadTable();

    if (activeLevel >= 3) {
      // clear municipality list (handled in loadProvinces)
      if (activeLevel === 3) await loadTable(); // will prompt select Province
      if (activeLevel === 4) await loadTable(); // will prompt select Municipality
    }
  });

  elProv.addEventListener('change', async () => {
    await loadMunicipalities(elProv.value);

    if (activeLevel === 3) await loadTable();
    if (activeLevel === 4) await loadTable(); // will prompt select Municipality
  });

  elMun.addEventListener('change', async () => {
    if (activeLevel === 4) await loadTable();
  });

  function escapeHtml(str) {
    return String(str ?? '')
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'",'&#039;');
  }

  // Init
  (async function init() {
    // Regions filter is only needed for tabs >= 2, but we preload it.
    await loadRegions();
    setFilterAvailability();
    await loadTable();
  })();

})();
</script>
<?= $this->endSection() ?>
