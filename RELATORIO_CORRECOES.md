# RELATÓRIO DE CORREÇÕES — Auditoria Completa do Segmentador

**Data:** 02/06/2026  
**Escopo:** Auditoria profunda pré-produção (13 fases)  
**Ambiente testado:** Laravel 13 + PHP 8.3 + SQLite local

---

## 1. Problemas encontrados

### Deploy e Git (CRÍTICO)
| # | Problema | Severidade |
|---|----------|------------|
| 1 | `.gitignore` com `*` ignorava **todos** os arquivos novos — classes criadas localmente nunca iam para o Railway | Crítica |
| 2 | `SegmentoSemanticParser` e 4 services irmãos existiam localmente mas não estavam no Git (corrigido na sessão anterior) | Crítica |
| 3 | `app/Models/Status.php` existia localmente mas **não estava versionado** — `Pedido::status()` quebrava em produção | Crítica |
| 4 | `nixpacks.toml` rodava seeders parciais, não o `DatabaseSeeder` completo | Alta |
| 5 | `railway.toml` não executava `migrate` no startup | Alta |
| 6 | SQLite efêmero no Railway — dados somem a cada redeploy sem volume persistente | Alta |
| 7 | `APP_KEY` não gerado automaticamente no build | Alta |
| 8 | `FORCE_HTTPS` definido no Nixpacks mas não lido pelo Laravel | Média |

### Autoload e Classes
| # | Problema |
|---|----------|
| 9 | Import morto `SegmentoSqlBuilder` no controller |
| 10 | `Cliente` model usava `created_at`/`updated_at` mas migration usa `cadastrado`/`atualizado` |
| 11 | `Pedido` sem `canal_pedido`/`forma_pagamento` no `$fillable` |

### Banco e Seeders
| # | Problema |
|---|----------|
| 12 | Duas migrations com timestamp `2026_06_04_000012` — coluna órfã `opcoes` |
| 13 | `SegmentoPresetSeeder` usava formato legado (conditions como string + chaves erradas: `ultima_compra`, `cashback_saldo`, etc.) — **presets não executavam** |
| 14 | `SegmentoClienteCampoSeeder` com `expressao_sql` MySQL (`TIMESTAMPDIFF`) para idade |
| 15 | Ordens duplicadas no seeder de campos (newsletter/funcionario = 11) |
| 16 | View `view_carrinho_abandonado` referenciada mas não criada nas migrations |
| 17 | Dual schema produto: `produto` (singular) vs `produtos` (plural) |

### Motor de Segmentação / SQLite
| # | Problema |
|---|----------|
| 18 | `SegmentoSqlBuilder` sem operadores: `between`, `between_dates`, `last_x_months`, `last_x_years`, `in`, `not_in` |
| 19 | `SegmentoEloquentBuilder` idade SQLite sem ajuste mês/dia (divergia do SQL Builder) |
| 20 | `SegmentoEloquentBuilder` sem `cashback_expira_em` e `month_between` |
| 21 | `applyProdutoComprado` com `not_equals`/`not_contains` quebrado sem tabela `pedido_produto_cliente` |
| 22 | `applyCarrinho` lançava exceção se view não existisse |

### Controllers e API
| # | Problema |
|---|----------|
| 23 | `campoOpcoes` para produto só consultava tabela `produtos`, ignorando `produto` |
| 24 | `exportar()` sem validação backend de `status_validacao === 'validada'` |
| 25 | Resposta JSON de prévia sem logs de execução |

### Frontend
| # | Problema |
|---|----------|
| 26 | Dashboard fragmentado (3 views, método `dashboard()` órfão) — **não alterado** (funciona via rota closure) |
| 27 | `welcome.blade.php` referencia rotas `login`/`register` inexistentes — **não roteado** |

---

## 2. Problemas corrigidos

| # | Correção |
|---|----------|
| 1 | `.gitignore` substituído pelo padrão Laravel |
| 2 | Services de segmentação commitados (sessão anterior) |
| 3 | Criado e versionado `app/Models/Status.php` |
| 4 | `nixpacks.toml`: `db:seed --force` completo + `APP_ENV=production` |
| 5 | `railway.toml`: `migrate --force` no startup |
| 6 | `AppServiceProvider`: lê `FORCE_HTTPS` além de `APP_ENV=production` |
| 7 | Removido import morto `SegmentoSqlBuilder` |
| 8 | `Cliente`: timestamps `cadastrado`/`atualizado` + `cliente_origem_id` no fillable |
| 9 | `Pedido`: `canal_pedido`/`forma_pagamento` no fillable |
| 10 | Removida migration duplicada `add_opcoes_to_segmento_cliente_campo_table.php` |
| 11 | `SegmentoPresetSeeder` reescrito com formato v2 e chaves corretas |
| 12 | `SegmentoClienteCampoSeeder`: idade sem SQL MySQL, ordens corrigidas |
| 13 | Nova migration `2026_06_05_000001_create_view_carrinho_abandonado.php` |
| 14 | `SegmentoSqlBuilder`: +6 operadores SQLite/MySQL compatíveis |
| 15 | `SegmentoEloquentBuilder`: idade SQLite, cashback_expira_em, month_between, produto not_equals, carrinho graceful |
| 16 | `campoOpcoes`: fallback para tabela `produto` |
| 17 | `exportar()`: bloqueio se segmento não validado |
| 18 | Prévia JSON inclui `logs` de execução |
| 19 | `.env.example`: documentado `ZAIA_WEBHOOK_URL` |

