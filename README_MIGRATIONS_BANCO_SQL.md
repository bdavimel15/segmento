# Migração do banco.sql para Laravel Migrations

Este pacote transforma o `banco.sql` em migrations/seeders Laravel.

## Arquivos incluídos

- `database/migrations/2026_06_04_000001_create_banco_sql_structure.php`
  - Cria as tabelas base e tabelas do motor de segmentação usando Schema Builder.
  - Usa `Schema::hasTable()` para não tentar recriar tabelas existentes.
  - Foi feito para funcionar melhor tanto em MySQL quanto em SQLite para testes.

- `database/seeders/SegmentoClienteCampoSeeder.php`
  - Popula o catálogo de campos permitidos.

- `database/seeders/SegmentoPresetSeeder.php`
  - Popula modelos prontos iniciais.

## Como usar

Extraia estes arquivos na raiz do projeto Laravel e rode:

```bash
composer dump-autoload
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan db:seed --class=SegmentoPresetSeeder
```

## Observação importante

O `banco.sql` enviado não possui uma tabela `produto`, apenas `pedido_item.produto_id`.
Se o projeto precisar segmentar por produtos, mantenha também a migration de `produto` que já foi criada anteriormente.
