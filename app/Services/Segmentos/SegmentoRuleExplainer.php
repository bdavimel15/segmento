<?php

namespace App\Services\Segmentos;

use App\Models\SegmentoClienteCampo;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class SegmentoRuleExplainer
{
    private Collection $campos;

    private const OP_LABELS = [
        'equals' => 'é igual a',
        'not_equals' => 'é diferente de',
        'greater_than' => 'é maior que',
        'greater_or_equal' => 'é pelo menos',
        'less_than' => 'é menor que',
        'less_or_equal' => 'é no máximo',
        'between' => 'está entre',
        'contains' => 'contém',
        'not_contains' => 'não contém',
        'starts_with' => 'começa com',
        'ends_with' => 'termina com',
        'is_empty' => 'está vazio',
        'is_not_empty' => 'não está vazio',
        'is_true' => 'sim',
        'is_false' => 'não',
        'exists' => 'existe',
        'not_exists' => 'não existe',
        'today' => 'é hoje',
        'yesterday' => 'foi ontem',
        'equals_date' => 'é na data',
        'before_date' => 'antes de',
        'after_date' => 'depois de',
        'between_dates' => 'entre datas',
        'last_x_days' => 'nos últimos X dias',
        'next_x_days' => 'nos próximos X dias',
        'exactly_x_days_ago' => 'exatamente X dias atrás',
        'more_than_x_days_ago' => 'há mais de X dias',
        'less_than_x_days_ago' => 'há menos de X dias',
        'month_equals' => 'no mês',
        'month_between' => 'entre os meses',
    ];

    public function __construct(?Collection $campos = null)
    {
        $this->campos = $campos ?? SegmentoClienteCampo::where('ativo', 'S')->get()->keyBy('chave');
    }

    public function resumoRegra(array $regra): array
    {
        $regra = SegmentoRuleHelper::enrichRule($regra);
        $groups = SegmentoRuleHelper::getGroups($regra);
        $topLogic = SegmentoRuleHelper::getTopLogic($regra);
        $origem = $regra['origem'] ?? 'manual';
        $linhas = [];

        foreach ($groups as $index => $group) {
            $groupLines = [];
            foreach ($group['conditions'] as $condition) {
                $groupLines[] = $this->formatConditionLine($condition);
            }

            $innerLogic = strtoupper($group['logic'] ?? 'AND') === 'OR' ? 'OU' : 'E';
            $bloco = count($groupLines) === 1
                ? $groupLines[0]
                : implode("\n{$innerLogic}\n", $groupLines);

            if (count($groups) > 1) {
                $linhas[] = [
                    'titulo' => 'Grupo ' . ($index + 1),
                    'logic' => $group['logic'] ?? 'AND',
                    'texto' => $bloco,
                    'condicoes' => array_map(fn ($c) => $this->formatConditionLine($c), $group['conditions']),
                ];
            } else {
                $linhas[] = [
                    'titulo' => null,
                    'logic' => $group['logic'] ?? 'AND',
                    'texto' => $bloco,
                    'condicoes' => array_map(fn ($c) => $this->formatConditionLine($c), $group['conditions']),
                ];
            }
        }

        return [
            'origem' => $origem,
            'top_logic' => $topLogic,
            'top_logic_label' => $topLogic === 'OR' ? 'OU' : 'E',
            'grupos' => $linhas,
            'texto_completo' => $this->montarTextoCompleto($linhas, $topLogic),
            'interpretacao_ia' => ($origem === 'ia') ? ($regra['resumo_humano'] ?? null) : null,
            'resumo_humano' => $regra['resumo_humano'] ?? null,
        ];
    }

    public function explainClient(array $row, array $regra): array
    {
        $regra = SegmentoRuleHelper::enrichRule($regra);
        $groups = SegmentoRuleHelper::getGroups($regra);
        $topLogic = SegmentoRuleHelper::getTopLogic($regra);
        $groupResults = [];
        $todasCondicoes = [];

        foreach ($groups as $index => $group) {
            $conditionResults = [];
            $passedFlags = [];

            foreach ($group['conditions'] as $condIndex => $condition) {
                $result = $this->evaluateCondition($row, $condition);
                $result['regra_num'] = $condIndex + 1;
                $result['grupo_num'] = $index + 1;
                $conditionResults[] = $result;
                $passedFlags[] = $result['passed'];
                $todasCondicoes[] = $result;
            }

            $innerLogic = strtoupper($group['logic'] ?? 'AND') === 'OR' ? 'OR' : 'AND';
            $groupPassed = $innerLogic === 'OR'
                ? in_array(true, $passedFlags, true)
                : !in_array(false, $passedFlags, true);

            $groupResults[] = [
                'grupo_num' => $index + 1,
                'logic' => $innerLogic,
                'passed' => $groupPassed,
                'condicoes' => $conditionResults,
            ];
        }

        $groupPassedFlags = array_column($groupResults, 'passed');
        $approved = $topLogic === 'OR'
            ? in_array(true, $groupPassedFlags, true)
            : !in_array(false, $groupPassedFlags, true);

        $motivos = array_values(array_filter(array_map(
            fn ($r) => $r['passed'] ? $r['resumo_curto'] : null,
            $todasCondicoes
        )));

        return [
            'nome' => $this->nomeCliente($row),
            'cliente_id' => $row['cliente_id'] ?? null,
            'approved' => $approved,
            'status_label' => $approved ? 'Cliente aprovado' : 'Cliente reprovado',
            'status_icon' => $approved ? '✔' : '✖',
            'top_logic' => $topLogic,
            'grupos' => $groupResults,
            'motivos_resumo' => array_slice($motivos, 0, 4),
            'motivos_texto' => implode("\n", array_map(fn ($m) => '✅ ' . $m, $motivos)),
        ];
    }

    public function explainPreview(array $rows, array $regra): array
    {
        $explicacoes = [];

        foreach ($rows as $row) {
            $explicacoes[] = $this->explainClient($row, $regra);
        }

        return $explicacoes;
    }

    private function evaluateCondition(array $row, array $condition): array
    {
        $field = (string) ($condition['field'] ?? '');
        $operator = (string) ($condition['operator'] ?? 'equals');
        $value = $condition['value'] ?? null;
        $campo = $this->campos->get($field);
        $label = $campo?->label ?? $field;
        $found = $this->resolveFieldValue($row, $field);
        $foundDisplay = $this->formatFoundValue($field, $found);
        $expectedDisplay = $this->formatExpectedValue($field, $operator, $value);
        $passed = $this->matches($field, $operator, $value, $found, $row);

        return [
            'field' => $field,
            'field_label' => $label,
            'operator' => $operator,
            'operator_label' => self::OP_LABELS[$operator] ?? $operator,
            'value' => $value,
            'expected' => $expectedDisplay,
            'found' => $foundDisplay,
            'found_raw' => $found,
            'passed' => $passed,
            'result_icon' => $passed ? '✅' : '❌',
            'descricao' => $campo?->descricao,
            'resumo_curto' => $label . ' ' . (self::OP_LABELS[$operator] ?? $operator) . ($expectedDisplay !== '—' ? ' ' . $expectedDisplay : ''),
        ];
    }

    private function matches(string $field, string $operator, mixed $value, mixed $found, array $row): bool
    {
        if ($field === 'busca_geral') {
            return $this->matchBuscaGeral($operator, (string) $value, $row);
        }

        if (in_array($operator, ['is_empty'], true)) {
            return $found === null || $found === '';
        }

        if (in_array($operator, ['is_not_empty'], true)) {
            return !($found === null || $found === '');
        }

        if (in_array($operator, ['is_true', 'exists'], true)) {
            return $this->truthy($found);
        }

        if (in_array($operator, ['is_false', 'not_exists'], true)) {
            return !$this->truthy($found);
        }

        if (in_array($operator, ['today', 'yesterday', 'equals_date', 'before_date', 'after_date', 'last_x_days', 'next_x_days', 'exactly_x_days_ago', 'more_than_x_days_ago', 'less_than_x_days_ago', 'month_equals', 'month_between'], true)) {
            return $this->matchDate($field, $operator, $value, $found);
        }

        if (in_array($field, ['sexo', 'newsletter', 'funcionario', 'estado'], true) && in_array($operator, ['equals', 'not_equals'], true)) {
            $match = $this->matchNormalized($field, (string) $value, $found);
            return $operator === 'equals' ? $match : !$match;
        }

        if (in_array($field, ['nome', 'cpf', 'telefone', 'email', 'bairro', 'municipio', 'estado', 'origem_contato', 'produto_comprado', 'produto_nome', 'produto'], true)) {
            return $this->matchText($operator, (string) $value, $found);
        }

        if (in_array($operator, ['greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'equals', 'not_equals', 'between'], true)) {
            return $this->matchNumeric($operator, $value, $found);
        }

        return $this->matchText($operator, (string) $value, $found);
    }

    private function matchBuscaGeral(string $operator, string $value, array $row): bool
    {
        $cols = ['cli_nome', 'cli_cpf', 'cli_telefone', 'cli_email', 'sexo_id', 'cli_newsletter', 'cli_cidade', 'cli_bairro'];
        $flags = [];

        foreach ($cols as $col) {
            if (!array_key_exists($col, $row)) {
                continue;
            }
            $flags[] = $this->matchText($operator, $value, $row[$col]);
        }

        if ($flags === []) {
            return false;
        }

        return ($operator === 'not_contains' || $operator === 'not_equals')
            ? !in_array(false, $flags, true)
            : in_array(true, $flags, true);
    }

    private function matchText(string $operator, string $value, mixed $found): bool
    {
        $a = $this->normalizeText((string) $found);
        $b = $this->normalizeText($value);

        return match ($operator) {
            'equals' => $a === $b,
            'not_equals' => $a !== $b,
            'contains' => str_contains($a, $b),
            'not_contains' => !str_contains($a, $b),
            'starts_with' => str_starts_with($a, $b),
            'ends_with' => str_ends_with($a, $b),
            default => str_contains($a, $b),
        };
    }

    private function matchNumeric(string $operator, mixed $value, mixed $found): bool
    {
        $n = is_numeric($found) ? (float) $found : null;

        if ($n === null && $found !== null && $found !== '') {
            if (is_string($found) && preg_match('/-?\d+(?:[\.,]\d+)?/', $found, $m)) {
                $n = (float) str_replace(',', '.', $m[0]);
            }
        }

        if ($operator === 'between') {
            if (is_array($value)) {
                $min = $value[0] ?? $value['min'] ?? null;
                $max = $value[1] ?? $value['max'] ?? null;
            } else {
                $parts = preg_split('/\s*(?:,|e|a|ate|até|-)\s*/iu', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
                $min = $parts[0] ?? null;
                $max = $parts[1] ?? null;
            }

            if ($n === null || !is_numeric($min) || !is_numeric($max)) {
                return false;
            }

            return $n >= (float) $min && $n <= (float) $max;
        }

        if ($n === null || !is_numeric($value)) {
            return false;
        }

        $v = (float) str_replace(',', '.', (string) $value);

        return match ($operator) {
            'equals' => $n == $v,
            'not_equals' => $n != $v,
            'greater_than' => $n > $v,
            'greater_or_equal' => $n >= $v,
            'less_than' => $n < $v,
            'less_or_equal' => $n <= $v,
            default => false,
        };
    }

    private function matchDate(string $field, string $operator, mixed $value, mixed $found): bool
    {
        if ($found === null || $found === '') {
            return false;
        }

        try {
            $date = Carbon::parse($found);
        } catch (\Throwable) {
            return false;
        }

        $today = Carbon::today();

        return match ($operator) {
            'today' => $field === 'nascimento'
                ? ($date->month === $today->month && $date->day === $today->day)
                : $date->isSameDay($today),
            'yesterday' => $date->isSameDay($today->copy()->subDay()),
            'equals_date' => $date->isSameDay(Carbon::parse((string) $value)),
            'before_date' => $date->lt(Carbon::parse((string) $value)),
            'after_date' => $date->gt(Carbon::parse((string) $value)),
            'last_x_days' => $date->gte($today->copy()->subDays((int) $value)),
            'next_x_days' => $date->between($today, $today->copy()->addDays((int) $value)),
            'exactly_x_days_ago' => $date->isSameDay($today->copy()->subDays((int) $value)),
            'more_than_x_days_ago' => $date->lt($today->copy()->subDays((int) $value)),
            'less_than_x_days_ago' => $date->gte($today->copy()->subDays((int) $value)),
            'month_equals' => $this->matchMonthEquals($date, $value),
            'month_between' => $this->matchMonthBetween($date, $value),
            default => false,
        };
    }


    private function matchMonthEquals(Carbon $date, mixed $value): bool
    {
        if ($value === 'current' || $value === 'atual') {
            return $date->month === Carbon::today()->month;
        }

        return $date->month === (int) $value;
    }

    private function matchMonthBetween(Carbon $date, mixed $value): bool
    {
        if (is_array($value)) {
            $start = (int)($value[0] ?? $value['start'] ?? $value['min'] ?? 1);
            $end = (int)($value[1] ?? $value['end'] ?? $value['max'] ?? 12);
        } else {
            $parts = preg_split('/\s*,\s*|\s+e\s+|\s+a\s+|\s+até\s+/iu', (string) $value, -1, PREG_SPLIT_NO_EMPTY);
            $start = (int)($parts[0] ?? 1);
            $end = (int)($parts[1] ?? 12);
        }

        $start = max(1, min($start, 12));
        $end = max(1, min($end, 12));

        if ($start > $end) {
            [$start, $end] = [$end, $start];
        }

        return $date->month >= $start && $date->month <= $end;
    }

    private function matchNormalized(string $field, string $value, mixed $found): bool
    {
        $a = $this->normalizeText((string) $found);
        $b = $this->normalizeText($value);

        if ($field === 'sexo') {
            $mapA = $this->sexoCanonico($a);
            $mapB = $this->sexoCanonico($b);
            return $mapA !== '' && $mapA === $mapB;
        }

        if (in_array($field, ['newsletter', 'funcionario'], true)) {
            return $this->boolCanonico($a) === $this->boolCanonico($b);
        }

        if ($field === 'estado') {
            return strtoupper(substr($a, 0, 2)) === strtoupper(substr($b, 0, 2));
        }

        return $a === $b;
    }

    private function resolveFieldValue(array $row, string $field): mixed
    {
        $alias = '_seg_' . $field;
        if (array_key_exists($alias, $row)) {
            return $row[$alias];
        }

        return match ($field) {
            'nome' => $row['cli_nome'] ?? null,
            'cpf' => $row['cli_cpf'] ?? null,
            'sexo' => $this->normalizeSexo($row['sexo_id'] ?? null),
            'telefone' => $row['cli_telefone'] ?? null,
            'email' => $row['cli_email'] ?? null,
            'nascimento' => $row['cli_data_nascimento'] ?? null,
            'idade' => $this->calcIdade($row['cli_data_nascimento'] ?? null),
            'bairro' => $row['cli_bairro'] ?? null,
            'municipio' => $row['cli_cidade'] ?? null,
            'estado' => $row['cli_estado'] ?? null,
            'funcionario' => $this->normalizeBool($row['cli_funcionario'] ?? null),
            'newsletter' => $this->normalizeBool($row['cli_newsletter'] ?? null),
            'qtd_pedidos', 'qtd_pedidos_confirmados' => $row['_seg_qtd_pedidos'] ?? $row['_seg_qtd_pedidos_confirmados'] ?? $row['cli_qtd_pedidos'] ?? null,
            'ultimo_pedido', 'ultima_compra' => $row['_seg_ultimo_pedido'] ?? $row['_seg_ultima_compra'] ?? null,
            'primeira_compra' => $row['_seg_primeira_compra'] ?? null,
            'valor_total_comprado', 'valor_total_compras' => $row['_seg_valor_total_comprado'] ?? $row['_seg_valor_total_compras'] ?? null,
            'cashback', 'cashback_saldo' => $row['_seg_cashback'] ?? $row['_seg_cashback_saldo'] ?? null,
            'pontos_totais' => $row['cli_pontos_totais'] ?? null,
            'data_cadastro' => $row['cadastrado'] ?? null,
            'cashback_expira_em' => $row['_seg_cashback_expira_em'] ?? null,
            'carrinho_abandonado' => $row['_seg_carrinho_abandonado'] ?? null,
            'produto_comprado', 'produto_nome', 'produto' => $row['_seg_produto_comprado'] ?? $row['_seg_produto_nome'] ?? $row['_seg_produto'] ?? null,
            default => $row[$field] ?? null,
        };
    }

    private function formatConditionLine(array $condition): string
    {
        $field = (string) ($condition['field'] ?? '');
        $campo = $this->campos->get($field);
        $label = $campo?->label ?? $field;
        $op = self::OP_LABELS[$condition['operator'] ?? ''] ?? ($condition['operator'] ?? '');
        $expected = $this->formatExpectedValue($field, (string) ($condition['operator'] ?? ''), $condition['value'] ?? null);

        if ($expected === '—') {
            return "{$label} {$op}";
        }

        return "{$label} {$op} {$expected}";
    }

    private function formatExpectedValue(string $field, string $operator, mixed $value): string
    {
        if (in_array($operator, ['today', 'yesterday', 'is_true', 'is_false', 'exists', 'not_exists', 'is_empty', 'is_not_empty'], true)) {
            return match ($operator) {
                'today' => 'hoje',
                'yesterday' => 'ontem',
                'is_true', 'exists' => 'sim',
                'is_false', 'not_exists' => 'não',
                'is_empty' => 'vazio',
                'is_not_empty' => 'preenchido',
                default => '—',
            };
        }

        if ($value === null || $value === '') {
            return '—';
        }

        if ($field === 'sexo') {
            return $this->sexoCanonico($this->normalizeText((string) $value)) ?: (string) $value;
        }

        if (in_array($field, ['newsletter', 'funcionario'], true)) {
            return $this->boolCanonico($this->normalizeText((string) $value)) === 'sim' ? 'Sim' : 'Não';
        }

        if ($operator === 'more_than_x_days_ago') {
            return (string) $value . ' dias';
        }

        if ($operator === 'less_than_x_days_ago') {
            return (string) $value . ' dias';
        }

        if ($operator === 'last_x_days' || $operator === 'next_x_days' || $operator === 'exactly_x_days_ago') {
            return (string) $value . ' dias';
        }

        return (string) $value;
    }

    private function formatFoundValue(string $field, mixed $found): string
    {
        if ($found === null || $found === '') {
            return '—';
        }

        if ($field === 'sexo') {
            return $this->normalizeSexo($found) ?? (string) $found;
        }

        if (in_array($field, ['newsletter', 'funcionario'], true)) {
            return $this->normalizeBool($found) ?? (string) $found;
        }

        if (in_array($field, ['nascimento', 'ultimo_pedido', 'ultima_compra', 'primeira_compra', 'data_cadastro', 'cashback_expira_em'], true)) {
            try {
                return Carbon::parse((string) $found)->format('d/m/Y');
            } catch (\Throwable) {
                return (string) $found;
            }
        }

        return is_scalar($found) ? (string) $found : json_encode($found, JSON_UNESCAPED_UNICODE);
    }

    private function montarTextoCompleto(array $linhas, string $topLogic): string
    {
        $partes = array_map(fn ($g) => $g['texto'], $linhas);

        if (count($partes) <= 1) {
            return $partes[0] ?? '';
        }

        $sep = "\n\n" . ($topLogic === 'OR' ? 'OU' : 'E') . "\n\n";

        return implode($sep, array_map(function ($g, $i) {
            return ($g['titulo'] ? $g['titulo'] . ":\n" : '') . $g['texto'];
        }, $linhas, array_keys($linhas)));
    }

    private function nomeCliente(array $row): string
    {
        return (string) ($row['cli_nome'] ?? $row['nome'] ?? 'Cliente');
    }

    private function calcIdade(mixed $nascimento): ?int
    {
        if (!$nascimento) {
            return null;
        }

        try {
            return Carbon::parse($nascimento)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeSexo(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $v = $this->normalizeText((string) $value);
        $canon = $this->sexoCanonico($v);

        return $canon !== '' ? ucfirst($canon) : (string) $value;
    }

    private function sexoCanonico(string $value): string
    {
        return match ($value) {
            'm', 'masculino', 'homem', 'homens', 'male', 'macho', 'machos', '1' => 'masculino',
            'f', 'feminino', 'mulher', 'mulheres', 'female', 'femea', 'femeas', '2' => 'feminino',
            default => $value,
        };
    }

    private function normalizeBool(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $this->boolCanonico($this->normalizeText((string) $value)) === 'sim' ? 'Sim' : 'Não';
    }

    private function boolCanonico(string $value): string
    {
        return in_array($value, ['s', 'sim', '1', 'true', 'aceita', 'ativo', 'ativos', 'assina', 'assinante'], true)
            ? 'sim'
            : 'nao';
    }

    private function truthy(mixed $found): bool
    {
        if ($found === null || $found === '') {
            return false;
        }

        $v = $this->normalizeText((string) $found);

        return !in_array($v, ['n', 'nao', 'não', '0', 'false', 'nao', ''], true);
    }

    private function normalizeText(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'], ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'], $v);

        return preg_replace('/[^a-z0-9@._\- ]+/u', '', $v) ?: $v;
    }
}
