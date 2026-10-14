<div class="fashion-announcement no-print"><div class="fashion-container">
@if(auth()->user()->isAdmin())
Your marketplace, thoughtfully managed. <a href="{{ route('settings') }}">Store settings →</a>
@elseif(auth()->user()->isVendor())
Your collection. Your business. <a href="{{ route('records.create','products') }}">Add a new product →</a>
@else
New season. New favorites. <a href="{{ route('catalog.index') }}">Discover the collection →</a>
@endif
</div></div>
<nav class="fashion-navigation navbar-expand-lg no-print">
<div class="fashion-container fashion-nav-inner">
<a class="fashion-brand" href="{{ route('dashboard') }}"><span class="fashion-brand-mark">AJ</span>AJ <span class="fashion-brand-light">Vibe</span></a>
<button class="navbar-toggler fashion-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#role-navigation" aria-controls="role-navigation" aria-expanded="false" aria-label="Toggle navigation">☰</button>
<div class="collapse navbar-collapse fashion-nav-collapse" id="role-navigation">
<div class="fashion-nav-links">
<a class="{{ request()->routeIs('dashboard') ? 'is-current' : '' }}" href="{{ route('dashboard') }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>Home</a>
@if(auth()->user()->role === 'customer')
<a href="{{ route('catalog.index') }}">Products</a><a href="{{ route('orders.index') }}">My orders</a><a href="{{ route('account') }}">My account</a>
@else
<a href="{{ route('records.index','products') }}">Inventory</a><a href="{{ route('orders.index') }}">Orders</a>
@if(auth()->user()->isAdmin())
@foreach(['users','categories','coupons'] as $entity)
<a href="{{ route('records.index',$entity) }}">{{ ucfirst($entity) }}</a>
@endforeach
<a href="{{ route('settings') }}">Settings</a>
@else
<a href="{{ route('catalog.index') }}">Shop</a><a href="{{ route('account') }}">My account</a>
@endif
@endif
</div>
<div class="fashion-nav-tools">
<button class="header-search-toggle" type="button" data-bs-toggle="modal" data-bs-target="#product-search-modal" aria-label="Search products"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/></svg></button>
<a href="{{ route('account') }}" aria-label="Your account">
<img class="avatar" src="{{ app(\App\Services\ImageService::class)->url(auth()->user()->avatar_path) ?? asset('images/default-avatar.svg') }}" alt="Your profile photo">
</a>
@if(auth()->user()->role === 'customer')
<a href="{{ route('cart.show') }}" aria-label="Shopping cart"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 7h14l1 14H4L5 7Z"/><path d="M8 8V6a4 4 0 0 1 8 0v2"/></svg></a>
@endif
</div></div></div></nav>
