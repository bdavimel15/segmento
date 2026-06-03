# Zaia

A Zaia deve receber a mensagem no campo `content` e responder JSON no formato:

```json
{
  "version": 1,
  "entity": "cliente",
  "logic": "AND",
  "conditions": "campo operador valor",
  "limit": 25,
  "order": "random asc"
}
```

Exemplo: `Clientes que compraram hoje` → `ultimo_pedido today`.
