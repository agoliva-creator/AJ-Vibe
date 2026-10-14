<div class="user-management-heading"><div><a class="management-back-button btn btn-sm btn-outline-secondary" href="{{ route('records.index', 'users') }}">← Back to users</a><p class="inventory-eyebrow mt-4">User management / {{ $record->exists ? 'Edit account' : 'New account' }}</p><h1>{{ $record->exists ? 'Edit account' : 'Create account' }}</h1><p class="text-muted mb-0">Manage profile details, role, and account access.</p></div></div>
<form method="post" enctype="multipart/form-data" action="{{ $record->exists ? route('records.update', ['users', $record->id]) : route('records.store', 'users') }}">
    @csrf
    @if($record->exists) @method('PUT') @endif
    <div class="user-management-grid">
        <aside class="user-management-panel user-profile-summary">
            <img id="upload-preview" class="upload-preview user-management-avatar" src="{{ app(\App\Services\ImageService::class)->url($record->avatar_path) ?? asset('images/default-avatar.svg') }}" alt="Profile photo preview">
            <h2>Profile photo</h2><p class="text-muted small">Add a clear photo for this account.</p>
            <label class="form-label text-start d-block" for="upload">Upload photo</label><input class="form-control @error('avatar') is-invalid @enderror" id="upload" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
            @error('avatar')<p class="text-danger small mt-2">{{ $message }}</p>@enderror
            <p class="text-muted small mt-3 mb-0">JPEG, PNG, or WebP · maximum 2 MB.<br>Leave blank to keep the current photo.</p>
        </aside>
        <section class="user-management-panel">
            <h2>Account details</h2><p class="text-muted small mb-4">Keep account information accurate and up to date.</p>
            <div class="user-editor-fields">
                <div><label class="form-label" for="name">Full name</label><input class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $record->name) }}" maxlength="255" required autocomplete="name">@error('name')<p class="text-danger small mt-2">{{ $message }}</p>@enderror</div>
                <div><label class="form-label" for="email">Email address</label><input class="form-control @error('email') is-invalid @enderror" id="email" name="email" type="email" value="{{ old('email', $record->email) }}" maxlength="255" required autocomplete="email">@error('email')<p class="text-danger small mt-2">{{ $message }}</p>@enderror</div>
                @foreach(['role' => 'Role', 'active' => 'Account status'] as $field => $label)
                    <div><label class="form-label" for="{{ $field }}">{{ $label }}</label><select class="form-select @error($field) is-invalid @enderror" id="{{ $field }}" name="{{ $field }}" required>@foreach($options[$field] as $value => $option)<option value="{{ $value }}" @selected((string) old($field, $record->$field) === (string) $value)>{{ $field === 'active' ? ($value ? 'Active' : 'Disabled') : ($value === 'vendor' ? 'Seller' : $option) }}</option>@endforeach</select>@error($field)<p class="text-danger small mt-2">{{ $message }}</p>@enderror</div>
                @endforeach
            </div>
            <div class="user-security-section"><h2>Password & security</h2><label class="form-label" for="password">{{ $record->exists ? 'New password' : 'Password' }}</label><input class="form-control @error('password') is-invalid @enderror" type="password" id="password" name="password" autocomplete="new-password" @required(!$record->exists)>@error('password')<p class="text-danger small mt-2">{{ $message }}</p>@enderror<p class="text-muted small mt-2 mb-0">Use at least 12 characters with uppercase and lowercase letters and numbers. @if($record->exists) Leave blank to keep the current password. @endif</p></div>
            <div class="user-editor-actions"><a class="btn btn-outline-secondary" href="{{ route('records.index', 'users') }}">Cancel</a><button class="btn btn-primary" type="submit">{{ $record->exists ? 'Save changes' : 'Create account' }}</button></div>
        </section>
    </div>
</form>
