@echo off
chcp 65001 >nul
setlocal

echo ============================================
echo   CORRIGIR LARAVEL - VENDOR / AUTOLOAD
echo ============================================
echo.

if not exist composer.json (
    echo ERRO: composer.json nao encontrado.
    echo Execute este arquivo dentro da pasta raiz do projeto Laravel.
    pause
    exit /b 1
)

where php >nul 2>nul
if errorlevel 1 (
    echo ERRO: PHP nao encontrado no PATH.
    echo Instale/configure o PHP antes de continuar.
    pause
    exit /b 1
)

where composer >nul 2>nul
if errorlevel 1 (
    echo ERRO: Composer nao encontrado no PATH.
    echo Instale o Composer antes de continuar.
    pause
    exit /b 1
)

echo PHP encontrado:
php -v

echo.
echo Composer encontrado:
composer -V

echo.
if not exist vendor\autoload.php (
    echo vendor/autoload.php nao existe. Instalando dependencias...
    composer install
    if errorlevel 1 (
        echo.
        echo ERRO: composer install falhou.
        pause
        exit /b 1
    )
) else (
    echo vendor/autoload.php encontrado. Pulando composer install.
)

if not exist .env (
    if exist .env.example (
        echo Criando .env a partir de .env.example...
        copy .env.example .env >nul
    ) else (
        echo AVISO: .env e .env.example nao encontrados.
    )
)

echo.
echo Limpando caches do Laravel...
php artisan optimize:clear

echo.
echo Verificando APP_KEY...
findstr /R /C:"^APP_KEY=base64:" .env >nul 2>nul
if errorlevel 1 (
    echo Gerando APP_KEY...
    php artisan key:generate
) else (
    echo APP_KEY ja configurada.
)

echo.
echo Deseja rodar migrations e seeders agora? [S/N]
set /p RODAR_DB=Resposta: 
if /I "%RODAR_DB%"=="S" (
    php artisan migrate
    php artisan db:seed --class=SegmentoClienteCampoSeeder
)

echo.
echo Iniciando servidor Laravel...
php artisan serve

endlocal
