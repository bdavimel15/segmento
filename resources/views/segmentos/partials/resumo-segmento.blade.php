@if(!empty($resumoRegra))
<div class="segment-summary">
  @if(($resumoRegra['origem'] ?? '') === 'ia' && !empty($resumoRegra['interpretacao_ia']))
    <div class="segment-summary-ia">
      <span class="segment-summary-label">Interpretação da IA</span>
      <p>{{ $resumoRegra['interpretacao_ia'] }}</p>
    </div>
  @elseif(!empty($resumoRegra['resumo_humano']) && ($segmento->origem ?? '') === 'ia')
    <div class="segment-summary-ia">
      <span class="segment-summary-label">Interpretação da IA</span>
      <p>{{ $resumoRegra['resumo_humano'] }}</p>
    </div>
  @endif

  <div class="segment-summary-label">Resumo das regras</div>
  <div class="segment-summary-body">
    @foreach($resumoRegra['grupos'] ?? [] as $gi => $grupo)
      @if(!empty($grupo['titulo']))
        <div class="segment-summary-group-title">{{ $grupo['titulo'] }}</div>
      @endif
      @foreach($grupo['condicoes'] ?? [] as $ci => $condicao)
        @if($ci > 0)
          <div class="segment-summary-logic">{{ strtoupper($grupo['logic'] ?? 'AND') === 'OR' ? 'OU' : 'E' }}</div>
        @endif
        <div class="segment-summary-rule">{{ $condicao }}</div>
      @endforeach
      @if($gi < count($resumoRegra['grupos']) - 1 && count($resumoRegra['grupos']) > 1)
        <div class="segment-summary-logic segment-summary-logic-top">{{ $resumoRegra['top_logic_label'] ?? 'E' }}</div>
      @endif
    @endforeach
  </div>
</div>
@endif
