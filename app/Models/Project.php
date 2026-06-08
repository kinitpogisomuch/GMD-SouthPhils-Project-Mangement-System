<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Project extends Model
{
    protected $fillable = [
        'name',
        'client',
        'contact_number',
        'email',
        'address',
        'client_type',
        'tank_type',
        'capacity',
        'dimensions',
        'start_date',
        'end_date',
        'payment_status',
        'status',
        'progress',
        'current_phase',
        'duration',
        'notes',
        'total_phases',
        'completion_percentage',
        'phase_completion_status',
        'last_phase_update_at',
        'last_phase_updated_by'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'progress' => 'integer',
        'completion_percentage' => 'integer',
        'total_phases' => 'integer',
        'phase_completion_status' => 'array',
        'last_phase_update_at' => 'datetime'
    ];

    // Define the phase order
    const PHASES = [
        'planning',
        'procurement',
        'matl_prep',
        'fabrication',
        'inspection',
        'painting',
        'completion',
        'delivery'
    ];

    // Relationship: Project has many updates
    public function updates()
    {
        return $this->hasMany(ProjectUpdate::class);
    }

    // Relationship: Project has many progress requests
    public function progressRequests()
    {
        return $this->hasMany(ProgressRequest::class);
    }

    // Relationship: Project has many payments
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Relationship: Get the user who last updated the phase
    public function lastPhaseUpdatedBy()
    {
        return $this->belongsTo(User::class, 'last_phase_updated_by');
    }

    // Get the current phase index (0-based)
    public function getCurrentPhaseIndex()
    {
        return array_search($this->current_phase, self::PHASES);
    }

    // Get the next phase
    public function getNextPhase()
    {
        $currentIndex = $this->getCurrentPhaseIndex();
        
        if ($currentIndex !== false && $currentIndex < count(self::PHASES) - 1) {
            return self::PHASES[$currentIndex + 1];
        }
        
        return null;
    }

    // Advance to the next phase
    public function advanceToNextPhase()
    {
        $nextPhase = $this->getNextPhase();
        
        if ($nextPhase) {
            $oldPhase = $this->current_phase;
            $this->current_phase = $nextPhase;
            $this->last_phase_update_at = now();
            $this->last_phase_updated_by = Auth::id();
            $this->save();
            
            // Mark the previous phase as completed in phase_completion_status
            $status = $this->phase_completion_status ?? [];
            $phaseIndex = array_search($oldPhase, self::PHASES);
            
            if ($phaseIndex !== false) {
                $status[$phaseIndex] = [
                    'phase' => $oldPhase,
                    'approved' => true,
                    'approved_at' => now(),
                    'approved_by' => Auth::id(),
                    'phase_number' => $phaseIndex + 1
                ];
                $this->phase_completion_status = $status;
                $this->save();
            }
            
            // Recalculate completion percentage
            $this->calculateCompletionPercentage();
            
            return true;
        }
        
        // If this was the last phase, mark project as completed
        if ($this->current_phase === 'delivery') {
            $this->status = 'completed';
            $this->save();
        }
        
        return false;
    }

    // Calculate overall completion percentage based on completed phases
    public function calculateCompletionPercentage()
    {
        if (!$this->total_phases || $this->total_phases <= 0) {
            $this->completion_percentage = 0;
            $this->save();
            return 0;
        }
        
        $completedPhases = 0;
        $phaseStatus = $this->phase_completion_status ?? [];
        
        // Count approved phases
        foreach ($phaseStatus as $status) {
            if (isset($status['approved']) && $status['approved'] === true) {
                $completedPhases++;
            }
        }
        
        // Calculate percentage
        $percentage = ($completedPhases / $this->total_phases) * 100;
        $this->completion_percentage = round($percentage);
        
        // Also update the legacy progress field for backward compatibility
        $this->progress = $this->completion_percentage;
        
        $this->save();
        
        return $this->completion_percentage;
    }

    // Check if a specific phase is completed
    public function isPhaseCompleted($phase)
    {
        $phaseIndex = array_search($phase, self::PHASES);
        $status = $this->phase_completion_status ?? [];
        
        return isset($status[$phaseIndex]) && $status[$phaseIndex]['approved'] === true;
    }

    // Get all completed phases
    public function getCompletedPhases()
    {
        $completed = [];
        $status = $this->phase_completion_status ?? [];
        
        foreach ($status as $index => $phaseStatus) {
            if ($phaseStatus['approved'] === true && isset(self::PHASES[$index])) {
                $completed[] = self::PHASES[$index];
            }
        }
        
        return $completed;
    }

    // Get phase completion status with details
    public function getPhaseCompletionDetails()
    {
        $details = [];
        $status = $this->phase_completion_status ?? [];
        
        foreach (self::PHASES as $index => $phase) {
            $details[$phase] = [
                'phase_number' => $index + 1,
                'is_completed' => isset($status[$index]) && $status[$index]['approved'] === true,
                'completed_at' => isset($status[$index]) ? $status[$index]['approved_at'] ?? null : null,
                'approved_by' => isset($status[$index]) ? $status[$index]['approved_by'] ?? null : null
            ];
        }
        
        return $details;
    }

    // Get progress percentage for a specific phase
    public function getPhaseProgress($phase)
    {
        // This can be customized based on your needs
        // For now, it returns 100% if phase is completed, 0% otherwise
        return $this->isPhaseCompleted($phase) ? 100 : 0;
    }

    // Scope for ongoing projects
    public function scopeOngoing($query)
    {
        return $query->where('status', 'ongoing');
    }

    // Scope for projects pending review
    public function scopePendingReview($query)
    {
        return $query->whereHas('updates', function ($q) {
            $q->where('update_type', 'requested')
              ->where('approval_status', 'pending');
        });
    }
}