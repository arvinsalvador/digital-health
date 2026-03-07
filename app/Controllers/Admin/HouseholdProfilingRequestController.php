<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\HhVisitChangeRequestModel;

class HouseholdProfilingRequestController extends BaseController
{
    public function index()
    {
        $actor = $this->actor();
        $actorType = (string) ($actor['user_type'] ?? '');

        if (! in_array($actorType, ['super_admin', 'admin', 'staff'], true)) {
            return redirect()->to(base_url('admin/dashboard'))->with('error', 'Not allowed.');
        }

        $model = new HhVisitChangeRequestModel();
        $builder = $model->orderBy('created_at', 'DESC');

        if ($actorType === 'super_admin') {
            // all pending
        } elseif ($actorType === 'admin') {
            $builder->where('review_level', 'admin')
                ->where('municipality_pcode', $actor['municipality_pcode'] ?? null);
        } elseif ($actorType === 'staff') {
            $builder->where('review_level', 'staff')
                ->where('barangay_pcode', $actor['barangay_pcode'] ?? null);
        }

        $rows = $builder->where('status', 'pending')->findAll();

        foreach ($rows as &$row) {
            $row['change_payload'] = $this->safeJsonDecode($row['change_payload_json'] ?? null);
            $row['diff_payload'] = $this->safeJsonDecode($row['diff_payload_json'] ?? null);
        }
        unset($row);

        return view('admin/registry/household_profiling_requests/index', [
            'pageTitle' => 'Profiling Requests',
            'rows' => $rows,
            'actor' => $actor,
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

        return view('admin/registry/household_profiling_requests/show', [
            'pageTitle' => 'Review Profiling Request',
            'row' => $row,
            'actor' => $actor,
        ]);
    }

    public function reject(int $id)
    {
        $actor = $this->actor();
        $row = $this->findAccessiblePendingRequest($id, $actor);

        if (! $row) {
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Request not found or not allowed.');
        }

        $notes = trim((string) ($this->request->getPost('reviewer_notes') ?? ''));

        $model = new HhVisitChangeRequestModel();
        $model->update($id, [
            'status' => 'rejected',
            'reviewed_by_user_id' => $actor['id'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'reviewer_notes' => $notes !== '' ? $notes : null,
        ]);

        return redirect()->to(base_url('admin/registry/household-profiling-requests'))
            ->with('success', 'Request rejected.');
    }

    public function approve(int $id)
    {
        $actor = $this->actor();
        $row = $this->findAccessiblePendingRequest($id, $actor);

        if (! $row) {
            return redirect()->to(base_url('admin/registry/household-profiling-requests'))
                ->with('error', 'Request not found or not allowed.');
        }

        $notes = trim((string) ($this->request->getPost('reviewer_notes') ?? ''));
        $changePayload = $this->safeJsonDecode($row['change_payload_json'] ?? null);

        if (! is_array($changePayload)) {
            return redirect()->back()->with('error', 'Invalid request payload.');
        }

        $applyService = service('hhVisitLiveApplyService');

        $db = \Config\Database::connect();
        $db->transStart();

        if (($row['request_type'] ?? '') === 'create') {
            $newVisitId = $applyService->applyCreate($changePayload, $actor);
            if (! $newVisitId) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Failed to apply create request.');
            }
        } elseif (($row['request_type'] ?? '') === 'update') {
            $ok = $applyService->applyUpdate((int) ($row['target_visit_id'] ?? 0), $changePayload, $actor);
            if (! $ok) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Failed to apply update request.');
            }
        } elseif (($row['request_type'] ?? '') === 'delete') {
            $ok = $applyService->applyDelete((int) ($row['target_visit_id'] ?? 0));
            if (! $ok) {
                $db->transRollback();
                return redirect()->back()->with('error', 'Failed to apply delete request.');
            }
        } else {
            $db->transRollback();
            return redirect()->back()->with('error', 'Unknown request type.');
        }

        $model = new HhVisitChangeRequestModel();
        $model->update($id, [
            'status' => 'approved',
            'reviewed_by_user_id' => $actor['id'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
            'applied_at' => date('Y-m-d H:i:s'),
            'reviewer_notes' => $notes !== '' ? $notes : null,
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return redirect()->back()->with('error', 'Failed to approve request.');
        }

        return redirect()->to(base_url('admin/registry/household-profiling-requests'))
            ->with('success', 'Request approved successfully.');
    }

    private function findAccessiblePendingRequest(int $id, array $actor): ?array
    {
        $actorType = (string) ($actor['user_type'] ?? '');

        if (! in_array($actorType, ['super_admin', 'admin', 'staff'], true)) {
            return null;
        }

        $model = new HhVisitChangeRequestModel();
        $row = $model->find($id);

        if (! $row || ($row['status'] ?? '') !== 'pending') {
            return null;
        }

        if ($actorType === 'super_admin') {
            return $row;
        }

        if ($actorType === 'admin') {
            if (($row['review_level'] ?? '') !== 'admin') return null;
            if (($row['municipality_pcode'] ?? null) !== ($actor['municipality_pcode'] ?? null)) return null;
            return $row;
        }

        if ($actorType === 'staff') {
            if (($row['review_level'] ?? '') !== 'staff') return null;
            if (($row['barangay_pcode'] ?? null) !== ($actor['barangay_pcode'] ?? null)) return null;
            return $row;
        }

        return null;
    }

    private function safeJsonDecode(?string $json): ?array
    {
        if (! $json) return null;
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : null;
    }
}