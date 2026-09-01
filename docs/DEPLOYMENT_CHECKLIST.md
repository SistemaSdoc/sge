# Checklist de Deployment - Laravel Multi-Tenant com Stancl Tenancy

Baseado na documentação oficial do Laravel e Stancl Tenancy. Esta checklist cobre pontos críticos para deploy de aplicações multi-tenant.

---

## 1. Configuração de Ambiente

### 1.1 Variáveis de Ambiente (`.env` produção)

```env
# Aplicação
APP_NAME=SGE
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sge.example.com

# Base de Dados Central
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sge_central
DB_USERNAME=sge_user
DB_PASSWORD=<STRONG_PASSWORD>

# Cache (usar Redis em produção)
CACHE_DRIVER=redis
CACHE_PREFIX=sge_

# Session
SESSION_DRIVER=cookie
SESSION_LIFETIME=120

# Queue (usar Redis ou Beanstalkd)
QUEUE_CONNECTION=redis

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_DB=0

# Mail
MAIL_DRIVER=smtp
MAIL_HOST=<SMTP_HOST>
MAIL_PORT=587
MAIL_USERNAME=<EMAIL>
MAIL_PASSWORD=<EMAIL_PASSWORD>
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@sge.example.com
MAIL_FROM_NAME="SGE Sistema"

# Logging
LOG_CHANNEL=stack
LOG_STACK=single,slack  # ou sentry para monitoring
LOG_LEVEL=warning

# Tenancy - Multi-tenant
TENANCY_DB_PREFIX=tenant_
TENANCY_DB_SUFFIX=
```

**⚠️ CRÍTICO**: 
- Nunca commit `.env.production` no repositório
- Usar gestor de secrets (AWS Secrets Manager, Vault, etc.)
- `APP_DEBUG=false` SEMPRE em produção
- Senhas de BD com mínimo 16 caracteres e símbolos especiais

### 1.2 Configuração de Aplicação

Validar configurações em `config/`:

```bash
php artisan config:show app.name
php artisan config:show database.default
php artisan config:show cache.default
php artisan config:show queue.default
```

---

## 2. Base de Dados

### 2.1 Preparação da BD Central

```bash
# 1. Criar database e utilizador
mysql -u root -p
CREATE DATABASE sge_central;
CREATE USER 'sge_user'@'localhost' IDENTIFIED BY '<STRONG_PASSWORD>';
GRANT ALL PRIVILEGES ON sge_central.* TO 'sge_user'@'localhost';
FLUSH PRIVILEGES;

# 2. Executar migrações centrais
php artisan migrate --database=mysql

# 3. Seed de dados iniciais (permissões, roles, etc.)
php artisan db:seed --class=CentralSeeder
```

### 2.2 Configuração de Tenancy para Base de Dados

Validar em `config/tenancy.php`:

```php
'database' => [
    'central_connection' => 'mysql',  // Conexão para BD central
    'template_tenant_connection' => 'mysql',  // Template para BDs de tenants
    'prefix' => 'tenant_',  // Nome das BDs será "tenant_<id>"
    'suffix' => '',
    
    'managers' => [
        'mysql' => \Stancl\Tenancy\Database\TenantDatabaseManagers\MySQLDatabaseManager::class,
        // Outros drivers conforme necessário
    ],
],
```

### 2.3 Criar Primeira BD de Tenant (Opcional - Script de Provisioning)

```php
// Script ou Artisan command de provisioning
use Stancl\Tenancy\Database\Concerns\CentralConnection;

$tenant = Tenant::create(['id' => 'tenant-id-1']);

// Gerar credenciais de BD
$tenant->database()->makeCredentials();

// Criar BD via manager
$manager = $tenant->database()->manager();
$manager->createDatabase($tenant->database()->getName());
if ($manager instanceof ManagesDatabaseUsers) {
    $manager->createUser(
        $tenant->database()->getUsername(),
        $tenant->database()->getPassword()
    );
}

// Executar migrações no contexto do tenant
tenancy()->run($tenant, function () {
    Artisan::call('migrate');
    Artisan::call('db:seed', ['--class' => 'TenantSeeder']);
});
```

### 2.4 Verificação de Migrações

