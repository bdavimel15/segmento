<?php

namespace App\Services\Segmentos;

use Illuminate\Support\Facades\DB;
use Throwable;

class SegmentoPreviewService
{
    public function preview(string $sql, array $bindings, ?array $regra = null): array
    {
        $started = microtime(true);

        try {
            $rows = DB::select($sql, $bindings);
            $exemplos = array_slice(json_decode(json_encode($rows), true), 0, 10);
            $elapsedMs = round((microtime(true) - $started) * 1000, 2);

            $result = [
                'ok' => true,
                'total' => count($rows),
                'exemplos' => $exemplos,
                'tempo_ms' => $elapsedMs,
                'analisados' => count($rows),
                'aprovados' => count($rows),
            ];

            if ($regra !== null) {
                $explainer = new SegmentoRuleExplainer();
                $result['resumo'] = $explainer->resumoRegra($regra);

                $explicacoesTodas = $explainer->explainPreview($exemplos, $regra);

                // Segurança extra: a tabela de prévia nunca deve exibir cliente reprovado.
                // A SQL já deve filtrar, mas este pós-filtro evita que erros de alias/campo
                // mostrem mulheres em segmento de homens, ou clientes reprovados em grupos aprovados.
                $exemplosAprovados = [];
                $explicacoesAprovadas = [];

                foreach ($exemplos as $index => $exemplo) {
                    $explicacao = $explicacoesTodas[$index] ?? $explainer->explainClient($exemplo, $regra);

                    if (($explicacao['approved'] ?? false) === true) {
                        $exemplosAprovados[] = $exemplo;
                        $explicacoesAprovadas[] = $explicacao;
                    }
                }

                $result['exemplos'] = $exemplosAprovados;
                $result['explicacoes'] = $explicacoesAprovadas;
                $result['total'] = count($exemplosAprovados);
                $result['aprovados'] = count($exemplosAprovados);
                $result['reprovados_descartados'] = count($exemplos) - count($exemplosAprovados);
            }

            return $result;
        } catch (Throwable $e) {
            return [
                'ok' => false,
                'total' => 0,
                'exemplos' => [],
                'explicacoes' => [],
                'erro' => $e->getMessage(),
                'tempo_ms' => round((microtime(true) - $started) * 1000, 2),
                'analisados' => 0,
                'aprovados' => 0,
            ];
        }
    }
}
