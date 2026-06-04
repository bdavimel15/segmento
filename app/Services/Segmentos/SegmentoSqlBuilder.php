<?php

namespace App\Services\Segmentos;

use App\Models\SegmentoClienteCampo;
use RuntimeException;

class SegmentoSqlBuilder
{
    public function build(array $regra): array
    {
        $campos = SegmentoClienteCampo::where('ativo', 'S')->get()->keyBy('chave');
        $topLogic = SegmentoRuleHelper::getTopLogic($regra) === 'OR' ? ' OR ' : ' AND ';
        $where = ['c.excluido IS NULL'];
        $bindings = [];
        $joins = [];

        $this->aplicarJoinDeOrdenacao($regra['order'] ?? [], $joins);

        $groups = SegmentoRuleHelper::getGroups($regra);

        foreach ($groups as $group) {
            $groupLogic = strtoupper($group['logic'] ?? 'AND') === 'OR' ? ' OR ' : ' AND ';
            $groupWhere = [];

            foreach (($group['conditions'] ?? []) as $condition) {
                $field = (string)($condition['field'] ?? '');
                $campo = $campos->get($field);

                if (!$campo) {
                    throw new RuntimeException('Campo inválido no SQL Builder: ' . $field);
                }

                $this->aplicarJoinNecessario($field, $joins);

                $expr = $campo->expressao_sql ?: ($campo->origem_tabela . '.' . $campo->origem_coluna);
                $op = (string)($condition['operator'] ?? '');
                $value = $condition['value'] ?? null;

                $value = $this->normalizarValor($field, $op, $value);

                [$sqlPart, $partBindings] = $this->conditionToSql($expr, $op, $value, $field);
                $groupWhere[] = $sqlPart;
                $bindings = array_merge($bindings, $partBindings);
            }

            if ($groupWhere !== []) {
                $where[] = '(' . implode($groupLogic, $groupWhere) . ')';
            }
        }

        $limit = min(max((int)($regra['limit'] ?? 25), 1), 500);
        $orderSql = $this->orderSql($regra['order'] ?? []);
        $select = $this->selectSql($joins);

        $sql = "SELECT {$select} FROM cliente c " . implode(' ', $joins) . ' WHERE ' . implode($topLogic, $where) . " {$orderSql} LIMIT {$limit}";

        $this->bloquearSqlPerigoso($sql);

        return ['sql' => $sql, 'bindings' => $bindings];
    }

    private function selectSql(array $joins): string
    {
        $select = ['c.*'];

        if (isset($joins['pedido_stats'])) {
            // Aliases usados pelo painel de detalhes/explicação.
            // Sem esses aliases, a SQL filtra corretamente, mas o drawer tenta validar
            // campos calculados como null e mostra clientes aprovados como reprovados.
            $select[] = 'COALESCE(ps.qtd_pedidos, 0) AS _seg_qtd_pedidos';
            $select[] = 'COALESCE(ps.qtd_pedidos, 0) AS _seg_qtd_pedidos_confirmados';
            $select[] = 'ps.ultimo_pedido AS _seg_ultimo_pedido';
            $select[] = 'ps.ultimo_pedido AS _seg_ultima_compra';
            $select[] = 'ps.primeira_compra AS _seg_primeira_compra';
            $select[] = 'COALESCE(ps.valor_total_comprado, 0) AS _seg_valor_total_comprado';
            $select[] = 'COALESCE(ps.valor_total_comprado, 0) AS _seg_valor_total_compras';
        }

        if (isset($joins['produto_stats'])) {
            $select[] = 'prod.produtos_comprados AS _seg_produto_comprado';
            $select[] = 'prod.produtos_comprados AS _seg_produto_nome';
            $select[] = 'prod.produtos_comprados AS _seg_produto';
        }

        if (isset($joins['pedido_canais'])) {
            $select[] = 'pcanal.status_pedido AS _seg_status_pedido';
            $select[] = 'pcanal.canal_pedido AS _seg_canal_pedido';
            $select[] = 'pcanal.forma_pagamento AS _seg_forma_pagamento';
        }

        if (isset($joins['cashback'])) {
            $select[] = 'COALESCE(cb.cashback, 0) AS _seg_cashback';
            $select[] = 'COALESCE(cb.cashback, 0) AS _seg_cashback_saldo';
            $select[] = 'cb.cashback_expira_em AS _seg_cashback_expira_em';
        }

        return implode(', ', $select);
    }

