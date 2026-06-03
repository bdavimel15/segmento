# Como rodar

```bash
composer install
php artisan key:generate
php artisan optimize:clear
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan db:seed --class=MockClienteSeeder
php artisan serve
```

No `.env`, configure:

```env
ZAIA_WEBHOOK_URL=https://seu-webhook-da-zaia
```
