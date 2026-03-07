<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitModel;
use App\Models\HhFamilyGroupModel;
use App\Models\HhGroupMemberModel;
use App\Models\HhGroupMemberQuarterModel;
use App\Models\HhGroupMemberMedicalHistoryModel;

class HouseholdProfilingApprovalController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();

        if (! $this->canApproveProfilingFromBarangayUsers() && ! $this->canApproveStaffDelete()) {
            return redirect()->to(base_url('admin/dashboard'))->with('error', 'Not allowed.');
        }

        $db = \Config\Database::connect();

        $builder = $db->table('hh_visits v')
            ->select("
                v.*,
                ba.name AS barangay_name,
                mu.name AS municipality_name,
                CONCAT(COALESCE(u.first_name,''), ' ', COALESCE(u.last_name,'')) AS submitted_by_name
            ")
            ->join('admin_areas ba', "ba.pcode = v.barangay_pcode AND ba.level = 4", 'left')
            ->join('admin_areas mu', "mu.pcode = v.municipality_pcode AND mu.level = 3", 'left')
            ->join('users u', 'u.id = v.submitted_by_user_id', 'left')
            ->groupStart()
                ->whereIn('v.approval_status', ['pending_staff_approval', 'pending_admin_delete_approval'])
            ->groupEnd()
            ->orderBy('v.updated_at', 'DESC')
            ->orderBy('v.id', 'DESC');

        if ($this->isAdmin()) {
            $builder->where('v.municipality_pcode', $actor['municipality_pcode'] ?? null);
        } elseif ($this->isStaff()) {
            $builder->where('v.barangay_pcode', $actor['barangay_pcode'] ?? null)
                    ->where('v.approval_status', 'pending_staff_approval');
        }

        $rows = $builder->get()->getResultArray();

        return view('admin/registry/household_profiling/approvals', [
            'pageTitle' => 'Profiling Approvals',
            'rows' => $rows,
            'actor' => $actor,
        ]);
    }

    public function approve(int $id)
    {
        $actor = $this->actor();
        if (! $this->canApproveProfilingFromBarangayUsers()) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $model = new HhVisitModel();
        $row = $model->find($id);

        if (! $row) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        if (($row['approval_status'] ?? '') !== 'pending_staff_approval') {
            return redirect()->back()->with('error', 'Record is not pending staff approval.');
        }

        if ($this->isStaff() && ($row['barangay_pcode'] ?? null) !== ($actor['barangay_pcode'] ?? null)) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        if ($this->isAdmin() && ($row['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $model->update($id, [
            'approval_status' => 'approved',
            'approval_action' => null,
            'approved_by_user_id' => $actor['id'] ?? null,
            'approved_at' => date('Y-m-d H:i:s'),
            'rejected_by_user_id' => null,
            'rejected_at' => null,
            'approval_remarks' => null,
        ]);

        return redirect()->back()->with('success', 'Record approved.');
    }

    public function reject(int $id)
    {
        $actor = $this->actor();
        if (! $this->canApproveProfilingFromBarangayUsers()) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $model = new HhVisitModel();
        $row = $model->find($id);

        if (! $row) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        if (($row['approval_status'] ?? '') !== 'pending_staff_approval') {
            return redirect()->back()->with('error', 'Record is not pending staff approval.');
        }

        if ($this->isStaff() && ($row['barangay_pcode'] ?? null) !== ($actor['barangay_pcode'] ?? null)) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        if ($this->isAdmin() && ($row['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $model->update($id, [
            'approval_status' => 'rejected',
            'rejected_by_user_id' => $actor['id'] ?? null,
            'rejected_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->back()->with('success', 'Record rejected.');
    }

    public function approveDelete(int $id)
    {
        $actor = $this->actor();
        if (! $this->canApproveStaffDelete()) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $visitModel = new HhVisitModel();
        $familyModel = new HhFamilyGroupModel();
        $groupMemberModel = new HhGroupMemberModel();
        $qModel = new HhGroupMemberQuarterModel();
        $mhModel = new HhGroupMemberMedicalHistoryModel();

        $visit = $visitModel->find($id);
        if (! $visit) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        if (($visit['approval_status'] ?? '') !== 'pending_admin_delete_approval') {
            return redirect()->back()->with('error', 'Record is not pending delete approval.');
        }

        if ($this->isAdmin() && ($visit['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

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
            return redirect()->back()->with('error', 'Failed to delete record.');
        }

        return redirect()->back()->with('success', 'Delete request approved and record deleted.');
    }

    public function rejectDelete(int $id)
    {
        $actor = $this->actor();
        if (! $this->canApproveStaffDelete()) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $model = new HhVisitModel();
        $row = $model->find($id);

        if (! $row) {
            return redirect()->back()->with('error', 'Record not found.');
        }

        if (($row['approval_status'] ?? '') !== 'pending_admin_delete_approval') {
            return redirect()->back()->with('error', 'Record is not pending delete approval.');
        }

        if ($this->isAdmin() && ($row['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) {
            return redirect()->back()->with('error', 'Not allowed.');
        }

        $model->update($id, [
            'approval_status' => 'approved',
            'approval_action' => null,
            'pending_delete_requested_by' => null,
            'pending_delete_requested_at' => null,
        ]);

        return redirect()->back()->with('success', 'Delete request rejected.');
    }
}