```bash
# Ver estado das migrações centrais
php artisan migrate:status

# Resetar migrações em produção (CUIDADO - destrói dados!)
# Só executar se absolutamente necessário
# php artisan migrate:reset --force
```

**⚠️ CRÍTICO**: Fazer backup ANTES de qualquer `migrate:reset`

---

## 3. Caching e Otimização

### 3.1 Configurar Caching em Produção

```bash
# Usar Redis (recomendado)
# Já configurado em REDIS_HOST, REDIS_PORT, etc.

# Cache de rutas e configuração
php artisan config:cache  # ❌ Não executar se usar .env em runtime
php artisan route:cache
php artisan view:cache
```

**⚠️ NOTA**: Em aplicações multi-tenant com `.env` dinâmicas, evitar `config:cache` se as configs variam por tenant.

### 3.2 Otimizar Composer

```bash
composer install --optimize-autoloader --no-dev
```

### 3.3 Compilar Assets (Frontend)

```bash
npm install --production
npm run build  # Vite/build production
```

**Validar**: Assets compilados em `public/build/`

---

## 4. Segurança

### 4.1 HTTPS Obrigatório

```php
// Em config/app.php ou AppServiceProvider
if ($this->app->environment('production')) {
    \URL::forceScheme('https');
    // Ou middleware
    // app(Middleware::class)->forceHttps();
}
```

### 4.2 Validar Aplicação Segura

```bash
# Verificar se usa HTTPS
php artisan route:list | grep https

# Validar headers de segurança
# Considerar adicionar middleware de segurança CORS, CSP, etc.
```

### 4.3 Autenticação e Autorização

- ✅ Validar todos os gatekeepers de autorização (Gates, Policies)
- ✅ Verificar permissões em `app/Policies/`
- ✅ Validar Guards configurados em `config/auth.php`
- ✅ Testar 2FA, passkeys, OAuth social (se implementados)

### 4.4 Isolamento Multi-Tenant (CRÍTICO)

#### Activar "Hardening Mode" em Tenancy

```php
// Em config/tenancy.php ou bootstrap
Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::$harden = true;
```

Isto previne:
- Acesso cross-tenant à base de dados
- Tentativas de acesso à BD central sem contexto correto

#### Validar Row-Level Security (RLS) - PostgreSQL

Se usar PostgreSQL, considerar RLS:

```php
'rls' => [
    'enabled' => true,  // Ativar RLS
    'tenant_id_column' => 'tenant_id',
],
```

#### Testes de Isolamento

```bash
# Executar testes de segurança multi-tenant
php artisan test tests/Unit/CrossTenantIsolationTest.php

# Resultado esperado: 13/13 testes passando
```

---

## 5. Logging e Monitoramento

### 5.1 Configurar Logging em Produção

```php
// config/logging.php
'default' => env('LOG_CHANNEL', 'stack'),

'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'slack'],  // Enviar para Slack/Sentry em produção
        'ignore_exceptions' => false,
    ],
    
    'single' => [
        'driver' => 'single',
        'path' => storage_path('logs/laravel.log'),
        'level' => env('LOG_LEVEL', 'debug'),
    ],
    
    // Para production, considerar:
    // 'sentry' => [ ... ],  // Sentry.io para exception tracking
    // 'slack' => [ ... ],   // Slack para alertas
],
```

### 5.2 Listener de Queries (Monitorar BD)

```php
// Em AppServiceProvider ou service próprio
DB::listen(function($query, $bindings, $time) {
    \Log::warning("Query: {$query} | Time: {$time}ms", compact('bindings'));
});
```

### 5.3 Estrutura de Logs da Tutela

Logs estruturados já implementados em:
- `app/Services/Tenant/Tutela/TutelaService.php`
- `app/Services/Tenant/Tutela/TutelaCentralService.php`

Exemplo:
```
[2026-09-01 15:30:45] production.INFO: Tutela flow started for tenant_id=acme, course_id=123
[2026-09-01 15:31:02] production.INFO: Central validation passed, shared link created
[2026-09-01 15:31:15] production.INFO: Tenant association completed
```

---

## 6. Filas (Queues)

### 6.1 Configurar Queue Worker

