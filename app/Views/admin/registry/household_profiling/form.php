<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
<?php endif; ?>

<?php
$mode = $mode ?? 'create';
$visit = $visit ?? null;
$members = $members ?? [];
$lock = $lock ?? ['municipality_locked'=>false,'barangay_locked'=>false,'municipality_pcode'=>null,'barangay_pcode'=>null];

$action = ($mode === 'edit' && $visit)
  ? base_url('admin/registry/household-profiling/'.$visit['id'])
  : base_url('admin/registry/household-profiling');

// old() helpers
$oldVisitDate = old('visit_date', $visit ? date('m/d/Y', strtotime($visit['visit_date'])) : '');
?>

<div class="card">
  <div class="card-header fw-semibold"><?= esc($pageTitle ?? 'Household Profiling') ?></div>

  <div class="card-body">
    <form method="post" action="<?= $action ?>">
      <?= csrf_field() ?>

      <div class="row g-3">

        <div class="col-md-3">
          <label class="form-label">Date of Visit (mm/dd/yyyy)</label>
          <input class="form-control" name="visit_date" id="visit_date" value="<?= esc($oldVisitDate) ?>" placeholder="mm/dd/yyyy" required>
          <div class="form-text">Quarter auto-detect will display on the right.</div>
        </div>

        <div class="col-md-2">
          <label class="form-label">Quarter</label>
          <input class="form-control" id="visit_quarter" value="" readonly>
        </div>

        <div class="col-md-4">
          <label class="form-label">Sitio/Purok</label>
          <input class="form-control" name="sitio_purok" value="<?= esc(old('sitio_purok', $visit['sitio_purok'] ?? '')) ?>" required>
        </div>

        <div class="col-md-3">
          <label class="form-label">Household Number</label>
          <input class="form-control" name="household_no" value="<?= esc(old('household_no', $visit['household_no'] ?? '')) ?>" required>
        </div>

        <!-- Barangay select -->
        <div class="col-md-6">
          <label class="form-label">Barangay</label>
          <select class="form-select" id="barangay_pcode" name="barangay_pcode" <?= $lock['barangay_locked'] ? 'disabled' : '' ?> required>
            <option value="">— Select Barangay —</option>
          </select>
          <?php if (!empty($lock['barangay_locked'])): ?>
            <input type="hidden" name="barangay_pcode" value="<?= esc(old('barangay_pcode', $visit['barangay_pcode'] ?? $lock['barangay_pcode'])) ?>">
          <?php endif; ?>
          <div class="form-text">Barangay list loads from Locations module (Del Carmen scope recommended).</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Remarks</label>
          <textarea class="form-control" name="remarks" rows="2"><?= esc(old('remarks', $visit['remarks'] ?? '')) ?></textarea>
        </div>

      </div>

      <hr class="my-4">

      <h5 class="mb-3">Respondent Information</h5>

      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Last Name</label>
          <input class="form-control" name="respondent_last_name" value="<?= esc(old('respondent_last_name', $visit['respondent_last_name'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">First Name</label>
          <input class="form-control" name="respondent_first_name" value="<?= esc(old('respondent_first_name', $visit['respondent_first_name'] ?? '')) ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Middle Name (optional)</label>
          <input class="form-control" name="respondent_middle_name" value="<?= esc(old('respondent_middle_name', $visit['respondent_middle_name'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Relationship to Household Head</label>
          <select class="form-select" name="respondent_relation" id="resp_relation" required>
            <?php
              $respRel = old('respondent_relation', $visit['respondent_relation'] ?? '');
              $respOptions = ['Head','Spouse','Son','Daughter','Parent','Sibling','Grandparent','Grandchild','Relative','Other'];
            ?>
            <option value="">— Select —</option>
            <?php foreach ($respOptions as $o): ?>
              <option value="<?= esc($o) ?>" <?= ($respRel===$o)?'selected':'' ?>><?= esc($o) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4" id="resp_relation_other_wrap" style="display:none;">
          <label class="form-label">If Other, specify</label>
          <input class="form-control" name="respondent_relation_other" value="<?= esc(old('respondent_relation_other', $visit['respondent_relation_other'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Ethnicity</label>
          <?php $ethMode = old('ethnicity_mode', $visit['ethnicity_mode'] ?? 'ip_household'); ?>
          <select class="form-select" name="ethnicity_mode" id="ethnicity_mode" required>
            <option value="ip_household" <?= ($ethMode==='ip_household')?'selected':'' ?>>IP Household</option>
            <option value="tribe" <?= ($ethMode==='tribe')?'selected':'' ?>>If IP Household, indicate Tribe</option>
          </select>
        </div>

        <div class="col-md-4" id="ethnicity_tribe_wrap" style="display:none;">
          <label class="form-label">Tribe</label>
          <input class="form-control" name="ethnicity_tribe" value="<?= esc(old('ethnicity_tribe', $visit['ethnicity_tribe'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Socioeconomic Status</label>
          <?php $soc = old('socioeconomic_status', $visit['socioeconomic_status'] ?? 'non_nhts'); ?>
          <select class="form-select" name="socioeconomic_status" id="socioeconomic_status" required>
            <option value="nhts_4ps" <?= ($soc==='nhts_4ps')?'selected':'' ?>>NHTS 4Ps</option>
            <option value="nhts_non4ps" <?= ($soc==='nhts_non4ps')?'selected':'' ?>>NHTS Non-4Ps</option>
            <option value="non_nhts" <?= ($soc==='non_nhts')?'selected':'' ?>>Non-NHTS</option>
          </select>
        </div>

        <div class="col-md-4" id="nhts_no_wrap" style="display:none;">
          <label class="form-label">NHTS No.</label>
          <input class="form-control" name="nhts_no" value="<?= esc(old('nhts_no', $visit['nhts_no'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Type of Water Source</label>
          <?php $water = old('water_source', $visit['water_source'] ?? 'level1'); ?>
          <select class="form-select" name="water_source" id="water_source" required>
            <option value="level1" <?= ($water==='level1')?'selected':'' ?>>Level I - Point Source</option>
            <option value="level2" <?= ($water==='level2')?'selected':'' ?>>Level II - Communal Faucet</option>
            <option value="level3" <?= ($water==='level3')?'selected':'' ?>>Level III - Individual Connection</option>
            <option value="others" <?= ($water==='others')?'selected':'' ?>>Others</option>
          </select>
        </div>

        <div class="col-md-4" id="water_other_wrap" style="display:none;">
          <label class="form-label">If Others, specify</label>
          <input class="form-control" name="water_source_other" value="<?= esc(old('water_source_other', $visit['water_source_other'] ?? '')) ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Type of Toilet Facility</label>
          <?php $toilet = old('toilet_facility', $visit['toilet_facility'] ?? 'A'); ?>
          <select class="form-select" name="toilet_facility" required>
            <option value="A" <?= ($toilet==='A')?'selected':'' ?>>A - Pour/flush type connected to septic tank</option>
            <option value="B" <?= ($toilet==='B')?'selected':'' ?>>B - Connected to septic tank AND sewerage system</option>
            <option value="C" <?= ($toilet==='C')?'selected':'' ?>>C - Ventilated Pit (VIP) latrine</option>
            <option value="D" <?= ($toilet==='D')?'selected':'' ?>>D - Water-sealed toilet</option>
            <option value="E" <?= ($toilet==='E')?'selected':'' ?>>E - Overhung latrine</option>
            <option value="F" <?= ($toilet==='F')?'selected':'' ?>>F - Open pit latrine</option>
            <option value="G" <?= ($toilet==='G')?'selected':'' ?>>G - Without toilet</option>
          </select>
        </div>
      </div>

      <hr class="my-4">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">Household Members</h5>
        <button type="button" class="btn btn-outline-primary btn-sm" id="addMemberBtn">
          <i class="fa-solid fa-plus"></i> Add Member
        </button>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle" id="membersTable">
          <thead>
            <tr>
              <th style="min-width:180px;">Name</th>
              <th style="min-width:220px;">Relationship</th>
              <th>Sex</th>
              <th style="min-width:130px;">DOB</th>
              <th>Civil</th>
              <th style="min-width:220px;">PhilHealth</th>
              <th style="min-width:160px;">Medical History</th>
              <th style="min-width:130px;">LMP</th>
              <th style="min-width:170px;">Education</th>
              <th style="min-width:150px;">Religion</th>
              <th style="min-width:260px;">Quarters (Age → Class)</th>
              <th>Remove</th>
            </tr>
          </thead>
          <tbody id="membersBody">
          </tbody>
        </table>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary">Save</button>
        <a href="<?= base_url('admin/registry/household-profiling') ?>" class="btn btn-light">Cancel</a>
      </div>

    </form>
  </div>
