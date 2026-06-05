<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\SegmentoCliente;
use App\Models\SegmentoClienteExecucao;
use App\Models\SegmentoClienteValidacao;
use App\Services\Segmentos\SegmentoPreviewService;
use App\Services\Segmentos\SegmentoQueryExecutor;
use App\Services\Segmentos\SegmentoRuleExplainer;
use App\Services\Segmentos\SegmentoRuleValidator;
use App\Support\SegmentadorUi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Throwable;

class SegmentadorAdminController extends Controller
{
    public function loginForm()
    {
        if (session('segmentador_admin')) {
            return redirect()->route('admin.index');
        }

        return view('admin.login');
    }

    public function login(Request $request)
    {
        $password = (string) $request->input('password', '');
        $expected = (string) config('segmentador.admin_password');

        if ($expected === '' || ! hash_equals($expected, $password)) {
            return back()->withInput()->with('erro', 'Senha administrativa incorreta.');
        }

        session(['segmentador_admin' => true]);

        return redirect()->route('admin.index')->with('ok', 'Área administrativa acessada.');
    }

    public function logout()
    {
        session()->forget('segmentador_admin');

        return redirect()->route('segmentos.create')->with('ok', 'Sessão administrativa encerrada.');
    }

    public function index(Request $request)
    {
        $filtro = $request->query('status', 'pendentes');

        $query = SegmentoCliente::whereNull('excluido')->orderByDesc('segmento_cliente_id');

        $segmentos = match ($filtro) {
            'aprovados' => $query->where('status_validacao', 'validada')->limit(100)->get(),
            'reprovados' => $query->where('status_validacao', 'reprovada')->limit(100)->get(),
            'analise' => $query->where('status_validacao', 'em_analise')->limit(100)->get(),
            default => $query->whereIn('status_validacao', ['pendente_validacao', 'rascunho', 'em_analise'])->limit(100)->get(),
        };

        return view('admin.index', compact('segmentos', 'filtro'));
    }

    public function show(int $id, SegmentoQueryExecutor $executor, SegmentoPreviewService $preview)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $validacoes = SegmentoClienteValidacao::where('segmento_cliente_id', $id)
            ->orderByDesc('segmento_cliente_validacao_id')
            ->limit(20)
            ->get();
        $execucoes = SegmentoClienteExecucao::where('segmento_cliente_id', $id)
            ->orderByDesc('segmento_cliente_execucao_id')
            ->limit(10)
            ->get();

        $previewData = ['ok' => false, 'total' => 0, 'exemplos' => []];
        $sqlData = ['sql' => null, 'bindings' => [], 'motor' => null];
        $logs = [];

        try {
            $regra = $segmento->regra_json ?? [];
            $exec = $executor->executar($regra);
            $previewData = $preview->previewFromRows($exec['rows'], $regra, $exec['motor']);
            $sqlData = ['sql' => $exec['sql'], 'bindings' => $exec['bindings'], 'motor' => $exec['motor']];
            $logs = $exec['logs'] ?? [];
        } catch (Throwable $e) {
            $previewData = ['ok' => false, 'total' => 0, 'exemplos' => [], 'erro' => $e->getMessage()];
        }

        $resumoRegra = $previewData['resumo'] ?? (new SegmentoRuleExplainer())->resumoRegra($segmento->regra_json ?? []);

        return view('admin.show', compact('segmento', 'previewData', 'sqlData', 'logs', 'validacoes', 'execucoes', 'resumoRegra'));
    }

    public function aprovar(int $id)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $statusAnterior = $segmento->status_validacao;
        $segmento->status_validacao = 'validada';
        $segmento->validado_em = now();
        $segmento->motivo_reprovacao = null;
        $segmento->save();

        SegmentoClienteValidacao::create([
            'segmento_cliente_id' => $segmento->segmento_cliente_id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'validada',
            'regra_json_snapshot' => $segmento->regra_json,
            'resumo_humano_snapshot' => $segmento->resumo_humano,
            'observacao' => 'Aprovado pela equipe interna.',
            'validado_por' => 'admin',
        ]);

        return redirect()->route('admin.show', $id)->with('ok', 'Segmento aprovado.');
    }

    public function reprovar(Request $request, int $id)
    {
        $request->validate(['motivo' => 'required|string|min:3|max:500']);

        $segmento = SegmentoCliente::findOrFail($id);
        $statusAnterior = $segmento->status_validacao;
        $segmento->status_validacao = 'reprovada';
        $segmento->motivo_reprovacao = $request->input('motivo');
        $segmento->save();

        SegmentoClienteValidacao::create([
            'segmento_cliente_id' => $segmento->segmento_cliente_id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'reprovada',
            'regra_json_snapshot' => $segmento->regra_json,
            'resumo_humano_snapshot' => $segmento->resumo_humano,
            'observacao' => $request->input('motivo'),
            'validado_por' => 'admin',
        ]);

        return redirect()->route('admin.show', $id)->with('ok', 'Segmento reprovado.');
    }

    public function emAnalise(int $id)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $statusAnterior = $segmento->status_validacao;
        $segmento->status_validacao = 'em_analise';
        $segmento->save();

        SegmentoClienteValidacao::create([
            'segmento_cliente_id' => $segmento->segmento_cliente_id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'em_analise',
            'regra_json_snapshot' => $segmento->regra_json,
            'resumo_humano_snapshot' => $segmento->resumo_humano,
            'observacao' => 'Marcado como em análise pela equipe interna.',
            'validado_por' => 'admin',
        ]);

        return redirect()->route('admin.show', $id)->with('ok', 'Segmento marcado como em análise.');
    }

    public function destroy(int $id)
    {
        $segmento = SegmentoCliente::findOrFail($id);
        $segmento->excluido = now();
        $segmento->status_validacao = 'inativa';
        $segmento->save();

        return redirect()->route('admin.index')->with('ok', 'Segmento excluído.');
    }

    public function clientes()
    {
        $clientes = Cliente::whereNull('excluido')->orderByDesc('cliente_id')->limit(100)->get();

        return view('admin.clientes', compact('clientes'));
    }

    public function logs()
    {
        $path = storage_path('logs/laravel.log');
        $tail = File::exists($path) ? collect(explode("\n", File::get($path)))->take(-120)->implode("\n") : 'Nenhum log encontrado.';

        return view('admin.logs', compact('tail'));
    }
}
