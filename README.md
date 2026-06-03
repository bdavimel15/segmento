# Segmentador de Clientes Laravel + Zaia

Motor seguro de grupos/segmentos baseado em:

`texto do usuário -> Zaia -> regra JSON -> validação Laravel -> SQL segura -> prévia -> salvar`

## Instalação rápida em projeto Laravel existente

Copie tudo de `_laravel_files/` para a raiz do seu projeto Laravel.

Depois rode:

```bash
composer dump-autoload
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan serve
```

No `.env`, adicione:

```env
ZAIA_WEBHOOK_URL=https://SEU-WEBHOOK-DA-ZAIA
```

Acesse:

```text
http://127.0.0.1:8000/segmentos
```

## O que foi implementado

- Criar grupo com IA via webhook da Zaia
- Criar grupo manualmente pelo editor visual
- Catálogo de campos permitidos
- Validador de regra
- SQL Builder seguro
- Preview com DB::select
- Salvamento do JSON como regra oficial
- SQL salva apenas em execução/auditoria
- Histórico básico em segmento_cliente_execucao
- Histórico básico em segmento_cliente_validacao

## Formato aceito da Zaia

A Zaia pode retornar assim:

```json
{
  "version": 1,
  "entity": "cliente",
  "logic": "AND",
  "conditions": "ultima_compra more_than_x_days_ago 30",
  "limit": 25,
  "order": "random asc"
}
```

O Laravel normaliza isso para o JSON oficial:

```json
{
  "version": 1,
  "entity": "cliente",
  "logic": "AND",
  "conditions": [
    {
      "field": "ultima_compra",
      "operator": "more_than_x_days_ago",
      "value": 30
    }
  ],
  "limit": 25,
  "order": {
    "field": "random",
    "direction": "asc"
  }
}
```

## Observação importante

A SQL gerada considera nomes comuns do banco, mas pode precisar de ajuste fino nos campos reais:

- `cliente_id`
- `cliente.nome`
- `cliente.cadastrado`
- `cliente.excluido`
- `pedido.cliente_id`
- `pedido.status_id`
- `status.status_id`
- `status.sta_confirmado`

Se seu banco tiver nomes diferentes, ajuste o `SegmentoSqlBuilder.php` e o `SegmentoClienteCampoSeeder.php`.
