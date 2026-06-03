@extends('layouts.app')
@section('title', $segmento->nome)

@section('content')
@php
    $statusClasses = [
        'validada' => 'badge-success',
        'pendente_validacao' => 'badge-warning',
        'rascunho' => 'badge-neutral',
        'reprovada' => 'badge-danger',
        'erro' => 'badge-danger',
        'inativa' => 'badge-neutral',
    ];
    $statusLabels = [
        'validada' => 'Validado',
        'pendente_validacao' => 'Aguardando validação',
        'rascunho' => 'Rascunho',
        'reprovada' => 'Reprovado',
        'erro' => 'Com erro',
        'inativa' => 'Inativo',
    ];
    $origemLabels = [
        'ia' => 'Criado com IA',
        'manual' => 'Montado manualmente',
        'preset' => 'Modelo pronto',
    ];
    $canalLabels = [
        'preview' => 'Prévia',
        'exportacao' => 'Exportação',
    ];
    $badge = $statusClasses[$segmento->status_validacao] ?? 'badge-neutral';
    $statusLabel = $statusLabels[$segmento->status_validacao] ?? ucfirst(str_replace('_', ' ', $segmento->status_validacao));
    $origemLabel = $origemLabels[$segmento->origem ?? 'manual'] ?? ucfirst($segmento->origem ?? 'manual');
    $previewOk = (bool)($previewData['ok'] ?? false);
    $exemplos = $previewData['exemplos'] ?? [];
    $colunasPreferidas = [
        'cliente_id', 'cli_nome', 'cli_cpf', 'sexo_id', 'cli_telefone', 'cli_email',
        'cli_data_nascimento', 'idade', 'cli_qtd_pedidos', 'qtd_pedidos', 'ultimo_pedido',
        'cli_newsletter', 'cli_proxima_compra', 'cashback', 'cli_pontos_totais', 'cadastrado'
    ];
    $precisaValidar = $segmento->status_validacao !== 'validada';
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $segmento->nome }}</h1>
    <p>{{ $segmento->descricao ?: ($segmento->resumo_humano ?: 'Grupo dinâmico de clientes.') }}</p>
    <div class="btn-group mt-8">
      <span class="badge {{ $badge }}"><span class="badge-dot"></span>{{ $statusLabel }}</span>
      <span class="badge badge-purple">{{ $origemLabel }}</span>
    </div>
  </div>
  <div class="btn-group">
    <a href="{{ route('segmentos.edit', $segmento->segmento_cliente_id) }}" class="btn btn-secondary">Editar</a>
    <a href="{{ route('segmentos.index') }}" class="btn btn-ghost">Voltar</a>
  </div>
</div>

