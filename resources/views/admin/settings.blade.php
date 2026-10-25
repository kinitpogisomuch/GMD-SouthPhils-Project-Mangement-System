<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | GMD South Phils</title>
    <link href="{{ asset('css/admin.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
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
            {{-- Saved (profile, password, landing page, portfolio, users…): the small dark toast below
                 the header (same as "Salary record saved."), shown for 3 seconds — see showToast() --}}
            <script>
                window.addEventListener('load', function () {
                    if (typeof showToast === 'function') showToast(@json(session('success')));
                });
            </script>
            @endif

            @if($errors->hasBag('tankCard'))
            <div class="alert-banner error">
                <i data-lucide="alert-circle"></i>
                @foreach($errors->getBag('tankCard')->all() as $error)
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
                                <span style="font-size:32px;font-weight:900;color:var(--dark);">
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
                        <form method="POST" action="{{ route('admin.settings.photo.remove') }}" id="removePhotoForm" style="display:none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        <div style="display:flex;gap:8px;">
                            <label class="avatar-change-btn" onclick="document.getElementById('avatarInput').click()" style="cursor:pointer;">
                                <i data-lucide="camera"></i>
                                Change Photo
                            </label>
                            @if(session('profile_photo'))
                            <button type="button" class="avatar-change-btn" onclick="confirmRemovePhoto()">
                                <i data-lucide="trash-2"></i>
                                Remove
                            </button>
                            @endif
                        </div>
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
                                Password must be at least 8 characters with an uppercase letter, lowercase letter, and number.
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
                                    <input type="password" name="current_password" id="currentPassword" placeholder="Enter current password" required>
                                    <button type="button" class="toggle-pw" data-target="currentPassword"><i data-lucide="eye"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password" id="newPassword" placeholder="Enter new password" required>
                                    <button type="button" class="toggle-pw" data-target="newPassword"><i data-lucide="eye"></i></button>
                                </div>
                                <div class="pw-req-row" style="display:flex;flex-wrap:wrap;gap:5px;margin-top:8px;">
                                    <span class="pw-req" id="req-len">Min 8 chars</span>
                                    <span class="pw-req" id="req-upper">Uppercase</span>
                                    <span class="pw-req" id="req-lower">Lowercase</span>
                                    <span class="pw-req" id="req-num">Number</span>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <div class="password-input-wrap">
                                    <input type="password" name="new_password_confirmation" id="confirmPassword" placeholder="Confirm new password" required>
                                    <button type="button" class="toggle-pw" data-target="confirmPassword"><i data-lucide="eye"></i></button>
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
                    <div class="table-toolbar" style="flex-wrap:wrap;gap:6px;">
                        <div>
                            <div style="font-weight:700;font-size:15px;">"Our Work" — Choose Your Tank, Get a Quotation</div>
                            <div style="font-size:12px;color:var(--muted);margin-top:2px;">The tank cards on the landing page and the client's quotation form. Each card opens its own details page, where clients choose a standard size and request a quotation. Click a card to change its picture, description, order or sizes; hide a tank you don't offer right now.</div>
                        </div>
                        <button class="add-btn" type="button" id="openAddTankCard">
                            <i data-lucide="plus"></i>
                            Add Tank Card
                        </button>
                    </div>
                    <div class="portfolio-grid" id="tankCardGrid">
                        @foreach($tankCards as $card)
                        <div class="portfolio-card {{ $card['visible'] ? '' : 'is-hidden-card' }}" title="Click to edit">
                            <div class="portfolio-card-image">
                                @if($card['image'])
                                    <img src="{{ $card['image'] }}" alt="">
                                @else
                                    <i data-lucide="{{ $card['icon'] }}"></i>
                                @endif
                                <span class="status-badge {{ $card['visible'] ? 'active' : 'archived' }} portfolio-card-status">
                                    {{ $card['visible'] ? 'Visible' : 'Hidden' }}
                                </span>
                            </div>
                            <div class="portfolio-card-body">
                                <div class="portfolio-card-title">{{ $card['type'] }}</div>
                                <div class="portfolio-card-chips">
                                    <span class="portfolio-chip"><i data-lucide="hash"></i>Order {{ $card['sort_order'] }}</span>
                                    {{-- what the details page offers: services (Fuel Pipeline), standard sizes, or a typed capacity --}}
                                    @if($card['choice'])
                                    <span class="portfolio-chip" title="{{ implode(' / ', array_column($card['choice']['options'], 'value')) }}"><i data-lucide="list-checks"></i>{{ count($card['choice']['options']) }} services</span>
                                    @endif
                                    @if(count($card['sizes']))
                                    <span class="portfolio-chip" title="{{ implode(', ', array_column($card['sizes'], 'capacity')) }}"><i data-lucide="ruler"></i>{{ count($card['sizes']) }} size{{ count($card['sizes']) !== 1 ? 's' : '' }}</span>
                                    @elseif($card['project_based'])
                                    <span class="portfolio-chip"><i data-lucide="ruler"></i>Per project</span>
                                    @else
                                    <span class="portfolio-chip"><i data-lucide="ruler"></i>Any size</span>
                                    @endif
                                </div>
                                <p class="portfolio-card-desc">{{ Str::limit($card['blurb'], 90) }}</p>
                            </div>
                            <div class="portfolio-card-actions">
                                <button class="action-btn view edit-tank-card-btn" type="button"
                                        data-url="{{ route('admin.tank_cards.update', $card['slug']) }}"
                                        data-type="{{ $card['type'] }}"
                                        data-image="{{ $card['image'] }}"
                                        data-custom-image="{{ $card['custom_image'] ? '1' : '0' }}"
                                        data-blurb="{{ $card['blurb'] === $card['default_blurb'] ? '' : $card['blurb'] }}"
                                        data-default-blurb="{{ $card['default_blurb'] }}"
                                        data-order="{{ $card['sort_order'] }}"
                                        data-custom="{{ $card['custom'] ? '1' : '0' }}"
                                        data-capacity-label="{{ $card['capacity_label'] }}"
                                        data-sizes="{{ json_encode($card['sizes']) }}"
                                        data-default-sizes="{{ json_encode($card['default_sizes']) }}"
                                        data-sizes-edited="{{ $card['sizes_edited'] ? '1' : '0' }}"
                                        data-capacity-name="{{ $card['capacity']['label'] }}"
                                        data-project-based="{{ $card['project_based'] ? '1' : '0' }}"
                                        title="Edit" aria-label="Edit {{ $card['type'] }}">
                                    <i data-lucide="pencil"></i>
                                </button>
                                @if($card['visible'])
                                <a class="action-btn view" href="{{ route('tanks.show', $card['slug']) }}" target="_blank" rel="noopener"
                                   title="View its details page" aria-label="View the {{ $card['type'] }} details page">
                                    <i data-lucide="external-link"></i>
                                </a>
                                @endif
                                <button class="action-btn view toggle-visibility-btn" type="button"
                                        data-archive-url="{{ route('admin.tank_cards.toggle', $card['slug']) }}"
                                        data-hidden="{{ $card['visible'] ? '0' : '1' }}"
                                        data-name="{{ $card['type'] }}"
                                        title="{{ $card['visible'] ? 'Hide from landing page' : 'Show on landing page' }}"
                                        aria-label="{{ $card['visible'] ? 'Hide' : 'Show' }} {{ $card['type'] }}">
                                    <i data-lucide="{{ $card['visible'] ? 'eye-off' : 'eye' }}"></i>
                                </button>
                                @if($card['custom'])
                                <button class="action-btn view delete-item-btn" type="button"
                                        data-delete-url="{{ route('admin.tank_cards.destroy', $card['slug']) }}"
                                        data-name="{{ $card['type'] }}"
                                        title="Delete" aria-label="Delete {{ $card['type'] }}">
                                    <i data-lucide="trash-2"></i>
                                </button>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <style>.portfolio-card.is-hidden-card { opacity: .55; }</style>
                </div>

                <div class="table-card" style="margin-top:24px;">
                    <div class="table-toolbar">
                        <span style="font-weight:700;font-size:15px;">Client Reviews</span>
                        <span style="font-size:12px;font-weight:700;color:var(--muted);">{{ $reviews->count() }} review{{ $reviews->count() !== 1 ? 's' : '' }}</span>
                    </div>
                    @if($reviews->isEmpty())
                    <div style="text-align:center;padding:40px;color:var(--muted);">
                        No client reviews yet.
                    </div>
                    @else
                    <div class="review-grid">
                        @foreach($reviews as $review)
                        <div class="review-card">
                            <div class="review-card-top">
                                <div style="min-width:0;">
                                    <div class="review-card-project">{{ $review->project->name ?? 'N/A' }}</div>
                                    <div class="review-card-client">
                                        {{ $review->client_name }}
                                        @if($review->is_anonymous)
                                        <span title="This client chose to stay anonymous publicly — shown as &quot;{{ $review->masked_client_name }}&quot; on the site" class="review-hidden-badge">ANONYMOUS</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="status-badge {{ $review->status === 'active' ? 'active' : 'archived' }}">
                                    {{ $review->status === 'active' ? 'Visible' : 'Hidden' }}
                                </span>
                            </div>
                            <div class="review-card-stars">
                                @for($i=1;$i<=5;$i++)
                                    <i data-lucide="star" style="color:{{ $i <= $review->rating ? 'var(--accent)' : 'var(--border)' }};{{ $i <= $review->rating ? 'fill:var(--accent);' : '' }}"></i>
                                @endfor
                            </div>
                            <p class="review-card-comment">"{{ $review->comment }}"</p>
                            <div class="review-card-actions">
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
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>

            <!-- ===== TAB: KPI TARGETS ===== -->
        </main>
    </div>

    <!-- ===== AVATAR CROP MODAL ===== -->
    <div class="modal-overlay" id="avatarCropModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Adjust Photo</h2>
                    <p>Drag to reposition, scroll or pinch to zoom.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeAvatarCropModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="avatar-crop-wrap">
                <img id="avatarCropImage" src="" alt="Crop preview">
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeAvatarCropModal()">Cancel</button>
                <button type="button" class="save-btn" onclick="saveAvatarCrop()">
                    <i data-lucide="check"></i> Save Photo
                </button>
            </div>
        </div>
    </div>

    <!-- ===== REMOVE PHOTO CONFIRM MODAL ===== -->
    <div class="modal-overlay" id="removePhotoModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Remove Profile Photo?</h2>
                    <p>This will clear your current photo.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeRemovePhotoModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="trash-2"></i></div>
                <p>Are you sure you want to remove your profile photo?</p>
                <p style="font-size:13px;color:var(--text-secondary);margin-top:8px;line-height:1.5;">
                    You can upload a new one anytime.
                </p>
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeRemovePhotoModal()">Cancel</button>
                <button type="button" class="save-btn" style="background:#dc2626;" onclick="document.getElementById('removePhotoForm').submit();">
                    <i data-lucide="trash-2"></i> Remove
                </button>
            </div>
        </div>
    </div>

    <!-- ===== ADD TANK CARD MODAL ===== -->
    <div class="modal-overlay" id="addTankCardModal">
        <div class="modal-card" style="max-width:600px;max-height:90vh;overflow-y:auto;">
            <div class="modal-header">
                <div>
                    <h2>Add Tank Card</h2>
                    <p>Adds a tank to "Our Work" on the landing page, with its own details page where clients pick one of your standard sizes (or type their own) and request a quotation: capacity, quantity, their requirements and optional dimensions. It is added at the end — change its order later with Edit.</p>
                </div>
                <button class="modal-close" type="button" id="closeAddTankCard" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" action="{{ route('admin.tank_cards.store') }}" enctype="multipart/form-data" id="addTankCardForm">
                @csrf

                @if($errors->hasBag('tankCardAdd'))
                <div class="alert-banner error" style="margin-bottom:14px;">
                    <i data-lucide="alert-circle"></i>
                    <div>
                        @foreach($errors->getBag('tankCardAdd')->all() as $error)<div>{{ $error }}</div>@endforeach
                        <div style="font-size:12px;margin-top:4px;">Please choose the picture again — browsers don't keep a picked file after the page reloads.</div>
                    </div>
                </div>
                @endif

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="addTankName">Tank Name <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="type" id="addTankName" required maxlength="100" value="{{ old('type') }}" placeholder="e.g. Septic Tank">
                </div>

                <div class="form-group form-group-full" style="margin-bottom:14px;">
                    <label for="addTankImage">Picture <span style="color:#dc2626;">*</span> <span style="font-weight:400;color:var(--muted);">(up to 10MB)</span></label>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <img id="addTankImagePreview" src="" alt=""
                             style="display:none;width:96px;height:72px;object-fit:cover;border-radius:10px;border:1px solid var(--border);flex-shrink:0;">
                        <input type="file" name="image" id="addTankImage" accept="image/*" required style="flex:1;min-width:0;">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="addTankCapacity">Capacity Label <span style="font-weight:400;color:var(--muted);">— what the capacity is called, e.g. Tank Volume</span></label>
                    <input type="text" name="capacity_label" id="addTankCapacity" maxlength="50" value="{{ old('capacity_label') }}" placeholder="Capacity / Size">
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="addTankBlurb">Description <span style="color:#dc2626;">*</span></label>
                    <textarea name="blurb" id="addTankBlurb" rows="3" required maxlength="500" style="resize:none;" placeholder="One or two sentences shown on the card">{{ old('blurb') }}</textarea>
                </div>

                {{-- same editor as Edit Tank Card; a new card always saves its own list (empty = clients type the capacity) --}}
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Standard Sizes <span style="font-weight:400;color:var(--muted);">— optional; shown on the details page</span></label>
                    <div class="ts-head" id="addTankSizesHead"><span>Capacity</span><span>Dimensions</span><span>Est. Price (₱)</span><span></span></div>
                    <div id="addTankSizesRows"></div>
                    <div id="addTankSizesEmpty" class="ts-empty">No standard sizes — clients type the capacity in the form.</div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                        <button type="button" class="cancel-btn ts-btn" id="addTankSizeAdd"><i data-lucide="plus"></i> Add size</button>
                    </div>
                </div>

                <div class="modal-actions" style="margin-top:20px;">
                    <button type="button" class="cancel-btn" id="cancelAddTankCard">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="check-circle" style="width:15px;height:15px;"></i>
                        Add Tank Card
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===== EDIT TANK CARD MODAL ===== -->
    <div class="modal-overlay" id="editTankCardModal">
        <div class="modal-card" style="max-width:600px;">
            <div class="modal-header">
                <div>
                    <h2 id="editTankCardTitle">Edit Tank Card</h2>
                    <p>How this tank appears under "Our Work" on the landing page and on the client's quotation form. Leave a field blank to use the default.</p>
                </div>
                <button class="modal-close" type="button" id="closeEditTankCard" aria-label="Close">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <form method="POST" id="editTankCardForm" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="reset_image" id="tankCardResetImage" value="0">

                {{-- only for cards the admin added: their own name and capacity label --}}
                <div id="tankCardCustomFields" class="tank-card-grid" style="display:none;grid-template-columns:2fr 1fr;gap:14px;margin-bottom:14px;">
                    <div class="form-group">
                        <label for="tankCardName">Tank Name</label>
                        <input type="text" name="type" id="tankCardName" maxlength="100" disabled>
                    </div>
                    <div class="form-group">
                        <label for="tankCardCapacityLabel">Capacity Label</label>
                        <input type="text" name="capacity_label" id="tankCardCapacityLabel" maxlength="50" placeholder="Capacity / Size" disabled>
                    </div>
                </div>

                <div class="form-group form-group-full" style="margin-bottom:14px;">
                    <label for="tankCardImageInput">Picture <span style="font-weight:400;color:var(--muted);">(up to 10MB — choosing a file replaces the current one)</span></label>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;">
                        <img id="tankCardImagePreview" src="" alt=""
                             style="display:none;width:96px;height:72px;object-fit:cover;border-radius:10px;border:1px solid var(--border);flex-shrink:0;">
                        <input type="file" name="image" id="tankCardImageInput" accept="image/*" style="flex:1;min-width:0;">
                    </div>
                    <button type="button" class="cancel-btn" id="tankCardUseDefault" style="margin-top:10px;display:none;">Use default picture</button>
                </div>

                <div class="form-group" style="margin-bottom:14px;max-width:220px;">
                    <label for="tankCardOrder">Order <span style="font-weight:400;color:var(--muted);">— position on the page</span></label>
                    <input type="number" name="sort_order" id="tankCardOrder" min="1" max="99">
                </div>

                <div class="form-group" style="margin-bottom:14px;">
                    <label for="tankCardBlurb">Description</label>
                    <textarea name="blurb" id="tankCardBlurb" rows="3" maxlength="500" style="resize:none;"></textarea>
                </div>

                {{-- the standard sizes clients pick from on the tank's details page (and as quick picks in the form) --}}
                <div class="form-group" style="margin-bottom:14px;">
                    <label>Standard Sizes <span style="font-weight:400;color:var(--muted);">— shown on the details page; clients can still type their own</span></label>
                    <input type="hidden" name="sizes_mode" id="tankSizesMode" value="default">
                    <div class="ts-head" id="tankSizesHead"><span id="tankSizesCapName">Capacity</span><span>Dimensions</span><span>Est. Price (₱)</span><span></span></div>
                    <div id="tankSizesRows"></div>
                    <div id="tankSizesEmpty" class="ts-empty">No standard sizes — clients type the capacity in the form.</div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;">
                        <button type="button" class="cancel-btn ts-btn" id="tankSizeAdd"><i data-lucide="plus"></i> Add size</button>
                        <button type="button" class="cancel-btn ts-btn" id="tankSizesReset"><i data-lucide="rotate-ccw"></i> Use default sizes</button>
                    </div>
                    <div id="tankSizesNote" class="ts-note"></div>
                </div>

                <div class="modal-actions" style="margin-top:20px;">
                    <button type="button" class="cancel-btn" id="cancelEditTankCard">Cancel</button>
                    <button type="submit" class="save-btn">
                        <i data-lucide="save" style="width:15px;height:15px;"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <style>
        @media (max-width: 560px) { .tank-card-grid { grid-template-columns: 1fr !important; } }
        /* Edit Tank Card → Standard Sizes rows */
        .ts-head, .ts-row { display: grid; grid-template-columns: 1fr 1.3fr 0.9fr 36px; gap: 8px; align-items: center; }
        .ts-head { font-size: 11px; font-weight: 800; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin: 4px 0 6px; }
        .ts-row { margin-bottom: 8px; }
        .ts-row input { min-width: 0; }
        .ts-row .action-btn { width: 36px; height: 36px; }
        .ts-empty { font-size: 12.5px; color: var(--muted); padding: 12px 14px; border: 1px dashed var(--border); border-radius: 10px; }
        .ts-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 12.5px; }
        .ts-btn svg, .ts-btn i { width: 14px; height: 14px; }
        .ts-note { font-size: 11.5px; color: var(--muted); margin-top: 8px; line-height: 1.5; }
        .ts-note:empty { display: none; }
    </style>

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

    <script>const ACTIVE_TAB = "{{ ($errors->hasBag('tankCardAdd') || $errors->hasBag('tankCard')) ? 'landing' : session('active_tab', request('tab', 'profile')) }}";</script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
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

        // ---- Edit Tank Card Modal ("Our Work" cards) ----
        document.querySelectorAll('.edit-tank-card-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var d = this.dataset;
                document.getElementById('editTankCardForm').action = d.url;
                document.getElementById('editTankCardTitle').textContent = d.type;
                var blurb = document.getElementById('tankCardBlurb');
                blurb.value = d.blurb || '';
                blurb.placeholder = d.defaultBlurb || '';
                document.getElementById('tankCardOrder').value = d.order || '';
                document.getElementById('tankCardImageInput').value = '';
                document.getElementById('tankCardResetImage').value = '0';
                var preview = document.getElementById('tankCardImagePreview');
                preview.src = d.image || '';
                preview.style.display = d.image ? '' : 'none';

                // an added card: its name and capacity label are editable, and it has no default picture
                var isCustom = d.custom === '1';
                var customFields = document.getElementById('tankCardCustomFields');
                customFields.style.display = isCustom ? 'grid' : 'none';
                customFields.querySelectorAll('input').forEach(function (el) { el.disabled = !isCustom; });
                document.getElementById('tankCardName').value = isCustom ? d.type : '';
                document.getElementById('tankCardName').required = isCustom;
                document.getElementById('tankCardCapacityLabel').value = d.capacityLabel || '';
                blurb.required = isCustom;

                var useDefault = document.getElementById('tankCardUseDefault');
                useDefault.disabled = false;
                useDefault.textContent = 'Use default picture';
                useDefault.style.display = !isCustom && d.customImage === '1' ? '' : 'none';

                // standard sizes: an added card always saves its own list; a built-in one keeps the defaults until edited
                tankSizes.defaults = JSON.parse(d.defaultSizes || '[]');
                tankSizes.custom   = isCustom;
                document.getElementById('tankSizesCapName').textContent = d.capacityName || 'Capacity';
                document.getElementById('tankSizesReset').style.display = isCustom ? 'none' : '';
                document.getElementById('tankSizesNote').textContent = d.projectBased === '1'
                    ? 'This one is priced per project, so it usually has no standard sizes.' : '';
                tankSizes.render(JSON.parse(d.sizes || '[]'), isCustom || d.sizesEdited === '1' ? 'custom' : 'default');
                openModal('editTankCardModal');
            });
        });

        // ---- Standard Sizes editor (Edit Tank Card: ids "tankSizes…", Add Tank Card: "addTankSizes…") ----
        function makeSizesEditor(p) {
          var ed = {
            defaults: [], custom: false,
            rows: function () { return document.getElementById(p + 'SizesRows'); },
            // Edit only: "default" keeps the built-in list, "custom" saves the rows (Add has no mode — always its own list)
            setMode: function (mode) {
                var input = document.getElementById(p + 'SizesMode');
                var reset = document.getElementById(p + 'SizesReset');
                if (!input || !reset) return;
                input.value = mode;
                reset.disabled = mode === 'default';
                reset.innerHTML = '<i data-lucide="rotate-ccw"></i> ' + (mode === 'default' ? 'Using default sizes' : 'Use default sizes');
                if (window.lucide) lucide.createIcons();
            },
            row: function (size) {
                var row = document.createElement('div');
                row.className = 'ts-row';
                row.innerHTML = '<input type="text" maxlength="60" data-f="capacity" placeholder="e.g. 10,000 liters">' +
                    '<input type="text" maxlength="80" data-f="dimensions" placeholder="e.g. 2.4 m Ø × 2.4 m L (optional)">' +
                    '<input type="text" maxlength="20" data-f="price" inputmode="decimal" placeholder="e.g. 210,000" title="GMD\'s estimated price for one tank of this size — the costing compares with it">' +
                    '<button type="button" class="action-btn" title="Remove this size" aria-label="Remove this size" style="color:#dc2626;"><i data-lucide="x"></i></button>';
                row.querySelector('[data-f=capacity]').value   = size.capacity || '';
                row.querySelector('[data-f=dimensions]').value = size.dimensions || '';
                row.querySelector('[data-f=price]').value      = size.price ? Number(size.price).toLocaleString('en-PH') : '';
                row.querySelector('button').addEventListener('click', function () { row.remove(); ed.changed(); });
                row.querySelectorAll('input').forEach(function (el) { el.addEventListener('input', function () { ed.changed(); }); });
                return row;
            },
            // field names follow the row order, so removing a row leaves no gaps
            reindex: function () {
                var rows = this.rows().querySelectorAll('.ts-row');
                rows.forEach(function (row, i) {
                    row.querySelector('[data-f=capacity]').name   = 'sizes[' + i + '][capacity]';
                    row.querySelector('[data-f=dimensions]').name = 'sizes[' + i + '][dimensions]';
                    row.querySelector('[data-f=price]').name      = 'sizes[' + i + '][price]';
                });
                document.getElementById(p + 'SizesEmpty').style.display = rows.length ? 'none' : '';
                document.getElementById(p + 'SizesHead').style.display  = rows.length ? '' : 'none';
                document.getElementById(p + 'SizeAdd').disabled = rows.length >= 12;
            },
            render: function (sizes, mode) {
                var box = this.rows();
                box.innerHTML = '';
                sizes.forEach(function (s) { box.appendChild(ed.row(s)); });
                this.reindex();
                this.setMode(mode);
                if (window.lucide) lucide.createIcons();
            },
            changed: function () { this.reindex(); this.setMode('custom'); }
          };
          document.getElementById(p + 'SizeAdd').addEventListener('click', function () {
              var row = ed.row({});
              ed.rows().appendChild(row);
              ed.changed();
              if (window.lucide) lucide.createIcons();
              row.querySelector('input').focus();
          });
          var reset = document.getElementById(p + 'SizesReset');
          if (reset) reset.addEventListener('click', function () { ed.render(ed.defaults, 'default'); });
          return ed;
        }
        var tankSizes    = makeSizesEditor('tank');
        var addTankSizes = makeSizesEditor('addTank');
        // a failed add comes back with the sizes that were typed
        addTankSizes.render({!! json_encode($errors->hasBag('tankCardAdd') ? array_values((array) old('sizes', [])) : [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}, 'custom');

        // ---- Add Tank Card Modal ----
        document.getElementById('openAddTankCard').addEventListener('click', function () { openModal('addTankCardModal'); });
        document.getElementById('closeAddTankCard').addEventListener('click', function () { closeModal('addTankCardModal'); });
        document.getElementById('cancelAddTankCard').addEventListener('click', function () { closeModal('addTankCardModal'); });
        document.getElementById('addTankImage').addEventListener('change', function () {
            var file = this.files && this.files[0];
            var preview = document.getElementById('addTankImagePreview');
            if (!file || (typeof validateFileSize === 'function' && !validateFileSize(this, 10))) { preview.style.display = 'none'; return; }
            var reader = new FileReader();
            reader.onload = function (e) { preview.src = e.target.result; preview.style.display = ''; };
            reader.readAsDataURL(file);
        });
        @if($errors->hasBag('tankCardAdd'))
        openModal('addTankCardModal');   // a failed add comes back with its errors
        @endif
        document.getElementById('closeEditTankCard').addEventListener('click', function () { closeModal('editTankCardModal'); });
        document.getElementById('cancelEditTankCard').addEventListener('click', function () { closeModal('editTankCardModal'); });
        document.getElementById('tankCardUseDefault').addEventListener('click', function () {
            document.getElementById('tankCardResetImage').value = '1';
            document.getElementById('tankCardImageInput').value = '';
            document.getElementById('tankCardImagePreview').style.display = 'none';
            this.textContent = 'Default picture will be used';
            this.disabled = true;
        });
        document.getElementById('tankCardImageInput').addEventListener('change', function () {
            var file = this.files && this.files[0];
            if (!file) return;
            if (typeof validateFileSize === 'function' && !validateFileSize(this, 10)) return;
            document.getElementById('tankCardResetImage').value = '0';
            var preview = document.getElementById('tankCardImagePreview');
            var reader = new FileReader();
            reader.onload = function (e) { preview.src = e.target.result; preview.style.display = ''; };
            reader.readAsDataURL(file);
        });
        // clicking anywhere on a card opens Edit — the action buttons opt out
        document.querySelectorAll('#tankCardGrid .portfolio-card').forEach(function (card) {
            var editBtn = card.querySelector('.edit-tank-card-btn');
            card.addEventListener('click', function (e) {
                if (e.target.closest('.action-btn')) return;
                editBtn.click();
            });
        });
        // ---- Toggle Visibility (Hide/Show) Modal — shared by Tank Cards & Reviews ----
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

        // ---- Delete Item Modal — shared by Tank Cards & Reviews ----
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

        // ---- Overlay click to close ----
        document.querySelectorAll('.modal-overlay').forEach(function (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === this) closeModal(this.id);
            });
        });
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
        setReq('req-len',   v.length >= 8);
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

    // Password form — a red outline on whichever field is blank instead of letting the
    // page reload just to show the server's "field is required" messages.
    (function () {
        var form = document.getElementById('passwordForm');
        if (!form) return;
        var fields = ['currentPassword', 'newPassword', 'confirmPassword'].map(function (id) {
            return document.getElementById(id);
        });
        fields.forEach(function (input) {
            input.addEventListener('input', function () { input.classList.remove('is-invalid'); });
        });
        form.addEventListener('submit', function (e) {
            var invalid = false;
            fields.forEach(function (input) {
                var blank = !input.value.trim();
                input.classList.toggle('is-invalid', blank);
                if (blank) invalid = true;
            });
            if (invalid) {
                e.preventDefault();
                fields.find(function (input) { return input.classList.contains('is-invalid'); }).focus();
            }
        });
    })();

    // --- Photo: open crop modal instead of uploading immediately ---
    document.getElementById('avatarInput').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;
        if (!validateFileSize(this, 4)) { this.value = ''; return; }
        avatarPendingFile = file;
        var reader = new FileReader();
        reader.onload = function (e) {
            document.getElementById('avatarCropImage').src = e.target.result;
            document.getElementById('avatarCropModal').classList.add('show');
            if (avatarCropper) { avatarCropper.destroy(); avatarCropper = null; }
            avatarCropper = new Cropper(document.getElementById('avatarCropImage'), {
                aspectRatio: 1,
                viewMode: 1,
                dragMode: 'move',
                background: false,
                autoCropArea: 1,
                cropBoxResizable: false,
                cropBoxMovable: false,
                toggleDragModeOnDblclick: false,
            });
        };
        reader.readAsDataURL(file);
    });

    function confirmRemovePhoto() {
        document.getElementById('removePhotoModal').classList.add('show');
    }
    function closeRemovePhotoModal() {
        document.getElementById('removePhotoModal').classList.remove('show');
    }

    var avatarCropper = null;
    var avatarPendingFile = null;

    function closeAvatarCropModal() {
        document.getElementById('avatarCropModal').classList.remove('show');
        if (avatarCropper) { avatarCropper.destroy(); avatarCropper = null; }
        document.getElementById('avatarInput').value = '';
        avatarPendingFile = null;
    }

    function saveAvatarCrop() {
        if (!avatarCropper) return;
        var outputType = (avatarPendingFile && avatarPendingFile.type === 'image/png') ? 'image/png' : 'image/jpeg';
        avatarCropper.getCroppedCanvas({ width: 400, height: 400 }).toBlob(function (blob) {
            if (!blob) return;
            var fileName    = (avatarPendingFile && avatarPendingFile.name) || 'avatar.jpg';
            var croppedFile = new File([blob], fileName, { type: blob.type });
            var dt          = new DataTransfer();
            dt.items.add(croppedFile);
            document.getElementById('avatarInput').files = dt.files;

            document.getElementById('avatarDisplay').innerHTML =
                '<img src="' + URL.createObjectURL(blob) + '" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';

            document.getElementById('avatarCropModal').classList.remove('show');
            avatarCropper.destroy();
            avatarCropper = null;
            document.getElementById('photoUploadForm').submit();
        }, outputType, 0.92);
    }

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

        /* Phones: the 4 password rule pills stay on one line, shrinking to fit */
        @media (max-width: 640px) {
            .pw-req-row { flex-wrap: nowrap !important; gap: 4px !important; }
            .pw-req-row .pw-req { flex: 1 1 auto; justify-content: center; white-space: nowrap;
                                  font-size: clamp(9px, 2.6vw, 11.5px); padding: 3px 5px; }
        }

        /* A blank required password field gets a red outline instead of blocking on the
           server-side message list. */
        #passwordForm input.is-invalid {
            border-color: #dc2626 !important;
        }

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

        /* "Our Work" tank cards — a card grid instead of a dense table, since this is
           visual content (a photo per row) that a table format under-serves. */
        .portfolio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            padding: 20px 24px 24px;
        }
        @media (max-width: 1000px) {
            .portfolio-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 620px) {
            .portfolio-grid { grid-template-columns: 1fr; }
        }
        .portfolio-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            background: var(--white);
            cursor: pointer;
            transition: 0.18s ease;
            display: flex;
            flex-direction: column;
        }
        .portfolio-card:hover {
            border-color: var(--dark);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        .portfolio-card-image {
            position: relative;
            height: 150px;
            background: var(--cream-soft);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portfolio-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .portfolio-card-image i,
        .portfolio-card-image svg {
            width: 30px;
            height: 30px;
            color: var(--muted);
            opacity: 0.5;
        }
        .portfolio-card-status {
            position: absolute;
            top: 10px;
            right: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.18);
        }
        .portfolio-card-body {
            padding: 14px 16px 4px;
            flex: 1;
        }
        .portfolio-card-title {
            font-size: 14.5px;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 8px;
        }
        .portfolio-card-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 10px;
        }
        .portfolio-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            font-weight: 700;
            color: var(--muted);
            background: var(--cream-soft);
            border: 1px solid var(--border);
            border-radius: 999px;
            padding: 3px 9px;
        }
        .portfolio-chip i,
        .portfolio-chip svg {
            width: 11px;
            height: 11px;
        }
        .portfolio-card-desc {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
            margin: 0;
        }
        .portfolio-card-actions {
            display: flex;
            gap: 6px;
            padding: 12px 16px;
            border-top: 1px solid var(--border);
            margin-top: 12px;
        }

        /* Client Reviews — a card grid instead of a dense table; a testimonial reads
           more naturally as a quote card than as six squeezed table columns. */
        .review-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            padding: 20px 24px 24px;
        }
        @media (max-width: 1000px) {
            .review-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 620px) {
            .review-grid { grid-template-columns: 1fr; }
        }
        .review-card {
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 16px;
            display: flex;
            flex-direction: column;
        }
        .review-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }
        .review-card-project {
            font-size: 14px;
            font-weight: 800;
            color: var(--dark);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .review-card-client {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            margin-top: 2px;
        }
        .review-hidden-badge {
            font-size: 10px;
            font-weight: 800;
            color: #6d28d9;
            background: #ede9fe;
            border-radius: 999px;
            padding: 2px 7px;
            flex-shrink: 0;
        }
        .review-card-stars {
            display: flex;
            gap: 2px;
            margin-bottom: 10px;
        }
        .review-card-stars i,
        .review-card-stars svg {
            width: 14px;
            height: 14px;
        }
        .review-card-comment {
            font-size: 13px;
            color: var(--dark);
            line-height: 1.55;
            font-style: italic;
            margin: 0 0 14px;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 4;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .review-card-actions {
            display: flex;
            gap: 6px;
            padding-top: 12px;
            border-top: 1px solid var(--border);
        }
    </style>
    {{-- ===================== CONFIRM PASSWORD UPDATE ===================== --}}
    <div class="modal-overlay" id="confirmPasswordModal">
        <div class="modal-card" style="max-width:440px;">
            <div class="modal-header">
                <div>
                    <h2>Update Password?</h2>
                    <p>Your current password will stop working.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeConfirmPasswordModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon" style="background:#fef3c7;color:#b45309;"><i data-lucide="key-round"></i></div>
                <p>Are you sure you want to update your password?</p>
                <div style="margin-top:8px;font-size:13px;color:var(--muted);line-height:1.6;padding:0 12px;">
                    From now on, sign in with your new password.
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeConfirmPasswordModal()">Cancel</button>
                <button type="button" class="save-btn" id="confirmPasswordBtn">
                    <i data-lucide="lock"></i> Yes, Update Password
                </button>
            </div>
        </div>
    </div>
    <script>
        // Asks before the password is changed — the modal appears as soon as Update Password is
        // clicked; the usual field checks run after the admin/employee/client answers "Yes".
        function closeConfirmPasswordModal() {
            document.getElementById('confirmPasswordModal').classList.remove('show');
            document.body.style.overflow = '';
        }
        document.addEventListener('DOMContentLoaded', function () {
            var form  = document.getElementById('passwordForm');
            var modal = document.getElementById('confirmPasswordModal');
            if (!form || !modal) return;
            var confirmed = false;
            // Capture phase, so this runs before the page's own checks: the question comes first.
            form.addEventListener('submit', function (e) {
                if (confirmed) return;           // answered "Yes" — let the normal checks and submit run
                e.preventDefault();
                e.stopImmediatePropagation();
                modal.classList.add('show');
                document.body.style.overflow = 'hidden';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            }, true);
            document.getElementById('confirmPasswordBtn').addEventListener('click', function () {
                closeConfirmPasswordModal();
                confirmed = true;
                form.requestSubmit();            // runs the blank-field checks; if one fails, the fields turn red
                confirmed = false;
            });
            modal.addEventListener('click', function (e) { if (e.target === modal) closeConfirmPasswordModal(); });

            // Clear (a reset button) empties the fields but not the requirement pills — refresh them too
            form.addEventListener('reset', function () {
                setTimeout(function () {
                    form.querySelectorAll('input').forEach(function (input) {
                        input.classList.remove('is-invalid');
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                }, 0);
            });
        });
    </script>
</body>
</html>