    private function aplicarJoinNecessario(string $field, array &$joins): void
    {
        if (in_array($field, ['qtd_pedidos', 'qtd_pedidos_confirmados', 'ultimo_pedido', 'ultima_compra', 'primeira_compra', 'valor_total_comprado', 'valor_total_compras'], true)) {
            $joins['pedido_stats'] = "LEFT JOIN (SELECT p.cliente_id, COUNT(*) qtd_pedidos, MAX(COALESCE(p.ped_data, p.cadastrado)) ultimo_pedido, MIN(COALESCE(p.ped_data, p.cadastrado)) primeira_compra, SUM(COALESCE(p.ped_valor_total, 0)) valor_total_comprado FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.excluido IS NULL AND s.sta_confirmado = 'S' GROUP BY p.cliente_id) ps ON ps.cliente_id = c.cliente_id";
        }

        if (in_array($field, ['produto_comprado', 'produto_nome', 'produto'], true)) {
            $this->aplicarJoinNecessario('qtd_pedidos', $joins);

            $produtoAgg = $this->isSqlite()
                ? "GROUP_CONCAT(DISTINCT pr.pro_nome) produtos_comprados"
                : "GROUP_CONCAT(DISTINCT pr.pro_nome ORDER BY pr.pro_nome SEPARATOR ' ') produtos_comprados";

            $joins['produto_stats'] = "LEFT JOIN (SELECT p.cliente_id, {$produtoAgg} FROM pedido p INNER JOIN status s ON s.status_id = p.status_id INNER JOIN pedido_item pi ON pi.pedido_id = p.pedido_id AND pi.excluido IS NULL INNER JOIN produto pr ON pr.produto_id = pi.produto_id AND pr.excluido IS NULL WHERE p.excluido IS NULL AND s.sta_confirmado = 'S' GROUP BY p.cliente_id) prod ON prod.cliente_id = c.cliente_id";
        }

        if (in_array($field, ['cashback', 'cashback_saldo', 'cashback_expira_em'], true)) {
            $expira = $this->isSqlite()
                ? "MAX(datetime(COALESCE(cadastrado, CURRENT_TIMESTAMP), '+30 days'))"
                : "MAX(DATE_ADD(COALESCE(cadastrado, NOW()), INTERVAL 30 DAY))";

            $joins['cashback'] = "LEFT JOIN (SELECT cliente_id, SUM(cas_valor) cashback, {$expira} cashback_expira_em FROM cashback WHERE excluido IS NULL GROUP BY cliente_id) cb ON cb.cliente_id = c.cliente_id";
        }

        if (in_array($field, ['status_pedido', 'canal_pedido', 'forma_pagamento'], true)) {
            $joins['pedido_canais'] = "LEFT JOIN (SELECT p.cliente_id, MAX(s.sta_nome) status_pedido, MAX(p.canal_pedido) canal_pedido, MAX(p.forma_pagamento) forma_pagamento FROM pedido p LEFT JOIN status s ON s.status_id = p.status_id WHERE p.excluido IS NULL GROUP BY p.cliente_id) pcanal ON pcanal.cliente_id = c.cliente_id";
        }

        if ($field === 'carrinho_abandonado') {
            $joins['carrinho'] = "LEFT JOIN view_carrinho_abandonado vca ON vca.cliente_id = c.cliente_id";
        }

        if ($field === 'recebeu_notificacao_nos_ultimos_dias') {
            $joins['notificacao'] = "LEFT JOIN notificacao_programada_envio npe ON npe.cliente_id = c.cliente_id";
        }
    }

