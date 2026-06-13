<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    private function currentUserId(): ?int
    {
        return session('user_id');
    }

    private function currentUserType(): ?string
    {
        return session('role');
    }

    /*
    |--------------------------------------------------------------------------
    | Unread Count (for polling)
    |--------------------------------------------------------------------------
    */
    public function unreadCount()
    {
        $userId   = $this->currentUserId();
        $userType = $this->currentUserType();
        if (!$userId || !$userType) {
            return response()->json(['count' => 0]);
        }

        $count = Notification::forUser($userId)
            ->where('user_type', $userType)
            ->unread()
            ->count();
        return response()->json(['count' => $count]);
    }

    /*
    |--------------------------------------------------------------------------
    | Recent notifications for the dropdown (last 15)
    |--------------------------------------------------------------------------
    */
    public function recent()
    {
        $userId   = $this->currentUserId();
        $userType = $this->currentUserType();
        if (!$userId || !$userType) {
            return response()->json(['notifications' => [], 'unread_count' => 0]);
        }

        $notifications = Notification::forUser($userId)
            ->where('user_type', $userType)
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->map(fn($n) => $this->format($n));

        $unreadCount = Notification::forUser($userId)
            ->where('user_type', $userType)
            ->unread()
            ->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Mark single notification as read
    |--------------------------------------------------------------------------
    */
    public function markRead($id)
    {
        $userId = $this->currentUserId();
        $notification = Notification::where('id', $id)
                                    ->where('user_id', $userId)
                                    ->firstOrFail();

        $notification->update(['is_read' => DB::raw('true')]);

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Full notifications page
    |--------------------------------------------------------------------------
    */
    public function page(Request $request)
    {
        $userId   = $this->currentUserId();
        $userType = $this->currentUserType();
        $role     = $userType ?? 'admin';

        $filter = $request->query('filter') === 'unread' ? 'unread' : 'all';

        $query = Notification::forUser($userId)
            ->where('user_type', $role);

        if ($filter === 'unread') {
            $query->unread();
        }

        $notifications = $query
            ->orderBy('created_at', 'desc')
            ->paginate(30)
            ->appends(['filter' => $filter]);

        $unreadCount = Notification::forUser($userId)
            ->where('user_type', $role)
            ->unread()
            ->count();

        $view = match($role) {
            'employee' => 'employee.notifications',
            'client'   => 'client.notifications',
            default    => 'admin.notifications',
        };

        return view($view, compact('notifications', 'filter', 'unreadCount'));
    }

    /*
    |--------------------------------------------------------------------------
    | Mark all notifications as read
    |--------------------------------------------------------------------------
    */
    public function markAllRead()
    {
        $userId   = $this->currentUserId();
        $userType = $this->currentUserType();
        if ($userId && $userType) {
            Notification::forUser($userId)
                ->where('user_type', $userType)
                ->unread()
                ->update(['is_read' => DB::raw('true')]);
        }

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | Format a notification for the JSON response
    |--------------------------------------------------------------------------
    */
    private function format(Notification $n): array
    {
        return [
            'id'                  => $n->id,
            'title'               => $n->title,
            'message'             => $n->message,
            'notification_type'   => $n->notification_type,
            'priority'            => $n->priority,
            'action_url'          => $n->action_url,
            'related_project_id'  => $n->related_project_id,
            'related_progress_id' => $n->related_progress_id,
            'is_read'             => $n->is_read,
            'created_at'          => $n->created_at->diffForHumans(),
            'created_at_full'     => $n->created_at->format('M d, Y g:i A'),
        ];
    }
}
