<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
$mode  = $mode ?? 'create';
$visit = $visit ?? null;
$groups = $groups ?? [];
$actor = $actor ?? [];
$lock  = $lock ?? ['municipality_locked'=>false,'barangay_locked'=>false,'municipality_pcode'=>'','barangay_pcode'=>''];

$oldVisitDate = old('visit_date', $visit['visit_date'] ?? '');
$oldQuarter   = old('visit_quarter', !empty($visit['visit_quarter']) ? ('Q'.$visit['visit_quarter']) : '');

$oldBarangay  = old('barangay_pcode', $visit['barangay_pcode'] ?? ($lock['barangay_pcode'] ?? ''));
$oldMunicipal = old('municipality_pcode', $visit['municipality_pcode'] ?? ($lock['municipality_pcode'] ?? ''));

$oldSitio     = old('sitio_purok', $visit['sitio_purok'] ?? '');
$oldHouseNo   = old('household_no', $visit['household_no'] ?? '');

$oldRespLN = old('respondent_last_name', $visit['respondent_last_name'] ?? '');
$oldRespFN = old('respondent_first_name', $visit['respondent_first_name'] ?? '');
$oldRespMN = old('respondent_middle_name', $visit['respondent_middle_name'] ?? '');
$oldRespRel = old('respondent_relation', $visit['respondent_relation'] ?? '');
$oldRespRelOther = old('respondent_relation_other', $visit['respondent_relation_other'] ?? '');

$ethMode = old('ethnicity_mode', $visit['ethnicity_mode'] ?? 'ip');
$ethTribe = old('ethnicity_tribe', $visit['ethnicity_tribe'] ?? '');

$socio = old('socioeconomic_status', $visit['socioeconomic_status'] ?? '');
$nhtsNo = old('nhts_no', $visit['nhts_no'] ?? '');

$water = old('water_source', $visit['water_source'] ?? '');
$waterOther = old('water_source_other', $visit['water_source_other'] ?? '');

$toilet = old('toilet_facility', $visit['toilet_facility'] ?? '');
$remarks = old('remarks', $visit['remarks'] ?? '');

$actorType = (string)($actor['user_type'] ?? '');
$isSuperAdmin = ($actorType === 'super_admin');
$lockedMunicipality = (string)($actor['municipality_pcode'] ?? $oldMunicipal);

