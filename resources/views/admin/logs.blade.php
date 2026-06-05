@extends('layouts.app')
@section('title', 'Logs — Admin')

@section('content')
<div class="admin-shell">
  <div class="page-header">
    <h1>Logs do sistema</h1>
    <a href="{{ route('admin.index') }}" class="btn btn-secondary">Voltar</a>
  </div>
  <div class="card">
    <pre class="code-block" style="max-height:70vh;overflow:auto;font-size:.75rem;">{{ $tail }}</pre>
  </div>
</div>
@endsection
