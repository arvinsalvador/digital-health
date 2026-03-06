<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
  integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
  crossorigin=""
/>

<style>
  #householdMap {
    height: 72vh;
    min-height: 520px;
    width: 100%;
    border-radius: .5rem;
  }

  .hh-popup dt {
    font-weight: 600;
    margin-bottom: .15rem;
  }

  .hh-popup dd {
    margin-bottom: .45rem;
  }
</style>

<?php $markerJson = json_encode($markers, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <div class="fw-semibold">Household Map</div>
      <div class="small text-muted">Showing households with saved map coordinates.</div>
    </div>
    <span class="badge text-bg-primary"><?= (int) $markerCount ?> mapped household<?= (int) $markerCount !== 1 ? 's' : '' ?></span>
  </div>

  <div class="card-body">
    <?php if (empty($markers)): ?>
      <div class="alert alert-info mb-3">
        No mapped households found yet. Save a household location first in the profiling form so it can appear here.
      </div>
    <?php endif; ?>

    <div id="householdMap"></div>
  </div>
</div>

<script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
  integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
  crossorigin=""
></script>
<script>
  (() => {
    const markers = <?= $markerJson ?: '[]' ?>;
    const defaultCenter = [9.8752, 125.9681];

    const map = L.map('householdMap', {
      zoomControl: true,
      scrollWheelZoom: true
    }).setView(defaultCenter, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const bounds = [];

    const escapeHtml = (value) => {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    };

    const popupHtml = (item) => {
      return `
        <div class="hh-popup">
          <dl class="mb-2">
            <dt>Household #:</dt>
            <dd>${escapeHtml(item.household_no)}</dd>

            <dt>Name of Respondent:</dt>
            <dd>${escapeHtml(item.respondent_name)}</dd>

            <dt># of Family Groups:</dt>
            <dd>${escapeHtml(item.family_group_count)}</dd>

            <dt>Ethnicity:</dt>
            <dd>${escapeHtml(item.ethnicity)}</dd>

            <dt>Socioeconomic Status:</dt>
            <dd>${escapeHtml(item.socioeconomic_status)}</dd>

            <dt>Water Source:</dt>
            <dd>${escapeHtml(item.water_source)}</dd>

            <dt>Toilet Type:</dt>
            <dd>${escapeHtml(item.toilet_type)}</dd>
          </dl>

          <div class="small text-muted mb-2">
            ${escapeHtml(item.municipality_name)} - ${escapeHtml(item.barangay_name)}
            ${item.sitio_purok ? ' / ' + escapeHtml(item.sitio_purok) : ''}
          </div>

          <a href="${escapeHtml(item.edit_url)}" class="btn btn-sm btn-outline-primary">Open Profiling</a>
        </div>
      `;
    };

    markers.forEach((item) => {
      if (typeof item.lat !== 'number' || typeof item.lng !== 'number') {
        return;
      }

      bounds.push([item.lat, item.lng]);

      L.marker([item.lat, item.lng])
        .addTo(map)
        .bindPopup(popupHtml(item), { maxWidth: 320 });
    });

    if (bounds.length === 1) {
      map.setView(bounds[0], 17);
    } else if (bounds.length > 1) {
      map.fitBounds(bounds, { padding: [30, 30] });
    }

    setTimeout(() => map.invalidateSize(), 150);
  })();
</script>

<?= $this->endSection() ?>
