# Guia de Logging da Tutela Externa

## Visão Geral

Logging estruturado foi adicionado a todos os pontos críticos do fluxo de tutela externa. Use este guia para troubleshoot issues em produção.

## Arquivos com Logging

- `app/Services/Tenant/Tutela/TutelaValidator.php` - Validação de elegibilidade
- `app/Services/Tenant/Tutela/TutelaCentralService.php` - Operações na base central
- `app/Services/Tenant/Tutela/TutelaTenantService.php` - Operações no tenant tutelado
- `app/Services/Tenant/Tutela/TutelaService.php` - Orquestração do fluxo
- `app/Services/Tenant/Tutela/TutelaNotificationService.php` - Notificações
- `app/Jobs/Tenant/Tutela/SincronizarAssociacaoTutela.php` - Recuperação de falhas

## Fluxo de Logs (Happy Path)

```
1. TutelaValidator::validarTutelaExterna
   └─ INFO: Validação iniciada (tenant_tutor_id, tenant_tutelado_id)
   └─ INFO: Validação completada com sucesso

2. TutelaCentralService::criarOuActualizarVinculo
   └─ DEBUG: Criação/actualização iniciada
   └─ INFO: Novo vínculo criado (shared_id) OU Vínculo actualizado
   
3. TutelaTenantService::associarTutelaExterna
   └─ DEBUG: Associação iniciada
   └─ INFO: Tutela associada no tenant
   
4. TutelaService::publicarEAssociarCurso
   └─ INFO: Publicação iniciada
   └─ INFO: Associação completada
   └─ INFO: Notificação enviada
   └─ INFO: Fluxo completado com sucesso
   
5. TutelaNotificationService::notificarNovaSolicitacao
   └─ DEBUG: Notificação iniciada
   └─ INFO: Notificação enviada para admin (admin_email)
```

## Fluxo de Logs (Caminho com Falha - Recovery)

```
1. [Todos os steps acima até passo 3]

2. TutelaTenantService::associarTutelaExterna FALHA
   └─ ERROR: Validação falhou (tenant_esperado vs tenant_vinculo)
   └─ ERROR: Vínculo não está pendente (status_atual)
   
3. TutelaService::publicarEAssociarCurso (catch Throwable)
   └─ ERROR: Falha ao associar no tenant (shared_id, exception)
   └─ INFO: Job de sincronização despachado
   
4. SincronizarAssociacaoTutela::handle (Job Recovery)
   └─ INFO: Job iniciado (attempt=1)
   └─ INFO: Job completado com sucesso
   
   OU (se falha novamente)
   
   └─ INFO: Job iniciado (attempt=2, backoff=60s)
   └─ ERROR: Job falha definitiva (attempt=5)
```

## Consultas de Logs Práticas

### 1. Encontrar um Vínculo de Tutela Específico

```bash
# Buscar todos os logs para um vínculo (shared_id)
tail -f storage/logs/laravel.log | grep "shared_id=abc-def-123"

# Com jq (se os logs são JSON)
cat storage/logs/laravel.log | jq 'select(.shared_id=="abc-def-123")'
```

### 2. Rastrear Fluxo Completo

```bash
# Ver todo o fluxo desde validação até notificação
grep "Validação de tutela externa\|Publicação iniciada\|Fluxo completado" storage/logs/laravel.log

# Com timestamp
tail -50 storage/logs/laravel.log | grep -E "INFO|ERROR" | head -20
```

### 3. Encontrar Falhas de Associação (Partial Failures)

```bash
# Ver onde as associações falharam (indica disparos de recovery job)
grep "Falha ao associar tutela\|Job de sincronização despachado" storage/logs/laravel.log

# Verificar sucesso do recovery
grep "Job de sincronização de associação de tutela completado" storage/logs/laravel.log
```

### 4. Monitorar Notificações

```bash
# Ver notificações não enviadas
grep "Notificação não enviada" storage/logs/laravel.log

# Ver quais admins receberam notificações
grep "admin_email" storage/logs/laravel.log | cut -d: -f2-
```

### 5. Diagnosticar Tentativas de Job

```bash
# Ver quantas tentativas um job fez
grep "shared_id=abc-def-123" storage/logs/laravel.log | grep "attempt="

# Ver se um job falhou definitivamente
grep "Falha definitiva do job" storage/logs/laravel.log | grep "shared_id=abc-def-123"
```

## Campos Importantes nos Logs