    private function aplicarJoinDeOrdenacao(mixed $order, array &$joins): void
    {
        if (!is_array($order)) {
            return;
        }

        $field = $order['field'] ?? null;

        if (in_array($field, ['qtd_pedidos', 'qtd_pedidos_confirmados', 'ultimo_pedido', 'ultima_compra', 'primeira_compra', 'valor_total_comprado', 'valor_total_compras'], true)) {
            $this->aplicarJoinNecessario((string)$field, $joins);
        }

        if (in_array($field, ['produto_comprado', 'produto_nome', 'produto'], true)) {
            $this->aplicarJoinNecessario('produto_comprado', $joins);
        }
    }

    private function normalizarValor(string $field, string $op, mixed $value): mixed
    {
        if (is_string($value) && in_array($field, ['sexo', 'newsletter', 'funcionario', 'bairro', 'municipio', 'estado', 'email', 'telefone', 'cpf', 'nome', 'origem_contato', 'status_pedido', 'canal_pedido', 'forma_pagamento', 'produto_comprado', 'produto_nome', 'produto'], true)) {
            $value = trim($value);
        }

        if (in_array($field, ['newsletter', 'funcionario'], true) && in_array($op, ['equals', 'not_equals'], true) && is_string($value)) {
            $v = $this->normalizarTextoBusca($value);
            return in_array($v, ['sim', 's', 'true', '1', 'aceita', 'ativo', 'ativos', 'assina', 'assinante'], true) ? 'sim' : 'nao';
        }

        return $value;
    }

    private function conditionToSql(string $expr, string $op, mixed $value, string $field = ''): array
    {
        if ($field === 'sexo' && in_array($op, ['equals', 'not_equals'], true)) {
            $valores = $this->valoresSexo($value);
            $placeholders = implode(',', array_fill(0, count($valores), '?'));
            $sexoExpr = "LOWER(TRIM(CAST(({$expr}) AS CHAR)))";

            return $op === 'equals'
                ? ["{$sexoExpr} IN ({$placeholders})", $valores]
                : ["(({$expr}) IS NULL OR {$sexoExpr} NOT IN ({$placeholders}))", $valores];
        }

        if (in_array($field, ['newsletter', 'funcionario'], true) && in_array($op, ['equals', 'not_equals'], true)) {
            $valorBool = $this->normalizarTextoBusca((string)$value);
            $valores = in_array($valorBool, ['sim', 's', 'true', '1', 'aceita', 'ativo', 'ativos', 'assina', 'assinante'], true)
                ? ['s', 'sim', '1', 'true']
                : ['n', 'nao', 'não', '0', 'false'];
            $placeholders = implode(',', array_fill(0, count($valores), '?'));
            $boolExpr = "LOWER(TRIM(CAST({$expr} AS CHAR)))";

            return $op === 'equals'
                ? ["{$boolExpr} IN ({$placeholders})", $valores]
                : ["({$expr} IS NULL OR {$boolExpr} NOT IN ({$placeholders}))", $valores];
        }

        if (in_array($field, ['bairro', 'municipio', 'estado', 'email', 'telefone', 'cpf', 'nome', 'origem_contato', 'status_pedido', 'canal_pedido', 'forma_pagamento', 'produto_comprado', 'produto_nome', 'produto'], true)) {
            return $this->textoConditionToSql($expr, $op, (string)$value);
        }

        return match ($op) {
            'equals' => ["{$expr} = ?", [$value]],
            'not_equals' => ["{$expr} <> ?", [$value]],
            'greater_than' => ["COALESCE({$expr}, 0) > ?", [$value]],
            'greater_or_equal' => ["COALESCE({$expr}, 0) >= ?", [$value]],
            'less_than' => ["COALESCE({$expr}, 0) < ?", [$value]],
            'less_or_equal' => ["COALESCE({$expr}, 0) <= ?", [$value]],
            'contains' => ["{$expr} LIKE ?", ['%' . $value . '%']],
            'not_contains' => ["{$expr} NOT LIKE ?", ['%' . $value . '%']],
            'starts_with' => ["{$expr} LIKE ?", [$value . '%']],
            'ends_with' => ["{$expr} LIKE ?", ['%' . $value]],
            'is_empty' => ["({$expr} IS NULL OR {$expr} = '')", []],
            'is_not_empty' => ["({$expr} IS NOT NULL AND {$expr} <> '')", []],
            'more_than_x_days_ago' => $this->isSqlite() ? ["datetime({$expr}) < datetime('now', '-' || ? || ' days')", [(int)$value]] : ["{$expr} < DATE_SUB(NOW(), INTERVAL ? DAY)", [(int)$value]],
            'less_than_x_days_ago' => $this->isSqlite() ? ["datetime({$expr}) >= datetime('now', '-' || ? || ' days')", [(int)$value]] : ["{$expr} >= DATE_SUB(NOW(), INTERVAL ? DAY)", [(int)$value]],
            'last_x_days' => $this->isSqlite() ? ["datetime({$expr}) >= datetime('now', '-' || ? || ' days')", [(int)$value]] : ["{$expr} >= DATE_SUB(NOW(), INTERVAL ? DAY)", [(int)$value]],
            'next_x_days' => $this->isSqlite() ? ["datetime({$expr}) BETWEEN datetime('now') AND datetime('now', '+' || ? || ' days')", [(int)$value]] : ["{$expr} BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)", [(int)$value]],
            'exactly_x_days_ago' => $this->isSqlite() ? ["date({$expr}) = date('now', '-' || ? || ' days')", [(int)$value]] : ["DATE({$expr}) = DATE(DATE_SUB(NOW(), INTERVAL ? DAY))", [(int)$value]],
            'yesterday' => $this->isSqlite() ? ["date({$expr}) = date('now', '-1 day')", []] : ["DATE({$expr}) = DATE(DATE_SUB(CURDATE(), INTERVAL 1 DAY))", []],
            'before_date' => ["DATE({$expr}) < ?", [$value]],
            'after_date' => ["DATE({$expr}) > ?", [$value]],
            'equals_date' => ["DATE({$expr}) = ?", [$value]],
            'today' => $this->todaySql($expr),
            'month_equals' => $this->monthEqualsSql($expr, $value),
            'month_between' => $this->monthBetweenSql($expr, $value),
            'is_true' => (str_contains($expr, 'cli_newsletter') || str_contains($expr, 'cli_funcionario')) ? ["UPPER(TRIM({$expr})) IN ('S','SIM','1','TRUE')", []] : ["{$expr} IS NOT NULL", []],
            'is_false' => (str_contains($expr, 'cli_newsletter') || str_contains($expr, 'cli_funcionario')) ? ["UPPER(TRIM({$expr})) IN ('N','NAO','NÃO','0','FALSE')", []] : ["{$expr} IS NULL", []],
            'exists' => ["{$expr} IS NOT NULL", []],
            'not_exists' => ["{$expr} IS NULL", []],
            default => throw new RuntimeException("Operador SQL não implementado: {$op}"),
        };
    }

