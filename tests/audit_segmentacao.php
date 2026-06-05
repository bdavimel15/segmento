<?php

/**
 * Script de auditoria — testes reais do motor de segmentação.
 * Uso: php tests/audit_segmentacao.php
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Segmentos\AiSegmentoInterpreter;
use App\Services\Segmentos\SegmentoQueryExecutor;
use App\Services\Segmentos\SegmentoRuleValidator;

$validator = app(SegmentoRuleValidator::class);
$executor = app(SegmentoQueryExecutor::class);
$interpreter = app(AiSegmentoInterpreter::class);

$casos = [
    'Sexo Masculino' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'sexo', 'operator' => 'equals', 'value' => 'Masculino']]]]],
    'Sexo Feminino' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'sexo', 'operator' => 'equals', 'value' => 'Feminino']]]]],
    'Nome contém João' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'nome', 'operator' => 'contains', 'value' => 'João']]]]],
    'Idade >= 18' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'idade', 'operator' => 'greater_or_equal', 'value' => 18]]]]],
    'Sem compra 30 dias' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 30]]]]],
    'Aniversariantes' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'nascimento', 'operator' => 'today', 'value' => null]]]]],
    'Top compradores' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'qtd_pedidos', 'operator' => 'greater_than', 'value' => 0]]]], 'limit' => 10, 'order' => ['field' => 'qtd_pedidos', 'direction' => 'desc']],
    'Clientes ativos (newsletter)' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'newsletter', 'operator' => 'is_true', 'value' => null]]]]],
    'Clientes inativos 90d' => ['groups' => [[ 'logic' => 'AND', 'conditions' => [['field' => 'ultimo_pedido', 'operator' => 'more_than_x_days_ago', 'value' => 90]]]]],
];

$ok = 0;
$fail = 0;
$results = [];

foreach ($casos as $nome => $payload) {
    try {
        $regra = $interpreter->normalizar([
            'version' => 2,
            'entity' => 'cliente',
            'logic' => 'AND',
            'groups' => $payload['groups'],
            'limit' => $payload['limit'] ?? 25,
            'order' => $payload['order'] ?? ['field' => 'random', 'direction' => 'asc'],
        ], '', 'manual');

        $validator->validar($regra);
        $exec = $executor->executar($regra);
        $total = count($exec['rows'] ?? []);
        $results[] = ['caso' => $nome, 'status' => 'OK', 'motor' => $exec['motor'], 'total' => $total];
        $ok++;
    } catch (Throwable $e) {
        $results[] = ['caso' => $nome, 'status' => 'ERRO', 'erro' => $e->getMessage()];
        $fail++;
    }
}

echo "=== AUDITORIA SEGMENTAÇÃO ===\n";
echo "OK: {$ok} | ERRO: {$fail}\n\n";

foreach ($results as $r) {
    if ($r['status'] === 'OK') {
        echo "[OK] {$r['caso']} — motor={$r['motor']} total={$r['total']}\n";
    } else {
        echo "[ERRO] {$r['caso']} — {$r['erro']}\n";
    }
}

exit($fail > 0 ? 1 : 0);
