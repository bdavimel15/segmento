<?php

namespace App\Services\Segmentos;

use App\Models\Cliente;
use App\Models\SegmentoClienteCampo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Converte filtros semânticos em consultas Eloquent/Query Builder.
 */
class SegmentoEloquentBuilder
{
    private Collection $campos;

    public function __construct(?Collection $campos = null)
    {
        $this->campos = $campos ?? SegmentoClienteCampo::where('ativo', 'S')->get()->keyBy('chave');
    }

    /**
     * @return array{query: Builder, limit: int, fields_needed: array<int, string>}
     */
    public function build(array $regra): array
    {
        $groups = SegmentoRuleHelper::getGroups($regra);

        if ($groups === []) {
            throw new RuntimeException('Nenhum grupo de condições para consulta Eloquent.');
        }

        $fieldsNeeded = $this->collectFields($groups);
        $limit = min(max((int) ($regra['limit'] ?? 25), 1), 500);

        $query = Cliente::query()->whereNull('excluido');
        $this->applyAggregations($query, $fieldsNeeded, $regra['order'] ?? []);

        $topLogic = SegmentoRuleHelper::getTopLogic($regra);

        $query->where(function (Builder $outer) use ($groups, $topLogic) {
            foreach ($groups as $index => $group) {
                $callback = function (Builder $groupQuery) use ($group) {
                    $this->applyGroup($groupQuery, $group);
                };

                if ($index === 0) {
                    $outer->where($callback);
                } elseif ($topLogic === 'OR') {
                    $outer->orWhere($callback);
                } else {
                    $outer->where($callback);
                }
            }
        });

        $this->applyOrder($query, $regra['order'] ?? [], $fieldsNeeded);

        return ['query' => $query, 'limit' => $limit, 'fields_needed' => $fieldsNeeded];
    }

    /**
     * @param  array{query: Builder, limit: int, fields_needed: array<int, string>}  $built
     * @return array<int, array<string, mixed>>
     */
    public function fetch(array $built): array
    {
        /** @var Builder $query */
        $query = $built['query'];
        $limit = $built['limit'];

        $models = (clone $query)->limit($limit)->get();
        $enricher = new SegmentoResultEnricher();

        return $enricher->toRows($models, $built['fields_needed']);
    }

    /** @param  array<int, array{logic: string, conditions: array<int, array<string, mixed>>}>  $groups */
    private function collectFields(array $groups): array
    {
        $fields = [];

        foreach ($groups as $group) {
            foreach ($group['conditions'] as $condition) {
                $field = (string) ($condition['field'] ?? '');
                if ($field !== '') {
                    $fields[] = $field;
                }
            }
        }

        return array_values(array_unique($fields));
    }

    /** @param  array<int, string>  $fieldsNeeded */
    private function applyAggregations(Builder $query, array $fieldsNeeded, array $order): void
    {
        $needsPedidos = ! empty(array_intersect($fieldsNeeded, [
            'qtd_pedidos', 'ultimo_pedido', 'primeira_compra', 'valor_total_comprado',
            'status_pedido', 'canal_pedido', 'forma_pagamento', 'produto_comprado', 'produto_nome', 'produto',
        ])) || in_array($order['field'] ?? '', ['qtd_pedidos', 'ultimo_pedido', 'valor_total_comprado', 'mais_pedidos'], true);

        if ($needsPedidos && $this->hasPedidoInfrastructure()) {
            $query->withCount(['pedidosConfirmados as _seg_qtd_pedidos']);
            $query->addSelect([
                '_seg_ultimo_pedido' => $this->pedidoStatsSubquery('MAX(COALESCE(p.ped_data, p.cadastrado))'),
                '_seg_primeira_compra' => $this->pedidoStatsSubquery('MIN(COALESCE(p.ped_data, p.cadastrado))'),
                '_seg_valor_total_comprado' => $this->pedidoStatsSubquery('SUM(COALESCE(p.ped_valor_total, 0))'),
            ]);
        }

        if ((in_array('cashback', $fieldsNeeded, true) || in_array('cashback_expira_em', $fieldsNeeded, true)) && Schema::hasTable('cashback')) {
            $query->addSelect([
                '_seg_cashback' => DB::table('cashback')
                    ->selectRaw('COALESCE(SUM(cas_valor), 0)')
                    ->whereColumn('cashback.cliente_id', 'cliente.cliente_id')
                    ->whereNull('excluido'),
            ]);
        }

        if (in_array('produto_comprado', $fieldsNeeded, true) || in_array('produto_nome', $fieldsNeeded, true) || in_array('produto', $fieldsNeeded, true)) {
            $query->addSelect([
                '_seg_produto_comprado' => $this->produtosCompradosSubquery(),
            ]);
        }
    }

