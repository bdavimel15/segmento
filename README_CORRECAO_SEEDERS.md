# Correção dos seeders

Corrigido:
- SegmentoPresetSeeder agora grava `ativo = 'S'` em vez de `ativo = 1`.
- Isso resolve o erro SQLite/Railway: CHECK constraint failed: ativo.

Depois de copiar os arquivos, rode:

php artisan optimize:clear
php artisan db:seed --class=SegmentoPresetSeeder --force

Para rodar todos:

php artisan db:seed --force

Se quiser recriar do zero em SQLite:

php artisan migrate:fresh --seed --force
