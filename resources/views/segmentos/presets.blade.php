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
    use Illuminate\Support\Str;

    $baseCategorias = [
      'aniversario'  => ['label' => 'Aniversário',  'icon' => '🎂', 'cor' => '#F59E0B'],
      'compras'      => ['label' => 'Compras',      'icon' => '🛍️', 'cor' => '#7C5CFF'],
      'cashback'     => ['label' => 'Cashback',     'icon' => '💰', 'cor' => '#16A34A'],
      'carrinho'     => ['label' => 'Carrinho',     'icon' => '🛒', 'cor' => '#0EA5E9'],
      'cadastro'     => ['label' => 'Cadastro',     'icon' => '📋', 'cor' => '#6B6680'],
      'engajamento'  => ['label' => 'Engajamento',  'icon' => '🔔', 'cor' => '#DC2626'],
      'perfil'       => ['label' => 'Perfil',       'icon' => '👤', 'cor' => '#8B5CF6'],
      'localizacao'  => ['label' => 'Localização',  'icon' => '📍', 'cor' => '#10B981'],
      'fidelidade'   => ['label' => 'Fidelidade',   'icon' => '🏆', 'cor' => '#EAB308'],
      'produtos'     => ['label' => 'Produtos',     'icon' => '📦', 'cor' => '#EF4444'],
      'pedidos'      => ['label' => 'Pedidos',      'icon' => '📦', 'cor' => '#8B5CF6'],
      'recencia'     => ['label' => 'Recência',     'icon' => '⏳', 'cor' => '#F97316'],
      'recompra'     => ['label' => 'Recompra',     'icon' => '🔄', 'cor' => '#7C5CFF'],
      'vip'          => ['label' => 'VIP',          'icon' => '⭐', 'cor' => '#EAB308'],
      'combinados'   => ['label' => 'Combinados',   'icon' => '🎯', 'cor' => '#A855F7'],
      'outros'       => ['label' => 'Outros',       'icon' => '📁', 'cor' => '#6B6680'],
    ];

    $normalizarCategoria = function ($categoria) {
      $categoria = trim((string) ($categoria ?: 'outros'));
      $slug = Str::slug($categoria, '_');

      $aliases = [
        'aniversario' => 'aniversario',
        'aniversarios' => 'aniversario',
        'aniversario_do_mes' => 'aniversario',
        'compras' => 'compras',
        'compra' => 'compras',
        'cashback' => 'cashback',
        'carrinho' => 'carrinho',
        'cadastro' => 'cadastro',
        'engajamento' => 'engajamento',
        'perfil' => 'perfil',
        'localizacao' => 'localizacao',
        'localizacao' => 'localizacao',
        'fidelidade' => 'fidelidade',
        'produtos' => 'produtos',
        'produto' => 'produtos',
        'pedidos' => 'pedidos',
        'pedido' => 'pedidos',
        'recencia' => 'recencia',
        'recompra' => 'recompra',
        'vip' => 'vip',
        'combinados' => 'combinados',
      ];

      return $aliases[$slug] ?? $slug ?: 'outros';
    };

    // Aceita Collection plana, array simples ou Collection agrupada pelo controller.
    $presetCollection = isset($presets) ? collect($presets) : collect();

    if ($presetCollection->isNotEmpty() && $presetCollection->first() instanceof \Illuminate\Support\Collection) {
      $presetCollection = $presetCollection->flatten(1);
    }

    $presetCollection = $presetCollection->filter(fn($p) => is_object($p))->values();

    $porCategoria = [];
    $categorias = [];

    foreach ($presetCollection as $preset) {
      $catKey = $normalizarCategoria($preset->categoria ?? 'outros');
      $porCategoria[$catKey][] = $preset;

      if (! isset($categorias[$catKey])) {
        $rawLabel = trim((string)($preset->categoria ?? 'Outros'));
        $categorias[$catKey] = $baseCategorias[$catKey] ?? [
          'label' => $rawLabel !== '' ? Str::headline(str_replace(['_', '-'], ' ', $rawLabel)) : 'Outros',
          'icon' => '📁',
          'cor' => '#6B6680',
        ];
      }
    }

    // Mantém uma ordem profissional, mas só exibe categorias que possuem modelos.
    $ordem = ['aniversario','compras','cashback','carrinho','cadastro','engajamento','perfil','pedidos','recencia','recompra','produtos','fidelidade','localizacao','vip','combinados','outros'];
    $categoriasOrdenadas = [];

    foreach ($ordem as $key) {
      if (isset($categorias[$key])) {
        $categoriasOrdenadas[$key] = $categorias[$key];
      }
    }

    foreach ($categorias as $key => $cat) {
      if (! isset($categoriasOrdenadas[$key])) {
        $categoriasOrdenadas[$key] = $cat;
      }
    }

    $iconeCard = function ($catKey) {
      return match ($catKey) {
        'aniversario' => 'M21 15.546c-.523 0-1.046.151-1.5.454a2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-3 0 2.704 2.704 0 00-3 0 2.704 2.704 0 01-1.5-.454M9 6l3-3 3 3m-6 0v5a3 3 0 006 0V6',
        'compras', 'pedidos', 'produtos' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        'cashback' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'carrinho' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        'engajamento' => 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9',
        default => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
      };
    };
  @endphp

  {{-- Filtro de categorias --}}
  @if($presetCollection->count())
    <div class="preset-filter-wrap">
      <button class="btn btn-secondary btn-sm categoria-filter active" data-cat="todos" type="button">
        Todos <span class="filter-count">{{ $presetCollection->count() }}</span>
      </button>

      @foreach($categoriasOrdenadas as $key => $cat)
        <button class="btn btn-secondary btn-sm categoria-filter" data-cat="{{ $key }}" type="button">
          {{ $cat['icon'] }} {{ $cat['label'] }}
          <span class="filter-count">{{ count($porCategoria[$key] ?? []) }}</span>
        </button>
      @endforeach
    </div>
  @endif

  @if($presetCollection->count())

    <div id="empty-filter-state" class="card" style="display:none;">
      <div class="empty-state">
        <div class="empty-state-icon">🔎</div>
        <h3>Nenhum modelo nesta categoria</h3>
        <p>Escolha outra categoria ou volte para “Todos”.</p>
      </div>
    </div>

    @foreach($categoriasOrdenadas as $catKey => $catInfo)
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
              <div class="preset-card" data-cat="{{ $catKey }}">
                <div class="preset-card-header">
                  <div class="preset-card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconeCard($catKey) }}"/>
                    </svg>
                  </div>

                  @if(($preset->ativo ?? 'S') === 'S' || ($preset->ativo ?? true) === true || ($preset->ativo ?? null) === 1)
                    <span class="badge badge-success" style="font-size:.7rem;"><span class="badge-dot"></span>Ativo</span>
                  @else
                    <span class="badge badge-neutral" style="font-size:.7rem;">Inativo</span>
                  @endif
                </div>

                <div class="preset-card-name">{{ $preset->nome }}</div>
                <div class="preset-card-desc">{{ $preset->descricao }}</div>

                <div class="preset-card-footer">
                  <span class="badge badge-purple" style="font-size:.72rem;">{{ $catInfo['label'] }}</span>

                  @if(($preset->ativo ?? 'S') === 'S' || ($preset->ativo ?? true) === true || ($preset->ativo ?? null) === 1)
                    <form method="POST" action="{{ route('segmentos.presets.usar', $preset->segmento_cliente_preset_id ?? $preset->id) }}" style="display:inline;">
                      @csrf
                      <button type="submit" class="btn btn-primary btn-sm">
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
      @endif
    @endforeach

  @else

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

  @endif

