@extends('layouts.app')
@section('title', 'Admin')

@section('content')
<div class="admin-shell">
  <div class="page-header">
    <div class="page-header-left">
      <h1>Área administrativa</h1>
      <p>Aprovação interna de segmentos</p>
    </div>
    <form method="POST" action="{{ route('admin.logout') }}">@csrf<button class="btn btn-ghost" type="submit">Sair</button></form>
  </div>

  <nav class="admin-nav">
    <a href="{{ route('admin.index', ['status' => 'pendentes']) }}" class="btn btn-secondary btn-sm {{ ($filtro ?? '') === 'pendentes' ? 'active' : '' }}">Pendentes</a>
    <a href="{{ route('admin.index', ['status' => 'analise']) }}" class="btn btn-secondary btn-sm {{ ($filtro ?? '') === 'analise' ? 'active' : '' }}">Em análise</a>
    <a href="{{ route('admin.index', ['status' => 'aprovados']) }}" class="btn btn-secondary btn-sm {{ ($filtro ?? '') === 'aprovados' ? 'active' : '' }}">Aprovados</a>
    <a href="{{ route('admin.index', ['status' => 'reprovados']) }}" class="btn btn-secondary btn-sm {{ ($filtro ?? '') === 'reprovados' ? 'active' : '' }}">Reprovados</a>
    <a href="{{ route('admin.clientes') }}" class="btn btn-ghost btn-sm">Clientes</a>
    <a href="{{ route('admin.logs') }}" class="btn btn-ghost btn-sm">Logs</a>
  </nav>

  <div class="table-wrapper">
    <table class="data-table">
      <thead>
        <tr>
          <th>Nome</th>
          <th>Data</th>
          <th>Prévia</th>
          <th>Status</th>
          <th style="text-align:right;">Ações</th>
        </tr>
      </thead>
      <tbody>
        @forelse($segmentos as $segmento)
          <tr>
            <td><strong>{{ $segmento->nome }}</strong></td>
            <td class="td-muted">{{ $segmento->cadastrado ? \Carbon\Carbon::parse($segmento->cadastrado)->format('d/m/Y H:i') : '—' }}</td>
            <td>{{ number_format($segmento->ultima_previa_qtd ?? 0) }}</td>
            <td><span class="badge {{ \App\Support\SegmentadorUi::statusBadgeClass($segmento->status_validacao) }}">{{ \App\Support\SegmentadorUi::statusLabel($segmento->status_validacao) }}</span></td>
            <td class="td-actions" style="justify-content:flex-end;">
              <a href="{{ route('admin.show', $segmento->segmento_cliente_id) }}" class="btn btn-secondary btn-sm">Analisar</a>
            </td>
          </tr>
        @empty
          <tr><td colspan="5" class="td-muted">Nenhum segmento nesta lista.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
