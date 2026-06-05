@extends('layouts.app')
@section('title', 'Criar segmento')

@section('content')
<div class="page-body create-segmento-page">
<section class="page-head create-page-head">
    <div>
        <p class="kicker">Módulo de segmentação</p>
        <h1>Criar segmento de clientes</h1>
        <p class="lead">Descreva o público ou monte regras visuais. Gere a prévia antes de salvar.</p>
    </div>
</section>

<div class="flow-guide flow-guide-compact mb-16">
    <div class="flow-guide-title">Passo a passo</div>
    <ol class="flow-steps">
        <li class="current" id="step1"><span>1</span> Descreva ou monte o público</li>
        <li id="step2"><span>2</span> Confira a prévia</li>
        <li id="step3"><span>3</span> Salve o segmento</li>
    </ol>
</div>

<div id="stepCreatePanel" class="step-create-panel">
<div class="grid grid-create-segmento">
    <section class="card full-card create-main-card">
        <div class="tabs tabs-segmento">
            <button class="tab active" type="button" id="tabIa" onclick="setModo('ia')">✨ Escrever o que eu quero</button>
            <button class="tab" type="button" id="tabManual" onclick="setModo('manual')">🧩 Montar com regras</button>
        </div>

        <div class="form-group create-name-field">
            <label class="form-label" for="nome">Nome do segmento</label>
            <input id="nome" class="form-control" type="text" placeholder="Ex: Homens inativos há 30 dias">
        </div>

        <section id="boxIa">
            <div class="form-group">
                <label class="form-label" for="texto">Descreva o público em português</label>
                <textarea id="texto" class="form-control" rows="4" placeholder="Ex: Clientes que compraram hoje"></textarea>
            </div>
            <div class="help help-compact">
                <b>Exemplos rápidos</b>
                <div class="pill-row">
                    <span class="pill" onclick="usarExemplo('Clientes que compraram hoje')">Compraram hoje</span>
                    <span class="pill" onclick="usarExemplo('Clientes com mais pedidos confirmados')">Top 4 por pedidos</span>
                    <span class="pill" onclick="usarExemplo('Clientes do sexo feminino')">Mulheres</span>
                    <span class="pill" onclick="usarExemplo('Clientes com cashback disponível')">Com cashback</span>
                    <span class="pill" onclick="usarExemplo('Clientes de uma cidade específica')">Por cidade</span>
                </div>
            </div>
            <div class="create-actions">
                <button class="btn btn-primary btn-lg" onclick="interpretar()" type="button">Gerar prévia</button>
            </div>
        </section>

        <section id="boxManual" class="hidden builder-panel">
            <div id="segmentLiveSummary" class="segment-live-summary">
                <div class="segment-live-summary-head">
                    <span class="segment-live-summary-label">Resumo do segmento</span>
                </div>
                <p id="segmentHumanText" class="segment-human-text">Adicione condições para definir o público.</p>
            </div>

            <div id="groupsBox" class="groups-box"></div>
            <button class="btn btn-secondary btn-add-group" onclick="addGroup()" type="button">+ Adicionar grupo</button>

            <div class="builder-settings">
                <div class="form-group">
                    <label class="form-label" for="manual_logic">Combinar grupos</label>
                    <select id="manual_logic" class="form-control" onchange="refreshGroupSeparators()">
                        <option value="AND">E — todos os grupos</option>
                        <option value="OR">OU — qualquer grupo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="manual_limit">Limite</label>
                    <input id="manual_limit" class="form-control" type="number" value="25" min="1" max="500">
                </div>
                <div class="form-group">
                    <label class="form-label" for="order_field">Ordenação</label>
                    <select id="order_field" class="form-control">
                        <option value="random">Aleatória</option>
                        <option value="qtd_pedidos">Mais pedidos</option>
                        <option value="mais_recentes">Mais recentes</option>
                        <option value="ultima_compra_desc">Último pedido ↓</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="order_direction">Direção</label>
                    <select id="order_direction" class="form-control">
                        <option value="asc">Padrão</option>
                        <option value="desc">Inverter</option>
                    </select>
                </div>
            </div>

            <div class="create-actions">
                <button class="btn btn-primary btn-lg" onclick="gerarManual()" type="button">Gerar prévia</button>
            </div>
        </section>

        <div id="msg" class="create-msg"></div>
    </section>

    <aside class="card catalog-sidebar">
        <div class="catalog-sidebar-head">
            <h2 class="card-title">Campos disponíveis</h2>
            <p class="catalog-sidebar-lead">Clique em um campo para inserir sugestão na descrição.</p>
        </div>
        <div class="catalog-search-wrap">
            <input type="search" id="catalogSearch" class="form-control catalog-search-input" placeholder="Buscar campo..." autocomplete="off">
        </div>
        <div id="catalogList" class="catalog-list">
            @php
                $uiCategoria = fn ($campo) => match ($campo->chave) {
                    'bairro', 'municipio', 'estado' => 'localizacao',
                    'qtd_pedidos', 'ultimo_pedido', 'produto_comprado' => 'compras',
                    'cashback', 'pontos_totais' => 'financeiro',
                    default => 'cliente',
                };
                $uiOrder = ['cliente', 'compras', 'financeiro', 'localizacao'];
                $uiCategoriaLabels = ['cliente' => '👤 Cliente', 'compras' => '🛒 Compras', 'financeiro' => '💰 Financeiro', 'localizacao' => '📍 Localização'];
                $camposUi = $campos->groupBy(fn ($c) => $uiCategoria($c));
            @endphp
            @foreach($uiOrder as $catKey)
                @if(!isset($camposUi[$catKey]) || $camposUi[$catKey]->isEmpty()) @continue @endif
                <div class="catalog-group" data-category="{{ $catKey }}">
                    <div class="catalog-group-title">{{ $uiCategoriaLabels[$catKey] ?? $catKey }}</div>
                    @foreach($camposUi[$catKey] as $campo)
                        <div class="catalog-item is-clickable" data-chave="{{ $campo->chave }}" data-label="{{ $campo->label }}" data-search="{{ strtolower($campo->label . ' ' . $campo->chave) }}">
                            <div class="catalog-item-top">
                                <span class="catalog-item-label">{{ $campo->label }}</span>
                            </div>
                            <div class="catalog-item-desc">{{ Str::limit($campo->descricao, 64) }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </aside>
</div>
</div>

<section id="stepPreviewPanel" class="step-preview-panel card full-card mt-16">
    <div class="section-header mb-16">
        <div>
            <h2 class="card-title">Prévia do segmento</h2>
            <p class="muted" id="previewSegmentName">—</p>
        </div>
        <span class="badge badge-purple" id="previewTotalBadge">0 clientes</span>
    </div>

    <div id="resumoCard" class="result-block mb-16"></div>

    <div class="result-block mb-16">
        <h3 class="result-block-title">Clientes encontrados</h3>
        <div id="previewTable"><p class="muted">Gere a prévia para ver os clientes.</p></div>
    </div>

    @if($segmentadorDevMode ?? false)
        <div id="debugPanel" class="result-block mb-16"></div>
    @endif

    <div class="create-actions create-actions-save">
        <button class="btn btn-secondary" type="button" onclick="Segmentador.backToEditStep()">Voltar para editar</button>
        <button class="btn btn-secondary" type="button" onclick="Segmentador.refreshPreview()">Atualizar prévia</button>
        <form method="POST" action="{{ route('segmentos.store') }}" style="display:inline;">
            @csrf
            <input type="hidden" name="nome" id="form_nome">
            <input type="hidden" name="origem" id="form_origem" value="ia">
            <input type="hidden" name="regra_json" id="form_regra">
            <button class="btn btn-success btn-lg" type="submit" id="btnSalvar" disabled>Salvar segmento</button>
        </form>
    </div>
    <p class="form-hint mt-8" id="salvarHint">Gere a prévia para habilitar o salvamento.</p>
</section>

@include('segmentos.partials.drawer-modal')

@push('scripts')
<script>
@php
    $normalizeOperadoresJson = function ($valor) {
        if (is_array($valor)) return array_values($valor);
        if (! is_string($valor) || $valor === '') return [];
        $decoded = json_decode($valor, true);
        return is_array($decoded) ? array_values($decoded) : [];
    };
@endphp
window.SEGMENTADOR = {
    devMode: @json($segmentadorDevMode ?? false),
    campos: {!! \Illuminate\Support\Js::from($campos->map(fn ($c) => [
        'chave' => $c->chave, 'label' => $c->label, 'tipo' => $c->tipo_valor,
        'operadores' => $normalizeOperadoresJson($c->operadores_json ?? []),
    ])->values()) !!},
    selectOptions: {},
    campoOpcoesUrl: @json(route('segmentos.campoOpcoes')),
    csrf: @json(csrf_token()),
    routes: {
        interpretar: @json(route('segmentos.interpretar')),
        manual: @json(route('segmentos.manual')),
    },
    fieldSuggestions: {
        cpf: 'Clientes com CPF registrado',
        nome: 'Clientes cujo nome contém ',
        sexo: 'Clientes do sexo masculino',
        telefone: 'Clientes com telefone cadastrado',
        email: 'Clientes com e-mail cadastrado',
        nascimento: 'Aniversariantes do dia',
        idade: 'Clientes com idade maior ou igual a 18',
        municipio: 'Clientes de uma cidade específica',
        ultimo_pedido: 'Clientes que compraram nos últimos 30 dias',
        qtd_pedidos: 'Clientes com mais pedidos confirmados',
        cashback: 'Clientes com cashback disponível',
        produto_comprado: 'Clientes que compraram um produto específico',
        newsletter: 'Clientes com newsletter ativa',
    }
};
</script>
<script src="{{ asset('js/segmentador.js') }}?v={{ filemtime(public_path('js/segmentador.js')) }}"></script>
<script>
function setModo(novo) {
    document.getElementById('tabIa').classList.toggle('active', novo === 'ia');
    document.getElementById('tabManual').classList.toggle('active', novo === 'manual');
    var boxIa = document.getElementById('boxIa');
    var boxManual = document.getElementById('boxManual');
    boxIa.classList.remove('hidden');
    boxManual.classList.remove('hidden');
    boxIa.style.display = (novo === 'ia') ? '' : 'none';
    boxManual.style.display = (novo === 'manual') ? '' : 'none';
    document.getElementById('form_origem').value = novo;
    if (novo === 'manual') {
        setTimeout(function () {
            if (document.querySelectorAll('.rule-group').length === 0) Segmentador.addGroup();
            Segmentador.updateLiveSummary();
        }, 0);
    }
}
function usarExemplo(texto) {
    document.getElementById('texto').value = texto;
    if (!document.getElementById('nome').value) document.getElementById('nome').value = texto;
    setModo('ia');
}
function addGroup(){ Segmentador.addGroup(); }
function refreshGroupSeparators(){ Segmentador.refreshGroupSeparators(); }

document.querySelectorAll('.catalog-item.is-clickable').forEach(function (item) {
    item.addEventListener('click', function () {
        Segmentador.insertFieldSuggestion(item.dataset.chave);
        setModo('ia');
    });
});

async function interpretar(){
    document.getElementById('msg').innerHTML = '<div class="alert ok">Gerando prévia...</div>';
    const res = await fetch(window.SEGMENTADOR.routes.interpretar, {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.SEGMENTADOR.csrf,'X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({texto:document.getElementById('texto').value})
    });
    await tratarResposta(res, 'ia');
}

async function gerarManual(){
    const erros = Segmentador.validateManual();
    if(erros.length){ Segmentador.showValidationError(erros); return; }
    document.getElementById('msg').innerHTML = '<div class="alert ok">Gerando prévia...</div>';
    const payload = Segmentador.coletarManual();
    const res = await fetch(window.SEGMENTADOR.routes.manual, {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':window.SEGMENTADOR.csrf,'X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({
            logic: payload.logic, groups: payload.groups,
            limit: document.getElementById('manual_limit').value,
            order_field: document.getElementById('order_field').value,
            order_direction: document.getElementById('order_direction').value
        })
    });
    await tratarResposta(res, 'manual');
}

async function tratarResposta(res, origem){
    const msg = document.getElementById('msg');
    const raw = await res.text();
    let data;
    try { data = JSON.parse(raw); } catch(e) {
        msg.innerHTML = '<div class="alert err">Resposta inválida do servidor.</div>';
        return;
    }
    if(!res.ok || !data.ok){
        msg.innerHTML = '<div class="alert err">' + (data.erro || data.message || 'Erro ao gerar prévia') + '</div>';
        return;
    }
    Segmentador.renderPreviewResults(data, origem);
    Segmentador.showPreviewStep();
    document.getElementById('form_nome').value = document.getElementById('nome').value || 'Novo segmento';
    document.getElementById('form_origem').value = origem;
    document.getElementById('form_regra').value = JSON.stringify(data.regra);
    document.getElementById('btnSalvar').disabled = false;
    document.getElementById('salvarHint').textContent = 'Revise a prévia e clique em Salvar segmento.';
    document.getElementById('step1').classList.add('done');
    document.getElementById('step2').classList.add('current');
    document.getElementById('step3').classList.remove('current');
    msg.innerHTML = '';
}
</script>
@endpush
</div>
@endsection
