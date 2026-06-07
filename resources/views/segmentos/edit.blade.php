@extends('layouts.app')
@section('title', 'Editar — ' . $segmento->nome)

@section('content')

<div class="page-header">
  <div class="page-header-left">
    <h1>Editar segmento</h1>
    <p>{{ $segmento->nome }}</p>
  </div>
  <a href="{{ route('segmentos.show', $segmento->segmento_cliente_id ?? $segmento) }}" class="btn btn-ghost">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
    Cancelar
  </a>
</div>

<div class="page-body">
  <div style="max-width:720px;">
    <form method="POST" action="{{ route('segmentos.update', $segmento->segmento_cliente_id ?? $segmento) }}">
      @csrf @method('PUT')

      <div class="card">
        <div class="form-section">
          <div class="form-section-title">Informações básicas</div>
          <div class="form-grid" style="grid-template-columns:1fr;">
            <div class="form-group">
              <label class="form-label" for="nome">Nome <span class="required">*</span></label>
              <input type="text" name="nome" id="nome" class="form-control"
                     value="{{ old('nome', $segmento->nome) }}" required>
            </div>
            <div class="form-group">
              <label class="form-label" for="descricao">Descrição</label>
              <textarea name="descricao" id="descricao" class="form-control" rows="3">{{ old('descricao', $segmento->descricao) }}</textarea>
            </div>
            <div class="form-group">
              <label class="form-label">Status atual</label>
              <p><span class="badge {{ \App\Support\SegmentadorUi::statusBadgeClass($segmento->status_validacao) }}">{{ \App\Support\SegmentadorUi::statusLabel($segmento->status_validacao) }}</span></p>
              <span class="form-hint">A aprovação é feita pela equipe interna.</span>
            </div>
          </div>
        </div>

        <div class="sep"></div>

        <details class="accordion" style="border:none;border-radius:0;margin:0;">
          <summary style="padding:0 0 12px;background:none;border-radius:0;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor" style="width:16px;height:16px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
            Editar regra JSON (avançado)
            <svg class="accordion-chevron" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
          </summary>
          <div style="padding-top:12px;">
            <div class="form-group">
              <label class="form-label">Regra JSON</label>
              <textarea name="regra_json" class="form-control" rows="10"
                        style="font-family:'DM Mono',monospace;font-size:.8rem;">{{ old('regra_json', $segmento->regra_json ? json_encode($segmento->regra_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '') }}</textarea>
              <span class="form-hint">Edite somente se souber o que está fazendo. Alterações aqui substituem a regra atual.</span>
            </div>
          </div>
        </details>

        <div class="sep"></div>

        <div class="btn-group">
          <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            Salvar alterações
          </button>
          <a href="{{ route('segmentos.show', $segmento->segmento_cliente_id ?? $segmento) }}" class="btn btn-ghost">Cancelar</a>
        </div>
      </div>

    </form>
  </div>
</div>
@endsection
