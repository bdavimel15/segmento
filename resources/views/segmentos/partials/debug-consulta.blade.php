@if($segmentadorDevMode ?? false)
<div class="debug-panel">
  <div class="debug-panel-title">Como o sistema executou a consulta</div>
  <div class="debug-metrics">
    <div class="debug-metric">
      <span class="debug-metric-value">{{ number_format($previewData['analisados'] ?? $previewData['total'] ?? 0) }}</span>
      <span class="debug-metric-label">Analisados</span>
    </div>
    <div class="debug-metric">
      <span class="debug-metric-value">{{ number_format($previewData['aprovados'] ?? $previewData['total'] ?? 0) }}</span>
      <span class="debug-metric-label">Aprovados</span>
    </div>
    <div class="debug-metric">
      <span class="debug-metric-value">{{ $previewData['tempo_ms'] ?? '—' }} ms</span>
      <span class="debug-metric-label">Tempo</span>
    </div>
  </div>
  <details class="debug-details">
    <summary>JSON gerado</summary>
    <pre class="pre">{{ json_encode($segmento->regra_json ?? [], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
  </details>
  <details class="debug-details">
    <summary>SQL gerada</summary>
    <pre class="pre">{{ $sqlData['sql'] ?? '--' }}</pre>
  </details>
</div>
@endif
