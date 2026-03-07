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
    $lock  = $lock ?? [
        'municipality_locked' => false,
        'barangay_locked' => false,
        'municipality_pcode' => '',
        'barangay_pcode' => ''
    ];
    $lockedBarangayName = $lockedBarangayName ?? '';
    $lockedMunicipalityName = $lockedMunicipalityName ?? '';

    $reviewDiff = $reviewDiff ?? [];
    $reviewMode = ($mode ?? '') === 'review';
    $visitFieldDiff = $reviewDiff['visit_fields'] ?? [];

    if (! function_exists('reviewFieldChanged')) {
        function reviewFieldChanged(array $visitFieldDiff, string $field): bool
        {
            return array_key_exists($field, $visitFieldDiff);
        }
    }

    if (! function_exists('reviewOldValue')) {
        function reviewOldValue(array $visitFieldDiff, string $field): string
        {
            return isset($visitFieldDiff[$field]['old'])
                ? (string) $visitFieldDiff[$field]['old']
                : '';
        }
    }

    if (! function_exists('reviewFieldClass')) {
        function reviewFieldClass(bool $reviewMode, array $visitFieldDiff, string $field): string
        {
            return $reviewMode && reviewFieldChanged($visitFieldDiff, $field)
                ? 'review-changed'
                : '';
        }
    }

    $displayVisitDate = $mode === 'create'
        ? date('Y-m-d')
        : ($visit['last_visit_date'] ?? $visit['visit_date'] ?? date('Y-m-d'));

    $oldVisitDate = old('visit_date', $displayVisitDate);
    $oldQuarter   = old('visit_quarter', !empty($visit['visit_quarter']) ? ('Q' . $visit['visit_quarter']) : '');

    $oldBarangay  = old('barangay_pcode', $visit['barangay_pcode'] ?? ($lock['barangay_pcode'] ?? ''));
    $oldMunicipal = old('municipality_pcode', $visit['municipality_pcode'] ?? ($lock['municipality_pcode'] ?? ''));

    $oldSitio     = old('sitio_purok', $visit['sitio_purok'] ?? '');
    $oldHouseNo   = old('household_no', $visit['household_no'] ?? '');
    $oldLat       = old('household_latitude', $visit['household_latitude'] ?? '');
    $oldLng       = old('household_longitude', $visit['household_longitude'] ?? '');
    $oldLocSource = old('household_location_source', $visit['geo_source'] ?? '');
    $oldLocAccuracy = old('household_location_accuracy', $visit['geo_accuracy_m'] ?? '');

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

    $actorType = (string) ($actor['user_type'] ?? '');
    $isSuperAdmin = ($actorType === 'super_admin');
    $lockedMunicipality = (string) ($actor['municipality_pcode'] ?? $oldMunicipal);

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
                    'medical_histories' => $m['medical_histories'] ?? [],
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
                <div class="text-muted small">
                    <?php if ($reviewMode): ?>
                        Review submitted household profiling changes
                    <?php else: ?>
                        <?= $mode === 'edit' ? 'Edit household profiling and family groups' : 'Create new visit and family groups' ?>
                    <?php endif; ?>
                </div>
            </div>

            <a href="<?= base_url('admin/registry/household-profiling') ?>" class="btn btn-sm btn-outline-secondary">
                Back
            </a>
        </div>
    </div>

    <div class="card-body">
        <form method="post" action="<?= $mode === 'edit' ? base_url('admin/registry/household-profiling/' . ($visit['id'] ?? 0)) : base_url('admin/registry/household-profiling') ?>">
            <?= csrf_field() ?>

            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date of Visit</label>
                    <input
                        type="date"
                        class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'visit_date') ?>"
                        name="visit_date"
                        id="visit_date"
                        value="<?= esc($oldVisitDate) ?>"
                        <?= $reviewMode ? 'readonly' : '' ?>
                        required
                    >
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'visit_date')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'visit_date')) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($mode === 'edit' && ! $reviewMode): ?>
                        <div class="form-text">
                            Update Only = saves record changes only. Update Visit = updates latest visit date and increases Visit Count by 1.
                        </div>
                        <div class="small text-muted mt-1">
                            <strong>First Visit:</strong> <?= !empty($visit['visit_date']) ? esc(date('m/d/Y', strtotime($visit['visit_date']))) : '-' ?>
                            <br>
                            <strong>Last Visit:</strong> <?= !empty($visit['last_visit_date']) ? esc(date('m/d/Y', strtotime($visit['last_visit_date']))) : '-' ?>
                            <br>
                            <strong>Visit Count:</strong> <?= (int) ($visit['visit_count'] ?? 1) ?>
                        </div>
                    <?php elseif (! $reviewMode): ?>
                        <div class="form-text">Auto-filled with today, but you may change it.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-1">
                    <label class="form-label">Quarter</label>
                    <input
                        class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'visit_quarter') ?>"
                        name="visit_quarter"
                        id="visit_quarter"
                        value="<?= esc($oldQuarter) ?>"
                        readonly
                    >
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'visit_quarter')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'visit_quarter')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Municipality</label>

                  <?php if ($isSuperAdmin && $mode !== 'review'): ?>
                      <select class="form-select" name="municipality_pcode" id="municipality_pcode" required>
                          <option value="">Select</option>
                      </select>
                      <div class="form-text">Super Admin can select municipality.</div>
                  <?php else: ?>
                      <input
                          class="form-control"
                          id="municipality_pcode_display"
                          value="<?= esc($lockedMunicipalityName ?: $lockedMunicipality ?: $oldMunicipal) ?>"
                          readonly
                      >
                      <input
                          type="hidden"
                          name="municipality_pcode"
                          id="municipality_pcode"
                          value="<?= esc($oldMunicipal) ?>"
                      >
                      <div class="form-text">
                          <?= $mode === 'review'
                              ? 'Municipality locked for review.'
                              : 'Auto-selected based on your assigned municipality.' ?>
                      </div>
                  <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Barangay</label>
                    <select
                        class="form-select <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'barangay_pcode') ?>"
                        name="barangay_pcode"
                        id="barangay_pcode"
                        <?= (!empty($lock['barangay_locked']) || $reviewMode) ? 'disabled' : '' ?>
                        required
                    >
                        <?php if (!empty($lock['barangay_locked']) && !empty($oldBarangay)): ?>
                            <option value="<?= esc($oldBarangay) ?>" selected><?= esc($lockedBarangayName ?: $oldBarangay) ?></option>
                        <?php elseif ($reviewMode && !empty($oldBarangay)): ?>
                            <option value="<?= esc($oldBarangay) ?>" selected><?= esc($lockedBarangayName ?: $oldBarangay) ?></option>
                        <?php else: ?>
                            <option value="">Loading...</option>
                        <?php endif; ?>
                    </select>

                    <?php if (!empty($lock['barangay_locked']) || $reviewMode): ?>
                        <input type="hidden" name="barangay_pcode" value="<?= esc($oldBarangay) ?>">
                    <?php endif; ?>

                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'barangay_pcode')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'barangay_pcode')) ?>
                        </div>
                    <?php elseif (!empty($lock['barangay_locked'])): ?>
                        <div class="form-text">Barangay is locked based on your assigned scope.</div>
                    <?php else: ?>
                        <div class="form-text">Select barangay.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Sitio / Purok</label>
                    <input
                        class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'sitio_purok') ?>"
                        name="sitio_purok"
                        value="<?= esc($oldSitio) ?>"
                        <?= $reviewMode ? 'readonly' : '' ?>
                        required
                    >
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'sitio_purok')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'sitio_purok')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Household No.</label>
                    <input
                        class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'household_no') ?>"
                        name="household_no"
                        value="<?= esc($oldHouseNo) ?>"
                        <?= $reviewMode ? 'readonly' : '' ?>
                        required
                    >
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'household_no')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'household_no')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-8">
                    <label class="form-label">Name of Respondent</label>
                    <div class="row g-2">
                        <div class="col-lg-4 col-md-12">
                            <input
                                class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'respondent_last_name') ?>"
                                name="respondent_last_name"
                                value="<?= esc($oldRespLN) ?>"
                                placeholder="Last"
                                <?= $reviewMode ? 'readonly' : '' ?>
                                required
                            >
                            <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'respondent_last_name')): ?>
                                <div class="review-old-value">
                                    Old value: <?= esc(reviewOldValue($visitFieldDiff, 'respondent_last_name')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <input
                                class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'respondent_first_name') ?>"
                                name="respondent_first_name"
                                value="<?= esc($oldRespFN) ?>"
                                placeholder="First"
                                <?= $reviewMode ? 'readonly' : '' ?>
                                required
                            >
                            <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'respondent_first_name')): ?>
                                <div class="review-old-value">
                                    Old value: <?= esc(reviewOldValue($visitFieldDiff, 'respondent_first_name')) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <input
                                class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'respondent_middle_name') ?>"
                                name="respondent_middle_name"
                                value="<?= esc($oldRespMN) ?>"
                                placeholder="Middle (optional)"
                                <?= $reviewMode ? 'readonly' : '' ?>
                            >
                            <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'respondent_middle_name')): ?>
                                <div class="review-old-value">
                                    Old value: <?= esc(reviewOldValue($visitFieldDiff, 'respondent_middle_name')) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Relationship to Household Head</label>
                    <select
                        class="form-select <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'respondent_relation') ?>"
                        name="respondent_relation"
                        id="resp_relation"
                        <?= $reviewMode ? 'disabled' : '' ?>
                        required
                    >
                        <option value="">Select</option>
                        <option value="head" <?= ($oldRespRel === 'head') ? 'selected' : '' ?>>Head</option>
                        <option value="spouse" <?= ($oldRespRel === 'spouse') ? 'selected' : '' ?>>Spouse</option>
                        <option value="child" <?= ($oldRespRel === 'child') ? 'selected' : '' ?>>Child</option>
                        <option value="relative" <?= ($oldRespRel === 'relative') ? 'selected' : '' ?>>Relative</option>
                        <option value="other" <?= ($oldRespRel === 'other') ? 'selected' : '' ?>>Other</option>
                    </select>
                    <?php if ($reviewMode): ?>
                        <input type="hidden" name="respondent_relation" value="<?= esc($oldRespRel) ?>">
                    <?php endif; ?>
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'respondent_relation')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'respondent_relation')) ?>
                        </div>
                    <?php endif; ?>

                    <div id="resp_relation_other_wrap" class="mt-2" style="display:none;">
                        <input
                            class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'respondent_relation_other') ?>"
                            name="respondent_relation_other"
                            id="resp_relation_other"
                            value="<?= esc($oldRespRelOther) ?>"
                            placeholder="Specify relationship"
                            <?= $reviewMode ? 'readonly' : '' ?>
                        >
                        <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'respondent_relation_other')): ?>
                            <div class="review-old-value">
                                Old value: <?= esc(reviewOldValue($visitFieldDiff, 'respondent_relation_other')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Ethnicity</label>
                    <select
                        class="form-select <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'ethnicity_mode') ?>"
                        name="ethnicity_mode"
                        id="ethnicity_mode"
                        <?= $reviewMode ? 'disabled' : '' ?>
                        required
                    >
                        <option value="ip" <?= ($ethMode === 'ip') ? 'selected' : '' ?>>IP Household</option>
                        <option value="tribe" <?= ($ethMode === 'tribe') ? 'selected' : '' ?>>If IP Household, indicate Tribe</option>
                    </select>
                    <?php if ($reviewMode): ?>
                        <input type="hidden" name="ethnicity_mode" value="<?= esc($ethMode) ?>">
                    <?php endif; ?>
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'ethnicity_mode')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'ethnicity_mode')) ?>
                        </div>
                    <?php endif; ?>

                    <div id="tribe_wrap" class="mt-2" style="display:none;">
                        <input
                            class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'ethnicity_tribe') ?>"
                            name="ethnicity_tribe"
                            value="<?= esc($ethTribe) ?>"
                            placeholder="Tribe (e.g., Mamanwa)"
                            <?= $reviewMode ? 'readonly' : '' ?>
                        >
                        <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'ethnicity_tribe')): ?>
                            <div class="review-old-value">
                                Old value: <?= esc(reviewOldValue($visitFieldDiff, 'ethnicity_tribe')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Socioeconomic Status</label>
                    <select
                        class="form-select <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'socioeconomic_status') ?>"
                        name="socioeconomic_status"
                        id="socioeconomic_status"
                        <?= $reviewMode ? 'disabled' : '' ?>
                    >
                        <option value="">—</option>
                        <option value="nhts" <?= ($socio === 'nhts') ? 'selected' : '' ?>>NHTS</option>
                        <option value="nhts_4ps" <?= ($socio === 'nhts_4ps') ? 'selected' : '' ?>>NHTS + 4Ps</option>
                        <option value="non_nhts" <?= ($socio === 'non_nhts') ? 'selected' : '' ?>>Non-NHTS</option>
                    </select>
                    <?php if ($reviewMode): ?>
                        <input type="hidden" name="socioeconomic_status" value="<?= esc($socio) ?>">
                    <?php endif; ?>
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'socioeconomic_status')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'socioeconomic_status')) ?>
                        </div>
                    <?php endif; ?>

                    <div id="nhts_wrap" class="mt-2" style="display:none;">
                        <input
                            class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'nhts_no') ?>"
                            name="nhts_no"
                            value="<?= esc($nhtsNo) ?>"
                            placeholder="NHTS No. (optional)"
                            <?= $reviewMode ? 'readonly' : '' ?>
                        >
                        <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'nhts_no')): ?>
                            <div class="review-old-value">
                                Old value: <?= esc(reviewOldValue($visitFieldDiff, 'nhts_no')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Water Source</label>
                    <select
                        class="form-select <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'water_source') ?>"
                        name="water_source"
                        id="water_source"
                        <?= $reviewMode ? 'disabled' : '' ?>
                    >
                        <option value="">—</option>
                        <option value="piped" <?= ($water === 'piped') ? 'selected' : '' ?>>Piped water</option>
                        <option value="deep_well" <?= ($water === 'deep_well') ? 'selected' : '' ?>>Deep well</option>
                        <option value="shallow_well" <?= ($water === 'shallow_well') ? 'selected' : '' ?>>Shallow well</option>
                        <option value="spring" <?= ($water === 'spring') ? 'selected' : '' ?>>Spring</option>
                        <option value="others" <?= ($water === 'others') ? 'selected' : '' ?>>Others</option>
                    </select>
                    <?php if ($reviewMode): ?>
                        <input type="hidden" name="water_source" value="<?= esc($water) ?>">
                    <?php endif; ?>
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'water_source')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'water_source')) ?>
                        </div>
                    <?php endif; ?>

                    <div id="water_other_wrap" class="mt-2" style="display:none;">
                        <input
                            class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'water_source_other') ?>"
                            name="water_source_other"
                            value="<?= esc($waterOther) ?>"
                            placeholder="Specify"
                            <?= $reviewMode ? 'readonly' : '' ?>
                        >
                        <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'water_source_other')): ?>
                            <div class="review-old-value">
                                Old value: <?= esc(reviewOldValue($visitFieldDiff, 'water_source_other')) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Toilet Type</label>
                    <select
                        class="form-select <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'toilet_facility') ?>"
                        name="toilet_facility"
                        <?= $reviewMode ? 'disabled' : '' ?>
                    >
                        <option value="">—</option>
                        <option value="A" <?= ($toilet === 'A') ? 'selected' : '' ?>>A - Water-sealed</option>
                        <option value="B" <?= ($toilet === 'B') ? 'selected' : '' ?>>B - Pour flush</option>
                        <option value="C" <?= ($toilet === 'C') ? 'selected' : '' ?>>C - Closed pit</option>
                        <option value="D" <?= ($toilet === 'D') ? 'selected' : '' ?>>D - Open pit</option>
                        <option value="E" <?= ($toilet === 'E') ? 'selected' : '' ?>>E - Shared</option>
                        <option value="F" <?= ($toilet === 'F') ? 'selected' : '' ?>>F - Open pit latrine</option>
                        <option value="G" <?= ($toilet === 'G') ? 'selected' : '' ?>>G - Without toilet</option>
                    </select>
                    <?php if ($reviewMode): ?>
                        <input type="hidden" name="toilet_facility" value="<?= esc($toilet) ?>">
                    <?php endif; ?>
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'toilet_facility')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'toilet_facility')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Remarks (optional)</label>
                    <textarea
                        class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'remarks') ?>"
                        name="remarks"
                        rows="2"
                        <?= $reviewMode ? 'readonly' : '' ?>
                    ><?= esc($remarks) ?></textarea>
                    <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'remarks')): ?>
                        <div class="review-old-value">
                            Old value: <?= esc(reviewOldValue($visitFieldDiff, 'remarks')) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Household Location Map</label>
                    <div class="border rounded p-3 bg-light">
                        <div class="row g-2 mb-2">
                            <div class="col-lg-3 col-md-6">
                                <input
                                    type="text"
                                    class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'household_latitude') ?>"
                                    name="household_latitude"
                                    id="household_latitude"
                                    value="<?= esc($oldLat) ?>"
                                    placeholder="Latitude (e.g. 9.8754321)"
                                    <?= $reviewMode ? 'readonly' : '' ?>
                                >
                                <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'household_latitude')): ?>
                                    <div class="review-old-value">
                                        Old value: <?= esc(reviewOldValue($visitFieldDiff, 'household_latitude')) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-lg-3 col-md-6">
                                <input
                                    type="text"
                                    class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'household_longitude') ?>"
                                    name="household_longitude"
                                    id="household_longitude"
                                    value="<?= esc($oldLng) ?>"
                                    placeholder="Longitude (e.g. 125.9876543)"
                                    <?= $reviewMode ? 'readonly' : '' ?>
                                >
                                <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'household_longitude')): ?>
                                    <div class="review-old-value">
                                        Old value: <?= esc(reviewOldValue($visitFieldDiff, 'household_longitude')) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-lg-2 col-md-6">
                                <input
                                    type="text"
                                    class="form-control <?= reviewFieldClass($reviewMode, $visitFieldDiff, 'geo_accuracy_m') ?>"
                                    name="household_location_accuracy"
                                    id="household_location_accuracy"
                                    value="<?= esc($oldLocAccuracy) ?>"
                                    placeholder="Accuracy (m)"
                                    readonly
                                >
                            </div>

                            <div class="col-lg-4 col-md-6 d-flex gap-2 flex-wrap">
                                <?php if (! $reviewMode): ?>
                                    <button type="button" class="btn btn-outline-primary" id="btnUseCurrentLocation">
                                        <i class="fa-solid fa-location-crosshairs"></i> Use Current Location
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" id="btnClearLocation">
                                        <i class="fa-solid fa-eraser"></i> Clear Pin
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>

                        <input type="hidden" name="household_location_source" id="household_location_source" value="<?= esc($oldLocSource) ?>">

                        <?php if ($reviewMode && reviewFieldChanged($visitFieldDiff, 'geo_source')): ?>
                            <div class="review-old-value mb-2">
                                Old source: <?= esc(reviewOldValue($visitFieldDiff, 'geo_source')) ?>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                            <div class="small text-muted">
                                <?php if ($reviewMode): ?>
                                    Map preview only.
                                <?php else: ?>
                                    Click the map to drop a household pin, drag the marker to adjust, or use your current device location.
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-secondary" id="household_location_status">
                                <?= esc($oldLocSource ?: 'No location pinned') ?>
                            </span>
                        </div>

                        <div id="household_map" class="household-map"></div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="mb-0">Family Groups (Occupants in this Household)</h5>

                <?php if (! $reviewMode): ?>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="addGroupBtn">
                        <i class="fa-solid fa-plus"></i> Add Family Group
                    </button>
                <?php endif; ?>
            </div>

            <div id="groupsWrap"></div>

            <?php if (! $reviewMode): ?>
                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a class="btn btn-outline-secondary" href="<?= base_url('admin/registry/household-profiling') ?>">
                        Cancel
                    </a>

                    <?php if ($mode === 'edit'): ?>
                        <button type="submit" name="submit_action" value="update_only" class="btn btn-outline-secondary">
                            Update Only
                        </button>
                        <button type="submit" name="submit_action" value="update_visit" class="btn btn-primary">
                            Update Visit
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-primary">
                            Save Visit
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (! $reviewMode): ?>
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
                                <tr>
                                    <td colspan="5" class="text-muted">Type to search...</td>
                                </tr>
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

    <div class="modal fade" id="medicalHistoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="medicalHistoryForm">
                    <div class="modal-header">
                        <h5 class="modal-title" id="medicalHistoryModalTitle">Add Medical History</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="mh_member_id" name="member_id">
                        <input type="hidden" id="mh_history_id" name="history_id">

                        <div class="mb-3">
                            <label class="form-label">Condition / Illness <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="mh_condition_name" name="condition_name" placeholder="e.g. Hypertension" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Date Diagnosed / Date Noted</label>
                            <input type="date" class="form-control" id="mh_date_diagnosed" name="date_diagnosed">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="mh_status" name="status">
                                <option value="">Select</option>
                                <option value="Active">Active</option>
                                <option value="Recovered">Recovered</option>
                                <option value="Under Treatment">Under Treatment</option>
                                <option value="Past History">Past History</option>
                            </select>
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" id="mh_remarks" name="remarks" rows="3" placeholder="Optional remarks"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="medicalHistorySaveBtn">
                            <i class="fa-solid fa-floppy-disk"></i> Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">