// Seed groups for JS
$seedGroups = [];
if (!empty($groups)) {
    foreach ($groups as $g) {
        $seedMembers = [];

        foreach (($g['members'] ?? []) as $m) {
            $seedMembers[] = [
                'id' => $m['id'] ?? '',
                'linked_member_id' => $m['linked_member_id'] ?? '',
                'local_last_name' => $m['local_last_name'] ?? '',
                'local_first_name' => $m['local_first_name'] ?? '',
                'local_middle_name' => $m['local_middle_name'] ?? '',
                'relationship_code' => $m['relationship_code'] ?? '',
                'relationship_other' => $m['relationship_other'] ?? '',
                'sex' => $m['sex'] ?? '',
                'dob' => $m['dob'] ?? '',
                'civil_status' => $m['civil_status'] ?? '',
                'philhealth_id' => $m['philhealth_id'] ?? '',
                'membership_type' => $m['membership_type'] ?? '',
                'philhealth_category' => $m['philhealth_category'] ?? '',
                'medical_history' => $m['medical_history_arr'] ?? [],
                'lmp_date' => $m['lmp_date'] ?? '',
                'educ_attainment' => $m['educ_attainment'] ?? '',
                'religion' => $m['religion'] ?? '',
                'status_in_household' => $m['status_in_household'] ?? '',
                'stay_from' => $m['stay_from'] ?? '',
                'stay_to' => $m['stay_to'] ?? '',
                'remarks' => $m['remarks'] ?? '',
                'q1_age' => $m['q1_age'] ?? '',
                'q1_class' => $m['q1_class'] ?? '',
                'q2_age' => $m['q2_age'] ?? '',
                'q2_class' => $m['q2_class'] ?? '',
                'q3_age' => $m['q3_age'] ?? '',
                'q3_class' => $m['q3_class'] ?? '',
                'q4_age' => $m['q4_age'] ?? '',
                'q4_class' => $m['q4_class'] ?? '',
            ];
        }

        $seedGroups[] = [
            'id' => $g['id'] ?? '',
            'group_name' => $g['group_name'] ?? '',
            'living_status' => $g['living_status'] ?? '',
            'notes' => $g['notes'] ?? '',
            'members' => $seedMembers,
        ];
    }
}
?>

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <strong><?= esc($pageTitle ?? 'Household Profiling') ?></strong>
        <div class="text-muted small"><?= $mode === 'edit' ? 'Edit visit and family groups' : 'Create new visit and family groups' ?></div>
      </div>
      <a href="<?= base_url('admin/registry/household-profiling') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>
  </div>

  <div class="card-body">
    <form method="post" action="<?= $mode === 'edit' ? base_url('admin/registry/household-profiling/'.($visit['id'] ?? 0)) : base_url('admin/registry/household-profiling') ?>">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Date of Visit</label>
          <input type="date" class="form-control" name="visit_date" id="visit_date" value="<?= esc($oldVisitDate) ?>" required>
          <div class="form-text">Quarter auto-detect will display on the right.</div>
        </div>

        <div class="col-md-1">
          <label class="form-label">Quarter</label>
          <input class="form-control" name="visit_quarter" id="visit_quarter" value="<?= esc($oldQuarter) ?>" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Municipality</label>

          <?php if ($isSuperAdmin): ?>
            <select class="form-select" name="municipality_pcode" id="municipality_pcode" required>
              <option value="">Select</option>
            </select>
            <div class="form-text">Super Admin can select municipality.</div>
          <?php else: ?>
            <input class="form-control" id="municipality_pcode_display" value="<?= esc($lockedMunicipality) ?>" readonly>
            <input type="hidden" name="municipality_pcode" id="municipality_pcode" value="<?= esc($lockedMunicipality) ?>">
            <div class="form-text">Auto-selected based on your assigned municipality.</div>
          <?php endif; ?>
        </div>

        <div class="col-md-4">
          <label class="form-label">Barangay</label>
          <select class="form-select" name="barangay_pcode" id="barangay_pcode" <?= !empty($lock['barangay_locked']) ? 'disabled' : '' ?> required>
            <option value="">Loading...</option>
          </select>
          <?php if (!empty($lock['barangay_locked'])): ?>
            <input type="hidden" name="barangay_pcode" value="<?= esc($oldBarangay) ?>">
            <div class="form-text">Barangay is locked based on your assigned scope.</div>
          <?php else: ?>
            <div class="form-text">Select barangay.</div>
          <?php endif; ?>
        </div>

        <div class="col-md-3">
          <label class="form-label">Sitio / Purok</label>
          <input class="form-control" name="sitio_purok" value="<?= esc($oldSitio) ?>" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Household No.</label>
          <input class="form-control" name="household_no" value="<?= esc($oldHouseNo) ?>" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Name of Respondent</label>
          <div class="row g-2">
            <div class="col-lg-4 col-md-12">
              <input class="form-control" name="respondent_last_name" value="<?= esc($oldRespLN) ?>" placeholder="Last" required>
            </div>
            <div class="col-lg-4 col-md-12">
              <input class="form-control" name="respondent_first_name" value="<?= esc($oldRespFN) ?>" placeholder="First" required>
            </div>
            <div class="col-lg-4 col-md-12">
              <input class="form-control" name="respondent_middle_name" value="<?= esc($oldRespMN) ?>" placeholder="Middle (optional)">
            </div>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Relationship to Household Head</label>
          <select class="form-select" name="respondent_relation" id="resp_relation" required>
            <option value="">Select</option>
            <option value="head" <?= ($oldRespRel==='head')?'selected':'' ?>>Head</option>
            <option value="spouse" <?= ($oldRespRel==='spouse')?'selected':'' ?>>Spouse</option>
            <option value="child" <?= ($oldRespRel==='child')?'selected':'' ?>>Child</option>
            <option value="relative" <?= ($oldRespRel==='relative')?'selected':'' ?>>Relative</option>
            <option value="other" <?= ($oldRespRel==='other')?'selected':'' ?>>Other</option>
          </select>
          <div id="resp_relation_other_wrap" class="mt-2" style="display:none;">
            <input class="form-control" name="respondent_relation_other" id="resp_relation_other" value="<?= esc($oldRespRelOther) ?>" placeholder="Specify relationship">
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Ethnicity</label>
          <select class="form-select" name="ethnicity_mode" id="ethnicity_mode" required>
            <option value="ip" <?= ($ethMode==='ip')?'selected':'' ?>>IP Household</option>
            <option value="tribe" <?= ($ethMode==='tribe')?'selected':'' ?>>If IP Household, indicate Tribe</option>
          </select>
          <div id="tribe_wrap" class="mt-2" style="display:none;">
            <input class="form-control" name="ethnicity_tribe" value="<?= esc($ethTribe) ?>" placeholder="Tribe (e.g., Mamanwa)">
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Socioeconomic Status</label>
          <select class="form-select" name="socioeconomic_status" id="socioeconomic_status">
            <option value="">—</option>
            <option value="nhts" <?= ($socio==='nhts')?'selected':'' ?>>NHTS</option>
            <option value="nhts_4ps" <?= ($socio==='nhts_4ps')?'selected':'' ?>>NHTS + 4Ps</option>
            <option value="non_nhts" <?= ($socio==='non_nhts')?'selected':'' ?>>Non-NHTS</option>
          </select>
          <div id="nhts_wrap" class="mt-2" style="display:none;">
            <input class="form-control" name="nhts_no" value="<?= esc($nhtsNo) ?>" placeholder="NHTS No. (optional)">
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Water Source</label>
          <select class="form-select" name="water_source" id="water_source">
            <option value="">—</option>
            <option value="piped" <?= ($water==='piped')?'selected':'' ?>>Piped water</option>
            <option value="deep_well" <?= ($water==='deep_well')?'selected':'' ?>>Deep well</option>
            <option value="shallow_well" <?= ($water==='shallow_well')?'selected':'' ?>>Shallow well</option>
            <option value="spring" <?= ($water==='spring')?'selected':'' ?>>Spring</option>
            <option value="others" <?= ($water==='others')?'selected':'' ?>>Others</option>
          </select>
          <div id="water_other_wrap" class="mt-2" style="display:none;">
            <input class="form-control" name="water_source_other" value="<?= esc($waterOther) ?>" placeholder="Specify">
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">Toilet Type</label>
          <select class="form-select" name="toilet_facility">
            <option value="">—</option>
            <option value="A" <?= ($toilet==='A')?'selected':'' ?>>A - Water-sealed</option>
            <option value="B" <?= ($toilet==='B')?'selected':'' ?>>B - Pour flush</option>
            <option value="C" <?= ($toilet==='C')?'selected':'' ?>>C - Closed pit</option>
            <option value="D" <?= ($toilet==='D')?'selected':'' ?>>D - Open pit</option>
            <option value="E" <?= ($toilet==='E')?'selected':'' ?>>E - Shared</option>
            <option value="F" <?= ($toilet==='F')?'selected':'' ?>>F - Open pit latrine</option>
            <option value="G" <?= ($toilet==='G')?'selected':'' ?>>G - Without toilet</option>
          </select>
        </div>

        <div class="col-md-12">
          <label class="form-label">Remarks (optional)</label>
          <textarea class="form-control" name="remarks" rows="2"><?= esc($remarks) ?></textarea>
        </div>
      </div>

      <hr class="my-4">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Family Groups (Occupants in this Household)</h5>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addGroupBtn">
          <i class="fa-solid fa-plus"></i> Add Family Group
        </button>
      </div>

      <div id="groupsWrap"></div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a class="btn btn-outline-secondary" href="<?= base_url('admin/registry/household-profiling') ?>">Cancel</a>
        <button class="btn btn-primary"><?= $mode === 'edit' ? 'Update Visit' : 'Save Visit' ?></button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="linkMemberModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Link Existing Member</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label">Search by name (at least 2 characters)</label>
          <input type="text" class="form-control" id="linkSearchInput" placeholder="e.g., Dela Cruz, Juan">
          <div class="form-text">Results are limited by your scope.</div>
        </div>

        <div class="table-responsive">
          <table class="table table-sm table-bordered align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th style="width:90px;">Sex</th>
                <th style="width:130px;">DOB</th>
                <th>Location</th>
                <th style="width:110px;">Action</th>
              </tr>
            </thead>
            <tbody id="linkResultsBody">
              <tr><td colspan="5" class="text-muted">Type to search...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<style>
  .group-card { border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
  .group-card .group-head { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap; }
  .badge-linked { font-size: 12px; }
  .table-mini th, .table-mini td { vertical-align: top; }
  .member-actions { white-space: nowrap; }
  .w-110 { min-width: 110px; }
  .w-140 { min-width: 140px; }
  .w-180 { min-width: 180px; }
  .w-220 { min-width: 220px; }
  .qCell input { min-width: 88px; }
</style>

<script>
(function(){
  const isSuperAdmin = <?= json_encode($isSuperAdmin) ?>;
  const municipalitySelect = document.getElementById('municipality_pcode');
  const selectedMunicipality = <?= json_encode($oldMunicipal) ?>;
  const brgySelect = document.getElementById('barangay_pcode');
  const selectedBarangay = <?= json_encode($oldBarangay) ?>;

  function loadMunicipalities(){
    if (!isSuperAdmin) return;

    const province = <?= json_encode((string)($actor['province_pcode'] ?? '')) ?>;
    const url = `<?= base_url('admin/settings/locations/list') ?>?level=3&parent=${encodeURIComponent(province)}`;

    fetch(url)
      .then(r => r.json())
      .then(rows => {
        municipalitySelect.innerHTML = '<option value="">Select</option>';
        (rows || []).forEach(r => {
          const o = document.createElement('option');
          o.value = r.pcode;
          o.textContent = r.name;
          municipalitySelect.appendChild(o);
        });

        if (selectedMunicipality) municipalitySelect.value = selectedMunicipality;
        loadBarangays();
      })
      .catch(() => {
        municipalitySelect.innerHTML = '<option value="">Unable to load</option>';
      });
  }

  function loadBarangays(){
    const muni = (document.getElementById('municipality_pcode').value || '').trim();

    fetch(`<?= base_url('admin/settings/locations/list') ?>?level=4&parent=${encodeURIComponent(muni)}`)
      .then(r => r.json())
      .then(rows => {
        brgySelect.innerHTML = '<option value="">Select</option>';
        (rows || []).forEach(r => {
          const o = document.createElement('option');
          o.value = r.pcode;
          o.textContent = r.name;
          brgySelect.appendChild(o);
        });

        if (selectedBarangay) brgySelect.value = selectedBarangay;
      })
      .catch(() => {
        brgySelect.innerHTML = '<option value="">Unable to load</option>';
      });
  }

  if (isSuperAdmin && municipalitySelect) {
    municipalitySelect.addEventListener('change', () => {
      brgySelect.value = '';
      loadBarangays();
    });
  }

  if (isSuperAdmin) loadMunicipalities();
  else loadBarangays();

  function computeQuarter(dateStr){
    if (!dateStr) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
      const m = parseInt(dateStr.slice(5,7),10);
      return (m>=1 && m<=12) ? Math.ceil(m/3) : '';
    }
    const dt = new Date(dateStr);
    if (isNaN(dt.getTime())) return '';
    return Math.ceil((dt.getMonth()+1)/3);
  }

  function updateQuarter(){
    const d = document.getElementById('visit_date').value.trim();
    const q = computeQuarter(d);
    document.getElementById('visit_quarter').value = q ? ('Q' + q) : '';
  }

  document.getElementById('visit_date').addEventListener('change', updateQuarter);
  document.getElementById('visit_date').addEventListener('input', updateQuarter);
  updateQuarter();

  const respRel = document.getElementById('resp_relation');
  const respOtherWrap = document.getElementById('resp_relation_other_wrap');
  const ethMode = document.getElementById('ethnicity_mode');
  const tribeWrap = document.getElementById('tribe_wrap');
  const soc = document.getElementById('socioeconomic_status');
  const nhtsWrap = document.getElementById('nhts_wrap');
  const water = document.getElementById('water_source');
  const waterOtherWrap = document.getElementById('water_other_wrap');

  function toggleConditional(){
    respOtherWrap.style.display = (respRel.value === 'other') ? '' : 'none';
    tribeWrap.style.display = (ethMode.value === 'tribe') ? '' : 'none';
    nhtsWrap.style.display = String(soc.value || '').startsWith('nhts') ? '' : 'none';
    waterOtherWrap.style.display = (water.value === 'others') ? '' : 'none';
  }

  respRel.addEventListener('change', toggleConditional);
  ethMode.addEventListener('change', toggleConditional);
  soc.addEventListener('change', toggleConditional);
  water.addEventListener('change', toggleConditional);
  toggleConditional();

  const seedGroups = <?= json_encode($seedGroups) ?>;
  const groupsWrap = document.getElementById('groupsWrap');
  const addGroupBtn = document.getElementById('addGroupBtn');

  let linkTarget = { gIndex: null };

  const livingStatusOptions = [
    ['owner_occupant','Owner-Occupant'],
    ['renter_tenant','Renter / Tenant'],
    ['boarder_lodger','Boarder / Lodger'],
    ['temporary_stay','Temporary Stay'],
    ['relative_staying_in','Relative Staying In'],
    ['caretaker','Caretaker'],
    ['co_occupant','Sharer / Co-occupant'],
    ['other','Other'],
  ];

  const memberStatusOptions = [
    ['primary_resident','Primary Resident'],
    ['temporary_resident','Temporary Resident'],
    ['boarder','Boarder'],
    ['tenant','Tenant'],
    ['visitor','Visitor / Short-term'],
    ['unknown','Unknown / For Validation'],
  ];

  const educOptions = [
    ['','—'],
    ['N','None'],['K','Kinder'],['ES','Elem Student'],['EU','Elem Undergrad'],['EG','Elem Graduate'],
    ['HS','HS Student'],['HU','HS Undergrad'],['HG','HS Graduate'],
    ['V','Vocational Course'],
    ['CS','College Student'],['CU','College Undergrad'],['CG','College Graduate'],
    ['PG','Postgraduate']
  ];

  function optHtml(opts, selected){
    return opts.map(([v, label]) => `<option value="${v}" ${String(v)===String(selected)?'selected':''}>${label}</option>`).join('');
  }

  function classFromAgeSex(age, sex){
    if (!age || age <= 0) return '';
    if (age <= 4) return 'U';
    if (age >= 5 && age <= 9) return 'S';
    if (age >= 10 && age <= 19) return 'A';
    if (age >= 20) return 'AB';
    return '';
  }

  function memberRowTemplate(gIndex, mIndex, data){
    const med = Array.isArray(data.medical_history) ? data.medical_history : [];
    const has = (x) => med.includes(x) ? 'checked' : '';
    const linked = !!data.linked_member_id;

    return `
      <tr class="member-row" data-g="${gIndex}" data-m="${mIndex}">
        <td class="w-220">
          <input type="hidden" name="groups[${gIndex}][members][${mIndex}][linked_member_id]" value="${data.linked_member_id||''}" class="linkedMemberId">

          <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
            <strong>${linked ? '<span class="badge bg-success badge-linked">LINKED</span>' : '<span class="badge bg-secondary badge-linked">NEW</span>'}</strong>
            ${linked ? '<button type="button" class="btn btn-sm btn-outline-secondary js-unlink">Unlink</button>' : ''}
          </div>

          <input class="form-control form-control-sm mb-1 localLastName" name="groups[${gIndex}][members][${mIndex}][local_last_name]" placeholder="Last" value="${data.local_last_name||''}" ${linked ? 'readonly' : 'required'}>
          <input class="form-control form-control-sm mb-1 localFirstName" name="groups[${gIndex}][members][${mIndex}][local_first_name]" placeholder="First" value="${data.local_first_name||''}" ${linked ? 'readonly' : 'required'}>
          <input class="form-control form-control-sm localMiddleName" name="groups[${gIndex}][members][${mIndex}][local_middle_name]" placeholder="Middle" value="${data.local_middle_name||''}" ${linked ? 'readonly' : ''}>
        </td>

        <td class="w-140">
          <select class="form-select form-select-sm mb-1 relSel" name="groups[${gIndex}][members][${mIndex}][relationship_code]">
            <option value="">—</option>
            <option value="1" ${String(data.relationship_code||'')==='1'?'selected':''}>1 - Head</option>
            <option value="2" ${String(data.relationship_code||'')==='2'?'selected':''}>2 - Spouse</option>
            <option value="3" ${String(data.relationship_code||'')==='3'?'selected':''}>3 - Son</option>
            <option value="4" ${String(data.relationship_code||'')==='4'?'selected':''}>4 - Daughter</option>
            <option value="5" ${String(data.relationship_code||'')==='5'?'selected':''}>5 - Others</option>
          </select>
          <input class="form-control form-control-sm relOther" name="groups[${gIndex}][members][${mIndex}][relationship_other]" placeholder="If Others, specify"
            value="${data.relationship_other||''}" style="display:${String(data.relationship_code||'')==='5'?'':'none'};">
        </td>

        <td class="w-110">
          <select class="form-select form-select-sm sexSel" name="groups[${gIndex}][members][${mIndex}][sex]" ${linked ? 'disabled' : 'required'}>
            <option value="">—</option>
            <option value="M" ${data.sex==='M'?'selected':''}>Male</option>
            <option value="F" ${data.sex==='F'?'selected':''}>Female</option>
          </select>
          ${linked ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][sex]" value="${data.sex||''}">` : ''}
        </td>

        <td class="w-140">
          <input type="date" class="form-control form-control-sm dobSel" name="groups[${gIndex}][members][${mIndex}][dob]" value="${data.dob||''}" ${linked ? 'readonly' : 'required'}>
        </td>

        <td class="w-140">
          <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][civil_status]">
            <option value="">Select</option>
            <option value="single" ${data.civil_status==='single'?'selected':''}>Single</option>
            <option value="married" ${data.civil_status==='married'?'selected':''}>Married</option>
            <option value="widowed" ${data.civil_status==='widowed'?'selected':''}>Widowed</option>
            <option value="separated" ${data.civil_status==='separated'?'selected':''}>Separated</option>
            <option value="live_in" ${data.civil_status==='live_in'?'selected':''}>Live-in / Cohabiting</option>
          </select>
        </td>

        <td class="w-180">
          <select class="form-select form-select-sm mb-1" name="groups[${gIndex}][members][${mIndex}][status_in_household]">
            ${optHtml(memberStatusOptions, data.status_in_household||'')}
          </select>
          <div class="d-flex gap-1">
            <input type="date" class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][stay_from]" value="${data.stay_from||''}" title="Stay from">
            <input type="date" class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][stay_to]" value="${data.stay_to||''}" title="Stay to">
          </div>
        </td>

        <td class="w-180">
          <input class="form-control form-control-sm mb-1" name="groups[${gIndex}][members][${mIndex}][philhealth_id]" placeholder="PhilHealth ID" value="${data.philhealth_id||''}">
          <div class="d-flex gap-1">
            <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][membership_type]">
              <option value="">—Type—</option>
              <option value="member" ${data.membership_type==='member'?'selected':''}>Member</option>
              <option value="dependent" ${data.membership_type==='dependent'?'selected':''}>Dependent</option>
            </select>
            <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][philhealth_category]">
              <option value="">—Cat—</option>
              <option value="FEP" ${data.philhealth_category==='FEP'?'selected':''}>FEP</option>
              <option value="FEG" ${data.philhealth_category==='FEG'?'selected':''}>FEG</option>
              <option value="IE" ${data.philhealth_category==='IE'?'selected':''}>IE</option>
              <option value="N" ${data.philhealth_category==='N'?'selected':''}>N</option>
              <option value="SC" ${data.philhealth_category==='SC'?'selected':''}>SC</option>
              <option value="IP" ${data.philhealth_category==='IP'?'selected':''}>IP</option>
              <option value="U" ${data.philhealth_category==='U'?'selected':''}>U</option>
            </select>
          </div>
        </td>

        <td class="w-180">
          <div class="form-check"><input class="form-check-input" type="checkbox" name="groups[${gIndex}][members][${mIndex}][medical_history][]" value="HPN" ${has('HPN')}> <label class="form-check-label">HPN</label></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="groups[${gIndex}][members][${mIndex}][medical_history][]" value="DM" ${has('DM')}> <label class="form-check-label">DM</label></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="groups[${gIndex}][members][${mIndex}][medical_history][]" value="TB" ${has('TB')}> <label class="form-check-label">TB</label></div>
          <div class="form-check"><input class="form-check-input" type="checkbox" name="groups[${gIndex}][members][${mIndex}][medical_history][]" value="S" ${has('S')}> <label class="form-check-label">Surgery</label></div>
        </td>

        <td class="w-140">
          <input type="date" class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][lmp_date]" value="${data.lmp_date||''}">
        </td>

        <td class="w-140">
          <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][educ_attainment]">
            ${optHtml(educOptions, data.educ_attainment||'')}
          </select>
        </td>

        <td class="w-140">
          <input class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][religion]" value="${data.religion||''}" placeholder="Religion">
        </td>

        <td class="w-180 qCell">
          ${[1,2,3,4].map(q => `
            <div class="d-flex gap-1 mb-1">
              <input class="form-control form-control-sm qAge" name="groups[${gIndex}][members][${mIndex}][q${q}_age]" placeholder="Q${q} Age" value="${data['q'+q+'_age']||''}">
              <input class="form-control form-control-sm qClass" name="groups[${gIndex}][members][${mIndex}][q${q}_class]" placeholder="Class" value="${data['q'+q+'_class']||''}" readonly>
            </div>
          `).join('')}
        </td>

        <td class="member-actions">
          <button type="button" class="btn btn-sm btn-outline-danger js-remove-member">Remove</button>
        </td>
      </tr>
    `;
  }

  function groupTemplate(gIndex, data){
    return `
      <div class="group-card" data-g="${gIndex}">
        <div class="group-head">
          <div class="row g-2 flex-grow-1">
            <div class="col-lg-4 col-md-12">
              <label class="form-label mb-1">Group Name (optional)</label>
              <input class="form-control" name="groups[${gIndex}][group_name]" value="${data.group_name||''}" placeholder="e.g., Owner Family, Renter Family 1">
            </div>
            <div class="col-lg-4 col-md-12">
              <label class="form-label mb-1">Living Status</label>
              <select class="form-select" name="groups[${gIndex}][living_status]" required>
                <option value="">Select</option>
                ${optHtml(livingStatusOptions, data.living_status||'')}
              </select>
            </div>
            <div class="col-lg-4 col-md-12">
              <label class="form-label mb-1">Notes (optional)</label>
              <input class="form-control" name="groups[${gIndex}][notes]" value="${data.notes||''}" placeholder="e.g., Apt A, 2nd floor room">
            </div>
          </div>

          <div class="d-flex gap-2 flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-primary js-add-member">+ Add New Member</button>
            <button type="button" class="btn btn-sm btn-outline-success js-link-member">+ Link Existing</button>
            <button type="button" class="btn btn-sm btn-outline-danger js-remove-group">Remove Group</button>
          </div>
        </div>

        <div class="table-responsive mt-2">
          <table class="table table-sm table-bordered table-mini align-middle">
            <thead>
              <tr>
                <th>Name</th>
                <th>Relationship</th>
                <th>Sex</th>
                <th>DOB</th>
                <th>Civil</th>
                <th>Status/Stay</th>
                <th>PhilHealth</th>
                <th>Medical</th>
                <th>LMP</th>
                <th>Educ</th>
                <th>Religion</th>
                <th>Quarters</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody class="groupMembersBody"></tbody>
          </table>
        </div>
      </div>
    `;
  }

  function renumberAll(){
    const groupCards = Array.from(groupsWrap.querySelectorAll('.group-card'));
    groupCards.forEach((card, newG) => {
      card.dataset.g = newG;

      card.querySelectorAll('[name]').forEach(el => {
        const nm = el.getAttribute('name');
        if (!nm) return;
        el.setAttribute('name', nm.replace(/^groups\[\d+\]/, `groups[${newG}]`));
      });

      const tbody = card.querySelector('.groupMembersBody');
      const rows = Array.from(tbody.querySelectorAll('tr.member-row'));
      rows.forEach((row, newM) => {
        row.dataset.g = newG;
        row.dataset.m = newM;

        row.querySelectorAll('[name]').forEach(el => {
          const nm = el.getAttribute('name');
          if (!nm) return;
          let updated = nm.replace(/^groups\[\d+\]/, `groups[${newG}]`);
          updated = updated.replace(/\[members\]\[\d+\]/, `[members][${newM}]`);
          el.setAttribute('name', updated);
        });
      });
    });

    bindQuarterAutoCompute();
  }

  function addGroup(seed){
    const gIndex = groupsWrap.querySelectorAll('.group-card').length;
    groupsWrap.insertAdjacentHTML('beforeend', groupTemplate(gIndex, seed || {}));
    const card = groupsWrap.querySelectorAll('.group-card')[gIndex];
    const tbody = card.querySelector('.groupMembersBody');

    const members = (seed && Array.isArray(seed.members)) ? seed.members : [];
    members.forEach((m, idx) => {
      tbody.insertAdjacentHTML('beforeend', memberRowTemplate(gIndex, idx, m));
    });

    if (members.length === 0) {
      tbody.insertAdjacentHTML('beforeend', memberRowTemplate(gIndex, 0, {
        medical_history: [],
        relationship_code: '',
        sex: '',
      }));
    }

    renumberAll();
  }

  addGroupBtn.addEventListener('click', () => {
    addGroup({ living_status: 'owner_occupant', members: [] });
  });

  groupsWrap.addEventListener('click', (e) => {
    const card = e.target.closest('.group-card');
    if (!card) return;
    const gIndex = parseInt(card.dataset.g, 10);
    const tbody = card.querySelector('.groupMembersBody');

    if (e.target.closest('.js-remove-group')) {
      if (!confirm('Remove this Family Group and all its members?')) return;
      card.remove();
      renumberAll();
      return;
    }

    if (e.target.closest('.js-add-member')) {
      const mIndex = tbody.querySelectorAll('tr.member-row').length;
      tbody.insertAdjacentHTML('beforeend', memberRowTemplate(gIndex, mIndex, { medical_history: [] }));
      renumberAll();
      return;
    }

    if (e.target.closest('.js-remove-member')) {
      const row = e.target.closest('tr.member-row');
      if (!row) return;
      row.remove();
      renumberAll();
      return;
    }

    if (e.target.closest('.js-link-member')) {
      linkTarget = { gIndex };
      const modalEl = document.getElementById('linkMemberModal');
      const modal = new bootstrap.Modal(modalEl);
      document.getElementById('linkSearchInput').value = '';
      renderLinkResults([]);
      modal.show();
      return;
    }

    if (e.target.closest('.js-unlink')) {
      const row = e.target.closest('tr.member-row');
      if (!row) return;
      const mIndex = parseInt(row.dataset.m, 10);

      row.outerHTML = memberRowTemplate(gIndex, mIndex, {
        medical_history: [],
        relationship_code: '',
        sex: '',
      });
      renumberAll();
      return;
    }
  });

  groupsWrap.addEventListener('change', (e) => {
    if (e.target.classList.contains('relSel')) {
      const row = e.target.closest('tr.member-row');
      if (!row) return;
      const other = row.querySelector('.relOther');
      if (other) other.style.display = (String(e.target.value) === '5') ? '' : 'none';
    }
  });

  function bindQuarterAutoCompute(){
    groupsWrap.querySelectorAll('tr.member-row').forEach(tr => {
      const sexSel = tr.querySelector('.sexSel');
      const qAges = tr.querySelectorAll('.qAge');
      const qClasses = tr.querySelectorAll('.qClass');

      if (!qAges.length || !qClasses.length) return;

      qAges.forEach((ageInput, idx) => {
        if (ageInput.dataset.bound === '1') return;

        const recalcOne = () => {
          const age = parseInt(ageInput.value || '0', 10);
          const sex = sexSel ? (sexSel.value || '') : '';
          qClasses[idx].value = age > 0 ? classFromAgeSex(age, sex) : '';
        };

        ageInput.addEventListener('input', recalcOne);
        ageInput.addEventListener('change', recalcOne);
        ageInput.dataset.bound = '1';

        const age = parseInt(ageInput.value || '0', 10);
        const sex = sexSel ? (sexSel.value || '') : '';
        qClasses[idx].value = age > 0 ? classFromAgeSex(age, sex) : '';
      });

      if (sexSel && sexSel.dataset.bound !== '1') {
        sexSel.addEventListener('change', () => {
          qAges.forEach((ageInput, idx) => {
            const age = parseInt(ageInput.value || '0', 10);
            qClasses[idx].value = age > 0 ? classFromAgeSex(age, sexSel.value || '') : '';
          });
        });
        sexSel.dataset.bound = '1';
      }
    });
  }

  const linkSearchInput = document.getElementById('linkSearchInput');
  const linkResultsBody = document.getElementById('linkResultsBody');

  function renderLinkResults(rows){
    if (!rows || rows.length === 0) {
      linkResultsBody.innerHTML = `<tr><td colspan="5" class="text-muted">No results.</td></tr>`;
      return;
    }

    linkResultsBody.innerHTML = rows.map(r => `
      <tr>
        <td>
          <strong>${escapeHtml(r.name)}</strong>
          <div class="text-muted small">#${r.id} | Visit ${r.visit_id} (${escapeHtml(r.visit_date||'')})</div>
        </td>
        <td>${escapeHtml(r.sex||'')}</td>
        <td>${escapeHtml(r.dob||'')}</td>
        <td class="small">
          <div><strong>Muni:</strong> ${escapeHtml(r.municipality_pcode||'')}</div>
          <div><strong>Brgy:</strong> ${escapeHtml(r.barangay_pcode||'')}</div>
          <div><strong>Sitio:</strong> ${escapeHtml(r.sitio_purok||'')}</div>
          <div><strong>HH#:</strong> ${escapeHtml(r.household_no||'')}</div>
        </td>
        <td>
          <button type="button"
                  class="btn btn-sm btn-success js-pick-link"
                  data-id="${r.id}"
                  data-name="${escapeAttr(r.name)}"
                  data-sex="${escapeAttr(r.sex||'')}"
                  data-dob="${escapeAttr(r.dob||'')}">
            Select
          </button>
        </td>
      </tr>
    `).join('');
  }

  function escapeHtml(str){
    return String(str || '').replace(/[&<>"']/g, s => ({
      '&':'&amp;',
      '<':'&lt;',
      '>':'&gt;',
      '"':'&quot;',
      "'":'&#39;'
    }[s]));
  }

  function escapeAttr(str){
    return escapeHtml(str).replace(/"/g, '&quot;');
  }

  let searchTimer = null;
  linkSearchInput.addEventListener('input', () => {
    const q = linkSearchInput.value.trim();
    clearTimeout(searchTimer);

    if (q.length < 2) {
      renderLinkResults([]);
      return;
    }

    searchTimer = setTimeout(async () => {
      try {
        const res = await fetch(`<?= base_url('admin/registry/household-profiling/search-members') ?>?q=${encodeURIComponent(q)}`);
        const rows = await res.json();
        renderLinkResults(rows);
      } catch (e) {
        linkResultsBody.innerHTML = `<tr><td colspan="5" class="text-danger">Search failed.</td></tr>`;
      }
    }, 250);
  });

  linkResultsBody.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-pick-link');
    if (!btn) return;

    const memberId = btn.dataset.id;
    const sex = btn.dataset.sex || '';
    const dob = btn.dataset.dob || '';
    const rawName = btn.dataset.name || '';

    const partsByComma = rawName.split(',');
    const last = (partsByComma[0] || '').trim();
    const rest = (partsByComma[1] || '').trim();

    const restParts = rest.split(' ').filter(Boolean);
    const first = restParts.shift() || '';
    const middle = restParts.join(' ') || '';

    const card = groupsWrap.querySelector(`.group-card[data-g="${linkTarget.gIndex}"]`);
    if (!card) return;

    const tbody = card.querySelector('.groupMembersBody');
    const mIndex = tbody.querySelectorAll('tr.member-row').length;

    tbody.insertAdjacentHTML('beforeend', memberRowTemplate(linkTarget.gIndex, mIndex, {
      linked_member_id: memberId,
      local_last_name: last,
      local_first_name: first,
      local_middle_name: middle,
      sex: sex,
      dob: dob,
      medical_history: [],
    }));

    renumberAll();

    const modalEl = document.getElementById('linkMemberModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
  });

  if (Array.isArray(seedGroups) && seedGroups.length) {
    seedGroups.forEach(g => addGroup(g));
  } else {
    addGroup({ living_status: 'owner_occupant', members: [] });
  }
})();
</script>

<?= $this->endSection() ?>