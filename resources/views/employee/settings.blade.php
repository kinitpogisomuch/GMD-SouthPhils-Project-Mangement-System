<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/gmdlogo-circle.svg') }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | GMD South Phils</title>
    <link href="{{ asset('css/employee.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
</head>
<body class="page-enter">

    @include('partials.employee.header')

    <main class="admin-content">

            <div class="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Manage your profile and account security.</p>
                </div>
            </div>


            <!-- Settings Tabs -->
            <div class="emp-tabs">
                <button class="emp-tab active" data-tab="profile">
                    <i data-lucide="user"></i>
                    My Profile
                </button>
                <button class="emp-tab" data-tab="password">
                    <i data-lucide="lock"></i>
                    Security
                </button>
            </div>

            <!-- ===== TAB: PROFILE ===== -->
            <div class="emp-tab-content active" id="tab-profile">
                <div class="settings-layout" style="align-items:stretch;">

                    <!-- Left column: Avatar Card + GCash QR, stacked so the QR doesn't take up the
                         full page width — it only needs to be as wide as the photo card beside it. -->
                    <div class="settings-side-col" style="display:flex;flex-direction:column;gap:10px;width:260px;flex-shrink:0;">
                    <div class="settings-avatar-card" style="flex:1;justify-content:center;">
                        <div class="settings-avatar" id="avatarDisplay">
                            @if(session('profile_photo'))
                                <img src="{{ session('profile_photo') }}" alt="Profile"
                                     style="width:100%;height:100%;object-fit:cover;">
                            @else
                                <span style="font-size:32px;font-weight:900;color:var(--dark);">
                                    {{ strtoupper(substr($employee->first_name ?: $employee->last_name, 0, 1)) }}
                                </span>
                            @endif
                        </div>
                        <div class="settings-avatar-name">{{ $employee->full_name }}</div>
                        <div class="settings-avatar-role">Employee</div>

                        <form method="POST" action="{{ route('employee.settings.photo') }}"
                              enctype="multipart/form-data" id="photoUploadForm">
                            @csrf
                            <input type="file" name="profile_photo" id="avatarInput"
                                   accept="image/jpeg,image/png,image/webp" style="display:none;">
                        </form>
                        <form method="POST" action="{{ route('employee.settings.photo.remove') }}" id="removePhotoForm" style="display:none;">
                            @csrf
                            @method('DELETE')
                        </form>
                        <div style="display:flex;gap:6px;">
                            <label class="avatar-change-btn" onclick="document.getElementById('avatarInput').click()"
                                   style="cursor:pointer;{{ session('profile_photo') ? 'padding:9px 10px;font-size:12px;flex:1;justify-content:center;' : '' }}">
                                <i data-lucide="camera"></i>
                                Change Photo
                            </label>
                            @if(session('profile_photo'))
                            <button type="button" class="avatar-change-btn" onclick="confirmRemovePhoto()" style="padding:9px 10px;font-size:12px;flex:1;justify-content:center;">
                                <i data-lucide="trash-2"></i>
                                Remove
                            </button>
                            @endif
                        </div>
                    </div>

                    <!-- GCash QR Code Card — stacked below the photo card, not spanning the full page -->
                    <div class="pv-card" style="flex:1;display:flex;flex-direction:column;">
                        <h3 class="pv-card-title" style="margin-bottom:4px;">GCash QR Code</h3>
                        <p style="font-size:12.5px;color:var(--muted);margin:0 0 16px;">
                            So admin can pay your salary via GCash.
                        </p>

                        <div style="display:flex;flex-direction:column;align-items:center;gap:14px;">
                            <div id="gcashQrDisplay" style="width:140px;height:140px;border-radius:12px;border:1.5px dashed var(--border);display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0;background:var(--surface-2);">
                                @if($employee->gcash_qr)
                                    <img src="{{ $employee->gcash_qr }}" alt="GCash QR" style="width:100%;height:100%;object-fit:contain;">
                                @else
                                    <i data-lucide="qr-code" style="width:32px;height:32px;color:var(--muted);opacity:.5;"></i>
                                @endif
                            </div>

                            <div style="width:100%;text-align:center;">
                                <form method="POST" action="{{ route('employee.settings.gcash_qr') }}"
                                      enctype="multipart/form-data" id="gcashQrUploadForm">
                                    @csrf
                                    <input type="file" name="gcash_qr" id="gcashQrInput"
                                           accept="image/jpeg,image/png,image/webp" style="display:none;">
                                </form>
                                <form method="POST" action="{{ route('employee.settings.gcash_qr.remove') }}" id="removeGcashQrForm" style="display:none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                                <div style="display:flex;gap:6px;justify-content:center;flex-wrap:nowrap;">
                                    <label class="avatar-change-btn" onclick="document.getElementById('gcashQrInput').click()" style="cursor:pointer;padding:9px 10px;font-size:12px;flex:1;justify-content:center;">
                                        <i data-lucide="upload"></i>
                                        {{ $employee->gcash_qr ? 'Change QR' : 'Upload QR' }}
                                    </label>
                                    @if($employee->gcash_qr)
                                    <button type="button" class="avatar-change-btn" onclick="confirmRemoveGcashQr()" style="padding:9px 10px;font-size:12px;flex:1;justify-content:center;">
                                        <i data-lucide="trash-2"></i>
                                        Remove
                                    </button>
                                    @endif
                                </div>
                                <p style="font-size:11px;color:var(--muted);margin-top:8px;">JPG, PNG, or WEBP · max 4MB</p>
                            </div>
                        </div>
                    </div>
                    </div>

                    <!-- Right: Profile Form -->
                    <div style="flex:1;">
                        <div class="pv-card">
                            <div class="profile-info-head" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                                <h3 class="pv-card-title" style="margin-bottom:0;">Profile Information</h3>
                                <div id="profileActions">
                                    <button type="button" class="save-btn" onclick="enableEdit()">
                                        <i data-lucide="pencil"></i>
                                        Edit Profile
                                    </button>
                                </div>
                                <div id="profileEditActions" style="display:none;gap:8px;">
                                    <button type="button" class="cancel-btn" onclick="cancelEdit()">
                                        <i data-lucide="x"></i> Cancel
                                    </button>
                                    <button type="submit" form="profileForm" class="save-btn">
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

                            <form method="POST" action="{{ route('employee.settings.profile') }}" id="profileForm">
                                @csrf
                                @method('PUT')
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label>First Name</label>
                                        <input type="text" name="first_name" class="profile-field"
                                               value="{{ old('first_name', $employee->first_name) }}"
                                               placeholder="First name" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Last Name</label>
                                        <input type="text" name="last_name" class="profile-field"
                                               value="{{ old('last_name', $employee->last_name) }}"
                                               placeholder="Last name" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Username <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="{{ $employee->username }}" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                    <div class="form-group">
                                        <label>Email Address</label>
                                        <input type="email" name="email" class="profile-field"
                                               value="{{ old('email', $employee->email) }}"
                                               placeholder="Email address" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Phone Number</label>
                                        <input type="text" name="contact" class="profile-field"
                                               value="{{ old('contact', $employee->contact) }}"
                                               placeholder="e.g. 09XX XXX XXXX" disabled>
                                    </div>
                                    <div class="form-group">
                                        <label>Role <span style="font-size:11px;color:var(--muted);font-weight:400;">(cannot be changed)</span></label>
                                        <input type="text" value="{{ $employee->role ?? 'Employee' }}" disabled
                                               style="background:var(--surface-2);color:var(--muted);cursor:not-allowed;">
                                    </div>
                                </div>

                                <div style="border-top:1px solid var(--border);margin:18px 0 16px;"></div>
                                <div style="font-size:12px;font-weight:800;color:var(--muted);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:14px;">Address Information</div>
                                <div class="form-grid">
                                    <div class="form-group" id="regionGroup">
                                        <label>Region</label>
                                        <select name="region" id="regionSelect" class="profile-field" disabled>
                                            @if(old('region', $employee->region))
                                            <option value="{{ old('region', $employee->region) }}">{{ old('region', $employee->region) }}</option>
                                            @else
                                            <option value="">— Not set —</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group" id="provinceGroup">
                                        <label>Province</label>
                                        <select name="province" id="provinceSelect" class="profile-field" disabled>
                                            @if(old('province', $employee->province))
                                            <option value="{{ old('province', $employee->province) }}">{{ old('province', $employee->province) }}</option>
                                            @else
                                            <option value="">— Not set —</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group" id="cityGroup">
                                        <label>City / Municipality</label>
                                        <select name="city" id="citySelect" class="profile-field" disabled>
                                            @if(old('city', $employee->city))
                                            <option value="{{ old('city', $employee->city) }}">{{ old('city', $employee->city) }}</option>
                                            @else
                                            <option value="">— Not set —</option>
                                            @endif
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Street Address</label>
                                        <input type="text" name="street_address" class="profile-field"
                                               value="{{ old('street_address', $employee->street_address) }}"
                                               placeholder="e.g. Poblacion Street" disabled>
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

                    <form method="POST" action="{{ route('employee.settings.password') }}" id="passwordForm">
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

    </main>

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

    <!-- ===== GCASH QR CROP MODAL ===== -->
    <div class="modal-overlay" id="qrCropModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Adjust QR Code</h2>
                    <p>Drag to reposition, scroll or pinch to zoom in on just the QR code.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeQrCropModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="qr-crop-wrap">
                <img id="qrCropImage" src="" alt="Crop preview">
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeQrCropModal()">Cancel</button>
                <button type="button" class="save-btn" onclick="saveQrCrop()">
                    <i data-lucide="check"></i> Save QR Code
                </button>
            </div>
        </div>
    </div>

    <!-- ===== REMOVE GCASH QR CONFIRM MODAL ===== -->
    <div class="modal-overlay" id="removeGcashQrModal">
        <div class="modal-card" style="max-width:420px;">
            <div class="modal-header">
                <div>
                    <h2>Remove GCash QR Code?</h2>
                    <p>This will clear your current QR code.</p>
                </div>
                <button class="modal-close" type="button" onclick="closeRemoveGcashQrModal()">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="delete-confirm-body">
                <div class="delete-confirm-icon" style="background:#fee2e2;color:#dc2626;"><i data-lucide="trash-2"></i></div>
                <p>Are you sure you want to remove your GCash QR code?</p>
                <p style="font-size:13px;color:var(--text-secondary);margin-top:8px;line-height:1.5;">
                    Admin won't be able to pay your salary via GCash until you upload a new one.
                </p>
            </div>
            <div class="modal-actions">
                <button type="button" class="cancel-btn" onclick="closeRemoveGcashQrModal()">Cancel</button>
                <button type="button" class="save-btn" style="background:#dc2626;" onclick="document.getElementById('removeGcashQrForm').submit();">
                    <i data-lucide="trash-2"></i> Remove
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

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <script src="{{ asset('js/employee.js') }}"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        // Tab switching
        @if($errors->hasBag('password') && $errors->getBag('password')->any())
        activateTab('password');
        @endif

        document.querySelectorAll('.emp-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                activateTab(this.getAttribute('data-tab'));
            });
        });

        // Password toggles
        document.querySelectorAll('.toggle-pw').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var field = document.getElementById(this.dataset.target);
                field.type = field.type === 'password' ? 'text' : 'password';
                // Lucide has already swapped the <i> for an <svg>, so put a fresh icon back in
                this.innerHTML = '<i data-lucide="' + (field.type === 'password' ? 'eye' : 'eye-off') + '"></i>';
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        });

        // Password strength
        var pwInput = document.getElementById('newPassword');
        var cfInput = document.getElementById('confirmPassword');
        if (pwInput) { pwInput.addEventListener('input', checkPw); cfInput.addEventListener('input', checkPw); }

        // If profile errors, open in edit mode
        @if($errors->hasBag('profile') && $errors->getBag('profile')->any())
        enableEdit();
        @endif

        // Avatar — open crop modal instead of uploading immediately
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

        // GCash QR — open the same "adjust before saving" flow as the profile photo, but with a
        // square (not circular) crop box: a QR code's corners must stay intact to stay scannable.
        document.getElementById('gcashQrInput').addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            if (!validateFileSize(this, 4)) { this.value = ''; return; }
            qrPendingFile = file;
            var reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('qrCropImage').src = e.target.result;
                document.getElementById('qrCropModal').classList.add('show');
                if (qrCropper) { qrCropper.destroy(); qrCropper = null; }
                qrCropper = new Cropper(document.getElementById('qrCropImage'), {
                    aspectRatio: 1,
                    viewMode: 1,
                    dragMode: 'move',
                    background: false,
                    autoCropArea: 1,
                    cropBoxResizable: true,
                    cropBoxMovable: true,
                    toggleDragModeOnDblclick: false,
                });
            };
            reader.readAsDataURL(file);
        });
    });

    function confirmRemoveGcashQr() {
        document.getElementById('removeGcashQrModal').classList.add('show');
    }
    function closeRemoveGcashQrModal() {
        document.getElementById('removeGcashQrModal').classList.remove('show');
    }

    var qrCropper = null;
    var qrPendingFile = null;

    function closeQrCropModal() {
        document.getElementById('qrCropModal').classList.remove('show');
        if (qrCropper) { qrCropper.destroy(); qrCropper = null; }
        document.getElementById('gcashQrInput').value = '';
        qrPendingFile = null;
    }

    function saveQrCrop() {
        if (!qrCropper) return;
        var outputType = (qrPendingFile && qrPendingFile.type === 'image/png') ? 'image/png' : 'image/jpeg';
        // Larger than the avatar's crop (600px vs 400px) — a QR code needs enough resolution
        // to stay scannable once cropped down tighter than the original photo.
        qrCropper.getCroppedCanvas({ width: 600, height: 600, imageSmoothingQuality: 'high' }).toBlob(function (blob) {
            if (!blob) return;
            var fileName    = (qrPendingFile && qrPendingFile.name) || 'gcash-qr.jpg';
            var croppedFile = new File([blob], fileName, { type: blob.type });
            var dt          = new DataTransfer();
            dt.items.add(croppedFile);
            document.getElementById('gcashQrInput').files = dt.files;

            document.getElementById('gcashQrDisplay').innerHTML =
                '<img src="' + URL.createObjectURL(blob) + '" alt="GCash QR" style="width:100%;height:100%;object-fit:contain;">';

            document.getElementById('qrCropModal').classList.remove('show');
            qrCropper.destroy();
            qrCropper = null;
            document.getElementById('gcashQrUploadForm').submit();
        }, outputType, 0.95);
    }

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
                '<img src="' + URL.createObjectURL(blob) + '" style="width:100%;height:100%;object-fit:cover;">';

            document.getElementById('avatarCropModal').classList.remove('show');
            avatarCropper.destroy();
            avatarCropper = null;
            document.getElementById('photoUploadForm').submit();
        }, outputType, 0.92);
    }

    function activateTab(name) {
        document.querySelectorAll('.emp-tab').forEach(function (b) {
            b.classList.toggle('active', b.getAttribute('data-tab') === name);
        });
        document.querySelectorAll('.emp-tab-content').forEach(function (p) {
            p.classList.toggle('active', p.id === 'tab-' + name);
        });
    }

    var originalValues = {};
    var psgcLoaded = false;

    const SAVED_REGION   = @json($employee->region ?? '');
    const SAVED_PROVINCE = @json($employee->province ?? '');
    const SAVED_CITY     = @json($employee->city ?? '');
    const PSGC_BASE      = 'https://psgc.cloud/api';

    function enableEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            originalValues[f.name] = f.value;
            if (f.tagName !== 'SELECT') f.disabled = false;
        });
        document.getElementById('profileActions').style.display = 'none';
        document.getElementById('profileEditActions').style.display = 'flex';
        if (!psgcLoaded) { psgcLoaded = true; psgcLoadRegions(true); }
        else { ['regionSelect','provinceSelect','citySelect'].forEach(function(id){ var s=document.getElementById(id); if(s)s.disabled=false; }); }
    }

    function cancelEdit() {
        document.querySelectorAll('#profileForm .profile-field').forEach(function (f) {
            if (f.tagName !== 'SELECT') { f.value = originalValues[f.name] ?? f.value; f.disabled = true; }
        });
        psgcResetToSaved('regionSelect',   SAVED_REGION);
        psgcResetToSaved('provinceSelect', SAVED_PROVINCE);
        psgcResetToSaved('citySelect',     SAVED_CITY);
        psgcLoaded = false;
        document.getElementById('profileActions').style.display = '';
        document.getElementById('profileEditActions').style.display = 'none';
    }

    // The PSGC API's own data has some names double UTF-8-encoded (e.g. "BiÃ±an" instead
    // of "Biñan"); this reverses that specific mis-encoding.
    function fixMojibake(str) {
        try { return decodeURIComponent(escape(str)); } catch (e) { return str; }
    }

    function psgcFetch(url) {
        return fetch(url).then(function(r){ if(!r.ok) throw new Error(r.status); return r.json(); })
            .then(function(data) {
                return Array.isArray(data) ? data.map(function (item) {
                    return Object.assign({}, item, { name: fixMojibake(item.name) });
                }) : data;
            });
    }

    function psgcBuildOptions(sel, items, val) {
        sel.innerHTML = '<option value="">-- Select --</option>';
        items.slice().sort(function(a,b){return a.name.localeCompare(b.name);}).forEach(function(item){
            var o = document.createElement('option');
            o.value = item.name; o.dataset.code = item.code; o.textContent = item.name;
            if (item.name === val) o.selected = true;
            sel.appendChild(o);
        });
        sel.disabled = false;
    }

    function psgcSetLoading(groupId, on) { var g=document.getElementById(groupId); if(g) g.classList.toggle('sel-loading', on); }
    function psgcReset(id, ph) { var s=document.getElementById(id); if(!s)return; s.innerHTML='<option value="">'+ph+'</option>'; s.disabled=true; }
    function psgcResetToSaved(id, val) {
        var s = document.getElementById(id); if(!s) return;
        s.innerHTML = val ? '<option value="'+val+'">'+val+'</option>' : '<option value="">— Not set —</option>';
        s.disabled = true;
    }

    async function psgcLoadRegions(restore) {
        var sel = document.getElementById('regionSelect');
        psgcSetLoading('regionGroup', true);
        try {
            var data = await psgcFetch(PSGC_BASE+'/regions');
            psgcBuildOptions(sel, data, SAVED_REGION);
            if (restore && SAVED_REGION) {
                var match = Array.from(sel.options).find(function(o){return o.value===SAVED_REGION;});
                if (match && match.dataset.code) await psgcLoadProvinces(match.dataset.code, true);
            }
        } catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('regionGroup', false);
    }

    async function psgcLoadProvinces(regionCode, restore) {
        var sel = document.getElementById('provinceSelect');
        psgcReset('citySelect','Select province first');
        psgcSetLoading('provinceGroup', true);
        sel.innerHTML='<option value="">Loading…</option>'; sel.disabled=true;
        try {
            var data = await psgcFetch(PSGC_BASE+'/regions/'+regionCode+'/provinces');
            if (data.length === 0) {
                await psgcLoadCitiesFromRegion(regionCode, restore);
                sel.innerHTML='<option value="NCR / No Province">NCR / No Province</option>';
                sel.value='NCR / No Province'; sel.disabled=false;
            } else {
                psgcBuildOptions(sel, data, SAVED_PROVINCE);
                if (restore && SAVED_PROVINCE) {
                    var match = Array.from(sel.options).find(function(o){return o.value===SAVED_PROVINCE;});
                    if (match && match.dataset.code) await psgcLoadCities(match.dataset.code, true);
                }
            }
        } catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('provinceGroup', false);
    }

    async function psgcLoadCitiesFromRegion(regionCode) {
        var sel = document.getElementById('citySelect');
        psgcSetLoading('cityGroup', true); sel.innerHTML='<option value="">Loading…</option>'; sel.disabled=true;
        try { var data = await psgcFetch(PSGC_BASE+'/regions/'+regionCode+'/cities-municipalities'); psgcBuildOptions(sel, data, SAVED_CITY); }
        catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('cityGroup', false);
    }

    async function psgcLoadCities(provinceCode) {
        var sel = document.getElementById('citySelect');
        psgcSetLoading('cityGroup', true); sel.innerHTML='<option value="">Loading…</option>'; sel.disabled=true;
        try { var data = await psgcFetch(PSGC_BASE+'/provinces/'+provinceCode+'/cities-municipalities'); psgcBuildOptions(sel, data, SAVED_CITY); }
        catch(e) { sel.innerHTML='<option value="">Failed to load</option>'; sel.disabled=false; }
        psgcSetLoading('cityGroup', false);
    }

    document.getElementById('regionSelect').addEventListener('change', function(){
        var code = this.options[this.selectedIndex]?.dataset?.code;
        if (code) psgcLoadProvinces(code, false);
        else { psgcReset('provinceSelect','Select region first'); psgcReset('citySelect','Select province first'); }
    });
    document.getElementById('provinceSelect').addEventListener('change', function(){
        var val = this.value, code = this.options[this.selectedIndex]?.dataset?.code;
        if (val === 'NCR / No Province') return;
        if (code) psgcLoadCities(code, false);
        else psgcReset('citySelect','Select province first');
    });

    function checkPw() {
        var v = document.getElementById('newPassword').value;
        var c = document.getElementById('confirmPassword').value;
        setReq('req-len',   v.length >= 8);
        setReq('req-upper', /[A-Z]/.test(v));
        setReq('req-lower', /[a-z]/.test(v));
        setReq('req-num',   /[0-9]/.test(v));
        setReq('req-match', v.length > 0 && v === c);
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
    function setReq(id, met) {
        var el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('met', met);
        el.classList.toggle('fail', !met && document.getElementById('newPassword').value.length > 0);
    }
    </script>

    <style>
        .profile-field:disabled { background: var(--surface-2); color: var(--text-secondary); cursor: default; border-color: var(--border); }
        select.profile-field { appearance:none;-webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right 12px center; padding-right:34px; }
        select.profile-field:disabled { background-color:var(--surface-2); cursor:not-allowed; }
        .sel-loading { position:relative; }
        .sel-loading::after { content:''; position:absolute; right:34px; top:50%; transform:translateY(-50%); width:12px; height:12px; border:2px solid #e5e7eb; border-top-color:var(--dark); border-radius:50%; animation:psgcSpin 0.7s linear infinite; pointer-events:none; }
        @keyframes psgcSpin { to { transform:translateY(-50%) rotate(360deg); } }
        .pw-req { font-size:11.5px;padding:3px 9px;border-radius:99px;border:1px solid #e5e7eb;color:#9ca3af;background:#f9fafb;display:inline-flex;align-items:center;gap:4px;transition:all 0.15s; }
        .pw-req.met  { background:#dcfce7;border-color:#86efac;color:#15803d; }
        .pw-req.fail { background:#fee2e2;border-color:#fca5a5;color:#dc2626; }

        /* A blank required password field gets a red outline instead of blocking on the
           server-side message list. */
        #passwordForm input.is-invalid {
            border-color: #dc2626 !important;
        }

        /* Phones: title on its own line, Cancel / Save Changes share the full width below it */
        @media (max-width: 640px) {
            .profile-info-head { flex-wrap: wrap; gap: 12px; }
            .profile-info-head .pv-card-title { flex: 1 1 100%; }
            .profile-info-head #profileEditActions { width: 100%; }
            .profile-info-head #profileEditActions button { flex: 1; justify-content: center; white-space: nowrap; }

            /* the 4 password rule pills stay on one line, shrinking to fit */
            .pw-req-row { flex-wrap: nowrap !important; gap: 4px !important; }
            .pw-req-row .pw-req { flex: 1 1 auto; justify-content: center; white-space: nowrap;
                                  font-size: clamp(9px, 2.6vw, 11.5px); padding: 3px 5px; }
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
    @if(session('success'))
    {{-- Profile / password / photo / GCash QR saved: small dark toast centered below the header, 3 seconds --}}
    <div class="toast" id="empSettingsToast" role="status">
        <i data-lucide="check-circle"></i>
        <span>{{ session('success') }}</span>
    </div>
    <script>
        window.addEventListener('load', function () {
            var t = document.getElementById('empSettingsToast');
            if (!t) return;
            if (typeof lucide !== 'undefined') lucide.createIcons();
            requestAnimationFrame(function () { requestAnimationFrame(function () { t.classList.add('show'); }); });
            setTimeout(function () { t.classList.remove('show'); }, 3000);
        });
    </script>
    @endif
</body>
</html>
