<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectUpdate extends Model
{
    protected $table = 'project_updates';

    protected $fillable = [
        'project_id',
        'submitted_by',
        'submitted_by_type',
        'type',
        'update_label',
        'parent_update_id',
        'revision_feedback',
        'phase',
        'percentage',
        'date_of_work',
        'work_done',
        'issues',
        'photos',
        'status',
    ];

    protected $casts = [
        'photos'       => 'array',
        'date_of_work' => 'date',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function submittedBy()
    {
        if ($this->submitted_by_type === 'employee') {
            return $this->belongsTo(Employee::class, 'submitted_by');
        }
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function getSubmittedByNameAttribute(): string
    {
        if (!$this->submitted_by) return 'System';
        $person = $this->submittedBy()->first();
        if (!$person) return 'Unknown';
        return $this->submitted_by_type === 'employee'
            ? trim($person->first_name . ' ' . $person->last_name)
            : ($person->full_name ?? $person->name ?? 'Unknown');
    }

    public function parentUpdate()
    {
        return $this->belongsTo(ProjectUpdate::class, 'parent_update_id');
    }


    public function requestedByAdmin()
    {
        return $this->belongsTo(User::class, 'requested_by_admin_id');
    }

    public function approvedByAdmin()
    {
        return $this->belongsTo(User::class, 'approved_by_admin_id');
    }

    public function attachments()
    {
        return $this->hasMany(ProjectUpdateAttachment::class);
    }

    public function progressRequest()
    {
        return $this->belongsTo(ProgressRequest::class, 'id', 'project_update_id');
    }

    // Helper Methods
    public function isPendingReview()
    {
        return $this->update_type === 'requested' && $this->approval_status === 'pending';
    }

    public function isApproved()
    {
        return $this->approval_status === 'approved';
    }

    public function isRevisionRequired()
    {
        return $this->approval_status === 'revision_required';
    }

    public function isRegularUpdate()
    {
        return $this->update_type === 'regular' || $this->update_type === null;
    }

    public function isRequestedUpdate()
    {
        return $this->update_type === 'requested';
    }

    // Approve the update
    public function approve()
    {
        $this->approval_status = 'approved';
        $this->approved_at = now();
        $this->approved_by_admin_id = auth()->id();
        $this->save();

        // Update the associated progress request if exists
        if ($this->progressRequest) {
            $this->progressRequest->update([
                'status' => 'completed',
                'fulfilled_by' => $this->submitted_by,
                'fulfilled_at' => now(),
                'project_update_id' => $this->id
            ]);
        }

        return true;
    }

    // Request revision for the update
    public function requestRevision($feedback)
    {
        $this->approval_status = 'revision_required';
        $this->revision_feedback = $feedback;
        $this->save();

        return true;
    }

    // Submit a new version (for revision)
    public function submitRevision($workDone, $issues = null, $photos = null)
    {
        $this->work_done = $workDone;
        $this->issues = $issues;
        if ($photos) {
            $existingPhotos = $this->photos ?? [];
            $this->photos = array_merge($existingPhotos, $photos);
        }
        $this->approval_status = 'pending';
        $this->revision_feedback = null;
        $this->date_of_work = now();
        $this->save();

        return true;
    }

    // Get status badge class for UI
    public function getStatusBadgeClass()
    {
        if ($this->isPendingReview()) {
            return 'badge-warning';
        } elseif ($this->isApproved()) {
            return 'badge-success';
        } elseif ($this->isRevisionRequired()) {
            return 'badge-danger';
        }
        
        return 'badge-secondary';
    }

    // Get status label for UI
    public function getStatusLabel()
    {
        if ($this->isPendingReview()) {
            return 'Pending Review';
        } elseif ($this->isApproved()) {
            return 'Approved';
        } elseif ($this->isRevisionRequired()) {
            return 'Revision Required';
        }
        
        return ucfirst($this->approval_status);
    }

    // Get update type label
    public function getUpdateTypeLabel()
    {
        if ($this->update_type === 'requested') {
            return 'Requested Update';
        } elseif ($this->update_type === 'system') {
            return 'System Update';
        }
        
        return 'Regular Update';
    }

    // Scopes
    public function scopePendingReview($query)
    {
        return $query->where('update_type', 'requested')
                     ->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeRequestedUpdates($query)
    {
        return $query->where('update_type', 'requested');
    }

    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }
}