<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Segmentador') — Segmentador IA</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : time() }}">
  @stack('styles')
</head>
<body class="module-body">

<header class="module-header">
  <div class="module-header-inner">
    <a href="{{ route('segmentos.create') }}" class="module-brand">
      <span class="module-brand-icon">◎</span>
      <span class="module-brand-text">Segmentador <strong>IA</strong></span>
    </a>

    <button type="button" class="module-nav-toggle" id="moduleNavToggle" aria-label="Menu">☰</button>

    <nav class="module-nav" id="moduleNav">
      <a href="{{ route('segmentos.create') }}" class="module-nav-link {{ request()->routeIs('segmentos.create') ? 'active' : '' }}">Criar segmento</a>
      <a href="{{ route('segmentos.index') }}" class="module-nav-link {{ request()->routeIs('segmentos.index', 'segmentos.show', 'segmentos.edit') ? 'active' : '' }}">Segmentos</a>
      <a href="{{ route('segmentos.modelos') }}" class="module-nav-link {{ request()->routeIs('segmentos.modelos', 'segmentos.presets', 'segmentos.presets.usar') ? 'active' : '' }}">Modelos prontos</a>
      <a href="{{ route('clientes.importForm') }}" class="module-nav-link {{ request()->routeIs('clientes.importForm', 'clientes.import') ? 'active' : '' }}">Importar clientes</a>
      @if($segmentadorIsAdmin ?? false)
        <a href="{{ route('admin.index') }}" class="module-nav-link module-nav-admin">Admin</a>
      @endif
    </nav>
  </div>
</header>

<main class="module-main">
  @if(session('success') || session('ok'))
    <div class="module-alerts">
      <div class="alert alert-success">{{ session('success') ?? session('ok') }}</div>
    </div>
  @endif

  @if(session('error') || session('erro'))
    <div class="module-alerts">
      <div class="alert alert-danger">{{ session('error') ?? session('erro') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div class="module-alerts">
      <div class="alert alert-danger">
        <strong>Corrija os erros abaixo:</strong>
        <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
      </div>
    </div>
  @endif

  @yield('content')
</main>

<script>
  document.getElementById('moduleNavToggle')?.addEventListener('click', () => {
    document.getElementById('moduleNav')?.classList.toggle('is-open');
  });
</script>
@stack('scripts')
</body>
</html>
