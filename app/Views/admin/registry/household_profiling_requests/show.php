<?= $this->extend('layouts/admin') ?>
<?= $this->section('content') ?>

<?php
    $diff = $row['diff_payload'] ?? [];
    $summaryItems = $diff['summary_items'] ?? [];

    function changeBadgeClass(string $type): string
    {
        return match ($type) {
            'added_group',
            'added_member',
            'added_medical_history' => 'bg-success',

            'updated_group',
            'updated_member',
            'updated_medical_history' => 'bg-warning text-dark',

            'removed_group',
            'removed_member',
            'removed_medical_history' => 'bg-danger',

            default => 'bg-secondary',
        };
    }

    function changeRowClass(string $type): string
    {
        return match ($type) {
            'added_group',
            'added_member',
            'added_medical_history' => 'table-success',

            'updated_group',
            'updated_member',
            'updated_medical_history' => 'table-warning',

            'removed_group',
            'removed_member',
            'removed_medical_history' => 'table-danger',

            default => '',
        };
    }

    function changeLabel(string $type): string
    {
        return match ($type) {
            'added_group' => 'Added Group',
            'updated_group' => 'Updated Group',
            'removed_group' => 'Removed Group',

            'added_member' => 'Added Member',
            'updated_member' => 'Updated Member',
            'removed_member' => 'Removed Member',

            'added_medical_history' => 'Added Medical History',
            'updated_medical_history' => 'Updated Medical History',
            'removed_medical_history' => 'Removed Medical History',

            default => ucwords(str_replace('_', ' ', $type)),
        };
    }

    function fieldLabel(string $field): string
    {
        return match ($field) {
            'visit_date' => 'Visit Date',
            'visit_quarter' => 'Visit Quarter',
            'municipality_pcode' => 'Municipality',
            'barangay_pcode' => 'Barangay',
            'sitio_purok' => 'Sitio / Purok',
            'household_no' => 'Household No.',
            'household_latitude' => 'Latitude',
            'household_longitude' => 'Longitude',
            'geo_source' => 'Location Source',
            'geo_accuracy_m' => 'Location Accuracy',
            'respondent_last_name' => 'Respondent Last Name',
            'respondent_first_name' => 'Respondent First Name',
            'respondent_middle_name' => 'Respondent Middle Name',
            'respondent_relation' => 'Respondent Relation',
            'respondent_relation_other' => 'Respondent Relation Other',
            'ethnicity_mode' => 'Ethnicity',
            'ethnicity_tribe' => 'Tribe',
            'socioeconomic_status' => 'Socioeconomic Status',
            'nhts_no' => 'NHTS No.',
            'water_source' => 'Water Source',
            'water_source_other' => 'Water Source Other',
            'toilet_facility' => 'Toilet Facility',
            'group_name' => 'Group Name',
            'living_status' => 'Living Status',
            'notes' => 'Notes',
            'local_last_name' => 'Last Name',
            'local_first_name' => 'First Name',
            'local_middle_name' => 'Middle Name',
            'relationship_code' => 'Relationship Code',
            'relationship_other' => 'Relationship Other',
            'sex' => 'Sex',
            'dob' => 'Date of Birth',
            'civil_status' => 'Civil Status',
            'philhealth_id' => 'PhilHealth ID',
            'membership_type' => 'Membership Type',
            'philhealth_category' => 'PhilHealth Category',
            'lmp_date' => 'LMP Date',
            'educ_attainment' => 'Educational Attainment',
            'religion' => 'Religion',
            'status_in_household' => 'Status in Household',
            'stay_from' => 'Stay From',
            'stay_to' => 'Stay To',
            'remarks' => 'Remarks',
            'q1_age' => 'Q1 Age',
            'q1_class' => 'Q1 Class',
            'q2_age' => 'Q2 Age',
            'q2_class' => 'Q2 Class',
            'q3_age' => 'Q3 Age',
            'q3_class' => 'Q3 Class',
            'q4_age' => 'Q4 Age',
            'q4_class' => 'Q4 Class',
            'condition_name' => 'Condition / Illness',
            'date_diagnosed' => 'Date Diagnosed',
            'status' => 'Status',
            default => ucwords(str_replace('_', ' ', $field)),
        };
    }

    function areaNameByPcodeView(?string $pcode): string
    {
        if (! $pcode) {
            return '';
        }

        $db = \Config\Database::connect();
        $row = $db->table('admin_areas')
            ->select('name')
            ->where('pcode', $pcode)
            ->get()
            ->getRowArray();

        return $row['name'] ?? (string) $pcode;
    }

    function codedValueLabel(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $value = (string) $value;

        return match ($field) {
            'relationship_code' => match ($value) {
                '1' => '1 - Head',
                '2' => '2 - Spouse',
                '3' => '3 - Son',
                '4' => '4 - Daughter',
                '5' => '5 - Others',
                default => $value,
            },

            'water_source' => match ($value) {
                'piped' => 'Piped water',
                'deep_well' => 'Deep well',
                'shallow_well' => 'Shallow well',
                'spring' => 'Spring',
                'others' => 'Others',
                default => $value,
            },

            'toilet_facility' => match ($value) {
                'A' => 'A - Water-sealed',
                'B' => 'B - Pour flush',
                'C' => 'C - Closed pit',
                'D' => 'D - Open pit',
                'E' => 'E - Shared',
                'F' => 'F - Open pit latrine',
                'G' => 'G - Without toilet',
                default => $value,
            },

            'civil_status' => match ($value) {
                'single' => 'Single',
                'married' => 'Married',
                'widowed' => 'Widowed',
                'separated' => 'Separated',
                'live_in' => 'Live-in / Cohabiting',
                default => $value,
            },

            'ethnicity_mode' => match ($value) {
                'ip' => 'IP Household',
                'tribe' => 'If IP Household, indicate Tribe',
                default => $value,
            },

            'socioeconomic_status' => match ($value) {
                'nhts' => 'NHTS',
                'nhts_4ps' => 'NHTS + 4Ps',
                'non_nhts' => 'Non-NHTS',
                default => $value,
            },

            'membership_type' => match ($value) {
                'member' => 'Member',
                'dependent' => 'Dependent',
                default => $value,
            },

            'philhealth_category' => match ($value) {
                'FEP' => 'FEP',
                'FEG' => 'FEG',
                'IE' => 'IE',
                'N' => 'N',
                'SC' => 'SC',
                'IP' => 'IP',
                'U' => 'U',
                default => $value,
            },

            'status_in_household' => match ($value) {
                'primary_resident' => 'Primary Resident',
                'temporary_resident' => 'Temporary Resident',
                'boarder' => 'Boarder',
                'tenant' => 'Tenant',
                'visitor' => 'Visitor / Short-term',
                'unknown' => 'Unknown / For Validation',
                default => $value,
            },

            'living_status' => match ($value) {
                'owner_occupant' => 'Owner-Occupant',
                'renter_tenant' => 'Renter / Tenant',
                'boarder_lodger' => 'Boarder / Lodger',
                'temporary_stay' => 'Temporary Stay',
                'relative_staying_in' => 'Relative Staying In',
                'caretaker' => 'Caretaker',
                'co_occupant' => 'Sharer / Co-occupant',
                'other' => 'Other',
                default => $value,
            },

            'sex' => match ($value) {
                'M' => 'Male',
                'F' => 'Female',
                default => $value,
            },

            'status' => match ($value) {
                'Active' => 'Active',
                'Recovered' => 'Recovered',
                'Under Treatment' => 'Under Treatment',
                'Past History' => 'Past History',
                default => $value,
            },

            default => $value,
        };
    }

    function displayDiffValue(string $field, $value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        if ($field === 'municipality_pcode' || $field === 'barangay_pcode') {
            return areaNameByPcodeView((string) $value);
        }

        if (in_array($field, [
            'relationship_code',
            'water_source',
            'toilet_facility',
            'civil_status',
            'ethnicity_mode',
            'socioeconomic_status',
            'membership_type',
            'philhealth_category',
            'status_in_household',
            'living_status',
            'sex',
            'status',
        ], true)) {
            return codedValueLabel($field, $value);
        }

        if (in_array($field, ['visit_date', 'dob', 'stay_from', 'stay_to', 'lmp_date', 'date_diagnosed'], true)) {
            $ts = strtotime((string) $value);
            if ($ts) {
                return date('m/d/Y', $ts);
            }
        }

        return (string) $value;
    }
