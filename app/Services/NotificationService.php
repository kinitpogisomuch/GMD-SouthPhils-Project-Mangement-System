<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Project;

class NotificationService
{
    const TYPE_PROJECT_CREATED    = 'project_created';
    const TYPE_PROGRESS_REQUESTED = 'progress_requested';
    const TYPE_PROGRESS_SUBMITTED = 'progress_submitted';
    const TYPE_REVISION_REQUESTED = 'revision_requested';
    const TYPE_REVISION_SUBMITTED = 'revision_submitted';
    const TYPE_PROGRESS_APPROVED  = 'progress_approved';
    const TYPE_PHASE_ADVANCED     = 'phase_advanced';
    const TYPE_PROJECT_COMPLETED  = 'project_completed';
    const TYPE_PENDING_REVIEW     = 'pending_review';
    const TYPE_MATERIAL_ADDED     = 'material_added';
    const TYPE_MATERIAL_UPDATED   = 'material_updated';
    const TYPE_MATERIAL_REMOVED   = 'material_removed';
    const TYPE_MATERIAL_REQUESTED = 'material_requested';
    const TYPE_MATERIAL_USAGE_LOGGED = 'material_usage_logged';
    const TYPE_LABOR_ADDED        = 'labor_added';
    const TYPE_LABOR_UPDATED      = 'labor_updated';
    const TYPE_SHOP_DRAWING_SUBMITTED = 'shop_drawing_submitted';
    const TYPE_SHOP_DRAWING_APPROVED  = 'shop_drawing_approved';
    const TYPE_SHOP_DRAWING_REVISION  = 'shop_drawing_revision_requested';
    const TYPE_QUOTATION_SENT         = 'quotation_sent';

