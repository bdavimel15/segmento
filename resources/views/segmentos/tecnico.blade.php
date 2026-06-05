@extends('layouts.app')
@section('title', 'Técnico — ' . $segmento->nome)

@section('content')
<div class="page-header">
  <div class="page-header-left">
    <h1>Visão técnica</h1>
    <p>{{ $segmento->nome }}</p>
  </div>
  <a href="{{ route('segmentos.show', $segmento->segmento_cliente_id) }}" class="btn btn-secondary">Voltar ao segmento</a>
</div>

<div class="page-body">
  @include('segmentos.partials.resumo-segmento')
  @include('segmentos.partials.debug-consulta')

  <div class="card mt-16">
    <div class="card-title">Logs de execução</div>
    <pre class="code-block">@json($logs ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)</pre>
  </div>

  <div class="card mt-16">
    <div class="card-title">Histórico de execuções</div>
    <div class="table-wrapper table-wrapper-flat">
      <table class="data-table">
        <thead><tr><th>Canal</th><th>Status</th><th>Total</th><th>Data</th></tr></thead>
        <tbody>
        @forelse($execucoes as $e)
          <tr>
            <td>{{ $e->canal }}</td>
            <td>{{ $e->status }}</td>
            <td>{{ $e->total_encontrado }}</td>
            <td>{{ $e->executado_em ?? $e->cadastrado }}</td>
          </tr>
        @empty
          <tr><td colspan="4">Nenhuma execução.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
