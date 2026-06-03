# Update: Produto conectado ao Cliente/Pedido

Esta atualização conecta o fluxo:

cliente → pedido → pedido_item → produto

Assim o motor consegue responder segmentações como:

- Clientes que compraram picanha
- Clientes que compraram pizza
- Clientes que compraram hambúrguer
- Clientes que compraram produto contendo coca

## O que foi alterado

- Criada/garantida migration das tabelas `produto` e `pedido_item`.
- Adicionados models Eloquent: `Pedido`, `PedidoItem`, `Produto`.
- Adicionados relacionamentos no model `Cliente`.
- O `SegmentoSqlBuilder` agora faz join seguro com pedidos confirmados e produtos.
- O campo `produto_comprado` entrou no catálogo seguro.
- O mock seeder agora cria produtos e conecta cada pedido aos produtos via `pedido_item`.
- A IA passa a normalizar `produto`, `produto_nome`, `item` e similares para `produto_comprado`.

## Comandos após aplicar

```bash
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan db:seed --class=MockClienteSeeder
php artisan view:clear
php artisan serve
```

## Exemplo de regra esperada

```json
{
  "field": "produto_comprado",
  "operator": "contains",
  "value": "picanha"
}
```

## SQL gerada em alto nível

O sistema cruza:

- `cliente c`
- `pedido p`
- `status s`
- `pedido_item pi`
- `produto pr`

Apenas pedidos confirmados entram na consulta:

```sql
p.excluido IS NULL
AND s.sta_confirmado = 'S'
```