<style>
    .group-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
        margin-bottom: 12px;
    }

    .group-card .group-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
        flex-wrap: wrap;
    }

    .badge-linked {
        font-size: 12px;
    }

    .table-mini th,
    .table-mini td {
        vertical-align: top;
    }

    .member-actions {
        white-space: nowrap;
    }

    .w-110 {
        min-width: 110px;
    }

    .w-140 {
        min-width: 140px;
    }

    .w-180 {
        min-width: 180px;
    }

    .w-220 {
        min-width: 220px;
    }

    .qCell input {
        min-width: 88px;
    }

    .household-map {
        width: 100%;
        height: 360px;
        border-radius: 8px;
        overflow: hidden;
        background: #f8fafc;
    }

    .leaflet-container {
        font: inherit;
    }

    .medical-history-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .medical-history-item {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px 10px;
        background: #fff;
    }

    .medical-history-item .mh-head {
        display: flex;
        justify-content: space-between;
        align-items: start;
        gap: 8px;
        margin-bottom: 4px;
    }

    .medical-history-item .mh-title {
        font-weight: 600;
    }

    .medical-history-item .mh-meta {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.4;
    }

    .medical-history-item .mh-actions .btn {
        padding: 2px 6px;
    }

    .medical-history-empty {
        font-size: 12px;
        color: #6b7280;
    }

    .review-changed {
        border: 2px solid #f59e0b !important;
        background: #fff8e1 !important;
    }

    .review-old-value {
        font-size: 12px;
        color: #92400e;
        margin-top: 4px;
    }
