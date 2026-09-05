<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Message;
use App\Models\User;
use App\Services\SupabaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageController extends Controller
{
    protected SupabaseStorageService $storage;

    public function __construct(SupabaseStorageService $storage)
    {
        $this->storage = $storage;
    }

    private function currentActor(): array
    {
        return ['type' => session('role'), 'id' => (int) session('user_id')];
    }

    /** GET /{portal}/messages */
    public function index()
    {
        $me = $this->currentActor();

        $contacts = $this->contactsFor($me['type'], $me['id']);
        $myName   = session('name', 'Me');
        $myPhoto  = session('profile_photo');

        $view = match ($me['type']) {
            'employee' => 'employee.messages',
            'client'   => 'client.messages',
            default    => 'admin.messages',
        };

        return view($view, compact('contacts', 'myName', 'myPhoto'));
    }

    /** GET /admin/messages/contacts — for chat popup */
    public function contacts()
    {
        $me       = $this->currentActor();
        $contacts = $this->contactsFor($me['type'], $me['id']);
        return response()->json($contacts);
    }

    /** GET /{portal}/messages/thread/{type}/{id} */
    public function thread(string $type, int $id)
    {
        $me = $this->currentActor();

        if (!in_array($type, ['admin', 'employee', 'client'], true)) {
            abort(404);
        }

        $messages = Message::betweenActors($me['type'], $me['id'], $type, $id)
            ->orderBy('created_at')
            ->get();

        Message::where('sender_type', $type)
            ->where('sender_id', $id)
            ->where('recipient_type', $me['type'])
            ->where('recipient_id', $me['id'])
            ->unread()
            ->update(['is_read' => DB::raw('true')]);

        return response()->json([
            'contact'  => $this->contactInfo($type, $id),
            'messages' => $messages->map(fn (Message $m) => $this->format($m, $me)),
        ]);
    }

    /** POST /{portal}/messages/send */
    public function send(Request $request)
    {
        $me = $this->currentActor();

        $validated = $request->validate([
            'recipient_type' => 'required|in:admin,employee,client',
            'recipient_id'   => 'required|integer',
            'body'           => 'nullable|string|max:2000',
            'attachments'    => 'array|max:5',
            'attachments.*'  => 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt',
        ]);

        if (empty($validated['body']) && !$request->hasFile('attachments')) {
            return response()->json(['message' => 'A message or attachment is required.'], 422);
        }

        $attachments = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $url = $this->storage->upload($file, 'messages');
                if ($url) {
                    $attachments[] = [
                        'url'  => $url,
                        'name' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                    ];
                }
            }
        }

        $message = Message::create([
            'sender_id'      => $me['id'],
            'sender_type'    => $me['type'],
            'recipient_id'   => $validated['recipient_id'],
            'recipient_type' => $validated['recipient_type'],
            'body'           => $validated['body'] ?? '',
            'attachments'    => $attachments,
        ]);

        // Push it live to the recipient's own channel — formatted from *their*
        // perspective (is_mine = false), since $me here is the sender. Guarded:
        // until real Pusher credentials are configured (or if Pusher hiccups),
        // this must never break sending — the existing polling still covers it.
        try {
            broadcast(new MessageSent(
                $validated['recipient_type'],
                $validated['recipient_id'],
                [
                    'message' => $this->format($message, [
                        'type' => $validated['recipient_type'],
                        'id'   => $validated['recipient_id'],
                    ]),
                    'from' => $me,
                ]
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return response()->json(['message' => $this->format($message, $me)]);
    }

    /** GET /{portal}/messages/unread-count */
    public function unreadCount()
    {
        $me = $this->currentActor();

        if (!$me['id'] || !$me['type']) {
            return response()->json(['count' => 0]);
        }

        $count = Message::where('recipient_type', $me['type'])
            ->where('recipient_id', $me['id'])
            ->unread()
            ->count();

        return response()->json(['count' => $count]);
    }

    /** Build the list of people this actor is allowed to message, with last-message previews */
    private function contactsFor(string $type, int $id)
    {
        $contacts = collect();

        if ($type === 'admin') {
            $contacts = $contacts
                ->concat($this->employeeContacts())
                ->concat($this->clientContacts());
        } elseif ($type === 'employee') {
            $contacts = $contacts
                ->concat($this->adminContacts());
        } elseif ($type === 'client') {
            $contacts = $contacts
                ->concat($this->adminContacts());
        }

        // Every contact's "between actor" thread shares the same "me" side (type/id),
        // so fetch all of this actor's messages once and group by counterpart in PHP
        // instead of running a last-message + unread-count query per contact.
        $allMessages = Message::where(function ($q) use ($type, $id) {
                $q->where('sender_type', $type)->where('sender_id', $id);
            })
            ->orWhere(function ($q) use ($type, $id) {
                $q->where('recipient_type', $type)->where('recipient_id', $id);
            })
            ->orderByDesc('created_at')
            ->get();

        $byCounterpart = $allMessages->groupBy(function (Message $m) use ($type, $id) {
            $isMine = $m->sender_type === $type && (int) $m->sender_id === $id;
            return $isMine
                ? $m->recipient_type . ':' . $m->recipient_id
                : $m->sender_type . ':' . $m->sender_id;
        });

        $contacts = $contacts->map(function (array $c) use ($type, $id, $byCounterpart) {
            $thread = $byCounterpart->get($c['type'] . ':' . $c['id'], collect());
            $last   = $thread->first(); // already ordered desc via the query above

            $c['last_message'] = $last
                ? ($last->body !== '' ? $last->body : ($last->attachments ? 'Sent an attachment' : ''))
                : null;
            $c['last_time']    = $last ? $this->formatTimestamp($last->created_at) : null;
            $c['last_at']      = $last?->created_at?->timestamp ?? 0;
            $c['unread']       = $thread->filter(fn (Message $m) =>
                $m->sender_type === $c['type'] && (int) $m->sender_id === $c['id'] && !$m->is_read
            )->count();

            return $c;
        });

        return $contacts->sortByDesc('last_at')->values();
    }

    private function adminContacts()
    {
        return User::where('role', 'admin')
            ->orderBy('full_name')
            ->get()
            ->map(fn (User $u) => [
                'type' => 'admin',
                'id'   => $u->id,
                'name' => $u->full_name ?: 'Admin',
                'role' => 'Admin',
                'profile_photo' => $u->profile_photo,
            ]);
    }

    private function employeeContacts()
    {
        return Employee::where('status', 'Active')
            ->orderBy('first_name')
            ->get()
            ->map(fn (Employee $e) => [
                'type' => 'employee',
                'id'   => $e->id,
                'name' => $e->name,
                'role' => $e->role ?? 'Employee',
                'profile_photo' => $e->profile_photo,
            ]);
    }

    private function clientContacts()
    {
        return Client::orderBy('first_name')
            ->get()
            ->map(fn (Client $c) => [
                'type' => 'client',
                'id'   => $c->id,
                'name' => $c->full_name,
                'role' => 'Client',
                'profile_photo' => $c->profile_photo,
            ]);
    }

    private function contactInfo(string $type, int $id): ?array
    {
        return match ($type) {
            'admin'    => ($u = User::find($id)) ? [
                'type' => 'admin', 'id' => $u->id, 'name' => $u->full_name ?: 'Admin', 'role' => 'Admin',
                'email' => $u->email, 'contact' => $u->phone, 'address' => $u->address, 'profile_photo' => $u->profile_photo,
            ] : null,
            'employee' => ($e = Employee::find($id)) ? [
                'type' => 'employee', 'id' => $e->id, 'name' => $e->name, 'role' => $e->role ?? 'Employee',
                'email' => $e->email, 'contact' => $e->contact, 'address' => $e->address, 'profile_photo' => $e->profile_photo,
            ] : null,
            'client'   => ($c = Client::find($id)) ? [
                'type' => 'client', 'id' => $c->id, 'name' => $c->full_name, 'role' => 'Client',
                'email' => $c->email, 'contact' => $c->contact, 'address' => $c->address, 'profile_photo' => $c->profile_photo,
            ] : null,
            default    => null,
        };
    }

    private function format(Message $m, array $me): array
    {
        return [
            'id'          => $m->id,
            'body'        => $m->body,
            'attachments' => $m->attachments ?? [],
            'is_mine'     => $m->sender_type === $me['type'] && (int) $m->sender_id === $me['id'],
            'time'        => $m->created_at->format('g:i A'),
            'date_label'  => $m->created_at->format('M j, Y'),
            'created_at'  => $m->created_at->toIso8601String(),
        ];
    }

    private function formatTimestamp($dt): ?string
    {
        if (!$dt) return null;
        if ($dt->isToday()) return $dt->format('g:i A');
        if ($dt->isYesterday()) return 'Yesterday';
        return $dt->format('M j');
    }
}
