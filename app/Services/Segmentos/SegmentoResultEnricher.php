<?php

namespace App\Services\Segmentos;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Converte modelos Eloquent em arrays compatíveis com SegmentoRuleExplainer.
 */
class SegmentoResultEnricher
{
    /**
     * @param  EloquentCollection<int, \App\Models\Cliente>  $models
     * @param  array<int, string>  $fieldsNeeded
     * @return array<int, array<string, mixed>>
     */
    public function toRows(EloquentCollection $models, array $fieldsNeeded = []): array
    {
        return $models->map(function ($cliente) {
            $row = $cliente->toArray();

            // Aliases esperados pelo Explainer (mesmo formato do SQL Builder)
            $row['cli_qtd_pedidos_calc'] = $cliente->_seg_qtd_pedidos ?? $cliente->getAttribute('_seg_qtd_pedidos') ?? null;
            $row['cli_ultimo_pedido_calc'] = $cliente->_seg_ultimo_pedido ?? $cliente->getAttribute('_seg_ultimo_pedido') ?? null;
            $row['cli_primeira_compra_calc'] = $cliente->_seg_primeira_compra ?? $cliente->getAttribute('_seg_primeira_compra') ?? null;
            $row['cli_valor_total_comprado_calc'] = $cliente->_seg_valor_total_comprado ?? $cliente->getAttribute('_seg_valor_total_comprado') ?? null;
            $row['cli_cashback_calc'] = $cliente->_seg_cashback ?? $cliente->getAttribute('_seg_cashback') ?? null;
            $row['cli_produtos_comprados_calc'] = $cliente->_seg_produto_comprado ?? $cliente->getAttribute('_seg_produto_comprado') ?? null;

            return $row;
        })->all();
    }
}
