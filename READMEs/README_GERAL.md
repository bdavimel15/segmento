# Segmentador de Clientes com IA — README Geral

## Visão geral

Este projeto cria grupos dinâmicos de clientes usando o fluxo:

```text
Texto do usuário → Zaia/IA → JSON oficial → validação → SQL segura → prévia → execução/exportação
```

A regra oficial é sempre o JSON. A SQL é apenas um artefato gerado pelo backend para prévia, execução e auditoria.

## O que já está implementado

- Criação de segmentos com IA/Zaia.
- Criação manual com campos e operadores humanizados.
- JSON oficial como regra principal.
- Validação de campos e operadores.
- SQL gerada pelo Laravel com bindings seguros.
- Prévia de clientes.
- Histórico de execução e validação.
- Presets/modelos prontos.
- CRUD de clientes.
- CRUD de segmentos.
- Importação de clientes por CSV e XLSX básico.
- Exportação CSV de clientes.
- Exportação CSV de clientes de um segmento.
- Copiar telefones/e-mails de um segmento.
- Dashboard executivo.
- Simulação de mensagem com variáveis como `{{primeiro_nome}}`.

## Comandos principais

```bash
php artisan optimize:clear
php artisan view:clear
php artisan migrate
php artisan db:seed --class=SegmentoClienteCampoSeeder
php artisan db:seed --class=SegmentoClientePresetSeeder
php artisan db:seed --class=MockClienteSeeder
php artisan serve
```

## Importação de clientes

Acesse:

```text
/clientes/importar
```

CSV recomendado:

```csv
nome;email;telefone;sexo;bairro;municipio;newsletter;qtd_pedidos;pontos_totais
João Silva;joao@email.com;71999999999;Masculino;Centro;Feira de Santana;S;3;120
```

Duplicados são detectados por e-mail, telefone ou CPF.

## Exportação

Clientes gerais:

```text
/clientes/exportar-csv
```

Clientes de um segmento:

```text
/segmentos/{id}/exportar-csv
```

Copiar telefones/e-mails:

```text
/segmentos/{id}/copiar/telefones
/segmentos/{id}/copiar/emails
```

## Ainda pendente

- Integração real com Painel V2, aguardando estrutura real do banco.
- Canais reais de envio: WhatsApp, SMS, e-mail e push.
- Importação avançada com mapeamento visual de colunas.
- Permissões/admin para SQL legado.