    private function valoresSexo(mixed $value): array
    {
        $v = $this->normalizarTextoBusca((string)$value);

        return match ($v) {
            'homem', 'homens', 'masculino', 'masculinos', 'macho', 'machos', 'male', 'males', 'm' => ['m', 'masculino', 'homem', 'homens', '1'],
            'mulher', 'mulheres', 'feminino', 'femininos', 'femea', 'femeas', 'female', 'females', 'f' => ['f', 'feminino', 'mulher', 'mulheres', '2'],
            default => [$v],
        };
    }

    private function normalizarTextoBusca(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'], ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'], $v);
        return preg_replace('/[^a-z0-9@._\- ]+/u', '', $v) ?: $v;
    }

    private function textoConditionToSql(string $expr, string $op, string $value): array
    {
        $textExpr = "LOWER(TRIM(CAST({$expr} AS CHAR)))";
        $value = $this->normalizarTextoBusca($value);

        return match ($op) {
            'equals' => ["{$textExpr} = ?", [$value]],
            'not_equals' => ["({$expr} IS NULL OR {$textExpr} <> ?)", [$value]],
            'contains' => ["{$textExpr} LIKE ?", ['%' . $value . '%']],
            'not_contains' => ["({$expr} IS NULL OR {$textExpr} NOT LIKE ?)", ['%' . $value . '%']],
            'starts_with' => ["{$textExpr} LIKE ?", [$value . '%']],
            'ends_with' => ["{$textExpr} LIKE ?", ['%' . $value]],
            'is_empty' => ["({$expr} IS NULL OR {$expr} = '')", []],
            'is_not_empty' => ["({$expr} IS NOT NULL AND {$expr} <> '')", []],
            default => throw new RuntimeException("Operador de texto não implementado: {$op}"),
        };
    }

    private function isSqlite(): bool
    {
        return strtolower((string) config('database.default')) === 'sqlite';
    }

    private function todaySql(string $expr): array
    {
        if (str_contains($expr, 'cli_data_nascimento')) {
            return $this->isSqlite()
                ? ["strftime('%m-%d', {$expr}) = strftime('%m-%d', 'now')", []]
                : ["MONTH({$expr}) = MONTH(CURDATE()) AND DAY({$expr}) = DAY(CURDATE())", []];
        }

        return $this->isSqlite()
            ? ["date({$expr}) = date('now')", []]
            : ["DATE({$expr}) = CURDATE()", []];
    }

    private function monthEqualsSql(string $expr, mixed $value): array
    {
        if ($value === 'current' || $value === 'atual') {
            return $this->isSqlite()
                ? ["strftime('%m', {$expr}) = strftime('%m', 'now')", []]
                : ["MONTH({$expr}) = MONTH(CURDATE())", []];
        }

        return $this->isSqlite()
            ? ["CAST(strftime('%m', {$expr}) AS INTEGER) = ?", [(int)$value]]
            : ["MONTH({$expr}) = ?", [(int)$value]];
    }

    private function monthBetweenSql(string $expr, mixed $value): array
    {
        if (is_array($value)) {
            $start = (int)($value[0] ?? 1);
            $end = (int)($value[1] ?? 12);
        } else {
            $parts = preg_split('/\s*,\s*|\s+e\s+|\s+a\s+|\s+até\s+/iu', (string)$value, -1, PREG_SPLIT_NO_EMPTY);
            $start = (int)($parts[0] ?? 1);
            $end = (int)($parts[1] ?? 12);
        }

        $start = max(1, min($start, 12));
        $end = max(1, min($end, 12));

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return $this->isSqlite() ? ["CAST(strftime('%m', {$expr}) AS INTEGER) BETWEEN ? AND ?", [$start, $end]] : ["MONTH({$expr}) BETWEEN ? AND ?", [$start, $end]];
    }

    private function orderSql(array $order): string
    {
        $field = $order['field'] ?? 'random';
        $direction = strtoupper($order['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        return match ($field) {
            'mais_recentes' => 'ORDER BY c.cadastrado DESC',
            'mais_antigos' => 'ORDER BY c.cadastrado ASC',
            'qtd_pedidos', 'qtd_pedidos_desc', 'mais_pedidos' => 'ORDER BY COALESCE(ps.qtd_pedidos, 0) DESC',
            'qtd_pedidos_asc', 'menos_pedidos' => 'ORDER BY COALESCE(ps.qtd_pedidos, 0) ASC',
            'valor_total_comprado', 'maior_valor' => 'ORDER BY COALESCE(ps.valor_total_comprado, 0) DESC',
            'ultimo_pedido_desc', 'ultima_compra_desc' => 'ORDER BY ps.ultimo_pedido DESC',
            'ultimo_pedido_asc', 'ultima_compra_asc' => 'ORDER BY ps.ultimo_pedido ASC',
            'produto_comprado', 'produto_nome', 'produto' => "ORDER BY prod.produtos_comprados {$direction}",
            'data_cadastro' => "ORDER BY c.cadastrado {$direction}",
            default => $this->isSqlite() ? 'ORDER BY RANDOM()' : 'ORDER BY RAND()',
        };
    }

    private function bloquearSqlPerigoso(string $sql): void
    {
        if (!preg_match('/^\s*SELECT\b/i', $sql)) {
            throw new RuntimeException('Apenas SELECT é permitido.');
        }

        if (preg_match('/\b(DELETE|UPDATE|INSERT|DROP|ALTER|TRUNCATE|CREATE)\b/i', $sql)) {
            throw new RuntimeException('SQL perigosa bloqueada.');
        }
    }
}
