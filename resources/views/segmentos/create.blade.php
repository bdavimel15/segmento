@extends('layouts.app')
@section('title', 'Novo Segmento')

@section('content')
<div class="page-body create-segmento-page">
<section class="page-head create-page-head">
    <div>
        <p class="kicker">Novo grupo</p>
        <h1>Criar segmento de clientes</h1>
        <p class="lead">Descreva o público em linguagem natural ou monte regras visuais. Tudo auditável e pronto para exportar.</p>
    </div>
    <a class="btn btn-secondary" href="{{ route('segmentos.index') }}">← Voltar</a>
</section>

<div class="flow-guide flow-guide-compact mb-16">
    <div class="flow-guide-title">Passo a passo</div>
    <ol class="flow-steps">
        <li class="current" id="step1"><span>1</span> Descreva ou monte o público</li>
        <li id="step2"><span>2</span> Gere a prévia e confira o resultado</li>
        <li id="step3"><span>3</span> Salve o segmento</li>
    </ol>
</div>

<div class="grid grid-create-segmento">
    <section class="card full-card create-main-card">
        <div class="tabs tabs-segmento">
            <button class="tab active" type="button" id="tabIa" onclick="setModo('ia')">✨ Escrever o que eu quero</button>
            <button class="tab" type="button" id="tabManual" onclick="setModo('manual')">🧩 Montar com opções</button>
        </div>

        <div class="form-group create-name-field">
            <label class="form-label" for="nome">Nome do grupo</label>
            <input id="nome" class="form-control" type="text" placeholder="Ex: Homens inativos há 30 dias">
        </div>

        <section id="boxIa">
            <div class="form-group">
                <label class="form-label" for="texto">Descreva o público em português</label>
                <textarea id="texto" class="form-control" rows="4" placeholder="Ex: Clientes que compraram hoje">Clientes que compraram hoje</textarea>
            </div>
            <div class="help help-compact">
                <b>Exemplos rápidos</b>
                <div class="pill-row">
                    <span class="pill" onclick="usarExemplo('Clientes que compraram hoje')">Compraram hoje</span>
                    <span class="pill" onclick="usarExemplo('Top 4 clientes com mais pedidos')">Top 4 por pedidos</span>
                    <span class="pill" onclick="usarExemplo('Somente clientes mulheres')">Mulheres</span>
                    <span class="pill" onclick="usarExemplo('Clientes com cashback')">Com cashback</span>
                    <span class="pill" onclick="usarExemplo('Clientes de Feira de Santana')">Por cidade</span>
                </div>
            </div>
            <div class="create-actions">
                <button class="btn btn-primary btn-lg" onclick="interpretar()" type="button">Gerar grupo e prévia</button>
            </div>
        </section>

        <section id="boxManual" class="hidden builder-panel">
            <div id="segmentLiveSummary" class="segment-live-summary">
                <div class="segment-live-summary-head">
                    <span class="segment-live-summary-label">Resumo do segmento</span>
                    <span class="segment-live-summary-badge">Atualizado em tempo real</span>
                </div>
                <p id="segmentHumanText" class="segment-human-text">Adicione condições para definir o público.</p>
                <p id="segmentEstimate" class="segment-estimate is-hidden">Estimativa atual: <strong>—</strong> clientes</p>
            </div>

            <div id="groupsBox" class="groups-box"></div>

            <button class="btn btn-secondary btn-add-group" onclick="addGroup()" type="button">+ Adicionar grupo</button>

            <div class="builder-settings">
                <div class="form-group">
                    <label class="form-label" for="manual_logic">Combinar grupos</label>
                    <select id="manual_logic" class="form-control" onchange="refreshGroupSeparators()">
                        <option value="AND">E — cumprir todos os grupos</option>
                        <option value="OR">OU — basta um grupo</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="manual_limit">Limite</label>
                    <input id="manual_limit" class="form-control" type="number" value="25" min="1" max="500" title="Máximo de clientes na prévia">
                </div>
                <div class="form-group">
                    <label class="form-label" for="order_field">Ordenação</label>
                    <select id="order_field" class="form-control">
                        <option value="random">Aleatória</option>
                        <option value="qtd_pedidos">Mais pedidos</option>
                        <option value="mais_recentes">Mais recentes</option>
                        <option value="mais_antigos">Mais antigos</option>
                        <option value="ultima_compra_desc">Último pedido ↓</option>
                        <option value="ultima_compra_asc">Último pedido ↑</option>
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
                <button class="btn btn-primary btn-lg" onclick="gerarManual()" type="button">Gerar grupo e prévia</button>
            </div>
        </section>

        <div id="msg" class="create-msg"></div>

        <section class="result-panel result-panel-compact">
            <div class="result-panel-head">
                <h2>Resultado antes de salvar</h2>
                <p class="result-panel-lead">Confira o resumo, a prévia e os detalhes técnicos antes de salvar.</p>
            </div>

            <div id="resumoCard" class="result-block">
                <p class="muted">Gere a prévia para ver o resumo e os clientes encontrados.</p>
            </div>

            <div class="result-block">
                <h3 class="result-block-title">Prévia dos clientes</h3>
                <div id="previewTable">
                    <p class="muted">Aguardando geração da prévia...</p>
                </div>
            </div>

            <div id="debugPanel" class="result-block"></div>

            <form method="POST" action="{{ route('segmentos.store') }}" class="create-actions create-actions-save">
                @csrf
                <input type="hidden" name="nome" id="form_nome">
                <input type="hidden" name="origem" id="form_origem" value="ia">
                <input type="hidden" name="regra_json" id="form_regra">
                <button class="btn btn-success btn-lg" type="submit" id="btnSalvar" disabled title="Gere a prévia antes de salvar">Salvar segmento</button>
                <span class="form-hint" id="salvarHint">Gere a prévia acima para habilitar o salvamento.</span>
            </form>
        </section>
    </section>

    <aside class="card catalog-sidebar">
        <div class="catalog-sidebar-head">
            <h2 class="card-title">Campos disponíveis</h2>
            <p class="catalog-sidebar-lead">Referência do catálogo — use a busca para encontrar rapidamente.</p>
        </div>
        <div class="catalog-search-wrap">
            <input type="search" id="catalogSearch" class="form-control catalog-search-input" placeholder="Buscar: nome, pedido, cashback..." autocomplete="off" aria-label="Buscar campo">
        </div>
        <div id="catalogList" class="catalog-list">
            @php
                $uiCategoria = function ($campo) {
                    return match ($campo->chave) {
                        'bairro', 'municipio', 'estado' => 'localizacao',
                        'newsletter', 'busca_geral', 'origem', 'notificacao_recente' => 'marketing',
                        'qtd_pedidos', 'ultimo_pedido', 'primeira_compra', 'valor_total_comprado', 'carrinho_abandonado', 'produto_comprado' => 'compras',
                        'cashback', 'cashback_expira_em', 'pontos_totais' => 'financeiro',
                        default => 'cliente',
                    };
                };
                $uiCategoriaLabels = [
                    'cliente' => '👤 Cliente',
                    'compras' => '🛒 Compras',
                    'financeiro' => '💰 Financeiro',
                    'marketing' => '📣 Marketing',
                    'localizacao' => '📍 Localização',
                ];
                $uiOrder = ['cliente', 'compras', 'financeiro', 'marketing', 'localizacao'];
                $tipoLabels = [
                    'string' => 'Texto',
                    'number' => 'Número',
                    'date' => 'Data',
                    'datetime' => 'Data/hora',
                    'boolean' => 'Sim/Não',
                    'money' => 'Valor',
                    'select' => 'Lista',
                ];
                $camposUi = $campos->groupBy(fn ($c) => $uiCategoria($c));
            @endphp
            @foreach($uiOrder as $catKey)
                @if(!isset($camposUi[$catKey]) || $camposUi[$catKey]->isEmpty())
                    @continue
                @endif
                <div class="catalog-group" data-category="{{ $catKey }}">
                    <div class="catalog-group-title">{{ $uiCategoriaLabels[$catKey] }}</div>
                    @foreach($camposUi[$catKey] as $campo)
                        <div class="catalog-item" data-search="{{ strtolower($campo->label . ' ' . $campo->chave . ' ' . ($campo->descricao ?? '') . ' ' . $catKey) }}">
                            <div class="catalog-item-top">
                                <span class="catalog-item-label">{{ $campo->label }}</span>
                                <span class="badge badge-purple badge-xs">{{ $tipoLabels[$campo->tipo_valor] ?? $campo->tipo_valor }}</span>
                            </div>
                            <div class="catalog-item-desc">{{ Str::limit($campo->descricao, 64) }}</div>
                        </div>
                    @endforeach
                </div>
            @endforeach
            <div id="catalogEmpty" class="catalog-empty is-hidden">Nenhum campo encontrado.</div>
        </div>
    </aside>
