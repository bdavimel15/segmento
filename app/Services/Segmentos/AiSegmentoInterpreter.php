<?php

namespace App\Services\Segmentos;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class AiSegmentoInterpreter
{
    public function interpretar(string $mensagem): array
    {
        $url = env('ZAIA_WEBHOOK_URL');

        if (!$url) {
            throw new \Exception('ZAIA_WEBHOOK_URL não configurada no .env');
        }

        try {
            $response = Http::timeout(20)
                ->connectTimeout(8)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    // Mantém todos os nomes para evitar diferença entre teste e produção na Zaia
                    'content' => $mensagem,
                    'mensagem' => $mensagem,
                    'texto' => $mensagem,
                    'descricao' => $mensagem,
                ]);
        } catch (ConnectionException $e) {
            throw new \Exception('Zaia demorou para responder ou não conectou.');
        }

        if (!$response->successful()) {
            throw new \Exception('Erro na Zaia: HTTP ' . $response->status());
        }

        $json = $response->json();

        // A Zaia pode responder de várias formas:
        // { data: {...} }, { content: {...} }, { code: 200, data: {...} } ou direto {...}
        $payload = $json['data'] ?? $json['content'] ?? $json;

        if (isset($payload['content']) && is_array($payload['content'])) {
            $payload = $payload['content'];
        }

        return $this->normalizar($payload, $mensagem, 'ia');
    }

    public function normalizar(array $regra, string $mensagem = '', string $origem = 'manual'): array
    {
        $groups = $this->normalizarGroups($regra['groups'] ?? []);
        $conditions = $this->normalizarConditions($regra['conditions'] ?? []);
        $order = $this->normalizarOrder($regra['order'] ?? 'random asc');
        $limit = max(1, min((int)($regra['limit'] ?? 25), 500));

        $produtoDetectado = $this->detectarProdutoComprado($mensagem);
        if ($produtoDetectado !== null && !$this->temCondicaoCampo($conditions, 'produto_comprado') && !$this->temCondicaoCampoNosGrupos($groups, 'produto_comprado')) {
            $nova = [
                'field' => 'produto_comprado',
                'operator' => 'contains',
                'value' => $produtoDetectado,
            ];

            if ($groups !== []) {
                $groups[0]['conditions'][] = $nova;
            } else {
                $conditions[] = $nova;
            }
        }

        // Regra de negócio importante: pedidos como "top 4 clientes com mais pedidos"
        // são ranking, não filtro simples. A IA às vezes devolve ordem aleatória.
        // Aqui garantimos ordenação correta e limite correto baseado na frase original.
        $ranking = $this->detectarRankingPorPedidos($mensagem);
        if ($ranking !== null) {
            $order = ['field' => 'qtd_pedidos', 'direction' => 'desc'];
            $limit = $ranking;

            if (!$this->temCondicaoCampo($conditions, 'qtd_pedidos') && !$this->temCondicaoCampoNosGrupos($groups, 'qtd_pedidos')) {
                $nova = [
                    'field' => 'qtd_pedidos',
                    'operator' => 'greater_than',
                    'value' => 0,
                ];

                if ($groups !== []) {
                    $groups[0]['conditions'][] = $nova;
                } else {
                    $conditions[] = $nova;
                }
            }
        }

        $payload = [
            'version' => (int)($regra['version'] ?? 1),
            'entity' => 'cliente',
            'logic' => strtoupper((string)($regra['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
            'limit' => $limit,
            'order' => $order,
            'origem' => $origem,
            'resumo_humano' => $regra['resumo_humano'] ?? ($origem === 'ia' ? 'Grupo criado com IA: ' . $mensagem : 'Grupo criado manualmente.'),
        ];

        if ($groups !== []) {
            $payload['version'] = max(2, $payload['version']);
            $payload['groups'] = $groups;
            $payload['conditions'] = [];
        } else {
            $payload['conditions'] = $conditions;
        }

        return $payload;
    }

    private function detectarProdutoComprado(string $mensagem): ?string
    {
        $texto = trim($mensagem);
        $normalizado = $this->normalizarTextoLivre($texto);

        if (!str_contains($normalizado, 'compr') && !str_contains($normalizado, 'produto') && !str_contains($normalizado, 'pedido')) {
            return null;
        }

        $padroes = [
            '/compr(?:ou|aram|aram\s+o|aram\s+a|ou\s+o|ou\s+a)?\s+(.+)$/iu',
            '/produto\s+(.+)$/iu',
            '/pedido\s+(?:com|de)\s+(.+)$/iu',
        ];

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $texto, $m)) {
                $produto = trim($m[1]);
                $produto = preg_replace('/^(o|a|os|as|de|do|da|dos|das)\s+/iu', '', $produto) ?: $produto;
                $produto = preg_replace('/\s+(hoje|ontem|nos ultimos.*|há.*)$/iu', '', $produto) ?: $produto;

                $produtoNormalizado = $this->normalizarTextoLivre($produto);
                $somenteTempo = preg_match('/^(hoje|ontem|amanha|últimos|ultimos|dias|semana|mes|mês|ano|anos|dias)$/iu', $produtoNormalizado);

                if ($produto !== '' && !$somenteTempo && mb_strlen($produto) <= 80) {
                    return $produto;
                }
            }
        }

        return null;
    }

    private function detectarRankingPorPedidos(string $mensagem): ?int
    {
        $texto = $this->normalizarTextoLivre($mensagem);

        $falaDeRanking = str_contains($texto, 'top')
            || str_contains($texto, 'mais pedidos')
            || str_contains($texto, 'maior quantidade de pedidos')
            || str_contains($texto, 'clientes com mais pedido')
            || str_contains($texto, 'clientes que mais compraram');

        $falaDePedido = str_contains($texto, 'pedido') || str_contains($texto, 'compraram') || str_contains($texto, 'compras');

        if (!$falaDeRanking || !$falaDePedido) {
            return null;
        }

        if (preg_match('/\btop\s*(\d{1,3})\b/i', $texto, $m)) {
            return max(1, min((int)$m[1], 500));
        }

        if (preg_match('/\b(\d{1,3})\s*(clientes|primeiros|maiores)\b/i', $texto, $m)) {
            return max(1, min((int)$m[1], 500));
        }

        return 25;
    }

    private function temCondicaoCampo(array $conditions, string $field): bool
    {
        foreach ($conditions as $condition) {
            if (($condition['field'] ?? null) === $field) {
                return true;
            }
        }

        return false;
    }

    private function temCondicaoCampoNosGrupos(array $groups, string $field): bool
    {
        foreach ($groups as $group) {
            foreach (($group['conditions'] ?? []) as $condition) {
                if (($condition['field'] ?? null) === $field) {
                    return true;
                }
            }
        }

        return false;
    }

    private function normalizarTextoLivre(string $value): string
    {
        $v = mb_strtolower(trim($value));
        $v = str_replace(['á','à','â','ã','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç'], ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c'], $v);
        return preg_replace('/[^a-z0-9 ]+/u', ' ', $v) ?: $v;
    }

    private function normalizarGroups(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        $resultado = [];

        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }

            $conditions = $this->normalizarConditions($group['conditions'] ?? []);

            if ($conditions === []) {
                continue;
            }

            $resultado[] = [
                'logic' => strtoupper((string)($group['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
                'conditions' => $conditions,
            ];
        }

        return $resultado;
    }

    private function normalizarConditions(mixed $conditions): array
    {
        if (is_array($conditions)) {
            // Já veio no formato oficial.
            if (isset($conditions[0]) && is_array($conditions[0]) && isset($conditions[0]['field'])) {
                return array_values(array_filter($conditions, fn ($c) => !empty($c['field']) && !empty($c['operator'])));
            }

            return [];
        }

        $texto = trim((string)$conditions);

        if ($texto === '' || $texto === '[]') {
            return [];
        }

        // Se algum dia a Zaia voltar a enviar JSON serializado, aceita também.
        $json = json_decode($texto, true);
        if (is_array($json)) {
            return array_values(array_filter($json, fn ($c) => is_array($c) && !empty($c['field']) && !empty($c['operator'])));
        }

        // Formato atual da Zaia:
        // ultima_compra more_than_x_days_ago 30
        // qtd_pedidos_confirmados greater_than 2 AND ultima_compra more_than_x_days_ago 30
        $partes = preg_split('/\s+(AND|OR)\s+/i', $texto, -1, PREG_SPLIT_NO_EMPTY);
        $resultado = [];

        foreach ($partes as $parte) {
            $tokens = preg_split('/\s+/', trim($parte));

            if (count($tokens) < 2) {
                continue;
            }

            $field = $this->normalizarCampo($tokens[0]);
            $operator = $tokens[1];
            $value = count($tokens) >= 3 ? implode(' ', array_slice($tokens, 2)) : null;

            if (is_string($value) && is_numeric($value)) {
                $value = $value + 0;
            }

            $resultado[] = [
                'field' => $field,
                'operator' => $operator,
                'value' => $value,
            ];
        }

        return $resultado;
    }


    private function normalizarCampo(string $field): string
    {
        $f = mb_strtolower(trim($field));
        $f = str_replace(['á','à','â','ã','é','ê','í','ó','ô','õ','ú','ç'], ['a','a','a','a','e','e','i','o','o','o','u','c'], $f);
        $f = preg_replace('/[^a-z0-9_]+/u', '_', $f) ?: $f;
        $f = trim($f, '_');

        return match ($f) {
            'genero', 'gender', 'genero_cliente', 'sexo_cliente', 'homem', 'mulher' => 'sexo',
            'cidade', 'cidade_cliente', 'municipio_cliente', 'localidade' => 'municipio',
            'bairro_cliente' => 'bairro',
            'nome_cliente', 'cliente_nome' => 'nome',
            'telefone_cliente', 'celular' => 'telefone',
            'email_cliente', 'e_mail' => 'email',
            'newsletter_cliente', 'recebe_newsletter' => 'newsletter',
            'aniversario', 'aniversário', 'nascimento_cliente', 'data_nascimento', 'data_de_nascimento', 'cli_data_nascimento' => 'nascimento',
            'funcionario_cliente' => 'funcionario',
            'qtd_pedidos_confirmados', 'quantidade_pedidos', 'quantidade_de_pedidos', 'pedidos' => 'qtd_pedidos',
            'ultima_compra', 'ultimo_pedido', 'ultimo_pedido_em' => 'ultimo_pedido',
            'cashback_saldo', 'saldo_cashback' => 'cashback',
            'valor_total_comprado', 'total_comprado' => 'valor_total_comprado',
            'produto', 'produtos', 'produto_nome', 'nome_produto', 'item', 'itens', 'produto_comprado', 'produtos_comprados', 'comprou_produto' => 'produto_comprado',
            default => $field,
        };
    }

    private function normalizarOrder(mixed $order): array
    {
        if (is_array($order)) {
            return [
                'field' => $order['field'] ?? 'random',
                'direction' => strtolower($order['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ];
        }

        $texto = trim((string)$order);
        if ($texto === '') {
            return ['field' => 'random', 'direction' => 'asc'];
        }

        $json = json_decode($texto, true);
        if (is_array($json)) {
            return [
                'field' => $json['field'] ?? 'random',
                'direction' => strtolower($json['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            ];
        }

        $tokens = preg_split('/\s+/', $texto);

        return [
            'field' => $tokens[0] ?? 'random',
            'direction' => strtolower($tokens[1] ?? 'asc') === 'desc' ? 'desc' : 'asc',
        ];
    }
}