    private function hasPedidoInfrastructure(): bool
    {
        return Schema::hasTable('pedido')
            && Schema::hasTable('status')
            && Schema::hasColumn('status', 'sta_confirmado');
    }

    private function pedidoStatsSubquery(string $aggregate): \Illuminate\Database\Query\Expression
    {
        if (! $this->hasPedidoInfrastructure()) {
            return DB::raw('NULL');
        }

        return DB::raw("(
            SELECT {$aggregate}
            FROM pedido p
            INNER JOIN status s ON s.status_id = p.status_id
            WHERE p.cliente_id = cliente.cliente_id
              AND p.excluido IS NULL
              AND s.sta_confirmado = 'S'
        )");
    }

    private function produtosCompradosSubquery(): \Illuminate\Database\Query\Expression
    {
        if (Schema::hasTable('pedido_produto_cliente') && Schema::hasTable('produtos')) {
            return DB::raw("(
                SELECT GROUP_CONCAT(DISTINCT pr.nome)
                FROM pedido_produto_cliente ppc
                INNER JOIN produtos pr ON pr.produto_id = ppc.produto_id
                WHERE ppc.cliente_id = cliente.cliente_id
                  AND COALESCE(pr.ativo, 'S') = 'S'
            )");
        }

        return DB::raw("(
            SELECT GROUP_CONCAT(DISTINCT pr.pro_nome)
            FROM pedido p
            INNER JOIN status s ON s.status_id = p.status_id
            INNER JOIN pedido_item pi ON pi.pedido_id = p.pedido_id
            INNER JOIN produto pr ON pr.produto_id = pi.produto_id
            WHERE p.cliente_id = cliente.cliente_id
              AND p.excluido IS NULL
              AND s.sta_confirmado = 'S'
        )");
    }

    /** @param  array{logic: string, conditions: array<int, array<string, mixed>>}  $group */
    private function applyGroup(Builder $query, array $group): void
    {
        $logic = strtoupper($group['logic'] ?? 'AND') === 'OR' ? 'orWhere' : 'where';

        foreach ($group['conditions'] as $index => $condition) {
            $method = $index === 0 ? 'where' : (strtoupper($group['logic'] ?? 'AND') === 'OR' ? 'orWhere' : 'where');
            $query->{$method}(function (Builder $q) use ($condition) {
                $this->applyCondition($q, $condition);
            });
        }
    }

