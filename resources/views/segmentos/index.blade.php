@extends('layouts.app')
@section('title', 'Segmentos')

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Segmentos</h1>
    <p>Gerencie todos os grupos de clientes criados por IA ou manualmente.</p>
  </div>
  <div class="btn-group">
    <a href="{{ route('segmentos.presets') }}" class="btn btn-secondary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      Modelos prontos
    </a>
    <a href="{{ route('segmentos.create') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Novo segmento
    </a>
  </div>
</div>

<div class="page-body">

  @if(isset($segmentos) && $segmentos->count())
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>Status</th>
            <th>Origem</th>
            <th>Última prévia</th>
            <th>Atualizado</th>
            <th style="text-align:right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @foreach($segmentos as $segmento)
          <tr>
            <td>
              <div style="font-weight:500;color:var(--text);">{{ $segmento->nome }}</div>
              @if($segmento->descricao)
                <div class="td-muted" style="font-size:.77rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                  {{ $segmento->descricao }}
                </div>
              @endif
            </td>
            <td>
              @php
                $statusMap = [
                  'validada'  => ['badge-success', 'Validada'],
                  'validado'  => ['badge-success', 'Validado'],
                  'reprovada' => ['badge-danger',  'Reprovada'],
                  'reprovado' => ['badge-danger',  'Reprovado'],
                  'pendente_validacao'  => ['badge-warning', 'Aguardando validação'],
                  'pendente'  => ['badge-warning', 'Pendente'],
                  'rascunho'  => ['badge-neutral', 'Rascunho'],
                ];
                [$cls, $label] = $statusMap[$segmento->status_validacao] ?? ['badge-neutral', ucfirst($segmento->status_validacao ?? '—')];
              @endphp
              <span class="badge {{ $cls }}">
                <span class="badge-dot"></span>
                {{ $label }}
              </span>
            </td>
            <td>
              @php
                $origemLabels = ['ia' => 'Criado com IA', 'manual' => 'Manual', 'preset' => 'Modelo pronto'];
              @endphp
              <span class="badge badge-purple">{{ $origemLabels[$segmento->origem] ?? ucfirst($segmento->origem ?? '—') }}</span>
            </td>
            <td>
              @if($segmento->ultima_previa_qtd !== null)
                <span style="font-weight:600;">{{ number_format($segmento->ultima_previa_qtd) }}</span>
                <span class="td-muted text-xs"> clientes</span>
              @else
                <span class="td-muted">Sem prévia</span>
              @endif
            </td>
            <td class="td-muted text-sm">
              @php
                $atualizadoEm = $segmento->atualizado ?? $segmento->updated_at ?? $segmento->cadastrado ?? null;
                try { $atualizadoTexto = $atualizadoEm ? \Carbon\Carbon::parse($atualizadoEm)->format('d/m/Y H:i') : '—'; } catch (\Throwable $e) { $atualizadoTexto = '—'; }
              @endphp
              {{ $atualizadoTexto }}
            </td>
            <td>
              <div class="td-actions" style="justify-content:flex-end;">
                <a href="{{ route('segmentos.show', $segmento->segmento_cliente_id ?? $segmento) }}" class="btn btn-secondary btn-sm">Abrir</a>
                <a href="{{ route('segmentos.edit', $segmento->segmento_cliente_id ?? $segmento) }}" class="btn btn-ghost btn-sm">Editar</a>
                <form method="POST" action="{{ route('segmentos.destroy', $segmento->segmento_cliente_id ?? $segmento) }}"
                      onsubmit="return confirm('Excluir este segmento?')" style="display:inline;">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-danger btn-sm">Excluir</button>
                </form>
              </div>
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>

    @if(method_exists($segmentos, 'links'))
      <div class="pagination-wrap">{{ $segmentos->links() }}</div>
    @endif

  @else
    <div class="card">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
        </div>
        <h3>Nenhum segmento criado ainda</h3>
        <p>Use a IA para descrever o público que quer atingir, ou monte as condições manualmente.</p>
        <div class="btn-group" style="justify-content:center;">
          <a href="{{ route('segmentos.create') }}" class="btn btn-primary">Criar com IA</a>
          <a href="{{ route('segmentos.presets') }}" class="btn btn-secondary">Usar modelo pronto</a>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection
