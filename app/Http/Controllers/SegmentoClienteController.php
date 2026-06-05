<?php

namespace App\Http\Controllers;

use App\Models\SegmentoCliente;
use App\Models\SegmentoClienteCampo;
use App\Models\SegmentoClienteExecucao;
use App\Models\SegmentoClientePreset;
use App\Models\SegmentoClienteValidacao;
use App\Models\Cliente;
use App\Services\Segmentos\AiSegmentoInterpreter;
use App\Services\Segmentos\SegmentoPreviewService;
use App\Services\Segmentos\SegmentoRuleExplainer;
use App\Services\Segmentos\SegmentoRuleHelper;
use App\Services\Segmentos\SegmentoRuleValidator;
use App\Services\Segmentos\SegmentoQueryExecutor;
use App\Services\Segmentos\SegmentoSemanticParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\SegmentadorUi;
use Throwable;

class SegmentoClienteController extends Controller
{

    public function dashboard()
    {
        $metricas = [
            'clientes' => Cliente::whereNull('excluido')->count(),
            'segmentos' => SegmentoCliente::whereNull('excluido')->count(),
            'segmentos_validados' => SegmentoCliente::whereNull('excluido')->where('status_validacao', 'validada')->count(),
            'execucoes' => SegmentoClienteExecucao::count(),
            'preview_total' => SegmentoCliente::whereNull('excluido')->sum('ultima_previa_qtd'),
        ];

        $ultimosSegmentos = SegmentoCliente::whereNull('excluido')
            ->orderByDesc('segmento_cliente_id')
            ->limit(6)
            ->get();

        $ultimasExecucoes = SegmentoClienteExecucao::orderByDesc('segmento_cliente_execucao_id')
            ->limit(8)
            ->get();

        return view('dashboard.index', compact('metricas', 'ultimosSegmentos', 'ultimasExecucoes'));
    }

    public function index()
    {
        $segmentos = SegmentoCliente::whereNull('excluido')->orderByDesc('segmento_cliente_id')->limit(50)->get();
        return view('segmentos.index', compact('segmentos'));
    }

    public function create()
    {
        $campos = SegmentoClienteCampo::where('ativo', 'S')->orderBy('ordem')->get();
        return view('segmentos.create', compact('campos'));
    }


    public function presets()
    {
        $presets = SegmentoClientePreset::where('ativo', 'S')
            ->orderBy('categoria')
            ->orderBy('ordem')
            ->orderBy('nome')
            ->get()
            ->groupBy(fn ($preset) => $preset->categoria ?: 'Geral');

        $presetsUsados = SegmentoCliente::whereNull('excluido')
            ->where('origem', 'preset')
            ->pluck('nome')
            ->unique()
            ->values()
            ->all();

        return view('segmentos.presets', compact('presets', 'presetsUsados'));
    }

    public function usarPreset(int $id, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $preset = SegmentoClientePreset::where('ativo', 'S')->findOrFail($id);
            $regra = (new SegmentoSemanticParser())->parse($preset->regra_json ?? []);

            if (!is_array($regra)) {
                throw new \RuntimeException('Preset com regra JSON inválida.');
            }

            $regra['origem'] = 'preset';
            $regra['preset_id'] = $preset->segmento_cliente_preset_id;
            $regra['resumo_humano'] = $regra['resumo_humano'] ?? ('Modelo pronto: ' . $preset->nome);
            $regra = SegmentoRuleHelper::enrichRule($regra);

            $validator->validar($regra);
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);

            $segmento = SegmentoCliente::create([
                'nome' => $preset->nome,
                'descricao' => $preset->descricao,
                'tipo' => 'dinamico',
                'origem' => 'preset',
                'regra_json' => $regra,
                'resumo_humano' => $regra['resumo_humano'] ?? null,
                'status_validacao' => 'pendente_validacao',
                'limite' => $regra['limit'] ?? 25,
                'ordenacao' => $this->mapOrdenacao($regra['order']['field'] ?? 'random'),
                'ultima_previa_qtd' => $previewData['total'] ?? 0,
                'ultima_previa_em' => now(),
            ]);

