<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
</head>
<body class="page-enter">

    @include('partials.admin.header')

    <div class="admin-layout">
        @include('partials.admin.sidebar')

        <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your profile, password, and system user accounts.</p>
                </div>
            </div>

            @if(session('success'))
            <div class="alert-banner success">
                <i data-lucide="check-circle"></i>
                {{ session('success') }}
            </div>
            @endif

            @if($errors->hasBag('portfolioEdit'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                @foreach($errors->getBag('portfolioEdit')->all() as $error)
                    {{ $error }}
                @endforeach
            </div>
            @endif

            <!-- Settings Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab" data-tab="profile">
                    <i data-lucide="user"></i>
                    My Profile
                </button>
                <button class="emp-tab" data-tab="password">
                    <i data-lucide="lock"></i>
                    Security
                </button>
                <button class="emp-tab" data-tab="landing">
                    <i data-lucide="image"></i>
                    Landing Page
                </button>
            </div>

            <!-- ===== TAB: PROFILE ===== -->
            <div class="emp-tab-content" id="tab-profile">
                <div class="settings-layout">

                    <!-- Left: Avatar Card -->
                    <div class="settings-avatar-card">
                        <div class="settings-avatar" id="avatarDisplay">
                            @if(session('profile_photo'))
                                <img src="{{ session('profile_photo') }}" alt="Profile"
                                     style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                            @else
                                <span style="font-size:32px;font-weight:900;color:var(--white);">
                                    {{ strtoupper(substr($adminData->last_name ?: $adminData->first_name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div class="settings-avatar-name">{{ $adminData->full_name ?: 'Admin' }}</div>
                        <div class="settings-avatar-role">Administrator</div>

                        <form method="POST" action="{{ route('admin.settings.photo') }}"
                              enctype="multipart/form-data" id="photoUploadForm">
                            @csrf
                            <input type="file" name="profile_photo" id="avatarInput"
                                   accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </form>
                        <label class="avatar-change-btn" onclick="document.getElementById('avatarInput').click()" style="cursor:pointer;">
                            <i data-lucide="camera"></i>
                            Change Photo
                        </label>
                        <div class="settings-meta-list">
                            <div class="settings-meta-item">
                                <i data-lucide="shield"></i>
                                <span>Administrator</span>
                            </div>
                            <div class="settings-meta-item">
                                <i data-lucide="calendar"></i>
                                <span>Member since {{ $adminData->member_since }}</span>
                            </div>
                            <div class="settings-meta-item">
                                <i data-lucide="map-pin"></i>
                                <span>GMD South Phils</span>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Profile Form -->
                    <div style="flex:1;">
                        <div class="pv-card">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                <h3 class="pv-card-title" style="margin-bottom:0;">Profile Information</h3>
                                <div id="profileActions">
                                    <button type="button" class="save-btn" id="editProfileBtn" onclick="enableEdit()">
                                        <i data-lucide="pencil"></i>
                                        Edit Profile
                                    </button>
                                </div>
                                <div id="profileEditActions" style="display:none;">
                                    <button type="button" class="cancel-btn" onclick="cancelEdit()">
                                        <i data-lucide="x"></i> Cancel
                                    </button>
                                    <button type="button" id="saveProfileBtn" class="save-btn">
                                        <i data-lucide="save"></i> Save Changes
                                    </button>
                                </div>
                            </div>

                            @if($errors->hasBag('profile') && $errors->getBag('profile')->any())
                            <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-bottom:16px;color:#dc2626;font-size:13px;">
                                @foreach($errors->getBag('profile')->all() as $error)
                                    <div>• {{ $error }}</div>
                                @endforeach
                            </div>
                            @endif

                            <form method="POST" action="{{ route('admin.settings.profile') }}" id="profileForm">
                                @csrf
                                @method('PUT')
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" name="first_name" class="profile-field"
                                               value="{{ old('first_name', $adminData->first_name) }}"
                                               placeholder="First name" disabled maxlength="100"
                                               oninput="profileStripDigits(this); profileCapName(this)">
                                        <span class="profile-field-err" id="profileFirstNameErr"></span>
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" name="last_name" class="profile-field"
                                               value="{{ old('last_name', $adminData->last_name) }}"
                                               placeholder="Last name" disabled maxlength="100"
                                               oninput="profileStripDigits(this); profileCapName(this)">
                                        <span class="profile-field-err" id="profileLastNameErr"></span>
                                    </div>
                                    <div class="form-group">
                                        <label>Username <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="{{ $adminData->username }}" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="profile-field"
                                               value="{{ old('email', $adminData->email) }}"
                                               placeholder="Email address" disabled maxlength="255"
                                               oninput="profileValidateEmail(this)">
                                        <span class="profile-field-err" id="profileEmailErr"></span>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="phone" class="profile-field"
                                               value="{{ old('phone', $adminData->phone) }}"
                                               placeholder="e.g. 09XX XXX XXXX" disabled maxlength="12"
                                               oninput="profilePhoneInput(this)">
                                        <span class="profile-field-err" id="profilePhoneErr"></span>
                                    </div>
                                    <div class="form-group">
                                        <label>Position / Role <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="Administrator" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ===== TAB: SECURITY ===== -->
            <div class="emp-tab-content" id="tab-password">
                <div class="pv-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:20px;flex-wrap:wrap;gap:10px;">
                        <div>
                            <h3 class="pv-card-title" style="margin-bottom:4px;">Security Settings</h3>
                            <p style="font-size:13px;color:var(--muted);margin:0;">
                                Password must be at least 6 characters with an uppercase letter, lowercase letter, and number.
                            </p>
                        </div>
                    </div>

                    @if($errors->hasBag('password') && $errors->getBag('password')->any())
                    <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-bottom:16px;color:#dc2626;font-size:13px;">
                        @foreach($errors->getBag('password')->all() as $error)
                            <div>• {{ $error }}</div>
                        @endforeach
                    </div>
                    @endif

                    <form method="POST" action="{{ route('admin.settings.password') }}" id="passwordForm">
                        @csrf
                        @method('PUT')
                        <div class="form-grid" style="grid-template-columns:repeat(3,1fr);">
                            <div class="form-group">
                                <label>Current Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="current_password" id="currentPassword"
                                           placeholder="Enter current password">
                                    <button type="button" class="toggle-pw" data-target="currentPassword">
                                        <span class="pw-icon-off">@include('partials.icons.eye-off')</span>
                                        <span class="pw-icon-on" style="display:none;">@include('partials.icons.eye')</span>
                                    </button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password" id="newPassword"
                                           placeholder="Enter new password">
                                    <button type="button" class="toggle-pw" data-target="newPassword">
                                        <span class="pw-icon-off">@include('partials.icons.eye-off')</span>
                                        <span class="pw-icon-on" style="display:none;">@include('partials.icons.eye')</span>
                                    </button>
                                </div>
                                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                                    <span class="pw-req" id="req-len">Min 6 chars</span>
                                    <span class="pw-req" id="req-upper">Uppercase</span>
                                    <span class="pw-req" id="req-lower">Lowercase</span>
                                    <span class="pw-req" id="req-num">Number</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password_confirmation" id="confirmPassword"
                                           placeholder="Confirm new password">
                                    <button type="button" class="toggle-pw" data-target="confirmPassword">
                                        <span class="pw-icon-off">@include('partials.icons.eye-off')</span>
                                        <span class="pw-icon-on" style="display:none;">@include('partials.icons.eye')</span>
                                    </button>
                                </div>
                                <span class="pw-req" id="req-match" style="margin-top:8px;display:inline-flex;">Passwords match</span>
                            </div>
                        </div>
                        <div class="settings-form-actions">
                            <button type="reset" class="cancel-btn"><i data-lucide="x"></i> Clear</button>
                            <button type="submit" class="save-btn"><i data-lucide="lock"></i> Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ===== TAB: LANDING PAGE ===== -->
            <div class="emp-tab-content" id="tab-landing">

                <!-- Contact Info -->
                <div class="pv-card" style="margin-bottom:24px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <div>
                            <h3 class="pv-card-title" style="margin-bottom:4px;">Contact Information</h3>
                            <p style="font-size:13px;color:var(--muted);margin:0;">Displayed on the public landing page.</p>
                        </div>
                        <div id="contactInfoActions">
                            <button type="button" class="save-btn" id="editContactInfoBtn" onclick="enableContactEdit()">
                                <i data-lucide="pencil"></i>
                                Edit Contact Info
                            </button>
                        </div>
                        <div id="contactInfoEditActions" style="display:none;">
                            <button type="button" class="cancel-btn" onclick="cancelContactEdit()">
                                <i data-lucide="x"></i> Cancel
                            </button>
                            <button type="submit" form="contactInfoForm" class="save-btn">
                                <i data-lucide="save"></i> Save Contact Info
                            </button>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('admin.settings.contact_info') }}" id="contactInfoForm">
                        @csrf
                        @method('PUT')
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="address" class="contact-field"
                                       value="{{ old('address', $contactInfo->address) }}"
                                       placeholder="e.g. Brgy. Masiit, Calauan, Philippines, 4012" maxlength="500" disabled>
                            </div>
                            <div class="form-group">
                                <label>Phone</label>
                                <input type="text" name="phone" class="contact-field"
                                       value="{{ old('phone', $contactInfo->phone) }}"
                                       placeholder="e.g. 0917 652 5201" maxlength="20" disabled>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="contact-field"
                                       value="{{ old('email', $contactInfo->email) }}"
                                       placeholder="e.g. gmdsouthphils@gmail.com" maxlength="255" disabled>
                            </div>
                            <div class="form-group">
                                <label>Facebook Page URL</label>
                                <input type="text" name="facebook" class="contact-field"
                                       value="{{ old('facebook', $contactInfo->facebook) }}"
                                       placeholder="e.g. https://facebook.com/gmdsouthphils" maxlength="255" disabled>
                            </div>
                            <div class="form-group form-group-full">
                                <label>Business Hours</label>
                                <input type="text" name="business_hours" class="contact-field"
                                       value="{{ old('business_hours', $contactInfo->business_hours) }}"
                                       placeholder="e.g. Monday – Saturday, 8:00 AM – 5:00 PM" maxlength="255" disabled>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="table-card">
                    <div class="table-toolbar">
                        <span style="font-weight:700;font-size:15px;">"Our Work" Portfolio Items</span>
                        <button class="add-btn" type="button" id="openAddPortfolioModal">
                            <i data-lucide="plus"></i>
                            Add Portfolio Item
                        </button>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Preview</th>
                                    <th>Capacity</th>
                                    <th>Tag</th>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($portfolioItems as $item)
                                @php
                                    $itemSrc = $item->image_url && !str_starts_with($item->image_url, 'http')
                                        ? asset($item->image_url)
                                        : $item->image_url;
                                @endphp
                                <tr>
                                    <td>
                                        @if($itemSrc)
                                            <img src="{{ $itemSrc }}" alt="" style="width:60px;height:40px;object-fit:cover;border-radius:8px;">
                                        @else
                                            <div style="width:60px;height:40px;border-radius:8px;background:var(--cream-soft);display:flex;align-items:center;justify-content:center;">
                                                <i data-lucide="{{ $item->icon ?: 'image' }}" style="width:18px;height:18px;"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item->spec }}</td>
                                    <td>{{ $item->tag }}</td>
                                    <td><strong>{{ $item->title }}</strong></td>
                                    <td style="max-width:200px;white-space:normal;font-size:12px;color:var(--muted);">{{ Str::limit($item->description, 60) }}</td>
                                    <td>
                                        <span class="status-badge {{ $item->status === 'active' ? 'active' : 'archived' }}">
                                            {{ $item->status === 'active' ? 'Visible' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <button class="action-btn view edit-portfolio-btn" type="button"
                                                data-id="{{ $item->id }}"
                                                data-image="{{ $itemSrc }}"
                                                data-icon="{{ $item->icon }}"
                                                data-spec="{{ $item->spec }}"
                                                data-tag="{{ $item->tag }}"
                                                data-title="{{ $item->title }}"
                                                data-description="{{ $item->description }}"
                                                title="Edit">
                                            <i data-lucide="pencil"></i>
                                        </button>
                                        <button class="action-btn view toggle-visibility-btn" type="button"
                                                data-archive-url="{{ route('admin.portfolio.archive', $item->id) }}"
                                                data-hidden="{{ $item->status === 'archived' ? '1' : '0' }}"
                                                data-name="{{ $item->title }}"
                                                title="{{ $item->status === 'archived' ? 'Show on landing page' : 'Hide from landing page' }}">
                                            <i data-lucide="{{ $item->status === 'archived' ? 'eye' : 'eye-off' }}"></i>
                                        </button>
                                        <button class="action-btn view delete-item-btn" type="button"
                                                data-delete-url="{{ route('admin.portfolio.destroy', $item->id) }}"
                                                data-name="{{ $item->title }}"
                                                title="Delete">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                        No portfolio items yet. Click "Add Portfolio Item" to create one.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-card" style="margin-top:24px;">
                    <div class="table-toolbar">
                        <span style="font-weight:700;font-size:15px;">Client Reviews</span>
                    </div>
                    <div class="table-wrapper">
                        <table class="data-table" style="table-layout:fixed;width:100%;">
                            <colgroup>
                                <col style="width:22%;">
                                <col style="width:13%;">
                                <col style="width:10%;">
                                <col style="width:33%;">
                                <col style="width:10%;">
                                <col style="width:12%;">
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Client</th>
                                    <th>Rating</th>
                                    <th>Review</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reviews as $review)
                                <tr>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><strong>{{ $review->project->name ?? 'N/A' }}</strong></td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $review->client_name }}</td>
                                    <td>
                                        <div style="display:flex;gap:2px;">
                                            @for($i=1;$i<=5;$i++)
                                                <i data-lucide="star" style="width:13px;height:13px;color:{{ $i <= $review->rating ? 'var(--accent)' : 'var(--border)' }};{{ $i <= $review->rating ? 'fill:var(--accent);' : '' }}"></i>
                                            @endfor
                                        </div>
                                    </td>
                                    <td style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $review->comment }}">{{ \Illuminate\Support\Str::limit($review->comment, 80) }}</td>
                                    <td>
                                        <span class="status-badge {{ $review->status === 'active' ? 'active' : 'archived' }}">
                                            {{ $review->status === 'active' ? 'Visible' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="action-cell">
                                        <button class="action-btn view toggle-visibility-btn" type="button"
                                                data-archive-url="{{ route('admin.reviews.archive', $review->id) }}"
                                                data-hidden="{{ $review->status === 'archived' ? '1' : '0' }}"
                                                data-name="{{ $review->client_name }}'s review"
                                                title="{{ $review->status === 'archived' ? 'Show on landing page' : 'Hide from landing page' }}">
                                            <i data-lucide="{{ $review->status === 'archived' ? 'eye' : 'eye-off' }}"></i>
                                        </button>
                                        <button class="action-btn view delete-item-btn" type="button"
                                                data-delete-url="{{ route('admin.reviews.destroy', $review->id) }}"
                                                data-name="{{ $review->client_name }}'s review"
                                                title="Delete">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                                        No client reviews yet.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ===== TAB: KPI TARGETS ===== -->
        </main>
    </div>

    <!-- ===== ADD PORTFOLIO ITEM MODAL ===== -->
    <div class="modal-overlay" id="addPortfolioModal">
        <div class="modal-card" style="max-width:560px;">
            <div class="modal-header">
                <div>
                    <h2>Add Portfolio Item</h2>
                    <p>Add a new project card to the "Our Work" section of the landing page.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddPortfolioModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.portfolio.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group form-group-full">
                        <label>Image <span style="font-weight:400;color:var(--muted);">(optional)</span></label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label>Capacity / Badge </label>
                        <input type="text" name="spec" required value="{{ old('spec') }}" placeholder="e.g. 10,000 L">
                    </div>
                    <div class="form-group">
                        <label>Category Tag</label>
                        <select name="tag" id="addTagSelect" required onchange="toggleAddCustomTag(this)">
                            <option value="" disabled selected hidden>Select category...</option>
                            <option value="Water Storage">Water Storage</option>
                            <option value="Oil Storage">Oil Storage</option>
                            <option value="Pipe Line">Pipe Line</option>
                            <option value="Tetrapod">Tetrapod</option>
                            <option value="Fuel Storage Tank">Fuel Storage Tank</option>
                            <option value="Cistern Tank">Cistern Tank</option>
                            <option value="__other__">Others (type manually)</option>
                        </select>
                        <input type="text" id="addTagCustom" name="tag_custom" placeholder="Enter custom category" maxlength="100" style="display:none;margin-top:8px;">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Title </label>
                        <input type="text" name="title" required value="{{ old('title') }}" placeholder="e.g. Diesel Storage Tank — Distribution Depot">
                    </div>
                    <div class="form-group form-group-full">
                        <label>Description </label>
                        <textarea name="description" rows="4" required style="resize:none;" placeholder="Short description of the project">{{ old('description') }}</textarea>
                    </div>
                </div>

                @if($errors->hasBag('portfolio'))
                <div style="background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:12px;margin-top:4px;color:#dc2626;font-size:13px;">
                    @foreach($errors->getBag('portfolio')->all() as $error)
                        <div>• {{ $error }}</div>
                    @endforeach
                </div>
                @endif

                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelAddPortfolio">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== EDIT PORTFOLIO ITEM MODAL ===== -->
    <div class="modal-overlay" id="editPortfolioModal">
        <div class="modal-card" style="max-width:620px;">
            <div class="modal-header">
                <div>
                    <h2>Edit Portfolio Item</h2>
                    <p>Update this project card on the landing page.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditPortfolioModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editPortfolioForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                {{-- Image section --}}
                <div class="form-group form-group-full" style="margin-bottom:14px;">
                    <label>Image <span style="font-weight:400;color:var(--muted);">(optional — choosing a new file replaces the current one)</span></label>
                    <div style="display:flex;align-items:center;gap:14px;">
                        <img id="editPortfolioImagePreview" src="" alt=""
                             style="display:none;width:70px;height:56px;object-fit:cover;border-radius:10px;border:1px solid var(--border);flex-shrink:0;">
                        <input type="file" name="image" id="editPortfolioImageInput" accept="image/*" style="flex:1;min-width:0;">
                    </div>
                </div>

                {{-- Two-column layout --}}
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group">
                        <label>Capacity / Badge</label>
                        <input type="text" name="spec" id="editPortfolioSpec" required placeholder="e.g. 10,000 L">
                    </div>
                    <div class="form-group">
                        <label>Category Tag</label>
                        <select name="tag" id="editTagSelect" required onchange="toggleEditCustomTag(this)">
                            <option value="" disabled hidden>Select category...</option>
                            <option value="Water Storage">Water Storage</option>
                            <option value="Oil Storage">Oil Storage</option>
                            <option value="Pipe Line">Pipe Line</option>
                            <option value="Tetrapod">Tetrapod</option>
                            <option value="Fuel Storage Tank">Fuel Storage Tank</option>
                            <option value="Cistern Tank">Cistern Tank</option>
                            <option value="__other__">Others (type manually)</option>
                        </select>
                        <input type="text" id="editTagCustom" name="tag_custom" placeholder="Enter custom category" maxlength="100" style="display:none;margin-top:8px;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label>Title</label>
                    <input type="text" name="title" id="editPortfolioTitle" required placeholder="e.g. Diesel Storage Tank — Distribution Depot">
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label>Description</label>
                    <textarea name="description" id="editPortfolioDescription" rows="4" required
                              style="resize:none;"
                              placeholder="Short description of the project..."></textarea>
                </div>

                <div class="modal-actions" style="margin-top:20px;">
                    <button type="button" class="cancel-btn" id="cancelEditPortfolio">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save" style="width:15px;height:15px;"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== TOGGLE VISIBILITY (HIDE/SHOW) MODAL ===== -->
    <div class="modal-overlay" id="visibilityModal">
        <div class="modal-card" style="max-width:460px;">
            <div class="modal-header">
                <div>
                    <h2 id="visibilityModalTitle">Hide Item</h2>
                    <p id="visibilityModalSubtitle">This will remove it from the landing page.</p>
                </div>
                <button class="modal-close" type="button" id="closeVisibilityModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="eye-off" id="visibilityModalIcon"></i></div>
                <p id="visibilityModalMsg">Are you sure you want to hide this item?</p>
            </div>
            <form method="POST" id="visibilityModalForm">
                @csrf
                @method('PATCH')
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelVisibilityModal">Cancel</button>
                    <button type="submit" class="save-btn" id="visibilityModalConfirmBtn">
                        <i data-lucide="eye-off"></i> Hide
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== DELETE ITEM MODAL ===== -->
    <div class="modal-overlay" id="deleteItemModal">
        <div class="modal-card" style="max-width:460px;">
            <div class="modal-header">
                <div>
                    <h2>Delete Item</h2>
                    <p>This action cannot be undone.</p>
                </div>
                <button class="modal-close" type="button" id="closeDeleteItemModal">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon"><i data-lucide="trash-2"></i></div>
                <p id="deleteItemMsg">Permanently delete this item?</p>
            </div>
            <form method="POST" id="deleteItemForm">
                @csrf
                @method('DELETE')
                <div class="modal-actions">
                    <button type="button" class="cancel-btn" id="cancelDeleteItem">Cancel</button>
                    <button type="submit" class="delete-btn">
                        <i data-lucide="trash-2"></i> Delete
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="toast" id="successToast">
        <i data-lucide="check-circle"></i>
        <span id="toastMsg">Saved successfully.</span>
    </div>

    <script>const ACTIVE_TAB = "{{ session('active_tab', request('tab', 'profile')) }}";</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="{{ asset('js/admin.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Activate tab
        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === ACTIVE_TAB);
        });
        document.querySelectorAll('.emp-tab-content').forEach(function (pane) {
            pane.classList.toggle('active', pane.id === 'tab-' + ACTIVE_TAB);
        });
        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.emp-tab').forEach(b => b.classList.remove('active'));
                document.querySelectorAll('.emp-tab-content').forEach(p => p.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-' + this.getAttribute('data-tab')).classList.add('active');
            });
        });

        // If validation errors exist on profile, open in edit mode
        @if($errors->hasBag('profile') && $errors->getBag('profile')->any())
        enableEdit();
        @endif

        // If validation errors exist on Add Portfolio Item, reopen that modal
        @if($errors->hasBag('portfolio'))
        openModal('addPortfolioModal');
        @endif

        // Save profile — validate then enable all fields and submit
        document.getElementById('saveProfileBtn').addEventListener('click', function () {
            if (!profileFormValid()) return;
            document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
                f.removeAttribute('disabled');
            });
            document.getElementById('profileForm').submit();
        });

        // User search (only if users tab exists)
        var userSearchEl = document.getElementById('userSearch');
        if (userSearchEl) {
            userSearchEl.addEventListener('keyup', function () {
                var q = this.value.toLowerCase();
                document.querySelectorAll('#usersTable tbody tr').forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            });
        }


        // Password strength indicators
        var pwInput   = document.getElementById('newPassword');
        var confInput = document.getElementById('confirmPassword');
        if (pwInput) {
            pwInput.addEventListener('input', checkPw);
            if (confInput) confInput.addEventListener('input', checkPw);
        }

        // ---- Add Portfolio Item Modal ----
        document.getElementById('openAddPortfolioModal')
            .addEventListener('click', function () { openModal('addPortfolioModal'); });
        document.getElementById('closeAddPortfolioModal')
            .addEventListener('click', function () { closeModal('addPortfolioModal'); });
        document.getElementById('cancelAddPortfolio')
            .addEventListener('click', function () { closeModal('addPortfolioModal'); });

        // ---- Edit Portfolio Item Modal ----
        document.querySelectorAll('.edit-portfolio-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.getElementById('editPortfolioForm').action = '/admin/portfolio-items/' + this.dataset.id;
                document.getElementById('editPortfolioSpec').value = this.dataset.spec || '';
                // Set tag dropdown — if value not in list, select Others + show custom input
                var tagVal = this.dataset.tag || '';
                var tagSel = document.getElementById('editTagSelect');
                var tagCustom = document.getElementById('editTagCustom');
                var tagOpts = Array.from(tagSel.options).map(function(o){ return o.value; });
                if (tagOpts.indexOf(tagVal) !== -1 && tagVal !== '__other__') {
                    tagSel.value = tagVal;
                    tagCustom.style.display = 'none';
                    tagCustom.value = '';
                } else {
                    tagSel.value = '__other__';
                    tagCustom.style.display = '';
                    tagCustom.value = tagVal;
                }
                document.getElementById('editPortfolioTitle').value = this.dataset.title || '';
                document.getElementById('editPortfolioDescription').value = this.dataset.description || '';

                var imageInput = document.getElementById('editPortfolioImageInput');
                imageInput.value = '';

                var preview = document.getElementById('editPortfolioImagePreview');
                if (this.dataset.image) {
                    preview.src = this.dataset.image;
                    preview.style.display = '';
                } else {
                    preview.src = '';
                    preview.style.display = 'none';
                }

                openModal('editPortfolioModal');
            });
        });
        document.getElementById('closeEditPortfolioModal')
            .addEventListener('click', function () { closeModal('editPortfolioModal'); });
        document.getElementById('cancelEditPortfolio')
            .addEventListener('click', function () { closeModal('editPortfolioModal'); });

        // ---- Toggle Visibility (Hide/Show) Modal — shared by Portfolio Items & Reviews ----
        document.querySelectorAll('.toggle-visibility-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var isHidden = this.dataset.hidden === '1';
                var name = this.dataset.name || 'this item';

                document.getElementById('visibilityModalTitle').textContent = isHidden ? 'Show Item' : 'Hide Item';
                document.getElementById('visibilityModalSubtitle').textContent = isHidden
                    ? 'This will make it visible on the landing page again.'
                    : 'This will remove it from the landing page.';
                document.getElementById('visibilityModalMsg').textContent = isHidden
                    ? 'Show "' + name + '" on the landing page again?'
                    : 'Hide "' + name + '" from the landing page?';
                document.getElementById('visibilityModalIcon').setAttribute('data-lucide', isHidden ? 'eye' : 'eye-off');
                document.getElementById('visibilityModalConfirmBtn').innerHTML = isHidden
                    ? '<i data-lucide="eye"></i> Show'
                    : '<i data-lucide="eye-off"></i> Hide';
                document.getElementById('visibilityModalForm').action = this.dataset.archiveUrl;

                openModal('visibilityModal');
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        });
        document.getElementById('closeVisibilityModal')
            .addEventListener('click', function () { closeModal('visibilityModal'); });
        document.getElementById('cancelVisibilityModal')
            .addEventListener('click', function () { closeModal('visibilityModal'); });

        // ---- Delete Item Modal — shared by Portfolio Items & Reviews ----
        document.querySelectorAll('.delete-item-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var name = this.dataset.name || 'this item';
                document.getElementById('deleteItemMsg').textContent = 'Permanently delete "' + name + '"? This cannot be undone.';
                document.getElementById('deleteItemForm').action = this.dataset.deleteUrl;
                openModal('deleteItemModal');
            });
        });
        document.getElementById('closeDeleteItemModal')
            .addEventListener('click', function () { closeModal('deleteItemModal'); });
        document.getElementById('cancelDeleteItem')
            .addEventListener('click', function () { closeModal('deleteItemModal'); });

        // ---- Live preview when replacing the portfolio image ----
        document.getElementById('editPortfolioImageInput')?.addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file) return;

            var preview = document.getElementById('editPortfolioImagePreview');
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = '';
            };
            reader.readAsDataURL(file);
        });

        // ---- Overlay click to close ----
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeModal(this.id);
            });
        });
    });

    // Category tag dropdown helpers
    var TAG_CATEGORIES = ['Water Storage','Oil Storage','Pipe Line','Tetrapod','Fuel Storage Tank','Cistern Tank'];

    function toggleAddCustomTag(sel) {
        var custom = document.getElementById('addTagCustom');
        if (sel.value === '__other__') { custom.style.display=''; custom.required=true; custom.focus(); }
        else { custom.style.display='none'; custom.required=false; custom.value=''; }
    }

    function toggleEditCustomTag(sel) {
        var custom = document.getElementById('editTagCustom');
        if (sel.value === '__other__') { custom.style.display=''; custom.required=true; custom.focus(); }
        else { custom.style.display='none'; custom.required=false; custom.value=''; }
    }

    // Before submitting add/edit forms, merge custom tag into hidden tag field
    document.getElementById('addPortfolioModal')?.querySelector('form')?.addEventListener('submit', function() {
        var sel = document.getElementById('addTagSelect');
        if (sel && sel.value === '__other__') {
            var custom = document.getElementById('addTagCustom');
            sel.removeAttribute('name');
            custom.name = 'tag';
        }
    });

    document.getElementById('editPortfolioForm')?.addEventListener('submit', function() {
        var sel = document.getElementById('editTagSelect');
        if (sel && sel.value === '__other__') {
            var custom = document.getElementById('editTagCustom');
            sel.removeAttribute('name');
            custom.name = 'tag';
        }
    });

    function openModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.add('show'); document.body.style.overflow = 'hidden'; }
    }
    function closeModal(id) {
        var m = document.getElementById(id);
        if (m) { m.classList.remove('show'); document.body.style.overflow = ''; }
    }

    // --- Profile edit/cancel ---
    var originalValues = {};

    // ---- Profile field helpers ----
    function profileSetErr(id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        el.textContent = msg;
        el.style.display = msg ? 'block' : 'none';
        var input = el.previousElementSibling;
        if (input) input.style.borderColor = msg ? '#dc2626' : '';
    }

    function profileStripDigits(input) {
        var pos     = input.selectionStart;
        var cleaned = input.value.replace(/[0-9]/g, '');
        if (cleaned !== input.value) {
            input.value = cleaned;
            input.setSelectionRange(Math.max(0, pos - 1), Math.max(0, pos - 1));
        }
    }

    function profileCapName(input) {
        var pos = input.selectionStart;
        input.value = input.value.replace(/(^|[\s'\-])([a-zà-öø-ÿñ])/g, function(_, sep, ch) {
            return sep + ch.toUpperCase();
        });
        input.setSelectionRange(pos, pos);
    }

    function profilePhoneInput(input) {
        var pos     = input.selectionStart;
        var digits  = input.value.replace(/[^0-9]/g, '').slice(0, 12);
        input.value = digits;
        input.setSelectionRange(Math.min(pos, digits.length), Math.min(pos, digits.length));
        var len = digits.length;
        if (len === 0) {
            profileSetErr('profilePhoneErr', '');
        } else if (len < 11) {
            profileSetErr('profilePhoneErr', 'Phone number is too short (minimum 11 digits).');
        } else if (len > 11) {
            profileSetErr('profilePhoneErr', 'Phone number must not exceed 11 digits.');
        } else {
            profileSetErr('profilePhoneErr', '');
        }
    }

    function profileValidateEmail(input) {
        var val = input.value.trim();
        var ok  = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val);
        profileSetErr('profileEmailErr', val && !ok ? 'Enter a valid email address (e.g. admin@example.com).' : '');
    }

    function profileFormValid() {
        var ok = true;
        var phone = document.getElementById('profileForm').querySelector('[name="phone"]');
        if (phone && phone.value) {
            var digits = phone.value.replace(/[^0-9]/g, '');
            if (digits.length !== 11) {
                profileSetErr('profilePhoneErr', digits.length < 11
                    ? 'Phone number is too short (must be 11 digits).'
                    : 'Phone number must not exceed 11 digits.');
                ok = false;
            }
        }
        var email = document.getElementById('profileForm').querySelector('[name="email"]');
        if (email && email.value.trim()) {
            var validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim());
            if (!validEmail) {
                profileSetErr('profileEmailErr', 'Enter a valid email address (e.g. admin@example.com).');
                ok = false;
            }
        }
        if (!ok) {
            var firstErr = document.querySelector('#profileForm .profile-field[style*="dc2626"]');
            if (firstErr) firstErr.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
        return ok;
    }

    function enableEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            originalValues[f.name] = f.value;
            f.removeAttribute('disabled');
        });
        document.getElementById('profileActions').style.display = 'none';
        document.getElementById('profileEditActions').style.display = 'flex';
    }

    function cancelEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            f.value = originalValues[f.name] !== undefined ? originalValues[f.name] : f.value;
            f.setAttribute('disabled', '');
        });
        document.getElementById('profileActions').style.display = '';
        document.getElementById('profileEditActions').style.display = 'none';
    }

    var contactOriginalValues = {};

    function enableContactEdit() {
        document.querySelectorAll('#contactInfoForm .contact-field').forEach(function (f) {
            contactOriginalValues[f.name] = f.value;
            f.removeAttribute('disabled');
        });
        document.getElementById('contactInfoActions').style.display = 'none';
        document.getElementById('contactInfoEditActions').style.display = 'flex';
    }

    function cancelContactEdit() {
        document.querySelectorAll('#contactInfoForm .contact-field').forEach(function (f) {
            f.value = contactOriginalValues[f.name] !== undefined ? contactOriginalValues[f.name] : f.value;
            f.setAttribute('disabled', '');
        });
        document.getElementById('contactInfoActions').style.display = '';
        document.getElementById('contactInfoEditActions').style.display = 'none';
    }

    // --- Password requirements ---
    function checkPw() {
        var v = document.getElementById('newPassword').value;
        var c = document.getElementById('confirmPassword').value;
        setReq('req-len',   v.length >= 6);
        setReq('req-upper', /[A-Z]/.test(v));
        setReq('req-lower', /[a-z]/.test(v));
        setReq('req-num',   /[0-9]/.test(v));
        setReq('req-match', v.length > 0 && v === c);
    }
    function setReq(id, met) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('met', met);
        el.classList.toggle('fail', !met && (document.getElementById('newPassword').value.length > 0));
    }

    // --- Photo preview + upload ---
    document.getElementById('avatarInput').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('avatarDisplay').innerHTML =
                '<img src="' + e.target.result + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        };
        reader.readAsDataURL(file);
        document.getElementById('photoUploadForm').submit();
    });

    </script>

    <style>
        .profile-field-err {
            display: none;
            color: #dc2626;
            font-size: 11.5px;
            margin-top: 4px;
            line-height: 1.4;
        }
        .profile-field:disabled {
            background: var(--surface-2);
            color: var(--text-secondary);
            cursor: default;
            border-color: var(--border);
        }
        .contact-field:disabled {
            background: var(--surface-2);
            color: var(--text-secondary);
            cursor: default;
            border-color: var(--border);
        }
        select.profile-field {
            appearance: none; -webkit-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 12px center; padding-right: 34px;
        }
        select.profile-field:disabled { background-color: var(--surface-2); cursor: not-allowed; }
        .sel-loading { position: relative; }
        .sel-loading::after {
            content: ''; position: absolute; right: 34px; top: 50%; transform: translateY(-50%);
            width: 12px; height: 12px; border: 2px solid #e5e7eb; border-top-color: var(--dark);
            border-radius: 50%; animation: psgcSpin 0.7s linear infinite; pointer-events: none;
        }
        @keyframes psgcSpin { to { transform: translateY(-50%) rotate(360deg); } }
        #profileEditActions { display: none; gap: 8px; flex-wrap: wrap; }
        #contactInfoEditActions { display: none; gap: 8px; flex-wrap: wrap; }
        .pw-req {
            font-size: 11.5px; padding: 3px 9px; border-radius: 99px;
            border: 1px solid #e5e7eb; color: #9ca3af; background: #f9fafb;
            display: inline-flex; align-items: center; gap: 4px; transition: all 0.15s;
        }
        .pw-req.met  { background: #dcfce7; border-color: #86efac; color: #15803d; }
        .pw-req.fail { background: #fee2e2; border-color: #fca5a5; color: #dc2626; }

        /* Landing Page tables: keep row divider lines flush across all columns
           (table-cell action columns with flex children can throw off row height/border alignment) */
        #tab-landing .data-table td,
        #tab-landing .data-table th {
            border-bottom: 1px solid var(--border);
        }
        #tab-landing .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        #tab-landing .action-cell {
            display: table-cell;
            white-space: nowrap;
            vertical-align: middle;
        }
        #tab-landing .action-cell .action-btn,
        #tab-landing .action-cell form {
            display: inline-flex;
            vertical-align: middle;
            margin-right: 6px;
        }
        #tab-landing .action-cell .action-btn:last-child,
        #tab-landing .action-cell form:last-child {
            margin-right: 0;
        }
    </style>
</body>
</html>
