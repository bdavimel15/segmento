<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Segmentador') — Motor de Segmentação</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ file_exists(public_path('css/app.css')) ? filemtime(public_path('css/app.css')) : time() }}">
  @stack('styles')
</head>
<body>

<div class="app-shell">

  {{-- ── Sidebar ── --}}
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="sidebar-logo-icon">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14H9V8h2v8zm4 0h-2V8h2v8z"/>
        </svg>
      </div>
      <div class="sidebar-logo-text">
        Segmentador
        <span>Motor de IA</span>
      </div>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-label">Principal</span>

      <a href="{{ route('dashboard') }}"
         class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
        </svg>
        Dashboard
      </a>

      <span class="nav-section-label">Segmentação</span>

      <a href="{{ route('segmentos.index') }}"
         class="nav-item {{ request()->routeIs('segmentos.index', 'segmentos.create', 'segmentos.show', 'segmentos.edit', 'segmentos.store', 'segmentos.manual', 'segmentos.interpretar') ? 'active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
        </svg>
        Segmentos
      </a>

      <a href="{{ route('segmentos.presets') }}"
         class="nav-item {{ request()->routeIs('segmentos.presets', 'segmentos.presets.usar') ? 'active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        Modelos prontos
      </a>

      <span class="nav-section-label">Base de dados</span>

      <a href="{{ route('clientes.index') }}"
         class="nav-item {{ request()->routeIs('clientes.index', 'clientes.create', 'clientes.edit', 'clientes.store', 'clientes.update', 'clientes.destroy') ? 'active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Clientes
      </a>

      <a href="{{ route('clientes.importForm') }}"
         class="nav-item {{ request()->routeIs('clientes.importForm', 'clientes.import') ? 'active' : '' }}">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        Importar clientes
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="nav-item" style="cursor:default; opacity:.7;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" style="width:17px;height:17px;">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
        <span style="font-size:.78rem;">v1.0 MVP</span>
      </div>
    </div>
  </aside>

  {{-- ── Main ── --}}
  <div class="main-content">

    {{-- Alerts globais --}}
    @if(session('success') || session('ok'))
      <div style="padding: 12px 32px 0;">
        <div class="alert alert-success">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ session('success') ?? session('ok') }}
        </div>
      </div>
    @endif

    @if(session('error') || session('erro'))
      <div style="padding: 12px 32px 0;">
        <div class="alert alert-danger">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ session('error') ?? session('erro') }}
        </div>
      </div>
    @endif

    @if($errors->any())
      <div style="padding: 12px 32px 0;">
        <div class="alert alert-danger">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <div>
            <strong>Corrija os erros abaixo:</strong>
            <ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
          </div>
        </div>
      </div>
    @endif

    @yield('content')
  </div>
</div>

<script>
  // Tab switcher
  function switchTab(group, tabId) {
    document.querySelectorAll('[data-tab-group="'+group+'"]').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('[data-panel-group="'+group+'"]').forEach(el => el.classList.remove('active'));
    document.querySelector('[data-tab-group="'+group+'"][data-tab="'+tabId+'"]').classList.add('active');
    document.querySelector('[data-panel-group="'+group+'"][data-panel="'+tabId+'"]').classList.add('active');
  }

  // Example chips
  document.querySelectorAll('.example-chip').forEach(chip => {
    chip.addEventListener('click', () => {
      const target = chip.dataset.target;
      const field = document.getElementById(target);
      if (field) field.value = chip.textContent.trim();
    });
  });

  // Upload drag & drop visual
  document.querySelectorAll('.upload-area').forEach(area => {
    area.addEventListener('dragover', e => { e.preventDefault(); area.classList.add('dragover'); });
    area.addEventListener('dragleave', () => area.classList.remove('dragover'));
    area.addEventListener('drop', e => { e.preventDefault(); area.classList.remove('dragover'); });
    area.addEventListener('click', () => area.querySelector('input[type=file]')?.click());
  });
</script>
@stack('scripts')
</body>
</html>
