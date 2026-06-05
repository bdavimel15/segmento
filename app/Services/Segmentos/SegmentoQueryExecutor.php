<?php

namespace App\Services\Segmentos;

use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Camada de execução: Eloquent (primário) com fallback SQL (legado).
 *
 * Fluxo: IA → JSON semântico → Parser → Eloquent → Consulta final
 */
class SegmentoQueryExecutor
{
    public function __construct(
        private SegmentoEloquentBuilder $eloquentBuilder,
        private SegmentoSqlBuilder $sqlBuilder,
        private ?SegmentoExecutionLogger $logger = null,
    ) {}

    /**
     * @param  array<string, mixed>  $regra
     * @param  array<string, mixed>  $context
     * @return array{motor: string, sql: string, bindings: array<int, mixed>, rows: array<int, array<string, mixed>>, logs: array<int, array<string, mixed>>}
     */
    public function executar(array $regra, array $context = []): array
    {
        $logger = $this->logger ?? new SegmentoExecutionLogger();
        $regra = SegmentoRuleHelper::enrichRule($regra);

        if (! empty($context['prompt'])) {
            $logger->prompt((string) $context['prompt']);
        }

        $logger->jsonGerado($regra);
        $logger->filtrosInterpretados($regra);

        // Tenta Eloquent primeiro
        try {
            $built = $this->eloquentBuilder->build($regra);
            $query = $built['query'];
            $rows = $this->eloquentBuilder->fetch($built);

            $sql = $query->toSql();
            $bindings = $query->getBindings();

            $logger->consultaMontada('eloquent', $sql, $bindings);
            $logger->quantidadeEncontrada(count($rows), 'eloquent');

            return [
                'motor' => 'eloquent',
                'sql' => $sql,
                'bindings' => $bindings,
                'rows' => $rows,
                'logs' => $logger->entries(),
            ];
        } catch (Throwable $e) {
            $logger->erro('Falha no motor Eloquent: ' . $e->getMessage(), $e);
            $logger->fallback('eloquent', 'sql', $e->getMessage());
        }

        // Fallback SQL legado
        try {
            $sqlData = $this->sqlBuilder->build($regra);
            $rows = json_decode(json_encode(DB::select($sqlData['sql'], $sqlData['bindings'])), true);

            $logger->consultaMontada('sql', $sqlData['sql'], $sqlData['bindings']);
            $logger->quantidadeEncontrada(count($rows), 'sql');

            return [
                'motor' => 'sql',
                'sql' => $sqlData['sql'],
                'bindings' => $sqlData['bindings'],
                'rows' => $rows,
                'logs' => $logger->entries(),
            ];
        } catch (Throwable $e) {
            $logger->erro('Falha no motor SQL (fallback): ' . $e->getMessage(), $e);
            throw $e;
        }
    }
}
