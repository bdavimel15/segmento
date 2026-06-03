# Troubleshooting

## Class Illuminate not found
Rode `composer install` para recriar a pasta `vendor`.

## Prévia sempre 0
Rode migrations e seeders. Verifique se o campo gerado no JSON existe no catálogo.

## Zaia retorna conditions vazio
Confirme se o workflow lê `body.content`.

## Variáveis de mensagem quebrando Blade
Use `@{{primeiro_nome}}` na interface para exibir chaves sem o Blade interpretar.