</div>

@include('segmentos.partials.drawer-modal')

@push('scripts')
<script>
@php
    $normalizeOperadoresJson = function ($valor) {
        if (is_array($valor)) {
            return array_values($valor);
        }
        if (! is_string($valor) || $valor === '') {
            return [];
        }
        $decoded = json_decode($valor, true);
        if (is_array($decoded)) {
            return array_values($decoded);
        }
        if (is_string($decoded)) {
            $decoded2 = json_decode($decoded, true);
            return is_array($decoded2) ? array_values($decoded2) : [];
        }
        return [];
    };

    $produtoOptions = [];
    try {
        if (\Illuminate\Support\Facades\Schema::hasTable('produtos')) {
            $produtoOptions = \Illuminate\Support\Facades\DB::table('produtos')
                ->where(function ($q) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('produtos', 'ativo')) {
                        $q->where('ativo', 'S');
                    }
                })
                ->orderBy('nome')
                ->pluck('nome')
                ->values()
                ->all();
        }
    } catch (\Throwable $e) {
        $produtoOptions = [];
    }

    $selectOptions = [
        'sexo' => ['Masculino', 'Feminino'],
        'newsletter' => ['Sim', 'Não'],
        'funcionario' => ['Sim', 'Não'],
        'produto_comprado' => $produtoOptions,
        'origem_contato' => ['WhatsApp', 'Instagram', 'iFood', 'Balcão', 'Site', 'Telefone'],
        'canal_pedido' => ['WhatsApp', 'iFood', 'Balcão', 'Delivery próprio', 'Site', 'Instagram'],
        'forma_pagamento' => ['Pix', 'Cartão de crédito', 'Cartão de débito', 'Dinheiro', 'Vale refeição'],
        'status_pedido' => ['Confirmado', 'Pendente', 'Cancelado', 'Entregue'],
    ];
