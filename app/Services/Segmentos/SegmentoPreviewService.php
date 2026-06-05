<?php

namespace App\Services\Segmentos;

use Illuminate\Support\Facades\DB;
use Throwable;

class SegmentoPreviewService
{
    /**
     * Prévia a partir de linhas já executadas (motor Eloquent ou SQL).
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function previewFromRows(array $rows, ?array $regra = null, string $motor = 'eloquent'): array
    {
        $started = microtime(true);

        try {
            $exemplos = array_slice($rows, 0, 10);
            $elapsedMs = round((microtime(true) - $started) * 1000, 2);

            $result = [
                'ok' => true,
                'total' => count($rows),
                'exemplos' => $exemplos,
                'tempo_ms' => $elapsedMs,
                'analisados' => count($rows),
                'aprovados' => count($rows),
                'motor' => $motor,
            ];

            return $this->enriquecerComExplicacoes($result, $exemplos, $regra);
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
                'motor' => $motor,
            ];
        }
    }

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
                'motor' => 'sql',
            ];

            return $this->enriquecerComExplicacoes($result, $exemplos, $regra);
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

    /**
     * @param  array<string, mixed>  $result
     * @param  array<int, array<string, mixed>>  $exemplos
     * @return array<string, mixed>
     */
    private function enriquecerComExplicacoes(array $result, array $exemplos, ?array $regra): array
    {
        if ($regra === null) {
            return $result;
        }

        $explainer = new SegmentoRuleExplainer();
        $result['resumo'] = $explainer->resumoRegra($regra);

        $explicacoesTodas = $explainer->explainPreview($exemplos, $regra);

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

        return $result;
    }
}