</div>

@push('styles')
<style>
  .preset-filter-wrap {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 24px;
    padding: 10px;
    border: 1px solid var(--border);
    border-radius: 14px;
    background: rgba(124, 92, 255, .045);
    width: fit-content;
    max-width: 100%;
  }

  .categoria-filter {
    white-space: nowrap;
  }

  .filter-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    margin-left: 6px;
    border-radius: 999px;
    background: rgba(124, 92, 255, .18);
    color: var(--text);
    font-size: .72rem;
    font-weight: 700;
  }

  .categoria-filter.active .filter-count {
    background: rgba(255,255,255,.2);
  }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const buttons = Array.from(document.querySelectorAll('.categoria-filter'));
  const sections = Array.from(document.querySelectorAll('.categoria-section'));
  const empty = document.getElementById('empty-filter-state');

  function filtrarCategoria(cat) {
    buttons.forEach(button => {
      button.classList.toggle('active', button.dataset.cat === cat);
    });

    let visibleCount = 0;

    sections.forEach(section => {
      const show = cat === 'todos' || section.dataset.cat === cat;
      section.hidden = !show;
      section.style.display = show ? '' : 'none';

      if (show) {
        visibleCount++;
      }
    });

    if (empty) {
      empty.style.display = visibleCount === 0 ? '' : 'none';
    }
  }

  buttons.forEach(button => {
    button.addEventListener('click', () => filtrarCategoria(button.dataset.cat || 'todos'));
  });
});
</script>
@endpush
@endsection
