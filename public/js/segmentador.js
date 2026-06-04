/**
 * Segmentador — construtor de regras e prévia profissional
 */
(function (window) {
    'use strict';

    const UF_LIST = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

    const OP_LABELS = {
        equals:'é igual a', not_equals:'é diferente de', greater_than:'é maior que', greater_or_equal:'é pelo menos',
        less_than:'é menor que', less_or_equal:'é no máximo', between:'está entre', contains:'contém', not_contains:'não contém',
        starts_with:'começa com', ends_with:'termina com', is_empty:'está vazio', is_not_empty:'não está vazio',
        is_true:'sim', is_false:'não', exists:'existe', not_exists:'não existe', today:'é hoje', yesterday:'foi ontem',
        equals_date:'é na data', before_date:'antes de', after_date:'depois de', between_dates:'entre datas',
        last_x_days:'nos últimos X dias', next_x_days:'nos próximos X dias', exactly_x_days_ago:'exatamente X dias atrás',
        more_than_x_days_ago:'há mais de X dias', less_than_x_days_ago:'há menos de X dias'
    };

    const OP_HINTS = {
        today:'Não precisa preencher valor.', yesterday:'Não precisa preencher valor.',
        is_true:'Não precisa preencher valor.', is_false:'Não precisa preencher valor.',
        exists:'Não precisa preencher valor.', not_exists:'Não precisa preencher valor.',
        is_empty:'Não precisa preencher valor.', is_not_empty:'Não precisa preencher valor.',
        last_x_days:'Digite o número de dias. Ex: 7', next_x_days:'Digite o número de dias. Ex: 7',
        more_than_x_days_ago:'Digite o número de dias. Ex: 30', less_than_x_days_ago:'Digite o número de dias. Ex: 30',
        exactly_x_days_ago:'Digite o número de dias. Ex: 30',
        before_date:'Selecione uma data.', after_date:'Selecione uma data.', equals_date:'Selecione uma data.',
        between:'Digite dois números separados por vírgula. Ex: 18, 65'
    };

    const LOGIC_LABELS = { AND: 'E', OR: 'OU' };
    const NO_VALUE_OPS = ['today','yesterday','is_true','is_false','exists','not_exists','is_empty','is_not_empty'];
    const DATE_OPS = ['equals_date','before_date','after_date','between_dates'];
    const DAY_OPS = ['last_x_days','next_x_days','exactly_x_days_ago','more_than_x_days_ago','less_than_x_days_ago'];
    const DATE_FIELDS = ['nascimento','ultima_compra','ultimo_pedido','data_cadastro','primeira_compra','cashback_expira_em'];

    let pendingGroupRemoveBtn = null;
    let lastPreviewData = null;

    function cfg() { return window.SEGMENTADOR || {}; }
    function campos() { return cfg().campos || []; }
    function campoByChave(chave) { return campos().find(c => c.chave === chave) || campos()[0]; }

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[ch]));
    }

    const TIPO_LABELS = {
        string: 'Texto', number: 'Número', date: 'Data', datetime: 'Data/hora',
        boolean: 'Sim/Não', money: 'Valor', select: 'Lista'
    };

    const CATEGORIA_LABELS = {
        cliente: 'Cliente', endereco: 'Endereço', pedido: 'Pedidos',
        cashback: 'Cashback', carrinho: 'Carrinho', geral: 'Geral'
    };

    const UI_CATEGORIA_ORDER = ['cliente', 'compras', 'financeiro', 'marketing', 'localizacao'];
    const UI_CATEGORIA_LABELS = {
        cliente: '👤 Cliente',
        compras: '🛒 Compras',
        financeiro: '💰 Financeiro',
        marketing: '📣 Marketing',
        localizacao: '📍 Localização',
    };

    const UI_FIELD_ORDER = {
        cliente: ['nome', 'cpf', 'sexo', 'telefone', 'email', 'nascimento', 'idade', 'funcionario', 'data_cadastro'],
        compras: ['qtd_pedidos', 'ultima_compra', 'primeira_compra', 'valor_total_comprado', 'status_pedido', 'canal_pedido', 'forma_pagamento', 'carrinho_abandonado', 'produto_comprado'],
        financeiro: ['cashback', 'pontos_totais', 'cashback_expira_em'],
        localizacao: ['municipio', 'estado', 'bairro'],
        marketing: ['newsletter', 'origem_contato', 'notificacao_recente', 'busca_geral'],
    };

    const ACTION_ICON_UP = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7"/></svg>';
    const ACTION_ICON_DOWN = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7"/></svg>';
    const ACTION_ICON_TRASH = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>';
    const TOOLBAR_ICON_COPY = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>';
    const TOOLBAR_ICON_TRASH = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6"/></svg>';

    function uiCategoria(campo) {
        const chave = campo?.chave || '';
        if (['bairro', 'municipio', 'estado'].includes(chave)) return 'localizacao';
        if (['newsletter', 'busca_geral', 'origem_contato', 'origem', 'notificacao_recente'].includes(chave)) return 'marketing';
        if (['qtd_pedidos', 'ultima_compra', 'ultimo_pedido', 'primeira_compra', 'valor_total_comprado', 'status_pedido', 'canal_pedido', 'forma_pagamento', 'carrinho_abandonado', 'produto_comprado'].includes(chave)) return 'compras';
        if (['cashback', 'cashback_expira_em', 'pontos_totais'].includes(chave)) return 'financeiro';
        return 'cliente';
    }

    function formatExpectedValue(field, operator, value) {
        if (NO_VALUE_OPS.includes(operator)) {
            const map = {
                today: 'hoje', yesterday: 'ontem', is_true: 'sim', is_false: 'não',
                exists: 'sim', not_exists: 'não', is_empty: 'vazio', is_not_empty: 'preenchido'
            };
            return map[operator] || '—';
        }
        if (value === null || value === '' || value === undefined) return '—';
        if (field === 'sexo') {
            const v = String(value).toLowerCase();
            if (v.includes('fem')) return 'Feminino';
            if (v.includes('masc')) return 'Masculino';
        }
        if (['last_x_days', 'next_x_days', 'exactly_x_days_ago', 'more_than_x_days_ago', 'less_than_x_days_ago'].includes(operator)) {
            return `${value} dias`;
        }
        return String(value);
    }

    function formatConditionLine(cond) {
        const campo = campoByChave(cond.field);
        const label = campo?.label || cond.field;
        const op = OP_LABELS[cond.operator] || cond.operator;
        const expected = formatExpectedValue(cond.field, cond.operator, cond.value);
        if (expected === '—') return `${label} ${op}`;
        return `${label} ${op} ${expected}`;
    }

    function buildCompactHeadline(payload) {
        const allConds = payload.groups.flatMap(g => g.conditions || []);
        const chunks = [];

        const sexo = allConds.find(c => c.field === 'sexo' && c.operator === 'equals' && c.value);
        if (sexo) {
            const v = String(sexo.value).toLowerCase();
            if (v.includes('fem')) chunks.push('Mulheres');
            else if (v.includes('masc')) chunks.push('Homens');
        }

        const pedidos = allConds.find(c => c.field === 'qtd_pedidos' && ['greater_than', 'greater_or_equal'].includes(c.operator) && c.value !== '');
        if (pedidos) {
            const n = pedidos.value;
            chunks.push(`com mais de ${n} pedido${Number(n) === 1 ? '' : 's'}`);
        }

        const cashback = allConds.find(c => c.field === 'cashback' && ['greater_than', 'greater_or_equal', 'is_true', 'exists'].includes(c.operator));
        if (cashback && chunks.length < 2) chunks.push('com cashback');

        const cidade = allConds.find(c => c.field === 'municipio' && c.operator === 'equals' && c.value);
        if (cidade) chunks.push(`de ${cidade.value}`);

        if (chunks.length >= 2) return chunks.join(' ');

        const parts = [];
        payload.groups.forEach((group, gi) => {
            const lines = (group.conditions || []).map(formatConditionLine).filter(Boolean);
            if (!lines.length) return;
            const join = group.logic === 'OR' ? ' ou ' : ' e ';
            let block = lines.join(join);
            if (payload.groups.length > 1) block = `Grupo ${gi + 1}: ${block}`;
            parts.push(block);
        });
        const topJoin = payload.logic === 'OR' ? ' ou ' : ' e ';
        return parts.join(topJoin) || 'Adicione condições para definir o público.';
    }

    function updateLiveSummary() {
        const humanEl = document.getElementById('segmentHumanText');
        const estimateEl = document.getElementById('segmentEstimate');
        if (!humanEl) return;

        const box = document.getElementById('boxManual');
        if (box?.classList.contains('hidden')) return;

        if (!document.querySelectorAll('.rule-group').length) {
            humanEl.textContent = 'Adicione condições para definir o público.';
            if (estimateEl) estimateEl.classList.add('is-hidden');
            return;
        }

        try {
            humanEl.textContent = buildCompactHeadline(coletarManual());
        } catch (e) {
            humanEl.textContent = 'Monte as regras para ver o resumo.';
        }

        if (estimateEl && lastPreviewData?.preview?.ok) {
            const count = lastPreviewData.preview.aprovados ?? lastPreviewData.preview.total ?? 0;
            estimateEl.innerHTML = `Estimativa atual: <strong>${count}</strong> cliente${count === 1 ? '' : 's'}`;
            estimateEl.classList.remove('is-hidden');
        }
    }

    function initLiveSummary() {
        const box = document.getElementById('groupsBox');
        if (!box) return;
        let timer = null;
        const refresh = () => {
            clearTimeout(timer);
            timer = setTimeout(updateLiveSummary, 120);
        };
        box.addEventListener('change', refresh);
        box.addEventListener('input', refresh);
        document.getElementById('manual_logic')?.addEventListener('change', refresh);
        updateLiveSummary();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLiveSummary);
    } else {
        initLiveSummary();
    }

    function tipoLabel(tipo) {
        return TIPO_LABELS[tipo] || tipo || 'Campo';
    }

    function getFieldChave(row) {
        return row?.querySelector('.field')?.value || '';
    }

    function sortCamposInCategory(items, catKey) {
        const order = UI_FIELD_ORDER[catKey] || [];
        return [...items].sort((a, b) => {
            const ia = order.indexOf(a.chave);
            const ib = order.indexOf(b.chave);
            if (ia === -1 && ib === -1) return (a.label || '').localeCompare(b.label || '', 'pt-BR');
            if (ia === -1) return 1;
            if (ib === -1) return -1;
            return ia - ib;
        });
    }

    function campoOptionsGrouped(selected) {
        const grouped = {};
        campos().forEach(c => {
            const key = uiCategoria(c);
            if (!grouped[key]) grouped[key] = [];
            grouped[key].push(c);
        });

        let html = '';
        UI_CATEGORIA_ORDER.forEach(catKey => {
            const items = grouped[catKey];
            if (!items?.length) return;
            html += `<optgroup label="${escapeHtml(UI_CATEGORIA_LABELS[catKey])}">`;
            sortCamposInCategory(items, catKey).forEach(c => {
                html += `<option value="${escapeHtml(c.chave)}"${selected === c.chave ? ' selected' : ''} title="${escapeHtml(c.descricao || '')}">${escapeHtml(c.label)}</option>`;
            });
            html += '</optgroup>';
        });
        return html;
    }

    function onFieldChanged(row, chave) {
        const campo = campoByChave(chave);
        const fieldSelect = row.querySelector('.field');
        if (fieldSelect) fieldSelect.title = campo?.descricao || '';
        row.querySelector('.operator').innerHTML = operadoresOptions(chave);
        refreshValueHint(row.querySelector('.operator'));
    }

    function initCatalogFilter() {
        const search = document.getElementById('catalogSearch');
        const list = document.getElementById('catalogList');
        if (!search || !list) return;

        search.addEventListener('input', () => {
            const q = search.value.trim().toLowerCase();
            let visible = 0;
            list.querySelectorAll('.catalog-item').forEach(item => {
                const hay = (item.dataset.search || '').toLowerCase();
                const show = !q || hay.includes(q);
                item.classList.toggle('is-hidden', !show);
                if (show) visible++;
            });
            list.querySelectorAll('.catalog-group').forEach(group => {
                const hasVisible = group.querySelectorAll('.catalog-item:not(.is-hidden)').length > 0;
                group.classList.toggle('is-hidden', !hasVisible);
            });
            const empty = document.getElementById('catalogEmpty');
            if (empty) empty.classList.toggle('is-hidden', visible > 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCatalogFilter);
    } else {
        initCatalogFilter();
    }

    function getWidgetType(chave, tipo) {
        const campo = campoByChave(chave);
        if (tipo === 'select' || (campo?.opcoes || []).length) return 'select';
        if (chave === 'sexo') return 'select';
        if (chave === 'estado') return 'select';
        if (chave === 'municipio') return 'autocomplete';
        if (chave === 'bairro') return 'autocomplete';
        if (tipo === 'boolean' || chave === 'newsletter' || chave === 'funcionario') return 'boolean';
        if (tipo === 'number' || tipo === 'money') return 'number';
        if (tipo === 'date' || tipo === 'datetime') return 'date';
        return 'text';
    }

    function buildValueWidgetHtml(chave, operator, value) {
        const campo = campoByChave(chave);
        const widget = getWidgetType(chave, campo?.tipo || 'string');
        const disabled = NO_VALUE_OPS.includes(operator) ? ' disabled' : '';
        const val = escapeHtml(String(value ?? ''));

        if (NO_VALUE_OPS.includes(operator)) {
            return `<input class="form-control value" type="text" placeholder="Não precisa" disabled>`;
        }

        if (widget === 'select') {
            const opcoes = campo?.opcoes || [];
            const opts = opcoes.map(opcao => {
                const selected = String(value ?? '') === String(opcao) ? ' selected' : '';
                return `<option value="${escapeHtml(opcao)}"${selected}>${escapeHtml(opcao)}</option>`;
            }).join('');
            return `<select class="form-control value"${disabled}>
                <option value="">Selecione...</option>${opts}
            </select>`;
        }

        if (widget === 'boolean') {
            const sim = ['sim','s','1','true'].includes(String(value).toLowerCase());
            const nao = ['nao','não','n','0','false'].includes(String(value).toLowerCase());
            return `<select class="form-control value"${disabled}>
                <option value="">Selecione...</option>
                <option value="sim"${sim ? ' selected' : ''}>Sim</option>
                <option value="nao"${nao ? ' selected' : ''}>Não</option>
            </select>`;
        }

        if (widget === 'estado') {
            const opts = UF_LIST.map(uf => `<option value="${uf}"${val === uf ? ' selected' : ''}>${uf}</option>`).join('');
            return `<select class="form-control value"${disabled}><option value="">Selecione a UF...</option>${opts}</select>`;
        }

        if (widget === 'autocomplete') {
            return `<div class="value-autocomplete">
                <input class="form-control value" type="text" list="ac-${chave}-${Math.random().toString(36).slice(2)}" value="${val}" placeholder="Digite para buscar..." autocomplete="off" data-autocomplete-campo="${escapeHtml(chave)}"${disabled}>
                <datalist class="value-datalist"></datalist>
            </div>`;
        }

        if (widget === 'number' || DAY_OPS.includes(operator)) {
            const step = widget === 'number' && campo?.tipo === 'money' ? '0.01' : '1';
            const ph = DAY_OPS.includes(operator) ? 'Ex: 30' : (operator === 'between' ? 'Ex: 10, 50' : 'Ex: 30');
            return `<input class="form-control value" type="number" step="${step}" min="0" value="${val}" placeholder="${ph}"${disabled}>`;
        }

        if (widget === 'date' || DATE_OPS.includes(operator)) {
            return `<input class="form-control value" type="date" value="${val}"${disabled}>`;
        }

        return `<input class="form-control value" type="text" value="${val}" placeholder="Digite o valor"${disabled}>`;
    }

    function readValueFromRow(row) {
        const el = row.querySelector('.value');
        if (!el) return '';
        if (el.disabled) return '';
        return el.value ?? '';
    }

    function bindAutocomplete(input) {
        const campo = input.dataset.autocompleteCampo;
        if (!campo || !cfg().campoOpcoesUrl) return;
        let timer = null;
        input.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(async () => {
                const q = input.value.trim();
                if (q.length < 2) return;
                try {
                    const res = await fetch(`${cfg().campoOpcoesUrl}?campo=${encodeURIComponent(campo)}&q=${encodeURIComponent(q)}`);
                    const items = await res.json();
                    const list = input.closest('.value-autocomplete')?.querySelector('.value-datalist');
                    if (!list) return;
                    list.innerHTML = items.map(i => `<option value="${escapeHtml(i)}">`).join('');
                    input.setAttribute('list', list.id || '');
                } catch (e) { /* silencioso */ }
            }, 250);
        });
    }

    function refreshValueWidget(row) {
        const field = row.querySelector('.field')?.value;
        const operator = row.querySelector('.operator')?.value;
        const oldVal = readValueFromRow(row);
        const container = row.querySelector('.value-container');
        if (!container) return;
        container.innerHTML = buildValueWidgetHtml(field, operator, oldVal);
        const input = container.querySelector('.value');
        if (input?.dataset?.autocompleteCampo) bindAutocomplete(input);
    }

    function refreshOps(el) {
        const row = el?.closest?.('.rule-row');
        if (!row) return;
        onFieldChanged(row, row.querySelector('.field')?.value);
    }

    function refreshValueHint(select) {
        const row = select.closest('.rule-row');
        const op = select.value;
        const hint = OP_HINTS[op] || '';
        const valueInput = row.querySelector('.value');
        if (valueInput) valueInput.title = hint;
        refreshValueWidget(row);
    }

    function operadoresOptions(chave, selected) {
        const campo = campoByChave(chave);
        return (campo?.operadores || []).map(op =>
            `<option value="${escapeHtml(op)}"${selected === op ? ' selected' : ''}>${escapeHtml(OP_LABELS[op] || op)}</option>`
        ).join('');
    }

    function addGroup(preset) {
        const box = document.getElementById('groupsBox');
        const index = box.querySelectorAll('.rule-group').length + 1;
        const wrapper = document.createElement('div');
        wrapper.className = 'rule-group-wrap';
        wrapper.innerHTML = `
            <div class="rule-group">
                <div class="rule-group-header">
                    <div class="rule-group-header-main">
                        <span class="rule-group-badge">Grupo ${index}</span>
                        <div class="rule-group-logic">
                            <label class="sr-only" for="group-logic-${index}">Lógica do grupo</label>
                            <select id="group-logic-${index}" class="form-control form-control-compact group-logic" onchange="Segmentador.refreshGroupConnectors(this.closest('.rule-group'))" title="Como combinar condições neste grupo">
                                <option value="AND"${(preset?.logic || 'AND') === 'AND' ? ' selected' : ''}>Todas as condições (E)</option>
                                <option value="OR"${preset?.logic === 'OR' ? ' selected' : ''}>Qualquer condição (OU)</option>
                            </select>
                        </div>
                    </div>
                    <div class="rule-group-toolbar" role="toolbar" aria-label="Ações do grupo">
                        <button class="toolbar-btn" type="button" onclick="Segmentador.duplicateGroup(this)" title="Duplicar este grupo com todas as condições" aria-label="Duplicar grupo">
                            ${TOOLBAR_ICON_COPY}<span>Duplicar</span>
                        </button>
                        <button class="toolbar-btn toolbar-btn-danger" type="button" onclick="Segmentador.confirmRemoveGroup(this)" title="Excluir este grupo de condições" aria-label="Excluir grupo">
                            ${TOOLBAR_ICON_TRASH}<span>Excluir</span>
                        </button>
                    </div>
                </div>
                <div class="rule-grid-columns" aria-hidden="true">
                    <span>Campo</span>
                    <span>Comparação</span>
                    <span>Valor</span>
                    <span class="col-actions">Ações</span>
                </div>
                <div class="rule-group-conditions"></div>
                <button class="btn-add-condition" type="button" onclick="Segmentador.addCondition(this)">
                    <span class="btn-add-condition-icon" aria-hidden="true">+</span>
                    Adicionar condição
                </button>
            </div>`;
        box.appendChild(wrapper);
        const conditions = preset?.conditions?.length ? preset.conditions : [{}];
        conditions.forEach(c => addCondition(wrapper.querySelector('.btn-add-condition'), c));
        renumberGroups();
        updateLiveSummary();
    }

    function confirmRemoveGroup(btn) {
        const box = document.getElementById('groupsBox');
        if (box.querySelectorAll('.rule-group').length <= 1) {
            showValidationError('Mantenha pelo menos um grupo de regras.');
            return;
        }
        pendingGroupRemoveBtn = btn;
        document.getElementById('modalRemoveGroup')?.classList.add('is-open');
    }

    function cancelRemoveGroup() {
        pendingGroupRemoveBtn = null;
        document.getElementById('modalRemoveGroup')?.classList.remove('is-open');
    }

    function executeRemoveGroup() {
        if (pendingGroupRemoveBtn) {
            pendingGroupRemoveBtn.closest('.rule-group-wrap')?.remove();
            renumberGroups();
            updateLiveSummary();
        }
        cancelRemoveGroup();
    }

    function duplicateGroup(btn) {
        const group = btn.closest('.rule-group');
        const preset = {
            logic: group.querySelector('.group-logic')?.value || 'AND',
            conditions: Array.from(group.querySelectorAll('.rule-condition-item')).map(item => ({
                field: item.querySelector('.field')?.value,
                operator: item.querySelector('.operator')?.value,
                value: readValueFromRow(item.querySelector('.rule-row'))
            }))
        };
        addGroup(preset);
    }

    function renumberGroups() {
        const wraps = Array.from(document.querySelectorAll('.rule-group-wrap'));
        wraps.forEach((wrap, i) => {
            wrap.querySelector('.rule-group-badge').textContent = `Grupo ${i + 1}`;
            if (i > 0) {
                if (!wrap.querySelector('.group-separator')) {
                    const sep = document.createElement('div');
                    sep.className = 'group-separator';
                    sep.innerHTML = '<span class="group-separator-label"></span>';
                    wrap.prepend(sep);
                }
            } else {
                wrap.querySelector('.group-separator')?.remove();
            }
        });
        refreshGroupSeparators();
        wraps.forEach(w => refreshGroupConnectors(w.querySelector('.rule-group')));
    }

    function refreshGroupSeparators() {
        const label = LOGIC_LABELS[document.getElementById('manual_logic')?.value] || 'E';
        document.querySelectorAll('.group-separator-label').forEach(el => { el.textContent = label; });
    }

    function addCondition(trigger, preset) {
        const group = trigger.closest('.rule-group');
        const first = campos()[0]?.chave || '';
        const field = preset?.field || first;
        const operator = preset?.operator || (campoByChave(field)?.operadores?.[0] || 'equals');
        const item = document.createElement('div');
        item.className = 'rule-condition-item';
        const campo = campoByChave(field);
        item.innerHTML = `
            <div class="rule-row rule-row-single">
                <div class="form-group form-group-field">
                    <select class="form-control field" onchange="Segmentador.refreshOps(this)" title="${escapeHtml(campo?.descricao || '')}">${campoOptionsGrouped(field)}</select>
                </div>
                <div class="form-group form-group-op">
                    <select class="form-control operator" onchange="Segmentador.refreshValueHint(this)" title="Tipo de comparação">${operadoresOptions(field, operator)}</select>
                </div>
                <div class="form-group form-group-value">
                    <div class="value-container">${buildValueWidgetHtml(field, operator, preset?.value ?? '')}</div>
                </div>
                <div class="rule-actions-col">
                    <button class="action-btn" type="button" onclick="Segmentador.moveCondition(this,-1)" title="Mover para cima" aria-label="Mover para cima">${ACTION_ICON_UP}</button>
                    <button class="action-btn" type="button" onclick="Segmentador.moveCondition(this,1)" title="Mover para baixo" aria-label="Mover para baixo">${ACTION_ICON_DOWN}</button>
                    <button class="action-btn action-btn-danger" type="button" onclick="Segmentador.removeCondition(this)" title="Remover condição" aria-label="Remover condição">${ACTION_ICON_TRASH}</button>
                </div>
            </div>`;
        group.querySelector('.rule-group-conditions').appendChild(item);
        const row = item.querySelector('.rule-row');
        refreshValueHint(item.querySelector('.operator'));
        const ac = item.querySelector('[data-autocomplete-campo]');
        if (ac) bindAutocomplete(ac);
        refreshGroupConnectors(group);
        updateLiveSummary();
    }

    function removeCondition(btn) {
        const group = btn.closest('.rule-group');
        const list = group.querySelector('.rule-group-conditions');
        if (list.querySelectorAll('.rule-condition-item').length <= 1) {
            showValidationError('Cada grupo precisa ter pelo menos uma condição.');
            return;
        }
        btn.closest('.rule-condition-item').remove();
        refreshGroupConnectors(group);
        updateLiveSummary();
    }

    function moveCondition(btn, dir) {
        const item = btn.closest('.rule-condition-item');
        const sibling = dir < 0 ? item.previousElementSibling : item.nextElementSibling;
        if (!sibling) return;
        if (dir < 0) item.parentElement.insertBefore(item, sibling);
        else item.parentElement.insertBefore(sibling, item);
        refreshGroupConnectors(btn.closest('.rule-group'));
        updateLiveSummary();
    }

    function refreshGroupConnectors(group) {
        const logic = group.querySelector('.group-logic')?.value || 'AND';
        const label = LOGIC_LABELS[logic] || 'E';
        const container = group.querySelector('.rule-group-conditions');
        container.querySelectorAll('.rule-logic-divider').forEach(el => el.remove());
        const items = container.querySelectorAll('.rule-condition-item');
        items.forEach((item, i) => {
            if (i === 0) return;
            const div = document.createElement('div');
            div.className = 'rule-logic-divider';
            div.innerHTML = `<span class="rule-logic-pill">${label}</span>`;
            container.insertBefore(div, item);
        });
    }

    function coletarManual() {
        return {
            logic: document.getElementById('manual_logic')?.value || 'AND',
            groups: Array.from(document.querySelectorAll('.rule-group')).map(group => ({
                logic: group.querySelector('.group-logic').value,
                conditions: Array.from(group.querySelectorAll('.rule-condition-item')).map(item => ({
                    field: item.querySelector('.field').value,
                    operator: item.querySelector('.operator').value,
                    value: readValueFromRow(item.querySelector('.rule-row'))
                }))
            }))
        };
    }

    function validateManual() {
        const payload = coletarManual();
        const erros = [];

        if (!payload.groups.length) {
            erros.push('Adicione pelo menos um grupo com condições.');
        }

        payload.groups.forEach((group, gi) => {
            group.conditions.forEach((cond, ci) => {
                const campo = campoByChave(cond.field);
                const label = campo?.label || cond.field;
                if (!cond.field) erros.push(`Grupo ${gi + 1}, condição ${ci + 1}: selecione um campo.`);
                if (!cond.operator) erros.push(`Grupo ${gi + 1}, condição ${ci + 1}: selecione uma comparação.`);
                if (!NO_VALUE_OPS.includes(cond.operator) && (cond.value === '' || cond.value == null)) {
                    erros.push(`Grupo ${gi + 1}, condição ${ci + 1} (${label}): informe um valor.`);
                }
                if (cond.field === 'sexo' && cond.operator === 'equals' && !cond.value) {
                    erros.push(`Grupo ${gi + 1}: selecione Masculino ou Feminino.`);
                }
            });
        });

        return erros;
    }

    function showValidationError(msg) {
        const el = document.getElementById('msg');
        if (!el) return;
        const text = Array.isArray(msg) ? msg.map(e => `<li>${escapeHtml(e)}</li>`).join('') : escapeHtml(msg);
        el.innerHTML = Array.isArray(msg)
            ? `<div class="alert err"><strong>Corrija antes de continuar:</strong><ul class="validation-list">${text}</ul></div>`
            : `<div class="alert err">${text}</div>`;
    }

    function renderResumoCard(resumo, origem) {
        if (!resumo) return '';
        const headline = resumo.texto_completo
            ? resumo.texto_completo.replace(/\n(E|OU)\n/g, ' · ').replace(/\n/g, ' · ')
            : (resumo.resumo_humano || '');
        const count = lastPreviewData?.preview?.aprovados ?? lastPreviewData?.preview?.total;
        let html = '<div class="segment-summary segment-summary-premium">';
        if (headline) {
            html += `<div class="segment-summary-headline">${escapeHtml(headline)}</div>`;
        }
        if (count != null && lastPreviewData?.preview?.ok) {
            html += `<div class="segment-summary-estimate">Estimativa atual: <strong>${count}</strong> cliente${count === 1 ? '' : 's'}</div>`;
        }
        if (origem === 'ia' && resumo.interpretacao_ia) {
            html += `<div class="segment-summary-ia"><span class="segment-summary-label">Interpretação da IA</span><p>${escapeHtml(resumo.interpretacao_ia)}</p></div>`;
        }
        html += '<div class="segment-summary-label">Detalhamento das regras</div><div class="segment-summary-body">';
        (resumo.grupos || []).forEach((g, i) => {
            if (g.titulo) html += `<div class="segment-summary-group-title">${escapeHtml(g.titulo)}</div>`;
            const conds = g.condicoes || [];
            conds.forEach((c, j) => {
                if (j > 0) html += `<div class="segment-summary-logic">${escapeHtml(g.logic === 'OR' ? 'OU' : 'E')}</div>`;
                html += `<div class="segment-summary-rule">${escapeHtml(c)}</div>`;
            });
            if (i < (resumo.grupos.length - 1) && resumo.grupos.length > 1) {
                html += `<div class="segment-summary-logic segment-summary-logic-top">${escapeHtml(resumo.top_logic_label || 'E')}</div>`;
            }
        });
        html += '</div></div>';
        return html;
    }

    function renderPreviewTable(preview, explicacoes) {
        if (!preview?.ok || !explicacoes?.length) {
            return `<div class="empty-state compact"><p>${preview?.ok ? 'Nenhum cliente encontrado com estas regras.' : escapeHtml(preview?.erro || 'Erro na prévia.')}</p></div>`;
        }
        let html = `<div class="table-wrapper"><table class="data-table preview-table"><thead><tr>
            <th>Cliente</th><th>E-mail</th><th>Telefone</th><th>Por que entrou?</th><th></th>
        </tr></thead><tbody>`;
        explicacoes.forEach((exp, idx) => {
            const motivos = (exp.motivos_resumo || []).map(m => `<span class="motivo-chip">✅ ${escapeHtml(m)}</span>`).join('');
            html += `<tr class="preview-row" data-exp-index="${idx}">
                <td><strong>${escapeHtml(exp.nome)}</strong></td>
                <td class="td-muted">—</td>
                <td class="td-muted">—</td>
                <td><div class="motivos-cell">${motivos || '—'}</div></td>
                <td><button type="button" class="btn btn-ghost btn-sm" onclick="Segmentador.openDrawer(${idx})">Detalhes</button></td>
            </tr>`;
        });
        html += '</tbody></table></div>';
        return html;
    }

    function renderDebugPanel(data) {
        const p = data.preview || {};
        return `<div class="debug-panel">
            <div class="debug-panel-title">Como o sistema executou a consulta</div>
            <div class="debug-metrics">
                <div class="debug-metric"><span class="debug-metric-value">${p.analisados ?? p.total ?? 0}</span><span class="debug-metric-label">Analisados</span></div>
                <div class="debug-metric"><span class="debug-metric-value">${p.aprovados ?? p.total ?? 0}</span><span class="debug-metric-label">Aprovados</span></div>
                <div class="debug-metric"><span class="debug-metric-value">${p.tempo_ms ?? '—'} ms</span><span class="debug-metric-label">Tempo</span></div>
            </div>
            <details class="debug-details"><summary>JSON gerado</summary><pre class="pre">${escapeHtml(JSON.stringify(data.regra, null, 2))}</pre></details>
            <details class="debug-details"><summary>SQL gerada</summary><pre class="pre">${escapeHtml(data.sql || '')}</pre></details>
        </div>`;
    }

    function openDrawer(index) {
        const exp = lastPreviewData?.preview?.explicacoes?.[index]
            || window.lastPreviewData?.preview?.explicacoes?.[index];
        if (!exp) return;
        const drawer = document.getElementById('explicacaoDrawer');
        const body = document.getElementById('explicacaoDrawerBody');
        if (!drawer || !body) return;

        let html = `<div class="drawer-client-header">
            <h3>${escapeHtml(exp.nome)}</h3>
            <span class="drawer-status ${exp.approved ? 'approved' : 'rejected'}">${exp.status_icon} ${escapeHtml(exp.status_label)}</span>
        </div>`;

        (exp.grupos || []).forEach(grupo => {
            if ((exp.grupos || []).length > 1) {
                html += `<div class="drawer-group-title">Grupo ${grupo.grupo_num} ${grupo.passed ? '✅' : '❌'}</div>`;
            }
            (grupo.condicoes || []).forEach(cond => {
                html += `<div class="drawer-rule-card">
                    <div class="drawer-rule-title">Regra ${cond.regra_num} ${cond.result_icon}</div>
                    <dl class="drawer-rule-dl">
                        <dt>Campo</dt><dd>${escapeHtml(cond.field_label)}</dd>
                        <dt>Esperado</dt><dd>${escapeHtml(cond.expected)}</dd>
                        <dt>Encontrado</dt><dd>${escapeHtml(cond.found)}</dd>
                        <dt>Resultado</dt><dd>${cond.result_icon}</dd>
                    </dl>
                    ${cond.descricao ? `<p class="drawer-rule-hint">${escapeHtml(cond.descricao)}</p>` : ''}
                </div>`;
            });
        });

        body.innerHTML = html;
        drawer.classList.add('is-open');
        drawer.setAttribute('aria-hidden', 'false');
    }

    function closeDrawer() {
        const drawer = document.getElementById('explicacaoDrawer');
        drawer?.classList.remove('is-open');
        drawer?.setAttribute('aria-hidden', 'true');
    }

    function enrichExplicacoesWithRowData(explicacoes, exemplos) {
        return (explicacoes || []).map((exp, i) => {
            const row = exemplos[i] || {};
            return Object.assign({}, exp, {
                _row: row,
                email: row.cli_email || '—',
                telefone: row.cli_telefone || '—'
            });
        });
    }

    function renderPreviewResults(data, origem) {
        lastPreviewData = data;
        const resumoEl = document.getElementById('resumoCard');
        const previewEl = document.getElementById('previewTable');
        const debugEl = document.getElementById('debugPanel');
        if (resumoEl) resumoEl.innerHTML = renderResumoCard(data.resumo, origem);
        if (previewEl) {
            const enriched = enrichExplicacoesWithRowData(data.preview?.explicacoes, data.preview?.exemplos);
            if (data.preview) data.preview.explicacoes = enriched;
            previewEl.innerHTML = renderPreviewTable(data.preview, enriched);
            previewEl.querySelectorAll('.preview-row').forEach((tr, i) => {
                const exp = enriched[i];
                if (!exp) return;
                const cells = tr.querySelectorAll('td');
                if (cells[1]) cells[1].textContent = exp.email || '—';
                if (cells[2]) cells[2].textContent = exp.telefone || '—';
            });
        }
        if (debugEl) debugEl.innerHTML = renderDebugPanel(data);
        updateLiveSummary();
    }

    window.Segmentador = {
        OP_LABELS, LOGIC_LABELS, addGroup, addCondition, removeCondition, moveCondition,
        refreshOps, refreshValueHint, refreshGroupConnectors, refreshGroupSeparators,
        coletarManual, validateManual, confirmRemoveGroup, cancelRemoveGroup, executeRemoveGroup,
        duplicateGroup, renumberGroups, openDrawer, closeDrawer, renderPreviewResults,
        showValidationError, renderResumoCard, updateLiveSummary, buildCompactHeadline
    };
})(window);