    // -------------------------------------------------------------------------
    // Core send — accepts any model with an id, or a raw integer ID + type
    // -------------------------------------------------------------------------
    private static function send(
        int    $userId,
        string $userType,
        string $title,
        string $message,
        string $type,
        string $priority,
        ?int   $projectId,
        ?int   $progressId,
        ?string $actionUrl
    ): void {
        Notification::create([
            'user_id'             => $userId,
            'user_type'           => $userType,
            'title'               => $title,
            'message'             => $message,
            'notification_type'   => $type,
            'priority'            => $priority,
            'related_project_id'  => $projectId,
            'related_progress_id' => $progressId,
            'action_url'          => $actionUrl,
            'is_read'             => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Audience helpers
    // -------------------------------------------------------------------------

    /** Notify all active employees (from employees table) */
    public static function notifyAllEmployees(
        string  $title,
        string  $message,
        string  $type,
        string  $priority = 'info',
        ?int    $projectId = null,
        ?int    $progressId = null,
        ?string $actionUrl = null
    ): void {
        Employee::where('status', 'Active')->get()->each(function ($emp) use (
            $title, $message, $type, $priority, $projectId, $progressId, $actionUrl
        ) {
            self::send($emp->id, 'employee', $title, $message, $type, $priority, $projectId, $progressId, $actionUrl);
        });
    }

    /** Notify a specific employee by their employees.id */
    public static function notifyEmployee(
        int     $employeeId,
        string  $title,
        string  $message,
        string  $type,
        string  $priority = 'info',
        ?int    $projectId = null,
        ?int    $progressId = null,
        ?string $actionUrl = null
    ): void {
        $employee = Employee::find($employeeId);
        if ($employee) {
            self::send($employee->id, 'employee', $title, $message, $type, $priority, $projectId, $progressId, $actionUrl);
        }
    }

    /** Notify all admin users (from users table) */
    public static function notifyAdmins(
        string  $title,
        string  $message,
        string  $type,
        string  $priority = 'info',
        ?int    $projectId = null,
        ?int    $progressId = null,
        ?string $actionUrl = null
    ): void {
        User::where('role', 'admin')->get()->each(function ($admin) use (
            $title, $message, $type, $priority, $projectId, $progressId, $actionUrl
        ) {
            self::send($admin->id, 'admin', $title, $message, $type, $priority, $projectId, $progressId, $actionUrl);
        });
    }

    /** Notify the client linked to a project (from clients table) */
    public static function notifyProjectClient(
        Project $project,
        string  $title,
        string  $message,
        string  $type,
        string  $priority = 'info',
        ?int    $progressId = null,
        ?string $actionUrl = null
    ): void {
        $client = null;

        if (!empty($project->email)) {
            $client = Client::where('email', $project->email)->first();
        }

        if (!$client && !empty($project->client)) {
            $client = Client::where('name', $project->client)->first();
        }

        if ($client) {
            self::send($client->id, 'client', $title, $message, $type, $priority, $project->id, $progressId, $actionUrl);
        }
    }

    // -------------------------------------------------------------------------
    // Event-specific notification builders
    // -------------------------------------------------------------------------

    /** Material added to a project → activity log for admins */
    public static function materialAdded(Project $project, string $materialName, float $quantity): void
    {
        self::notifyAdmins(
            'Material Added',
            "Material added to Project: {$project->name}.\nMaterial: {$materialName}\nQuantity: {$quantity}",
            self::TYPE_MATERIAL_ADDED,
            'info',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
        );
    }

    /** Material usage logged for a project → activity log for admins */
    public static function materialUsageLogged(Project $project, string $materialName, float $quantity, ?string $loggedBy = null): void
    {
        $message = "Material usage logged for Project: {$project->name}.\nMaterial: {$materialName}\nQuantity Used: {$quantity}";

        if ($loggedBy) {
            $message .= "\nLogged by: {$loggedBy}";
        }

        self::notifyAdmins(
            'Material Usage Logged',
            $message,
            self::TYPE_MATERIAL_USAGE_LOGGED,
            'info',
            $project->id,
            null,
            "/admin/material-usage/{$project->id}"
        );
    }

    /** Employee flags a material as short and requests more → activity log for admins */
    public static function materialRequested(Project $project, string $employeeName, string $materialName, $quantity, ?string $notes): void
    {
        $message = "{$employeeName} reported a material shortage on Project: {$project->name}.\n" .
            "Material: {$materialName}\nQuantity Needed: {$quantity}";

        if ($notes) {
            $message .= "\nNotes: {$notes}";
        }

        self::notifyAdmins(
            'Material Shortage Reported',
            $message,
            self::TYPE_MATERIAL_REQUESTED,
            'warning',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
        );
    }

    /** Labor entry added to a project → activity log for admins */
    public static function laborAdded(Project $project, string $description, float $dailyRate): void
    {
        self::notifyAdmins(
            'Labor Entry Added',
            "Labor entry added to Project: {$project->name}.\nEmployee: {$description}\nDaily Rate: ₱" . number_format($dailyRate, 2),
            self::TYPE_LABOR_ADDED,
            'info',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
        );
    }

    /** Labor entry updated on a project → activity log for admins */
    public static function laborUpdated(Project $project, string $description): void
    {
        self::notifyAdmins(
            'Labor Entry Updated',
            "Labor entry updated in Project: {$project->name}.\nRole: {$description}",
            self::TYPE_LABOR_UPDATED,
            'info',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
        );
    }

    /** Material updated on a project → activity log for admins */
    public static function materialUpdated(Project $project, string $materialName): void
    {
        self::notifyAdmins(
            'Material Updated',
            "Material updated in Project: {$project->name}.\nMaterial: {$materialName}",
            self::TYPE_MATERIAL_UPDATED,
            'info',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
        );
    }

    /** Material removed from a project → activity log for admins */
    public static function materialRemoved(Project $project, string $materialName): void
    {
        self::notifyAdmins(
            'Material Removed',
            "Material removed from Project: {$project->name}.\nMaterial: {$materialName}",
            self::TYPE_MATERIAL_REMOVED,
            'warning',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
        );
    }

    /** Admin created a new project → notify all employees + client */
    public static function projectCreated(Project $project): void
    {
        $phaseName = ucfirst(str_replace('_', ' ', $project->current_phase));

        self::notifyAllEmployees(
            'New Project Available',
            "A new project has been posted: {$project->name}.\nClient: {$project->client}\nCurrent Phase: {$phaseName}",
            self::TYPE_PROJECT_CREATED,
            'info',
            $project->id,
            null,
            "/employee/project-view/{$project->id}"
        );

        self::notifyProjectClient(
            $project,
            'Your Project Has Been Created',
            "Your project \"{$project->name}\" has been created successfully.",
            self::TYPE_PROJECT_CREATED,
            'success',
            null,
            "/client/project-view/{$project->id}"
        );
    }

    /** Admin requested a progress update → notify all employees */
    public static function progressRequested(Project $project, ?string $adminMessage = null): void
    {
        $body = "Admin has requested a progress update for Project: {$project->name}.";
        if ($adminMessage) {
            $body .= "\nMessage: {$adminMessage}";
        }

        self::notifyAllEmployees(
            'Progress Update Requested',
            $body,
            self::TYPE_PROGRESS_REQUESTED,
            'warning',
            $project->id,
            null,
            "/employee/project-view/{$project->id}"
        );
    }

    /** Employee submitted a progress update → notify admins */
    public static function progressSubmitted(Project $project, Employee $employee, int $updateId): void
    {
        $name = trim($employee->last_name . ', ' . $employee->first_name);

        self::notifyAdmins(
            'Progress Update Submitted',
            "{$name} submitted a progress update for Project: {$project->name}.",
            self::TYPE_PROGRESS_SUBMITTED,
            'info',
            $project->id,
            $updateId,
            "/admin/project-view/{$project->id}"
        );
    }

    /** Admin requested a revision → notify the submitting employee */
    public static function revisionRequested(Project $project, int $employeeId, string $feedback): void
    {
        self::notifyEmployee(
            $employeeId,
            'Revision Required',
            "Revision Required for Project: {$project->name}.\nAdmin Feedback: {$feedback}",
            self::TYPE_REVISION_REQUESTED,
            'warning',
            $project->id,
            null,
            "/employee/project-view/{$project->id}"
        );
    }

    /** Employee submitted a revision → notify admins */
    public static function revisionSubmitted(Project $project, Employee $employee, int $updateId): void
    {
        $name = trim($employee->last_name . ', ' . $employee->first_name);

        self::notifyAdmins(
            'Revision Submitted',
            "{$name} submitted a revision for Project: {$project->name}.",
            self::TYPE_REVISION_SUBMITTED,
            'info',
            $project->id,
            $updateId,
            "/admin/project-view/{$project->id}"
        );
    }

    /**
     * Admin approved an update → phase advanced.
     * Notifies: the submitting employee, all employees (phase change), and the client.
     */
    public static function progressApproved(
        Project $project,
        string  $newPhase,
        int     $submittedByEmployeeId,
        int     $updateId
    ): void {
        $phaseName   = ucfirst(str_replace('_', ' ', $newPhase));
        $isCompleted = ($newPhase === 'delivery' || $project->status === 'completed');

        self::notifyEmployee(
            $submittedByEmployeeId,
            'Progress Update Approved',
            "Your progress update has been approved.\nProject: {$project->name}",
            self::TYPE_PROGRESS_APPROVED,
            'success',
            $project->id,
            $updateId,
            "/employee/project-view/{$project->id}"
        );

        self::notifyAllEmployees(
            'Project Phase Advanced',
            "Project {$project->name} has advanced to the {$phaseName} phase.",
            self::TYPE_PHASE_ADVANCED,
            'info',
            $project->id,
            null,
            "/employee/project-view/{$project->id}"
        );

        if ($isCompleted) {
            self::notifyProjectClient(
                $project,
                'Project Completed',
                "Congratulations! Your project \"{$project->name}\" has been marked as completed.",
                self::TYPE_PROJECT_COMPLETED,
                'success',
                $updateId,
                "/client/project-view/{$project->id}"
            );
        } else {
            self::notifyProjectClient(
                $project,
                'Project Phase Updated',
                "Your project \"{$project->name}\" has advanced to the {$phaseName} phase.\nProject progress has been updated and approved by the administrator.",
                self::TYPE_PHASE_ADVANCED,
                'info',
                $updateId,
                "/client/project-view/{$project->id}"
            );
        }
    }

    /** Owner submitted shop drawing / tank design documents → notify client for review */
    public static function shopDrawingSubmitted(Project $project): void
    {
        self::notifyProjectClient(
            $project,
            'Shop Drawing / Tank Design Update',
            "Shop drawing and tank design documents for \"{$project->name}\" are ready for your review.\nPlease review and approve, or request a revision.",
            self::TYPE_SHOP_DRAWING_SUBMITTED,
            'info',
            null,
            "/client/project-view/{$project->id}"
        );
    }

    /** Client approved the shop drawing / tank design → notify admins */
    public static function shopDrawingApproved(Project $project): void
    {
        self::notifyAdmins(
            'Shop Drawing Approved',
            "The client approved the shop drawing / tank design for Project: {$project->name}.\nYou may now proceed to send the project quotation.",
            self::TYPE_SHOP_DRAWING_APPROVED,
            'success',
            $project->id,
            null,
            "/admin/project-view/{$project->id}"
        );
    }

    /** Client requested a revision of the shop drawing / tank design → notify admins */
    public static function shopDrawingRevisionRequested(Project $project, string $notes): void
    {
        self::notifyAdmins(
            'Shop Drawing Revision Requested',
            "The client requested a revision to the shop drawing / tank design for Project: {$project->name}.\nNotes: {$notes}",
            self::TYPE_SHOP_DRAWING_REVISION,
            'warning',
            $project->id,
            null,
            "/admin/project-view/{$project->id}"
        );
    }

    /** Owner sent the project quotation to the client */
    public static function quotationSent(Project $project): void
    {
        self::notifyProjectClient(
            $project,
            'Quotation Sent',
            "The quotation for project \"{$project->name}\" has been sent to you.\nThe project quotation must be settled before proceeding to the next phase.",
            self::TYPE_QUOTATION_SENT,
            'info',
            null,
            "/client/project-view/{$project->id}"
        );
    }

    /** Project advanced to a new phase → notify all employees, plus the client with a custom message */
    public static function phaseAdvanced(Project $project, string $newPhase, string $clientMessage): void
    {
        $phaseName = ucfirst(str_replace('_', ' ', $newPhase));

        self::notifyAllEmployees(
            'Project Phase Advanced',
            "Project {$project->name} has advanced to the {$phaseName} phase.",
            self::TYPE_PHASE_ADVANCED,
            'info',
            $project->id,
            null,
            "/employee/project-view/{$project->id}"
        );

        self::notifyProjectClient(
            $project,
            'Project Update',
            $clientMessage,
            self::TYPE_PHASE_ADVANCED,
            'info',
            null,
            "/client/project-view/{$project->id}"
        );
    }

    /** Project fully delivered and marked completed → notify client and employees */
    public static function projectCompleted(Project $project): void
    {
        self::notifyProjectClient(
            $project,
            'Project Completed',
            "Congratulations! Your project \"{$project->name}\" has been marked as completed.",
            self::TYPE_PROJECT_COMPLETED,
            'success',
            null,
            "/client/project-view/{$project->id}"
        );

        self::notifyAllEmployees(
            'Project Completed',
            "Project {$project->name} has been completed and delivered.",
            self::TYPE_PROJECT_COMPLETED,
            'success',
            $project->id,
            null,
            "/employee/project-view/{$project->id}"
        );
    }
}
