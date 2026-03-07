<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitModel;
use App\Models\HhMemberModel;
use App\Models\HhFamilyGroupModel;
use App\Models\HhGroupMemberModel;
use App\Models\HhGroupMemberQuarterModel;
use App\Models\HhGroupMemberMedicalHistoryModel;
use App\Models\HhVisitChangeRequestModel;

class HouseholdProfilingController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();
        $db = \Config\Database::connect();

        $builder = $db->table('hh_visits v')
            ->select("
                v.*,
                ba.name AS barangay_name,
                mu.name AS municipality_name
            ")
            ->join('admin_areas ba', "ba.pcode = v.barangay_pcode AND ba.level = 4", 'left')
            ->join('admin_areas mu', "mu.pcode = v.municipality_pcode AND mu.level = 3", 'left')
            ->orderBy('v.visit_date', 'DESC')
            ->orderBy('v.id', 'DESC');

        if (($actor['user_type'] ?? '') === 'super_admin') {
            // no filter
        } elseif (($actor['user_type'] ?? '') === 'admin') {
            $builder->where('v.municipality_pcode', $actor['municipality_pcode'] ?? null);
        } else {
            $builder->where('v.barangay_pcode', $actor['barangay_pcode'] ?? null);
        }

        $visits = $builder->get()->getResultArray();

        return view('admin/registry/household_profiling/index', [
            'pageTitle' => 'Household Profiling',
            'visits' => $visits,
            'actor' => $actor,
            'canDelete' => in_array(($actor['user_type'] ?? ''), ['super_admin', 'admin', 'staff'], true),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function create()
    {
        $actor = $this->actor();

        return view('admin/registry/household_profiling/form', [
            'pageTitle' => 'New Household Visit',
            'mode' => 'create',
            'visit' => null,
            'groups' => [],
            'actor' => $actor,
            'lock' => $this->locationLockForActor($actor),
            'lockedBarangayName' => $this->areaNameByPcode($actor['barangay_pcode'] ?? null),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function store()
    {
        $actor = $this->actor();

        $post = $this->request->getPost();
        $groups = $this->request->getPost('groups');

        $error = $this->validateVisitAndGroups($actor, $post, $groups);
        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $visitDate = $this->toDbDate($post['visit_date']);
        $quarter = $this->quarterFromDate($visitDate);

        $visitPayload = [
            'visit_date' => $visitDate,
            'last_visit_date' => $visitDate,
            'visit_count' => 1,
            'visit_quarter' => $quarter,
            'interviewed_by_user_id' => $actor['id'] ?? null,

            'sitio_purok' => trim($post['sitio_purok']),
            'barangay_pcode' => $post['barangay_pcode'],
            'municipality_pcode' => $post['municipality_pcode'] ?? ($actor['municipality_pcode'] ?? null),

            'household_no' => trim($post['household_no']),

            'household_latitude' => trim($post['household_latitude'] ?? '') !== '' ? (float) $post['household_latitude'] : null,
            'household_longitude' => trim($post['household_longitude'] ?? '') !== '' ? (float) $post['household_longitude'] : null,
            'geo_source' => trim($post['household_location_source'] ?? '') ?: null,
            'geo_accuracy_m' => trim($post['household_location_accuracy'] ?? '') !== '' ? (float) $post['household_location_accuracy'] : null,

            'respondent_last_name' => trim($post['respondent_last_name']),
            'respondent_first_name' => trim($post['respondent_first_name']),
            'respondent_middle_name' => trim($post['respondent_middle_name'] ?? '') ?: null,
            'respondent_relation' => $post['respondent_relation'],
            'respondent_relation_other' => trim($post['respondent_relation_other'] ?? '') ?: null,

            'ethnicity_mode' => $post['ethnicity_mode'],
            'ethnicity_tribe' => trim($post['ethnicity_tribe'] ?? '') ?: null,

            'socioeconomic_status' => $post['socioeconomic_status'],
            'nhts_no' => trim($post['nhts_no'] ?? '') ?: null,

            'water_source' => $post['water_source'],
            'water_source_other' => trim($post['water_source_other'] ?? '') ?: null,

            'toilet_facility' => $post['toilet_facility'],

            'remarks' => trim($post['remarks'] ?? '') ?: null,
        ];

        $visitPayload = $this->applyLocationLocksToPayload($actor, $visitPayload);

        // Barangay-level users create change requests only
        if ($this->profilingNeedsStaffApproval()) {
            $builder = service('hhVisitChangeRequestBuilder');
            $requestModel = new HhVisitChangeRequestModel();

            $requestData = $builder->buildCreateRequest($actor, $visitPayload, $groups ?: []);
            $requestModel->insert($requestData);

            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('success', 'New record submitted for review.');
        }

        // Admin / SuperAdmin write directly to live tables
        $visitPayload['approval_status'] = 'approved';
        $visitPayload['approval_action'] = null;
        $visitPayload['submitted_by_user_id'] = $actor['id'] ?? null;
        $visitPayload['approved_by_user_id'] = $actor['id'] ?? null;
        $visitPayload['approved_at'] = date('Y-m-d H:i:s');
        $visitPayload['rejected_by_user_id'] = null;
        $visitPayload['rejected_at'] = null;
        $visitPayload['approval_remarks'] = null;
        $visitPayload['pending_delete_requested_by'] = null;
        $visitPayload['pending_delete_requested_at'] = null;

        $visitModel = new HhVisitModel();
        $db = \Config\Database::connect();
        $db->transStart();

        $visitId = $visitModel->insert($visitPayload, true);
        $this->insertGroupsForVisit($visitId, $visitDate, $groups ?: [], []);

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Failed to save profiling. DB Error: ' . json_encode($db->error()));
            log_message('error', 'Failed to save profiling payload: ' . json_encode($visitPayload));

            return redirect()->back()->withInput()->with('error', 'Failed to save profiling. Please try again.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling'))
            ->with('success', 'Household profiling saved.');
    }

    public function edit(int $id)
    {
        $actor = $this->actor();

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $visit = $visitModel->find($id);
        if (! $visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Record not found.');
        }
        if (! $this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Not allowed.');
        }

        $groups = $familyModel->where('visit_id', $id)->orderBy('id', 'ASC')->findAll();
        $year = (int) substr($visit['visit_date'], 0, 4);

        foreach ($groups as &$g) {
            $members = $groupMemberModel->where('family_group_id', $g['id'])->orderBy('id', 'ASC')->findAll();

            foreach ($members as &$m) {
                $m['medical_history_arr'] = ! empty($m['medical_history']) ? explode(',', $m['medical_history']) : [];

                $m['medical_histories'] = $mhModel
                    ->where('group_member_id', $m['id'])
                    ->orderBy('date_diagnosed', 'DESC')
                    ->orderBy('id', 'DESC')
                    ->findAll();

                $quarters = $qModel
                    ->where('group_member_id', $m['id'])
                    ->where('year', $year)
                    ->findAll();

                $map = [];
                foreach ($quarters as $qq) {
                    $map[(int) $qq['quarter']] = $qq;
                }

                for ($q = 1; $q <= 4; $q++) {
                    $m["q{$q}_age"] = $map[$q]['age'] ?? '';
                    $m["q{$q}_class"] = $map[$q]['class_code'] ?? '';
                }
            }
            unset($m);

            $g['members'] = $members;
        }
        unset($g);

        return view('admin/registry/household_profiling/form', [
            'pageTitle' => 'Edit Household Visit',
            'mode' => 'edit',
            'visit' => $visit,
            'groups' => $groups,
            'actor' => $actor,
            'lock' => $this->locationLockForActor($actor),
            'lockedBarangayName' => $this->areaNameByPcode($visit['barangay_pcode'] ?? ($actor['barangay_pcode'] ?? null)),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function update(int $id)
    {
        $actor = $this->actor();

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $visit = $visitModel->find($id);
        if (! $visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Record not found.');
        }
        if (! $this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Not allowed.');
        }

        $post = $this->request->getPost();
        $groups = $this->request->getPost('groups');

        $error = $this->validateVisitAndGroups($actor, $post, $groups);
        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $submitAction = trim((string) ($post['submit_action'] ?? 'update_visit'));
        if (! in_array($submitAction, ['update_only', 'update_visit'], true)) {
            $submitAction = 'update_visit';
        }

        $submittedVisitDate = $this->toDbDate($post['visit_date']);

        $payload = [
            'sitio_purok' => trim($post['sitio_purok']),
            'barangay_pcode' => $post['barangay_pcode'],
            'municipality_pcode' => $post['municipality_pcode'] ?? ($visit['municipality_pcode'] ?? null),

            'household_no' => trim($post['household_no']),

            'household_latitude' => trim($post['household_latitude'] ?? '') !== '' ? (float) $post['household_latitude'] : null,
            'household_longitude' => trim($post['household_longitude'] ?? '') !== '' ? (float) $post['household_longitude'] : null,
            'geo_source' => trim($post['household_location_source'] ?? '') ?: null,
            'geo_accuracy_m' => trim($post['household_location_accuracy'] ?? '') !== '' ? (float) $post['household_location_accuracy'] : null,

            'respondent_last_name' => trim($post['respondent_last_name']),
            'respondent_first_name' => trim($post['respondent_first_name']),
            'respondent_middle_name' => trim($post['respondent_middle_name'] ?? '') ?: null,
            'respondent_relation' => $post['respondent_relation'],
            'respondent_relation_other' => trim($post['respondent_relation_other'] ?? '') ?: null,

            'ethnicity_mode' => $post['ethnicity_mode'],
            'ethnicity_tribe' => trim($post['ethnicity_tribe'] ?? '') ?: null,

            'socioeconomic_status' => $post['socioeconomic_status'],
            'nhts_no' => trim($post['nhts_no'] ?? '') ?: null,

            'water_source' => $post['water_source'],
            'water_source_other' => trim($post['water_source_other'] ?? '') ?: null,

            'toilet_facility' => $post['toilet_facility'],
            'remarks' => trim($post['remarks'] ?? '') ?: null,
        ];

        if ($submitAction === 'update_visit') {
            $quarter = $this->quarterFromDate($submittedVisitDate);
            $payload['last_visit_date'] = $submittedVisitDate;
            $payload['visit_count'] = ((int) ($visit['visit_count'] ?? 1)) + 1;
            $payload['visit_quarter'] = $quarter;
        }

        $payload = $this->applyLocationLocksToPayload($actor, $payload);

        // Barangay-level users submit update requests only
        if ($this->profilingNeedsStaffApproval()) {
            $builder = service('hhVisitChangeRequestBuilder');
            $requestModel = new HhVisitChangeRequestModel();

            $requestData = $builder->buildUpdateRequest($actor, $id, $visit, $payload, $groups ?: []);
            $requestModel->insert($requestData);

            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('success', 'Update request submitted for review.');
        }

        // Admin / SuperAdmin update live record directly
        $payload['approval_status'] = 'approved';
        $payload['approval_action'] = null;
        $payload['submitted_by_user_id'] = $actor['id'] ?? null;
        $payload['approved_by_user_id'] = $actor['id'] ?? null;
        $payload['approved_at'] = date('Y-m-d H:i:s');
        $payload['rejected_by_user_id'] = null;
        $payload['rejected_at'] = null;
        $payload['approval_remarks'] = null;
        $payload['pending_delete_requested_by'] = null;
        $payload['pending_delete_requested_at'] = null;

        $historyMap = [];

        if (is_array($groups)) {
            foreach ($groups as $g) {
                $members = $g['members'] ?? [];
                if (! is_array($members)) {
                    continue;
                }

                foreach ($members as $m) {
                    $oldMemberId = ! empty($m['id']) ? (int) $m['id'] : 0;
                    if ($oldMemberId > 0 && ! isset($historyMap[$oldMemberId])) {
                        $historyMap[$oldMemberId] = $mhModel
                            ->where('group_member_id', $oldMemberId)
                            ->orderBy('id', 'ASC')
                            ->findAll();
                    }
                }
            }
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $visitModel->update($id, $payload);

        $oldGroups = $familyModel->where('visit_id', $id)->findAll();
        foreach ($oldGroups as $og) {
            $oldMembers = $groupMemberModel->where('family_group_id', $og['id'])->findAll();
            foreach ($oldMembers as $om) {
                $qModel->where('group_member_id', $om['id'])->delete();
                $mhModel->where('group_member_id', $om['id'])->delete();
            }
            $groupMemberModel->where('family_group_id', $og['id'])->delete();
        }

        $familyModel->where('visit_id', $id)->delete();

        $effectiveDateForGroups = ($submitAction === 'update_visit')
            ? $submittedVisitDate
            : ($visit['visit_date'] ?? $submittedVisitDate);

        $this->insertGroupsForVisit($id, $effectiveDateForGroups, $groups ?: [], $historyMap);

        $db->transComplete();

        if ($db->transStatus() === false) {
            log_message('error', 'Failed to update profiling. DB Error: ' . json_encode($db->error()));
            log_message('error', 'Failed to update profiling payload: ' . json_encode($payload));
            log_message('error', 'Failed to update profiling submit_action: ' . $submitAction);

            return redirect()->back()->withInput()->with('error', 'Failed to update profiling.');
        }

        $message = $submitAction === 'update_only'
            ? 'Profiling record updated without changing visit count.'
            : 'Visit updated successfully.';

        return redirect()->to(base_url('admin/registry/household-profiling'))->with('success', $message);
    }

    public function delete(int $id)
    {
        $actor = $this->actor();

        if (! in_array(($actor['user_type'] ?? ''), ['super_admin', 'admin', 'staff'], true)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('error', 'You are not allowed to delete profiling records.');
        }

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $visit = $visitModel->find($id);
        if (! $visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('error', 'Record not found.');
        }

        if (! $this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('error', 'Not allowed.');
        }

        // Staff create delete request only
        if ($this->profilingDeleteNeedsAdminApproval()) {
            $builder = service('hhVisitChangeRequestBuilder');
            $requestModel = new HhVisitChangeRequestModel();

            $requestData = $builder->buildDeleteRequest($actor, $id, $visit);
            $requestModel->insert($requestData);

            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('success', 'Delete request submitted for admin approval.');
        }

        // Admin / SuperAdmin delete directly
        $db = \Config\Database::connect();
        $db->transStart();

        $groups = $familyModel->where('visit_id', $id)->findAll();
        foreach ($groups as $group) {
            $members = $groupMemberModel->where('family_group_id', $group['id'])->findAll();
            foreach ($members as $member) {
                $qModel->where('group_member_id', $member['id'])->delete();
                $mhModel->where('group_member_id', $member['id'])->delete();
            }
            $groupMemberModel->where('family_group_id', $group['id'])->delete();
        }

        $familyModel->where('visit_id', $id)->delete();
        $visitModel->delete($id);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to(base_url('admin/registry/household-profiling'))
                ->with('error', 'Failed to delete profiling.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling'))
            ->with('success', 'Profiling record deleted.');
    }

    public function show(int $id)
    {
        return redirect()->to(base_url('admin/registry/household-profiling/' . $id . '/edit'));
    }

    public function searchMembers()
    {
        $actor = $this->actor();
        $q = trim((string) $this->request->getGet('q'));

        if ($q === '' || strlen($q) < 2) {
            return $this->response->setJSON([]);
        }

        $db = \Config\Database::connect();
        $builder = $db->table('hh_members m')
            ->select('m.id, m.last_name, m.first_name, m.middle_name, m.sex, m.dob, v.barangay_pcode, v.municipality_pcode, v.sitio_purok, v.household_no, v.visit_date, v.id as visit_id')
            ->join('hh_visits v', 'v.id = m.visit_id', 'left')
            ->orderBy('m.last_name', 'ASC')
            ->orderBy('m.first_name', 'ASC')
            ->limit(25);

        if (($actor['user_type'] ?? '') === 'super_admin') {
            // no filter
        } elseif (($actor['user_type'] ?? '') === 'admin') {
            if (! empty($actor['municipality_pcode'])) {
                $builder->where('v.municipality_pcode', $actor['municipality_pcode']);
            }
        } else {
            if (! empty($actor['barangay_pcode'])) {
                $builder->where('v.barangay_pcode', $actor['barangay_pcode']);
            }
        }

        $qNorm = preg_replace('/\s+/', ' ', $q);
        $parts = preg_split('/[\s,]+/', $qNorm) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), static fn ($v) => $v !== ''));

        $builder->groupStart();
        foreach ($parts as $part) {
            $builder->groupStart()
                ->like('m.last_name', $part)
                ->orLike('m.first_name', $part)
                ->orLike('m.middle_name', $part)
                ->orLike('m.dob', $part)
                ->groupEnd();
        }
        $builder->groupEnd();

        $rows = $builder->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $full = trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? ''));
            $out[] = [
                'id' => (int) $r['id'],
                'name' => $full,
                'sex' => $r['sex'] ?? '',
                'dob' => $r['dob'] ?? '',
                'barangay_pcode' => $r['barangay_pcode'] ?? '',
                'municipality_pcode' => $r['municipality_pcode'] ?? '',
                'sitio_purok' => $r['sitio_purok'] ?? '',
                'household_no' => $r['household_no'] ?? '',
                'visit_date' => $r['visit_date'] ?? '',
                'visit_id' => (int) ($r['visit_id'] ?? 0),
            ];
        }

        return $this->response->setJSON($out);
    }

    private function insertGroupsForVisit(int $visitId, string $visitDate, array $groups, array $historyMap = []): void
    {
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $year = (int) substr($visitDate, 0, 4);

        foreach ($groups as $g) {
            $groupId = $familyModel->insert([
                'visit_id' => $visitId,
                'group_name' => trim($g['group_name'] ?? '') ?: null,
                'living_status' => $g['living_status'],
                'notes' => trim($g['notes'] ?? '') ?: null,
            ], true);

            $members = $g['members'] ?? [];
            if (! is_array($members)) {
                $members = [];
            }

            foreach ($members as $m) {
                $prepared = $this->prepareGroupMemberSnapshot($visitId, $m);
                $oldMemberId = ! empty($m['id']) ? (int) $m['id'] : 0;

                $gmId = $groupMemberModel->insert([
                    'family_group_id' => $groupId,
                    'linked_member_id' => $prepared['linked_member_id'],

                    'local_last_name' => $prepared['snapshot']['local_last_name'] ?: null,
                    'local_first_name' => $prepared['snapshot']['local_first_name'] ?: null,
                    'local_middle_name' => $prepared['snapshot']['local_middle_name'] ?: null,

                    'relationship_code' => $prepared['relationship_code'],
                    'relationship_other' => $prepared['relationship_other'],

                    'sex' => $prepared['snapshot']['sex'] ?: null,
                    'dob' => $prepared['snapshot']['dob'],
                    'civil_status' => $prepared['snapshot']['civil_status'],

                    'philhealth_id' => $prepared['snapshot']['philhealth_id'],
                    'membership_type' => $prepared['snapshot']['membership_type'],
                    'philhealth_category' => $prepared['snapshot']['philhealth_category'],

                    'medical_history' => null,

                    'lmp_date' => $prepared['snapshot']['lmp_date'],
                    'educ_attainment' => $prepared['snapshot']['educ_attainment'],
                    'religion' => $prepared['snapshot']['religion'],

                    'status_in_household' => $prepared['status_in_household'],
                    'stay_from' => $prepared['stay_from'],
                    'stay_to' => $prepared['stay_to'],

                    'remarks' => $prepared['remarks'],
                ], true);

                if ($oldMemberId > 0 && ! empty($historyMap[$oldMemberId])) {
                    foreach ($historyMap[$oldMemberId] as $hist) {
                        $conditionName = trim((string) ($hist['condition_name'] ?? ''));
                        if ($conditionName === '') {
                            continue;
                        }

                        $mhModel->insert([
                            'group_member_id' => $gmId,
                            'condition_name'  => $conditionName,
                            'date_diagnosed'  => ! empty($hist['date_diagnosed']) ? $hist['date_diagnosed'] : null,
                            'status'          => trim((string) ($hist['status'] ?? '')) ?: null,
                            'remarks'         => trim((string) ($hist['remarks'] ?? '')) ?: null,
                        ]);
                    }
                }

                for ($q = 1; $q <= 4; $q++) {
                    $age = isset($m["q{$q}_age"]) && $m["q{$q}_age"] !== '' ? (int) $m["q{$q}_age"] : null;
                    $class = trim((string) ($m["q{$q}_class"] ?? ''));

                    if ($age === null && $class === '') {
                        continue;
                    }

                    if ($class === '' && $age !== null) {
                        $class = $this->classCodeFromAge($age);
                    }

                    $qModel->insert([
                        'group_member_id' => $gmId,
                        'year' => $year,
                        'quarter' => $q,
                        'age' => $age,
                        'class_code' => $class ?: null,
                    ]);
                }
            }
        }
    }

    private function prepareGroupMemberSnapshot(int $visitId, array $m): array
    {
        $hhMemberModel = new HhMemberModel();

        $linkedId = ! empty($m['linked_member_id']) ? (int) $m['linked_member_id'] : null;

        $snapshot = [
            'local_last_name' => trim($m['local_last_name'] ?? ''),
            'local_first_name' => trim($m['local_first_name'] ?? ''),
            'local_middle_name' => trim($m['local_middle_name'] ?? ''),
            'sex' => trim($m['sex'] ?? ''),
            'dob' => ! empty($m['dob']) ? $this->toDbDate($m['dob']) : null,
            'civil_status' => trim($m['civil_status'] ?? '') ?: null,

            'philhealth_id' => trim($m['philhealth_id'] ?? '') ?: null,
            'membership_type' => trim($m['membership_type'] ?? '') ?: null,
            'philhealth_category' => trim($m['philhealth_category'] ?? '') ?: null,

            'lmp_date' => ! empty($m['lmp_date']) ? $this->toDbDate($m['lmp_date']) : null,
            'educ_attainment' => trim($m['educ_attainment'] ?? '') ?: null,
            'religion' => trim($m['religion'] ?? '') ?: null,
        ];

        if ($linkedId) {
            $linked = $hhMemberModel->find($linkedId);
            if ($linked) {
                if ($snapshot['local_last_name'] === '') {
                    $snapshot['local_last_name'] = $linked['last_name'] ?? '';
                }
                if ($snapshot['local_first_name'] === '') {
                    $snapshot['local_first_name'] = $linked['first_name'] ?? '';
                }
                if ($snapshot['local_middle_name'] === '') {
                    $snapshot['local_middle_name'] = $linked['middle_name'] ?? '';
                }
                if (($snapshot['sex'] ?? '') === '') {
                    $snapshot['sex'] = $linked['sex'] ?? '';
                }
                if (empty($snapshot['dob'])) {
                    $snapshot['dob'] = $linked['dob'] ?? null;
                }

                if (empty($snapshot['civil_status'])) {
                    $snapshot['civil_status'] = $this->mapLegacyCivilStatus($linked['civil_status'] ?? null);
                }

                if (empty($snapshot['philhealth_id'])) {
                    $snapshot['philhealth_id'] = $linked['philhealth_id'] ?? null;
                }
                if (empty($snapshot['membership_type'])) {
                    $snapshot['membership_type'] = $linked['membership_type'] ?? null;
                }
                if (empty($snapshot['philhealth_category'])) {
                    $snapshot['philhealth_category'] = $linked['philhealth_category'] ?? null;
                }

                if (empty($snapshot['educ_attainment'])) {
                    $snapshot['educ_attainment'] = $linked['educ_attainment'] ?? null;
                }
                if (empty($snapshot['religion'])) {
                    $snapshot['religion'] = $linked['religion'] ?? null;
                }
            } else {
                $linkedId = null;
            }
        }

        if (! $linkedId) {
            $linkedId = $this->findOrCreateLinkedMemberId($visitId, [
                'last_name' => $snapshot['local_last_name'],
                'first_name' => $snapshot['local_first_name'],
                'middle_name' => $snapshot['local_middle_name'],
                'relationship_code' => ! empty($m['relationship_code']) ? (int) $m['relationship_code'] : null,
                'relationship_other' => trim($m['relationship_other'] ?? '') ?: null,
                'sex' => $snapshot['sex'] ?: null,
                'dob' => $snapshot['dob'],
                'civil_status' => $this->legacyCivilStatusCode($snapshot['civil_status']),
                'philhealth_id' => $snapshot['philhealth_id'],
                'membership_type' => $snapshot['membership_type'],
                'philhealth_category' => $snapshot['philhealth_category'],
                'medical_history' => null,
                'lmp_date' => $snapshot['lmp_date'],
                'educ_attainment' => $snapshot['educ_attainment'],
                'religion' => $snapshot['religion'],
                'remarks' => trim($m['remarks'] ?? '') ?: null,
            ]);
        }

        return [
            'linked_member_id' => $linkedId,
            'snapshot' => $snapshot,
            'relationship_code' => ! empty($m['relationship_code']) ? (int) $m['relationship_code'] : null,
            'relationship_other' => trim($m['relationship_other'] ?? '') ?: null,
            'medical_history' => null,
            'status_in_household' => trim($m['status_in_household'] ?? '') ?: null,
            'stay_from' => ! empty($m['stay_from']) ? $this->toDbDate($m['stay_from']) : null,
            'stay_to' => ! empty($m['stay_to']) ? $this->toDbDate($m['stay_to']) : null,
            'remarks' => trim($m['remarks'] ?? '') ?: null,
        ];
    }

    private function findOrCreateLinkedMemberId(int $visitId, array $payload): ?int
    {
        if (
            empty($payload['last_name']) ||
            empty($payload['first_name']) ||
            empty($payload['sex']) ||
            empty($payload['dob'])
        ) {
            return null;
        }

        $hhMemberModel = new HhMemberModel();

        $existing = $hhMemberModel
            ->where('last_name', $payload['last_name'])
            ->where('first_name', $payload['first_name'])
            ->groupStart()
                ->where('middle_name', $payload['middle_name'] ?? null)
                ->orGroupStart()
                    ->where('middle_name', '')
                    ->where('middle_name', $payload['middle_name'] ?? '')
                ->groupEnd()
            ->groupEnd()
            ->where('sex', $payload['sex'])
            ->where('dob', $payload['dob'])
            ->orderBy('id', 'DESC')
            ->first();

        if ($existing) {
            return (int) $existing['id'];
        }

        $payload['visit_id'] = $visitId;
        return $hhMemberModel->insert($payload, true);
    }

    private function classCodeFromAge(int $age): ?string
    {
        if ($age < 0) {
            return null;
        }

        if ($age <= 5) {
            return 'INFANT';
        }
        if ($age <= 12) {
            return 'CHILD';
        }
        if ($age <= 19) {
            return 'TEEN';
        }
        if ($age <= 59) {
            return 'ADULT';
        }

        return 'SENIOR';
    }

    private function validateVisitAndGroups(array $actor, array $post, $groups): ?string
    {
        if (empty($post['visit_date'])) return 'Visit date is required.';
        if (empty($post['sitio_purok'])) return 'Sitio/Purok is required.';
        if (empty($post['barangay_pcode'])) return 'Barangay is required.';
        if (empty($post['household_no'])) return 'Household No. is required.';

        if (empty($post['respondent_last_name']) || empty($post['respondent_first_name'])) {
            return 'Respondent name is required.';
        }
        if (empty($post['respondent_relation'])) return 'Respondent relationship is required.';
        if (($post['respondent_relation'] ?? '') === 'other' && empty($post['respondent_relation_other'])) {
            return 'Please specify respondent relationship.';
        }

        if (empty($post['ethnicity_mode'])) return 'Ethnicity is required.';
        if (($post['ethnicity_mode'] ?? '') === 'tribe' && empty($post['ethnicity_tribe'])) {
            return 'Please specify tribe.';
        }

        if (! isset($post['socioeconomic_status'])) return 'Socioeconomic status is required.';

        if (! isset($post['water_source'])) return 'Water source is required.';
        if (($post['water_source'] ?? '') === 'others' && empty($post['water_source_other'])) {
            return 'Please specify water source.';
        }

        if (empty($post['toilet_facility'])) return 'Toilet type is required.';

        if (! is_array($groups) || empty($groups)) {
            return 'Please add at least one Family Group.';
        }

        foreach ($groups as $g) {
            if (empty($g['living_status'])) {
                return 'Family Group living status is required.';
            }

            $members = $g['members'] ?? null;
            if (! is_array($members) || empty($members)) {
                return 'Each Family Group must have at least one member.';
            }

            foreach ($members as $m) {
                $linked = ! empty($m['linked_member_id']);

                if (! $linked) {
                    if (empty($m['local_last_name']) || empty($m['local_first_name'])) {
                        return 'Member name (Last/First) is required.';
                    }
                    if (empty($m['sex'])) return 'Member sex is required.';
                    if (empty($m['dob'])) return 'Member DOB is required.';
                }

                $rc = isset($m['relationship_code']) ? (int) $m['relationship_code'] : null;
                if ($rc === 5 && empty($m['relationship_other'])) {
                    return 'Please specify relationship (Others).';
                }
            }
        }

        return null;
    }

    private function toDbDate(string $dateStr): string
    {
        $dateStr = trim($dateStr);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            return $dateStr;
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $dateStr)) {
            $mm = substr($dateStr, 0, 2);
            $dd = substr($dateStr, 3, 2);
            $yy = substr($dateStr, 6, 4);
            return "{$yy}-{$mm}-{$dd}";
        }

        $ts = strtotime($dateStr);
        return date('Y-m-d', $ts ?: time());
    }

    private function quarterFromDate(string $ymd): int
    {
        $m = (int) substr($ymd, 5, 2);
        return (int) ceil($m / 3);
    }

    private function canAccessVisit(array $actor, array $visit): bool
    {
        $type = $actor['user_type'] ?? '';

        if ($type === 'super_admin') return true;

        if ($type === 'admin') {
            return ! empty($actor['municipality_pcode']) && ($visit['municipality_pcode'] ?? null) === $actor['municipality_pcode'];
        }

        return ! empty($actor['barangay_pcode']) && ($visit['barangay_pcode'] ?? null) === $actor['barangay_pcode'];
    }

    private function locationLockForActor(array $actor): array
    {
        $type = $actor['user_type'] ?? '';

        if ($type === 'super_admin') {
            return [
                'municipality_locked' => false,
                'barangay_locked' => false,
                'municipality_pcode' => '',
                'barangay_pcode' => '',
            ];
        }

        if ($type === 'admin') {
            return [
                'municipality_locked' => true,
                'barangay_locked' => false,
                'municipality_pcode' => $actor['municipality_pcode'] ?? '',
                'barangay_pcode' => '',
            ];
        }

        return [
            'municipality_locked' => true,
            'barangay_locked' => true,
            'municipality_pcode' => $actor['municipality_pcode'] ?? '',
            'barangay_pcode' => $actor['barangay_pcode'] ?? '',
        ];
    }

    private function applyLocationLocksToPayload(array $actor, array $payload): array
    {
        $type = $actor['user_type'] ?? '';

        if ($type === 'super_admin') {
            return $payload;
        }

        if ($type === 'admin') {
            $payload['municipality_pcode'] = $actor['municipality_pcode'] ?? $payload['municipality_pcode'] ?? null;
            return $payload;
        }

        $payload['municipality_pcode'] = $actor['municipality_pcode'] ?? $payload['municipality_pcode'] ?? null;
        $payload['barangay_pcode'] = $actor['barangay_pcode'] ?? $payload['barangay_pcode'];
        return $payload;
    }

    private function legacyCivilStatusCode(?string $civil): ?string
    {
        $civil = strtolower(trim((string) $civil));
        if ($civil === '') return null;

        return match ($civil) {
            'single' => 'S',
            'married' => 'M',
            'widowed' => 'W',
            'separated' => 'SP',
            'live_in' => 'C',
            default => null,
        };
    }

    private function mapLegacyCivilStatus(?string $legacy): ?string
    {
        $legacy = strtoupper(trim((string) $legacy));
        if ($legacy === '') return null;

        return match ($legacy) {
            'S'  => 'single',
            'M'  => 'married',
            'W'  => 'widowed',
            'SP' => 'separated',
            'C'  => 'live_in',
            default => null,
        };
    }

    private function areaNameByPcode(?string $pcode): string
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

        return $row['name'] ?? '';
    }

    public function medicalHistories(int $memberId)
    {
        $actor = $this->actor();
        $member = $this->findAccessibleGroupMember($actor, $memberId);

        if (! $member) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Member not found or not accessible.',
            ]);
        }

        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $rows = $mhModel
            ->where('group_member_id', $memberId)
            ->orderBy('date_diagnosed', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll();

        return $this->response->setJSON([
            'ok' => true,
            'rows' => $rows,
        ]);
    }

    public function saveMedicalHistory(int $memberId)
    {
        $actor = $this->actor();
        $member = $this->findAccessibleGroupMember($actor, $memberId);

        if (! $member) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Member not found or not accessible.',
            ]);
        }

        $post = $this->request->getPost();

        $historyId = (int) ($post['history_id'] ?? 0);
        $conditionName = trim((string) ($post['condition_name'] ?? ''));
        $dateDiagnosed = trim((string) ($post['date_diagnosed'] ?? ''));
        $status = trim((string) ($post['status'] ?? ''));
        $remarks = trim((string) ($post['remarks'] ?? ''));

        if ($conditionName === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'ok' => false,
                'message' => 'Condition/Illness is required.',
            ]);
        }

        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $payload = [
            'group_member_id' => $memberId,
            'condition_name'  => $conditionName,
            'date_diagnosed'  => $dateDiagnosed !== '' ? $this->toDbDate($dateDiagnosed) : null,
            'status'          => $status !== '' ? $status : null,
            'remarks'         => $remarks !== '' ? $remarks : null,
        ];

        if ($historyId > 0) {
            $existing = $mhModel->find($historyId);

            if (! $existing || (int) $existing['group_member_id'] !== $memberId) {
                return $this->response->setStatusCode(404)->setJSON([
                    'ok' => false,
                    'message' => 'Medical history record not found.',
                ]);
            }

            $mhModel->update($historyId, $payload);
        } else {
            $historyId = $mhModel->insert($payload, true);
        }

        $row = $mhModel->find($historyId);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Medical history saved successfully.',
            'row' => $row,
        ]);
    }

    public function deleteMedicalHistory(int $historyId)
    {
        $actor = $this->actor();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $row = $mhModel->find($historyId);
        if (! $row) {
            return $this->response->setStatusCode(404)->setJSON([
                'ok' => false,
                'message' => 'Medical history record not found.',
            ]);
        }

        $member = $this->findAccessibleGroupMember($actor, (int) $row['group_member_id']);
        if (! $member) {
            return $this->response->setStatusCode(403)->setJSON([
                'ok' => false,
                'message' => 'Not allowed.',
            ]);
        }

        $mhModel->delete($historyId);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Medical history deleted successfully.',
        ]);
    }

    private function findAccessibleGroupMember(array $actor, int $memberId): ?array
    {
        $db = \Config\Database::connect();

        $row = $db->table('hh_group_members gm')
            ->select('gm.*, fg.visit_id, v.barangay_pcode, v.municipality_pcode')
            ->join('hh_family_groups fg', 'fg.id = gm.family_group_id', 'left')
            ->join('hh_visits v', 'v.id = fg.visit_id', 'left')
            ->where('gm.id', $memberId)
            ->get()
            ->getRowArray();

        if (! $row) {
            return null;
        }

        if (! $this->canAccessVisit($actor, [
            'barangay_pcode' => $row['barangay_pcode'] ?? null,
            'municipality_pcode' => $row['municipality_pcode'] ?? null,
        ])) {
            return null;
        }

        return $row;
    }
}