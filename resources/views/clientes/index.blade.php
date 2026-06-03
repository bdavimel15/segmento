@extends('layouts.app')
@section('title', 'Clientes')

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Clientes</h1>
    <p>Base completa de clientes cadastrados no sistema.</p>
  </div>
  <div class="btn-group">
    <a href="{{ route('clientes.exportCsv') }}" class="btn btn-secondary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
      Exportar CSV
    </a>
    <a href="{{ route('clientes.importForm') }}" class="btn btn-secondary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      Importar clientes
    </a>
    <a href="{{ route('clientes.create') }}" class="btn btn-primary">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
      Novo cliente
    </a>
  </div>
</div>

<div class="page-body">

  @if(isset($clientes) && $clientes->count())
    <div class="table-wrapper">
      <table class="data-table">
        <thead>
          <tr>
            <th>Nome</th>
            <th>CPF</th>
            <th>Sexo</th>
            <th>Telefone</th>
            <th>E-mail</th>
            <th>Nascimento</th>
            <th>Idade</th>
            <th>Pedidos</th>
            <th>Último pedido</th>
            <th>Próx. compra</th>
            <th style="text-align:center;">News.</th>
            <th>Cashback</th>
            <th>Pontos</th>
            <th style="text-align:right;">Ações</th>
          </tr>
        </thead>
        <tbody>
          @foreach($clientes as $cliente)
          <tr>
            <td>
              <div style="font-weight:500;min-width:140px;">{{ $cliente->cli_nome }}</div>
            </td>
            <td class="td-muted" style="font-family:'DM Mono',monospace;font-size:.78rem;">
              {{ $cliente->cli_cpf ? \Str::mask($cliente->cli_cpf, '*', 3, 6) : '—' }}
            </td>
            <td class="td-muted">{{ $cliente->sexo_texto ?? '—' }}</td>
            <td class="td-muted" style="font-family:'DM Mono',monospace;font-size:.78rem;">{{ $cliente->cli_telefone ?? '—' }}</td>
            <td class="td-muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;">{{ $cliente->cli_email ?? '—' }}</td>
            <td class="td-muted text-sm">
              {{ $cliente->cli_data_nascimento ? \Carbon\Carbon::parse($cliente->cli_data_nascimento)->format('d/m/Y') : '—' }}
            </td>
            <td style="text-align:center;">
              {{ $cliente->cli_data_nascimento ? \Carbon\Carbon::parse($cliente->cli_data_nascimento)->age : '—' }}
            </td>
            <td style="text-align:center;font-weight:600;">{{ $cliente->cli_qtd_pedidos ?? 0 }}</td>
            <td class="td-muted text-sm">
              {{ $cliente->ultimo_pedido ? \Carbon\Carbon::parse($cliente->ultimo_pedido)->format('d/m/Y') : '—' }}
            </td>
            <td class="td-muted text-sm">
              {{ $cliente->cli_proxima_compra ? \Carbon\Carbon::parse($cliente->cli_proxima_compra)->format('d/m/Y') : '—' }}
            </td>
            <td style="text-align:center;">
              @if(($cliente->cli_newsletter === 'S'))
                <span class="badge badge-success" style="font-size:.7rem;">Sim</span>
              @else
                <span class="badge badge-neutral" style="font-size:.7rem;">Não</span>
              @endif
            </td>
            <td>
              @if($cliente->cashback)
                <span style="font-weight:500;color:var(--success);">R$ {{ number_format($cliente->cashback, 2, ',', '.') }}</span>
              @else
                <span class="td-muted">—</span>
              @endif
            </td>
            <td>
              @if($cliente->cli_pontos_totais)
                <span style="font-weight:500;color:var(--purple);">{{ number_format($cliente->cli_pontos_totais) }}</span>
              @else
                <span class="td-muted">—</span>
              @endif
            </td>
            <td>
              <div class="td-actions" style="justify-content:flex-end;">
                <a href="{{ route('clientes.edit', $cliente->cliente_id ?? $cliente) }}" class="btn btn-ghost btn-sm">Editar</a>
                <form method="POST" action="{{ route('clientes.destroy', $cliente->cliente_id ?? $cliente) }}"
                      onsubmit="return confirm('Excluir este cliente?')" style="display:inline;">
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

    @if(method_exists($clientes, 'links'))
      <div class="pagination-wrap">{{ $clientes->links() }}</div>
    @endif

  @else
    <div class="card">
      <div class="empty-state">
        <div class="empty-state-icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </div>
        <h3>Nenhum cliente cadastrado</h3>
        <p>Importe uma planilha CSV ou cadastre clientes manualmente para começar.</p>
        <div class="btn-group" style="justify-content:center;">
          <a href="{{ route('clientes.importForm') }}" class="btn btn-primary">Importar CSV</a>
          <a href="{{ route('clientes.create') }}" class="btn btn-secondary">Cadastrar manualmente</a>
        </div>
      </div>
    </div>
  @endif

</div>
@endsection