@endphp
window.SEGMENTADOR = {
    campos: {!! \Illuminate\Support\Js::from(
        $campos->map(fn ($c) => [
            'chave' => $c->chave,
            'label' => $c->label,
            'tipo' => $c->tipo_valor,
            'categoria' => $c->categoria ?? 'geral',
            'operadores' => $normalizeOperadoresJson($c->operadores_json ?? []),
            'descricao' => $c->descricao,
        ])->values()
    ) !!},
    selectOptions: {!! \Illuminate\Support\Js::from($selectOptions) !!},
    campoOpcoesUrl: @json(route('segmentos.campoOpcoes')),
    csrf: @json(csrf_token()),
    routes: {
        interpretar: @json(route('segmentos.interpretar')),
        manual: @json(route('segmentos.manual')),
    }
};
/* Diagnóstico: confirma que campos chegaram ao JS */
(function () {
    var qtd = window.SEGMENTADOR && window.SEGMENTADOR.campos ? window.SEGMENTADOR.campos.length : 0;
    if (qtd === 0) {
        console.error('[Segmentador] ERRO CRÍTICO: window.SEGMENTADOR.campos está vazio! ' +
            'O construtor manual não funcionará. Rode: php artisan db:seed --class=SegmentoClienteCampoSeeder');
    } else {
        console.info('[Segmentador] OK — ' + qtd + ' campos carregados.');
    }
})();
</script>
<script src="{{ asset('js/segmentador.js') }}?v={{ file_exists(public_path('js/segmentador.js')) ? filemtime(public_path('js/segmentador.js')) : time() }}"></script>
<script>
function setModo(novo) {
    document.getElementById('tabIa').classList.toggle('active', novo === 'ia');
    document.getElementById('tabManual').classList.toggle('active', novo === 'manual');

    var boxIa     = document.getElementById('boxIa');
    var boxManual = document.getElementById('boxManual');

    /* Remove a classe .hidden de ambos para que o JS interno não aborte */
    boxIa.classList.remove('hidden');
    boxManual.classList.remove('hidden');

    /* Usa style.display — tem precedência sobre CSS .hidden {display:none!important}
       e é aplicado imediatamente, sem aguardar recálculo de estilo */
    boxIa.style.display     = (novo === 'ia')     ? '' : 'none';
    boxManual.style.display = (novo === 'manual') ? '' : 'none';

    document.getElementById('form_origem').value = novo;

    if (novo === 'manual') {
        /* setTimeout(0) garante que o event loop completou o ciclo de layout
           antes de addGroup tentar criar/inserir elementos no DOM interno */
        setTimeout(function () {
            if (document.querySelectorAll('.rule-group').length === 0) {
                Segmentador.addGroup();
            }
            Segmentador.updateLiveSummary();
        }, 0);
    }
}
function usarExemplo(texto){ document.getElementById('texto').value = texto; if(!document.getElementById('nome').value) document.getElementById('nome').value = texto; }
function addGroup(){ Segmentador.addGroup(); }
function refreshGroupSeparators(){ Segmentador.refreshGroupSeparators(); }

