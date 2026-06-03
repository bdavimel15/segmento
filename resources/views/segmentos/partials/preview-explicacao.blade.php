@if(!empty($previewData['ok']) && !empty($previewData['explicacoes']))
<div class="table-wrapper">
  <table class="data-table preview-table">
    <thead>
      <tr>
        <th>Cliente</th>
        <th>E-mail</th>
        <th>Telefone</th>
        <th>Por que entrou?</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      @foreach($previewData['explicacoes'] as $idx => $exp)
        @php
          $row = $previewData['exemplos'][$idx] ?? [];
        @endphp
        <tr>
          <td><strong>{{ $exp['nome'] ?? ($row['cli_nome'] ?? 'Cliente') }}</strong></td>
          <td class="td-muted">{{ $row['cli_email'] ?? '—' }}</td>
          <td class="td-muted">{{ $row['cli_telefone'] ?? '—' }}</td>
          <td>
            <div class="motivos-cell">
              @forelse($exp['motivos_resumo'] ?? [] as $motivo)
                <span class="motivo-chip">✅ {{ $motivo }}</span>
              @empty
                —
              @endforelse
            </div>
          </td>
          <td>
            <button type="button" class="btn btn-ghost btn-sm btn-explicacao-detalhe" data-exp-index="{{ $idx }}">Detalhes</button>
          </td>
        </tr>
      @endforeach
    </tbody>
  </table>
</div>
@elseif($previewOk ?? false)
  <div class="empty-state compact"><p>Nenhum cliente encontrado com estas regras.</p></div>
@endif
