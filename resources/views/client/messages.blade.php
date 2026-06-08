<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | GMD South Phils</title>
    <link href="{{ asset('css/client.css') }}" rel="stylesheet">
</head>
<body>

    @include('partials.client.header')

    <div class="admin-layout">
        @include('partials.client.sidebar')

        <main class="admin-content">

            <div class="card" style="height: calc(100vh - 160px); display:flex; flex-direction:column; margin-bottom:0;">
                <div class="card-header">
                    <span class="card-title">Messages</span>
                    <span class="badge badge-teal">3 Unread</span>
                </div>
                <div style="display:flex; flex:1; overflow:hidden;">

                    <!-- Thread List -->
                    <div style="width:300px; border-right:1px solid var(--border); overflow-y:auto; flex-shrink:0;">

                        @php
                        $threads = [
                            ['name'=>'Admin – GMD', 'preview'=>'Please confirm the updated delivery schedule...', 'time'=>'10:24 AM', 'unread'=>2, 'active'=>true],
                            ['name'=>'Project Manager', 'preview'=>'Tank fabrication Phase 2 is now 65% done.', 'time'=>'Yesterday', 'unread'=>1, 'active'=>false],
                            ['name'=>'Billing Team', 'preview'=>'Invoice INV-2025-004 is now due for payment.', 'time'=>'Mar 28', 'unread'=>0, 'active'=>false],
                        ];
                        @endphp

                        @foreach($threads as $t)
                        <div style="padding:14px 16px; border-bottom:1px solid var(--border); cursor:pointer; background:{{ $t['active'] ? 'var(--accent-light)' : 'transparent' }}; transition:background 0.18s;"
                             onmouseover="this.style.background='var(--surface-2)'"
                             onmouseout="this.style.background='{{ $t['active'] ? 'var(--accent-light)' : 'transparent' }}'">
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                                <div style="font-weight:{{ $t['unread'] > 0 ? '700' : '600' }}; font-size:13.5px;">{{ $t['name'] }}</div>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <span style="font-size:11px; color:var(--text-muted);">{{ $t['time'] }}</span>
                                    @if($t['unread'] > 0)
                                    <span style="background:var(--accent); color:#fff; font-size:10px; font-weight:700; padding:1px 6px; border-radius:999px;">{{ $t['unread'] }}</span>
                                    @endif
                                </div>
                            </div>
                            <div style="font-size:12.5px; color:var(--text-secondary); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">{{ $t['preview'] }}</div>
                        </div>
                        @endforeach

                    </div>

                    <!-- Chat Window -->
                    <div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">

                        <!-- Chat Header -->
                        <div style="padding:14px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; gap:12px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-light);color:var(--accent-dark);display:flex;align-items:center;justify-content:center;">
                                <i data-lucide="user" style="width:16px;height:16px;"></i>
                            </div>
                            <div>
                                <div style="font-weight:700;font-size:14px;">Admin – GMD</div>
                                <div style="font-size:11.5px;color:var(--success);display:flex;align-items:center;gap:4px;">
                                    <span style="width:7px;height:7px;background:var(--success);border-radius:50%;display:inline-block;"></span> Online
                                </div>
                            </div>
                        </div>

                        <!-- Messages -->
                        <div style="flex:1; overflow-y:auto; padding:20px; display:flex; flex-direction:column; gap:16px;">

                            <!-- Received -->
                            <div style="display:flex; gap:10px; max-width:70%;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:auto;">
                                    <i data-lucide="user" style="width:14px;height:14px;color:var(--text-secondary);"></i>
                                </div>
                                <div>
                                    <div style="background:var(--surface-2);border:1px solid var(--border);padding:12px 14px;border-radius:0 12px 12px 12px;font-size:13.5px;line-height:1.6;">
                                        Good morning! The Storage Tank Fabrication Unit A is progressing well. Welding is now at 65% completion.
                                    </div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">10:10 AM</div>
                                </div>
                            </div>

                            <!-- Sent -->
                            <div style="display:flex; justify-content:flex-end;">
                                <div style="max-width:70%;">
                                    <div style="background:var(--accent);color:#fff;padding:12px 14px;border-radius:12px 0 12px 12px;font-size:13.5px;line-height:1.6;">
                                        Great to hear! Can you confirm the updated delivery schedule for the materials?
                                    </div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;text-align:right;">10:18 AM ✓✓</div>
                                </div>
                            </div>

                            <!-- Received -->
                            <div style="display:flex; gap:10px; max-width:70%;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--surface-2);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:auto;">
                                    <i data-lucide="user" style="width:14px;height:14px;color:var(--text-secondary);"></i>
                                </div>
                                <div>
                                    <div style="background:var(--surface-2);border:1px solid var(--border);padding:12px 14px;border-radius:0 12px 12px 12px;font-size:13.5px;line-height:1.6;">
                                        Please confirm the updated delivery schedule — the next batch of materials is expected to arrive April 10.
                                    </div>
                                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">10:24 AM</div>
                                </div>
                            </div>

                        </div>

                        <!-- Input -->
                        <div style="padding:16px 20px; border-top:1px solid var(--border); display:flex; gap:10px; align-items:center;">
                            <input type="text" placeholder="Type a message..."
                                style="flex:1; padding:10px 14px; border:1px solid var(--border-2); border-radius:999px; font-size:13.5px; outline:none; font-family:inherit; background:var(--surface-2);"
                                onfocus="this.style.borderColor='var(--accent)'"
                                onblur="this.style.borderColor='var(--border-2)'">
                            <button class="btn btn-primary" style="border-radius:999px; padding:10px 18px;">
                                <i data-lucide="send"></i> Send
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/client.js') }}"></script>
</body>
</html>