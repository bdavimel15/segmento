<?php

namespace App\Services\Segmentos;

use App\Models\SegmentoClienteCampo;
use RuntimeException;

class SegmentoRuleValidator
{
    private array $operadoresGlobais = [
        'equals', 'not_equals', 'greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'between',
        'contains', 'not_contains', 'starts_with', 'ends_with', 'is_empty', 'is_not_empty',
        'is_true', 'is_false', 'exists', 'not_exists', 'today', 'yesterday', 'equals_date',
        'before_date', 'after_date', 'between_dates', 'last_x_days', 'next_x_days',
        'exactly_x_days_ago', 'more_than_x_days_ago', 'less_than_x_days_ago', 'month_equals', 'month_between',
    ];

    public function validar(array $regra): void
    {
        if (($regra['entity'] ?? null) !== 'cliente') {
            throw new RuntimeException('Entidade inválida. Apenas cliente é permitido neste MVP.');
        }

        if (!in_array(strtoupper($regra['logic'] ?? 'AND'), ['AND', 'OR'], true)) {
            throw new RuntimeException('Lógica inválida. Use AND ou OR.');
        }

        $groups = SegmentoRuleHelper::getGroups($regra);

        if ($groups === []) {
            throw new RuntimeException('A regra precisa ter pelo menos uma condição.');
        }

        $campos = SegmentoClienteCampo::where('ativo', 'S')->get()->keyBy('chave');

        foreach ($groups as $group) {
            if (!in_array(strtoupper($group['logic'] ?? 'AND'), ['AND', 'OR'], true)) {
                throw new RuntimeException('Lógica de grupo inválida. Use AND ou OR.');
            }

            foreach ($group['conditions'] as $condition) {
                $field = $condition['field'] ?? null;
                $operator = $condition['operator'] ?? null;

                if (!$field || !$campos->has($field)) {
                    throw new RuntimeException("Campo não permitido: {$field}");
                }

                if (!$operator || !in_array($operator, $this->operadoresGlobais, true)) {
                    throw new RuntimeException("Operador não permitido: {$operator}");
                }

                $campo = $campos->get($field);
                $permitidos = $campo->operadores_json ?? [];
                if (is_array($permitidos) && $permitidos !== [] && !in_array($operator, $permitidos, true)) {
                    throw new RuntimeException("Operador \"{$operator}\" não é válido para o campo {$campo->label}.");
                }

                $this->validarValor($campo->tipo_valor, $operator, $condition['value'] ?? null);
            }
        }
    }

    private function validarValor(string $tipo, string $operator, mixed $value): void
    {
        if (in_array($operator, ['today', 'yesterday', 'is_true', 'is_false', 'exists', 'not_exists', 'is_empty', 'is_not_empty'], true)) {
            return;
        }

        if ($value === null || $value === '') {
            throw new RuntimeException('Valor obrigatório para o operador ' . $operator . '.');
        }

        if ($operator === 'month_equals') {
            if ($value === 'current' || $value === 'atual') {
                return;
            }
            $month = (int)$value;
            if ($month < 1 || $month > 12) {
                throw new RuntimeException('Mês inválido. Use um número de 1 a 12.');
            }
            return;
        }

        if ($operator === 'month_between') {
            $parts = is_array($value) ? $value : preg_split('/\s*,\s*|\s+e\s+|\s+a\s+|\s+até\s+/iu', (string)$value, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) {
                throw new RuntimeException('Intervalo de meses inválido. Use algo como 1,6 ou 7,12.');
            }
            $m1 = (int)$parts[0];
            $m2 = (int)$parts[1];
            if ($m1 < 1 || $m1 > 12 || $m2 < 1 || $m2 > 12) {
                throw new RuntimeException('Intervalo de meses inválido. Use meses entre 1 e 12.');
            }
            return;
        }

        // Validação flexível: o usuário final pode escrever livremente e a IA pode devolver texto.
        // A conversão/normalização segura fica no SQL Builder usando bindings, sem SQL livre.
        if (in_array($tipo, ['number', 'money'], true) && in_array($operator, ['greater_than', 'greater_or_equal', 'less_than', 'less_or_equal', 'equals', 'not_equals'], true)) {
            if (is_string($value) && preg_match('/-?\d+(?:[\.,]\d+)?/', $value, $m)) {
                return;
            }
            if (!is_numeric($value)) {
                throw new RuntimeException('Valor numérico inválido.');
            }
        }
    }
}
