<?php

namespace App\Libraries;

use App\Models\HhVisitModel;
use App\Models\HhMemberModel;
use App\Models\HhFamilyGroupModel;
use App\Models\HhGroupMemberModel;
use App\Models\HhGroupMemberQuarterModel;
use App\Models\HhGroupMemberMedicalHistoryModel;

class HhVisitLiveApplyService
{
    public function applyCreate(array $changePayload, array $reviewActor): ?int
    {
        $visitPayload = $changePayload['visit'] ?? null;
        $groups = $changePayload['groups'] ?? [];

        if (! is_array($visitPayload)) {
            return null;
        }

        $visitModel = new HhVisitModel();

        $visitPayload['approval_status'] = 'approved';
        $visitPayload['approval_action'] = null;
        $visitPayload['approved_by_user_id'] = $reviewActor['id'] ?? null;
        $visitPayload['approved_at'] = date('Y-m-d H:i:s');
        $visitPayload['rejected_by_user_id'] = null;
        $visitPayload['rejected_at'] = null;
        $visitPayload['approval_remarks'] = null;
        $visitPayload['pending_delete_requested_by'] = null;
        $visitPayload['pending_delete_requested_at'] = null;

        $visitId = $visitModel->insert($visitPayload, true);
        if (! $visitId) {
            return null;
        }

        $visitDate = (string) ($visitPayload['visit_date'] ?? date('Y-m-d'));
        $this->insertGroupsForVisit((int) $visitId, $visitDate, is_array($groups) ? $groups : [], []);

        return (int) $visitId;
    }

    public function applyUpdate(int $targetVisitId, array $changePayload, array $reviewActor): bool
    {
        if ($targetVisitId <= 0) {
            return false;
        }

        $visitPayload = $changePayload['visit'] ?? null;
        $groups = $changePayload['groups'] ?? [];

        if (! is_array($visitPayload)) {
            return false;
        }

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $visit = $visitModel->find($targetVisitId);
        if (! $visit) {
            return false;
        }

        $visitPayload['approval_status'] = 'approved';
        $visitPayload['approval_action'] = null;
        $visitPayload['approved_by_user_id'] = $reviewActor['id'] ?? null;
        $visitPayload['approved_at'] = date('Y-m-d H:i:s');
        $visitPayload['rejected_by_user_id'] = null;
        $visitPayload['rejected_at'] = null;
        $visitPayload['approval_remarks'] = null;
        $visitPayload['pending_delete_requested_by'] = null;
        $visitPayload['pending_delete_requested_at'] = null;

        $historyMap = [];

        $oldGroups = $familyModel->where('visit_id', $targetVisitId)->findAll();
        foreach ($oldGroups as $og) {
            $oldMembers = $groupMemberModel->where('family_group_id', $og['id'])->findAll();
            foreach ($oldMembers as $om) {
                $historyMap[(int) $om['id']] = $mhModel
                    ->where('group_member_id', $om['id'])
                    ->orderBy('id', 'ASC')
                    ->findAll();
            }
        }

        $visitModel->update($targetVisitId, $visitPayload);

        foreach ($oldGroups as $og) {
            $oldMembers = $groupMemberModel->where('family_group_id', $og['id'])->findAll();
            foreach ($oldMembers as $om) {
                $qModel->where('group_member_id', $om['id'])->delete();
                $mhModel->where('group_member_id', $om['id'])->delete();
            }
            $groupMemberModel->where('family_group_id', $og['id'])->delete();
        }

        $familyModel->where('visit_id', $targetVisitId)->delete();

        $visitDate = (string) ($visitPayload['visit_date'] ?? $visit['visit_date']);
        $this->insertGroupsForVisit($targetVisitId, $visitDate, is_array($groups) ? $groups : [], $historyMap);

        return true;
    }

    public function applyDelete(int $targetVisitId): bool
    {
        if ($targetVisitId <= 0) {
            return false;
        }

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $visit = $visitModel->find($targetVisitId);
        if (! $visit) {
            return false;
        }

        $groups = $familyModel->where('visit_id', $targetVisitId)->findAll();
        foreach ($groups as $group) {
            $members = $groupMemberModel->where('family_group_id', $group['id'])->findAll();
            foreach ($members as $member) {
                $qModel->where('group_member_id', $member['id'])->delete();
                $mhModel->where('group_member_id', $member['id'])->delete();
            }
            $groupMemberModel->where('family_group_id', $group['id'])->delete();
        }

        $familyModel->where('visit_id', $targetVisitId)->delete();
        $visitModel->delete($targetVisitId);

        return true;
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
                'living_status' => $g['living_status'] ?? null,
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
                            'condition_name' => $conditionName,
                            'date_diagnosed' => ! empty($hist['date_diagnosed']) ? $hist['date_diagnosed'] : null,
                            'status' => trim((string) ($hist['status'] ?? '')) ?: null,
                            'remarks' => trim((string) ($hist['remarks'] ?? '')) ?: null,
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

        if ($age <= 5) return 'INFANT';
        if ($age <= 12) return 'CHILD';
        if ($age <= 19) return 'TEEN';
        if ($age <= 59) return 'ADULT';

        return 'SENIOR';
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
}