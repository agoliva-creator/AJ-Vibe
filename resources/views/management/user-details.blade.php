<div class="user-management-heading">
    <div><a class="management-back-button btn btn-sm btn-outline-secondary" href="{{ route('records.index', 'users') }}">← Back to users</a><p class="inventory-eyebrow mt-4">User management / Account #{{ $record->id }}</p><h1>Account overview</h1><p class="text-muted mb-0">Profile information and account access at a glance.</p></div>
    @can('update', $record)<a class="btn btn-primary" href="{{ route('records.edit', ['users', $record->id]) }}">Edit account</a>@endcan
</div>
<div class="user-management-grid">
    <aside class="user-management-panel user-profile-summary">
        <img class="user-management-avatar" src="{{ app(\App\Services\ImageService::class)->url($record->avatar_path) ?? asset('images/default-avatar.svg') }}" alt="{{ $record->name }} profile photo">
        <h2>{{ $record->name }}</h2><p class="text-muted user-email">{{ $record->email }}</p>
        <div class="d-flex justify-content-center gap-2 flex-wrap"><span class="user-account-badge">{{ $record->role === 'vendor' ? 'Seller' : ucfirst($record->role) }}</span><span class="user-account-badge">{{ $record->active ? 'Active' : 'Disabled' }}</span></div>
    </aside>
    <section class="user-management-panel">
        <h2>Account information</h2><p class="text-muted small">Details registered to this account.</p>
        <dl class="user-account-facts">
            <div><dt>Full name</dt><dd>{{ $record->name }}</dd></div>
            <div><dt>Email address</dt><dd>{{ $record->email }}</dd></div>
            <div><dt>Role</dt><dd>{{ $record->role === 'vendor' ? 'Seller' : ucfirst($record->role) }}</dd></div>
            <div><dt>Account status</dt><dd>{{ $record->active ? 'Active' : 'Disabled' }}</dd></div>
            <div><dt>Email verification</dt><dd>{{ $record->email_verified_at ? 'Verified' : 'Not verified' }}</dd></div>
            <div><dt>Joined</dt><dd>{{ $record->created_at?->copy()->timezone(config('commerce.display_timezone', 'Asia/Manila'))->format('M d, Y · g:i A') ?? '—' }}</dd></div>
        </dl>
    </section>
</div>
