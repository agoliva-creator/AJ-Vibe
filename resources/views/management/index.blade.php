@extends('layout')
@section('content')
<div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-4"><div><h1>{{ ucfirst($entity) }}</h1><p class="text-muted mb-0">{{ $records->total() }} records{{ auth()->user()->isVendor() ? ' · Your inventory' : '' }}</p></div><a class="btn btn-primary" href="{{ route('records.create',$entity) }}">+ Add {{ \Illuminate\Support\Str::singular($entity) }}</a></div><div class="card p-3 table-responsive"><table class="table align-middle mb-0"><thead><tr><th>Record</th><th>Details</th><th>Actions</th></tr></thead><tbody>
@forelse($records as $record)
<tr><td class="fw-semibold">
@if(in_array($entity,['products','users']))
@php($recordImage = $entity === 'products' ? $record->image_path : $record->avatar_path)
@if($recordImage || $entity === 'users')
<img class="record-thumbnail me-2 {{ $entity === 'users' ? 'profile-circle' : '' }}" src="{{ app(\App\Services\ImageService::class)->url($recordImage) ?? asset('images/default-avatar.svg') }}" alt="{{ $record->name }}" loading="lazy">
@endif
@endif
{{ $record->name ?? $record->code }}</td><td>
@switch($entity)
@case('users')
{{ ucfirst($record->role) }} · {{ $record->email }} · {{ $record->active ? 'Active' : 'Disabled' }}
@break
@case('products')
₱{{ number_format($record->price,2) }} · {{ $record->stock }} in stock · {{ $record->status }}
@break
@case('coupons')
{{ $record->type === 'percent' ? $record->value.'%' : '₱'.\App\Services\Money::decimal($record->value) }} · {{ $record->uses }} uses
@break
@default
{{ $record->slug }}
@endswitch
</td><td><div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-secondary" href="{{ route('records.show',[$entity,$record->id]) }}">View</a><a class="btn btn-sm btn-outline-success" href="{{ route('records.edit',[$entity,$record->id]) }}">Edit</a>
@can('delete',$record)
<form method="post" action="{{ route('records.destroy',[$entity,$record->id]) }}" data-confirm="Delete this record?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger">Delete</button></form>
@endcan
</div></td></tr>
@empty
<tr><td colspan="3" class="py-4 text-center text-muted">No {{ $entity }} yet. Add your first record to get started.</td></tr>
@endforelse
</tbody></table></div><div class="mt-4">{{ $records->links() }}</div>
@endsection
