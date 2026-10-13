@extends('layout')
@section('title', 'Notifications · AJ Vibe')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4"><h1>Notifications</h1><form method="post" action="{{ route('notifications.read') }}">@csrf<button class="btn btn-primary">Mark all as read</button></form></div>
<div class="list-group">
@forelse($notifications as $notification)
<a class="list-group-item list-group-item-action py-3" href="{{ $notification->data['url'] }}"><span @class(['fw-bold' => !$notification->read_at])>{{ $notification->data['message'] }}</span><small class="d-block text-muted mt-1">{{ $notification->created_at->diffForHumans() }} · {{ $notification->read_at ? 'Read' : 'Unread' }}</small></a>
@empty
<div class="card p-5 text-center text-muted">No notifications yet.</div>
@endforelse
</div><div class="mt-4">{{ $notifications->links() }}</div>
@endsection
