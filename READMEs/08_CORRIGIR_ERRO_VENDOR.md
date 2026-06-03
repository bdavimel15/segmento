# Corrigir erro `Class "Illuminate\Foundation\Application" not found`

Esse erro acontece quando o Laravel está sem a pasta `vendor` ou sem o arquivo:

```txt
vendor/autoload.php
```

Isso normalmente acontece depois de copiar o projeto sem as dependências do Composer.

## Correção rápida

Na raiz do projeto, rode:

```bash
composer install
php artisan optimize:clear
php artisan serve
```

## Correção pelo Windows

Execute:

```bat
CORRIGIR_VENDOR_E_INICIAR.bat
```

Esse BAT:

1. verifica PHP;
2. verifica Composer;
3. roda `composer install` se `vendor/autoload.php` não existir;
4. cria `.env` se necessário;
5. limpa caches;
6. gera `APP_KEY` se necessário;
7. pergunta se deseja rodar migrations/seeders;
8. inicia `php artisan serve`.

## O que não copiar ao mover o projeto

Não é recomendado copiar estas pastas manualmente:

```txt
vendor
node_modules
.git
storage/logs
bootstrap/cache
```

Ao mover para outra pasta, copie o projeto sem essas pastas e depois rode:

```bash
composer install
npm install
```
