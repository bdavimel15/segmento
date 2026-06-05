@extends('layouts.app')
@section('title', 'Análise — ' . $segmento->nome)

@section('content')
@php use App\Support\SegmentadorUi; @endphp
<div class="admin-shell">
  <div class="page-header">
    <div class="page-header-left">
      <h1>{{ $segmento->nome }}</h1>
      <p>Análise técnica do segmento</p>
      <span class="badge {{ SegmentadorUi::statusBadgeClass($segmento->status_validacao) }}">{{ SegmentadorUi::statusLabel($segmento->status_validacao) }}</span>
    </div>
    <a href="{{ route('admin.index') }}" class="btn btn-secondary">Voltar</a>
  </div>

  @include('segmentos.partials.resumo-segmento')

  <div class="card mt-16">
    <div class="card-title">Prévia ({{ number_format($previewData['total'] ?? 0) }} clientes)</div>
    @if(empty($previewData['exemplos']))
      <p class="muted">Nenhum cliente encontrado.</p>
    @else
      <div class="table-wrapper">
        <table class="data-table preview-table-simple">
          <thead><tr><th>Cliente</th><th>Telefone</th><th>E-mail</th></tr></thead>
          <tbody>
            @foreach(array_slice($previewData['exemplos'], 0, 15) as $row)
              <tr>
                <td>{{ $row['cli_nome'] ?? '—' }}</td>
                <td>{{ $row['cli_telefone'] ?? '—' }}</td>
                <td>{{ $row['cli_email'] ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>

  @include('segmentos.partials.debug-consulta')

  <div class="card mt-16">
    <div class="card-title">Logs</div>
    <pre class="code-block">@json($logs ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)</pre>
  </div>

  <div class="card mt-16">
    <div class="card-title">Ações administrativas</div>
    <div class="btn-group">
      <form method="POST" action="{{ route('admin.aprovar', $segmento->segmento_cliente_id) }}">@csrf<button class="btn btn-success" type="submit">Aprovar segmento</button></form>
      <form method="POST" action="{{ route('admin.analise', $segmento->segmento_cliente_id) }}">@csrf<button class="btn btn-secondary" type="submit">Marcar em análise</button></form>
    </div>
    <form method="POST" action="{{ route('admin.reprovar', $segmento->segmento_cliente_id) }}" class="mt-16">
      @csrf
      <div class="form-group">
        <label class="form-label" for="motivo">Motivo da reprovação (obrigatório)</label>
        <input name="motivo" id="motivo" class="form-control" required placeholder="Descreva o motivo para o cliente">
      </div>
      <button class="btn btn-danger mt-8" type="submit">Reprovar segmento</button>
    </form>
    <form method="POST" action="{{ route('admin.destroy', $segmento->segmento_cliente_id) }}" class="mt-12" onsubmit="return confirm('Excluir segmento?')">
      @csrf @method('DELETE')
      <button class="btn btn-ghost" type="submit">Excluir segmento</button>
    </form>
  </div>
</div>
@endsection