```bash
# Iniciar queue worker em produção (usar Supervisor ou similar)
php artisan queue:work redis --timeout=600 --tries=3 --backoff=3

# Ou para daemon
php artisan queue:listen
```

### 6.2 Configuração de Retry

Validar em jobs (ex: `SincronizarAssociacaoTutela.php`):

```php
class SincronizarAssociacaoTutela implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 120, 300];  // 1m, 2m, 5m
    public $timeout = 600;  // 10 minutos
}
```

### 6.3 Monitorar Fila

```bash
# Ver jobs na fila
php artisan queue:failed

# Retry jobs falhados
php artisan queue:retry all
```

### 6.4 Job de Recovery de Tutela

O job `SincronizarAssociacaoTutela` já implementa:
- ✅ Retry automático com backoff
- ✅ Unique lock para prevenir duplicatas
- ✅ `afterCommit()` para garantir transação completada

---

## 7. Testes Pré-Deploy

### 7.1 Testes de Tutela (Foco Multi-Tenant)

```bash
# Executar suite completa de testes de tutela
php artisan test tests/Unit/CrossTenantIsolationTest.php --compact

# Resultado esperado: 13/13 testes passando
```

Testes validam:
- ✅ Validação de vinculação (PENDENTE, ACTIVO, REJEITADO)
- ✅ Acesso cross-tenant autorizado
- ✅ Isolamento de dados
- ✅ Notificações para tutor
- ✅ Exportação de pauta remota
- ✅ Bloqueios de gestão quando PENDENTE

### 7.2 Lint e Formatação

```bash
# PHP Code Style (Pint)
./vendor/bin/pint --dirty --format agent

# TypeScript/React
npm run build  # Valida syntax

# PHPStan (type checking)
./vendor/bin/phpstan analyse app/
```

### 7.3 Validar Migrações

```bash
php artisan migrate:status
```

**Esperado**: Todas as migrações com status `[✓] Ran`

---

## 8. Deployment Steps (Production)

### 8.1 Pre-Deployment Checklist

- [ ] Todos os testes passando (`php artisan test --compact`)
- [ ] Code formatting válido (`pint`, `eslint`)
- [ ] Migrações prontas e testadas
- [ ] Ambiente `.env` configurado
- [ ] Redis/Cache iniciado
- [ ] SMTP/Mail configurado
- [ ] Secrets armazenados em gestor seguro
- [ ] Backup de BD central pronto
- [ ] Queue worker configurado

### 8.2 Passos de Deploy

```bash
# 1. Atualizar código
git pull origin production

# 2. Instalar dependências (sem dev)
composer install --optimize-autoloader --no-dev
npm install --production

# 3. Compilar assets
npm run build

# 4. Backup de BD (CRÍTICO)
mysqldump -u sge_user -p sge_central > backup-$(date +%Y%m%d-%H%M%S).sql

# 5. Executar migrações
php artisan migrate --force

# 6. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# 7. Warm up caches (opcional)
php artisan route:cache
php artisan view:cache

# 8. Reiniciar queue worker (Supervisor)
sudo supervisorctl restart all

# 9. Validar health check
curl https://sge.example.com/api/health

# 10. Monitorar logs
tail -f storage/logs/laravel.log
```

### 8.3 Rollback em Caso de Erro

```bash
# 1. Reverter código
git revert <commit-hash>
git push origin production

# 2. Rollback de migrações (CUIDADO!)
php artisan migrate:rollback --force

# 3. Restaurar backup de BD
mysql -u sge_user -p sge_central < backup-YYYYMMDD-HHMMSS.sql

# 4. Reiniciar aplicação
sudo supervisorctl restart all
```

---

## 9. Monitoramento Pós-Deploy

### 9.1 Health Checks

```bash
# Verificar aplicação
curl https://sge.example.com/

# Verificar autenticação
curl -X POST https://sge.example.com/api/login

# Verificar BD central
php artisan tinker
> DB::table('users')->count()

# Verificar tenant
php artisan tinker
> tenancy()->initialize(Tenant::find('tenant-id-1'));
> DB::table('instituicoes')->count()
```

### 9.2 Monitorar Logs