            SegmentoClienteExecucao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'canal' => 'preview',
                'regra_json_snapshot' => $regra,
                'sql_gerada_snapshot' => $exec['sql'],
                'total_encontrado' => $previewData['total'] ?? 0,
                'status' => ($previewData['ok'] ?? false) ? 'concluida' : 'erro',
                'erro' => $previewData['erro'] ?? null,
                'executado_em' => now(),
            ]);

            SegmentoClienteValidacao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'status_anterior' => null,
                'status_novo' => 'pendente_validacao',
                'regra_json_snapshot' => $regra,
                'resumo_humano_snapshot' => $segmento->resumo_humano,
                'observacao' => 'Criado a partir de modelo pronto — aguardando aprovação interna.',
            ]);

            return redirect()->route('segmentos.show', $segmento->segmento_cliente_id)
                ->with('ok', 'Modelo aplicado! Segmento criado e aguardando aprovação da equipe.');
        } catch (Throwable $e) {
            return redirect()->route('segmentos.presets')->with('erro', $e->getMessage());
        }
    }

    public function show(int $id, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $execucoes = SegmentoClienteExecucao::where('segmento_cliente_id', $id)
            ->orderByDesc('segmento_cliente_execucao_id')
            ->limit(20)
            ->get();
        $validacoes = SegmentoClienteValidacao::where('segmento_cliente_id', $id)
            ->orderByDesc('segmento_cliente_validacao_id')
            ->limit(20)
            ->get();

        $sqlData = ['sql' => null, 'bindings' => [], 'motor' => null];
        $previewData = ['ok' => false, 'total' => 0, 'exemplos' => []];

        try {
            $regra = $segmento->regra_json ?? [];
            $exec = $executor->executar($regra);
            $sqlData = ['sql' => $exec['sql'], 'bindings' => $exec['bindings'], 'motor' => $exec['motor']];
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);
        } catch (Throwable $e) {
            $previewData = ['ok' => false, 'total' => 0, 'exemplos' => [], 'explicacoes' => [], 'erro' => $e->getMessage()];
        }

        $resumoRegra = $previewData['resumo'] ?? (new SegmentoRuleExplainer())->resumoRegra($segmento->regra_json ?? []);

        return view('segmentos.show', compact('segmento', 'execucoes', 'validacoes', 'sqlData', 'previewData', 'resumoRegra'));
    }

    public function tecnico(int $id, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        if (! SegmentadorUi::devMode() && ! SegmentadorUi::isAdmin()) {
            abort(404);
        }

        $segmento = SegmentoCliente::findOrFail($id);
        $execucoes = SegmentoClienteExecucao::where('segmento_cliente_id', $id)
            ->orderByDesc('segmento_cliente_execucao_id')
            ->limit(20)
            ->get();
        $validacoes = SegmentoClienteValidacao::where('segmento_cliente_id', $id)
            ->orderByDesc('segmento_cliente_validacao_id')
            ->limit(20)
            ->get();

        $sqlData = ['sql' => null, 'bindings' => [], 'motor' => null];
        $previewData = ['ok' => false, 'total' => 0, 'exemplos' => []];
        $logs = [];

        try {
            $regra = $segmento->regra_json ?? [];
            $exec = $executor->executar($regra);
            $sqlData = ['sql' => $exec['sql'], 'bindings' => $exec['bindings'], 'motor' => $exec['motor']];
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);
            $logs = $exec['logs'] ?? [];
        } catch (Throwable $e) {
            $previewData = ['ok' => false, 'total' => 0, 'exemplos' => [], 'explicacoes' => [], 'erro' => $e->getMessage()];
        }

        $resumoRegra = $previewData['resumo'] ?? (new SegmentoRuleExplainer())->resumoRegra($segmento->regra_json ?? []);

        return view('segmentos.tecnico', compact('segmento', 'execucoes', 'validacoes', 'sqlData', 'previewData', 'resumoRegra', 'logs'));
    }

    public function interpretar(Request $request, AiSegmentoInterpreter $ai, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $texto = $request->input('texto', '');
            $regra = $ai->interpretar($texto);

            return $this->validarEGerarPreview($regra, $validator, $executor, $preview, ['prompt' => $texto]);
        } catch (Throwable $e) {
            report($e);

            return $this->jsonSegmentoErro('Erro ao interpretar segmento.', $e);
        }
    }

    public function manual(Request $request, AiSegmentoInterpreter $normalizer, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $groups = $this->parseGroupsFromRequest($request);

            if ($groups === []) {
                throw new \RuntimeException('Adicione pelo menos uma condição válida.');
            }

            $regra = $normalizer->normalizar([
                'version' => 2,
                'entity' => 'cliente',
                'logic' => $request->input('logic', 'AND'),
                'groups' => $groups,
                'limit' => (int) $request->input('limit', 25),
                'order' => [
                    'field' => $request->input('order_field', 'random'),
                    'direction' => $request->input('order_direction', 'asc'),
                ],
                'resumo_humano' => 'Grupo criado manualmente no editor visual.',
            ], '', 'manual');

            return $this->validarEGerarPreview($regra, $validator, $executor, $preview);
        } catch (Throwable $e) {
            report($e);

            return $this->jsonSegmentoErro('Erro ao gerar prévia manual.', $e);
        }
    }

    public function preview(Request $request, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $regra = json_decode($request->input('regra_json', '{}'), true);
            if (!is_array($regra)) {
                throw new \RuntimeException('JSON de regra inválido.');
            }
            return $this->validarEGerarPreview($regra, $validator, $executor, $preview);
        } catch (Throwable $e) {
            report($e);

            return $this->jsonSegmentoErro('Erro ao gerar prévia.', $e);
        }
    }

    public function refreshPreview(int $id, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $segmento = SegmentoCliente::findOrFail($id);
            $regra = $segmento->regra_json ?? [];

            $validator->validar($regra);
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);

            $segmento->ultima_previa_qtd = $previewData['total'] ?? 0;
            $segmento->ultima_previa_em = now();
            $segmento->save();

            SegmentoClienteExecucao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'canal' => 'preview',
                'regra_json_snapshot' => $regra,
                'sql_gerada_snapshot' => $exec['sql'],
                'total_encontrado' => $previewData['total'] ?? 0,
                'status' => ($previewData['ok'] ?? false) ? 'concluida' : 'erro',
                'erro' => $previewData['erro'] ?? null,
                'executado_em' => now(),
            ]);

            return redirect()->route('segmentos.show', $id)->with('ok', 'Prévia atualizada.');
        } catch (Throwable $e) {
            return redirect()->route('segmentos.show', $id)->with('erro', $e->getMessage());
        }
    }

    public function validar(int $id)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $statusAnterior = $segmento->status_validacao;
        $segmento->status_validacao = 'validada';
        $segmento->validado_em = now();
        $segmento->save();

        SegmentoClienteValidacao::create([
            'segmento_cliente_id' => $segmento->segmento_cliente_id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'validada',
            'regra_json_snapshot' => $segmento->regra_json,
            'resumo_humano_snapshot' => $segmento->resumo_humano,
            'observacao' => 'Segmento validado manualmente no painel.',
            'validado_por' => null,
        ]);

        return redirect()->route('segmentos.show', $id)->with('ok', 'Grupo validado com sucesso.');
    }

    public function reprovar(Request $request, int $id)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $statusAnterior = $segmento->status_validacao;
        $segmento->status_validacao = 'reprovada';
        $segmento->motivo_reprovacao = $request->input('motivo', 'Reprovado manualmente.');
        $segmento->save();

        SegmentoClienteValidacao::create([
            'segmento_cliente_id' => $segmento->segmento_cliente_id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'reprovada',
            'regra_json_snapshot' => $segmento->regra_json,
            'resumo_humano_snapshot' => $segmento->resumo_humano,
            'observacao' => $segmento->motivo_reprovacao,
            'validado_por' => null,
        ]);

        return redirect()->route('segmentos.show', $id)->with('ok', 'Grupo reprovado.');
    }

    public function store(Request $request, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $regra = json_decode($request->input('regra_json', '{}'), true);
            if (!is_array($regra)) {
                throw new \RuntimeException('JSON de regra inválido.');
            }

            $regra = SegmentoRuleHelper::enrichRule($regra);

            $validator->validar($regra);
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);

            $origem = $regra['origem'] ?? $request->input('origem', 'manual');

            $segmento = SegmentoCliente::create([
                'nome' => $request->input('nome', 'Novo segmento'),
                'descricao' => $request->input('descricao'),
                'tipo' => 'dinamico',
                'origem' => in_array($origem, ['ia','preset'], true) ? $origem : 'manual',
                'regra_json' => $regra,
                'resumo_humano' => $regra['resumo_humano'] ?? null,
                'status_validacao' => 'pendente_validacao',
                'limite' => $regra['limit'] ?? 25,
                'ordenacao' => $this->mapOrdenacao($regra['order']['field'] ?? 'random'),
                'ultima_previa_qtd' => $previewData['total'] ?? 0,
                'ultima_previa_em' => now(),
            ]);

            SegmentoClienteExecucao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'canal' => 'preview',
                'regra_json_snapshot' => $regra,
                'sql_gerada_snapshot' => $exec['sql'],
                'total_encontrado' => $previewData['total'] ?? 0,
                'status' => ($previewData['ok'] ?? false) ? 'concluida' : 'erro',
                'erro' => $previewData['erro'] ?? null,
                'executado_em' => now(),
            ]);

            SegmentoClienteValidacao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'status_anterior' => null,
                'status_novo' => $segmento->status_validacao,
                'regra_json_snapshot' => $regra,
                'resumo_humano_snapshot' => $segmento->resumo_humano,
                'observacao' => $origem === 'ia' ? 'Criado por IA e enviado para validação.' : 'Criado manualmente e validado automaticamente.',
            ]);

            return redirect()->route('segmentos.show', $segmento->segmento_cliente_id)
                ->with('ok', 'Segmento salvo! Status: Pendente — aguardando aprovação da equipe.');
        } catch (Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }


    public function edit(int $id)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $campos = SegmentoClienteCampo::where('ativo', 'S')->orderBy('ordem')->get();

        return view('segmentos.edit', compact('segmento', 'campos'));
    }

    public function update(Request $request, int $id, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $segmento = SegmentoCliente::findOrFail($id);
            $regra = json_decode($request->input('regra_json', '{}'), true);

            if (!is_array($regra)) {
                throw new \RuntimeException('JSON de regra inválido.');
            }

            $regra = SegmentoRuleHelper::enrichRule($regra);

            $validator->validar($regra);
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);

            $segmento->update([
                'nome' => $request->input('nome', $segmento->nome),
                'descricao' => $request->input('descricao'),
                'regra_json' => $regra,
                'resumo_humano' => $regra['resumo_humano'] ?? $segmento->resumo_humano,
                'limite' => $regra['limit'] ?? 25,
                'ordenacao' => $this->mapOrdenacao($regra['order']['field'] ?? 'random'),
                'ultima_previa_qtd' => $previewData['total'] ?? 0,
                'ultima_previa_em' => now(),
            ]);

            SegmentoClienteExecucao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'canal' => 'preview',
                'regra_json_snapshot' => $regra,
                'sql_gerada_snapshot' => $exec['sql'],
                'total_encontrado' => $previewData['total'] ?? 0,
                'status' => ($previewData['ok'] ?? false) ? 'concluida' : 'erro',
                'erro' => $previewData['erro'] ?? null,
                'executado_em' => now(),
            ]);

            return redirect()->route('segmentos.show', $id)->with('ok', 'Segmento atualizado com sucesso.');
        } catch (Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            $segmento = SegmentoCliente::findOrFail($id);
            $segmento->excluido = now();
            $segmento->status_validacao = 'inativa';
            $segmento->save();

            return redirect()->route('segmentos.index')->with('ok', 'Segmento excluído com sucesso.');
        } catch (Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }
    }


    public function exportar(Request $request, int $id, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        $segmento = SegmentoCliente::findOrFail($id);

        if (! SegmentadorUi::canExportSegment($segmento->status_validacao)) {
            return redirect()->route('segmentos.show', $id)->with('erro', 'Exportação indisponível para este status. Aguarde aprovação ou corrija o segmento.');
        }

        $tipo = (string) $request->query('tipo', 'csv');

        return match ($tipo) {
            'csv' => $this->exportarCsv($id, $executor, $preview),
            'telefones', 'emails' => $this->copiarContatos($id, $tipo, $executor, $preview),
            default => redirect()->route('segmentos.show', $id)->with('erro', 'Tipo de exportação inválido.'),
        };
    }

    public function exportarCsv(int $id, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $segmento = SegmentoCliente::findOrFail($id);
            $regra = $segmento->regra_json ?? [];
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);
            $rows = $previewData['exemplos'] ?? [];

            $filename = 'segmento_' . $segmento->segmento_cliente_id . '_' . now()->format('Ymd_His') . '.csv';

            SegmentoClienteExecucao::create([
                'segmento_cliente_id' => $segmento->segmento_cliente_id,
                'canal' => 'exportacao',
                'regra_json_snapshot' => $segmento->regra_json ?? [],
                'sql_gerada_snapshot' => $exec['sql'],
                'total_encontrado' => $previewData['total'] ?? 0,
                'total_processado' => count($rows),
                'status' => ($previewData['ok'] ?? false) ? 'concluida' : 'erro',
                'erro' => $previewData['erro'] ?? null,
                'executado_em' => now(),
            ]);

            return response()->streamDownload(function () use ($rows) {
                $out = fopen('php://output', 'w');
                $cols = ['cliente_id','cli_nome','cli_email','cli_telefone','sexo_id','cli_cidade','cli_bairro','cli_qtd_pedidos','cli_cashback','cli_pontos_totais'];
                fputcsv($out, $cols, ';');
                foreach ($rows as $row) {
                    fputcsv($out, array_map(fn ($col) => $row[$col] ?? '', $cols), ';');
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        } catch (Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }
    }

    public function copiarContatos(int $id, string $tipo, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        try {
            $segmento = SegmentoCliente::findOrFail($id);
            $regra = $segmento->regra_json ?? [];
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);
            $rows = $previewData['exemplos'] ?? [];

            $coluna = $tipo === 'emails' ? 'cli_email' : 'cli_telefone';
            $valores = collect($rows)
                ->pluck($coluna)
                ->filter(fn ($v) => trim((string)$v) !== '')
                ->unique()
                ->values()
                ->implode("
");

            return response($valores, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
        } catch (Throwable $e) {
            return response('Erro: ' . $e->getMessage(), 422, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }
    }

    private function validarEGerarPreview(array $regra, SegmentoRuleValidator $validator, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview, array $context = []): JsonResponse
    {
        try {
            $regra = SegmentoRuleHelper::enrichRule($regra);
            $validator->validar($regra);
            $exec = $executor->executar($regra, $context);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);

            return $this->jsonSegmentoOk([
                'message' => 'Prévia gerada com sucesso.',
                'regra' => $regra,
                'sql' => $exec['sql'],
                'bindings' => $exec['bindings'],
                'motor' => $exec['motor'],
                'logs' => $exec['logs'] ?? [],
                'preview' => $previewData,
                'total' => $previewData['total'] ?? 0,
                'clientes' => $previewData['exemplos'] ?? [],
                'resumo' => $previewData['resumo'] ?? (new SegmentoRuleExplainer())->resumoRegra($regra),
            ]);
        } catch (Throwable $e) {
            report($e);

            return $this->jsonSegmentoErro('Erro ao gerar prévia.', $e);
        }
    }

    private function jsonSegmentoOk(array $payload = [], int $status = 200): JsonResponse
    {
        return response()->json(array_merge([
            'ok' => true,
            'success' => true,
        ], $payload), $status, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    }

    private function jsonSegmentoErro(string $message, ?Throwable $e = null, int $status = 422): JsonResponse
    {
        $technical = $e?->getMessage() ?? $message;

        return response()->json([
            'ok' => false,
            'success' => false,
            'message' => $message,
            'erro' => config('app.debug') ? $technical : $message,
            'error' => config('app.debug') ? $technical : $message,
        ], $status, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    }

    public function campoOpcoes(Request $request)
    {
        $campo = (string) $request->input('campo', '');
        $q = trim((string) $request->input('q', ''));

        if ($campo === 'municipio') {
            $query = Cliente::query()->whereNull('excluido')->whereNotNull('cli_cidade')->where('cli_cidade', '!=', '');
            if ($q !== '') {
                $query->where('cli_cidade', 'like', '%' . $q . '%');
            }

            return response()->json(
                $query->distinct()->orderBy('cli_cidade')->limit(20)->pluck('cli_cidade')->values()
            );
        }

        if ($campo === 'bairro') {
            $query = Cliente::query()->whereNull('excluido')->whereNotNull('cli_bairro')->where('cli_bairro', '!=', '');
            if ($q !== '') {
                $query->where('cli_bairro', 'like', '%' . $q . '%');
            }

            return response()->json(
                $query->distinct()->orderBy('cli_bairro')->limit(20)->pluck('cli_bairro')->values()
            );
        }

        if ($campo === 'estado') {
            $ufs = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
            if ($q !== '') {
                $ufs = array_values(array_filter($ufs, fn ($uf) => str_starts_with(strtolower($uf), strtolower($q))));
            }

            return response()->json($ufs);
        }

        if ($campo === 'produto_comprado') {
            if (Schema::hasTable('produtos')) {
                $query = DB::table('produtos')->whereNotNull('nome')->where('nome', '!=', '');

                if (Schema::hasColumn('produtos', 'ativo')) {
                    $query->where('ativo', 'S');
                }

                if ($q !== '') {
                    $query->where('nome', 'like', '%' . $q . '%');
                }

                return response()->json(
                    $query->distinct()->orderBy('nome')->limit(50)->pluck('nome')->values()
                );
            }

            if (Schema::hasTable('produto')) {
                $nomeCol = Schema::hasColumn('produto', 'pro_nome') ? 'pro_nome' : 'nome';
                $query = DB::table('produto')->whereNotNull($nomeCol)->where($nomeCol, '!=', '');

                if (Schema::hasColumn('produto', 'excluido')) {
                    $query->whereNull('excluido');
                }

                if ($q !== '') {
                    $query->where($nomeCol, 'like', '%' . $q . '%');
                }

                return response()->json(
                    $query->distinct()->orderBy($nomeCol)->limit(50)->pluck($nomeCol)->values()
                );
            }

            return response()->json([]);
        }

        $listasFixas = [
            'sexo' => ['Masculino', 'Feminino'],
            'newsletter' => ['Sim', 'Não'],
            'funcionario' => ['Sim', 'Não'],
            'origem_contato' => ['WhatsApp', 'Instagram', 'iFood', 'Balcão', 'Site', 'Telefone'],
            'canal_pedido' => ['WhatsApp', 'iFood', 'Balcão', 'Delivery próprio', 'Site', 'Instagram'],
            'forma_pagamento' => ['Pix', 'Cartão de crédito', 'Cartão de débito', 'Dinheiro', 'Vale refeição'],
            'status_pedido' => ['Confirmado', 'Pendente', 'Cancelado', 'Entregue'],
        ];

        if (isset($listasFixas[$campo])) {
            $items = $listasFixas[$campo];
            if ($q !== '') {
                $items = array_values(array_filter($items, fn ($item) => str_contains(mb_strtolower($item), mb_strtolower($q))));
            }
            return response()->json($items);
        }

        return response()->json([]);
    }

    /**
     * @return array<int, array{logic: string, conditions: array<int, array<string, mixed>>}>
     */
    private function parseGroupsFromRequest(Request $request): array
    {
        $groups = [];
        $inputGroups = $request->input('groups');

        if (! is_array($inputGroups) && $request->isJson()) {
            $inputGroups = $request->json('groups', []);
        }

        foreach ($inputGroups ?? [] as $group) {
            if (!is_array($group)) {
                continue;
            }

            $conditions = [];

            foreach ($group['conditions'] ?? [] as $condition) {
                if (!is_array($condition) || empty($condition['field']) || empty($condition['operator'])) {
                    continue;
                }

                $conditions[] = [
                    'field' => $condition['field'],
                    'operator' => $condition['operator'],
                    'value' => $condition['value'] ?? null,
                ];
            }

            if ($conditions !== []) {
                $groups[] = [
                    'logic' => strtoupper((string) ($group['logic'] ?? 'AND')) === 'OR' ? 'OR' : 'AND',
                    'conditions' => $conditions,
                ];
            }
        }

        if ($groups !== []) {
            return $groups;
        }

        $conditions = [];

        foreach ($request->input('conditions', []) as $condition) {
            if (!is_array($condition) || empty($condition['field']) || empty($condition['operator'])) {
                continue;
            }

            $conditions[] = [
                'field' => $condition['field'],
                'operator' => $condition['operator'],
                'value' => $condition['value'] ?? null,
            ];
        }

        if ($conditions === []) {
            return [];
        }

        return [[
            'logic' => strtoupper((string) $request->input('logic', 'AND')) === 'OR' ? 'OR' : 'AND',
            'conditions' => $conditions,
        ]];
    }

    private function mapOrdenacao(string $field): string
    {
        return match ($field) {
            'mais_recentes', 'data_cadastro' => 'mais_recentes',
            'mais_antigos' => 'mais_antigos',
            'ultima_compra_desc' => 'ultima_compra_desc',
            'ultima_compra_asc' => 'ultima_compra_asc',
            default => 'aleatoria',
        };
    }
}
