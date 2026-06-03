@extends('layouts.app')
@section('title', 'Editar — ' . $cliente->cli_nome)

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Editar cliente</h1>
    <p>{{ $cliente->cli_nome }}</p>
  </div>
  <a href="{{ route('clientes.index') }}" class="btn btn-ghost">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Voltar
  </a>
</div>

<div class="page-body">
  <div style="max-width:820px;">
    <form method="POST" action="{{ route('clientes.update', $cliente->cliente_id ?? $cliente) }}">
      @csrf @method('PUT')

      <div class="card">

        {{-- Dados pessoais --}}
        <div class="form-section">
          <div class="form-section-title">Dados pessoais</div>
          <div class="form-grid">
            <div class="form-group" style="grid-column:1/-1;">
              <label class="form-label" for="nome">Nome completo <span class="required">*</span></label>
              <input type="text" name="cli_nome" id="cli_nome" class="form-control"
                     value="{{ old('cli_nome', $cliente->cli_nome) }}" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="cpf">CPF</label>
              <input type="text" name="cli_cpf" id="cli_cpf" class="form-control"
                     value="{{ old('cli_cpf', $cliente->cli_cpf) }}" maxlength="14">
            </div>
            <div class="form-group">
              <label class="form-label" for="sexo">Sexo</label>
              <select name="sexo_id" id="sexo_id" class="form-control">
                <option value="">Não informado</option>
                <option value="M" {{ old('sexo_id', $cliente->sexo_id) === 'M' ? 'selected' : '' }}>Masculino</option>
                <option value="F" {{ old('sexo_id', $cliente->sexo_id) === 'F' ? 'selected' : '' }}>Feminino</option>
                <option value="O" {{ old('sexo_id', $cliente->sexo_id) === 'O' ? 'selected' : '' }}>Outro</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label" for="nascimento">Data de nascimento</label>
              <input type="date" name="cli_data_nascimento" id="cli_data_nascimento" class="form-control"
                     value="{{ old('cli_data_nascimento', $cliente->cli_data_nascimento ? \Carbon\Carbon::parse($cliente->cli_data_nascimento)->format('Y-m-d') : '') }}">
            </div>
          </div>
        </div>

        {{-- Contato --}}
        <div class="form-section">
          <div class="form-section-title">Contato</div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="telefone">Telefone</label>
              <input type="text" name="cli_telefone" id="cli_telefone" class="form-control"
                     value="{{ old('cli_telefone', $cliente->cli_telefone) }}">
            </div>
            <div class="form-group">
              <label class="form-label" for="email">E-mail</label>
              <input type="email" name="cli_email" id="cli_email" class="form-control"
                     value="{{ old('cli_email', $cliente->cli_email) }}">
            </div>
          </div>
        </div>

        {{-- Comportamento --}}
        <div class="form-section">
          <div class="form-section-title">Comportamento de compra</div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="qtd_pedidos">Qtd. de pedidos</label>
              <input type="number" name="cli_qtd_pedidos" id="cli_qtd_pedidos" class="form-control"
                     value="{{ old('cli_qtd_pedidos', $cliente->cli_qtd_pedidos ?? 0) }}" min="0">
            </div>
            <div class="form-group">
              <label class="form-label" for="ultimo_pedido">Último pedido</label>
              <input type="date" name="ultimo_pedido" id="ultimo_pedido" class="form-control"
                     value="{{ old('ultimo_pedido', $cliente->ultimo_pedido ? \Carbon\Carbon::parse($cliente->ultimo_pedido)->format('Y-m-d') : '') }}">
            </div>
            <div class="form-group">
              <label class="form-label" for="proxima_compra">Próxima compra prevista</label>
              <input type="date" name="cli_proxima_compra" id="cli_proxima_compra" class="form-control"
                     value="{{ old('cli_proxima_compra', $cliente->cli_proxima_compra ? \Carbon\Carbon::parse($cliente->cli_proxima_compra)->format('Y-m-d') : '') }}">
            </div>
          </div>
        </div>

        {{-- Fidelidade --}}
        <div class="form-section">
          <div class="form-section-title">Fidelidade</div>
          <div class="form-grid">
            <div class="form-group">
              <label class="form-label" for="cashback">Saldo de cashback (R$)</label>
              <input type="number" name="cashback" id="cashback" class="form-control"
                     value="{{ old('cashback', $cliente->cashback ?? 0) }}" min="0" step="0.01">
            </div>
            <div class="form-group">
              <label class="form-label" for="pontos">Pontos totais</label>
              <input type="number" name="cli_pontos_totais" id="cli_pontos_totais" class="form-control"
                     value="{{ old('cli_pontos_totais', $cliente->cli_pontos_totais ?? 0) }}" min="0">
            </div>
            <div class="form-group">
              <label class="form-label">Newsletter</label>
              <div class="radio-group">
                <label class="radio-option">
                  <input type="radio" name="cli_newsletter" value="S" {{ old('cli_newsletter', $cliente->cli_newsletter) == 'S' ? 'checked' : '' }}>
                  Inscrito
                </label>
                <label class="radio-option">
                  <input type="radio" name="cli_newsletter" value="N" {{ old('cli_newsletter', $cliente->cli_newsletter) == 'N' ? 'checked' : '' }}>
                  Não inscrito
                </label>
              </div>
            </div>
          </div>
        </div>

        <div class="sep"></div>
        <div class="btn-group">
          <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Salvar alterações
          </button>
          <a href="{{ route('clientes.index') }}" class="btn btn-ghost">Cancelar</a>
        </div>

      </div>
    </form>
  </div>
</div>
@endsection
