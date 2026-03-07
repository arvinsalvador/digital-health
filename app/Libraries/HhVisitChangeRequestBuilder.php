<?php

namespace App\Libraries;

use App\Models\HhFamilyGroupModel;
use App\Models\HhGroupMemberModel;
use App\Models\HhGroupMemberMedicalHistoryModel;

class HhVisitChangeRequestBuilder
{
    public function buildCreateRequest(array $actor, array $visitPayload, array $groups): array
    {
        $fullPayload = [
            'visit' => $visitPayload,
            'groups' => $groups,
        ];

        $groupChanges = [];
        $memberChanges = [];
        $medicalHistoryChanges = [];
        $summaryItems = ['New record submitted'];

        foreach ($groups as $group) {
            $groupChanges[] = [
                'type' => 'added_group',
                'group_name' => $group['group_name'] ?? null,
                'living_status' => $group['living_status'] ?? null,
            ];
            $summaryItems[] = 'Added family group';

            foreach (($group['members'] ?? []) as $member) {
                $memberChanges[] = [
                    'type' => 'added_member',
                    'group_id' => $group['id'] ?? null,
                    'member_name' => $this->memberDisplayName($member),
                ];
                $summaryItems[] = 'Added family member';

                foreach (($member['medical_histories'] ?? []) as $mh) {
                    $medicalHistoryChanges[] = [
                        'type' => 'added_medical_history',
                        'group_id' => $group['id'] ?? null,
                        'member_id' => $member['id'] ?? null,
                        'member_name' => $this->memberDisplayName($member),
                        'condition_name' => $mh['condition_name'] ?? null,
                    ];
                    $summaryItems[] = 'Added medical history';
                }
            }
        }

        $summaryItems = array_values(array_unique($summaryItems));

        return [
            'target_visit_id' => null,
            'request_type' => 'create',
            'status' => 'pending',
            'review_level' => 'staff',
            'requested_by_user_id' => $actor['id'] ?? null,
            'barangay_pcode' => $visitPayload['barangay_pcode'] ?? ($actor['barangay_pcode'] ?? null),
            'municipality_pcode' => $visitPayload['municipality_pcode'] ?? ($actor['municipality_pcode'] ?? null),
            'summary_text' => 'New record submitted.',
            'change_payload_json' => json_encode($fullPayload, JSON_UNESCAPED_UNICODE),
            'diff_payload_json' => json_encode([
                'type' => 'create',
                'summary_items' => $summaryItems,
                'visit_fields' => [],
                'groups' => $groupChanges,
                'members' => $memberChanges,
                'medical_histories' => $medicalHistoryChanges,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    public function buildUpdateRequest(array $actor, int $targetVisitId, array $originalVisit, array $visitPayload, array $groups): array
    {
        $visitDiff = $this->diffVisitFields($originalVisit, $visitPayload);

        $originalGroups = $this->loadOriginalGroupsTree($targetVisitId);
        $groupDiff = $this->diffGroupsTree($originalGroups, $groups);

        $summaryItems = [];

        foreach ($visitDiff as $field => $change) {
            $summaryItems[] = $this->labelizeField($field) . ' updated';
        }

        foreach ($groupDiff['summary_items'] as $item) {
            $summaryItems[] = $item;
        }

        if (empty($summaryItems)) {
            $summaryItems[] = 'Record details updated';
        }

        $summaryItems = array_values(array_unique($summaryItems));

        $fullPayload = [
            'visit' => $visitPayload,
            'groups' => $groups,
        ];

        $diffPayload = [
            'type' => 'update',
            'visit_fields' => $visitDiff,
            'groups' => $groupDiff['groups'],
            'members' => $groupDiff['members'],
            'medical_histories' => $groupDiff['medical_histories'],
            'summary_items' => $summaryItems,
        ];

        return [
            'target_visit_id' => $targetVisitId,
            'request_type' => 'update',
            'status' => 'pending',
            'review_level' => 'staff',
            'requested_by_user_id' => $actor['id'] ?? null,
            'barangay_pcode' => $originalVisit['barangay_pcode'] ?? ($actor['barangay_pcode'] ?? null),
            'municipality_pcode' => $originalVisit['municipality_pcode'] ?? ($actor['municipality_pcode'] ?? null),
            'summary_text' => implode('; ', $summaryItems) . '.',
            'change_payload_json' => json_encode($fullPayload, JSON_UNESCAPED_UNICODE),
            'diff_payload_json' => json_encode($diffPayload, JSON_UNESCAPED_UNICODE),
        ];
    }

    public function buildDeleteRequest(array $actor, int $targetVisitId, array $visit): array
    {
        return [
            'target_visit_id' => $targetVisitId,
            'request_type' => 'delete',
            'status' => 'pending',
            'review_level' => 'admin',
            'requested_by_user_id' => $actor['id'] ?? null,
            'barangay_pcode' => $visit['barangay_pcode'] ?? ($actor['barangay_pcode'] ?? null),
            'municipality_pcode' => $visit['municipality_pcode'] ?? ($actor['municipality_pcode'] ?? null),
            'summary_text' => 'Delete request submitted.',
            'change_payload_json' => json_encode([
                'visit_id' => $targetVisitId,
            ], JSON_UNESCAPED_UNICODE),
            'diff_payload_json' => json_encode([
                'type' => 'delete',
                'summary_items' => ['Delete request submitted'],
                'visit_fields' => [],
                'groups' => [],
                'members' => [],
                'medical_histories' => [],
            ], JSON_UNESCAPED_UNICODE),
        ];
    }

    private function diffVisitFields(array $old, array $new): array
    {
        $ignore = [
            'id', 'created_at', 'updated_at',
            'approval_status', 'approval_action',
            'submitted_by_user_id', 'approved_by_user_id',
            'approved_at', 'rejected_by_user_id', 'rejected_at',
            'approval_remarks', 'pending_delete_requested_by',
            'pending_delete_requested_at',
        ];

        $diff = [];

        foreach ($new as $key => $newValue) {
            if (in_array($key, $ignore, true)) {
                continue;
            }

            $oldValue = $old[$key] ?? null;

            if ((string) $oldValue !== (string) $newValue) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $diff;
    }

    private function loadOriginalGroupsTree(int $visitId): array
    {
        $groupModel = new HhFamilyGroupModel();
        $memberModel = new HhGroupMemberModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $groups = $groupModel->where('visit_id', $visitId)->orderBy('id', 'ASC')->findAll();

        foreach ($groups as &$group) {
            $members = $memberModel->where('family_group_id', $group['id'])->orderBy('id', 'ASC')->findAll();

            foreach ($members as &$member) {
                $member['medical_histories'] = $mhModel
                    ->where('group_member_id', $member['id'])
                    ->orderBy('id', 'ASC')
                    ->findAll();
            }
            unset($member);

            $group['members'] = $members;
        }
        unset($group);

        return $groups;
    }

    private function diffGroupsTree(array $oldGroups, array $newGroups): array
    {
        $groupChanges = [];
        $memberChanges = [];
        $medicalHistoryChanges = [];
        $summaryItems = [];

        $oldGroupsById = [];
        foreach ($oldGroups as $group) {
            if (!empty($group['id'])) {
                $oldGroupsById[(int) $group['id']] = $group;
            }
        }

        $newGroupsById = [];
        foreach ($newGroups as $group) {
            if (!empty($group['id'])) {
                $newGroupsById[(int) $group['id']] = $group;
            }
        }

        foreach ($newGroups as $group) {
            $groupId = !empty($group['id']) ? (int) $group['id'] : 0;

            if ($groupId <= 0) {
                $groupChanges[] = [
                    'type' => 'added_group',
                    'group_name' => $group['group_name'] ?? null,
                    'living_status' => $group['living_status'] ?? null,
                ];
                $summaryItems[] = 'Added family group';
                $this->collectNewGroupMemberChanges($group, $memberChanges, $medicalHistoryChanges, $summaryItems);
                continue;
            }

            if (!isset($oldGroupsById[$groupId])) {
                $groupChanges[] = [
                    'type' => 'added_group',
                    'group_id' => $groupId,
                    'group_name' => $group['group_name'] ?? null,
                    'living_status' => $group['living_status'] ?? null,
                ];
                $summaryItems[] = 'Added family group';
                $this->collectNewGroupMemberChanges($group, $memberChanges, $medicalHistoryChanges, $summaryItems);
                continue;
            }

            $oldGroup = $oldGroupsById[$groupId];
            $fieldDiff = $this->compareSimpleFields(
                $oldGroup,
                $group,
                ['group_name', 'living_status', 'notes']
            );

            if (!empty($fieldDiff)) {
                $groupChanges[] = [
                    'type' => 'updated_group',
                    'group_id' => $groupId,
                    'changes' => $fieldDiff,
                ];
                $summaryItems[] = 'Updated family group';
            }

            $memberResult = $this->diffMembersOfGroup($oldGroup, $group);
            $memberChanges = array_merge($memberChanges, $memberResult['members']);
            $medicalHistoryChanges = array_merge($medicalHistoryChanges, $memberResult['medical_histories']);
            $summaryItems = array_merge($summaryItems, $memberResult['summary_items']);
        }

        foreach ($oldGroups as $oldGroup) {
            $oldGroupId = !empty($oldGroup['id']) ? (int) $oldGroup['id'] : 0;
            if ($oldGroupId > 0 && !isset($newGroupsById[$oldGroupId])) {
                $groupChanges[] = [
                    'type' => 'removed_group',
                    'group_id' => $oldGroupId,
                    'group_name' => $oldGroup['group_name'] ?? null,
                    'living_status' => $oldGroup['living_status'] ?? null,
                ];
                $summaryItems[] = 'Removed family group';
            }
        }

        return [
            'groups' => $groupChanges,
            'members' => $memberChanges,
            'medical_histories' => $medicalHistoryChanges,
            'summary_items' => array_values(array_unique($summaryItems)),
        ];
    }

    private function diffMembersOfGroup(array $oldGroup, array $newGroup): array
    {
        $memberChanges = [];
        $medicalHistoryChanges = [];
        $summaryItems = [];

        $oldMembers = $oldGroup['members'] ?? [];
        $newMembers = $newGroup['members'] ?? [];

        $oldMembersById = [];
        foreach ($oldMembers as $member) {
            if (!empty($member['id'])) {
                $oldMembersById[(int) $member['id']] = $member;
            }
        }

        $newMembersById = [];
        foreach ($newMembers as $member) {
            if (!empty($member['id'])) {
                $newMembersById[(int) $member['id']] = $member;
            }
        }

        foreach ($newMembers as $member) {
            $memberId = !empty($member['id']) ? (int) $member['id'] : 0;

            if ($memberId <= 0) {
                $memberChanges[] = [
                    'type' => 'added_member',
                    'group_id' => $oldGroup['id'] ?? null,
                    'member_name' => $this->memberDisplayName($member),
                ];
                $summaryItems[] = 'Added family member';
                continue;
            }

            if (!isset($oldMembersById[$memberId])) {
                $memberChanges[] = [
                    'type' => 'added_member',
                    'group_id' => $oldGroup['id'] ?? null,
                    'member_id' => $memberId,
                    'member_name' => $this->memberDisplayName($member),
                ];
                $summaryItems[] = 'Added family member';
                continue;
            }

            $oldMember = $oldMembersById[$memberId];
            $fieldDiff = $this->compareSimpleFields(
                $oldMember,
                $member,
                [
                    'local_last_name',
                    'local_first_name',
                    'local_middle_name',
                    'relationship_code',
                    'relationship_other',
                    'sex',
                    'dob',
                    'civil_status',
                    'philhealth_id',
                    'membership_type',
                    'philhealth_category',
                    'lmp_date',
                    'educ_attainment',
                    'religion',
                    'status_in_household',
                    'stay_from',
                    'stay_to',
                    'remarks',
                    'q1_age', 'q1_class',
                    'q2_age', 'q2_class',
                    'q3_age', 'q3_class',
                    'q4_age', 'q4_class',
                ]
            );

            if (!empty($fieldDiff)) {
                $memberChanges[] = [
                    'type' => 'updated_member',
                    'group_id' => $oldGroup['id'] ?? null,
                    'member_id' => $memberId,
                    'member_name' => $this->memberDisplayName($member),
                    'changes' => $fieldDiff,
                ];
                $summaryItems[] = 'Updated family member';
            }

            $mhResult = $this->diffMedicalHistoriesOfMember($oldMember, $member, $oldGroup['id'] ?? null);
            $medicalHistoryChanges = array_merge($medicalHistoryChanges, $mhResult['medical_histories']);
            $summaryItems = array_merge($summaryItems, $mhResult['summary_items']);
        }

        foreach ($oldMembers as $oldMember) {
            $oldMemberId = !empty($oldMember['id']) ? (int) $oldMember['id'] : 0;
            if ($oldMemberId > 0 && !isset($newMembersById[$oldMemberId])) {
                $memberChanges[] = [
                    'type' => 'removed_member',
                    'group_id' => $oldGroup['id'] ?? null,
                    'member_id' => $oldMemberId,
                    'member_name' => $this->memberDisplayName($oldMember),
                ];
                $summaryItems[] = 'Removed family member';
            }
        }

        return [
            'members' => $memberChanges,
            'medical_histories' => $medicalHistoryChanges,
            'summary_items' => array_values(array_unique($summaryItems)),
        ];
    }

    private function diffMedicalHistoriesOfMember(array $oldMember, array $newMember, $groupId): array
    {
        $changes = [];
        $summaryItems = [];

        $oldRows = $oldMember['medical_histories'] ?? [];
        $newRows = $newMember['medical_histories'] ?? [];

        $oldById = [];
        foreach ($oldRows as $row) {
            if (!empty($row['id'])) {
                $oldById[(int) $row['id']] = $row;
            }
        }

        $newById = [];
        foreach ($newRows as $row) {
            if (!empty($row['id'])) {
                $newById[(int) $row['id']] = $row;
            }
        }

        foreach ($newRows as $row) {
            $rowId = !empty($row['id']) ? (int) $row['id'] : 0;

            if ($rowId <= 0) {
                $changes[] = [
                    'type' => 'added_medical_history',
                    'group_id' => $groupId,
                    'member_id' => $newMember['id'] ?? null,
                    'member_name' => $this->memberDisplayName($newMember),
                    'condition_name' => $row['condition_name'] ?? null,
                ];
                $summaryItems[] = 'Added medical history';
                continue;
            }

            if (!isset($oldById[$rowId])) {
                $changes[] = [
                    'type' => 'added_medical_history',
                    'group_id' => $groupId,
                    'member_id' => $newMember['id'] ?? null,
                    'member_name' => $this->memberDisplayName($newMember),
                    'condition_name' => $row['condition_name'] ?? null,
                ];
                $summaryItems[] = 'Added medical history';
                continue;
            }

            $oldRow = $oldById[$rowId];
            $fieldDiff = $this->compareSimpleFields(
                $oldRow,
                $row,
                ['condition_name', 'date_diagnosed', 'status', 'remarks']
            );

            if (!empty($fieldDiff)) {
                $changes[] = [
                    'type' => 'updated_medical_history',
                    'group_id' => $groupId,
                    'member_id' => $newMember['id'] ?? null,
                    'member_name' => $this->memberDisplayName($newMember),
                    'medical_history_id' => $rowId,
                    'condition_name' => $row['condition_name'] ?? null,
                    'changes' => $fieldDiff,
                ];
                $summaryItems[] = 'Updated medical history';
            }
        }

        foreach ($oldRows as $oldRow) {
            $oldRowId = !empty($oldRow['id']) ? (int) $oldRow['id'] : 0;
            if ($oldRowId > 0 && !isset($newById[$oldRowId])) {
                $changes[] = [
                    'type' => 'removed_medical_history',
                    'group_id' => $groupId,
                    'member_id' => $oldMember['id'] ?? null,
                    'member_name' => $this->memberDisplayName($oldMember),
                    'medical_history_id' => $oldRowId,
                    'condition_name' => $oldRow['condition_name'] ?? null,
                ];
                $summaryItems[] = 'Removed medical history';
            }
        }

        return [
            'medical_histories' => $changes,
            'summary_items' => array_values(array_unique($summaryItems)),
        ];
    }

    private function collectNewGroupMemberChanges(array $group, array &$memberChanges, array &$medicalHistoryChanges, array &$summaryItems): void
    {
        foreach (($group['members'] ?? []) as $member) {
            $memberChanges[] = [
                'type' => 'added_member',
                'group_id' => $group['id'] ?? null,
                'member_name' => $this->memberDisplayName($member),
            ];
            $summaryItems[] = 'Added family member';

            foreach (($member['medical_histories'] ?? []) as $mh) {
                $medicalHistoryChanges[] = [
                    'type' => 'added_medical_history',
                    'group_id' => $group['id'] ?? null,
                    'member_id' => $member['id'] ?? null,
                    'member_name' => $this->memberDisplayName($member),
                    'condition_name' => $mh['condition_name'] ?? null,
                ];
                $summaryItems[] = 'Added medical history';
            }
        }
    }

    private function compareSimpleFields(array $old, array $new, array $fields): array
    {
        $changes = [];

        foreach ($fields as $field) {
            $oldValue = $old[$field] ?? null;
            $newValue = $new[$field] ?? null;

            if ((string) $oldValue !== (string) $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changes;
    }

    private function memberDisplayName(array $member): string
    {
        $last = trim((string) ($member['local_last_name'] ?? ''));
        $first = trim((string) ($member['local_first_name'] ?? ''));
        $middle = trim((string) ($member['local_middle_name'] ?? ''));

        return trim($last . ', ' . $first . ($middle !== '' ? ' ' . $middle : ''));
    }

    private function labelizeField(string $field): string
    {
        return ucfirst(str_replace('_', ' ', $field));
    }
}