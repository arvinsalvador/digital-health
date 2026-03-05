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
        $builder = $visitModel->orderBy('visit_date', 'DESC')->orderBy('id', 'DESC');

        // Scope filtering
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
            'currentUserName' => $this->currentUserName(),
        ]);
    }

    public function create()
    {
        $actor = $this->actor();

        return view('admin/registry/household_profiling/form', [
            'pageTitle' => 'New Household Visit',
            'mode' => 'create',
            'visit' => null,
            'members' => [],
            'quarters' => [],
            'actor' => $actor,
            'currentUserName' => $this->currentUserName(),
        ]);
    }

    public function store()
    {
        $actor = $this->actor();

        $visitDate = (string) $this->request->getPost('visit_date');
        $sitio     = trim((string) $this->request->getPost('sitio'));
        $barangay  = (string) $this->request->getPost('barangay_pcode');
        $houseNo   = trim((string) $this->request->getPost('household_no'));
        $respLn    = trim((string) $this->request->getPost('respondent_last_name'));
        $respFn    = trim((string) $this->request->getPost('respondent_first_name'));
        $respMn    = trim((string) $this->request->getPost('respondent_middle_name'));
        $relation  = trim((string) $this->request->getPost('respondent_relation'));
        $ethType   = (string) $this->request->getPost('ethnicity_type'); // "IP Household" | "Non-IP" (or your UI choice)
        $tribe     = trim((string) $this->request->getPost('ethnicity_tribe'));
        $ses       = trim((string) $this->request->getPost('socioeconomic_status'));

        // Enforce barangay locking for non-RHU / non-superadmin users
        if (!in_array(($actor['user_type'] ?? ''), ['super_admin', 'admin', 'staff'], true)) {
            $barangay = (string)($actor['barangay_pcode'] ?? '');
        }

        // Basic validation (adjust fields as needed)
        if ($visitDate === '' || $sitio === '' || $barangay === '' || $respLn === '' || $respFn === '') {
            return redirect()->back()->withInput()->with('error', 'Please fill in all required fields (Visit Date, Sitio/Purok, Barangay, Respondent Name).');
        }

        $quarterKey = $this->quarterKeyFromDate($visitDate);

        $db = db_connect();
        $db->transStart();

        try {
            $visitModel = new HhVisitModel();

            $visitData = [
                'visit_date' => $visitDate,
                'quarter_key' => $quarterKey,

                'interviewed_by_user_id' => (int)($actor['id'] ?? 0),
                'interviewed_by_name' => $this->currentUserName(),

                'sitio' => $sitio,
                'barangay_pcode' => $barangay,
                'municipality_pcode' => $actor['municipality_pcode'] ?? null,
                'province_pcode' => $actor['province_pcode'] ?? null,
                'region_pcode' => $actor['region_pcode'] ?? null,

                'household_no' => $houseNo !== '' ? $houseNo : null,

                'respondent_last_name' => $respLn,
                'respondent_first_name' => $respFn,
                'respondent_middle_name' => $respMn !== '' ? $respMn : null,
                'respondent_relation' => $relation !== '' ? $relation : null,

                // Ethnicity handling
                'ethnicity_type' => $ethType !== '' ? $ethType : null,
                'ethnicity_tribe' => ($ethType === 'IP Household' && $tribe !== '') ? $tribe : null,

                'socioeconomic_status' => $ses !== '' ? $ses : null,
            ];

            $visitId = (int) $visitModel->insert($visitData, true);

            // Members payload:
            // members[0][last_name], members[0][first_name], ...
            // members[0][quarters][2026-Q1][weight], etc.
            $members = $this->request->getPost('members');
            if (!is_array($members)) {
                $members = [];
            }

            $memberModel = new HhMemberModel();
            $quarterModel = new HhMemberQuarterModel();

            foreach ($members as $m) {
                if (!is_array($m)) {
                    continue;
                }

                $mLn = trim((string)($m['last_name'] ?? ''));
                $mFn = trim((string)($m['first_name'] ?? ''));

                // Skip empty member rows
                if ($mLn === '' && $mFn === '') {
                    continue;
                }

                $memberData = [
                    'visit_id' => $visitId,
                    'last_name' => $mLn,
                    'first_name' => $mFn,
                    'middle_name' => trim((string)($m['middle_name'] ?? '')) ?: null,
                    'sex' => (string)($m['sex'] ?? null) ?: null,
                    'birthdate' => (string)($m['birthdate'] ?? null) ?: null,
                    'relationship_to_head' => trim((string)($m['relationship_to_head'] ?? '')) ?: null,
                    'remarks' => trim((string)($m['remarks'] ?? '')) ?: null,
                ];

                $memberId = (int) $memberModel->insert($memberData, true);

                // Quarters for this member
                $quarters = $m['quarters'] ?? [];
                if (is_array($quarters)) {
                    foreach ($quarters as $qKey => $qPayload) {
                        if (!is_array($qPayload)) {
                            continue;
                        }

                        // Only store if at least one value is provided
                        if (!$this->hasAnyQuarterValue($qPayload)) {
                            continue;
                        }

                        $quarterRow = [
                            'member_id' => $memberId,
                            'quarter_key' => (string)$qKey,

                            // Common examples (edit to match your DB columns)
                            'weight' => $this->nullableNumber($qPayload['weight'] ?? null),
                            'height' => $this->nullableNumber($qPayload['height'] ?? null),
                            'bp_systolic' => $this->nullableNumber($qPayload['bp_systolic'] ?? null),
                            'bp_diastolic' => $this->nullableNumber($qPayload['bp_diastolic'] ?? null),
                            'notes' => trim((string)($qPayload['notes'] ?? '')) ?: null,
                        ];

                        $quarterModel->insert($quarterRow);
                    }
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()->withInput()->with('error', 'Failed to save visit. Please try again.');
            }

            return redirect()->to(base_url('admin/registry/household-profiling'))->with('success', 'Household visit saved successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'HouseholdProfiling store error: {msg}', ['msg' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'An unexpected error occurred while saving. Please check logs.');
        }
    }

    public function edit($id)
    {
        $actor = $this->actor();

        $visitModel = new HhVisitModel();
        $memberModel = new HhMemberModel();
        $quarterModel = new HhMemberQuarterModel();

        $visit = $visitModel->find((int)$id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Visit not found.');
        }

        // Scope check
        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Access denied.');
        }

        $members = $memberModel->where('visit_id', (int)$id)->orderBy('id', 'ASC')->findAll();

        // Load quarters for members and group them: [member_id][quarter_key] => row
        $quartersByMember = [];
        if (!empty($members)) {
            $memberIds = array_map(fn($m) => (int)$m['id'], $members);

            $rows = $quarterModel->whereIn('member_id', $memberIds)->findAll();
            foreach ($rows as $r) {
                $mid = (int)$r['member_id'];
                $qk = (string)$r['quarter_key'];
                $quartersByMember[$mid][$qk] = $r;
            }
        }

        return view('admin/registry/household_profiling/form', [
            'pageTitle' => 'Edit Household Visit',
            'mode' => 'edit',
            'visit' => $visit,
            'members' => $members,
            'quarters' => $quartersByMember,
            'actor' => $actor,
            'currentUserName' => $this->currentUserName(),
        ]);
    }

    public function update($id)
    {
        $actor = $this->actor();

        $visitModel = new HhVisitModel();
        $memberModel = new HhMemberModel();
        $quarterModel = new HhMemberQuarterModel();

        $visit = $visitModel->find((int)$id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Visit not found.');
        }

        // Scope check
        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Access denied.');
        }

        $visitDate = (string) $this->request->getPost('visit_date');
        $sitio     = trim((string) $this->request->getPost('sitio'));
        $barangay  = (string) $this->request->getPost('barangay_pcode');
        $houseNo   = trim((string) $this->request->getPost('household_no'));

        $respLn    = trim((string) $this->request->getPost('respondent_last_name'));
        $respFn    = trim((string) $this->request->getPost('respondent_first_name'));
        $respMn    = trim((string) $this->request->getPost('respondent_middle_name'));
        $relation  = trim((string) $this->request->getPost('respondent_relation'));

        $ethType   = (string) $this->request->getPost('ethnicity_type');
        $tribe     = trim((string) $this->request->getPost('ethnicity_tribe'));
        $ses       = trim((string) $this->request->getPost('socioeconomic_status'));

        if (!in_array(($actor['user_type'] ?? ''), ['super_admin', 'admin', 'staff'], true)) {
            $barangay = (string)($actor['barangay_pcode'] ?? '');
        }

        if ($visitDate === '' || $sitio === '' || $barangay === '' || $respLn === '' || $respFn === '') {
            return redirect()->back()->withInput()->with('error', 'Please fill in all required fields (Visit Date, Sitio/Purok, Barangay, Respondent Name).');
        }

        $quarterKey = $this->quarterKeyFromDate($visitDate);

        $db = db_connect();
        $db->transStart();

        try {
            $visitUpdate = [
                'visit_date' => $visitDate,
                'quarter_key' => $quarterKey,

                'sitio' => $sitio,
                'barangay_pcode' => $barangay,

                'household_no' => $houseNo !== '' ? $houseNo : null,

                'respondent_last_name' => $respLn,
                'respondent_first_name' => $respFn,
                'respondent_middle_name' => $respMn !== '' ? $respMn : null,
                'respondent_relation' => $relation !== '' ? $relation : null,

                'ethnicity_type' => $ethType !== '' ? $ethType : null,
                'ethnicity_tribe' => ($ethType === 'IP Household' && $tribe !== '') ? $tribe : null,

                'socioeconomic_status' => $ses !== '' ? $ses : null,
            ];

            $visitModel->update((int)$id, $visitUpdate);

            // Existing members list in DB
            $existingMembers = $memberModel->where('visit_id', (int)$id)->findAll();
            $existingIds = array_map(fn($m) => (int)$m['id'], $existingMembers);

            // Incoming members
            $members = $this->request->getPost('members');
            if (!is_array($members)) {
                $members = [];
            }

            $seenIds = [];

            foreach ($members as $m) {
                if (!is_array($m)) {
                    continue;
                }

                $memberId = (int)($m['id'] ?? 0);

                $mLn = trim((string)($m['last_name'] ?? ''));
                $mFn = trim((string)($m['first_name'] ?? ''));

                // Skip empty member rows
                if ($mLn === '' && $mFn === '') {
                    continue;
                }

                $memberData = [
                    'visit_id' => (int)$id,
                    'last_name' => $mLn,
                    'first_name' => $mFn,
                    'middle_name' => trim((string)($m['middle_name'] ?? '')) ?: null,
                    'sex' => (string)($m['sex'] ?? null) ?: null,
                    'birthdate' => (string)($m['birthdate'] ?? null) ?: null,
                    'relationship_to_head' => trim((string)($m['relationship_to_head'] ?? '')) ?: null,
                    'remarks' => trim((string)($m['remarks'] ?? '')) ?: null,
                ];

                if ($memberId > 0 && in_array($memberId, $existingIds, true)) {
                    $memberModel->update($memberId, $memberData);
                    $seenIds[] = $memberId;
                } else {
                    $memberId = (int)$memberModel->insert($memberData, true);
                    $seenIds[] = $memberId;
                }

                // Upsert quarters per member
                $quarters = $m['quarters'] ?? [];
                if (is_array($quarters)) {
                    foreach ($quarters as $qKey => $qPayload) {
                        if (!is_array($qPayload)) {
                            continue;
                        }

                        // If nothing set, skip
                        if (!$this->hasAnyQuarterValue($qPayload)) {
                            continue;
                        }

                        $row = [
                            'member_id' => $memberId,
                            'quarter_key' => (string)$qKey,
                            'weight' => $this->nullableNumber($qPayload['weight'] ?? null),
                            'height' => $this->nullableNumber($qPayload['height'] ?? null),
                            'bp_systolic' => $this->nullableNumber($qPayload['bp_systolic'] ?? null),
                            'bp_diastolic' => $this->nullableNumber($qPayload['bp_diastolic'] ?? null),
                            'notes' => trim((string)($qPayload['notes'] ?? '')) ?: null,
                        ];

                        // Check if exists
                        $existing = $quarterModel
                            ->where('member_id', $memberId)
                            ->where('quarter_key', (string)$qKey)
                            ->first();

                        if ($existing) {
                            $quarterModel->update((int)$existing['id'], $row);
                        } else {
                            $quarterModel->insert($row);
                        }
                    }
                }
            }

            // Optional: delete removed members (and their quarters) if not present in incoming
            // Comment out if you want to keep historical rows.
            $toDelete = array_diff($existingIds, $seenIds);
            if (!empty($toDelete)) {
                $quarterModel->whereIn('member_id', $toDelete)->delete();
                $memberModel->whereIn('id', $toDelete)->delete();
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return redirect()->back()->withInput()->with('error', 'Failed to update visit. Please try again.');
            }

            return redirect()->to(base_url('admin/registry/household-profiling'))->with('success', 'Household visit updated successfully.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'HouseholdProfiling update error: {msg}', ['msg' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'An unexpected error occurred while updating. Please check logs.');
        }
    }

    public function show($id)
    {
        $actor = $this->actor();

        $visitModel = new HhVisitModel();
        $memberModel = new HhMemberModel();
        $quarterModel = new HhMemberQuarterModel();

        $visit = $visitModel->find((int)$id);
        if (!$visit) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Visit not found.');
        }

        // Scope check
        if (!$this->canAccessVisit($actor, $visit)) {
            return redirect()->to(base_url('admin/registry/household-profiling'))->with('error', 'Access denied.');
        }

        $members = $memberModel->where('visit_id', (int)$id)->orderBy('id', 'ASC')->findAll();

        $quartersByMember = [];
        if (!empty($members)) {
            $memberIds = array_map(fn($m) => (int)$m['id'], $members);

            $rows = $quarterModel->whereIn('member_id', $memberIds)->findAll();
            foreach ($rows as $r) {
                $mid = (int)$r['member_id'];
                $qk = (string)$r['quarter_key'];
                $quartersByMember[$mid][$qk] = $r;
            }
        }

        return view('admin/registry/household_profiling/show', [
            'pageTitle' => 'Household Visit Details',
            'visit' => $visit,
            'members' => $members,
            'quarters' => $quartersByMember,
            'actor' => $actor,
            'currentUserName' => $this->currentUserName(),
        ]);
    }

    // -------------------------
    // Helpers
    // -------------------------

    private function canAccessVisit(array $actor, array $visit): bool
    {
        $type = (string)($actor['user_type'] ?? '');

        if ($type === 'super_admin') {
            return true;
        }

        if (in_array($type, ['admin', 'staff'], true)) {
            return (string)($visit['municipality_pcode'] ?? '') !== ''
                && (string)($visit['municipality_pcode'] ?? '') === (string)($actor['municipality_pcode'] ?? '');
        }

        return (string)($visit['barangay_pcode'] ?? '') !== ''
            && (string)($visit['barangay_pcode'] ?? '') === (string)($actor['barangay_pcode'] ?? '');
    }

    /**
     * Produces a quarter key like "2026-Q1" from a date string.
     * Accepts "YYYY-MM-DD" or anything strtotime() can parse.
     */
    private function quarterKeyFromDate(string $date): string
    {
        $ts = strtotime($date);
        if ($ts === false) {
            // fallback: treat as current date
            $ts = time();
        }

        $year = (int)date('Y', $ts);
        $month = (int)date('n', $ts);
        $q = (int)ceil($month / 3);

        return $year . '-Q' . $q;
    }

    private function nullableNumber($value): ?float
    {
        if ($value === null) {
            return null;
        }
        $v = trim((string)$value);
        if ($v === '') {
            return null;
        }
        if (!is_numeric($v)) {
            return null;
        }
        return (float)$v;
    }

    /**
     * Checks whether quarter payload has any meaningful input.
     * Add/remove keys based on your form and DB columns.
     */
    private function hasAnyQuarterValue(array $qPayload): bool
    {
        $keys = ['weight', 'height', 'bp_systolic', 'bp_diastolic', 'notes'];

        foreach ($keys as $k) {
            if (!array_key_exists($k, $qPayload)) {
                continue;
            }
            $v = $qPayload[$k];
            if (is_string($v) && trim($v) !== '') {
                return true;
            }
            if (is_numeric($v)) {
                return true;
            }
        }

        return false;
    }
}