</style>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
(function () {
    const reviewMode = <?= json_encode($reviewMode) ?>;
    const isSuperAdmin = <?= json_encode($isSuperAdmin) ?>;
    const barangayLocked = <?= json_encode(!empty($lock['barangay_locked'])) ?>;
    const municipalitySelect = document.getElementById('municipality_pcode');
    const selectedMunicipality = <?= json_encode($oldMunicipal) ?>;
    const brgySelect = document.getElementById('barangay_pcode');
    const selectedBarangay = <?= json_encode($oldBarangay) ?>;
    const initialLatitude = <?= json_encode($oldLat !== '' ? (float) $oldLat : null) ?>;
    const initialLongitude = <?= json_encode($oldLng !== '' ? (float) $oldLng : null) ?>;

    function loadMunicipalities() {
        if (!isSuperAdmin || reviewMode) return;

        const province = <?= json_encode((string) ($actor['province_pcode'] ?? '')) ?>;
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

    function loadBarangays() {
        if (reviewMode) return;

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

    if (!reviewMode && isSuperAdmin && municipalitySelect) {
        municipalitySelect.addEventListener('change', () => {
            brgySelect.value = '';
            loadBarangays();
        });
    }

    if (!reviewMode) {
        if (isSuperAdmin) {
            loadMunicipalities();
        } else if (!barangayLocked) {
            loadBarangays();
        }
    }

    function computeQuarter(dateStr) {
        if (!dateStr) return '';
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            const m = parseInt(dateStr.slice(5, 7), 10);
            return (m >= 1 && m <= 12) ? Math.ceil(m / 3) : '';
        }
        const dt = new Date(dateStr);
        if (isNaN(dt.getTime())) return '';
        return Math.ceil((dt.getMonth() + 1) / 3);
    }

    function updateQuarter() {
        const visitDateEl = document.getElementById('visit_date');
        const quarterEl = document.getElementById('visit_quarter');
        if (!visitDateEl || !quarterEl) return;

        const d = visitDateEl.value.trim();
        const q = computeQuarter(d);
        quarterEl.value = q ? ('Q' + q) : '';
    }

    const visitDateEl = document.getElementById('visit_date');
    if (visitDateEl && !reviewMode) {
        visitDateEl.addEventListener('change', updateQuarter);
        visitDateEl.addEventListener('input', updateQuarter);
    }
    updateQuarter();

    const respRel = document.getElementById('resp_relation');
    const respOtherWrap = document.getElementById('resp_relation_other_wrap');
    const ethMode = document.getElementById('ethnicity_mode');
    const tribeWrap = document.getElementById('tribe_wrap');
    const soc = document.getElementById('socioeconomic_status');
    const nhtsWrap = document.getElementById('nhts_wrap');
    const water = document.getElementById('water_source');
    const waterOtherWrap = document.getElementById('water_other_wrap');

    function toggleConditional() {
        if (respRel && respOtherWrap) {
            respOtherWrap.style.display = (respRel.value === 'other') ? '' : 'none';
        }
        if (ethMode && tribeWrap) {
            tribeWrap.style.display = (ethMode.value === 'tribe') ? '' : 'none';
        }
        if (soc && nhtsWrap) {
            nhtsWrap.style.display = String(soc.value || '').startsWith('nhts') ? '' : 'none';
        }
        if (water && waterOtherWrap) {
            waterOtherWrap.style.display = (water.value === 'others') ? '' : 'none';
        }
    }

    if (respRel && !reviewMode) respRel.addEventListener('change', toggleConditional);
    if (ethMode && !reviewMode) ethMode.addEventListener('change', toggleConditional);
    if (soc && !reviewMode) soc.addEventListener('change', toggleConditional);
    if (water && !reviewMode) water.addEventListener('change', toggleConditional);
    toggleConditional();

    const latInput = document.getElementById('household_latitude');
    const lngInput = document.getElementById('household_longitude');
    const accuracyInput = document.getElementById('household_location_accuracy');
    const sourceInput = document.getElementById('household_location_source');
    const locationStatus = document.getElementById('household_location_status');
    const useCurrentLocationBtn = document.getElementById('btnUseCurrentLocation');
    const clearLocationBtn = document.getElementById('btnClearLocation');

    const defaultCenter = [12.8797, 121.7740];
    const initialZoom = (initialLatitude !== null && initialLongitude !== null) ? 17 : 6;

    const householdMap = L.map('household_map', {
        dragging: !reviewMode,
        touchZoom: !reviewMode,
        doubleClickZoom: !reviewMode,
        scrollWheelZoom: !reviewMode,
        boxZoom: !reviewMode,
        keyboard: !reviewMode,
        zoomControl: true
    }).setView(
        (initialLatitude !== null && initialLongitude !== null) ? [initialLatitude, initialLongitude] : defaultCenter,
        initialZoom
    );

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 20,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(householdMap);

    let householdMarker = null;

    function setLocationStatus(text) {
        locationStatus.textContent = text || 'No location pinned';
    }

    function roundCoord(value) {
        return Number(value).toFixed(7);
    }

    function setMarker(lat, lng, source = '') {
        if (!householdMarker) {
            householdMarker = L.marker([lat, lng], { draggable: !reviewMode }).addTo(householdMap);

            if (!reviewMode) {
                householdMarker.on('dragend', () => {
                    const pos = householdMarker.getLatLng();
                    latInput.value = roundCoord(pos.lat);
                    lngInput.value = roundCoord(pos.lng);
                    sourceInput.value = 'map_drag';
                    setLocationStatus('Pinned via map drag');
                });
            }
        } else {
            householdMarker.setLatLng([lat, lng]);
        }

        latInput.value = roundCoord(lat);
        lngInput.value = roundCoord(lng);

        if (source) {
            sourceInput.value = source;
            if (source === 'browser_geolocation') {
                setLocationStatus('Pinned via current device location');
            } else if (source === 'manual_coordinates') {
                setLocationStatus('Pinned via manual coordinates');
            } else if (source === 'map_click') {
                setLocationStatus('Pinned via map click');
            } else if (source === 'saved_location') {
                setLocationStatus('Saved household location');
            } else {
                setLocationStatus(source);
            }
        }

        householdMap.setView([lat, lng], Math.max(householdMap.getZoom(), 17));
    }

    function clearMarker() {
        if (householdMarker) {
            householdMap.removeLayer(householdMarker);
            householdMarker = null;
        }
        latInput.value = '';
        lngInput.value = '';
        accuracyInput.value = '';
        sourceInput.value = '';
        setLocationStatus('No location pinned');
    }

    function syncMarkerFromInputs() {
        const lat = parseFloat((latInput.value || '').trim());
        const lng = parseFloat((lngInput.value || '').trim());

        if (Number.isFinite(lat) && Number.isFinite(lng) && lat >= -90 && lat <= 90 && lng >= -180 && lng <= 180) {
            accuracyInput.value = '';
            setMarker(lat, lng, 'manual_coordinates');
            return;
        }

        if ((latInput.value || '').trim() === '' && (lngInput.value || '').trim() === '') {
            clearMarker();
        }
    }

    if (!reviewMode) {
        householdMap.on('click', (e) => {
            accuracyInput.value = '';
            setMarker(e.latlng.lat, e.latlng.lng, 'map_click');
        });
    }

    if (useCurrentLocationBtn && !reviewMode) {
        useCurrentLocationBtn.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by this browser.');
                return;
            }

            useCurrentLocationBtn.disabled = true;
            useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    accuracyInput.value = Number.isFinite(accuracy) ? Number(accuracy).toFixed(2) : '';
                    setMarker(lat, lng, 'browser_geolocation');

                    useCurrentLocationBtn.disabled = false;
                    useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Use Current Location';
                },
                (err) => {
                    alert(err && err.message ? err.message : 'Unable to get your current location.');
                    useCurrentLocationBtn.disabled = false;
                    useCurrentLocationBtn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> Use Current Location';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
            );
        });
    }

    if (clearLocationBtn && !reviewMode) {
        clearLocationBtn.addEventListener('click', clearMarker);
    }

    if (!reviewMode) {
        latInput.addEventListener('change', syncMarkerFromInputs);
        lngInput.addEventListener('change', syncMarkerFromInputs);
        latInput.addEventListener('blur', syncMarkerFromInputs);
        lngInput.addEventListener('blur', syncMarkerFromInputs);
    }

    if (initialLatitude !== null && initialLongitude !== null) {
        setMarker(initialLatitude, initialLongitude, <?= json_encode($oldLocSource ?: 'saved_location') ?>);
        <?php if ($oldLocAccuracy !== ''): ?>
            accuracyInput.value = <?= json_encode((string) $oldLocAccuracy) ?>;
        <?php endif; ?>
    } else {
        setLocationStatus(<?= json_encode($oldLocSource ?: 'No location pinned') ?>);
    }

    setTimeout(() => householdMap.invalidateSize(), 200);

    const seedGroups = <?= json_encode($seedGroups) ?>;
    const groupsWrap = document.getElementById('groupsWrap');
    const addGroupBtn = document.getElementById('addGroupBtn');

    let linkTarget = { gIndex: null };

    const livingStatusOptions = [
        ['owner_occupant', 'Owner-Occupant'],
        ['renter_tenant', 'Renter / Tenant'],
        ['boarder_lodger', 'Boarder / Lodger'],
        ['temporary_stay', 'Temporary Stay'],
        ['relative_staying_in', 'Relative Staying In'],
        ['caretaker', 'Caretaker'],
        ['co_occupant', 'Sharer / Co-occupant'],
        ['other', 'Other'],
    ];

    const memberStatusOptions = [
        ['primary_resident', 'Primary Resident'],
        ['temporary_resident', 'Temporary Resident'],
        ['boarder', 'Boarder'],
        ['tenant', 'Tenant'],
        ['visitor', 'Visitor / Short-term'],
        ['unknown', 'Unknown / For Validation'],
    ];

    const educOptions = [
        ['', '—'],
        ['N', 'None'], ['K', 'Kinder'], ['ES', 'Elem Student'], ['EU', 'Elem Undergrad'], ['EG', 'Elem Graduate'],
        ['HS', 'HS Student'], ['HU', 'HS Undergrad'], ['HG', 'HS Graduate'],
        ['V', 'Vocational Course'],
        ['CS', 'College Student'], ['CU', 'College Undergrad'], ['CG', 'College Graduate'],
        ['PG', 'Postgraduate']
    ];

    function optHtml(opts, selected) {
        return opts.map(([v, label]) => `<option value="${v}" ${String(v) === String(selected) ? 'selected' : ''}>${label}</option>`).join('');
    }

    function classFromAgeSex(age, sex) {
        if (!age || age <= 0) return '';
        if (age <= 4) return 'U';
        if (age >= 5 && age <= 9) return 'S';
        if (age >= 10 && age <= 19) return 'A';
        if (age >= 20) return 'AB';
        return '';
    }

    function memberRowTemplate(gIndex, mIndex, data) {
        const linked = !!data.linked_member_id;
        const medicalHistories = Array.isArray(data.medical_histories) ? data.medical_histories : [];

        return `
            <tr class="member-row" data-g="${gIndex}" data-m="${mIndex}">
                <td class="w-220">
                    <input type="hidden" name="groups[${gIndex}][members][${mIndex}][linked_member_id]" value="${data.linked_member_id || ''}" class="linkedMemberId">

                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <strong>${linked ? '<span class="badge bg-success badge-linked">LINKED</span>' : '<span class="badge bg-secondary badge-linked">NEW</span>'}</strong>
                        ${linked && !reviewMode ? '<button type="button" class="btn btn-sm btn-outline-secondary js-unlink">Unlink</button>' : ''}
                    </div>

                    <input class="form-control form-control-sm mb-1 localLastName" name="groups[${gIndex}][members][${mIndex}][local_last_name]" placeholder="Last" value="${data.local_last_name || ''}" ${(linked || reviewMode) ? 'readonly' : 'required'}>
                    <input class="form-control form-control-sm mb-1 localFirstName" name="groups[${gIndex}][members][${mIndex}][local_first_name]" placeholder="First" value="${data.local_first_name || ''}" ${(linked || reviewMode) ? 'readonly' : 'required'}>
                    <input class="form-control form-control-sm localMiddleName" name="groups[${gIndex}][members][${mIndex}][local_middle_name]" placeholder="Middle" value="${data.local_middle_name || ''}" ${(linked || reviewMode) ? 'readonly' : ''}>
                </td>

                <td class="w-140">
                    <select class="form-select form-select-sm mb-1 relSel" name="groups[${gIndex}][members][${mIndex}][relationship_code]" ${reviewMode ? 'disabled' : ''}>
                        <option value="">—</option>
                        <option value="1" ${String(data.relationship_code || '') === '1' ? 'selected' : ''}>1 - Head</option>
                        <option value="2" ${String(data.relationship_code || '') === '2' ? 'selected' : ''}>2 - Spouse</option>
                        <option value="3" ${String(data.relationship_code || '') === '3' ? 'selected' : ''}>3 - Son</option>
                        <option value="4" ${String(data.relationship_code || '') === '4' ? 'selected' : ''}>4 - Daughter</option>
                        <option value="5" ${String(data.relationship_code || '') === '5' ? 'selected' : ''}>5 - Others</option>
                    </select>
                    ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][relationship_code]" value="${data.relationship_code || ''}">` : ''}
                    <input class="form-control form-control-sm relOther" name="groups[${gIndex}][members][${mIndex}][relationship_other]" placeholder="If Others, specify"
                        value="${data.relationship_other || ''}" style="display:${String(data.relationship_code || '') === '5' ? '' : 'none'};" ${reviewMode ? 'readonly' : ''}>
                </td>

                <td class="w-110">
                    <select class="form-select form-select-sm sexSel" name="groups[${gIndex}][members][${mIndex}][sex]" ${(linked || reviewMode) ? 'disabled' : 'required'}>
                        <option value="">—</option>
                        <option value="M" ${data.sex === 'M' ? 'selected' : ''}>Male</option>
                        <option value="F" ${data.sex === 'F' ? 'selected' : ''}>Female</option>
                    </select>
                    ${(linked || reviewMode) ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][sex]" value="${data.sex || ''}">` : ''}
                </td>

                <td class="w-140">
                    <input type="date" class="form-control form-control-sm dobSel" name="groups[${gIndex}][members][${mIndex}][dob]" value="${data.dob || ''}" ${(linked || reviewMode) ? 'readonly' : 'required'}>
                </td>

                <td class="w-140">
                    <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][civil_status]" ${reviewMode ? 'disabled' : ''}>
                        <option value="">Select</option>
                        <option value="single" ${data.civil_status === 'single' ? 'selected' : ''}>Single</option>
                        <option value="married" ${data.civil_status === 'married' ? 'selected' : ''}>Married</option>
                        <option value="widowed" ${data.civil_status === 'widowed' ? 'selected' : ''}>Widowed</option>
                        <option value="separated" ${data.civil_status === 'separated' ? 'selected' : ''}>Separated</option>
                        <option value="live_in" ${data.civil_status === 'live_in' ? 'selected' : ''}>Live-in / Cohabiting</option>
                    </select>
                    ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][civil_status]" value="${data.civil_status || ''}">` : ''}
                </td>

                <td class="w-180">
                    <select class="form-select form-select-sm mb-1" name="groups[${gIndex}][members][${mIndex}][status_in_household]" ${reviewMode ? 'disabled' : ''}>
                        ${optHtml(memberStatusOptions, data.status_in_household || '')}
                    </select>
                    ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][status_in_household]" value="${data.status_in_household || ''}">` : ''}

                    <div class="d-flex gap-1">
                        <input type="date" class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][stay_from]" value="${data.stay_from || ''}" title="Stay from" ${reviewMode ? 'readonly' : ''}>
                        <input type="date" class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][stay_to]" value="${data.stay_to || ''}" title="Stay to" ${reviewMode ? 'readonly' : ''}>
                    </div>
                </td>

                <td class="w-180">
                    <input class="form-control form-control-sm mb-1" name="groups[${gIndex}][members][${mIndex}][philhealth_id]" placeholder="PhilHealth ID" value="${data.philhealth_id || ''}" ${reviewMode ? 'readonly' : ''}>

                    <div class="d-flex gap-1">
                        <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][membership_type]" ${reviewMode ? 'disabled' : ''}>
                            <option value="">—Type—</option>
                            <option value="member" ${data.membership_type === 'member' ? 'selected' : ''}>Member</option>
                            <option value="dependent" ${data.membership_type === 'dependent' ? 'selected' : ''}>Dependent</option>
                        </select>
                        ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][membership_type]" value="${data.membership_type || ''}">` : ''}

                        <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][philhealth_category]" ${reviewMode ? 'disabled' : ''}>
                            <option value="">—Cat—</option>
                            <option value="FEP" ${data.philhealth_category === 'FEP' ? 'selected' : ''}>FEP</option>
                            <option value="FEG" ${data.philhealth_category === 'FEG' ? 'selected' : ''}>FEG</option>
                            <option value="IE" ${data.philhealth_category === 'IE' ? 'selected' : ''}>IE</option>
                            <option value="N" ${data.philhealth_category === 'N' ? 'selected' : ''}>N</option>
                            <option value="SC" ${data.philhealth_category === 'SC' ? 'selected' : ''}>SC</option>
                            <option value="IP" ${data.philhealth_category === 'IP' ? 'selected' : ''}>IP</option>
                            <option value="U" ${data.philhealth_category === 'U' ? 'selected' : ''}>U</option>
                        </select>
                        ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][philhealth_category]" value="${data.philhealth_category || ''}">` : ''}
                    </div>
                </td>

                <td class="w-220">
                    <input type="hidden" name="groups[${gIndex}][members][${mIndex}][id]" value="${data.id || ''}" class="groupMemberId">

                    ${data.id ? `
                        <div class="medical-history-list js-medical-history-list" data-member-id="${data.id}">
                            ${renderMedicalHistoryItems(medicalHistories)}
                        </div>
                        ${!reviewMode ? `
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary js-add-medical-history" data-member-id="${data.id}">
                                    <i class="fa-solid fa-plus"></i> Add
                                </button>
                            </div>
                        ` : ''}
                    ` : `
                        <div class="medical-history-empty">
                            Save the household record first, then reopen Edit to manage medical history.
                        </div>
                    `}
                </td>

                <td class="w-140">
                    <input type="date" class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][lmp_date]" value="${data.lmp_date || ''}" ${reviewMode ? 'readonly' : ''}>
                </td>

                <td class="w-140">
                    <select class="form-select form-select-sm" name="groups[${gIndex}][members][${mIndex}][educ_attainment]" ${reviewMode ? 'disabled' : ''}>
                        ${optHtml(educOptions, data.educ_attainment || '')}
                    </select>
                    ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][members][${mIndex}][educ_attainment]" value="${data.educ_attainment || ''}">` : ''}
                </td>

                <td class="w-140">
                    <input class="form-control form-control-sm" name="groups[${gIndex}][members][${mIndex}][religion]" value="${data.religion || ''}" placeholder="Religion" ${reviewMode ? 'readonly' : ''}>
                </td>

                <td class="w-180 qCell">
                    ${[1, 2, 3, 4].map(q => `
                        <div class="d-flex gap-1 mb-1">
                            <input class="form-control form-control-sm qAge" name="groups[${gIndex}][members][${mIndex}][q${q}_age]" placeholder="Q${q} Age" value="${data['q' + q + '_age'] || ''}" ${reviewMode ? 'readonly' : ''}>
                            <input class="form-control form-control-sm qClass" name="groups[${gIndex}][members][${mIndex}][q${q}_class]" placeholder="Class" value="${data['q' + q + '_class'] || ''}" readonly>
                        </div>
                    `).join('')}
                </td>

                <td class="member-actions">
                    ${!reviewMode ? '<button type="button" class="btn btn-sm btn-outline-danger js-remove-member">Remove</button>' : ''}
                </td>
            </tr>
        `;
    }

    function groupTemplate(gIndex, data) {
        return `
            <div class="group-card" data-g="${gIndex}">
                <div class="group-head">
                    <div class="row g-2 flex-grow-1">
                        <div class="col-lg-4 col-md-12">
                            <label class="form-label mb-1">Group Name (optional)</label>
                            <input class="form-control" name="groups[${gIndex}][group_name]" value="${data.group_name || ''}" placeholder="e.g., Owner Family, Renter Family 1" ${reviewMode ? 'readonly' : ''}>
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <label class="form-label mb-1">Living Status</label>
                            <select class="form-select" name="groups[${gIndex}][living_status]" ${reviewMode ? 'disabled' : 'required'}>
                                <option value="">Select</option>
                                ${optHtml(livingStatusOptions, data.living_status || '')}
                            </select>
                            ${reviewMode ? `<input type="hidden" name="groups[${gIndex}][living_status]" value="${data.living_status || ''}">` : ''}
                        </div>

                        <div class="col-lg-4 col-md-12">
                            <label class="form-label mb-1">Notes (optional)</label>
                            <input class="form-control" name="groups[${gIndex}][notes]" value="${data.notes || ''}" placeholder="e.g., Apt A, 2nd floor room" ${reviewMode ? 'readonly' : ''}>
                        </div>
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        ${!reviewMode ? '<button type="button" class="btn btn-sm btn-outline-primary js-add-member">+ Add New Member</button>' : ''}
                        ${!reviewMode ? '<button type="button" class="btn btn-sm btn-outline-success js-link-member">+ Link Existing</button>' : ''}
                        ${!reviewMode ? '<button type="button" class="btn btn-sm btn-outline-danger js-remove-group">Remove Group</button>' : ''}
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

    function renumberAll() {
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

    function addGroup(seed) {
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
                medical_histories: [],
                relationship_code: '',
                sex: '',
            }));
        }

        renumberAll();
    }

    if (addGroupBtn && !reviewMode) {
        addGroupBtn.addEventListener('click', () => {
            addGroup({ living_status: 'owner_occupant', members: [] });
        });
    }

    function escapeHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/"/g, '&quot;');
    }

    function formatDisplayDate(ymd) {
        if (!ymd) return '-';
        const d = new Date(ymd + 'T00:00:00');
        if (isNaN(d.getTime())) return ymd;
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        const yy = d.getFullYear();
        return `${mm}/${dd}/${yy}`;
    }

    function renderMedicalHistoryItems(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return `<div class="medical-history-empty">No medical history recorded.</div>`;
        }

        return rows.map(row => `
            <div class="medical-history-item" data-history-id="${row.id}">
                <div class="mh-head">
                    <div class="mh-title">${escapeHtml(row.condition_name || '')}</div>
                    <div class="mh-actions d-flex gap-1">
                        ${!reviewMode ? `
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary js-edit-medical-history"
                                    data-history-id="${row.id}"
                                    data-condition="${escapeHtml(row.condition_name || '')}"
                                    data-date="${escapeHtml(row.date_diagnosed || '')}"
                                    data-status="${escapeHtml(row.status || '')}"
                                    data-remarks="${escapeHtml(row.remarks || '')}">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                            <button type="button"
                                    class="btn btn-sm btn-outline-danger js-delete-medical-history"
                                    data-history-id="${row.id}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
                <div class="mh-meta">
                    <div><strong>Date:</strong> ${escapeHtml(formatDisplayDate(row.date_diagnosed))}</div>
                    <div><strong>Status:</strong> ${escapeHtml(row.status || '-')}</div>
                    <div><strong>Remarks:</strong> ${escapeHtml(row.remarks || '-')}</div>
                </div>
            </div>
        `).join('');
    }

    function updateMedicalHistoryList(memberId, rows) {
        const list = groupsWrap.querySelector(`.js-medical-history-list[data-member-id="${memberId}"]`);
        if (!list) return;
        list.innerHTML = renderMedicalHistoryItems(rows || []);
    }

    if (!reviewMode) {
        groupsWrap.addEventListener('click', (e) => {
            const card = e.target.closest('.group-card');
            if (!card) return;
            const gIndex = parseInt(card.dataset.g, 10);
            const tbody = card.querySelector('.groupMembersBody');

            const memberRow = e.target.closest('tr.member-row');
            const memberIdInput = memberRow ? memberRow.querySelector('.groupMemberId') : null;
            const memberId = memberIdInput ? String(memberIdInput.value || '').trim() : '';

            if (e.target.closest('.js-remove-group')) {
                if (!confirm('Remove this Family Group and all its members?')) return;
                card.remove();
                renumberAll();
                return;
            }

            if (e.target.closest('.js-add-member')) {
                const mIndex = tbody.querySelectorAll('tr.member-row').length;
                tbody.insertAdjacentHTML('beforeend', memberRowTemplate(gIndex, mIndex, {
                    medical_histories: [],
                    relationship_code: '',
                    sex: '',
                }));
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
                    medical_histories: [],
                    relationship_code: '',
                    sex: '',
                });
                renumberAll();
                return;
            }

            if (e.target.closest('.js-add-medical-history')) {
                const btn = e.target.closest('.js-add-medical-history');
                const memberId = btn.dataset.memberId || '';
                if (!memberId) {
                    alert('Please save the household record first before adding medical history.');
                    return;
                }
                openAddMedicalHistoryModal(memberId);
                return;
            }

            if (e.target.closest('.js-edit-medical-history')) {
                const btn = e.target.closest('.js-edit-medical-history');
                if (!memberId) {
                    alert('Member record is not yet saved.');
                    return;
                }
                openEditMedicalHistoryModal(memberId, btn);
                return;
            }

            if (e.target.closest('.js-delete-medical-history')) {
                const btn = e.target.closest('.js-delete-medical-history');
                const historyId = btn.dataset.historyId || '';

                if (!memberId || !historyId) {
                    alert('Medical history record not found.');
                    return;
                }

                if (!confirm('Are you sure you want to delete this medical history record?')) {
                    return;
                }

                const formData = new FormData();
                formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

                fetch(`<?= base_url('admin/registry/household-profiling/medical-history') ?>/${historyId}/delete`, {
                    method: 'POST',
                    body: formData
                })
                    .then(async r => {
                        const res = await r.json();
                        if (!r.ok || !res.ok) {
                            throw new Error(res && res.message ? res.message : 'Failed to delete medical history.');
                        }
                        return res;
                    })
                    .then(() => {
                        loadMedicalHistories(memberId);
                    })
                    .catch(err => {
                        alert(err.message || 'Failed to delete medical history.');
                    });

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
    }

    function bindQuarterAutoCompute() {
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

                if (!reviewMode) {
                    ageInput.addEventListener('input', recalcOne);
                    ageInput.addEventListener('change', recalcOne);
                }
                ageInput.dataset.bound = '1';

                const age = parseInt(ageInput.value || '0', 10);
                const sex = sexSel ? (sexSel.value || '') : '';
                qClasses[idx].value = age > 0 ? classFromAgeSex(age, sex) : '';
            });

            if (sexSel && sexSel.dataset.bound !== '1') {
                if (!reviewMode) {
                    sexSel.addEventListener('change', () => {
                        qAges.forEach((ageInput, idx) => {
                            const age = parseInt(ageInput.value || '0', 10);
                            qClasses[idx].value = age > 0 ? classFromAgeSex(age, sexSel.value || '') : '';
                        });
                    });
                }
                sexSel.dataset.bound = '1';
            }
        });
    }

    if (!reviewMode) {
        const linkSearchInput = document.getElementById('linkSearchInput');
        const linkResultsBody = document.getElementById('linkResultsBody');

        function renderLinkResults(rows) {
            if (!rows || rows.length === 0) {
                linkResultsBody.innerHTML = `<tr><td colspan="5" class="text-muted">No results.</td></tr>`;
                return;
            }

            linkResultsBody.innerHTML = rows.map(r => `
                <tr>
                    <td>
                        <strong>${escapeHtml(r.name)}</strong>
                        <div class="text-muted small">#${r.id} | Visit ${r.visit_id} (${escapeHtml(r.visit_date || '')})</div>
                    </td>
                    <td>${escapeHtml(r.sex || '')}</td>
                    <td>${escapeHtml(r.dob || '')}</td>
                    <td class="small">
                        <div><strong>Muni:</strong> ${escapeHtml(r.municipality_pcode || '')}</div>
                        <div><strong>Brgy:</strong> ${escapeHtml(r.barangay_pcode || '')}</div>
                        <div><strong>Sitio:</strong> ${escapeHtml(r.sitio_purok || '')}</div>
                        <div><strong>HH#:</strong> ${escapeHtml(r.household_no || '')}</div>
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-sm btn-success js-pick-link"
                                data-id="${r.id}"
                                data-name="${escapeAttr(r.name)}"
                                data-sex="${escapeAttr(r.sex || '')}"
                                data-dob="${escapeAttr(r.dob || '')}">
                            Select
                        </button>
                    </td>
                </tr>
            `).join('');
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
                medical_histories: [],
            }));

            renumberAll();

            const modalEl = document.getElementById('linkMemberModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        });

        const medicalHistoryModalEl = document.getElementById('medicalHistoryModal');
        const medicalHistoryModal = new bootstrap.Modal(medicalHistoryModalEl);
        const medicalHistoryForm = document.getElementById('medicalHistoryForm');

        function resetMedicalHistoryForm() {
            medicalHistoryForm.reset();
            document.getElementById('mh_member_id').value = '';
            document.getElementById('mh_history_id').value = '';
            document.getElementById('medicalHistoryModalTitle').textContent = 'Add Medical History';
        }

        function openAddMedicalHistoryModal(memberId) {
            resetMedicalHistoryForm();
            document.getElementById('mh_member_id').value = memberId;
            document.getElementById('medicalHistoryModalTitle').textContent = 'Add Medical History';
            medicalHistoryModal.show();
        }

        function openEditMedicalHistoryModal(memberId, button) {
            resetMedicalHistoryForm();
            document.getElementById('mh_member_id').value = memberId;
            document.getElementById('mh_history_id').value = button.dataset.historyId || '';
            document.getElementById('mh_condition_name').value = button.dataset.condition || '';
            document.getElementById('mh_date_diagnosed').value = button.dataset.date || '';
            document.getElementById('mh_status').value = button.dataset.status || '';
            document.getElementById('mh_remarks').value = button.dataset.remarks || '';
            document.getElementById('medicalHistoryModalTitle').textContent = 'Edit Medical History';
            medicalHistoryModal.show();
        }

        function loadMedicalHistories(memberId) {
            fetch(`<?= base_url('admin/registry/household-profiling/member') ?>/${memberId}/medical-histories`)
                .then(r => r.json())
                .then(res => {
                    if (!res || !res.ok) return;
                    updateMedicalHistoryList(memberId, res.rows || []);
                })
                .catch(() => {});
        }

        medicalHistoryForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const memberId = document.getElementById('mh_member_id').value;
            if (!memberId) return;

            const formData = new FormData(medicalHistoryForm);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

            fetch(`<?= base_url('admin/registry/household-profiling/member') ?>/${memberId}/medical-histories/save`, {
                method: 'POST',
                body: formData
            })
                .then(async r => {
                    const res = await r.json();
                    if (!r.ok || !res.ok) {
                        throw new Error(res && res.message ? res.message : 'Failed to save medical history.');
                    }
                    return res;
                })
                .then(() => {
                    medicalHistoryModal.hide();
                    loadMedicalHistories(memberId);
                })
                .catch(err => {
                    alert(err.message || 'Failed to save medical history.');
                });
        });

        window.openAddMedicalHistoryModal = openAddMedicalHistoryModal;
        window.openEditMedicalHistoryModal = openEditMedicalHistoryModal;
        window.loadMedicalHistories = loadMedicalHistories;
    }

    if (Array.isArray(seedGroups) && seedGroups.length) {
        seedGroups.forEach(g => addGroup(g));
    } else {
        addGroup({ living_status: 'owner_occupant', members: [] });
    }
})();
</script>

<?= $this->endSection() ?>