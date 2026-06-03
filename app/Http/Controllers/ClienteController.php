<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;
use ZipArchive;

class ClienteController extends Controller
{
    private array $colunas = [
        'cli_nome', 'cli_cpf', 'sexo_id', 'cli_telefone', 'cli_email', 'cli_data_nascimento',
        'cli_bairro', 'cli_cidade', 'cli_estado', 'cli_qtd_pedidos', 'cli_newsletter',
        'cli_funcionario', 'cli_pontos_totais', 'cli_proxima_compra', 'cliente_origem_id',
        'excluido', 'cadastrado', 'atualizado',
    ];

    public function index(Request $request)
    {
        $busca = trim((string) $request->input('busca', ''));
        $existentes = $this->colunasExistentes();

        $clientes = Cliente::query()
            ->select('cliente.*')
            ->whereNull('excluido')
            ->when($busca !== '', function ($query) use ($busca, $existentes) {
                $like = '%' . $busca . '%';
                $camposBusca = array_values(array_intersect([
                    'cli_nome', 'cli_email', 'cli_telefone', 'cli_cpf', 'cli_bairro', 'cli_cidade', 'sexo_id'
                ], $existentes));

                if ($camposBusca) {
                    $query->where(function ($q) use ($camposBusca, $like) {
                        foreach ($camposBusca as $campo) {
                            $q->orWhere($campo, 'like', $like);
                        }
                    });
                }
            })
            ->orderByDesc('cliente_id')
            ->paginate(25)
            ->withQueryString();

        $base = Cliente::whereNull('excluido');
        $metricas = [
            'total' => (clone $base)->count(),
            'newsletter' => $this->temColuna('cli_newsletter') ? (clone $base)->where('cli_newsletter', 'S')->count() : 0,
            'com_email' => $this->temColuna('cli_email') ? (clone $base)->whereNotNull('cli_email')->where('cli_email', '<>', '')->count() : 0,
            'com_telefone' => $this->temColuna('cli_telefone') ? (clone $base)->whereNotNull('cli_telefone')->where('cli_telefone', '<>', '')->count() : 0,
        ];

        return view('clientes.index', compact('clientes', 'busca', 'metricas', 'existentes'));
    }

    public function create()
    {
        $cliente = new Cliente();
        $existentes = $this->colunasExistentes();
        return view('clientes.create', compact('cliente', 'existentes'));
    }

    public function store(Request $request)
    {
        try {
            Cliente::create($this->filtrarColunasExistentes($this->validarDados($request)));
            return redirect()->route('clientes.index')->with('ok', 'Cliente criado com sucesso.');
        } catch (Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        $cliente = Cliente::whereNull('excluido')->findOrFail($id);
        $existentes = $this->colunasExistentes();
        return view('clientes.edit', compact('cliente', 'existentes'));
    }

    public function update(Request $request, int $id)
    {
        try {
            $cliente = Cliente::whereNull('excluido')->findOrFail($id);
            $cliente->update($this->filtrarColunasExistentes($this->validarDados($request)));
            return redirect()->route('clientes.index')->with('ok', 'Cliente atualizado com sucesso.');
        } catch (Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function destroy(int $id)
    {
        try {
            $cliente = Cliente::whereNull('excluido')->findOrFail($id);
            $cliente->excluido = now();
            $cliente->save();

            return redirect()->route('clientes.index')->with('ok', 'Cliente excluído com sucesso.');
        } catch (Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }
    }

    public function importForm()
    {
        return view('clientes.importar');
    }

    public function import(Request $request)
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:10240'],
            'modo' => ['nullable', 'in:criar,atualizar'],
        ]);

        try {
            $arquivo = $request->file('arquivo');
            $ext = strtolower($arquivo->getClientOriginalExtension());
            $linhas = $ext === 'xlsx'
                ? $this->lerXlsx($arquivo->getPathname())
                : $this->lerCsv($arquivo->getPathname());

            if (count($linhas) < 2) {
                throw new \RuntimeException('Arquivo sem dados para importar.');
            }

            $cabecalho = array_map(fn ($v) => $this->normalizarCabecalho((string)$v), array_shift($linhas));
            $modo = $request->input('modo', 'atualizar');
            $stats = ['criados' => 0, 'atualizados' => 0, 'ignorados' => 0, 'erros' => []];

            foreach ($linhas as $idx => $linha) {
                if ($this->linhaVazia($linha)) {
                    continue;
                }

                $dadosOriginais = [];
                foreach ($cabecalho as $i => $chave) {
                    if ($chave !== '') {
                        $dadosOriginais[$chave] = trim((string)($linha[$i] ?? ''));
                    }
                }

                $dados = $this->filtrarColunasExistentes($this->mapearLinhaImportacao($dadosOriginais));
                if (($dados['cli_nome'] ?? '') === '' && ($dados['cli_email'] ?? '') === '' && ($dados['cli_telefone'] ?? '') === '') {
                    $stats['ignorados']++;
                    $stats['erros'][] = 'Linha ' . ($idx + 2) . ': sem nome, e-mail ou telefone.';
                    continue;
                }

                $existente = $this->buscarClienteExistente($dados);

                if ($existente && $modo === 'atualizar') {
                    $existente->update($dados);
                    $stats['atualizados']++;
                    continue;
                }

                if ($existente) {
                    $stats['ignorados']++;
                    continue;
                }

                Cliente::create($dados);
                $stats['criados']++;
            }

            $msg = "Importação concluída: {$stats['criados']} criados, {$stats['atualizados']} atualizados, {$stats['ignorados']} ignorados.";
            return redirect()->route('clientes.index')->with('ok', $msg)->with('import_stats', $stats);
        } catch (Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }
    }