</div>

<?php
// If validation failed, CI returns old('members') as array
$oldMembers = old('members');
$seedMembers = [];

if (is_array($oldMembers) && !empty($oldMembers)) {
    $seedMembers = $oldMembers;
} else {
    // convert existing members (edit mode) into the same shape
    foreach ($members as $m) {
        $seedMembers[] = [
            'last_name' => $m['last_name'],
            'first_name' => $m['first_name'],
            'middle_name' => $m['middle_name'],
            'relationship_code' => $m['relationship_code'],
            'relationship_other' => $m['relationship_other'],
            'sex' => $m['sex'],
            'dob' => date('m/d/Y', strtotime($m['dob'])),
            'civil_status' => $m['civil_status'],
            'philhealth_id' => $m['philhealth_id'],
            'membership_type' => $m['membership_type'],
            'philhealth_category' => $m['philhealth_category'],
            'medical_history' => $m['medical_history_arr'] ?? [],
            'lmp_date' => !empty($m['lmp_date']) ? date('m/d/Y', strtotime($m['lmp_date'])) : '',
            'educ_attainment' => $m['educ_attainment'],
            'religion' => $m['religion'],
            'remarks' => $m['remarks'],

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
}
?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function(){
  const LOC_LIST_URL = "<?= base_url('admin/settings/locations/list') ?>";
  const lock = <?= json_encode($lock) ?>;

  // selected barangay value (old -> visit -> lock)
  const selectedBarangay = "<?= esc(old('barangay_pcode', $visit['barangay_pcode'] ?? ($lock['barangay_pcode'] ?? ''))) ?>";
//   const selectedMunicipality = "<?= esc(old('municipality_pcode', $visit['municipality_pcode'] ?? ($lock['municipality_pcode'] ?? ''))) ?>";
  const selectedMunicipality = "<?= esc($lock['municipality_pcode'] ?? 'PH1606708') ?>";

  async function fetchJSON(url){
    const res = await fetch(url, { headers: { 'Accept':'application/json' }});
    if(!res.ok) return [];
    return await res.json();
  }

  // For Phase 1: load barangays by municipality only (Del Carmen scope)
  async function loadBarangays(){
    const brgySelect = document.getElementById('barangay_pcode');

    // If you have municipality scope, call: level=4&parent=<municipality_pcode>
    // Otherwise, you can swap to load all barangays (not recommended).
    const mun = selectedMunicipality || lock.municipality_pcode || '';
    if (!mun) return;

    const rows = await fetchJSON(`${LOC_LIST_URL}?level=4&parent=${encodeURIComponent(mun)}`);

    brgySelect.innerHTML = '<option value="">— Select Barangay —</option>';
    rows.forEach(r=>{
      const o = document.createElement('option');
      o.value = r.pcode;
      o.textContent = r.name;
      brgySelect.appendChild(o);
    });

    if (selectedBarangay) brgySelect.value = selectedBarangay;
  }

  // quarter from visit_date
  function computeQuarter(mmddyyyy){
    if (!mmddyyyy || !/^\d{2}\/\d{2}\/\d{4}$/.test(mmddyyyy)) return '';
    const m = parseInt(mmddyyyy.slice(0,2),10);
    if (!m || m<1 || m>12) return '';
    return Math.ceil(m/3);
  }

  function updateQuarter(){
    const d = document.getElementById('visit_date').value.trim();
    const q = computeQuarter(d);
    document.getElementById('visit_quarter').value = q ? ('Q' + q) : '';
  }

  // conditional wrappers
  const respRel = document.getElementById('resp_relation');
  const respOtherWrap = document.getElementById('resp_relation_other_wrap');

  const ethMode = document.getElementById('ethnicity_mode');
  const tribeWrap = document.getElementById('ethnicity_tribe_wrap');

  const soc = document.getElementById('socioeconomic_status');
  const nhtsWrap = document.getElementById('nhts_no_wrap');

  const water = document.getElementById('water_source');
  const waterOtherWrap = document.getElementById('water_other_wrap');

  function toggleConditional(){
    respOtherWrap.style.display = (respRel.value === 'Other') ? '' : 'none';
    tribeWrap.style.display = (ethMode.value === 'tribe') ? '' : 'none';
    nhtsWrap.style.display = (soc.value.startsWith('nhts')) ? '' : 'none';
    waterOtherWrap.style.display = (water.value === 'others') ? '' : 'none';
  }

  // Member builder
  const membersSeed = <?= json_encode($seedMembers) ?>;

  const membersBody = document.getElementById('membersBody');
  const addBtn = document.getElementById('addMemberBtn');

  const educOptions = [
    ['','—'],
    ['N','None'],['K','Kinder'],['ES','Elem Student'],['EU','Elem Undergrad'],['EG','Elem Graduate'],
    ['HS','HS Student'],['HU','HS Undergrad'],['HG','HS Graduate'],
    ['V','Vocational Course'],
    ['CS','College Student'],['CU','College Undergrad'],['CG','College Graduate'],
    ['PG','Postgraduate']
  ];

  function classFromAgeSex(age, sex){
    // Phase 1 default rules (can refine later)
    if (!age || age < 0) return '';
    if (age >= 60) return 'SC';
    if (sex === 'F' && age >= 15 && age <= 49) return 'WRA';
    if (age <= 4) return 'U';
    if (age >= 5 && age <= 9) return 'S';
    if (age >= 10 && age <= 19) return 'A';
    if (age >= 26) return 'AB';
    return 'AB'; // 20-25 fallback to adult
  }

  function relationshipRank(code){
    // 1 Head, 2 Spouse, 3 Son, 4 Daughter, 5 Others
    if (code === 1) return 1;
    if (code === 2) return 2;
    if (code === 3 || code === 4) return 3;
    return 4;
  }

  function parseDob(mmddyyyy){
    if (!mmddyyyy || !/^\d{2}\/\d{2}\/\d{4}$/.test(mmddyyyy)) return '9999-12-31';
    const mm = mmddyyyy.slice(0,2);
    const dd = mmddyyyy.slice(3,5);
    const yy = mmddyyyy.slice(6,10);
    return `${yy}-${mm}-${dd}`;
  }

  function sortRows(){
    const rows = Array.from(membersBody.querySelectorAll('tr'));
    rows.sort((a,b)=>{
      const ra = parseInt(a.dataset.rel || '5',10);
      const rb = parseInt(b.dataset.rel || '5',10);

      const rankA = relationshipRank(ra);
      const rankB = relationshipRank(rb);
      if (rankA !== rankB) return rankA - rankB;

      // children eldest->youngest: older DOB first => smaller YYYY-MM-DD first
      const da = a.dataset.dob || '9999-12-31';
      const db = b.dataset.dob || '9999-12-31';
      return da.localeCompare(db);
    });

    rows.forEach(r=>membersBody.appendChild(r));
  }

  function memberRowTemplate(i, data){
    const rel = parseInt(data.relationship_code || '5',10);
    const dob = data.dob || '';
    const dobKey = parseDob(dob);

    const med = data.medical_history || [];
    const has = (code)=> (Array.isArray(med) && med.includes(code)) ? 'checked' : '';

    const optSel = (v, target)=> (String(v||'')===String(target||'')) ? 'selected' : '';

    const eduHtml = educOptions.map(([v,label]) =>
      `<option value="${v}" ${optSel(data.educ_attainment, v)}>${label}</option>`
    ).join('');

    return `
    <tr data-rel="${rel}" data-dob="${dobKey}">
      <td>
        <input class="form-control mb-1" name="members[${i}][last_name]" placeholder="Last" value="${data.last_name||''}" required>
        <input class="form-control mb-1" name="members[${i}][first_name]" placeholder="First" value="${data.first_name||''}" required>
        <input class="form-control" name="members[${i}][middle_name]" placeholder="Middle (optional)" value="${data.middle_name||''}">
      </td>

      <td>
        <select class="form-select mb-1 relSel" name="members[${i}][relationship_code]" required>
          <option value="">—</option>
          <option value="1" ${optSel(rel,1)}>1 - Head</option>
          <option value="2" ${optSel(rel,2)}>2 - Spouse</option>
          <option value="3" ${optSel(rel,3)}>3 - Son</option>
          <option value="4" ${optSel(rel,4)}>4 - Daughter</option>
          <option value="5" ${optSel(rel,5)}>5 - Others</option>
        </select>
        <input class="form-control relOther" name="members[${i}][relationship_other]" placeholder="If Others, specify" value="${data.relationship_other||''}" style="display:${rel===5?'':'none'};">
      </td>

      <td>
        <select class="form-select sexSel" name="members[${i}][sex]" required>
          <option value="">—</option>
          <option value="M" ${optSel(data.sex,'M')}>Male</option>
          <option value="F" ${optSel(data.sex,'F')}>Female</option>
        </select>
      </td>

      <td>
        <input class="form-control dobSel" name="members[${i}][dob]" placeholder="mm/dd/yyyy" value="${dob}" required>
      </td>

      <td>
        <select class="form-select" name="members[${i}][civil_status]" required>
          <option value="">—</option>
          <option value="M" ${optSel(data.civil_status,'M')}>M</option>
          <option value="S" ${optSel(data.civil_status,'S')}>S</option>
          <option value="W" ${optSel(data.civil_status,'W')}>W</option>
          <option value="SP" ${optSel(data.civil_status,'SP')}>SP</option>
          <option value="C" ${optSel(data.civil_status,'C')}>C</option>
        </select>
      </td>

      <td>
        <input class="form-control mb-1" name="members[${i}][philhealth_id]" placeholder="PhilHealth ID (optional)" value="${data.philhealth_id||''}">
        <div class="d-flex gap-1">
          <select class="form-select" name="members[${i}][membership_type]">
            <option value="">—Type—</option>
            <option value="member" ${optSel(data.membership_type,'member')}>Member</option>
            <option value="dependent" ${optSel(data.membership_type,'dependent')}>Dependent</option>
          </select>
          <select class="form-select" name="members[${i}][philhealth_category]">
            <option value="">—Cat—</option>
            <option value="FEP" ${optSel(data.philhealth_category,'FEP')}>FEP</option>
            <option value="FEG" ${optSel(data.philhealth_category,'FEG')}>FEG</option>
            <option value="IE" ${optSel(data.philhealth_category,'IE')}>IE</option>
            <option value="N" ${optSel(data.philhealth_category,'N')}>N</option>
            <option value="SC" ${optSel(data.philhealth_category,'SC')}>SC</option>
            <option value="IP" ${optSel(data.philhealth_category,'IP')}>IP</option>
            <option value="U" ${optSel(data.philhealth_category,'U')}>U</option>
          </select>
        </div>
      </td>

      <td>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="members[${i}][medical_history][]" value="HPN" ${has('HPN')}> <label class="form-check-label">HPN</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="members[${i}][medical_history][]" value="DM" ${has('DM')}> <label class="form-check-label">DM</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="members[${i}][medical_history][]" value="TB" ${has('TB')}> <label class="form-check-label">TB</label></div>
        <div class="form-check"><input class="form-check-input" type="checkbox" name="members[${i}][medical_history][]" value="S" ${has('S')}> <label class="form-check-label">Surgery</label></div>
      </td>

      <td>
        <input class="form-control" name="members[${i}][lmp_date]" placeholder="mm/dd/yyyy" value="${data.lmp_date||''}">
      </td>

      <td>
        <select class="form-select" name="members[${i}][educ_attainment]">
          ${eduHtml}
        </select>
      </td>

      <td>
        <input class="form-control" name="members[${i}][religion]" value="${data.religion||''}" placeholder="e.g., Roman Catholic">
      </td>

      <td>
        ${[1,2,3,4].map(q=>`
          <div class="d-flex gap-1 mb-1">
            <input class="form-control form-control-sm qAge" style="width:70px;" name="members[${i}][q${q}_age]" placeholder="Q${q} Age" value="${data['q'+q+'_age']||''}" required>
            <input class="form-control form-control-sm qClass" style="width:70px;" name="members[${i}][q${q}_class]" placeholder="Class" value="${data['q'+q+'_class']||''}" readonly required>
          </div>
        `).join('')}
      </td>

      <td class="text-center">
        <button type="button" class="btn btn-sm btn-outline-danger removeRowBtn">X</button>
      </td>
    </tr>`;
  }

  function refreshRowIndexes(){
    // rebuild names so members[i] indexes are contiguous
    const rows = Array.from(membersBody.querySelectorAll('tr'));
    const data = rows.map(r => collectRowData(r));
    membersBody.innerHTML = '';
    data.forEach((d,i)=> membersBody.insertAdjacentHTML('beforeend', memberRowTemplate(i,d)));
    bindRowEvents();
    sortRows();
  }

  function collectRowData(tr){
    const d = {};
    const inputs = tr.querySelectorAll('input,select,textarea');
    inputs.forEach(el=>{
      const name = el.getAttribute('name') || '';
      const m = name.match(/members\[\d+\]\[(.+?)\](\[\])?/);
      if (!m) return;
      const key = m[1];

      if (el.type === 'checkbox') {
        if (!d[key]) d[key] = [];
        if (el.checked) d[key].push(el.value);
        return;
      }

      d[key] = el.value;
    });

    // keep computed quarters
    for (let q=1;q<=4;q++){
      d[`q${q}_age`] = d[`q${q}_age`] || '';
      d[`q${q}_class`] = d[`q${q}_class`] || '';
    }

    return d;
  }

  function bindRowEvents(){
    membersBody.querySelectorAll('tr').forEach(tr=>{
      const relSel = tr.querySelector('.relSel');
      const relOther = tr.querySelector('.relOther');
      const dobSel = tr.querySelector('.dobSel');
      const sexSel = tr.querySelector('.sexSel');

      relSel.addEventListener('change', ()=>{
        const code = parseInt(relSel.value||'5',10);
        tr.dataset.rel = code;
        relOther.style.display = (code===5) ? '' : 'none';
        sortRows();
      });

      dobSel.addEventListener('change', ()=>{
        tr.dataset.dob = parseDob(dobSel.value);
        sortRows();
      });

      // quarter class auto compute
      tr.querySelectorAll('.qAge').forEach((ageInput, idx)=>{
        const q = idx+1;
        ageInput.addEventListener('input', ()=>{
          const age = parseInt(ageInput.value||'0',10);
          const sex = sexSel.value || '';
          tr.querySelectorAll('.qClass')[idx].value = classFromAgeSex(age, sex);
        });
        // initialize
        const age = parseInt(ageInput.value||'0',10);
        if (age > 0 && !tr.querySelectorAll('.qClass')[idx].value) {
          tr.querySelectorAll('.qClass')[idx].value = classFromAgeSex(age, sexSel.value || '');
        }
      });

      sexSel.addEventListener('change', ()=>{
        // recompute all classes when sex changes
        tr.querySelectorAll('.qAge').forEach((ageInput, idx)=>{
          const age = parseInt(ageInput.value||'0',10);
          tr.querySelectorAll('.qClass')[idx].value = classFromAgeSex(age, sexSel.value || '');
        });
      });

      tr.querySelector('.removeRowBtn').addEventListener('click', ()=>{
        tr.remove();
        refreshRowIndexes();
      });
    });
  }

  function addMemberRow(seed){
    const i = membersBody.querySelectorAll('tr').length;
    membersBody.insertAdjacentHTML('beforeend', memberRowTemplate(i, seed || {}));
    bindRowEvents();
    sortRows();
  }

  addBtn.addEventListener('click', ()=> addMemberRow({
    relationship_code: 5,
    sex: '',
    civil_status: '',
    medical_history: []
  }));

  // Init visit quarter + conditionals + barangay list + seed members
  document.getElementById('visit_date').addEventListener('input', updateQuarter);
  updateQuarter();

  respRel.addEventListener('change', toggleConditional);
  ethMode.addEventListener('change', toggleConditional);
  soc.addEventListener('change', toggleConditional);
  water.addEventListener('change', toggleConditional);
  toggleConditional();

  // Seed initial member rows
  if (membersSeed.length) {
    membersSeed.forEach(m=> addMemberRow(m));
  } else {
    addMemberRow({ relationship_code: 1 }); // start with Head row
  }

  // Load barangays
  loadBarangays();
})();
</script>
<?= $this->endSection() ?>