```bash
# Logs de aplicação
tail -f storage/logs/laravel.log

# Logs de queue
tail -f storage/logs/queue.log

# Logs de sistema
journalctl -u supervisor -f  # Se usar supervisor para queue
```

### 9.3 Alertas Recomendados

Configurar alertas para:
- ❌ Taxa alta de erros (> 5% de requisições)
- ❌ Tempo de resposta lento (> 2 segundos)
- ❌ Fila de jobs acumulando (> 100 jobs)
- ❌ Disco cheio (< 10% livre)
- ❌ Memória crítica (> 90%)

---

## 10. Problemas Comuns e Troubleshooting

### 10.1 "Unable to locate file in Vite manifest"

```bash
npm run build  # Recompilar assets
php artisan view:clear
```

### 10.2 "Cannot migrate: no such table"

```bash
# Migrations não foram executadas
php artisan migrate --force

# Ou rollback e re-run
php artisan migrate:rollback --force
php artisan migrate --force
```

### 10.3 Cross-Tenant Access Denied

```bash
# Validar Hardening Mode
Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::$harden = true

# Verificar se tenant está inicializado
tenancy()->check()  # Deve retornar true se dentro de tenant context

# Testar isolamento
php artisan test tests/Unit/CrossTenantIsolationTest.php
```

### 10.4 Queue Jobs Falhando

```bash
# Ver jobs falhados
php artisan queue:failed

# Retry
php artisan queue:retry all

# Debugar job específico
php artisan queue:work --once  # Processar um job e sair
php artisan tinker
> Log::info(file_get_contents('storage/logs/laravel.log'));
```

### 10.5 Redis Connection Issues

```bash
# Testar conexão Redis
redis-cli ping  # Deve retornar PONG

# Se cache:clear falhar:
redis-cli FLUSHALL  # Limpar todo o Redis (CUIDADO!)
```

---

## 11. Security & Compliance

### 11.1 GDPR / Data Protection

- [ ] Dados de tenants isolados em BDs separadas
- [ ] Backup regular com encriptação
- [ ] Política de retenção de logs
- [ ] Capacidade de exportar/deletar dados de tenant
- [ ] Audit logs de operações sensíveis

### 11.2 Audit Logging (Tutela Específico)

O fluxo de tutela tem logging estruturado:

```php
// Exemplo de log auditável
Log::channel('tutela')->info('Tutela published', [
    'tenant_id' => $tenant->id,
    'curso_id' => $curso->id,
    'status' => $status,
    'user_id' => auth()->id(),
    'timestamp' => now(),
]);
```

### 11.3 Validar Permissions & Policies

```bash
# Testar políticas
php artisan test tests/Unit/CrossTenantIsolationTest.php
```

---

## 12. Checklist Final

Antes de fazer deploy em produção:

- [ ] **Ambiente**: `.env` configurado, secrets armazenados seguramente
- [ ] **BD**: Migrações testadas, backup de BD central pronto
- [ ] **Cache**: Redis iniciado e testado
- [ ] **Queue**: Queue worker configurado (Supervisor)
- [ ] **Segurança**: HTTPS ativo, isolamento multi-tenant validado, hardening mode ON
- [ ] **Testes**: CrossTenantIsolationTest 13/13 passando
- [ ] **Code Quality**: Pint passing, ESLint clean
- [ ] **Assets**: npm run build completou sem erros
- [ ] **Logs**: Logging configurado (Sentry/Slack opcional)
- [ ] **Monitoring**: Health checks funcionando
- [ ] **Rollback Plan**: Backup e procedimento de rollback documentado
- [ ] **Team**: Todos da equipa sabem como fazer deploy e rollback

---

## Referências

- **Laravel Documentation**: https://laravel.com/docs
- **Stancl Tenancy**: https://tenancyforlaravel.com/
- **Laravel Security**: https://laravel.com/docs/security
- **Queue Documentation**: https://laravel.com/docs/queues
- **Testing**: https://laravel.com/docs/testing

---

**Última Atualização**: 2026-09-01  
**Projeto**: SGE - Sistema de Gestão Educacional  
**Versão**: Laravel 13, Inertia v3, Stancl Tenancy
