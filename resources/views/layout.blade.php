<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="csrf-token" content="{{ csrf_token() }}"><title>@yield('title', 'AJ Vibe')</title><script>try{document.documentElement.dataset.bsTheme=localStorage.getItem('aj-vibe-theme')||(matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light')}catch{document.documentElement.dataset.bsTheme='light'}</script>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="@yield('body-class', 'fashion-dashboard workspace-shell')" data-authenticated="{{ auth()->check() ? 'true' : 'false' }}">
@hasSection('navigation')
@yield('navigation')
@elseif(auth()->check())
@include('partials.fashion-navigation')
@else
@include('partials.guest-navigation')
@endif
<div class="fashion-container theme-toolbar no-print"><span class="toolbar-brand-note">AJ VIBE <span>/ Everyday menswear</span></span><button type="button" class="theme-toggle" aria-label="Switch color theme" aria-pressed="false">Dark mode</button>
@auth
<a href="{{ route('notifications.index') }}" class="notification-link"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9Z"/><path d="M9 21h6"/></svg> Notifications <span class="badge text-bg-danger" data-notification-count data-count-url="{{ route('notifications.count') }}" hidden>0</span></a>
@endauth
</div><div id="activity-toast" class="activity-toast no-print" role="status" hidden>New activity received. <a href="{{ route('notifications.index') }}">View notifications →</a></div>
<main class="@yield('main-class', 'container my-5')">
@if(session('success'))
<div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger" role="alert"><ul class="mb-0">
@foreach($errors->all() as $error)
<li>{{ $error }}</li>
@endforeach
</ul></div>
@endif
@yield('content')
</main>
@include('partials.product-search')
@hasSection('footer')
@yield('footer')
@elseif(auth()->check())
@include('partials.fashion-footer')
@else
<footer class="fashion-footer no-print"><div class="fashion-container"><a class="fashion-brand" href="{{ route('catalog.index') }}">AJ <span class="fashion-brand-light">Vibe</span></a><p>Your everyday collection. Your own point of view.</p><a class="fashion-footer-link" href="{{ route('catalog.index') }}">Explore the shop →</a></div></footer>
@endif
</body></html>