    /** @param  array<string, mixed>  $condition */
    private function applyCondition(Builder $query, array $condition): void
    {
        $field = (string) ($condition['field'] ?? '');
        $operator = (string) ($condition['operator'] ?? '');
        $value = $condition['value'] ?? null;

        $campo = $this->campos->get($field);
        if (!$campo) {
            throw new RuntimeException("Campo Eloquent não mapeado: {$field}");
        }

        $value = $this->normalizarValor($field, $operator, $value, $campo);

        match ($field) {
            'sexo' => $this->applySexo($query, $operator, $value),
            'newsletter', 'funcionario' => $this->applyBooleanSelect($query, $field, $operator, $value),
            'nome' => $this->applyText($query, 'cli_nome', $operator, (string) $value),
            'cpf' => $this->applyText($query, 'cli_cpf', $operator, (string) $value),
            'telefone' => $this->applyText($query, 'cli_telefone', $operator, (string) $value),
            'email' => $this->applyText($query, 'cli_email', $operator, (string) $value),
            'bairro' => $this->applyText($query, 'cli_bairro', $operator, (string) $value),
            'municipio' => $this->applyText($query, 'cli_cidade', $operator, (string) $value),
            'estado' => $this->applyText($query, 'cli_estado', $operator, (string) $value),
            'nascimento' => $this->applyDate($query, 'cli_data_nascimento', $operator, $value, 'nascimento'),
            'data_cadastro' => $this->applyDate($query, 'cadastrado', $operator, $value, 'data_cadastro'),
            'idade' => $this->applyIdade($query, $operator, $value),
            'pontos_totais' => $this->applyNumeric($query, 'cli_pontos_totais', $operator, $value),
            'qtd_pedidos' => $this->applyPedidoCount($query, $operator, $value),
            'ultimo_pedido' => $this->applyUltimoPedido($query, $operator, $value),
            'primeira_compra' => $this->applyPrimeiraCompra($query, $operator, $value),
            'valor_total_comprado' => $this->applyValorTotal($query, $operator, $value),
            'cashback' => $this->applyCashback($query, $operator, $value),
            'cashback_expira_em' => $this->applyCashbackExpira($query, $operator, $value),
            'produto_comprado', 'produto_nome', 'produto' => $this->applyProdutoComprado($query, $operator, (string) $value),
            'carrinho_abandonado' => $this->applyCarrinho($query, $operator),
            default => throw new RuntimeException("Campo Eloquent não implementado: {$field}"),
        };
    }

    private function applySexo(Builder $query, string $op, mixed $value): void
    {
        $valores = $this->valoresSexo($value);

        if ($op === 'equals') {
            $query->where(function (Builder $q) use ($valores) {
                foreach ($valores as $i => $v) {
                    $i === 0 ? $q->whereRaw('LOWER(TRIM(sexo_id)) = ?', [mb_strtolower($v)]) : $q->orWhereRaw('LOWER(TRIM(sexo_id)) = ?', [mb_strtolower($v)]);
                }
            });
        } elseif ($op === 'not_equals') {
            $query->where(function (Builder $q) use ($valores) {
                $q->whereNull('sexo_id')->orWhereNotIn(DB::raw('LOWER(TRIM(sexo_id))'), array_map('mb_strtolower', $valores));
            });
        } elseif ($op === 'is_empty') {
            $query->where(fn (Builder $q) => $q->whereNull('sexo_id')->orWhere('sexo_id', ''));
        } elseif ($op === 'is_not_empty') {
            $query->whereNotNull('sexo_id')->where('sexo_id', '!=', '');
        } else {
            throw new RuntimeException("Operador sexo não suportado: {$op}");
        }
    }

    private function applyBooleanSelect(Builder $query, string $field, string $op, mixed $value): void
    {
        $col = $field === 'newsletter' ? 'cli_newsletter' : 'cli_funcionario';
        $sim = ['s', 'sim', '1', 'true'];
        $nao = ['n', 'nao', 'não', '0', 'false'];

        if (in_array($op, ['is_true', 'equals'], true)) {
            $query->whereIn(DB::raw('LOWER(TRIM(' . $col . '))'), $sim);
        } elseif (in_array($op, ['is_false', 'not_equals'], true)) {
            $query->where(function (Builder $q) use ($col, $nao) {
                $q->whereNull($col)->orWhereIn(DB::raw('LOWER(TRIM(' . $col . '))'), $nao);
            });
        } else {
            $this->applyText($query, $col, $op, (string) $value);
        }
    }

