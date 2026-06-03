@extends('layouts.app')
@section('title', 'Importar Clientes')

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Importar clientes</h1>
    <p>Carregue um arquivo CSV para adicionar ou atualizar clientes em lote.</p>
  </div>
  <a href="{{ route('clientes.index') }}" class="btn btn-ghost">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Voltar
  </a>
</div>

<div class="page-body">
  <div style="max-width:760px;">

    <form method="POST" action="{{ route('clientes.import') }}" enctype="multipart/form-data" id="import-form">
      @csrf

      {{-- Upload area --}}
      <div class="card mb-24">
        <div class="card-title">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          Selecionar arquivo
        </div>

        <div class="upload-area" id="upload-area">
          <input type="file" name="arquivo" id="arquivo" accept=".csv,.txt"
                 style="display:none;" required onchange="handleFileSelect(this)">
          <div class="upload-area-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <h3 id="upload-label">Clique ou arraste o arquivo CSV aqui</h3>
          <p>Formato aceito: .csv ou .txt separado por vírgula</p>
        </div>

        <div class="sep"></div>

        <button type="submit" class="btn btn-primary" style="width:100%;">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
          Importar clientes
        </button>
      </div>

    </form>

    {{-- Resultado --}}
    @if(isset($resultado))
    <div class="card mb-24">
      <div class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Resultado da importação
      </div>
      <div class="metrics-grid" style="grid-template-columns:repeat(3,1fr);">
        <div class="metric-card">
          <div class="metric-card-value" style="color:var(--success);">{{ $resultado['importados'] ?? 0 }}</div>
          <div class="metric-card-label">Importados</div>
        </div>
        <div class="metric-card">
          <div class="metric-card-value" style="color:var(--warning);">{{ $resultado['atualizados'] ?? 0 }}</div>
          <div class="metric-card-label">Atualizados</div>
        </div>
        <div class="metric-card">
          <div class="metric-card-value" style="color:var(--danger);">{{ $resultado['erros'] ?? 0 }}</div>
          <div class="metric-card-label">Com erro</div>
        </div>
      </div>
      @if(!empty($resultado['linhas_com_erro']))
        <div class="mt-16">
          <div class="code-label" style="color:var(--danger);">Linhas com problema</div>
          <div class="code-block" style="font-size:.75rem;">{{ implode("\n", $resultado['linhas_com_erro']) }}</div>
        </div>
      @endif
    </div>
    @endif

    {{-- Guia de formato --}}
    <div class="card">
      <div class="card-title">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Formato do arquivo
      </div>

      <p style="font-size:.86rem;color:var(--text-muted);margin-bottom:14px;">
        O arquivo deve ter cabeçalho na primeira linha com as colunas abaixo. A ordem importa.
      </p>

      <div class="table-wrapper" style="margin-bottom:16px;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Coluna</th>
              <th>Obrigatório</th>
              <th>Exemplo</th>
            </tr>
          </thead>
          <tbody>
            <tr><td><code>nome</code></td><td><span class="badge badge-danger" style="font-size:.7rem;">Sim</span></td><td class="td-muted">Maria Oliveira</td></tr>
            <tr><td><code>cpf</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">123.456.789-00</td></tr>
            <tr><td><code>sexo</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">F</td></tr>
            <tr><td><code>telefone</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">(11) 99999-0000</td></tr>
            <tr><td><code>email</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">maria@email.com</td></tr>
            <tr><td><code>nascimento</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">1990-05-20</td></tr>
            <tr><td><code>newsletter</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">1 ou 0</td></tr>
            <tr><td><code>qtd_pedidos</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">5</td></tr>
            <tr><td><code>ultimo_pedido</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">2024-01-10</td></tr>
            <tr><td><code>proxima_compra</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">2024-02-15</td></tr>
            <tr><td><code>cashback</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">25.50</td></tr>
            <tr><td><code>pontos</code></td><td><span class="badge badge-neutral" style="font-size:.7rem;">Não</span></td><td class="td-muted">300</td></tr>
          </tbody>
        </table>
      </div>

      <div class="code-label">Exemplo de arquivo CSV</div>
      <div class="code-block">nome,cpf,sexo,telefone,email,nascimento,newsletter,qtd_pedidos,ultimo_pedido,proxima_compra,cashback,pontos
Maria Oliveira,123.456.789-00,F,(11) 99999-0000,maria@email.com,1990-05-20,1,5,2024-01-10,2024-02-15,25.50,300
João Silva,987.654.321-00,M,(21) 98888-1111,joao@email.com,1985-11-03,0,2,2024-01-05,,0,50</div>
    </div>

  </div>
</div>

@push('scripts')
<script>
function handleFileSelect(input) {
  const label = document.getElementById('upload-label');
  if (input.files && input.files[0]) {
    label.textContent = input.files[0].name;
  }
}
</script>
@endpush
@endsection
