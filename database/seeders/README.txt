Substitua seu SegmentoClienteCampoSeeder pela versão corrigida que salva opcoes apenas quando a coluna existir.
Depois execute:
php artisan migrate --force
php artisan db:seed --class=SegmentoClienteCampoSeeder --force
