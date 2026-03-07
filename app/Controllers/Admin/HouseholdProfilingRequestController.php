<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitChangeRequestModel;
use App\Models\HhVisitModel;
use App\Models\HhFamilyGroupModel;
use App\Models\HhGroupMemberModel;
use App\Models\HhGroupMemberQuarterModel;
use App\Models\HhGroupMemberMedicalHistoryModel;

class HouseholdProfilingRequestController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();

        $model = new HhVisitChangeRequestModel();
        $builder = $model->builder();

        $builder->where('status', 'pending');

        if (($actor['user_type'] ?? '') === 'super_admin') {
            // all pending
        } elseif (($actor['user_type'] ?? '') === 'admin') {
            $builder->where('review_level', 'admin');
            $builder->where('municipality_pcode', $actor['municipality_pcode'] ?? null);
        } elseif (($actor['user_type'] ?? '') === 'staff') {
            $builder->where('review_level', 'staff');
            $builder->where('barangay_pcode', $actor['barangay_pcode'] ?? null);
        } else {
            return redirect()->to(base_url('admin'))->with('error', 'Not allowed.');
        }

        $rows = $builder
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view('admin/registry/household_profiling_requests/index', [
            'pageTitle' => 'Profiling Requests',
            'rows' => $rows,
            'actor' => $actor,
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function show(int $id)
    {
        $actor = $this->actor();
        $row = $this->findAccessiblePendingRequest($id, $actor);

        if (! $row) {
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Request not found or not allowed.');
        }

        $row['change_payload'] = $this->safeJsonDecode($row['change_payload_json'] ?? null);
        $row['diff_payload'] = $this->safeJsonDecode($row['diff_payload_json'] ?? null);

        $payloadVisit = $row['change_payload']['visit'] ?? [];
        $payloadGroups = $row['change_payload']['groups'] ?? [];

        $currentVisit = null;
        $currentGroups = [];

        if (! empty($row['target_visit_id'])) {
            $visitModel = new HhVisitModel();
            $familyModel = new HhFamilyGroupModel();
            $groupMemberModel = new HhGroupMemberModel();
            $qModel = new HhGroupMemberQuarterModel();
            $mhModel = new HhGroupMemberMedicalHistoryModel();

            $currentVisit = $visitModel->find((int) $row['target_visit_id']);

            if ($currentVisit) {
                $currentGroups = $familyModel
                    ->where('visit_id', (int) $row['target_visit_id'])
                    ->orderBy('id', 'ASC')
                    ->findAll();

                $year = ! empty($currentVisit['visit_date'])
                    ? (int) substr((string) $currentVisit['visit_date'], 0, 4)
                    : (int) date('Y');

                foreach ($currentGroups as &$g) {
                    $members = $groupMemberModel
                        ->where('family_group_id', $g['id'])
                        ->orderBy('id', 'ASC')
                        ->findAll();

                    foreach ($members as &$m) {
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
            }
        }

        $previewVisit = array_merge($currentVisit ?? [], is_array($payloadVisit) ? $payloadVisit : []);
        $previewGroups = is_array($payloadGroups) ? $payloadGroups : [];

        $previewBarangayPcode = $previewVisit['barangay_pcode'] ?? '';
        $previewMunicipalityPcode = $previewVisit['municipality_pcode'] ?? '';

        return view('admin/registry/household_profiling/review_form', [
            'pageTitle' => 'Review Profiling Request',
            'row' => $row,
            'actor' => $actor,
            'visit' => $previewVisit,
            'groups' => $previewGroups,
            'mode' => 'review',
            'reviewDiff' => $row['diff_payload'] ?? [],
            'currentVisit' => $currentVisit,
            'currentGroups' => $currentGroups,
            'lock' => [
                'municipality_locked' => true,
                'barangay_locked' => true,
                'municipality_pcode' => $previewMunicipalityPcode,
                'barangay_pcode' => $previewBarangayPcode,
            ],
            'lockedMunicipalityName' => $this->areaNameByPcode($previewMunicipalityPcode),
            'lockedBarangayName' => $this->areaNameByPcode($previewBarangayPcode),
            'pendingProfilingRequestCount' => $this->pendingProfilingRequestCount($actor),
        ]);
    }

    public function approve(int $id)
    {
        $actor = $this->actor();
        $row = $this->findAccessiblePendingRequest($id, $actor);

        if (! $row) {
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Request not found or not allowed.');
        }

        $payload = $this->safeJsonDecode($row['change_payload_json'] ?? null);
        $visitData = $payload['visit'] ?? [];
        $groups = $payload['groups'] ?? [];
        $requestType = (string) ($row['request_type'] ?? '');

        $visitModel = new HhVisitModel();
        $groupModel = new HhFamilyGroupModel();
        $memberModel = new HhGroupMemberModel();
        $quarterModel = new HhGroupMemberQuarterModel();
        $medicalHistoryModel = new HhGroupMemberMedicalHistoryModel();

        $db = \Config\Database::connect();
        $db->transStart();

        if ($requestType === 'create') {
            $visitData['approval_status'] = 'approved';
            $visitData['approval_action'] = null;
            $visitData['approved_by_user_id'] = $actor['id'] ?? null;
            $visitData['approved_at'] = date('Y-m-d H:i:s');
            $visitData['rejected_by_user_id'] = null;
            $visitData['rejected_at'] = null;
            $visitData['approval_remarks'] = null;
            $visitData['pending_delete_requested_by'] = null;
            $visitData['pending_delete_requested_at'] = null;

            $visitId = $visitModel->insert($visitData, true);

            $effectiveVisitDate = ! empty($visitData['last_visit_date'])
                ? (string) $visitData['last_visit_date']
                : (! empty($visitData['visit_date']) ? (string) $visitData['visit_date'] : date('Y-m-d'));

            $this->insertApprovedGroupsTree(
                $visitId,
                $effectiveVisitDate,
                is_array($groups) ? $groups : [],
                $groupModel,
                $memberModel,
                $quarterModel,
                $medicalHistoryModel
            );
        } elseif ($requestType === 'update') {
            $visitId = (int) ($row['target_visit_id'] ?? 0);

            if ($visitId <= 0) {
                $db->transRollback();
                return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                    ->with('error', 'Invalid target visit for update request.');
            }

            $existingVisit = $visitModel->find($visitId);
            if (! $existingVisit) {
                $db->transRollback();
                return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                    ->with('error', 'Target visit record not found.');
            }

            $visitData['approval_status'] = 'approved';
            $visitData['approval_action'] = null;
            $visitData['approved_by_user_id'] = $actor['id'] ?? null;
            $visitData['approved_at'] = date('Y-m-d H:i:s');
            $visitData['rejected_by_user_id'] = null;
            $visitData['rejected_at'] = null;
            $visitData['approval_remarks'] = null;
            $visitData['pending_delete_requested_by'] = null;
            $visitData['pending_delete_requested_at'] = null;

            $visitModel->update($visitId, $visitData);

            $oldGroups = $groupModel->where('visit_id', $visitId)->findAll();
            foreach ($oldGroups as $g) {
                $members = $memberModel->where('family_group_id', $g['id'])->findAll();

                foreach ($members as $m) {
                    $quarterModel->where('group_member_id', $m['id'])->delete();
                    $medicalHistoryModel->where('group_member_id', $m['id'])->delete();
                }

                $memberModel->where('family_group_id', $g['id'])->delete();
            }

            $groupModel->where('visit_id', $visitId)->delete();

            $effectiveVisitDate = ! empty($visitData['last_visit_date'])
                ? (string) $visitData['last_visit_date']
                : (! empty($visitData['visit_date'])
                    ? (string) $visitData['visit_date']
                    : (! empty($existingVisit['last_visit_date'])
                        ? (string) $existingVisit['last_visit_date']
                        : (string) ($existingVisit['visit_date'] ?? date('Y-m-d'))));

            $this->insertApprovedGroupsTree(
                $visitId,
                $effectiveVisitDate,
                is_array($groups) ? $groups : [],
                $groupModel,
                $memberModel,
                $quarterModel,
                $medicalHistoryModel
            );
        } elseif ($requestType === 'delete') {
            $visitId = (int) ($row['target_visit_id'] ?? 0);

            if ($visitId <= 0) {
                $db->transRollback();
                return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                    ->with('error', 'Invalid target visit for delete request.');
            }

            $oldGroups = $groupModel->where('visit_id', $visitId)->findAll();
            foreach ($oldGroups as $g) {
                $members = $memberModel->where('family_group_id', $g['id'])->findAll();

                foreach ($members as $m) {
                    $quarterModel->where('group_member_id', $m['id'])->delete();
                    $medicalHistoryModel->where('group_member_id', $m['id'])->delete();
                }

                $memberModel->where('family_group_id', $g['id'])->delete();
            }

            $groupModel->where('visit_id', $visitId)->delete();
            $visitModel->delete($visitId);
        } else {
            $db->transRollback();
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Unsupported request type.');
        }

        $this->markRequestApproved($id, $actor, trim((string) $this->request->getPost('reviewer_notes')));

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Failed to approve request.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling-requests'))
            ->with('success', 'Request approved successfully.');
    }

    public function reject(int $id)
    {
        $actor = $this->actor();
        $row = $this->findAccessiblePendingRequest($id, $actor);

        if (! $row) {
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Request not found or not allowed.');
        }

        $remarks = trim((string) $this->request->getPost('reviewer_notes'));

        $model = new HhVisitChangeRequestModel();
        $model->update($id, [
            'status' => 'rejected',
            'reviewed_by_user_id' => $actor['id'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_remarks' => $remarks !== '' ? $remarks : null,
        ]);

        return redirect()->to(base_url('admin/registry/household-profiling-requests'))
            ->with('success', 'Request rejected.');
    }

    private function insertApprovedGroupsTree(
        int $visitId,
        string $visitDate,
        array $groups,
        HhFamilyGroupModel $groupModel,
        HhGroupMemberModel $memberModel,
        HhGroupMemberQuarterModel $quarterModel,
        HhGroupMemberMedicalHistoryModel $medicalHistoryModel
    ): void {
        $year = (int) substr($visitDate, 0, 4);

        foreach ($groups as $g) {
            $groupId = $groupModel->insert([
                'visit_id' => $visitId,
                'group_name' => trim((string) ($g['group_name'] ?? '')) ?: null,
                'living_status' => $g['living_status'] ?? null,
                'notes' => trim((string) ($g['notes'] ?? '')) ?: null,
            ], true);

            $members = $g['members'] ?? [];
            if (! is_array($members)) {
                $members = [];
            }

            foreach ($members as $m) {
                $memberId = $memberModel->insert([
                    'family_group_id' => $groupId,
                    'linked_member_id' => ! empty($m['linked_member_id']) ? (int) $m['linked_member_id'] : null,

                    'local_last_name' => trim((string) ($m['local_last_name'] ?? '')) ?: null,
                    'local_first_name' => trim((string) ($m['local_first_name'] ?? '')) ?: null,
                    'local_middle_name' => trim((string) ($m['local_middle_name'] ?? '')) ?: null,

                    'relationship_code' => ! empty($m['relationship_code']) ? (int) $m['relationship_code'] : null,
                    'relationship_other' => trim((string) ($m['relationship_other'] ?? '')) ?: null,

                    'sex' => trim((string) ($m['sex'] ?? '')) ?: null,
                    'dob' => ! empty($m['dob']) ? $m['dob'] : null,
                    'civil_status' => trim((string) ($m['civil_status'] ?? '')) ?: null,

                    'philhealth_id' => trim((string) ($m['philhealth_id'] ?? '')) ?: null,
                    'membership_type' => trim((string) ($m['membership_type'] ?? '')) ?: null,
                    'philhealth_category' => trim((string) ($m['philhealth_category'] ?? '')) ?: null,

                    'medical_history' => null,

                    'lmp_date' => ! empty($m['lmp_date']) ? $m['lmp_date'] : null,
                    'educ_attainment' => trim((string) ($m['educ_attainment'] ?? '')) ?: null,
                    'religion' => trim((string) ($m['religion'] ?? '')) ?: null,

                    'status_in_household' => trim((string) ($m['status_in_household'] ?? '')) ?: null,
                    'stay_from' => ! empty($m['stay_from']) ? $m['stay_from'] : null,
                    'stay_to' => ! empty($m['stay_to']) ? $m['stay_to'] : null,

                    'remarks' => trim((string) ($m['remarks'] ?? '')) ?: null,
                ], true);

                for ($q = 1; $q <= 4; $q++) {
                    $age = isset($m["q{$q}_age"]) && $m["q{$q}_age"] !== '' ? (int) $m["q{$q}_age"] : null;
                    $class = trim((string) ($m["q{$q}_class"] ?? ''));

                    if ($age === null && $class === '') {
                        continue;
                    }

                    $quarterModel->insert([
                        'group_member_id' => $memberId,
                        'year' => $year,
                        'quarter' => $q,
                        'age' => $age,
                        'class_code' => $class !== '' ? $class : null,
                    ]);
                }

                $medicalHistories = $m['medical_histories'] ?? [];
                if (! is_array($medicalHistories)) {
                    $medicalHistories = [];
                }

                foreach ($medicalHistories as $mh) {
                    $conditionName = trim((string) ($mh['condition_name'] ?? ''));
                    if ($conditionName === '') {
                        continue;
                    }

                    $medicalHistoryModel->insert([
                        'group_member_id' => $memberId,
                        'condition_name' => $conditionName,
                        'date_diagnosed' => ! empty($mh['date_diagnosed']) ? $mh['date_diagnosed'] : null,
                        'status' => trim((string) ($mh['status'] ?? '')) ?: null,
                        'remarks' => trim((string) ($mh['remarks'] ?? '')) ?: null,
                    ]);
                }
            }
        }
    }

    private function markRequestApproved(int $id, array $actor, ?string $remarks = null): void
    {
        $model = new HhVisitChangeRequestModel();

        $model->update($id, [
            'status' => 'approved',
            'reviewed_by_user_id' => $actor['id'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'review_remarks' => $remarks !== '' ? $remarks : null,
        ]);
    }

    private function findAccessiblePendingRequest(int $id, array $actor): ?array
    {
        $model = new HhVisitChangeRequestModel();
        $row = $model->find($id);

        if (! $row) {
            return null;
        }

        $type = (string) ($actor['user_type'] ?? '');

        if ($type === 'super_admin') {
            return $row;
        }

        if ($type === 'admin') {
            if (($row['review_level'] ?? '') !== 'admin') {
                return null;
            }

            if (($row['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) {
                return null;
            }

            return $row;
        }

        if ($type === 'staff') {
            if (($row['review_level'] ?? '') !== 'staff') {
                return null;
            }

            if (($row['barangay_pcode'] ?? null) !== ($actor['barangay_pcode'] ?? null)) {
                return null;
            }

            return $row;
        }

        return null;
    }

    private function safeJsonDecode($json): array
    {
        if (! is_string($json) || trim($json) === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
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
}