    public function exportCsv(Request $request)
    {
        $existentes = $this->colunasExistentes();
        $clientes = Cliente::whereNull('excluido')->orderBy('cliente_id')->get();
        $filename = 'clientes_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ];

        return response()->streamDownload(function () use ($clientes, $existentes) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            $cabecalho = ['id', 'nome', 'cpf', 'sexo', 'telefone', 'email', 'nascimento', 'idade', 'qtd_pedidos', 'newsletter', 'proxima_compra', 'origem', 'cadastrado'];
            if (in_array('cli_bairro', $existentes, true)) $cabecalho[] = 'bairro';
            if (in_array('cli_cidade', $existentes, true)) $cabecalho[] = 'municipio';
            if (in_array('cli_funcionario', $existentes, true)) $cabecalho[] = 'funcionario';
            if (in_array('cli_pontos_totais', $existentes, true)) $cabecalho[] = 'pontos_totais';
            fputcsv($out, $cabecalho, ';');

            foreach ($clientes as $c) {
                $linha = [
                    $c->cliente_id,
                    $c->cli_nome,
                    $c->cli_cpf,
                    $c->sexo_texto,
                    $c->cli_telefone,
                    $c->cli_email,
                    $this->formatarData($c->cli_data_nascimento, 'Y-m-d'),
                    $c->idade,
                    $c->cli_qtd_pedidos ?? 0,
                    $c->cli_newsletter,
                    $this->formatarData($c->cli_proxima_compra, 'Y-m-d H:i:s'),
                    $c->cliente_origem_id,
                    $this->formatarData($c->cadastrado, 'Y-m-d H:i:s'),
                ];
                if (in_array('cli_bairro', $existentes, true)) $linha[] = $c->cli_bairro;
                if (in_array('cli_cidade', $existentes, true)) $linha[] = $c->cli_cidade;
                if (in_array('cli_funcionario', $existentes, true)) $linha[] = $c->cli_funcionario;
                if (in_array('cli_pontos_totais', $existentes, true)) $linha[] = $c->cli_pontos_totais;
                fputcsv($out, $linha, ';');
            }
            fclose($out);
        }, $filename, $headers);
    }

    private function validarDados(Request $request): array
    {
        $dados = $request->validate([
            'cli_nome' => ['required', 'string', 'max:255'],
            'cli_cpf' => ['nullable', 'string', 'max:20'],
            'sexo_id' => ['nullable', 'string', 'max:20'],
            'cli_telefone' => ['nullable', 'string', 'max:30'],
            'cli_email' => ['nullable', 'email', 'max:100'],
            'cli_data_nascimento' => ['nullable', 'date'],
            'cli_bairro' => ['nullable', 'string', 'max:120'],
            'cli_cidade' => ['nullable', 'string', 'max:120'],
            'cli_estado' => ['nullable', 'string', 'max:2'],
            'cli_qtd_pedidos' => ['nullable', 'integer', 'min:0'],
            'cli_newsletter' => ['nullable', 'in:S,N'],
            'cli_funcionario' => ['nullable', 'in:S,N'],
            'cli_pontos_totais' => ['nullable', 'integer', 'min:0'],
            'cli_proxima_compra' => ['nullable', 'date'],
            'cliente_origem_id' => ['nullable', 'integer'],
        ]);

        return $this->normalizarDadosCliente($dados);
    }

    private function normalizarDadosCliente(array $dados): array
    {
        $dados['cli_nome'] = trim((string)($dados['cli_nome'] ?? 'Cliente sem nome'));
        $dados['cli_telefone'] = trim((string)($dados['cli_telefone'] ?? ''));
        $dados['cli_email'] = trim((string)($dados['cli_email'] ?? '')) ?: null;
        $dados['cli_cpf'] = trim((string)($dados['cli_cpf'] ?? '')) ?: null;
        $dados['cli_qtd_pedidos'] = (int) ($dados['cli_qtd_pedidos'] ?? 0);
        $dados['cli_pontos_totais'] = (int) ($dados['cli_pontos_totais'] ?? 0);
        $dados['cli_newsletter'] = $this->normalizarSimNao($dados['cli_newsletter'] ?? 'S');
        $dados['cli_funcionario'] = $this->normalizarSimNao($dados['cli_funcionario'] ?? 'N');
        $dados['sexo_id'] = $this->normalizarSexo($dados['sexo_id'] ?? null);

        return array_filter($dados, fn ($v) => $v !== '');
    }

    private function normalizarSexo(?string $sexo): ?string
    {
        if ($sexo === null || trim($sexo) === '') {
            return null;
        }

        $valor = mb_strtolower(trim($sexo));
        return match ($valor) {
            'm', '1', 'masculino', 'homem', 'homens' => 'M',
            'f', '2', 'feminino', 'mulher', 'mulheres' => 'F',
            default => trim($sexo),
        };
    }

    private function normalizarSimNao(?string $valor): string
    {
        $v = mb_strtolower(trim((string)$valor));
        return in_array($v, ['s', 'sim', '1', 'true', 'yes', 'y'], true) ? 'S' : 'N';
    }

    private function lerCsv(string $path): array
    {
        $linhas = [];
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new \RuntimeException('Não foi possível ler o CSV.');
        }

        $primeira = fgets($handle) ?: '';
        $delimitador = substr_count($primeira, ';') >= substr_count($primeira, ',') ? ';' : ',';
        rewind($handle);

        while (($data = fgetcsv($handle, 0, $delimitador)) !== false) {
            $linhas[] = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $data);
        }
        fclose($handle);

        return $linhas;
    }

    private function lerXlsx(string $path): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('Extensão ZipArchive não disponível. Use CSV ou habilite zip no PHP.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Não foi possível abrir XLSX. Use CSV se persistir.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            preg_match_all('/<t[^>]*>(.*?)<\/t>/s', $sharedXml, $matches);
            foreach ($matches[1] ?? [] as $texto) {
                $sharedStrings[] = html_entity_decode(strip_tags($texto), ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
        }

        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();
        if ($sheet === false) {
            throw new \RuntimeException('Planilha principal não encontrada no XLSX.');
        }

        $rows = [];
        preg_match_all('/<row[^>]*>(.*?)<\/row>/s', $sheet, $rowMatches);
        foreach ($rowMatches[1] ?? [] as $rowXml) {
            $row = [];
            preg_match_all('/<c([^>]*)>(.*?)<\/c>/s', $rowXml, $cellMatches, PREG_SET_ORDER);
            foreach ($cellMatches as $cell) {
                $attrs = $cell[1] ?? '';
                $body = $cell[2] ?? '';
                preg_match('/<v>(.*?)<\/v>/s', $body, $vMatch);
                $value = $vMatch[1] ?? '';
                if (str_contains($attrs, 't="s"') && $value !== '') {
                    $value = $sharedStrings[(int)$value] ?? $value;
                }
                $row[] = html_entity_decode((string)$value, ENT_QUOTES | ENT_XML1, 'UTF-8');
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function formatarData($valor, string $formato): string
    {
        if (empty($valor)) {
            return '';
        }

        try {
            return $valor instanceof \DateTimeInterface
                ? $valor->format($formato)
                : Carbon::parse($valor)->format($formato);
        } catch (Throwable $e) {
            return (string) $valor;
        }
    }

    private function normalizarCabecalho(string $valor): string
    {
        $v = mb_strtolower(trim($valor));
        $v = str_replace([' ', '-', '.', '/'], '_', $v);
        $map = [
            'nome' => 'nome', 'cliente' => 'nome',
            'cpf' => 'cpf', 'sexo' => 'sexo', 'genero' => 'sexo',
            'telefone' => 'telefone', 'celular' => 'telefone',
            'email' => 'email', 'e_mail' => 'email',
            'nascimento' => 'nascimento', 'data_nascimento' => 'nascimento', 'data_de_nascimento' => 'nascimento',
            'bairro' => 'bairro', 'cidade' => 'municipio', 'municipio' => 'municipio',
            'qtd_pedidos' => 'qtd_pedidos', 'quantidade_de_pedidos' => 'qtd_pedidos',
            'newsletter' => 'newsletter', 'funcionario' => 'funcionario',
            'pontos' => 'pontos_totais', 'pontos_totais' => 'pontos_totais',
            'proxima_compra' => 'proxima_compra', 'origem' => 'cliente_origem_id',
        ];
        return $map[$v] ?? $v;
    }

    private function mapearLinhaImportacao(array $dados): array
    {
        return $this->normalizarDadosCliente([
            'cli_nome' => $dados['nome'] ?? null,
            'cli_cpf' => $dados['cpf'] ?? null,
            'sexo_id' => $dados['sexo'] ?? null,
            'cli_telefone' => $dados['telefone'] ?? null,
            'cli_email' => $dados['email'] ?? null,
            'cli_data_nascimento' => $dados['nascimento'] ?? null,
            'cli_bairro' => $dados['bairro'] ?? null,
            'cli_cidade' => $dados['municipio'] ?? null,
            'cli_qtd_pedidos' => $dados['qtd_pedidos'] ?? 0,
            'cli_newsletter' => $dados['newsletter'] ?? 'S',
            'cli_funcionario' => $dados['funcionario'] ?? 'N',
            'cli_pontos_totais' => $dados['pontos_totais'] ?? 0,
            'cli_proxima_compra' => $dados['proxima_compra'] ?? null,
            'cliente_origem_id' => $dados['cliente_origem_id'] ?? null,
        ]);
    }

    private function buscarClienteExistente(array $dados): ?Cliente
    {
        $chaves = [];
        foreach (['cli_email', 'cli_telefone', 'cli_cpf'] as $campo) {
            if (!empty($dados[$campo]) && $this->temColuna($campo)) {
                $chaves[$campo] = $dados[$campo];
            }
        }

        if (!$chaves) {
            return null;
        }

        return Cliente::whereNull('excluido')
            ->where(function ($query) use ($chaves) {
                foreach ($chaves as $campo => $valor) {
                    $query->orWhere($campo, $valor);
                }
            })
            ->first();
    }

    private function linhaVazia(array $linha): bool
    {
        foreach ($linha as $valor) {
            if (trim((string)$valor) !== '') {
                return false;
            }
        }
        return true;
    }

    private function colunasExistentes(): array
    {
        return array_values(array_filter($this->colunas, fn ($col) => $this->temColuna($col)));
    }

    private function temColuna(string $coluna): bool
    {
        static $cache = [];
        if (!array_key_exists($coluna, $cache)) {
            $cache[$coluna] = Schema::hasColumn('cliente', $coluna);
        }
        return $cache[$coluna];
    }

    private function filtrarColunasExistentes(array $dados): array
    {
        $permitidas = array_flip($this->colunasExistentes());
        return array_intersect_key($dados, $permitidas);
    }
}