---

## 3. Arquivos alterados

- `.gitignore`
- `.env.example`
- `app/Http/Controllers/SegmentoClienteController.php`
- `app/Models/Cliente.php`
- `app/Models/Pedido.php`
- `app/Providers/AppServiceProvider.php`
- `app/Services/Segmentos/SegmentoEloquentBuilder.php`
- `app/Services/Segmentos/SegmentoSqlBuilder.php`
- `database/seeders/SegmentoClienteCampoSeeder.php`
- `database/seeders/SegmentoPresetSeeder.php`
- `nixpacks.toml`
- `railway.toml`

---

## 4. Arquivos criados

- `app/Models/Status.php`
- `database/migrations/2026_06_05_000001_create_view_carrinho_abandonado.php`
- `tests/audit_segmentacao.php`
- `RELATORIO_CORRECOES.md`

---

## 5. Arquivos removidos

- `database/migrations/2026_06_04_000012_add_opcoes_to_segmento_cliente_campo_table.php` (duplicata — coluna órfã `opcoes`)

---

## 6. Melhorias realizadas

- **Pipeline IA intacto:** Zaia → `SegmentoSemanticParser` → `SegmentoEloquentBuilder` → resultado (SQL só como fallback)
- **Paridade Eloquent/SQL** ampliada para operadores de intervalo e listas
- **28 presets** agora executam no motor v2
- **Logs estruturados** retornados na API de prévia (`logs[]`)
- **Script de auditoria** `tests/audit_segmentacao.php` para regressão rápida
- **Deploy Railway** mais robusto: migrate no start + seed completo no build

---

## 7. Problemas que ainda existem

| # | Problema | Impacto | Recomendação |
|---|----------|---------|--------------|
| 1 | SQLite efêmero no Railway — dados somem a cada redeploy | Alto | Usar PostgreSQL do Railway ou volume persistente |
| 2 | `APP_KEY` deve ser definido manualmente no painel Railway | Alto | Configurar variável `APP_KEY=base64:...` |
| 3 | `php artisan serve` é servidor de desenvolvimento | Médio | Migrar para FrankenPHP/Octane ou nginx+php-fpm |
| 4 | Dual schema `produto`/`produtos` — dois grafos de dados | Médio | Unificar em migration futura |
| 5 | Controller `SegmentoClienteController` com ~700 linhas | Baixo | Extrair orchestrator/action classes |
| 6 | Dual motor Eloquent+SQL duplica lógica de operadores | Baixo | Manter fallback, mas sincronizar em testes |
| 7 | Sem autenticação nas rotas | Baixo | Adicionar auth se for multi-tenant |
| 8 | `view_carrinho_abandonado` é stub vazio (0 registros) | Baixo | Popular quando houver dados reais de carrinho |
| 9 | Dashboard com 3 views duplicadas | Baixo | Unificar em refactor futuro |

---

## 8. Recomendações futuras

### Railway (obrigatório para produção estável)
```
APP_KEY=base64:...          # gerar com: php artisan key:generate --show
APP_ENV=production
APP_DEBUG=false
APP_URL=https://seu-app.up.railway.app
DB_CONNECTION=sqlite         # ou postgresql para persistência
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
ZAIA_WEBHOOK_URL=...        # opcional
```

### Persistência
- Trocar SQLite por **PostgreSQL** no Railway (addon gratuito/trial)
- Ou montar volume em `/app/database/database.sqlite`

### Testes
```bash
php tests/audit_segmentacao.php
```

### Comandos executados nesta auditoria
```bash
composer dump-autoload
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan migrate --force
php artisan db:seed --force
php artisan route:list
```

---

## 9. Resultados dos testes reais

| Caso | Motor | Total | Status |
|------|-------|-------|--------|
| Sexo = Masculino | eloquent | 9 | OK |
| Sexo = Feminino | eloquent | 11 | OK |
| Nome contém João | eloquent | 1 | OK |
| Idade >= 18 | eloquent | 20 | OK |
| Sem compra há 30 dias | eloquent | 11 | OK |
| Aniversariantes | eloquent | 1 | OK |
| Top compradores | eloquent | 10 | OK |
| Clientes ativos (newsletter) | eloquent | 15 | OK |
| Clientes inativos 90d | eloquent | 1 | OK |

**Resultado:** 9/9 testes OK — 0 erros Class not found — 0 erros SQLite — 31 rotas registradas.

---

## 10. Mapa do sistema (referência)

```
IA/Manual → AiSegmentoInterpreter → SegmentoSemanticParser
  → SegmentoRuleValidator → SegmentoQueryExecutor
      ├─ [1] SegmentoEloquentBuilder (primário)
      └─ [2] SegmentoSqlBuilder (fallback)
  → SegmentoPreviewService → JSON para frontend
```

**Rotas principais:**
- `POST /segmentos/manual` — prévia manual
- `POST /segmentos/interpretar` — IA
- `POST /segmentos/preview` — prévia JSON
- `POST /segmentos` — salvar segmento
- `GET /segmentos/campo-opcoes` — autocomplete
