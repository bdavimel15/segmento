@extends('layouts.app')
@section('title', $segmento->nome)

@section('content')
@php
    use App\Support\SegmentadorUi;
    $statusLabel = SegmentadorUi::statusLabel($segmento->status_validacao);
    $badge = SegmentadorUi::statusBadgeClass($segmento->status_validacao);
    $canExport = SegmentadorUi::canExportSegment($segmento->status_validacao);
    $previewOk = (bool)($previewData['ok'] ?? false);
    $exemplos = $previewData['exemplos'] ?? [];
    $statusClass = match ($segmento->status_validacao) {
        'validada' => 'is-aprovado',
        'reprovada' => 'is-reprovado',
        'em_analise' => 'is-analise',
        default => 'is-pendente',
    };
@endphp

<div class="page-header">
  <div class="page-header-left">
    <h1>{{ $segmento->nome }}</h1>
    <p>{{ $segmento->resumo_humano ?: 'Segmento de clientes' }}</p>
    <div class="btn-group mt-8">
      <span class="badge {{ $badge }}"><span class="badge-dot"></span>{{ $statusLabel }}</span>
    </div>
  </div>
  <div class="btn-group">
    <a href="{{ route('segmentos.edit', $segmento->segmento_cliente_id) }}" class="btn btn-secondary">Editar</a>
    <a href="{{ route('segmentos.index') }}" class="btn btn-ghost">Voltar</a>
  </div>
</div>

<div class="page-body">
  <div class="status-banner {{ $statusClass }} mb-16">
    @if($segmento->status_validacao === 'validada')
      Segmento aprovado pela equipe. Você pode exportar os contatos abaixo.
    @elseif($segmento->status_validacao === 'reprovada')
      Segmento reprovado.@if($segmento->motivo_reprovacao) Motivo: {{ $segmento->motivo_reprovacao }}@endif
    @elseif($segmento->status_validacao === 'em_analise')
      Segmento em análise pela equipe interna.
    @else
      Segmento pendente de aprovação. Você pode editar e atualizar a prévia enquanto aguarda.
    @endif
  </div>

  <div class="metrics-grid mb-24" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));">
    <div class="metric-card">
      <div class="metric-card-value">{{ number_format($previewData['total'] ?? $segmento->ultima_previa_qtd ?? 0) }}</div>
      <div class="metric-card-label">Clientes encontrados</div>
    </div>
  </div>

  <div class="card mb-24">
    @include('segmentos.partials.resumo-segmento')

    <div class="section-header mt-16">
      <span class="card-title">Prévia dos clientes</span>
    </div>

    @if(!$previewOk)
      <div class="empty-state compact">
        <p>{{ $previewData['erro'] ?? 'Não foi possível gerar a prévia.' }}</p>
      </div>
    @elseif(empty($exemplos))
      <div class="empty-state compact">
        <p>Nenhum cliente encontrado para esta regra.</p>
      </div>
    @else
      <div class="table-wrapper mt-8">
        <table class="data-table preview-table-simple">
          <thead><tr><th>Cliente</th><th>Telefone</th><th>E-mail</th></tr></thead>
          <tbody>
            @foreach(array_slice($exemplos, 0, 25) as $row)
              <tr>
                <td><strong>{{ $row['cli_nome'] ?? $row['nome'] ?? '—' }}</strong></td>
                <td>{{ $row['cli_telefone'] ?? '—' }}</td>
                <td>{{ $row['cli_email'] ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif

    <div class="create-actions mt-16">
      <form method="POST" action="{{ route('segmentos.refreshPreview', $segmento->segmento_cliente_id) }}">
        @csrf
        <button class="btn btn-secondary" type="submit">Atualizar prévia</button>
      </form>
    </div>
  </div>

  @if($canExport)
  <div class="card mb-24">
    <div class="card-title">Exportar contatos</div>
    <p class="action-help">Baixe ou copie os contatos da prévia atual.</p>
    <div class="btn-group mt-8">
      <a href="{{ route('segmentos.exportar', ['id' => $segmento->segmento_cliente_id, 'tipo' => 'csv']) }}" class="btn btn-secondary">Baixar CSV</a>
      <a href="{{ route('segmentos.exportar', ['id' => $segmento->segmento_cliente_id, 'tipo' => 'telefones']) }}" target="_blank" class="btn btn-secondary">Copiar telefones</a>
      <a href="{{ route('segmentos.exportar', ['id' => $segmento->segmento_cliente_id, 'tipo' => 'emails']) }}" target="_blank" class="btn btn-secondary">Copiar e-mails</a>
    </div>
  </div>
  @endif

  @if($segmentadorDevMode ?? false)
    <p class="form-hint"><a href="{{ route('segmentos.tecnico', $segmento->segmento_cliente_id) }}">Abrir visão técnica</a></p>
  @endif
</div>
@endsection
