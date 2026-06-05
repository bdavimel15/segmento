<?php

namespace App\Services\Segmentos;

/**
 * Converte respostas da IA (formatos variados) em estrutura semântica oficial.
 * A IA NÃO deve gerar SQL — qualquer SQL recebido é descartado.
 */
class SegmentoSemanticParser
{
    /** @return array<string, mixed> */
    public function emptyStructure(): array
    {
        return [
            'version' => 2,
            'entity' => 'cliente',
            'logic' => 'AND',
            'groups' => [],
            'conditions' => [],
            'limit' => 25,
            'order' => ['field' => 'random', 'direction' => 'asc'],
        ];
    }

    private const OPERADOR_ALIASES = [
        'igual' => 'equals', 'igual_a' => 'equals', 'equal' => 'equals', 'eq' => 'equals',
        'diferente' => 'not_equals', 'diferente_de' => 'not_equals', 'not_equal' => 'not_equals', 'neq' => 'not_equals',
        'maior_que' => 'greater_than', 'gt' => 'greater_than',
        'maior_ou_igual' => 'greater_or_equal', 'gte' => 'greater_or_equal',
        'menor_que' => 'less_than', 'lt' => 'less_than',
        'menor_ou_igual' => 'less_or_equal', 'lte' => 'less_or_equal',
        'contem' => 'contains', 'contém' => 'contains', 'like' => 'contains',
        'nao_contem' => 'not_contains', 'não_contém' => 'not_contains',
        'comeca_com' => 'starts_with', 'começa_com' => 'starts_with',
        'termina_com' => 'ends_with', 'ends_with' => 'ends_with',
        'esta_vazio' => 'is_empty', 'está_vazio' => 'is_empty',
        'nao_esta_vazio' => 'is_not_empty', 'não_está_vazio' => 'is_not_empty',
        'hoje' => 'today', 'ontem' => 'yesterday',
        'antes_de' => 'before_date', 'depois_de' => 'after_date',
        'entre_datas' => 'between_dates', 'entre' => 'between',
        'ultimos_x_dias' => 'last_x_days', 'últimos_x_dias' => 'last_x_days',
        'ultimos_x_meses' => 'last_x_months', 'últimos_x_meses' => 'last_x_months',
        'ultimos_x_anos' => 'last_x_years', 'últimos_x_anos' => 'last_x_years',
        'ha_mais_de_x_dias' => 'more_than_x_days_ago', 'há_mais_de_x_dias' => 'more_than_x_days_ago',
        'ha_menos_de_x_dias' => 'less_than_x_days_ago',
        'esta_em' => 'in', 'está_em' => 'in',
        'nao_esta_em' => 'not_in', 'não_está_em' => 'not_in',
        'sim' => 'is_true', 'nao' => 'is_false', 'não' => 'is_false',
    ];

    private const CAMPO_ALIASES = [
        'genero' => 'sexo', 'gênero' => 'sexo', 'gender' => 'sexo',
        'homem' => 'sexo', 'homens' => 'sexo', 'mulher' => 'sexo', 'mulheres' => 'sexo',
        'cidade' => 'municipio', 'municipio' => 'municipio',
        'nome_cliente' => 'nome', 'cliente_nome' => 'nome',
        'telefone_cliente' => 'telefone', 'celular' => 'telefone',
        'email_cliente' => 'email', 'e_mail' => 'email',
        'aniversario' => 'nascimento', 'aniversário' => 'nascimento', 'data_nascimento' => 'nascimento',
        'qtd_pedidos_confirmados' => 'qtd_pedidos', 'quantidade_pedidos' => 'qtd_pedidos', 'pedidos' => 'qtd_pedidos',
        'ultima_compra' => 'ultimo_pedido', 'ultimo_pedido_em' => 'ultimo_pedido',
        'cashback_saldo' => 'cashback', 'saldo_cashback' => 'cashback',
        'produto' => 'produto_comprado', 'produtos' => 'produto_comprado', 'produto_nome' => 'produto_comprado',
        'valor_total_comprado' => 'valor_total_comprado', 'total_comprado' => 'valor_total_comprado',
    ];