| Campo                | Significado                           | Exemplo                                                  |
| -------------------- | ------------------------------------- | -------------------------------------------------------- |
| `shared_id`          | ID do vínculo na central             | `123e4567-e89b-12d3-a456-426614174000`                   |
| `tenant_tutor_id`    | Tenant que oferece tutela             | `tenant-instituto-1`                                     |
| `tenant_tutelado_id` | Tenant que recebe tutela              | `tenant-colegio-1`                                       |
| `curso_tutelado_id`  | Curso no tenant tutelado              | `id-do-curso`                                            |
| `status_vinculo`     | Estado actual (PENDENTE, ACTIVO, etc) | `activo`                                                 |
| `attempt`            | Numero da tentativa (para jobs)       | `2`                                                      |
| `exception`          | Mensagem de erro                      | `"O vínculo de tutela não pertence ao tenant actual."` |
| `admin_email`        | Email do admin notificado             | `admin@instituicao.pt`                                   |

## Níveis de Log


| Nível    | Quando Usado                                        | Ação                                |
| --------- | --------------------------------------------------- | ------------------------------------- |
| `DEBUG`   | Operações rotineiras, validações                | Ignorar em produção (muito verbose) |
| `INFO`    | Sucesso, transições de estado, marcos importantes | Monitorar em produção               |
| `WARNING` | Falhas de validação (recoverable)                 | Investigar se frequente               |
| `ERROR`   | Exceções, falhas não-recoverable                 | **ALERTAR IMEDIATAMENTE**             |

## Sentry/Rollbar Integration

Se usando Sentry ou Rollbar:

```php
// Errors são automaticamente reportados
report($exception);

// Para adicionar contexto extra
Sentry::captureMessage('Tutela falhou', 'error', [
    'shared_id' => $shared->id,
    'tenant_ids' => [$tutorId, $tuteladoId],
]);
```

## Exemplo: Troubleshoot um Cliente Reclamando

**Cliente:** "Meu vínculo de tutela não está funcionando"

**Passos:**

1. **Encontre o shared_id:**

   ```bash
   # No seu app, consulte database (central)
   php artisan tinker
   >>> $shared = \App\Models\Central\CursoTuteladoShared::find('shared-id')
   >>> $shared->curso_nome, $shared->status
   ```
2. **Procure no log:**

   ```bash
   tail -1000 storage/logs/laravel.log | grep "shared_id=$SHARED_ID"
   ```
3. **Interprete os resultados:**

   - Se vir `"Fluxo completado com sucesso"` → Tudo OK, problema no client-side
   - Se vir `"Falha ao associar tutela"` → Recurra o recovery job status (veja passo 4)
   -  Se não vir nada → Problema ocorreu antes de tentar (validação falhou)
4. **Verifique o status do recovery job:**

   ```bash
   php artisan queue:failed
   # Procure jobs com shared_id=$SHARED_ID

   # Retry manualmente se necessário
   php artisan queue:retry
   ```

## Troubleshooting: Cenários Comuns

### Cenário 1: "Validação falhou: institução tutelada não é colégio"

**Causa:** Cliente tentou criar tutela em instituição tipo 'instituto' (deve ser 'colegio')

**Logs:**

```
WARNING: Validação falhou: instituição tutelada não é colégio
  instituicao_tipo: instituto
```

**Solução:** Mostrar ao cliente que só colégios podem ter tutela externa

### Cenário 2: "Falha ao associar tutela no tenant tutelado"

**Causa:** Falha parcial (central OK, tenant falhou - ex: conexão perdida)

**Logs:**

```
ERROR: Falha ao associar tutela no tenant tutelado
INFO: Job de sincronização despachado
INFO: Job de sincronização completado com sucesso (30 segundos depois)
```

**Solução:** Recovery job corrigiu automaticamente. Normal em ambientes frágeis.

### Cenário 3: "Notificação não enviada: dados incompletos"

**Causa:** Tenant tutor ou admin não existe/não configurado

**Logs:**

```
WARNING: Notificação não enviada: dados incompletos
  admin_user_id: null
```

**Solução:** Admin deve estar configurado no tenant tutor (`tenants.admin_user_id`)

## Performance: Monitorar N+1 Queries

Logging inclui carregamento correto com `loadMissing()`:

```php
// BOM - Vê "loadMissing" no código
Log::debug('Criação iniciada', [...]);
$cursoTutelado->loadMissing('instituicaoCurso.curso');

// Resultado: N+1 evitado
```

Se ver queries lentas, procure por:

```bash
grep "Criação iniciada" storage/logs/laravel.log -A 5
# Verificar tempo entre "Criação iniciada" e "Novo vínculo criado"
```

## Próximos Passos

1. **Configure log rotation** (daily, single size limit)

   ```php
   // config/logging.php
   'daily' => [
       'driver' => 'daily',
       'path' => storage_path('logs/laravel.log'),
       'level' => env('LOG_LEVEL', 'debug'),
       'days' => 14, // Manter 14 dias
   ],
   ```
2. **Configure Sentry/Rollbar** para alertas automáticos de erros
3. **Configure CloudWatch/Datadog** para agregar logs e alertas
4. **Crie dashboard** para monitorar:

   - Taxa de sucesso de tutelas
   - Tempo médio de processamento
   - Tentativas de recovery job
   - Notificações não enviadas
