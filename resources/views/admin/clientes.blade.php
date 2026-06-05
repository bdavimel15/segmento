@extends('layouts.app')
@section('title', 'Clientes — Admin')

@section('content')
<div class="admin-shell">
  <div class="page-header">
    <h1>Clientes</h1>
    <a href="{{ route('admin.index') }}" class="btn btn-secondary">Voltar</a>
  </div>
  <div class="table-wrapper">
    <table class="data-table">
      <thead><tr><th>ID</th><th>Nome</th><th>E-mail</th><th>Telefone</th></tr></thead>
      <tbody>
        @foreach($clientes as $c)
          <tr>
            <td>{{ $c->cliente_id }}</td>
            <td>{{ $c->cli_nome }}</td>
            <td>{{ $c->cli_email ?? '—' }}</td>
            <td>{{ $c->cli_telefone ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endsection
