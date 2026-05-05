<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Excel Insights')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet">
    <style>
        :root { --insight-bg:#f3f6fb; --insight-surface:rgba(255,255,255,.92); --insight-sidebar:linear-gradient(180deg,#09152f 0%,#142a58 100%); --insight-primary:#2f6bff; --insight-primary-soft:rgba(47,107,255,.12); --insight-text:#18243a; --insight-border:rgba(120,138,168,.18); }
        body { margin:0; min-height:100vh; background:radial-gradient(circle at top left, rgba(47,107,255,.12), transparent 28%), radial-gradient(circle at bottom right, rgba(47,182,110,.12), transparent 24%), var(--insight-bg); color:var(--insight-text); font-family:'Manrope',sans-serif; }
        .insight-shell{display:flex;min-height:100vh}.insight-sidebar{width:280px;background:var(--insight-sidebar);color:#fff;padding:24px 18px;position:sticky;top:0;min-height:100vh;box-shadow:16px 0 42px rgba(7,18,44,.18)}
        .brand-box{display:flex;align-items:center;gap:14px;padding:10px 8px 28px}.brand-icon{width:52px;height:52px;border-radius:18px;display:grid;place-items:center;background:linear-gradient(135deg, rgba(255,255,255,.2), rgba(47,107,255,.35));font-size:24px}
        .nav-stack{display:grid;gap:10px}.nav-link-tile{display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:16px;color:rgba(255,255,255,.84);text-decoration:none;transition:.2s ease}.nav-link-tile:hover,.nav-link-tile.active{color:#fff;background:linear-gradient(135deg, rgba(47,107,255,.92), rgba(95,141,255,.88));box-shadow:0 16px 30px rgba(47,107,255,.28)}
        .insight-user{margin-top:auto;padding:18px 10px 4px;display:flex;align-items:center;gap:12px}.insight-avatar{width:46px;height:46px;border-radius:50%;object-fit:cover;border:2px solid rgba(255,255,255,.16)}
        .insight-main{flex:1;padding:26px}.topbar-card,.surface-card{background:var(--insight-surface);border:1px solid var(--insight-border);border-radius:26px;backdrop-filter:blur(16px);box-shadow:0 22px 50px rgba(16,31,66,.08)}
        .topbar-card{padding:18px 22px;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:24px}.surface-card{padding:24px}.stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.stat-card{padding:20px;border-radius:20px;background:#fff;border:1px solid rgba(120,138,168,.14);box-shadow:0 16px 30px rgba(17,24,39,.05)}
        .chart-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}.chart-card{padding:20px;border-radius:22px;background:#fff;border:1px solid rgba(120,138,168,.14);min-height:340px}.upload-dropzone{border:1.5px dashed rgba(47,107,255,.25);background:linear-gradient(180deg, rgba(47,107,255,.04), rgba(255,255,255,.88));border-radius:28px;padding:50px 24px;text-align:center}
        .tab-picker{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.tab-card{border:1px solid rgba(120,138,168,.16);border-radius:20px;padding:18px;background:#fff;display:flex;justify-content:space-between;gap:12px}.chat-shell{display:grid;grid-template-columns:320px minmax(0,1fr);gap:18px}.chat-stream{max-height:620px;overflow:auto;padding-right:8px}.chat-bubble{max-width:78%;padding:16px 18px;border-radius:18px;margin-bottom:16px;background:#f1f4fb}.chat-bubble.user{margin-left:auto;background:linear-gradient(135deg, rgba(47,107,255,.12), rgba(47,107,255,.22))}.chat-bubble.assistant{border:1px solid rgba(120,138,168,.14);background:#fff}.pill-note{display:inline-flex;align-items:center;gap:8px;border-radius:999px;padding:8px 14px;font-size:13px;background:var(--insight-primary-soft);color:var(--insight-primary)}
        @media (max-width:1200px){.stat-grid,.chart-grid,.tab-picker,.chat-shell{grid-template-columns:1fr}.insight-shell{flex-direction:column}.insight-sidebar{position:relative;width:auto;min-height:auto}}
    </style>
    @stack('styles')
</head>
<body>
<div class="insight-shell">
    <aside class="insight-sidebar d-flex flex-column">
        <div class="brand-box"><div class="brand-icon"><i class="fa fa-file-excel-o"></i></div><div><div class="fw-bold fs-5">Excel Insight</div><div class="text-white-50 small">Analyzer Suite</div></div></div>
        <nav class="nav-stack">
            <a href="{{ route('upload.index') }}" class="nav-link-tile {{ request()->routeIs('upload.*') ? 'active' : '' }}"><i class="fa fa-cloud-upload"></i><span>Upload File</span></a>
            <a href="{{ route('dashboard.index') }}" class="nav-link-tile {{ request()->routeIs('dashboard.*') ? 'active' : '' }}"><i class="fa fa-line-chart"></i><span>Graph View</span></a>
            <a href="{{ route('chat.index') }}" class="nav-link-tile {{ request()->routeIs('chat.*') ? 'active' : '' }}"><i class="fa fa-comments-o"></i><span>Chat Assistant</span></a>
            <a href="{{ route('history.index') }}" class="nav-link-tile {{ request()->routeIs('history.*') ? 'active' : '' }}"><i class="fa fa-history"></i><span>History</span></a>
        </nav>
        <div class="insight-user"><img class="insight-avatar" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=1c3d7a&color=ffffff" alt="User"><div><div class="fw-semibold">{{ auth()->user()->name }}</div><div class="text-white-50 small">Authenticated User</div></div></div>
    </aside>
    <main class="insight-main">
        <div class="topbar-card"><div><div class="text-uppercase small text-secondary fw-semibold">Excel-based analysis workspace</div><h1 class="h3 mb-0">@yield('page-title', 'Excel Insight Analyzer')</h1></div><div class="d-flex align-items-center gap-3"><span class="pill-note"><i class="fa fa-shield"></i> Authenticated session</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-outline-secondary rounded-pill px-3">Logout</button></form></div></div>
        @if(session('success'))<div class="alert alert-success border-0 shadow-sm rounded-4">{{ session('success') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger border-0 shadow-sm rounded-4">{{ session('error') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger border-0 shadow-sm rounded-4">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        @yield('content')
    </main>
</div>
@stack('scripts')
</body>
</html>
