<?php

return [
    /*
    | Modo técnico: exibe JSON, SQL, logs e painéis de debug nas telas do cliente.
    | Ativo quando APP_DEBUG=true ou SEGMENTADOR_DEV_MODE=true
    */
    'dev_mode' => env('SEGMENTADOR_DEV_MODE', env('APP_DEBUG', false)),

    /*
    | Senha simples para área /admin (integração futura substituirá por auth externa).
    | Defina SEGMENTADOR_ADMIN_PASSWORD no .env
    */
    'admin_password' => env('SEGMENTADOR_ADMIN_PASSWORD', 'segmentador-admin'),

    /*
    | Exportação permitida para segmentos pendentes (prévia antes da aprovação admin).
    */
    'export_when_pending' => env('SEGMENTADOR_EXPORT_PENDING', true),
];