    private function applyText(Builder $query, string $column, string $op, string $value): void
    {
        $normalized = mb_strtolower(trim($value));

        match ($op) {
            'equals' => $query->whereRaw("LOWER(TRIM({$column})) = ?", [$normalized]),
            'not_equals' => $query->where(function (Builder $q) use ($column, $normalized) {
                $q->whereNull($column)->orWhereRaw("LOWER(TRIM({$column})) <> ?", [$normalized]);
            }),
            'contains' => $query->whereRaw("LOWER(TRIM({$column})) LIKE ?", ['%' . $normalized . '%']),
            'not_contains' => $query->where(function (Builder $q) use ($column, $normalized) {
                $q->whereNull($column)->orWhereRaw("LOWER(TRIM({$column})) NOT LIKE ?", ['%' . $normalized . '%']);
            }),
            'starts_with' => $query->whereRaw("LOWER(TRIM({$column})) LIKE ?", [$normalized . '%']),
            'ends_with' => $query->whereRaw("LOWER(TRIM({$column})) LIKE ?", ['%' . $normalized]),
            'is_empty' => $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, '')),
            'is_not_empty' => $query->whereNotNull($column)->where($column, '!=', ''),
            'in' => $query->whereIn(DB::raw("LOWER(TRIM({$column}))"), array_map('mb_strtolower', (array) $value)),
            'not_in' => $query->whereNotIn(DB::raw("LOWER(TRIM({$column}))"), array_map('mb_strtolower', (array) $value)),
            default => throw new RuntimeException("Operador texto não suportado: {$op}"),
        };
    }

    private function applyNumeric(Builder $query, string $column, string $op, mixed $value): void
    {
        match ($op) {
            'equals' => $query->where($column, '=', $value),
            'not_equals' => $query->where($column, '!=', $value),
            'greater_than' => $query->where($column, '>', $value),
            'greater_or_equal' => $query->where($column, '>=', $value),
            'less_than' => $query->where($column, '<', $value),
            'less_or_equal' => $query->where($column, '<=', $value),
            'between' => $this->applyBetween($query, $column, $value),
            'is_empty' => $query->where(fn (Builder $q) => $q->whereNull($column)->orWhere($column, 0)),
            'is_not_empty' => $query->where($column, '>', 0),
            default => throw new RuntimeException("Operador numérico não suportado: {$op}"),
        };
    }

    private function applyBetween(Builder $query, string $column, mixed $value): void
    {
        if (is_array($value)) {
            $min = $value[0] ?? $value['min'] ?? null;
            $max = $value[1] ?? $value['max'] ?? null;
        } else {
            $parts = preg_split('/\s*(?:,|e|a|-)\s*/iu', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
            $min = $parts[0] ?? null;
            $max = $parts[1] ?? null;
        }

        $query->whereBetween($column, [(float) $min, (float) $max]);
    }

    private function applyDate(Builder $query, string $column, string $op, mixed $value, string $field): void
    {
        $today = Carbon::today();

        match ($op) {
            'today' => $field === 'nascimento'
                ? $query->whereMonth($column, $today->month)->whereDay($column, $today->day)
                : $query->whereDate($column, $today),
            'yesterday' => $query->whereDate($column, $today->copy()->subDay()),
            'equals_date' => $query->whereDate($column, Carbon::parse((string) $value)),
            'before_date' => $query->whereDate($column, '<', Carbon::parse((string) $value)),
            'after_date' => $query->whereDate($column, '>', Carbon::parse((string) $value)),
            'between_dates' => $this->applyBetweenDates($query, $column, $value),
            'last_x_days' => $query->whereDate($column, '>=', $today->copy()->subDays((int) $value)),
            'last_x_months' => $query->whereDate($column, '>=', $today->copy()->subMonths((int) $value)),
            'last_x_years' => $query->whereDate($column, '>=', $today->copy()->subYears((int) $value)),
            'more_than_x_days_ago' => $query->whereDate($column, '<', $today->copy()->subDays((int) $value)),
            'less_than_x_days_ago' => $query->whereDate($column, '>=', $today->copy()->subDays((int) $value)),
            'next_x_days' => $query->whereBetween(DB::raw("DATE({$column})"), [$today, $today->copy()->addDays((int) $value)]),
            'exactly_x_days_ago' => $query->whereDate($column, $today->copy()->subDays((int) $value)),
            'month_equals' => $value === 'current' || $value === 'atual'
                ? $query->whereMonth($column, $today->month)
                : $query->whereMonth($column, (int) $value),
            'month_between' => $this->applyMonthBetween($query, $column, $value),
            'is_empty' => $query->whereNull($column),
            'is_not_empty' => $query->whereNotNull($column),
            default => throw new RuntimeException("Operador data não suportado: {$op}"),
        };
    }

    private function applyBetweenDates(Builder $query, string $column, mixed $value): void
    {
        if (is_array($value)) {
            $start = $value[0] ?? null;
            $end = $value[1] ?? null;
        } else {
            $parts = preg_split('/\s*(?:,|e|a|-)\s*/iu', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
            $start = $parts[0] ?? null;
            $end = $parts[1] ?? null;
        }

        $query->whereBetween(DB::raw("DATE({$column})"), [Carbon::parse((string) $start), Carbon::parse((string) $end)]);
    }

    private function applyMonthBetween(Builder $query, string $column, mixed $value): void
    {
        if (is_array($value)) {
            $start = (int) ($value[0] ?? 1);
            $end = (int) ($value[1] ?? 12);
        } else {
            $parts = preg_split('/\s*(?:,|e|a|-)\s*/iu', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
            $start = (int) ($parts[0] ?? 1);
            $end = (int) ($parts[1] ?? 12);
        }

        $start = max(1, min($start, 12));
        $end = max(1, min($end, 12));

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        if (DB::getDriverName() === 'sqlite') {
            $query->whereBetween(DB::raw("CAST(strftime('%m', {$column}) AS INTEGER)"), [$start, $end]);
        } else {
            $query->whereBetween(DB::raw("MONTH({$column})"), [$start, $end]);
        }
    }

    private function applyIdade(Builder $query, string $op, mixed $value): void
    {
        $idadeExpr = DB::getDriverName() === 'sqlite'
            ? "(CAST(strftime('%Y','now','localtime') AS INTEGER) - CAST(strftime('%Y', cli_data_nascimento) AS INTEGER) - (strftime('%m-%d','now','localtime') < strftime('%m-%d', cli_data_nascimento)))"
            : 'TIMESTAMPDIFF(YEAR, cli_data_nascimento, CURDATE())';

        match ($op) {
            'equals' => $query->whereRaw("{$idadeExpr} = ?", [$value]),
            'not_equals' => $query->whereRaw("{$idadeExpr} <> ?", [$value]),
            'greater_than' => $query->whereRaw("{$idadeExpr} > ?", [$value]),
            'greater_or_equal' => $query->whereRaw("{$idadeExpr} >= ?", [$value]),
            'less_than' => $query->whereRaw("{$idadeExpr} < ?", [$value]),
            'less_or_equal' => $query->whereRaw("{$idadeExpr} <= ?", [$value]),
            'between' => $this->applyBetweenRaw($query, $idadeExpr, $value),
            default => throw new RuntimeException("Operador idade não suportado: {$op}"),
        };
    }

    private function applyBetweenRaw(Builder $query, string $expr, mixed $value): void
    {
        if (is_array($value)) {
            $min = $value[0] ?? null;
            $max = $value[1] ?? null;
        } else {
            $parts = preg_split('/\s*(?:,|e|a|-)\s*/iu', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
            $min = $parts[0] ?? null;
            $max = $parts[1] ?? null;
        }

        $query->whereRaw("{$expr} BETWEEN ? AND ?", [(float) $min, (float) $max]);
    }

    private function applyPedidoCount(Builder $query, string $op, mixed $value): void
    {
        if (! $this->hasPedidoInfrastructure()) {
            throw new RuntimeException('Tabela de pedidos/status não disponível para filtro de quantidade.');
        }

        $sub = '(SELECT COUNT(*) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')';

        match ($op) {
            'equals' => $query->whereRaw("{$sub} = ?", [$value]),
            'not_equals' => $query->whereRaw("{$sub} <> ?", [$value]),
            'greater_than' => $query->whereRaw("{$sub} > ?", [$value]),
            'greater_or_equal' => $query->whereRaw("{$sub} >= ?", [$value]),
            'less_than' => $query->whereRaw("{$sub} < ?", [$value]),
            'less_or_equal' => $query->whereRaw("{$sub} <= ?", [$value]),
            'between' => $this->applyBetweenRaw($query, $sub, $value),
            default => throw new RuntimeException("Operador qtd_pedidos não suportado: {$op}"),
        };
    }

    private function applyUltimoPedido(Builder $query, string $op, mixed $value): void
    {
        $sub = '(SELECT MAX(COALESCE(p.ped_data, p.cadastrado)) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')';
        $this->applyDateSubquery($query, $sub, $op, $value);
    }

    private function applyPrimeiraCompra(Builder $query, string $op, mixed $value): void
    {
        $sub = '(SELECT MIN(COALESCE(p.ped_data, p.cadastrado)) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')';
        $this->applyDateSubquery($query, $sub, $op, $value);
    }

    private function applyDateSubquery(Builder $query, string $sub, string $op, mixed $value): void
    {
        $today = Carbon::today();

        match ($op) {
            'today' => $query->whereRaw("DATE({$sub}) = ?", [$today->toDateString()]),
            'yesterday' => $query->whereRaw("DATE({$sub}) = ?", [$today->copy()->subDay()->toDateString()]),
            'equals_date' => $query->whereRaw("DATE({$sub}) = ?", [Carbon::parse((string) $value)->toDateString()]),
            'before_date' => $query->whereRaw("DATE({$sub}) < ?", [Carbon::parse((string) $value)->toDateString()]),
            'after_date' => $query->whereRaw("DATE({$sub}) > ?", [Carbon::parse((string) $value)->toDateString()]),
            'last_x_days' => $query->whereRaw("DATE({$sub}) >= ?", [$today->copy()->subDays((int) $value)->toDateString()]),
            'more_than_x_days_ago' => $query->whereRaw("DATE({$sub}) < ?", [$today->copy()->subDays((int) $value)->toDateString()]),
            'less_than_x_days_ago' => $query->whereRaw("DATE({$sub}) >= ?", [$today->copy()->subDays((int) $value)->toDateString()]),
            'exactly_x_days_ago' => $query->whereRaw("DATE({$sub}) = ?", [$today->copy()->subDays((int) $value)->toDateString()]),
            'is_empty' => $query->whereRaw("{$sub} IS NULL"),
            'is_not_empty' => $query->whereRaw("{$sub} IS NOT NULL"),
            default => throw new RuntimeException("Operador data subquery não suportado: {$op}"),
        };
    }

    private function applyValorTotal(Builder $query, string $op, mixed $value): void
    {
        $sub = '(SELECT COALESCE(SUM(p.ped_valor_total), 0) FROM pedido p INNER JOIN status s ON s.status_id = p.status_id WHERE p.cliente_id = cliente.cliente_id AND p.excluido IS NULL AND s.sta_confirmado = \'S\')';
        $this->applyNumericSubquery($query, $sub, $op, $value);
    }

    private function applyCashback(Builder $query, string $op, mixed $value): void
    {
        $sub = '(SELECT COALESCE(SUM(cas_valor), 0) FROM cashback WHERE cliente_id = cliente.cliente_id AND excluido IS NULL)';
        $this->applyNumericSubquery($query, $sub, $op, $value);
    }

    private function applyCashbackExpira(Builder $query, string $op, mixed $value): void
    {
        $expiraExpr = DB::getDriverName() === 'sqlite'
            ? "(SELECT MAX(datetime(COALESCE(cadastrado, 'now'), '+30 days')) FROM cashback WHERE cliente_id = cliente.cliente_id AND excluido IS NULL)"
            : "(SELECT MAX(DATE_ADD(COALESCE(cadastrado, NOW()), INTERVAL 30 DAY)) FROM cashback WHERE cliente_id = cliente.cliente_id AND excluido IS NULL)";

        $today = Carbon::today();

        match ($op) {
            'next_x_days' => $query->whereRaw("DATE({$expiraExpr}) BETWEEN ? AND ?", [$today->toDateString(), $today->copy()->addDays((int) $value)->toDateString()]),
            'before_date' => $query->whereRaw("DATE({$expiraExpr}) < ?", [Carbon::parse((string) $value)->toDateString()]),
            'after_date' => $query->whereRaw("DATE({$expiraExpr}) > ?", [Carbon::parse((string) $value)->toDateString()]),
            'equals_date' => $query->whereRaw("DATE({$expiraExpr}) = ?", [Carbon::parse((string) $value)->toDateString()]),
            default => throw new RuntimeException("Operador cashback_expira_em não suportado: {$op}"),
        };
    }

    private function applyNumericSubquery(Builder $query, string $sub, string $op, mixed $value): void
    {
        match ($op) {
            'equals' => $query->whereRaw("{$sub} = ?", [$value]),
            'not_equals' => $query->whereRaw("{$sub} <> ?", [$value]),
            'greater_than' => $query->whereRaw("{$sub} > ?", [$value]),
            'greater_or_equal' => $query->whereRaw("{$sub} >= ?", [$value]),
            'less_than' => $query->whereRaw("{$sub} < ?", [$value]),
            'less_or_equal' => $query->whereRaw("{$sub} <= ?", [$value]),
            'between' => $this->applyBetweenRaw($query, $sub, $value),
            default => throw new RuntimeException("Operador numérico subquery não suportado: {$op}"),
        };
    }

    private function applyProdutoComprado(Builder $query, string $op, string $value): void
    {
        $normalized = mb_strtolower(trim($value));

        $callback = function (Builder $q) use ($normalized, $op) {
            if (Schema::hasTable('pedido_produto_cliente') && Schema::hasTable('produtos')) {
                $q->whereExists(function ($sub) use ($normalized, $op) {
                    $sub->from('pedido_produto_cliente as ppc')
                        ->join('produtos as pr', 'pr.produto_id', '=', 'ppc.produto_id')
                        ->whereColumn('ppc.cliente_id', 'cliente.cliente_id')
                        ->whereRaw($this->produtoLikeClause('pr.nome', $op), $this->produtoLikeBindings($normalized, $op));
                });
            } else {
                $q->whereHas('pedidosConfirmados', function (Builder $pq) use ($normalized, $op) {
                    $pq->whereHas('itens.produto', function (Builder $prq) use ($normalized, $op) {
                        $col = Schema::hasColumn('produto', 'pro_nome') ? 'pro_nome' : 'nome';
                        $prq->whereRaw($this->produtoLikeClause($col, $op), $this->produtoLikeBindings($normalized, $op));
                    });
                });
            }
        };

        if (in_array($op, ['not_equals', 'not_contains'], true)) {
            if (Schema::hasTable('pedido_produto_cliente') && Schema::hasTable('produtos')) {
                $query->whereNotExists(function ($sub) use ($normalized) {
                    $sub->from('pedido_produto_cliente as ppc')
                        ->join('produtos as pr', 'pr.produto_id', '=', 'ppc.produto_id')
                        ->whereColumn('ppc.cliente_id', 'cliente.cliente_id')
                        ->whereRaw($this->produtoLikeClause('pr.nome', 'contains'), $this->produtoLikeBindings($normalized, 'contains'));
                });
            } else {
                $query->whereDoesntHave('pedidosConfirmados', function (Builder $pq) use ($normalized) {
                    $pq->whereHas('itens.produto', function (Builder $prq) use ($normalized) {
                        $col = Schema::hasColumn('produto', 'pro_nome') ? 'pro_nome' : 'nome';
                        $prq->whereRaw($this->produtoLikeClause($col, 'contains'), $this->produtoLikeBindings($normalized, 'contains'));
                    });
                });
            }
        } else {
            $query->where($callback);
        }
    }

    private function produtoLikeClause(string $column, string $op): string
    {
        return match ($op) {
            'equals', 'contains' => "LOWER(TRIM({$column})) LIKE ?",
            'starts_with' => "LOWER(TRIM({$column})) LIKE ?",
            'ends_with' => "LOWER(TRIM({$column})) LIKE ?",
            'not_equals', 'not_contains' => "LOWER(TRIM({$column})) NOT LIKE ?",
            default => "LOWER(TRIM({$column})) LIKE ?",
        };
    }

    /** @return array<int, string> */
    private function produtoLikeBindings(string $normalized, string $op): array
    {
        return match ($op) {
            'starts_with' => [$normalized . '%'],
            'ends_with' => ['%' . $normalized],
            default => ['%' . $normalized . '%'],
        };
    }

    private function applyCarrinho(Builder $query, string $op): void
    {
        if (! Schema::hasTable('view_carrinho_abandonado')) {
            if (in_array($op, ['is_true', 'exists'], true)) {
                $query->whereRaw('1 = 0');

                return;
            }

            return;
        }

        $exists = fn (Builder $q) => $q->whereExists(function ($sub) {
            $sub->from('view_carrinho_abandonado as vca')
                ->whereColumn('vca.cliente_id', 'cliente.cliente_id');
        });

        match ($op) {
            'is_true', 'exists' => $query->where($exists),
            'is_false', 'not_exists' => $query->whereNotExists(function ($sub) {
                $sub->from('view_carrinho_abandonado as vca')
                    ->whereColumn('vca.cliente_id', 'cliente.cliente_id');
            }),
            default => throw new RuntimeException("Operador carrinho não suportado: {$op}"),
        };
    }

    /** @param  array<int, string>  $fieldsNeeded */
    private function applyOrder(Builder $query, array $order, array $fieldsNeeded): void
    {
        $field = $order['field'] ?? 'random';
        $direction = strtolower($order['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

        match ($field) {
            'mais_recentes', 'data_cadastro' => $query->orderBy('cadastrado', 'desc'),
            'mais_antigos' => $query->orderBy('cadastrado', 'asc'),
            'qtd_pedidos', 'qtd_pedidos_desc', 'mais_pedidos' => $query->orderBy('_seg_qtd_pedidos', 'desc'),
            'valor_total_comprado', 'maior_valor' => $query->orderBy('_seg_valor_total_comprado', 'desc'),
            'ultimo_pedido_desc', 'ultima_compra_desc' => $query->orderBy('_seg_ultimo_pedido', 'desc'),
            'ultimo_pedido_asc', 'ultima_compra_asc' => $query->orderBy('_seg_ultimo_pedido', 'asc'),
            default => $query->inRandomOrder(),
        };
    }

    private function normalizarValor(string $field, string $op, mixed $value, SegmentoClienteCampo $campo): mixed
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (in_array($field, ['newsletter', 'funcionario'], true) && in_array($op, ['equals', 'not_equals'], true) && is_string($value)) {
            $v = mb_strtolower($value);

            return in_array($v, ['sim', 's', 'true', '1'], true) ? 'sim' : 'nao';
        }

        $dayOps = ['last_x_days', 'last_x_months', 'last_x_years', 'more_than_x_days_ago', 'less_than_x_days_ago'];
        if (in_array($op, $dayOps, true) || in_array($campo->tipo_valor, ['number', 'money'], true)) {
            if (is_string($value) && is_numeric($value)) {
                return str_contains($value, '.') ? (float) $value : (int) $value;
            }
        }

        return $value;
    }

    /** @return array<int, string> */
    private function valoresSexo(mixed $value): array
    {
        $v = mb_strtolower(trim((string) $value));

        return match ($v) {
            'homem', 'homens', 'masculino', 'm', 'male' => ['m', 'masculino', 'homem', '1'],
            'mulher', 'mulheres', 'feminino', 'f', 'female' => ['f', 'feminino', 'mulher', '2'],
            default => [$v],
        };
    }
}
