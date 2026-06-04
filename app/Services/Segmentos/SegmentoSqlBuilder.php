<?php

namespace App\Services\Segmentos;

use App\Models\SegmentoClienteCampo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
                if ($field === 'idade' && $this->isSqlite()) {
                    $expr = "(CAST(strftime('%Y','now','localtime') AS INTEGER) - CAST(strftime('%Y', c.cli_data_nascimento) AS INTEGER) - (strftime('%m-%d','now','localtime') < strftime('%m-%d', c.cli_data_nascimento)))";
                }
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
            $select[] = 'COALESCE(ps.qtd_pedidos, 0) AS cli_qtd_pedidos_calc';
            $select[] = 'ps.ultimo_pedido AS cli_ultimo_pedido_calc';
            $select[] = 'ps.primeira_compra AS cli_primeira_compra_calc';
            $select[] = 'COALESCE(ps.valor_total_comprado, 0) AS cli_valor_total_comprado_calc';
        }

        if (isset($joins['produto_stats'])) {
            $select[] = 'prod.produtos_comprados AS cli_produtos_comprados_calc';
        }

        if (isset($joins['cashback'])) {
            $select[] = 'COALESCE(cb.cashback, 0) AS cli_cashback_calc';
        }

        return implode(', ', $select);
    }

    private function aplicarJoinNecessario(string $field, array &$joins): void
    {
        if (in_array($field, ['qtd_pedidos', 'ultimo_pedido', 'primeira_compra', 'valor_total_comprado', 'status_pedido', 'canal_pedido', 'forma_pagamento'], true)) {
            $joins['pedido_stats'] = "LEFT JOIN (SELECT p.cliente_id, COUNT(*) qtd_pedidos, MAX(COALESCE(p.ped_data, p.cadastrado)) ultimo_pedido, MIN(COALESCE(p.ped_data, p.cadastrado)) primeira_compra, SUM(COALESCE(p.ped_valor_total, 0)) valor_total_comprado, " . $this->groupConcatDistinctSql('s.sta_nome') . " status_pedido, NULL canal_pedido, NULL forma_pagamento FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.excluido IS NULL AND s.sta_confirmado = 'S' GROUP BY p.cliente_id) ps ON ps.cliente_id = c.cliente_id";
        }

        if (in_array($field, ['produto_comprado', 'produto_nome', 'produto'], true)) {
            $joins['produto_stats'] = $this->produtoJoinSql();
        }

        if (in_array($field, ['cashback', 'cashback_expira_em'], true)) {
            $joins['cashback'] = $this->cashbackJoinSql();
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

        if (in_array($field, ['qtd_pedidos', 'ultimo_pedido', 'primeira_compra', 'valor_total_comprado', 'status_pedido', 'canal_pedido', 'forma_pagamento'], true)) {
            $this->aplicarJoinNecessario((string)$field, $joins);
        }

        if (in_array($field, ['produto_comprado', 'produto_nome', 'produto'], true)) {
            $this->aplicarJoinNecessario('produto_comprado', $joins);
        }
    }

    private function normalizarValor(string $field, string $op, mixed $value): mixed
    {
        if (is_string($value) && in_array($field, ['sexo', 'newsletter', 'funcionario', 'bairro', 'municipio', 'estado', 'email', 'telefone', 'cpf', 'nome', 'produto_comprado', 'produto_nome', 'produto'], true)) {
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
            $sexoExpr = $this->lowerTextSql($expr);

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
            $boolExpr = $this->lowerTextSql($expr);

            return $op === 'equals'
                ? ["{$boolExpr} IN ({$placeholders})", $valores]
                : ["({$expr} IS NULL OR {$boolExpr} NOT IN ({$placeholders}))", $valores];
        }

        if (in_array($field, ['produto_comprado', 'produto_nome', 'produto'], true)) {
            return $this->produtoConditionToSql($op, (string)$value);
        }

        if (in_array($field, ['bairro', 'municipio', 'estado', 'email', 'telefone', 'cpf', 'nome', 'origem_contato', 'status_pedido', 'canal_pedido', 'forma_pagamento'], true)) {
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
            'more_than_x_days_ago' => $this->daysAgoSql($expr, 'more', (int)$value),
            'less_than_x_days_ago' => $this->daysAgoSql($expr, 'less', (int)$value),
            'last_x_days' => $this->daysAgoSql($expr, 'less', (int)$value),
            'next_x_days' => $this->nextDaysSql($expr, (int)$value),
            'exactly_x_days_ago' => $this->exactlyDaysAgoSql($expr, (int)$value),
            'yesterday' => $this->yesterdaySql($expr),
            'before_date' => [$this->dateOnlySql($expr) . ' < ?', [$value]],
            'after_date' => [$this->dateOnlySql($expr) . ' > ?', [$value]],
            'equals_date' => [$this->dateOnlySql($expr) . ' = ?', [$value]],
            'today' => $this->todaySql($expr),
            'month_equals' => $this->monthEqualsSql($expr, $value),
            'month_between' => $this->monthBetweenSql($expr, $value),
            'is_true' => (str_contains($expr, 'cli_newsletter') || str_contains($expr, 'cli_funcionario')) ? ["{$expr} = 'SIM'", []] : ["{$expr} IS NOT NULL", []],
            'is_false' => (str_contains($expr, 'cli_newsletter') || str_contains($expr, 'cli_funcionario')) ? ["{$expr} = 'NÃO'", []] : ["{$expr} IS NULL", []],
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
        $textExpr = $this->lowerTextSql($expr);
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

    private function todaySql(string $expr): array
    {
        if (str_contains($expr, 'cli_data_nascimento')) {
            return $this->isSqlite()
                ? ["strftime('%m-%d', {$expr}) = strftime('%m-%d', 'now', 'localtime')", []]
                : ["MONTH({$expr}) = MONTH(CURDATE()) AND DAY({$expr}) = DAY(CURDATE())", []];
        }

        return [$this->dateOnlySql($expr) . ' = ' . $this->currentDateSql(), []];
    }

    private function monthEqualsSql(string $expr, mixed $value): array
    {
        if ($value === 'current' || $value === 'atual') {
            return $this->isSqlite()
                ? ["strftime('%m', {$expr}) = strftime('%m', 'now', 'localtime')", []]
                : ["MONTH({$expr}) = MONTH(CURDATE())", []];
        }

        $month = max(1, min((int)$value, 12));

        return $this->isSqlite()
            ? ["strftime('%m', {$expr}) = ?", [str_pad((string)$month, 2, '0', STR_PAD_LEFT)]]
            : ["MONTH({$expr}) = ?", [$month]];
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

        return $this->isSqlite()
            ? ["CAST(strftime('%m', {$expr}) AS INTEGER) BETWEEN ? AND ?", [$start, $end]]
            : ["MONTH({$expr}) BETWEEN ? AND ?", [$start, $end]];
    }

    private function produtoConditionToSql(string $op, string $value): array
    {
        $expr = $this->lowerTextSql('prod.produtos_comprados');
        $value = $this->normalizarTextoBusca($value);

        return match ($op) {
            'equals', 'contains' => ["{$expr} LIKE ?", ['%' . $value . '%']],
            'not_equals', 'not_contains' => ["(prod.produtos_comprados IS NULL OR {$expr} NOT LIKE ?)", ['%' . $value . '%']],
            'starts_with' => ["{$expr} LIKE ?", [$value . '%']],
            'ends_with' => ["{$expr} LIKE ?", ['%' . $value]],
            'is_empty' => ["(prod.produtos_comprados IS NULL OR prod.produtos_comprados = '')", []],
            'is_not_empty' => ["(prod.produtos_comprados IS NOT NULL AND prod.produtos_comprados <> '')", []],
            default => throw new RuntimeException("Operador de produto não implementado: {$op}"),
        };
    }

    private function produtoJoinSql(): string
    {
        if (Schema::hasTable('pedido_produto_cliente') && Schema::hasTable('produtos')) {
            return "LEFT JOIN (SELECT ppc.cliente_id, " . $this->groupConcatDistinctSql('pr.nome') . " produtos_comprados FROM pedido_produto_cliente ppc INNER JOIN produtos pr ON pr.produto_id = ppc.produto_id WHERE COALESCE(pr.ativo, 'S') = 'S' GROUP BY ppc.cliente_id) prod ON prod.cliente_id = c.cliente_id";
        }

        if (Schema::hasTable('pedido_item') && Schema::hasTable('produto')) {
            $wherePi = Schema::hasColumn('pedido_item', 'excluido') ? ' AND pi.excluido IS NULL' : '';
            $wherePr = Schema::hasColumn('produto', 'excluido') ? ' AND pr.excluido IS NULL' : '';

            return "LEFT JOIN (SELECT p.cliente_id, " . $this->groupConcatDistinctSql('pr.pro_nome') . " produtos_comprados FROM pedido p INNER JOIN status s ON s.status_id = p.status_id INNER JOIN pedido_item pi ON pi.pedido_id = p.pedido_id{$wherePi} INNER JOIN produto pr ON pr.produto_id = pi.produto_id{$wherePr} WHERE p.excluido IS NULL AND s.sta_confirmado = 'S' GROUP BY p.cliente_id) prod ON prod.cliente_id = c.cliente_id";
        }

        return "LEFT JOIN (SELECT NULL cliente_id, NULL produtos_comprados WHERE 1 = 0) prod ON prod.cliente_id = c.cliente_id";
    }

    private function cashbackJoinSql(): string
    {
        $expira = $this->isSqlite()
            ? "MAX(datetime(COALESCE(cadastrado, 'now'), '+30 days'))"
            : "MAX(DATE_ADD(COALESCE(cadastrado, NOW()), INTERVAL 30 DAY))";

        return "LEFT JOIN (SELECT cliente_id, SUM(cas_valor) cashback, {$expira} cashback_expira_em FROM cashback WHERE excluido IS NULL GROUP BY cliente_id) cb ON cb.cliente_id = c.cliente_id";
    }

    private function groupConcatDistinctSql(string $column): string
    {
        return $this->isSqlite()
            ? "GROUP_CONCAT(DISTINCT {$column})"
            : "GROUP_CONCAT(DISTINCT {$column} ORDER BY {$column} SEPARATOR ' ')";
    }

    private function daysAgoSql(string $expr, string $mode, int $days): array
    {
        if ($this->isSqlite()) {
            $modifier = '-' . max(0, $days) . ' days';
            $operator = $mode === 'more' ? '<' : '>=';
            return [$this->dateOnlySql($expr) . " {$operator} date('now', 'localtime', ?)", [$modifier]];
        }

        $operator = $mode === 'more' ? '<' : '>=';
        return ["{$expr} {$operator} DATE_SUB(NOW(), INTERVAL ? DAY)", [$days]];
    }

    private function nextDaysSql(string $expr, int $days): array
    {
        if ($this->isSqlite()) {
            return [$this->dateOnlySql($expr) . " BETWEEN date('now', 'localtime') AND date('now', 'localtime', ?)", ['+' . max(0, $days) . ' days']];
        }

        return ["{$expr} BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL ? DAY)", [$days]];
    }

    private function exactlyDaysAgoSql(string $expr, int $days): array
    {
        if ($this->isSqlite()) {
            return [$this->dateOnlySql($expr) . " = date('now', 'localtime', ?)", ['-' . max(0, $days) . ' days']];
        }

        return ["DATE({$expr}) = DATE(DATE_SUB(NOW(), INTERVAL ? DAY))", [$days]];
    }

    private function yesterdaySql(string $expr): array
    {
        return $this->isSqlite()
            ? [$this->dateOnlySql($expr) . " = date('now', 'localtime', '-1 day')", []]
            : ["DATE({$expr}) = DATE(DATE_SUB(CURDATE(), INTERVAL 1 DAY))", []];
    }

    private function dateOnlySql(string $expr): string
    {
        return $this->isSqlite() ? "date({$expr})" : "DATE({$expr})";
    }

    private function currentDateSql(): string
    {
        return $this->isSqlite() ? "date('now', 'localtime')" : 'CURDATE()';
    }

    private function lowerTextSql(string $expr): string
    {
        $type = $this->isSqlite() ? 'TEXT' : 'CHAR';
        return "LOWER(TRIM(CAST({$expr} AS {$type})))";
    }

    private function randomFunctionSql(): string
    {
        return $this->isSqlite() ? 'RANDOM()' : 'RAND()';
    }

    private function isSqlite(): bool
    {
        return DB::getDriverName() === 'sqlite';
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
            default => 'ORDER BY ' . $this->randomFunctionSql(),
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
