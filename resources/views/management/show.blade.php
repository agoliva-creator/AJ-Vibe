@extends('layout')
@section('content')
@if($entity === 'products')
@include('management.product-details')
@elseif($entity === 'users')
@include('management.user-details')
@else
<a class="management-back-button btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-2" href="{{ route('records.index',$entity) }}"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12H4m6-6-6 6 6 6"/></svg><span>Back to {{ $entity }}</span></a><h1 class="mt-3">{{ $record->name ?? $record->code }}</h1><div class="card p-4">
@foreach(\App\Http\Controllers\ManagementController::FIELDS[$entity] as $field)
@if($field !== 'password')
<p><strong>{{ ucfirst(str_replace('_',' ',$field)) }}:</strong> {{ $record->$field }}</p>
@endif
@endforeach
@if($record->image_path || $record->avatar_path || $entity === 'users')
<img class="img-fluid mb-3 {{ $entity === 'users' ? 'profile-circle profile-detail-avatar' : 'rounded' }}" style="max-width:300px" src="{{ app(\App\Services\ImageService::class)->url($record->image_path ?? $record->avatar_path) ?? asset('images/default-avatar.svg') }}" alt="{{ $record->name }}">
@endif
<div><a class="btn btn-primary" href="{{ route('records.edit',[$entity,$record->id]) }}">Edit record</a></div></div>
@endif
@endsection