?>

<style>
    .change-legend {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .summary-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .summary-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid #dbeafe;
        background: #eff6ff;
        color: #1d4ed8;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 12px;
        font-weight: 600;
    }

    .change-card-label {
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .mini-change-box {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 10px 12px;
        background: #fff;
    }

    .old-val {
        color: #6b7280;
    }

    .new-val {
        font-weight: 600;
    }
</style>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <strong>Request Summary</strong>
        <a href="<?= base_url('admin/registry/household-profiling-requests') ?>" class="btn btn-sm btn-outline-secondary">Back</a>
    </div>

    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <strong>Type:</strong> <?= esc($row['request_type']) ?>
            </div>
            <div class="col-md-3">
                <strong>Status:</strong> <?= esc($row['status']) ?>
            </div>
            <div class="col-md-3">
                <strong>Review Level:</strong> <?= esc($row['review_level'] ?? '-') ?>
            </div>
            <div class="col-md-3">
                <strong>Created:</strong> <?= esc($row['created_at']) ?>
            </div>
            <div class="col-md-12">
                <strong>Summary:</strong> <?= esc($row['summary_text']) ?>
            </div>
        </div>

        <?php if (!empty($summaryItems) && is_array($summaryItems)): ?>
            <div class="mb-3">
                <div class="fw-semibold mb-2">Quick Summary</div>
                <div class="summary-chips">
                    <?php foreach ($summaryItems as $item): ?>
                        <span class="summary-chip"><?= esc((string) $item) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="change-legend">
            <span class="badge bg-success">Added</span>
            <span class="badge bg-warning text-dark">Updated</span>
            <span class="badge bg-danger">Removed</span>
        </div>
    </div>
</div>

<?php if (!empty($diff['visit_fields'])): ?>
    <div class="card mb-3">
        <div class="card-header">
            <strong>Visit Field Changes</strong>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:220px;">Field</th>
                        <th>Old</th>
                        <th>New</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diff['visit_fields'] as $field => $change): ?>
                        <tr class="table-warning">
                            <td><?= esc(fieldLabel((string) $field)) ?></td>
                            <td class="old-val"><?= esc(displayDiffValue((string) $field, $change['old'] ?? '')) ?></td>
                            <td class="new-val"><?= esc(displayDiffValue((string) $field, $change['new'] ?? '')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($diff['groups'])): ?>
    <div class="card mb-3">
        <div class="card-header">
            <strong>Family Group Changes</strong>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:180px;">Action</th>
                        <th>Group</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diff['groups'] as $g): ?>
                        <tr class="<?= esc(changeRowClass((string) ($g['type'] ?? ''))) ?>">
                            <td>
                                <span class="badge <?= esc(changeBadgeClass((string) ($g['type'] ?? ''))) ?>">
                                    <?= esc(changeLabel((string) ($g['type'] ?? ''))) ?>
                                </span>
                            </td>
                            <td><?= esc($g['group_name'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($g['changes']) && is_array($g['changes'])): ?>
                                    <div class="row g-2">
                                        <?php foreach ($g['changes'] as $field => $change): ?>
                                            <div class="col-md-6">
                                                <div class="mini-change-box">
                                                    <div class="change-card-label"><?= esc(fieldLabel((string) $field)) ?></div>
                                                    <div class="old-val">Old: <?= esc(displayDiffValue((string) $field, $change['old'] ?? '')) ?></div>
                                                    <div class="new-val">New: <?= esc(displayDiffValue((string) $field, $change['new'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($diff['members'])): ?>
    <div class="card mb-3">
        <div class="card-header">
            <strong>Member Changes</strong>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:180px;">Action</th>
                        <th style="width:260px;">Member</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diff['members'] as $m): ?>
                        <tr class="<?= esc(changeRowClass((string) ($m['type'] ?? ''))) ?>">
                            <td>
                                <span class="badge <?= esc(changeBadgeClass((string) ($m['type'] ?? ''))) ?>">
                                    <?= esc(changeLabel((string) ($m['type'] ?? ''))) ?>
                                </span>
                            </td>
                            <td><?= esc($m['member_name'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($m['changes']) && is_array($m['changes'])): ?>
                                    <div class="row g-2">
                                        <?php foreach ($m['changes'] as $field => $change): ?>
                                            <div class="col-md-6">
                                                <div class="mini-change-box">
                                                    <div class="change-card-label"><?= esc(fieldLabel((string) $field)) ?></div>
                                                    <div class="old-val">Old: <?= esc(displayDiffValue((string) $field, $change['old'] ?? '')) ?></div>
                                                    <div class="new-val">New: <?= esc(displayDiffValue((string) $field, $change['new'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($diff['medical_histories'])): ?>
    <div class="card mb-3">
        <div class="card-header">
            <strong>Medical History Changes</strong>
        </div>

        <div class="card-body">
            <table class="table table-bordered table-sm align-middle">
                <thead>
                    <tr>
                        <th style="width:180px;">Action</th>
                        <th style="width:260px;">Member</th>
                        <th style="width:220px;">Condition</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($diff['medical_histories'] as $mh): ?>
                        <tr class="<?= esc(changeRowClass((string) ($mh['type'] ?? ''))) ?>">
                            <td>
                                <span class="badge <?= esc(changeBadgeClass((string) ($mh['type'] ?? ''))) ?>">
                                    <?= esc(changeLabel((string) ($mh['type'] ?? ''))) ?>
                                </span>
                            </td>
                            <td><?= esc($mh['member_name'] ?? '-') ?></td>
                            <td><?= esc($mh['condition_name'] ?? '-') ?></td>
                            <td>
                                <?php if (!empty($mh['changes']) && is_array($mh['changes'])): ?>
                                    <div class="row g-2">
                                        <?php foreach ($mh['changes'] as $field => $change): ?>
                                            <div class="col-md-6">
                                                <div class="mini-change-box">
                                                    <div class="change-card-label"><?= esc(fieldLabel((string) $field)) ?></div>
                                                    <div class="old-val">Old: <?= esc(displayDiffValue((string) $field, $change['old'] ?? '')) ?></div>
                                                    <div class="new-val">New: <?= esc(displayDiffValue((string) $field, $change['new'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <strong>Reviewer Action</strong>
    </div>

    <div class="card-body">
        <form method="post" action="<?= base_url('admin/registry/household-profiling-requests/' . $row['id'] . '/approve') ?>" class="mb-4">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Reviewer Notes</label>
                <textarea class="form-control" name="reviewer_notes" rows="3"></textarea>
            </div>

            <button class="btn btn-success">
                Approve Request
            </button>
        </form>

        <hr>

        <form method="post" action="<?= base_url('admin/registry/household-profiling-requests/' . $row['id'] . '/reject') ?>">
            <?= csrf_field() ?>

            <div class="mb-3">
                <label class="form-label">Rejection Notes</label>
                <textarea class="form-control" name="reviewer_notes" rows="3"></textarea>
            </div>

            <button class="btn btn-danger">
                Reject Request
            </button>
        </form>
    </div>
</div>

<?= $this->endSection() ?>