<div class="page-body">
  <div class="flow-guide mb-24">
    <div class="flow-guide-title">Como usar este segmento</div>
    <ol class="flow-steps">
      <li class="{{ $previewOk ? 'done' : 'current' }}"><span>1</span> Confira a prévia de clientes abaixo</li>
      <li class="{{ !$precisaValidar ? 'done' : ($previewOk ? 'current' : '') }}"><span>2</span> Valide o grupo para liberar exportações</li>
      <li class="{{ !$precisaValidar && $previewOk ? 'current' : '' }}"><span>3</span> Exporte contatos (CSV, telefones ou e-mails)</li>
    </ol>
    @if($precisaValidar)
      <p class="flow-guide-hint">Este segmento ainda precisa ser validado antes de exportar contatos.</p>
    @else
      <p class="flow-guide-hint">Segmento validado. Você pode exportar CSV ou copiar telefones e e-mails da prévia.</p>
    @endif
  </div>

  <div class="metrics-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
    <div class="metric-card"><div class="metric-card-value">{{ number_format($previewData['total'] ?? $segmento->ultima_previa_qtd ?? 0) }}</div><div class="metric-card-label">Clientes encontrados</div></div>
    <div class="metric-card"><div class="metric-card-value" style="font-size:1rem">{{ $segmento->ultima_previa_em ? \Carbon\Carbon::parse($segmento->ultima_previa_em)->format('d/m/Y H:i') : '—' }}</div><div class="metric-card-label">Última prévia</div></div>
    <div class="metric-card"><div class="metric-card-value" style="font-size:1rem">{{ $segmento->atualizado ? \Carbon\Carbon::parse($segmento->atualizado)->format('d/m/Y H:i') : '—' }}</div><div class="metric-card-label">Atualizado em</div></div>
  </div>

  <div class="card mb-24">
    <div class="card-title">O que você pode fazer agora</div>

    <div class="action-group">
      <div class="action-group-title">Prévia</div>
      <p class="action-help">Atualize a lista de clientes com base nas regras atuais do segmento.</p>
      <form method="POST" action="{{ route('segmentos.refreshPreview', $segmento->segmento_cliente_id) }}">
        @csrf
        <button class="btn btn-secondary" type="submit">Atualizar prévia de clientes</button>
      </form>
    </div>

    @if($precisaValidar)
    <div class="action-group">
      <div class="action-group-title">Validação</div>
      <p class="action-help">Confirme que a prévia está correta para liberar exportações.</p>
      <form method="POST" action="{{ route('segmentos.validar', $segmento->segmento_cliente_id) }}">
        @csrf
        <button class="btn btn-primary" type="submit">Aprovar segmento</button>
      </form>
    </div>
    @endif

    <div class="action-group">
      <div class="action-group-title">Exportar contatos</div>
      <p class="action-help">Baixe ou copie os contatos da prévia atual (até o limite configurado no segmento).</p>
      <div class="btn-group">
        <a href="{{ route('segmentos.exportar', ['id' => $segmento->segmento_cliente_id, 'tipo' => 'csv']) }}" class="btn btn-secondary">Baixar CSV</a>
        <a href="{{ route('segmentos.exportar', ['id' => $segmento->segmento_cliente_id, 'tipo' => 'telefones']) }}" target="_blank" class="btn btn-secondary" title="Abre lista de telefones para copiar">Copiar telefones</a>
        <a href="{{ route('segmentos.exportar', ['id' => $segmento->segmento_cliente_id, 'tipo' => 'emails']) }}" target="_blank" class="btn btn-secondary" title="Abre lista de e-mails para copiar">Copiar e-mails</a>
      </div>
    </div>
  </div>

  <div class="layout-split">
    <div class="layout-split-main">
      <div class="card">
        <div class="section-header">
          <span class="card-title">Prévia dos clientes</span>
          <span class="badge badge-purple">{{ number_format($previewData['total'] ?? 0) }} encontrados</span>
        </div>

        @include('segmentos.partials.resumo-segmento')

        @if(!$previewOk)
          <div class="error-friendly mb-16">
            <div class="error-friendly-title">Não foi possível gerar a prévia</div>
            <div class="error-friendly-msg">{{ $previewData['mensagem'] ?? 'Verifique a regra e tente novamente.' }}</div>
            @if(!empty($previewData['erro']))
              <details><summary>Ver detalhes técnicos</summary><div class="code-block">{{ $previewData['erro'] }}</div></details>
            @endif
          </div>
        @endif

        @include('segmentos.partials.preview-explicacao')

        @if(empty($previewData['explicacoes']) && $previewOk && empty($exemplos))
          <div class="empty-state" style="padding:28px 16px;">
            <h3>Nenhum cliente encontrado</h3>
            <p>Ajuste as regras do segmento ou clique em "Atualizar prévia de clientes".</p>
          </div>
        @elseif(empty($previewData['explicacoes']) && !empty($exemplos))
          <p class="form-hint mt-8">Mostrando até 10 clientes. Exporte para ver a lista completa dentro do limite.</p>
        @elseif(!empty($previewData['explicacoes']))
          <p class="form-hint mt-8">Clique em <strong>Detalhes</strong> para ver regra a regra por que cada cliente entrou no segmento.</p>
        @endif

        @include('segmentos.partials.debug-consulta')
      </div>

      <div class="card">
        <div class="card-title">Histórico de execuções</div>
        <div class="table-wrapper table-wrapper-flat">
          <table class="data-table">
            <thead><tr><th>Ação</th><th>Status</th><th>Total</th><th>Data</th></tr></thead>
            <tbody>
            @forelse($execucoes as $e)
              <tr>
                <td>{{ $canalLabels[$e->canal] ?? ucfirst($e->canal) }}</td>
                <td><span class="badge badge-neutral">{{ $e->status === 'concluida' ? 'Concluída' : ucfirst($e->status) }}</span></td>
                <td>{{ $e->total_encontrado }}</td>
                <td class="td-muted">{{ $e->executado_em ?? $e->cadastrado }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="td-muted">Nenhuma execução registrada ainda.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card">
        <div class="card-title">Histórico de validações</div>
        <div class="table-wrapper table-wrapper-flat">
          <table class="data-table">
            <thead><tr><th>Status anterior</th><th>Novo status</th><th>Observação</th><th>Data</th></tr></thead>
            <tbody>
            @forelse($validacoes as $v)
              <tr>
                <td>{{ $statusLabels[$v->status_anterior] ?? ($v->status_anterior ?? '—') }}</td>
                <td><span class="badge badge-neutral">{{ $statusLabels[$v->status_novo] ?? $v->status_novo }}</span></td>
                <td>{{ $v->observacao ?? '—' }}</td>
                <td class="td-muted">{{ $v->cadastrado }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="td-muted">Nenhuma validação registrada.</td></tr>
            @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="layout-split-side">
      <div class="card">
        <div class="card-title">Reprovar ou excluir</div>
        <p class="action-help">Reprovar marca o segmento como inválido. Excluir remove da listagem.</p>
        <form method="POST" action="{{ route('segmentos.reprovar', $segmento->segmento_cliente_id) }}" class="mt-8">
          @csrf
          <div class="form-group">
            <label class="form-label" for="motivo">Motivo da reprovação</label>
            <input name="motivo" id="motivo" class="form-control" type="text" placeholder="Ex: Prévia com clientes incorretos">
          </div>
          <button class="btn btn-danger btn-block mt-8" type="submit">Reprovar segmento</button>
        </form>
        <form method="POST" action="{{ route('segmentos.destroy', $segmento->segmento_cliente_id) }}" onsubmit="return confirm('Excluir este segmento permanentemente?')" class="mt-12">
          @csrf @method('DELETE')
          <button class="btn btn-ghost btn-block" type="submit">Excluir segmento</button>
        </form>
      </div>

      <details class="accordion">
        <summary>Detalhes técnicos (JSON e SQL)</summary>
        <div class="accordion-body">
          <div class="code-label">Regra JSON</div>
          <div class="code-block">{{ json_encode($segmento->regra_json, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</div>
          <div class="code-label mt-8">SQL gerada</div>
          <div class="code-block">{{ $sqlData['sql'] ?? '--' }}</div>
        </div>
      </details>
    </div>
  </div>
</div>

@include('segmentos.partials.drawer-modal')

@push('scripts')
<script src="{{ asset('js/segmentador.js') }}"></script>
<script>
window.lastPreviewData = { preview: @json($previewData ?? []) };
document.querySelectorAll('.btn-explicacao-detalhe').forEach(btn => {
  btn.addEventListener('click', () => Segmentador.openDrawer(parseInt(btn.dataset.expIndex, 10)));
});
</script>
@endpush
@endsection
