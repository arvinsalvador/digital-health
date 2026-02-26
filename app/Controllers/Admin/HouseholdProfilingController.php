<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitModel;
use App\Models\HhMemberModel;
use App\Models\HhMemberQuarterModel;

class HouseholdProfilingController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();
        $visitModel = new HhVisitModel();

        $builder = $visitModel->orderBy('visit_date','DESC')->orderBy('id','DESC');

        // Scope filtering (works later when auth exists)
        if (($actor['user_type'] ?? '') === 'super_admin') {
            $visits = $builder->findAll();
        } elseif (in_array(($actor['user_type'] ?? ''), ['admin','staff'], true)) {
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
            'pageTitle' => 'New Household Profiling',
            'mode' => 'create',
            'visit' => null,
            'members' => [],
            'actor' => $actor,
            'lock' => $this->locationLockForActor($actor),
        ]);
    }

    public function store()
    {
        $actor = $this->actor();

        $post = $this->request->getPost();
        $members = $this->request->getPost('members'); // array

        // Server-side validation
        $error = $this->validateVisitAndMembers($actor, $post, $members);
        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        $visitDate = $this->toDbDate($post['visit_date']);
        $quarter = $this->quarterFromDate($visitDate);

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

        // Enforce scope + locks server-side
        $visitPayload = $this->applyLocationLocksToPayload($actor, $visitPayload);

        $visitModel = new HhVisitModel();
        $memberModel = new HhMemberModel();
        $qModel = new HhMemberQuarterModel();

        $db = \Config\Database::connect();
        $db->transStart();

        $visitId = $visitModel->insert($visitPayload, true);

        // Insert members + quarters
        $year = (int) substr($visitDate, 0, 4);

        foreach ($members as $m) {
            $med = $m['medical_history'] ?? [];
            if (!is_array($med)) $med = [];
            $medStr = implode(',', array_values(array_filter($med)));

            $memberId = $memberModel->insert([
                'visit_id' => $visitId,
                'last_name' => trim($m['last_name']),
                'first_name' => trim($m['first_name']),
                'middle_name' => trim($m['middle_name'] ?? '') ?: null,

                'relationship_code' => (int)$m['relationship_code'],
                'relationship_other' => trim($m['relationship_other'] ?? '') ?: null,

                'sex' => $m['sex'],
                'dob' => $this->toDbDate($m['dob']),
                'civil_status' => $m['civil_status'],

                'philhealth_id' => trim($m['philhealth_id'] ?? '') ?: null,
                'membership_type' => trim($m['membership_type'] ?? '') ?: null,
                'philhealth_category' => trim($m['philhealth_category'] ?? '') ?: null,

                'medical_history' => $medStr ?: null,

                'lmp_date' => !empty($m['lmp_date']) ? $this->toDbDate($m['lmp_date']) : null,

                'educ_attainment' => trim($m['educ_attainment'] ?? '') ?: null,
                'religion' => trim($m['religion'] ?? '') ?: null,

                'remarks' => trim($m['remarks'] ?? '') ?: null,
            ], true);

            // quarters (required 1-4)
            for ($q=1; $q<=4; $q++) {
                $age = (int)($m["q{$q}_age"] ?? 0);
                $class = trim((string)($m["q{$q}_class"] ?? ''));
                $qModel->insert([
                    'member_id' => $memberId,
                    'year' => $year,
                    'quarter' => $q,
                    'age' => $age,
                    'class_code' => $class,
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return back()->withInput()->with('error', 'Failed to save profiling. Please try again.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling'))
            ->with('success', 'Household profiling saved.');
    }

    public function edit(int $id)
    {
        $actor = $this->actor();
        $visitModel = new HhVisitModel();
        $memberModel = new HhMemberModel();
        $qModel = new HhMemberQuarterModel();

        $visit = $visitModel->find($id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error','Record not found.');
        }

        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error','Not allowed.');
        }

        $members = $memberModel->where('visit_id', $id)->findAll();

        // attach quarters (year from visit_date)
        $year = (int)substr($visit['visit_date'], 0, 4);
        foreach ($members as &$m) {
            $quarters = $qModel->where('member_id', $m['id'])->where('year',$year)->findAll();
            // map by quarter
            $map = [];
            foreach ($quarters as $qq) $map[(int)$qq['quarter']] = $qq;
            for ($q=1; $q<=4; $q++) {
                $m["q{$q}_age"] = $map[$q]['age'] ?? '';
                $m["q{$q}_class"] = $map[$q]['class_code'] ?? '';
            }
            // medical history array for checkboxes
            $m['medical_history_arr'] = !empty($m['medical_history']) ? explode(',', $m['medical_history']) : [];
        }
        unset($m);

        // Sort members for display
        $members = $this->sortMembers($members);

        return view('admin/registry/household_profiling/form', [
            'pageTitle' => 'Edit Household Profiling',
            'mode' => 'edit',
            'visit' => $visit,
            'members' => $members,
            'actor' => $actor,
            'lock' => $this->locationLockForActor($actor),
        ]);
    }

    public function update(int $id)
    {
        // For Phase 1 simplicity: implement update later (or you can reuse store logic with delete+reinsert members/quarters).
        // I’m giving you a safe “replace members + quarters” update now.

        $actor = $this->actor();
        $visitModel = new HhVisitModel();
        $memberModel = new HhMemberModel();
        $qModel = new HhMemberQuarterModel();

        $visit = $visitModel->find($id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error','Record not found.');
        }
        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error','Not allowed.');
        }

        $post = $this->request->getPost();
        $members = $this->request->getPost('members');

        $error = $this->validateVisitAndMembers($actor, $post, $members);
        if ($error) {
            return back()->withInput()->with('error', $error);
        }

        $visitDate = $this->toDbDate($post['visit_date']);
        $quarter = $this->quarterFromDate($visitDate);
        $year = (int)substr($visitDate,0,4);

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

        // remove old members + quarters then reinsert
        $oldMembers = $memberModel->where('visit_id',$id)->findAll();
        foreach ($oldMembers as $om) {
            $qModel->where('member_id', $om['id'])->delete();
        }
        $memberModel->where('visit_id',$id)->delete();

        foreach ($members as $m) {
            $med = $m['medical_history'] ?? [];
            if (!is_array($med)) $med = [];
            $medStr = implode(',', array_values(array_filter($med)));

            $memberId = $memberModel->insert([
                'visit_id' => $id,
                'last_name' => trim($m['last_name']),
                'first_name' => trim($m['first_name']),
                'middle_name' => trim($m['middle_name'] ?? '') ?: null,

                'relationship_code' => (int)$m['relationship_code'],
                'relationship_other' => trim($m['relationship_other'] ?? '') ?: null,

                'sex' => $m['sex'],
                'dob' => $this->toDbDate($m['dob']),
                'civil_status' => $m['civil_status'],

                'philhealth_id' => trim($m['philhealth_id'] ?? '') ?: null,
                'membership_type' => trim($m['membership_type'] ?? '') ?: null,
                'philhealth_category' => trim($m['philhealth_category'] ?? '') ?: null,

                'medical_history' => $medStr ?: null,

                'lmp_date' => !empty($m['lmp_date']) ? $this->toDbDate($m['lmp_date']) : null,

                'educ_attainment' => trim($m['educ_attainment'] ?? '') ?: null,
                'religion' => trim($m['religion'] ?? '') ?: null,

                'remarks' => trim($m['remarks'] ?? '') ?: null,
            ], true);

            for ($q=1; $q<=4; $q++) {
                $qModel->insert([
                    'member_id' => $memberId,
                    'year' => $year,
                    'quarter' => $q,
                    'age' => (int)($m["q{$q}_age"] ?? 0),
                    'class_code' => trim((string)($m["q{$q}_class"] ?? '')),
                ]);
            }
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return back()->withInput()->with('error','Failed to update profiling.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling'))->with('success','Updated.');
    }

    public function show(int $id)
    {
        // Optional Phase 1: simple read-only page (can expand later)
        return redirect()->to(base_url('admin/registry/household-profiling/'.$id.'/edit'));
    }

    /* =========================
       Validation + Helpers
    ========================= */

    private function actor(): array
    {
        $u = session('auth_user');

        // dev fallback so you can test even without auth
        if (!$u) {
            return [
                'id' => null,
                'user_type' => 'super_admin',
                'municipality_pcode' => null,
                'barangay_pcode' => null,
            ];
        }
        return $u;
    }

    private function locationLockForActor(array $actor): array
    {
        // SA: choose all
        $lock = [
            'barangay_locked' => false,
            'municipality_locked' => false,
            'barangay_pcode' => $actor['barangay_pcode'] ?? null,
            'municipality_pcode' => $actor['municipality_pcode'] ?? null,
        ];

        $type = $actor['user_type'] ?? '';

        if ($type === 'super_admin') return $lock;

        // Admin/Staff can choose barangay but within their municipality (municipality locked)
        if (in_array($type, ['admin','staff'], true)) {
            $lock['municipality_locked'] = true;
            $lock['barangay_locked'] = false;
            return $lock;
        }

        // Barangay roles: barangay locked
        $lock['municipality_locked'] = true;
        $lock['barangay_locked'] = true;
        return $lock;
    }

    private function applyLocationLocksToPayload(array $actor, array $payload): array
    {
        $lock = $this->locationLockForActor($actor);

        if (!empty($lock['municipality_locked'])) {
            $payload['municipality_pcode'] = $lock['municipality_pcode'];
        }
        if (!empty($lock['barangay_locked'])) {
            $payload['barangay_pcode'] = $lock['barangay_pcode'];
        }

        // Scope enforcement: non-SA must stay in their scope
        $type = $actor['user_type'] ?? '';
        if ($type !== 'super_admin') {
            if (in_array($type, ['admin','staff'], true)) {
                if (($payload['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) {
                    throw new \RuntimeException('Invalid municipality scope.');
                }
            } else {
                if (($payload['barangay_pcode'] ?? null) !== ($actor['barangay_pcode'] ?? null)) {
                    throw new \RuntimeException('Invalid barangay scope.');
                }
            }
        }

        return $payload;
    }

    private function canAccessVisit(array $actor, array $visit): bool
    {
        $type = $actor['user_type'] ?? '';
        if ($type === 'super_admin') return true;

        if (in_array($type, ['admin','staff'], true)) {
            return ($visit['municipality_pcode'] ?? null) === ($actor['municipality_pcode'] ?? null);
        }

        return ($visit['barangay_pcode'] ?? null) === ($actor['barangay_pcode'] ?? null);
    }

    private function validateVisitAndMembers(array $actor, array $post, $members): ?string
    {
        // Visit date required
        if (empty($post['visit_date']) || !$this->isMmddyyyy($post['visit_date'])) {
            return 'Date of Visit is required (mm/dd/yyyy).';
        }

        if (empty(trim($post['sitio_purok'] ?? ''))) return 'Sitio/Purok is required.';
        if (empty(trim($post['household_no'] ?? ''))) return 'Household Number is required.';

        // Barangay required
        if (empty($post['barangay_pcode'])) return 'Barangay is required.';

        // Respondent
        if (empty(trim($post['respondent_last_name'] ?? ''))) return 'Respondent Last Name is required.';
        if (empty(trim($post['respondent_first_name'] ?? ''))) return 'Respondent First Name is required.';
        if (empty($post['respondent_relation'])) return 'Respondent relationship is required.';
        if ($post['respondent_relation'] === 'Other' && empty(trim($post['respondent_relation_other'] ?? ''))) {
            return 'Respondent relationship "Other" must be specified.';
        }

        // Ethnicity
        if (empty($post['ethnicity_mode'])) return 'Ethnicity selection is required.';
        if ($post['ethnicity_mode'] === 'tribe' && empty(trim($post['ethnicity_tribe'] ?? ''))) {
            return 'Tribe is required when ethnicity is set to Tribe.';
        }

        // Socioeconomic
        if (empty($post['socioeconomic_status'])) return 'Socioeconomic Status is required.';
        if (str_starts_with($post['socioeconomic_status'], 'nhts') && empty(trim($post['nhts_no'] ?? ''))) {
            return 'NHTS No. is required when Socioeconomic Status is NHTS.';
        }

        // Water + toilet
        if (empty($post['water_source'])) return 'Type of Water Source is required.';
        if ($post['water_source'] === 'others' && empty(trim($post['water_source_other'] ?? ''))) {
            return 'Please specify Water Source (Others).';
        }
        if (empty($post['toilet_facility'])) return 'Type of Toilet Facility is required.';

        // Members required
        if (empty($members) || !is_array($members)) {
            return 'At least one household member is required.';
        }

        foreach ($members as $i => $m) {
            $row = $i + 1;

            if (empty(trim($m['last_name'] ?? '')) || empty(trim($m['first_name'] ?? ''))) {
                return "Member #{$row}: First Name and Last Name are required.";
            }

            $rel = (int)($m['relationship_code'] ?? 0);
            if ($rel < 1 || $rel > 5) return "Member #{$row}: Relationship is required.";
            if ($rel === 5 && empty(trim($m['relationship_other'] ?? ''))) {
                return "Member #{$row}: Relationship 'Others' must be specified.";
            }

            if (!in_array(($m['sex'] ?? ''), ['M','F'], true)) return "Member #{$row}: Sex is required.";
            if (empty($m['dob']) || !$this->isMmddyyyy($m['dob'])) return "Member #{$row}: Date of Birth is required (mm/dd/yyyy).";

            $cs = $m['civil_status'] ?? '';
            if (!in_array($cs, ['M','S','W','SP','C'], true)) return "Member #{$row}: Civil Status is required.";

            // quarters required
            for ($q=1; $q<=4; $q++) {
                $age = (int)($m["q{$q}_age"] ?? 0);
                $class = trim((string)($m["q{$q}_class"] ?? ''));

                if ($age <= 0) return "Member #{$row}: Q{$q} Age is required.";
                if ($class === '') return "Member #{$row}: Q{$q} Class is required (auto-computed).";
            }
        }

        return null;
    }

    private function quarterFromDate(string $dbDate): int
    {
        $m = (int)substr($dbDate, 5, 2);
        return (int)ceil($m / 3);
    }

    private function isMmddyyyy(string $s): bool
    {
        return (bool)preg_match('/^\d{2}\/\d{2}\/\d{4}$/', trim($s));
    }

    private function toDbDate(string $mmddyyyy): string
    {
        // mm/dd/yyyy -> yyyy-mm-dd
        [$mm,$dd,$yy] = explode('/', $mmddyyyy);
        return sprintf('%04d-%02d-%02d', (int)$yy, (int)$mm, (int)$dd);
    }

    private function sortMembers(array $members): array
    {
        // relationship rank: 1 head, 2 spouse, 3/4 child, 5 others
        usort($members, function($a,$b){
            $ra = (int)($a['relationship_code'] ?? 5);
            $rb = (int)($b['relationship_code'] ?? 5);

            $rankA = ($ra===1?1:($ra===2?2:(($ra===3||$ra===4)?3:4)));
            $rankB = ($rb===1?1:($rb===2?2:(($rb===3||$rb===4)?3:4)));

            if ($rankA !== $rankB) return $rankA <=> $rankB;

            // within children: eldest to youngest (older DOB first -> smaller date)
            $dobA = $a['dob'] ?? '9999-12-31';
            $dobB = $b['dob'] ?? '9999-12-31';

            return strcmp($dobA, $dobB);
        });

        return $members;
    }
}