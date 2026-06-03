@extends('layouts.app')
@section('title', 'Modelos Prontos')

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Modelos prontos</h1>
    <p>Segmentos pré-configurados para os casos de uso mais comuns. Clique em usar para criar um segmento baseado no modelo.</p>
  </div>
  <a href="{{ route('segmentos.create') }}" class="btn btn-primary">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
    Criar do zero
  </a>
</div>

<div class="page-body">

  @php
    $categorias = [
      'aniversario' => ['label' => 'Aniversário',  'icon' => '🎂', 'cor' => '#F59E0B'],
      'compras'     => ['label' => 'Compras',       'icon' => '🛍️', 'cor' => '#7C5CFF'],
      'cashback'    => ['label' => 'Cashback',      'icon' => '💰', 'cor' => '#16A34A'],
      'carrinho'    => ['label' => 'Carrinho',      'icon' => '🛒', 'cor' => '#0EA5E9'],
      'cadastro'    => ['label' => 'Cadastro',      'icon' => '📋', 'cor' => '#6B6680'],
      'engajamento' => ['label' => 'Engajamento',   'icon' => '🔔', 'cor' => '#DC2626'],
    ];

    // Aceita tanto Collection plana quanto Collection agrupada pelo controller.
    $presetCollection = isset($presets) ? collect($presets)->flatten(1) : collect();
    $porCategoria = [];
    foreach($presetCollection as $p) {
      if (is_object($p)) {
        $porCategoria[$p->categoria ?: 'outros'][] = $p;
      }
    }
  @endphp

  {{-- Filtro de categorias --}}
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px;">
    <button class="btn btn-secondary btn-sm categoria-filter active" data-cat="todos"
            onclick="filtrarCategoria('todos', this)">
      Todos
    </button>
    @foreach($categorias as $key => $cat)
      <button class="btn btn-secondary btn-sm categoria-filter" data-cat="{{ $key }}"
              onclick="filtrarCategoria('{{ $key }}', this)">
        {{ $cat['icon'] }} {{ $cat['label'] }}
      </button>
    @endforeach
  </div>

  @if(isset($presetCollection) && $presetCollection->count())

    @foreach($categorias as $catKey => $catInfo)
      @if(isset($porCategoria[$catKey]) && count($porCategoria[$catKey]))
        <div class="section categoria-section" data-cat="{{ $catKey }}">
          <div class="section-header" style="margin-bottom:16px;">
            <div class="section-title" style="display:flex;align-items:center;gap:8px;">
              <span style="font-size:1.1rem;">{{ $catInfo['icon'] }}</span>
              {{ $catInfo['label'] }}
              <span class="badge badge-neutral" style="font-size:.72rem;">{{ count($porCategoria[$catKey]) }}</span>
            </div>
          </div>

          <div class="presets-grid">
            @foreach($porCategoria[$catKey] as $preset)
              <div class="preset-card">
                <div class="preset-card-header">
                  <div class="preset-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      @if($catKey === 'aniversario')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-1.5-.454M9 6l3-3 3 3m-6 0v5a3 3 0 006 0V6"/>
                      @elseif($catKey === 'compras')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                      @elseif($catKey === 'cashback')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                      @elseif($catKey === 'carrinho')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                      @elseif($catKey === 'engajamento')
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                      @else
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                      @endif
                    </svg>
                  </div>
                  @if($preset->ativo)
                    <span class="badge badge-success" style="font-size:.7rem;"><span class="badge-dot"></span>Ativo</span>
                  @else
                    <span class="badge badge-neutral" style="font-size:.7rem;">Inativo</span>
                  @endif
                </div>

                <div>
                  <div class="preset-card-name">{{ $preset->nome }}</div>
                </div>

                <div class="preset-card-desc">{{ $preset->descricao }}</div>

                <div class="preset-card-footer">
                  <span class="badge badge-purple" style="font-size:.72rem;">{{ $catInfo['label'] }}</span>
                  @if($preset->ativo)
                    <form method="POST" action="{{ route('segmentos.presets.usar', $preset->segmento_cliente_preset_id ?? $preset->id) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn btn-primary btn-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Usar modelo
                      </button>
                    </form>
                  @else
                    <button class="btn btn-ghost btn-sm" disabled style="opacity:.5;cursor:not-allowed;">Indisponível</button>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>

        @if(!$loop->last)
          <div class="sep"></div>
        @endif
      @endif
    @endforeach

    {{-- Presets sem categoria mapeada --}}
    @php
      $semCategoria = isset($presetCollection) ? $presetCollection->filter(fn($p) => !array_key_exists($p->categoria ?: 'outros', $categorias)) : collect();
    @endphp
    @if($semCategoria->count())
      <div class="section categoria-section" data-cat="outros">
        <div class="section-header" style="margin-bottom:16px;">
          <div class="section-title">Outros</div>
        </div>
        <div class="presets-grid">
          @foreach($semCategoria as $preset)
            <div class="preset-card">
              <div class="preset-card-header">
                <div class="preset-card-icon">
                  <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </div>
                @if($preset->ativo)
                  <span class="badge badge-success" style="font-size:.7rem;"><span class="badge-dot"></span>Ativo</span>
                @else
                  <span class="badge badge-neutral" style="font-size:.7rem;">Inativo</span>
                @endif
              </div>
              <div class="preset-card-name">{{ $preset->nome }}</div>
              <div class="preset-card-desc">{{ $preset->descricao }}</div>
              <div class="preset-card-footer">
                <span class="badge badge-neutral" style="font-size:.72rem;">{{ ucfirst($preset->categoria ?? 'Geral') }}</span>
                @if($preset->ativo)
                  <form method="POST" action="{{ route('segmentos.presets.usar', $preset->segmento_cliente_preset_id ?? $preset->id) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-sm">Usar modelo</button>
                  </form>
                @else
                  <button class="btn btn-ghost btn-sm" disabled style="opacity:.5;cursor:not-allowed;">Indisponível</button>
                @endif
              </div>
            </div>
          @endforeach
        </div>
      </div>
    @endif

  @else

    {{-- Empty state com exemplos visuais --}}
    <div class="card">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        </div>
        <h3>Nenhum modelo disponível</h3>
        <p>Os modelos prontos serão exibidos aqui quando forem cadastrados no sistema.</p>
        <a href="{{ route('segmentos.create') }}" class="btn btn-primary">Criar segmento do zero</a>
      </div>
    </div>

    {{-- Preview de categorias disponíveis --}}
    <div class="mt-24">
      <div class="section-title" style="margin-bottom:16px;color:var(--text-muted);font-size:.88rem;">
        Categorias que estarão disponíveis:
      </div>
      <div style="display:flex;gap:12px;flex-wrap:wrap;">
        @foreach($categorias as $key => $cat)
          <div class="card" style="padding:14px 18px;display:flex;align-items:center;gap:10px;min-width:160px;">
            <span style="font-size:1.4rem;">{{ $cat['icon'] }}</span>
            <div>
              <div style="font-weight:600;font-size:.88rem;">{{ $cat['label'] }}</div>
              <div style="font-size:.76rem;color:var(--text-muted);">Em breve</div>
            </div>
          </div>
        @endforeach
      </div>
    </div>

  @endif

</div>

@push('scripts')
<script>
function filtrarCategoria(cat, btn) {
  // Atualiza botões
  document.querySelectorAll('.categoria-filter').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');

  // Filtra seções
  document.querySelectorAll('.categoria-section').forEach(sec => {
    if (cat === 'todos' || sec.dataset.cat === cat) {
      sec.style.display = '';
    } else {
      sec.style.display = 'none';
    }
  });

  // Oculta seps entre seções escondidas
  document.querySelectorAll('.sep').forEach(sep => {
    const prev = sep.previousElementSibling;
    const next = sep.nextElementSibling;
    if (prev && next) {
      sep.style.display =
        (prev.style.display === 'none' || next.style.display === 'none') ? 'none' : '';
    }
  });
}
</script>
@endpush
@endsection
