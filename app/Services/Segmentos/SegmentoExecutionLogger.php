<?php

namespace App\Services\Segmentos;

use Illuminate\Support\Facades\Log;

class SegmentoExecutionLogger
{
    private string $correlationId;

    private array $entries = [];

    public function __construct(?string $correlationId = null)
    {
        $this->correlationId = $correlationId ?? uniqid('seg_', true);
    }

    public function id(): string
    {
        return $this->correlationId;
    }

    public function log(string $etapa, array $dados = []): void
    {
        $entry = [
            'correlation_id' => $this->correlationId,
            'etapa' => $etapa,
            'timestamp' => now()->toIso8601String(),
            'dados' => $dados,
        ];

        $this->entries[] = $entry;

        try {
            Log::channel('single')->info('[Segmentador] ' . $etapa, $entry);
        } catch (\Throwable) {
            // Falha de log não deve derrubar a prévia/API
        }
    }

    public function prompt(string $texto): void
    {
        $this->log('prompt_recebido', ['prompt' => $texto]);
    }

    public function jsonGerado(mixed $json): void
    {
        $this->log('json_gerado', ['json' => $json]);
    }

    public function filtrosInterpretados(array $regra): void
    {
        $this->log('filtros_interpretados', [
            'logic' => $regra['logic'] ?? 'AND',
            'groups' => SegmentoRuleHelper::getGroups($regra),
            'limit' => $regra['limit'] ?? null,
            'order' => $regra['order'] ?? null,
        ]);
    }

    public function consultaMontada(string $motor, string $sql, array $bindings = []): void
    {
        $this->log('consulta_montada', [
            'motor' => $motor,
            'sql' => $sql,
            'bindings' => $bindings,
        ]);
    }

    public function quantidadeEncontrada(int $total, string $motor): void
    {
        $this->log('quantidade_encontrada', ['total' => $total, 'motor' => $motor]);
    }

    public function erro(string $mensagem, ?\Throwable $e = null): void
    {
        $this->log('erro', [
            'mensagem' => $mensagem,
            'exception' => $e?->getMessage(),
            'trace' => $e ? array_slice($e->getTrace(), 0, 3) : null,
        ]);
    }

    public function fallback(string $de, string $para, string $motivo): void
    {
        $this->log('fallback', ['de' => $de, 'para' => $para, 'motivo' => $motivo]);
    }

    /** @return array<int, array<string, mixed>> */
    public function entries(): array
    {
        return $this->entries;
    }
}