async function interpretar(){
    const msg = document.getElementById('msg');
    msg.innerHTML = '<div class="alert ok">Gerando grupo com IA...</div>';
    const res = await fetch(window.SEGMENTADOR.routes.interpretar, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.SEGMENTADOR.csrf},
        body: JSON.stringify({texto:document.getElementById('texto').value})
    });
    await tratarResposta(res, 'ia');
}

async function gerarManual(){
    const erros = Segmentador.validateManual();
    if(erros.length){ Segmentador.showValidationError(erros); return; }
    const msg = document.getElementById('msg');
    msg.innerHTML = '<div class="alert ok">Gerando grupo manual...</div>';
    const payload = Segmentador.coletarManual();
    const res = await fetch(window.SEGMENTADOR.routes.manual, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':window.SEGMENTADOR.csrf},
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
    let data;
    try { data = await res.json(); } catch(e) { msg.innerHTML = '<div class="alert err">Resposta inválida do servidor.</div>'; return; }
    if(!data.ok){ msg.innerHTML = '<div class="alert err">'+String(data.erro || 'Erro ao gerar regra').replace(/[&<>'"]/g, c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]))+'</div>'; return; }
    Segmentador.renderPreviewResults(data, origem);
    document.getElementById('form_nome').value = document.getElementById('nome').value || document.getElementById('texto')?.value || 'Novo grupo';
    document.getElementById('form_origem').value = origem;
    document.getElementById('form_regra').value = JSON.stringify(data.regra);
    document.getElementById('btnSalvar').disabled = false;
    document.getElementById('salvarHint').textContent = 'Prévia gerada! Confira o resultado e clique em Salvar.';
    document.getElementById('step2').classList.add('done');
    document.getElementById('step2').classList.remove('current');
    document.getElementById('step3').classList.add('current');
    document.querySelector('.result-panel')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    msg.innerHTML = '<div class="alert ok">Prévia gerada! Confira o resumo, a coluna "Por que entrou?" e os detalhes de cada cliente.</div>';
}
</script>
@endpush
</div>
@endsection