    /**
     * @param  array<string, mixed>|string  $payload
     * @return array<string, mixed>
     */
    public function parse(mixed $payload, string $prompt = ''): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            $payload = is_array($decoded) ? $decoded : ['conditions' => $payload];
        }

        if (!is_array($payload) || $payload === []) {
            return $prompt !== '' ? $this->fallbackFromPrompt($prompt) : $this->emptyStructure();
        }

        // Remove qualquer SQL que a IA possa ter retornado indevidamente
        unset($payload['sql'], $payload['query'], $payload['sql_query'], $payload['consulta_sql']);

        $result = [
            'version' => (int) ($payload['version'] ?? 2),
            'entity' => 'cliente',
            'logic' => $this->normalizarLogic($payload['logic'] ?? $payload['operador'] ?? $payload['operador_grupos'] ?? 'AND'),
            'limit' => max(1, min((int) ($payload['limit'] ?? $payload['limite'] ?? 25), 500)),
            'order' => $this->normalizarOrder($payload['order'] ?? $payload['ordenacao'] ?? $payload['ordem'] ?? 'random asc'),
            'resumo_humano' => $payload['resumo_humano'] ?? $payload['resumo'] ?? null,
        ];

        $groups = $this->parseGroups($payload);

        if ($groups !== []) {
            $result['version'] = max(2, $result['version']);
            $result['groups'] = $groups;
            $result['conditions'] = [];
        } else {
            $conditions = $this->parseConditions($payload['conditions'] ?? $payload['condicoes'] ?? $payload['filtros'] ?? []);
            $result['conditions'] = $conditions;
        }

        if (($result['groups'] ?? []) === [] && ($result['conditions'] ?? []) === []) {
            return $prompt !== '' ? $this->fallbackFromPrompt($prompt) : $this->emptyStructure();
        }

        return $result;
    }

    /**
     * Fallback heurístico quando a IA retorna JSON inválido ou vazio.
     *
     * @return array<string, mixed>
     */
    public function fallbackFromPrompt(string $prompt): array
    {
        $texto = $this->normalizarTexto($prompt);

        if ($texto === '') {
            return ['version' => 2, 'entity' => 'cliente', 'logic' => 'AND', 'conditions' => [], 'limit' => 25, 'order' => ['field' => 'random', 'direction' => 'asc']];
        }

        $conditions = [];

        // Homens / Mulheres
        if (preg_match('/\b(homens?|masculinos?|machos?)\b/u', $texto)) {
            $conditions[] = ['field' => 'sexo', 'operator' => 'equals', 'value' => 'Masculino'];
        } elseif (preg_match('/\b(mulheres?|femininas?|femininos?)\b/u', $texto)) {
            $conditions[] = ['field' => 'sexo', 'operator' => 'equals', 'value' => 'Feminino'];
        }

        // Mais de X pedidos
        if (preg_match('/mais\s+de\s+(\d+)\s+pedidos?/u', $texto, $m) || preg_match('/(\d+)\s+ou\s+mais\s+pedidos?/u', $texto, $m)) {
            $conditions[] = ['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => (int) $m[1]];
        }

        // Sem compra há X dias
        if (preg_match('/sem\s+compra.*?(\d+)\s+dias?/u', $texto, $m) || preg_match('/inativos?\s+h[aá]\s+(\d+)\s+dias?/u', $texto, $m)) {
            $conditions[] = ['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => (int) $m[1]];
        }

        // Compraram produto
        if (preg_match('/compr(?:ou|aram)\s+(.+?)(?:\s+hoje|\s+ontem|$)/u', $prompt, $m)) {
            $produto = trim(preg_replace('/^(o|a|os|as)\s+/iu', '', $m[1]));
            if ($produto !== '' && mb_strlen($produto) <= 80) {
                $conditions[] = ['field' => 'produto_comprado', 'operator' => 'contains', 'value' => $produto];
            }
        }

        // Cashback
        if (str_contains($texto, 'cashback') || str_contains($texto, 'pontos')) {
            $conditions[] = ['field' => 'cashback', 'operator' => 'greater_than', 'value' => 0];
        }

        // Aniversariantes
        if (str_contains($texto, 'aniversari')) {
            $conditions[] = ['field' => 'nascimento', 'operator' => 'today', 'value' => null];
        }

        $order = ['field' => 'random', 'direction' => 'asc'];
        $limit = 25;

        if (preg_match('/\btop\s*(\d{1,3})\b/i', $texto, $m)) {
            $limit = max(1, min((int) $m[1], 500));
            $order = ['field' => 'qtd_pedidos', 'direction' => 'desc'];
            if (! $this->temCampo($conditions, 'qtd_pedidos')) {
                $conditions[] = ['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => 0];
            }
        }

        return [
            'version' => 2,
            'entity' => 'cliente',
            'logic' => 'AND',
            'conditions' => $conditions,
            'limit' => $limit,
            'order' => $order,
            'resumo_humano' => 'Interpretado automaticamente: ' . $prompt,
            '_fallback' => true,
        ];
    }

    /** @param  array<string, mixed>  $payload */
    private function parseGroups(array $payload): array
    {
        $raw = $payload['groups'] ?? $payload['grupos'] ?? [];

        if (!is_array($raw)) {
            return [];
        }

        $groups = [];

        foreach ($raw as $group) {
            if (!is_array($group)) {
                continue;
            }

            $conditions = $this->parseConditions($group['conditions'] ?? $group['condicoes'] ?? $group['filtros'] ?? []);

            if ($conditions === []) {
                continue;
            }

            $groups[] = [
                'logic' => $this->normalizarLogic($group['logic'] ?? $group['operador'] ?? 'AND'),
                'conditions' => $conditions,
            ];
        }

        return $groups;
    }

    /** @return array<int, array<string, mixed>> */
    private function parseConditions(mixed $conditions): array
    {
        if (is_string($conditions)) {
            return $this->parseConditionsString($conditions);
        }

        if (!is_array($conditions)) {
            return [];
        }

        // Array de condições oficiais
        if (isset($conditions[0]) && is_array($conditions[0])) {
            $result = [];
            foreach ($conditions as $cond) {
                if (!is_array($cond)) {
                    continue;
                }
                $parsed = $this->parseCondition($cond);
                if ($parsed !== null) {
                    $result[] = $parsed;
                }
            }

            return $result;
        }

        // Objeto único
        if (isset($conditions['field']) || isset($conditions['campo'])) {
            $parsed = $this->parseCondition($conditions);

            return $parsed !== null ? [$parsed] : [];
        }

        return [];
    }

    /** @param  array<string, mixed>  $cond */
    private function parseCondition(array $cond): ?array
    {
        $field = $this->normalizarCampo((string) ($cond['field'] ?? $cond['campo'] ?? $cond['chave'] ?? ''));
        $operator = $this->normalizarOperador((string) ($cond['operator'] ?? $cond['operador'] ?? $cond['comparacao'] ?? ''));
        $value = $cond['value'] ?? $cond['valor'] ?? $cond['val'] ?? null;

        if ($field === '' || $operator === '') {
            return null;
        }

        if (is_string($value) && is_numeric(trim($value)) && ! in_array($operator, ['contains', 'not_contains', 'starts_with', 'ends_with'], true)) {
            $value = str_contains(trim($value), '.') ? (float) trim($value) : (int) trim($value);
        }

        return ['field' => $field, 'operator' => $operator, 'value' => $value];
    }

    /** @return array<int, array<string, mixed>> */
    private function parseConditionsString(string $texto): array
    {
        $texto = trim($texto);
        if ($texto === '' || $texto === '[]') {
            return [];
        }

        $json = json_decode($texto, true);
        if (is_array($json)) {
            return $this->parseConditions($json);
        }

        $partes = preg_split('/\s+(AND|OR)\s+/i', $texto, -1, PREG_SPLIT_NO_EMPTY);
        $resultado = [];

        foreach ($partes as $parte) {
            $tokens = preg_split('/\s+/', trim($parte));
            if (count($tokens) < 2) {
                continue;
            }

            $field = $this->normalizarCampo($tokens[0]);
            $operator = $this->normalizarOperador($tokens[1]);
            $value = count($tokens) >= 3 ? implode(' ', array_slice($tokens, 2)) : null;

            if (is_string($value) && is_numeric($value)) {
                $value = $value + 0;
            }

            $resultado[] = ['field' => $field, 'operator' => $operator, 'value' => $value];
        }

        return $resultado;
    }

    private function normalizarCampo(string $field): string
    {
        $f = mb_strtolower(trim($field));
        $f = str_replace(['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $f);
        $f = preg_replace('/[^a-z0-9_]+/u', '_', $f) ?: $f;
        $f = trim($f, '_');

        return self::CAMPO_ALIASES[$f] ?? $f;
    }

    private function normalizarOperador(string $operator): string
    {
        $op = mb_strtolower(trim($operator));
        $op = str_replace(['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç', ' '], ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c', '_'], $op);

        return self::OPERADOR_ALIASES[$op] ?? $op;
    }

    private function normalizarLogic(mixed $logic): string
    {
        return strtoupper((string) $logic) === 'OR' ? 'OR' : 'AND';
    }

    /** @return array{field: string, direction: string} */
    private function normalizarOrder(mixed $order): array
    {
        if (is_array($order)) {
            return [
                'field' => (string) ($order['field'] ?? $order['campo'] ?? 'random'),
                'direction' => strtolower((string) ($order['direction'] ?? $order['direcao'] ?? 'asc')) === 'desc' ? 'desc' : 'asc',
            ];
        }

        $texto = trim((string) $order);
        if ($texto === '') {
            return ['field' => 'random', 'direction' => 'asc'];
        }

        $tokens = preg_split('/\s+/', $texto);

        return [
            'field' => $tokens[0] ?? 'random',
            'direction' => strtolower($tokens[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
        ];
    }

    private function normalizarTexto(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á', 'à', 'â', 'ã', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'], ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'], $v);

        return preg_replace('/[^a-z0-9 ]+/u', ' ', $v) ?: $v;
    }

    /** @param  array<int, array<string, mixed>>  $conditions */
    private function temCampo(array $conditions, string $field): bool
    {
        foreach ($conditions as $c) {
            if (($c['field'] ?? null) === $field) {
                return true;
            }
        }

        return false;
    }
}
