<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\ProjectUpdate;
use App\Models\ProgressRequest;
use App\Models\ProjectUpdateAttachment;
use App\Services\SupabaseStorageService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectUpdateController extends Controller
{
    protected $storage;

    private $phases = [
        'planning','procurement','matl_prep',
        'fabrication','inspection','painting',
        'completion','delivery',
    ];

    private $progressMap = [
        'planning'    => 5,
        'procurement' => 15,
        'matl_prep'   => 25,
        'fabrication' => 60,
        'inspection'  => 75,
        'painting'    => 85,
        'completion'  => 95,
        'delivery'    => 100,
    ];

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Single Update Details (For Modal)
    |--------------------------------------------------------------------------
    */
    public function show($id)
    {
        $update = ProjectUpdate::with(['submittedBy'])->findOrFail($id);

        $canApprove          = $update->status === 'pending_review';
        $canRequestRevision  = $update->status === 'pending_review';

        $photos = [];
        if ($update->photos) {
            foreach ($update->photos as $photo) {
                $photos[] = [
                    'url'      => $photo,
                    'path'     => $photo,
                    'is_image' => true,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'update'  => [
                'id'                    => $update->id,
                'project_id'            => $update->project_id,
                'submitted_by_name'     => $update->submittedBy ? $update->submittedBy->full_name : 'Admin',
                'created_at_formatted'  => $update->created_at->format('F d, Y g:i A'),
                'phase'                 => ucfirst(str_replace('_', ' ', $update->phase)),
                'phase_key'             => $update->phase,
                'status'                => $update->status,
                'is_pending'            => $canApprove,
                'work_done'             => $update->work_done,
                'issues'                => $update->issues,
                'percentage'            => $update->percentage,
                'date_of_work'          => $update->date_of_work?->format('Y-m-d'),
                'revision_feedback'     => $update->revision_feedback,
            ],
            'photos'               => $photos,
            'can_approve'          => $canApprove,
            'can_request_revision' => $canRequestRevision,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Approve Update
    | Marks an employee submission as approved and visible in the project's
    | history. Phase advancement is handled separately by the admin's
    | "Save Progress Update" checklist flow — approving a submission here
    | does NOT change the project's phase, progress, or status.
    | Uses status = 'pending_review' as the check (not approval_status)
    |--------------------------------------------------------------------------
    */
    public function approve($updateId)
    {
        $update  = ProjectUpdate::findOrFail($updateId);
        $project = Project::findOrFail($update->project_id);

        // ✅ Check status column, not approval_status
        if ($update->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'This update cannot be approved because it is not pending review.',
            ], 422);
        }

        $update->update(['status' => 'approved']);

        NotificationService::notifyEmployee(
            $update->submitted_by,
            'Progress Update Approved',
            "Your progress update has been approved.\nProject: {$project->name}",
            NotificationService::TYPE_PROGRESS_APPROVED,
            'success',
            $project->id,
            $update->id,
            "/employee/project-view/{$project->id}"
        );

        return redirect()->route('admin.project_view', $project->id)
            ->with('success', 'Update approved!');
    }

    /*
    |--------------------------------------------------------------------------
    | Request Revision
    | Uses status = 'pending_review' as the check (not approval_status)
    |--------------------------------------------------------------------------
    */
    public function requestRevision(Request $request, $updateId)
    {
        // Support both 'revision_comment' (from blade form) and 'feedback' (legacy)
        $request->validate([
            'revision_comment' => 'required_without:feedback|nullable|string',
            'feedback'         => 'required_without:revision_comment|nullable|string',
        ]);

        $update  = ProjectUpdate::findOrFail($updateId);
        $comment = $request->revision_comment ?? $request->feedback;

        if ($update->status !== 'pending_review') {
            return response()->json([
                'success' => false,
                'message' => 'Revision cannot be requested because this update is not pending review.',
            ], 422);
        }

        // Store clean feedback on the update
        $update->update([
            'status'            => 'needs_revision',
            'revision_feedback' => $comment,
        ]);

        // Set ProgressRequest to 'revision_requested' so employee sees the form
        $latestRequest = ProgressRequest::where('project_id', $update->project_id)
                            ->latest()
                            ->first();

        $project = Project::findOrFail($update->project_id);

        if ($latestRequest) {
            $latestRequest->update([
                'status'       => 'revision_requested',
                'fulfilled_by' => null,
                'fulfilled_at' => null,
            ]);
        } else {
            ProgressRequest::create([
                'project_id'   => $update->project_id,
                'requested_by' => session('user_id') ?? 1,
                'message'      => $comment,
                'phase'        => $project->current_phase,
                'status'       => 'revision_requested',
            ]);
        }

        NotificationService::revisionRequested($project, $update->submitted_by, $comment);

        return redirect()->route('admin.project_view', $update->project_id)
            ->with('success', 'Revision requested. Employee can now resubmit.');
    }

    /*
    |--------------------------------------------------------------------------
    | Upload Attachment (AJAX)
    |--------------------------------------------------------------------------
    */
    public function uploadAttachment(Request $request, $updateId)
    {
        $request->validate(['file' => 'required|file|max:10240']);

        $update = ProjectUpdate::findOrFail($updateId);

        try {
            $file = $request->file('file');
            $path = $this->storage->upload(
                $file,
                'projects/' . $update->project_id . '/updates/' . $update->id . '/attachments'
            );

            $attachment = ProjectUpdateAttachment::create([
                'project_update_id' => $update->id,
                'file_path'         => $path,
                'file_name'         => $file->getClientOriginalName(),
                'file_type'         => $file->getMimeType(),
                'file_size'         => $file->getSize(),
            ]);

            return response()->json([
                'success'    => true,
                'attachment' => [
                    'id'        => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_type' => $attachment->file_type,
                    'file_size' => $attachment->file_size,
                    'url'       => $path,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Attachment
    |--------------------------------------------------------------------------
    */
    public function deleteAttachment($attachmentId)
    {
        $attachment = ProjectUpdateAttachment::findOrFail($attachmentId);

        try {
            $this->storage->delete($attachment->file_path);
            $attachment->delete();

            return response()->json(['success' => true, 'message' => 'Attachment deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Pending Updates Count (For Dashboard)
    |--------------------------------------------------------------------------
    */
    public function getPendingCount()
    {
        $count = ProjectUpdate::where('status', 'pending_review')->count();

        return response()->json(['success' => true, 'pending_count' => $count]);
    }

    /*
    |--------------------------------------------------------------------------
    | Get All Pending Updates (For Admin Dashboard)
    |--------------------------------------------------------------------------
    */
    public function getPendingUpdates()
    {
        $updates = ProjectUpdate::where('status', 'pending_review')
                    ->with(['project', 'submittedBy'])
                    ->orderBy('created_at', 'desc')
                    ->get()
                    ->map(function ($update) {
                        return [
                            'id'                => $update->id,
                            'project_name'      => $update->project->name,
                            'submitted_by'      => $update->submittedBy?->full_name ?? 'Unknown',
                            'phase'             => $update->phase,
                            'created_at'        => $update->created_at->format('Y-m-d H:i:s'),
                            'work_done_preview' => substr($update->work_done, 0, 100)
                                                    . (strlen($update->work_done) > 100 ? '...' : ''),
                        ];
                    });

        return response()->json(['success' => true, 'updates' => $updates]);
    }
}