<?php

namespace Database\Seeders;

use App\Models\SegmentoClienteCampo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SegmentoClienteCampoSeeder extends Seeder
{
    public function run(): void
    {
        $campos = [
            ['chave'=>'nome','label'=>'Nome','descricao'=>'Nome do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_nome','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>1],
            ['chave'=>'cpf','label'=>'CPF','descricao'=>'CPF cadastrado do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_cpf','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>2],
            ['chave'=>'sexo','label'=>'Sexo','descricao'=>'Sexo do cliente. Use Masculino ou Feminino.','categoria'=>'cliente','tipo_valor'=>'select','opcoes'=>['Masculino','Feminino'],'expressao_sql'=>"CASE WHEN c.sexo_id IN ('M','Masculino','masculino') THEN 'Masculino' WHEN c.sexo_id IN ('F','Feminino','feminino') THEN 'Feminino' ELSE c.sexo_id END",'operadores_json'=>['equals','not_equals','is_empty','is_not_empty'],'ordem'=>3],
            ['chave'=>'telefone','label'=>'Telefone','descricao'=>'Telefone do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_telefone','operadores_json'=>['equals','not_equals','contains','not_contains','is_empty','is_not_empty'],'ordem'=>4],
            ['chave'=>'email','label'=>'E-mail','descricao'=>'E-mail do cliente.','categoria'=>'cliente','tipo_valor'=>'string','expressao_sql'=>'c.cli_email','operadores_json'=>['equals','not_equals','contains','not_contains','ends_with','is_empty','is_not_empty'],'ordem'=>5],
            ['chave'=>'nascimento','label'=>'Nascimento','descricao'=>'Data de nascimento do cliente.','categoria'=>'cliente','tipo_valor'=>'date','expressao_sql'=>'c.cli_data_nascimento','operadores_json'=>['today','yesterday','next_x_days','equals_date','before_date','after_date'],'ordem'=>6],
            ['chave'=>'idade','label'=>'Idade','descricao'=>'Idade calculada pela data de nascimento.','categoria'=>'cliente','tipo_valor'=>'number','expressao_sql'=>'TIMESTAMPDIFF(YEAR, c.cli_data_nascimento, CURDATE())','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal','between'],'ordem'=>7],
            ['chave'=>'bairro','label'=>'Bairro','descricao'=>'Bairro cadastrado do cliente.','categoria'=>'endereco','tipo_valor'=>'string','expressao_sql'=>'c.cli_bairro','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>8],
            ['chave'=>'municipio','label'=>'Município','descricao'=>'Município/cidade cadastrado do cliente.','categoria'=>'endereco','tipo_valor'=>'string','expressao_sql'=>'c.cli_cidade','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>9],
            ['chave'=>'estado','label'=>'Estado (UF)','descricao'=>'UF do cliente, como BA, SP ou RJ.','categoria'=>'endereco','tipo_valor'=>'string','expressao_sql'=>'c.cli_estado','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>10],
            ['chave'=>'funcionario','label'=>'Funcionário(a)','descricao'=>'Indica se o cliente é funcionário.','categoria'=>'cliente','tipo_valor'=>'select','opcoes'=>['SIM','NÃO'],'expressao_sql'=>"CASE WHEN c.cli_funcionario IN ('S','SIM','Sim','sim','1') THEN 'SIM' ELSE 'NÃO' END",'operadores_json'=>['is_true','is_false','equals','not_equals'],'ordem'=>11],
            ['chave'=>'newsletter','label'=>'Newsletter','descricao'=>'Indica se o cliente aceita receber comunicações.','categoria'=>'cliente','tipo_valor'=>'select','opcoes'=>['SIM','NÃO'],'expressao_sql'=>"CASE WHEN c.cli_newsletter IN ('S','SIM','Sim','sim','1') THEN 'SIM' ELSE 'NÃO' END",'operadores_json'=>['is_true','is_false','equals','not_equals'],'ordem'=>11],
            ['chave'=>'qtd_pedidos','label'=>'Qtd. de pedidos','descricao'=>'Quantidade de pedidos confirmados do cliente.','categoria'=>'pedido','tipo_valor'=>'number','expressao_sql'=>'ps.qtd_pedidos','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>12],
            ['chave'=>'ultimo_pedido','label'=>'Último pedido','descricao'=>'Data do último pedido confirmado do cliente.','categoria'=>'pedido','tipo_valor'=>'datetime','expressao_sql'=>'ps.ultimo_pedido','operadores_json'=>['today','yesterday','more_than_x_days_ago','less_than_x_days_ago','last_x_days','exactly_x_days_ago','before_date','after_date','equals_date'],'ordem'=>13],
            ['chave'=>'cashback','label'=>'Cashback','descricao'=>'Saldo de cashback do cliente.','categoria'=>'cashback','tipo_valor'=>'money','expressao_sql'=>'cb.cashback','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>14],
            ['chave'=>'pontos_totais','label'=>'Pontos totais','descricao'=>'Total de pontos do cliente.','categoria'=>'cliente','tipo_valor'=>'number','expressao_sql'=>'c.cli_pontos_totais','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>15],
            ['chave'=>'data_cadastro','label'=>'Data de cadastro','descricao'=>'Data em que o cliente foi cadastrado.','categoria'=>'cliente','tipo_valor'=>'datetime','expressao_sql'=>'c.cadastrado','operadores_json'=>['today','yesterday','last_x_days','more_than_x_days_ago','less_than_x_days_ago','before_date','after_date','equals_date'],'ordem'=>16],
            ['chave'=>'primeira_compra','label'=>'Primeira compra','descricao'=>'Data da primeira compra confirmada do cliente.','categoria'=>'pedido','tipo_valor'=>'datetime','expressao_sql'=>'ps.primeira_compra','operadores_json'=>['today','yesterday','last_x_days','more_than_x_days_ago','before_date','after_date','equals_date'],'ordem'=>17],
            ['chave'=>'valor_total_comprado','label'=>'Valor total comprado','descricao'=>'Soma do valor dos pedidos confirmados.','categoria'=>'pedido','tipo_valor'=>'money','expressao_sql'=>'ps.valor_total_comprado','operadores_json'=>['equals','not_equals','greater_than','greater_or_equal','less_than','less_or_equal'],'ordem'=>18],
            ['chave'=>'cashback_expira_em','label'=>'Cashback expira em','descricao'=>'Data estimada de expiração do cashback.','categoria'=>'cashback','tipo_valor'=>'datetime','expressao_sql'=>'cb.cashback_expira_em','operadores_json'=>['next_x_days','before_date','after_date','equals_date'],'ordem'=>19],
            ['chave'=>'produto_comprado','label'=>'Produto comprado','descricao'=>'Produto comprado em algum pedido confirmado.','categoria'=>'produto','tipo_valor'=>'select','opcoes'=>$this->produtoOptions(),'expressao_sql'=>'prod.produtos_comprados','operadores_json'=>['equals','not_equals','contains','not_contains','starts_with','ends_with','is_empty','is_not_empty'],'ordem'=>20],
            ['chave'=>'carrinho_abandonado','label'=>'Carrinho abandonado','descricao'=>'Cliente presente na view de carrinho abandonado.','categoria'=>'carrinho','tipo_valor'=>'boolean','expressao_sql'=>'vca.cliente_id','operadores_json'=>['is_true','is_false','exists','not_exists'],'ordem'=>20],
        ];

        $chavesAtivas = array_column($campos, 'chave');

        foreach ($campos as $campo) {
            $campo = $this->normalizeCampoPayload($campo);

            SegmentoClienteCampo::updateOrCreate(
                ['chave' => $campo['chave']],
                $campo + ['ativo' => 'S']
            );
        }

        SegmentoClienteCampo::whereNotIn('chave', $chavesAtivas)->update(['ativo' => 'N']);
    }

    private function normalizeCampoPayload(array $campo): array
    {
        if (isset($campo['opcoes'])) {
            $campo['opcoes_json'] = is_array($campo['opcoes']) ? $campo['opcoes'] : $campo['opcoes'];
            unset($campo['opcoes']);
        }

        foreach ($campo as $key => $value) {
            // operadores_json e opcoes_json: arrays puros — o cast do Eloquent serializa
            if (in_array($key, ['operadores_json', 'opcoes_json'], true)) {
                continue;
            }
            if (is_array($value)) {
                $campo[$key] = json_encode($value, JSON_UNESCAPED_UNICODE);
            }
        }

        return $campo;
    }


    private function produtoOptions(): array
    {
        if (Schema::hasTable('produtos')) {
            $produtos = DB::table('produtos')
                ->where('ativo', 'S')
                ->orderBy('nome')
                ->pluck('nome')
                ->filter()
                ->values()
                ->all();

            if (! empty($produtos)) {
                return $produtos;
            }
        }

        return [
            'Pizza Calabresa',
            'Pizza Portuguesa',
            'Pizza Frango com Catupiry',
            'Pizza Marguerita',
            'Picanha',
            'Picanha Premium',
            'Costela',
            'Frango Assado',
            'Hambúrguer Artesanal',
            'X-Bacon',
            'X-Tudo',
            'Lasanha',
            'Parmegiana',
            'Açaí 500ml',
            'Sorvete',
            'Coca-Cola',
            'Guaraná',
            'Suco de Laranja',
            'Água Mineral',
        ];
    }

}
