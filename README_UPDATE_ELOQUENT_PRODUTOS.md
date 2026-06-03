# Update — Eloquent Query Builder + Produtos

## O que foi alterado

- O `SegmentoSqlBuilder` agora monta a consulta usando `Cliente::query()` / Eloquent Query Builder e continua retornando SQL + bindings para auditoria.
- Adicionadas tabelas via migration:
  - `produto`
  - `pedido_item`
- Adicionados models:
  - `Produto`
  - `Pedido`
  - `PedidoItem`
- `Cliente` agora tem relacionamento `pedidos()`.
- O mock seeder agora cria produtos e conecta pedidos aos produtos por `pedido_item`.
- Campo `produto_comprado` foi adicionado ao catálogo seguro.
- A IA/normalizador agora reconhece produto/produtos/item/itens como `produto_comprado`.
- Corrigido visual do editor manual para não mostrar “Grupo 1” duas vezes.

## Exemplo de uso

Frases que passam a funcionar melhor:

- Clientes que compraram picanha
- Clientes que compraram pizza
- Clientes que compraram hambúrguer
- Top 4 clientes com mais pedidos

## Comandos após aplicar

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan db:seed --class=MockClienteSeeder
php artisan view:clear
php artisan serve
```

## Validação feita

- `php -l` nos arquivos PHP alterados.
- `php artisan route:list` executado com sucesso.
