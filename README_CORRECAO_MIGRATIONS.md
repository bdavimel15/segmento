# Correção de migrations duplicadas

Este update torna as migrations principais idempotentes.

Problema corrigido:
- `Table 'segmento_cliente' already exists`

Causa:
- A tabela já existia no banco, mas o Laravel ainda tentou executar a migration de criação.

Depois de extrair este ZIP na raiz do projeto, rode:

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan db:seed --class=MockClienteSeeder
php artisan serve
```

Se quiser recomeçar do zero apagando todos os dados, use:

```bash
php artisan migrate:fresh --seed
```

Atenção: `migrate:fresh` apaga as tabelas e dados.
