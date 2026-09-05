<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Employee;
use App\Models\Client;
use App\Models\Project;
use App\Models\QuotationRequest;

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
    const TYPE_LABOR_UPDATED      = 'labor_updated';
    const TYPE_SHOP_DRAWING_SUBMITTED = 'shop_drawing_submitted';
    const TYPE_SHOP_DRAWING_APPROVED  = 'shop_drawing_approved';
    const TYPE_SHOP_DRAWING_REVISION  = 'shop_drawing_revision_requested';
    const TYPE_QUOTATION_SENT         = 'quotation_sent';
    const TYPE_FUND_RELEASED            = 'fund_released';
    const TYPE_FUND_REPLENISHED         = 'fund_replenished';
    const TYPE_CLIENT_SIGNUP_PENDING       = 'client_signup_pending';
    const TYPE_CLIENT_APPROVED             = 'client_approved';
    const TYPE_CLIENT_REJECTED             = 'client_rejected';
    const TYPE_QUOTATION_REQUEST_SUBMITTED = 'quotation_request_submitted';
    const TYPE_QUOTATION_REQUEST_DECLINED  = 'quotation_request_declined';
    const TYPE_QUOTATION_REQUEST_QUOTATION_SENT = 'quotation_request_quotation_sent';
    const TYPE_QUOTATION_REQUEST_APPROVED       = 'quotation_request_approved';

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

    /** Notify a specific client by their clients.id (not tied to any project) */
    public static function notifyClient(
        int     $clientId,
        string  $title,
        string  $message,
        string  $type,
        string  $priority = 'info',
        ?int    $projectId = null,
        ?string $actionUrl = null
    ): void {
        $client = Client::find($clientId);
        if ($client) {
            self::send($client->id, 'client', $title, $message, $type, $priority, $projectId, null, $actionUrl);
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

    /** Admin releases revolving fund money to a project → activity log for admins */
    public static function revolvingFundReleased(Project $project, float $amount, string $purpose): void
    {
        $message = "₱" . number_format($amount, 2) . " released for Project: {$project->name}.\nPurpose: {$purpose}";

        self::notifyAdmins(
            'Revolving Fund Released',
            $message,
            self::TYPE_FUND_RELEASED,
            'info',
            $project->id,
            null,
            '/admin/revolving-fund'
        );
    }

    /** A project payment automatically replenishes the revolving fund → activity log for admins */
    public static function revolvingFundReplenished(Project $project, float $amount): void
    {
        $message = "₱" . number_format($amount, 2) . " replenished from Project: {$project->name} payment.";

        self::notifyAdmins(
            'Revolving Fund Replenished',
            $message,
            self::TYPE_FUND_REPLENISHED,
            'info',
            $project->id,
            null,
            '/admin/revolving-fund'
        );
    }

    /** Material stock is running low (≤25% of purchased stock remaining) → alert admins once per material, no spam */
    public static function lowStockAlert(\App\Models\Project $project, \App\Models\ProjectMaterial $material, ?float $remainingQty = null): void
    {
        // Skip if an unread low-stock alert for this exact material on this project already exists
        $alreadyAlerted = \App\Models\Notification::where('user_type', 'admin')
            ->where('related_project_id', $project->id)
            ->where('title', 'Low Material Stock')
            ->where('message', 'like', '%"' . $material->material_name . '"%')
            ->unread()
            ->exists();

        if ($alreadyAlerted) {
            return;
        }

        $remainingText = $remainingQty !== null ? rtrim(rtrim(number_format($remainingQty, 2), '0'), '.') : $material->quantity;
        $message = "Low stock alert: \"{$material->material_name}\" has only {$remainingText} unit(s) remaining in Project: {$project->name}.";

        self::notifyAdmins(
            'Low Material Stock',
            $message,
            self::TYPE_MATERIAL_REQUESTED,
            'warning',
            $project->id,
            null,
            "/admin/project-materials/{$project->id}"
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

    /** A new client self-registered and is awaiting admin approval → notify admins */
    public static function clientSignupPending(Client $client): void
    {
        self::notifyAdmins(
            'New Client Signup Pending Approval',
            "{$client->name} ({$client->email}) has signed up and is awaiting approval.",
            self::TYPE_CLIENT_SIGNUP_PENDING,
            'warning',
            null,
            null,
            '/admin/clients'
        );
    }

    /** Admin approved a pending client account → notify the client */
    public static function clientApproved(Client $client): void
    {
        self::notifyClient(
            $client->id,
            'Account Approved',
            'Your GMD South Phils client account has been approved. You can now log in.',
            self::TYPE_CLIENT_APPROVED,
            'success',
            null,
            '/login'
        );
    }

    /** Admin rejected a pending client account → notify the client */
    public static function clientRejected(Client $client, ?string $reason = null): void
    {
        self::notifyClient(
            $client->id,
            'Account Application Declined',
            'Your GMD South Phils account application was not approved.' . ($reason ? " Reason: {$reason}" : ''),
            self::TYPE_CLIENT_REJECTED,
            'warning',
            null,
            '/login'
        );
    }

    /** Client submitted a quotation request → notify admins */
    public static function quotationRequestSubmitted(QuotationRequest $request): void
    {
        self::notifyAdmins(
            'New Quotation Request',
            "{$request->client->name} submitted a quotation request for {$request->tank_summary}.",
            self::TYPE_QUOTATION_REQUEST_SUBMITTED,
            'info',
            null,
            null,
            '/admin/quotation-requests'
        );
    }

    /** Admin declined a quotation request → notify the client */
    public static function quotationRequestDeclined(QuotationRequest $request): void
    {
        self::notifyClient(
            $request->client_id,
            'Quotation Request Update',
            'Your quotation request has been reviewed and was not approved at this time.'
                . ($request->decline_reason ? " Reason: {$request->decline_reason}" : ''),
            self::TYPE_QUOTATION_REQUEST_DECLINED,
            'warning',
            null,
            '/client/request-quotation/status'
        );
    }

    /** Admin sent a quotation for a request → notify the client */
    public static function quotationRequestQuotationSent(QuotationRequest $request): void
    {
        self::notifyClient(
            $request->client_id,
            'Quotation Ready for Review',
            "We've prepared a quotation for your {$request->tank_summary} request. Please review and approve it.",
            self::TYPE_QUOTATION_REQUEST_QUOTATION_SENT,
            'info',
            null,
            '/client/request-quotation/status'
        );
    }

    /** Client approved the sent quotation → notify admins */
    public static function quotationRequestApproved(QuotationRequest $request): void
    {
        self::notifyAdmins(
            'Quotation Approved by Client',
            "{$request->client->name} approved the quotation for their {$request->tank_summary} request. It's ready to be converted into a project.",
            self::TYPE_QUOTATION_REQUEST_APPROVED,
            'success',
            null,
            null,
            '/admin/quotation-requests'
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
