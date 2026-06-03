@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
  $totalClientes = $totalClientes ?? ($metricas['clientes'] ?? 0);
  $totalSegmentos = $totalSegmentos ?? ($metricas['segmentos'] ?? 0);
  $segmentosValidados = $segmentosValidados ?? ($metricas['segmentos_validados'] ?? 0);
  $clientesEmPrevias = $clientesEmPrevias ?? ($metricas['preview_total'] ?? 0);
  $segmentosRecentes = $segmentosRecentes ?? ($ultimosSegmentos ?? collect());
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>Motor de segmentação inteligente</h1>
    <p>Crie públicos com IA, valide regras e gere prévias de clientes em segundos.</p>
  </div>
  <div class="btn-group">
    <a href="{{ route('segmentos.create') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Criar segmento
    </a>
    <a href="{{ route('clientes.importForm') }}" class="btn btn-secondary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      Importar clientes
    </a>
  </div>
</div>

<div class="page-body">

  {{-- Métricas --}}
  <div class="metrics-grid">
    <div class="metric-card">
      <div class="metric-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      </div>
      <div class="metric-card-value">{{ number_format($totalClientes ?? 0) }}</div>
      <div class="metric-card-label">Total de clientes</div>
    </div>

    <div class="metric-card">
      <div class="metric-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
      </div>
      <div class="metric-card-value">{{ number_format($totalSegmentos ?? 0) }}</div>
      <div class="metric-card-label">Segmentos criados</div>
    </div>

    <div class="metric-card">
      <div class="metric-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      </div>
      <div class="metric-card-value">{{ number_format($segmentosValidados ?? 0) }}</div>
      <div class="metric-card-label">Segmentos validados</div>
    </div>

    <div class="metric-card">
      <div class="metric-card-icon">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      </div>
      <div class="metric-card-value">{{ number_format($clientesEmPrevias ?? 0) }}</div>
      <div class="metric-card-label">Clientes em prévias</div>
    </div>
  </div>

  {{-- Conteúdo principal em duas colunas --}}
  <div style="display:grid; grid-template-columns: 1fr 340px; gap:20px; align-items:start;">

    {{-- Segmentos recentes --}}
    <div class="card">
      <div class="section-header">
        <span class="card-title">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
          Segmentos recentes
        </span>
        <a href="{{ route('segmentos.index') }}" class="btn btn-ghost btn-sm">Ver todos →</a>
      </div>

      @if(isset($segmentosRecentes) && $segmentosRecentes->count())
        <div class="table-wrapper" style="border:none;box-shadow:none;">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Status</th>
                <th>Origem</th>
                <th>Prévia</th>
                <th>Atualizado</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($segmentosRecentes as $segmento)
              <tr>
                <td>
                  <div style="font-weight:500;">{{ $segmento->nome }}</div>
                  @if($segmento->descricao)
                    <div class="td-muted text-xs" style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $segmento->descricao }}</div>
                  @endif
                </td>
                <td>
                  @php
                    $statusMap = [
                      'validado'  => 'badge-success',
                      'reprovado' => 'badge-danger',
                      'pendente'  => 'badge-warning',
                      'rascunho'  => 'badge-neutral',
                    ];
                    $cls = $statusMap[$segmento->status_validacao] ?? 'badge-neutral';
                  @endphp
                  <span class="badge {{ $cls }}">
                    <span class="badge-dot"></span>
                    {{ ucfirst($segmento->status_validacao ?? '—') }}
                  </span>
                </td>
                <td class="td-muted">{{ ucfirst($segmento->origem ?? '—') }}</td>
                <td>
                  @if($segmento->ultima_previa_qtd !== null)
                    <strong>{{ number_format($segmento->ultima_previa_qtd) }}</strong>
                    <span class="td-muted text-xs"> clientes</span>
                  @else
                    <span class="td-muted">—</span>
                  @endif
                </td>
                <td class="td-muted text-xs">@php
                    $atualizadoEm = $segmento->atualizado ?? $segmento->updated_at ?? $segmento->cadastrado ?? null;
                    try {
                      $atualizadoTexto = $atualizadoEm ? \Carbon\Carbon::parse($atualizadoEm)->diffForHumans() : '—';
                    } catch (\Throwable $e) {
                      $atualizadoTexto = '—';
                    }
                  @endphp
                  {{ $atualizadoTexto }}</td>
                <td>
                  <a href="{{ route('segmentos.show', $segmento->segmento_cliente_id ?? $segmento) }}" class="btn btn-ghost btn-sm">Abrir</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="empty-state" style="padding:32px 16px;">
          <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
          </div>
          <h3>Nenhum segmento ainda</h3>
          <p>Crie seu primeiro segmento com IA para começar.</p>
          <a href="{{ route('segmentos.create') }}" class="btn btn-primary btn-sm">Criar agora</a>
        </div>
      @endif
    </div>

    {{-- Coluna lateral --}}
    <div style="display:flex;flex-direction:column;gap:16px;">

      {{-- Status MVP --}}
      <div class="card">
        <div class="card-title">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
          Status do sistema
        </div>
        <div style="display:flex;flex-direction:column;gap:10px;">
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.84rem;">
            <span class="text-muted">Motor de IA</span>
            <span class="badge badge-success"><span class="badge-dot"></span>Ativo</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.84rem;">
            <span class="text-muted">Geração de SQL</span>
            <span class="badge badge-success"><span class="badge-dot"></span>Ativo</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.84rem;">
            <span class="text-muted">Prévia de clientes</span>
            <span class="badge badge-success"><span class="badge-dot"></span>Ativo</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;font-size:.84rem;">
            <span class="text-muted">Exportação CSV</span>
            <span class="badge badge-success"><span class="badge-dot"></span>Ativo</span>
          </div>
        </div>
      </div>

      {{-- Atalhos --}}
      <div class="card">
        <div class="card-title">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          Atalhos rápidos
        </div>
        <div style="display:flex;flex-direction:column;gap:6px;">
          <a href="{{ route('segmentos.create') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Novo segmento com IA
          </a>
          <a href="{{ route('segmentos.presets') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            Usar modelo pronto
          </a>
          <a href="{{ route('clientes.index') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Gerenciar clientes
          </a>
          <a href="{{ route('clientes.importForm') }}" class="btn btn-secondary btn-sm" style="justify-content:flex-start;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            Importar CSV
          </a>
        </div>
      </div>

    </div>
  </div>

</div>
@endsection
