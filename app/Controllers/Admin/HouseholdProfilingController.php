<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitModel;
use App\Models\HhMemberModel; // existing table used for "link existing"
use App\Models\HhFamilyGroupModel;
use App\Models\HhGroupMemberModel;
use App\Models\HhGroupMemberQuarterModel;

class HouseholdProfilingController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();
        $visitModel = new HhVisitModel();

        $builder = $visitModel->orderBy('visit_date', 'DESC')->orderBy('id', 'DESC');

        if (($actor['user_type'] ?? '') === 'super_admin') {
            $visits = $builder->findAll();
        } elseif (in_array(($actor['user_type'] ?? ''), ['admin', 'staff'], true)) {
            $visits = $builder->where('municipality_pcode', $actor['municipality_pcode'] ?? null)->findAll();
        } else {
            $visits = $builder->where('barangay_pcode', $actor['barangay_pcode'] ?? null)->findAll();
        }

        return view('admin/registry/household_profiling/index', [
            'pageTitle' => 'Household Profiling',
            'visits' => $visits,
            'actor' => $actor,
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
        $year = (int)substr($visitDate, 0, 4);

        $visitPayload = [
            'visit_date' => $visitDate,
            'visit_quarter' => $quarter,
            'interviewed_by_user_id' => $actor['id'] ?? null,

            'sitio_purok' => trim($post['sitio_purok']),
            'barangay_pcode' => $post['barangay_pcode'],
            'municipality_pcode' => $post['municipality_pcode'] ?? ($actor['municipality_pcode'] ?? null),

            'household_no' => trim($post['household_no']),

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

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $hhMemberModel = new HhMemberModel(); // for linked lookup

        $db = \Config\Database::connect();
        $db->transStart();

        $visitId = $visitModel->insert($visitPayload, true);

        foreach ($groups as $g) {
            $groupId = $familyModel->insert([
                'visit_id' => $visitId,
                'group_name' => trim($g['group_name'] ?? '') ?: null,
                'living_status' => $g['living_status'],
                'notes' => trim($g['notes'] ?? '') ?: null,
            ], true);

            $members = $g['members'] ?? [];
            if (!is_array($members)) $members = [];

            foreach ($members as $m) {
                $linkedId = !empty($m['linked_member_id']) ? (int)$m['linked_member_id'] : null;

                // Medical history array -> comma
                $med = $m['medical_history'] ?? [];
                if (!is_array($med)) $med = [];
                $medStr = implode(',', array_values(array_filter($med)));

                // If linked, copy snapshot from hh_members when local fields not provided
                $snapshot = [
                    'local_last_name' => trim($m['local_last_name'] ?? ''),
                    'local_first_name' => trim($m['local_first_name'] ?? ''),
                    'local_middle_name' => trim($m['local_middle_name'] ?? ''),
                    'sex' => trim($m['sex'] ?? ''),
                    'dob' => !empty($m['dob']) ? $this->toDbDate($m['dob']) : null,
                    'civil_status' => trim($m['civil_status'] ?? '') ?: null,

                    'philhealth_id' => trim($m['philhealth_id'] ?? '') ?: null,
                    'membership_type' => trim($m['membership_type'] ?? '') ?: null,
                    'philhealth_category' => trim($m['philhealth_category'] ?? '') ?: null,

                    'lmp_date' => !empty($m['lmp_date']) ? $this->toDbDate($m['lmp_date']) : null,
                    'educ_attainment' => trim($m['educ_attainment'] ?? '') ?: null,
                    'religion' => trim($m['religion'] ?? '') ?: null,
                ];

                if ($linkedId) {
                    $linked = $hhMemberModel->find($linkedId);
                    if ($linked) {
                        if ($snapshot['local_last_name'] === '') $snapshot['local_last_name'] = $linked['last_name'] ?? '';
                        if ($snapshot['local_first_name'] === '') $snapshot['local_first_name'] = $linked['first_name'] ?? '';
                        if ($snapshot['local_middle_name'] === '') $snapshot['local_middle_name'] = $linked['middle_name'] ?? '';
                        if (($snapshot['sex'] ?? '') === '') $snapshot['sex'] = $linked['sex'] ?? '';
                        if (empty($snapshot['dob'])) $snapshot['dob'] = $linked['dob'] ?? null;

                        // linked civil_status from hh_members is legacy codes; keep your new civil if already selected
                        if (empty($snapshot['civil_status'])) {
                            $snapshot['civil_status'] = $this->mapLegacyCivilStatus($linked['civil_status'] ?? null);
                        }

                        if (empty($snapshot['philhealth_id'])) $snapshot['philhealth_id'] = $linked['philhealth_id'] ?? null;
                        if (empty($snapshot['membership_type'])) $snapshot['membership_type'] = $linked['membership_type'] ?? null;
                        if (empty($snapshot['philhealth_category'])) $snapshot['philhealth_category'] = $linked['philhealth_category'] ?? null;

                        if (empty($snapshot['educ_attainment'])) $snapshot['educ_attainment'] = $linked['educ_attainment'] ?? null;
                        if (empty($snapshot['religion'])) $snapshot['religion'] = $linked['religion'] ?? null;
                    }
                }

                $gmId = $groupMemberModel->insert([
                    'family_group_id' => $groupId,
                    'linked_member_id' => $linkedId,

                    'local_last_name' => $snapshot['local_last_name'] ?: null,
                    'local_first_name' => $snapshot['local_first_name'] ?: null,
                    'local_middle_name' => $snapshot['local_middle_name'] ?: null,

                    'relationship_code' => !empty($m['relationship_code']) ? (int)$m['relationship_code'] : null,
                    'relationship_other' => trim($m['relationship_other'] ?? '') ?: null,

                    'sex' => $snapshot['sex'] ?: null,
                    'dob' => $snapshot['dob'],
                    'civil_status' => $snapshot['civil_status'],

                    'philhealth_id' => $snapshot['philhealth_id'],
                    'membership_type' => $snapshot['membership_type'],
                    'philhealth_category' => $snapshot['philhealth_category'],

                    'medical_history' => $medStr ?: null,

                    'lmp_date' => $snapshot['lmp_date'],
                    'educ_attainment' => $snapshot['educ_attainment'],
                    'religion' => $snapshot['religion'],

                    'status_in_household' => trim($m['status_in_household'] ?? '') ?: null,
                    'stay_from' => !empty($m['stay_from']) ? $this->toDbDate($m['stay_from']) : null,
                    'stay_to' => !empty($m['stay_to']) ? $this->toDbDate($m['stay_to']) : null,

                    'remarks' => trim($m['remarks'] ?? '') ?: null,
                ], true);

                // quarters are OPTIONAL now
                for ($q = 1; $q <= 4; $q++) {
                    $age = isset($m["q{$q}_age"]) && $m["q{$q}_age"] !== '' ? (int)$m["q{$q}_age"] : null;
                    $class = trim((string)($m["q{$q}_class"] ?? ''));

                    // only insert if there is something
                    if ($age === null && $class === '') continue;

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

        $db->transComplete();

        if ($db->transStatus() === false) {
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

        $visit = $visitModel->find($id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Record not found.');
        }
        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Not allowed.');
        }

        $groups = $familyModel->where('visit_id', $id)->orderBy('id', 'ASC')->findAll();
        $year = (int)substr($visit['visit_date'], 0, 4);

        foreach ($groups as &$g) {
            $members = $groupMemberModel->where('family_group_id', $g['id'])->orderBy('id', 'ASC')->findAll();
            foreach ($members as &$m) {
                $m['medical_history_arr'] = !empty($m['medical_history']) ? explode(',', $m['medical_history']) : [];

                // map quarters
                $quarters = $qModel->where('group_member_id', $m['id'])->where('year', $year)->findAll();
                $map = [];
                foreach ($quarters as $qq) $map[(int)$qq['quarter']] = $qq;

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
        ]);
    }

    public function update(int $id)
    {
        $actor = $this->actor();

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();

        $visit = $visitModel->find($id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Record not found.');
        }
        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Not allowed.');
        }

        $post = $this->request->getPost();
        $groups = $this->request->getPost('groups');

        $error = $this->validateVisitAndGroups($actor, $post, $groups);
        if ($error) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $visitDate = $this->toDbDate($post['visit_date']);
        $quarter = $this->quarterFromDate($visitDate);

        $payload = [
            'visit_date' => $visitDate,
            'visit_quarter' => $quarter,

            'sitio_purok' => trim($post['sitio_purok']),
            'barangay_pcode' => $post['barangay_pcode'],
            'municipality_pcode' => $post['municipality_pcode'] ?? ($visit['municipality_pcode'] ?? null),

            'household_no' => trim($post['household_no']),

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

        $payload = $this->applyLocationLocksToPayload($actor, $payload);

        $db = \Config\Database::connect();
        $db->transStart();

        $visitModel->update($id, $payload);

        // delete old groups -> members -> quarters
        $oldGroups = $familyModel->where('visit_id', $id)->findAll();
        foreach ($oldGroups as $og) {
            $oldMembers = $groupMemberModel->where('family_group_id', $og['id'])->findAll();
            foreach ($oldMembers as $om) {
                $qModel->where('group_member_id', $om['id'])->delete();
            }
            $groupMemberModel->where('family_group_id', $og['id'])->delete();
        }
        $familyModel->where('visit_id', $id)->delete();

        // re-insert fresh using store logic (reuse by calling store is messy)
        // We'll call the same logic as store, but inline:
        $this->insertGroupsForVisit($id, $visitDate, $groups);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->withInput()->with('error', 'Failed to update profiling.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling'))->with('success', 'Updated.');
    }

    public function show(int $id)
    {
        return redirect()->to(base_url('admin/registry/household-profiling/' . $id . '/edit'));
    }

    /**
     * AJAX: Search existing hh_members records for linking.
     * GET ?q=... (name/dob partial)
     */
    public function searchMembers()
    {
        $actor = $this->actor();
        $q = trim((string)$this->request->getGet('q'));

        if ($q === '' || strlen($q) < 2) {
            return $this->response->setJSON([]);
        }

        $memberModel = new HhMemberModel();
        $db = \Config\Database::connect();

        // join visits for location context
        $builder = $db->table('hh_members m')
            ->select('m.id, m.last_name, m.first_name, m.middle_name, m.sex, m.dob, v.barangay_pcode, v.municipality_pcode, v.sitio_purok, v.household_no, v.visit_date, v.id as visit_id')
            ->join('hh_visits v', 'v.id = m.visit_id', 'left')
            ->orderBy('m.last_name', 'ASC')
            ->limit(25);

        // scope: super admin = all, admin/staff = municipality, others = municipality if known else barangay
        if (($actor['user_type'] ?? '') === 'super_admin') {
            // no filter
        } elseif (in_array(($actor['user_type'] ?? ''), ['admin', 'staff'], true)) {
            if (!empty($actor['municipality_pcode'])) {
                $builder->where('v.municipality_pcode', $actor['municipality_pcode']);
            }
        } else {
            if (!empty($actor['municipality_pcode'])) {
                $builder->where('v.municipality_pcode', $actor['municipality_pcode']);
            } elseif (!empty($actor['barangay_pcode'])) {
                $builder->where('v.barangay_pcode', $actor['barangay_pcode']);
            }
        }

        // search: match "last, first" or any part
        $builder->groupStart()
            ->like('m.last_name', $q)
            ->orLike('m.first_name', $q)
            ->orLike('m.middle_name', $q)
            ->groupEnd();

        $rows = $builder->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $full = trim(($r['last_name'] ?? '') . ', ' . ($r['first_name'] ?? '') . ' ' . ($r['middle_name'] ?? ''));
            $out[] = [
                'id' => (int)$r['id'],
                'name' => $full,
                'sex' => $r['sex'] ?? '',
                'dob' => $r['dob'] ?? '',
                'barangay_pcode' => $r['barangay_pcode'] ?? '',
                'municipality_pcode' => $r['municipality_pcode'] ?? '',
                'sitio_purok' => $r['sitio_purok'] ?? '',
                'household_no' => $r['household_no'] ?? '',
                'visit_date' => $r['visit_date'] ?? '',
                'visit_id' => (int)($r['visit_id'] ?? 0),
            ];
        }

        return $this->response->setJSON($out);
    }

    /* =========================
       Internal helper for update
    ========================= */

    private function insertGroupsForVisit(int $visitId, string $visitDate, array $groups): void
    {
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $hhMemberModel = new HhMemberModel();

        $year = (int)substr($visitDate, 0, 4);

        foreach ($groups as $g) {
            $groupId = $familyModel->insert([
                'visit_id' => $visitId,
                'group_name' => trim($g['group_name'] ?? '') ?: null,
                'living_status' => $g['living_status'],
                'notes' => trim($g['notes'] ?? '') ?: null,
            ], true);

            $members = $g['members'] ?? [];
            if (!is_array($members)) $members = [];

            foreach ($members as $m) {
                $linkedId = !empty($m['linked_member_id']) ? (int)$m['linked_member_id'] : null;

                $med = $m['medical_history'] ?? [];
                if (!is_array($med)) $med = [];
                $medStr = implode(',', array_values(array_filter($med)));

                $snapshot = [
                    'local_last_name' => trim($m['local_last_name'] ?? ''),
                    'local_first_name' => trim($m['local_first_name'] ?? ''),
                    'local_middle_name' => trim($m['local_middle_name'] ?? ''),
                    'sex' => trim($m['sex'] ?? ''),
                    'dob' => !empty($m['dob']) ? $this->toDbDate($m['dob']) : null,
                    'civil_status' => trim($m['civil_status'] ?? '') ?: null,

                    'philhealth_id' => trim($m['philhealth_id'] ?? '') ?: null,
                    'membership_type' => trim($m['membership_type'] ?? '') ?: null,
                    'philhealth_category' => trim($m['philhealth_category'] ?? '') ?: null,

                    'lmp_date' => !empty($m['lmp_date']) ? $this->toDbDate($m['lmp_date']) : null,
                    'educ_attainment' => trim($m['educ_attainment'] ?? '') ?: null,
                    'religion' => trim($m['religion'] ?? '') ?: null,
                ];

                if ($linkedId) {
                    $linked = $hhMemberModel->find($linkedId);
                    if ($linked) {
                        if ($snapshot['local_last_name'] === '') $snapshot['local_last_name'] = $linked['last_name'] ?? '';
                        if ($snapshot['local_first_name'] === '') $snapshot['local_first_name'] = $linked['first_name'] ?? '';
                        if ($snapshot['local_middle_name'] === '') $snapshot['local_middle_name'] = $linked['middle_name'] ?? '';
                        if (($snapshot['sex'] ?? '') === '') $snapshot['sex'] = $linked['sex'] ?? '';
                        if (empty($snapshot['dob'])) $snapshot['dob'] = $linked['dob'] ?? null;

                        if (empty($snapshot['civil_status'])) {
                            $snapshot['civil_status'] = $this->mapLegacyCivilStatus($linked['civil_status'] ?? null);
                        }

                        if (empty($snapshot['philhealth_id'])) $snapshot['philhealth_id'] = $linked['philhealth_id'] ?? null;
                        if (empty($snapshot['membership_type'])) $snapshot['membership_type'] = $linked['membership_type'] ?? null;
                        if (empty($snapshot['philhealth_category'])) $snapshot['philhealth_category'] = $linked['philhealth_category'] ?? null;

                        if (empty($snapshot['educ_attainment'])) $snapshot['educ_attainment'] = $linked['educ_attainment'] ?? null;
                        if (empty($snapshot['religion'])) $snapshot['religion'] = $linked['religion'] ?? null;
                    }
                }

                $gmId = $groupMemberModel->insert([
                    'family_group_id' => $groupId,
                    'linked_member_id' => $linkedId,

                    'local_last_name' => $snapshot['local_last_name'] ?: null,
                    'local_first_name' => $snapshot['local_first_name'] ?: null,
                    'local_middle_name' => $snapshot['local_middle_name'] ?: null,

                    'relationship_code' => !empty($m['relationship_code']) ? (int)$m['relationship_code'] : null,
                    'relationship_other' => trim($m['relationship_other'] ?? '') ?: null,

                    'sex' => $snapshot['sex'] ?: null,
                    'dob' => $snapshot['dob'],
                    'civil_status' => $snapshot['civil_status'],

                    'philhealth_id' => $snapshot['philhealth_id'],
                    'membership_type' => $snapshot['membership_type'],
                    'philhealth_category' => $snapshot['philhealth_category'],

                    'medical_history' => $medStr ?: null,

                    'lmp_date' => $snapshot['lmp_date'],
                    'educ_attainment' => $snapshot['educ_attainment'],
                    'religion' => $snapshot['religion'],

                    'status_in_household' => trim($m['status_in_household'] ?? '') ?: null,
                    'stay_from' => !empty($m['stay_from']) ? $this->toDbDate($m['stay_from']) : null,
                    'stay_to' => !empty($m['stay_to']) ? $this->toDbDate($m['stay_to']) : null,

                    'remarks' => trim($m['remarks'] ?? '') ?: null,
                ], true);

                for ($q = 1; $q <= 4; $q++) {
                    $age = isset($m["q{$q}_age"]) && $m["q{$q}_age"] !== '' ? (int)$m["q{$q}_age"] : null;
                    $class = trim((string)($m["q{$q}_class"] ?? ''));

                    if ($age === null && $class === '') continue;

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

    /* =========================
       Validation + Helpers
    ========================= */

    // private function actor(): array
    // {
    //     $u = session('auth_user');

    //     if (!$u) {
    //         return [
    //             'id' => null,
    //             'user_type' => 'super_admin',
    //             'municipality_pcode' => null,
    //             'barangay_pcode' => null,
    //             'province_pcode' => null,
    //         ];
    //     }

    //     return is_array($u) ? $u : [];
    // }

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

        if (!isset($post['socioeconomic_status'])) return 'Socioeconomic status is required.';
        if (in_array(($post['socioeconomic_status'] ?? ''), ['nhts', 'nhts_4ps'], true) && empty($post['nhts_no'])) {
            // optional in your UI, but you can enforce if you want
        }

        if (!isset($post['water_source'])) return 'Water source is required.';
        if (($post['water_source'] ?? '') === 'others' && empty($post['water_source_other'])) {
            return 'Please specify water source.';
        }

        if (empty($post['toilet_facility'])) return 'Toilet type is required.';

        if (!is_array($groups) || empty($groups)) {
            return 'Please add at least one Family Group.';
        }

        foreach ($groups as $gi => $g) {
            if (empty($g['living_status'])) {
                return 'Family Group living status is required.';
            }

            $members = $g['members'] ?? null;
            if (!is_array($members) || empty($members)) {
                return 'Each Family Group must have at least one member.';
            }

            foreach ($members as $mi => $m) {
                $linked = !empty($m['linked_member_id']);

                if (!$linked) {
                    if (empty($m['local_last_name']) || empty($m['local_first_name'])) {
                        return 'Member name (Last/First) is required.';
                    }
                    if (empty($m['sex'])) return 'Member sex is required.';
                    if (empty($m['dob'])) return 'Member DOB is required.';
                }

                // If relationship_code "Others" then require relationship_other
                $rc = isset($m['relationship_code']) ? (int)$m['relationship_code'] : null;
                if ($rc === 5 && empty($m['relationship_other'])) {
                    return 'Please specify relationship (Others).';
                }
            }
        }

        return null;
    }

    private function toDbDate(string $dateStr): string
    {
        // Accept YYYY-MM-DD from <input type="date">; also accept MM/DD/YYYY
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
        $m = (int)substr($ymd, 5, 2);
        return (int)ceil($m / 3);
    }

    private function canAccessVisit(array $actor, array $visit): bool
    {
        $type = $actor['user_type'] ?? '';

        if ($type === 'super_admin') return true;

        if (in_array($type, ['admin', 'staff'], true)) {
            return !empty($actor['municipality_pcode']) && ($visit['municipality_pcode'] ?? null) === $actor['municipality_pcode'];
        }

        return !empty($actor['barangay_pcode']) && ($visit['barangay_pcode'] ?? null) === $actor['barangay_pcode'];
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

        if (in_array($type, ['admin', 'staff'], true)) {
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

        if (in_array($type, ['admin', 'staff'], true)) {
            $payload['municipality_pcode'] = $actor['municipality_pcode'] ?? $payload['municipality_pcode'] ?? null;
            return $payload;
        }

        $payload['municipality_pcode'] = $actor['municipality_pcode'] ?? $payload['municipality_pcode'] ?? null;
        $payload['barangay_pcode'] = $actor['barangay_pcode'] ?? $payload['barangay_pcode'];
        return $payload;
    }

    private function mapLegacyCivilStatus(?string $legacy): ?string
    {
        $legacy = strtoupper(trim((string)$legacy));
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
}