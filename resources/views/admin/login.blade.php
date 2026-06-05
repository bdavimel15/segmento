@extends('layouts.app')
@section('title', 'Login Admin')

@section('content')
<div class="admin-shell" style="max-width:420px;">
  <div class="card">
    <h1 class="card-title">Acesso administrativo</h1>
    <p class="muted mb-16">Área exclusiva da equipe interna.</p>
    <form method="POST" action="{{ route('admin.login.submit') }}">
      @csrf
      <div class="form-group">
        <label class="form-label" for="password">Senha</label>
        <input type="password" name="password" id="password" class="form-control" required autofocus>
      </div>
      <button class="btn btn-primary btn-block mt-8" type="submit">Entrar</button>
    </form>
  </div>
</div>
@endsection
