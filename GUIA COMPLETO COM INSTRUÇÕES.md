# Como Testar o Sistema PicPay

Este guia explica de forma simples e prática como testar o sistema de transferências.

## Requisitos Rápidos

**Antes de começar, você precisa ter:**
-  **Docker Desktop** instalado e rodando
-  **PHP 8.2+** (opcional - já vem no Docker)
-  **Composer** (opcional - já vem no Docker)

**Para testar, você pode usar:**
-  **Postman** (recomendado - interface gráfica)
-  **Terminal de Comando** (PowerShell, Terminal, CMD)

**Nota:** Se você usar Docker, não precisa instalar PHP e Composer no seu computador - eles já vêm dentro dos containers.

## Índice Rápido

1. [Requisitos de Instalação](#requisitos-de-instalação) - Verificar antes de começar
2. [Instalação Inicial](#instalação-inicial) - Configurar o ambiente
3. [Preparação Inicial](#preparação-inicial) - 2 minutos
4. [Testes Automatizados](#testes-automatizados) - 1 minuto
5. [Testes Manuais](#testes-manuais) - 5 minutos
6. [Verificação de Resultados](#verificação-de-resultados) - 2 minutos

---

## Requisitos de Instalação

Antes de começar a testar o sistema, você precisa ter instalado:

### Obrigatório

1. **Docker Desktop** (Windows/Mac) ou **Docker Engine + Docker Compose** (Linux)
   - Download: https://www.docker.com/products/docker-desktop/
   - Versão mínima: Docker 20.10+
   - **Importante:** Docker Desktop deve estar **rodando** antes de executar comandos

2. **PHP 8.2+** (opcional se usar Docker - já vem no container)
   - Download: https://www.php.net/downloads.php
   - Necessário apenas se for executar fora do Docker

3. **Composer** (opcional se usar Docker - já vem no container)
   - Download: https://getcomposer.org/download/
   - Necessário apenas se for executar fora do Docker

### Verificação Rápida

Execute estes comandos para verificar se tudo está instalado:

```bash
# Verificar Docker
docker --version
docker-compose --version

# Verificar PHP (se instalado localmente)
php --version

# Verificar Composer (se instalado localmente)
composer --version
```

### Ferramentas para Testar

Você pode testar o sistema usando:

- **Postman** - Interface gráfica para testar APIs (recomendado para iniciantes)
  - Download: https://www.postman.com/downloads/
- **Terminal de Comando** - PowerShell (Windows), Terminal (Linux/Mac), ou CMD (Windows)
  - Usando `curl` ou `Invoke-RestMethod` (PowerShell)

**Nota:** Se você usar Docker, não precisa instalar PHP e Composer no seu computador - eles já vêm dentro dos containers Docker.

---

## Instalação Inicial

Antes de testar o sistema, você precisa configurar o ambiente pela primeira vez. Siga estes passos na ordem:

### Passo 1: Clonar o Repositório (se ainda não fez)

```bash
git clone <url-do-repositorio>
cd Desafio-pickpay
```

### Passo 2: Iniciar os Containers Docker

```bash
# Iniciar todos os containers (app, nginx, db, php-cli)
docker-compose up -d

# Aguardar alguns segundos para tudo inicializar
# Verificar se estão rodando:
docker-compose ps
```

**Resultado esperado:**
```
NAME                STATUS              PORTS
picpay_app          Up                  9000/tcp
picpay_db           Up                  0.0.0.0:5432->5432/tcp
picpay_nginx        Up                  0.0.0.0:8000->80/tcp
picpay_php_cli      Up                  9000/tcp
```

### Passo 3: Instalar Dependências do Composer

```bash
# Instalar todas as dependências PHP dentro do container
docker exec picpay_php_cli composer install
```

**Resultado esperado:**
```
Loading composer repositories with package information
Installing dependencies from lock file
...
Package operations: X installs, 0 updates, 0 removals
...
Generating autoload files
```

**Tempo estimado:** 1-2 minutos na primeira vez

### Passo 4: Executar Migrações do Banco de Dados

```bash
# Criar todas as tabelas no banco de dados
docker exec picpay_php_cli php database/migrations/run_migrations.php
```

**Resultado esperado:**
```
Creating users table...
Users table created successfully.
Creating wallets table...
Wallets table created successfully.
Creating transfers table...
Transfers table created successfully.
Migrations completed successfully!
```

### Passo 5: Popular Banco com Dados de Teste

```bash
# Criar usuários e carteiras de exemplo
docker exec picpay_php_cli php database/seeds/seed_users.php
```

**Resultado esperado:**
```
Seeding users...
Created user: João Silva (ID: 1) - Balance: 1000.00
Created user: Maria Santos (ID: 2) - Balance: 500.00
Created merchant: Loja do Zé (ID: 3) - Balance: 0.00

Seed completed successfully!
```

### Passo 6: Verificar se Tudo Está Funcionando

```bash
# Verificar se a API está respondendo
curl http://localhost:8000/transfer

# Ou no PowerShell:
Invoke-RestMethod -Uri "http://localhost:8000/transfer" -Method GET
```

**Resultado esperado:** Erro 405 (Method Not Allowed) ou erro de validação - isso é normal, significa que a API está funcionando!

### Resumo dos Comandos de Instalação

Execute estes comandos na ordem (apenas na primeira vez):

```bash
# 1. Iniciar containers
docker-compose up -d

# 2. Instalar dependências do Composer
docker exec picpay_php_cli composer install

# 3. Criar tabelas do banco de dados
docker exec picpay_php_cli php database/migrations/run_migrations.php

# 4. Popular banco com dados de teste
docker exec picpay_php_cli php database/seeds/seed_users.php
```

**Tempo total estimado:** 3-5 minutos na primeira vez

**Verificação rápida após instalação:**
```bash
# Verificar se tudo está funcionando
docker-compose ps                    # Containers rodando
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT COUNT(*) FROM users;"  # Deve retornar 3
```

**Próximo passo:** Após a instalação inicial, você pode ir direto para a seção [Testes Manuais](#testes-manuais) ou [Testes Automatizados](#testes-automatizados).

---

## Preparação Inicial

**Nota:** Se você já executou a [Instalação Inicial](#instalação-inicial), pode pular esta seção e ir direto para os testes.

### Passo 1: Verificar se os Containers Estão Rodando

```bash
# Verificar status dos containers
docker-compose ps
```

Se os containers não estiverem rodando:

```bash
# Iniciar containers Docker
docker-compose up -d

# Aguardar alguns segundos para tudo inicializar
```

### Passo 2: Verificar se as Dependências Estão Instaladas

```bash
# Verificar se o vendor existe
docker exec picpay_php_cli ls -la vendor

# Se não existir, instalar:
docker exec picpay_php_cli composer install
```

### Passo 3: Verificar se as Tabelas Existem

```bash
# Verificar se as tabelas foram criadas
docker exec picpay_db psql -U picpay_user -d picpay_db -c "\dt"

# Se não existirem, executar migrações:
docker exec picpay_php_cli php database/migrations/run_migrations.php
```

### Passo 4: Verificar se Existem Dados de Teste

```bash
# Verificar se existem usuários
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT COUNT(*) FROM users;"

# Se não existirem, popular dados:
docker exec picpay_php_cli php database/seeds/seed_users.php
```

**Resultado esperado:**
```
Seeding users...
Created user: João Silva (ID: 1)
Created user: Maria Santos (ID: 2)
Created merchant: Loja do Zé (ID: 3)

Seed completed successfully!
```

---

## Testes Automatizados

### Executar Todos os Testes

```bash
docker exec picpay_php_cli composer test
```

**O que isso faz?**
- Executa testes unitários (testam classes isoladas)
- Executa testes de integração (testam fluxos completos)
- Mostra quais testes passaram ou falharam

**Resultado esperado:**
```
PHPUnit 10.x.x by Sebastian Bergmann and contributors.

..                                                                   2 / 2 (100%)

Time: 00:00.123, Memory: 8.00 MB

OK (2 tests, 10 assertions)
```

### Executar Testes Específicos

```bash
# Apenas testes unitários
docker exec picpay_php_cli vendor/bin/phpunit tests/Unit

# Apenas testes de integração
docker exec picpay_php_cli vendor/bin/phpunit tests/Integration

# Um arquivo específico
docker exec picpay_php_cli vendor/bin/phpunit tests/Unit/Domain/Entity/WalletTest.php
```

---

## Testes Manuais

### Opção 1: Usando Script Automatizado (Mais Fácil)

**No Windows (PowerShell):**
```powershell
.\testar_transferencias.ps1
```

**No Linux/Mac:**
```bash
./test_api.sh
```

O script executa vários testes automaticamente e mostra os resultados.

### Opção 2: Testes Manuais com cURL

#### Teste 1: Transferência Bem-Sucedida

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.0, "payer": 1, "payee": 2}'
```

**Resultado esperado:**
```json
{
  "transfer_id": 1,
  "status": "completed",
  "message": "Transfer completed successfully"
}
```

#### Teste 2: Saldo Insuficiente

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 2000.0, "payer": 1, "payee": 2}'
```

**Resultado esperado:**
```json
{
  "error": "Payer 1 has insufficient balance"
}
```

#### Teste 3: Lojista Tentando Enviar

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.0, "payer": 3, "payee": 1}'
```

**Resultado esperado:**
```json
{
  "error": "User 3 is a merchant and cannot send money"
}
```

#### Teste 4: Usuário Não Encontrado

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.0, "payer": 999, "payee": 1}'
```

**Resultado esperado:**
```json
{
  "error": "Payer with ID 999 not found"
}
```

### Opção 3: Usando Postman (Recomendado para Iniciantes)

**Pré-requisito:** Ter o Postman instalado
- Download: https://www.postman.com/downloads/

**Passos:**

1. Abra o Postman
2. Crie uma nova requisição:
   - Clique em "New" → "HTTP Request"
   - **Método:** Selecione "POST" no dropdown
   - **URL:** Digite `http://localhost:8000/transfer`
   - **Headers:** 
     - Clique na aba "Headers"
     - Adicione: `Content-Type` = `application/json`
   - **Body:**
     - Clique na aba "Body"
     - Selecione "raw"
     - No dropdown ao lado, selecione "JSON"
     - Cole o JSON:
     ```json
     {
       "value": 100.0,
       "payer": 1,
       "payee": 2
     }
     ```
3. Clique em "Send"

**Resultado esperado:**
```json
{
  "transfer_id": 1,
  "status": "completed",
  "message": "Transfer completed successfully"
}
```

**Status HTTP:** 201 Created

### Opção 4: Usando PowerShell (Windows)

**Pré-requisito:** PowerShell (já vem instalado no Windows)

**Comando básico:**
```powershell
Invoke-RestMethod -Uri "http://localhost:8000/transfer" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"value": 100.0, "payer": 1, "payee": 2}'
```

**Versão mais detalhada (com tratamento de erro):**
```powershell
try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/transfer" `
        -Method POST `
        -ContentType "application/json" `
        -Body '{"value": 100.0, "payer": 1, "payee": 2}'
    Write-Host "Sucesso:" -ForegroundColor Green
    $response | ConvertTo-Json
} catch {
    Write-Host "Erro:" -ForegroundColor Red
    $_.Exception.Message
}
```

### Opção 5: Usando cURL (Linux/Mac/Windows)

**Pré-requisito:** cURL (já vem instalado na maioria dos sistemas)

**Comando básico:**
```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.0, "payer": 1, "payee": 2}'
```

**Versão mais detalhada (com status HTTP):**
```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.0, "payer": 1, "payee": 2}' \
  -w "\nHTTP Status: %{http_code}\n" \
  -v
```

---

## Usando Mock de Autorização (Local e Web)

O sistema possui um mock de autorização que permite testar transferências mesmo quando o serviço externo `https://util.devi.tools/api/v2/authorize` não está disponível. O mock sempre autoriza as transferências, facilitando testes locais.


### Como Ativar o Mock

#### Opção 1: Via Comando Docker (Recomendado)

1. **Adicionar variável de ambiente no container:**
   ```bash
   docker exec picpay_php_cli sh -c "echo 'USE_MOCK_AUTHORIZATION=true' >> /var/www/html/.env"
   ```

2. **Reinicie o container:**
   ```bash
   docker restart picpay_php_cli
   ```

3. **Aguarde alguns segundos e teste:**
   ```powershell
   # PowerShell
   Invoke-RestMethod -Uri "http://localhost:8000/transfer" `
     -Method POST `
     -ContentType "application/json" `
     -Body '{"value": 100.0, "payer": 1, "payee": 2}'
   ```

   Ou via Postman:
   - Método: POST
   - URL: `http://localhost:8000/transfer`
   - Body: `{"value": 100.0, "payer": 1, "payee": 2}`

#### Opção 2: Editar o .env Localmente

1. **Crie ou edite o arquivo `.env` na raiz do projeto:**
   ```env
   USE_MOCK_AUTHORIZATION=true
   ```

2. **Reinicie o container:**
   ```bash
   docker restart picpay_php_cli
   ```

### Verificar se o Mock Está Ativo

Após ativar o mock, você verá nos logs:

```bash
docker logs picpay_php_cli | grep -i "mock\|authorization"
```

**Com mock ativo:**
```
[INFO] Mock authorization service: Always authorizing {"authorized":true}
```

**Sem mock (serviço real):**
```
[INFO] Authorization service response {"status_code":200,...}
```

### Testar com Mock Ativo

#### Via PowerShell (Local)

```powershell
Invoke-RestMethod -Uri "http://localhost:8000/transfer" `
  -Method POST `
  -ContentType "application/json" `
  -Body '{"value": 100.0, "payer": 1, "payee": 2}'
```

#### Via Postman (Local ou Web)

1. Abra o Postman
2. Configure a requisição:
   - Método: POST
   - URL: `http://localhost:8000/transfer` (local) ou `http://seu-servidor:8000/transfer` (web)
   - Headers: `Content-Type: application/json`
   - Body (raw JSON):
     ```json
     {
       "value": 100.0,
       "payer": 1,
       "payee": 2
     }
     ```
3. Clique em "Send"

**Resultado esperado:**
```json
{
  "transfer_id": 1,
  "status": "completed",
  "message": "Transfer completed successfully"
}
```

### Desativar o Mock

Para voltar a usar o serviço real de autorização:

1. **Remova a variável do `.env`:**
   ```bash
   docker exec picpay_php_cli sh -c "sed -i '/USE_MOCK_AUTHORIZATION/d' /var/www/html/.env"
   ```

2. **Ou defina como false:**
   ```bash
   docker exec picpay_php_cli sh -c "sed -i 's/USE_MOCK_AUTHORIZATION=true/USE_MOCK_AUTHORIZATION=false/' /var/www/html/.env"
   ```

3. **Reinicie o container:**
   ```bash
   docker restart picpay_php_cli
   ```

### Comparação: Mock vs Serviço Real

| Característica | Mock | Serviço Real |
|---------------|------|-------------|
| Autorização | Sempre autoriza | Pode autorizar ou não |
| Dependência Externa | Não | Sim |
| Velocidade | Instantâneo | Depende da rede |
| Uso | Testes locais | Produção/Testes reais |

### Quando Usar o Mock

**Use o mock quando:**
- Testando localmente
- Serviço externo está indisponível
- Quer garantir que transferências sempre funcionem para testes
- Desenvolvendo novas funcionalidades

**Não use o mock quando:**
- Testando integração real com serviços externos
- Em ambiente de produção
- Validando comportamento do serviço de autorização

### Troubleshooting do Mock

#### Mock não está funcionando

1. **Verifique se a variável está definida:**
   ```bash
   docker exec picpay_php_cli cat /var/www/html/.env | grep MOCK
   ```

2. **Verifique os logs:**
   ```bash
   docker logs picpay_php_cli --tail 20
   ```

3. **Reinicie o container:**
   ```bash
   docker restart picpay_php_cli
   ```

#### Erro ao reiniciar

Se houver erro ao reiniciar, verifique se o container está rodando:

```bash
docker ps | grep picpay_php_cli
```

Se não estiver, inicie novamente:

```bash
docker-compose up -d php-cli
```

---

## Verificação de Resultados

### Ver Saldos Atualizados

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"
```

**Exemplo de saída:**
```
 id |   full_name   | balance 
----+---------------+---------
  1 | João Silva    |  900.00
  2 | Maria Santos  |  600.00
  3 | Loja do Zé    |    0.00
```

### Ver Histórico de Transferências

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, payer_id, payee_id, value, status, created_at FROM transfers ORDER BY created_at DESC LIMIT 5;"
```

**Exemplo de saída:**
```
 id | payer_id | payee_id | value  |  status   |      created_at       
----+----------+----------+--------+-----------+-----------------------
  1 |        1 |        2 | 100.00 | completed | 2024-01-15 10:30:00
```

### Ver Logs da Aplicação

```bash
# Ver todos os logs
docker logs picpay_php_cli

# Ver logs em tempo real
docker logs -f picpay_php_cli

# Filtrar apenas erros
docker logs picpay_php_cli | grep ERROR
```
---

## Cenários de Teste Completos

### Cenário 1: Fluxo Completo de Transferência

1. **Verificar saldo inicial:**
   ```bash
   docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id WHERE u.id IN (1,2);"
   ```

2. **Fazer transferência:**
   ```bash
   curl -X POST http://localhost:8000/transfer \
     -H "Content-Type: application/json" \
     -d '{"value": 100.0, "payer": 1, "payee": 2}'
   ```

3. **Verificar saldo final:**
   ```bash
   docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id WHERE u.id IN (1,2);"
   ```

4. **Verificar transferência registrada:**
   ```bash
   docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT * FROM transfers WHERE payer_id = 1 ORDER BY created_at DESC LIMIT 1;"
   ```

### Cenário 2: Teste de Erro e Rollback

1. **Fazer transferência que vai falhar (saldo insuficiente):**
   ```bash
   curl -X POST http://localhost:8000/transfer \
     -H "Content-Type: application/json" \
     -d '{"value": 2000.0, "payer": 1, "payee": 2}'
   ```

2. **Verificar que saldo não mudou:**
   ```bash
   docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id WHERE u.id = 1;"
   ```

3. **Verificar que transferência foi marcada como falha:**
   ```bash
   docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, status FROM transfers ORDER BY created_at DESC LIMIT 1;"
   ```

---

## Resolução de Problemas

### Problema: "Connection refused"

**Causa:** API não está rodando

**Solução:**
```bash
# Verificar containers
docker ps

# Se não estiverem rodando
docker-compose up -d

# Verificar logs
docker logs picpay_php_cli
```

### Problema: "User not found"

**Causa:** Usuários não foram criados

**Solução:**
```bash
# Executar seed novamente
docker exec picpay_php_cli php database/seeds/seed_users.php
```

### Problema: Testes falhando

**Causa:** Banco de dados não está limpo ou configurado

**Solução:**
```bash
# Limpar banco
docker exec picpay_db psql -U picpay_user -d picpay_db -c "TRUNCATE TABLE transfers, wallets, users RESTART IDENTITY CASCADE;"

# Recriar dados
docker exec picpay_php_cli php database/seeds/seed_users.php

# Executar testes novamente
docker exec picpay_php_cli composer test
```

### Problema: Erro de permissão no script

**Windows PowerShell:**
```powershell
# Permitir execução de scripts (se necessário)
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

---

## Comandos Úteis para Banco de Dados

Este guia mostra como consultar informações no banco de dados PostgreSQL.

### Verificar Saldos Atuais

#### Comando Direto (PowerShell/CMD)

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, u.user_type, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"
```

#### Versão Simplificada (só saldos)

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"
```

---

## Outros Comandos Úteis

### 1. Ver Todos os Usuários

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, full_name, cpf, email, user_type FROM users ORDER BY id;"
```

### 2. Ver Transferências Recentes

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, payer_id, payee_id, value, status, created_at FROM transfers ORDER BY created_at DESC LIMIT 10;"
```

### 3. Ver Transferências por Status

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT status, COUNT(*) as total, SUM(value) as total_value FROM transfers GROUP BY status;"
```

### 4. Ver Histórico de um Usuário Específico

```bash
# Substitua 1 pelo ID do usuário
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT t.id, t.value, t.status, CASE WHEN t.payer_id = 1 THEN 'Enviado para ' || u2.full_name WHEN t.payee_id = 1 THEN 'Recebido de ' || u1.full_name END as description, t.created_at FROM transfers t LEFT JOIN users u1 ON t.payer_id = u1.id LEFT JOIN users u2 ON t.payee_id = u2.id WHERE t.payer_id = 1 OR t.payee_id = 1 ORDER BY t.created_at DESC;"
```

### 5. Ver Saldo Total no Sistema

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT SUM(balance) as total_balance FROM wallets;"
```

### 6. Ver Transferências Bem-Sucedidas

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT COUNT(*) as total_completed, SUM(value) as total_value FROM transfers WHERE status = 'completed';"
```

### 7. Ver Transferências Falhadas

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT COUNT(*) as total_failed, SUM(value) as total_value FROM transfers WHERE status = 'failed';"
```

### 8. Ver Detalhes Completos de uma Transferência

```bash
# Substitua 7 pelo ID da transferência
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT t.*, u1.full_name as payer_name, u2.full_name as payee_name FROM transfers t JOIN users u1 ON t.payer_id = u1.id JOIN users u2 ON t.payee_id = u2.id WHERE t.id = 7;"
```

---

## Acessar o Banco Interativamente

Para acessar o banco de dados e executar comandos SQL interativamente:

```bash
docker exec -it picpay_db psql -U picpay_user -d picpay_db
```

Depois você pode executar comandos SQL diretamente:

```sql
-- Ver saldos
SELECT u.id, u.full_name, w.balance 
FROM users u 
JOIN wallets w ON u.id = w.user_id 
ORDER BY u.id;

-- Ver transferências
SELECT * FROM transfers ORDER BY created_at DESC LIMIT 10;

-- Sair
\q
```

---

## Comandos Mais Usados

### Ver Saldos (Mais Usado)

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"
```

### Ver Últimas Transferências

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, payer_id, payee_id, value, status FROM transfers ORDER BY created_at DESC LIMIT 5;"
```

---

## Dicas

1. **Use comandos diretos** - Mais rápido que scripts para consultas simples
2. **Salve comandos frequentes** - Crie scripts para comandos que usa muito
3. **Acesso interativo** - Use `docker exec -it` quando precisar fazer várias consultas
4. **Formatação** - Adicione `\x` no psql para saída expandida (mais legível)

---

## Troubleshooting

### Erro: "container not found"

```bash
# Verificar se o container está rodando
docker ps | grep picpay_db

# Se não estiver, iniciar
docker-compose up -d db
```

### Erro: "connection refused"

```bash
# Aguardar alguns segundos para o banco inicializar
# Verificar logs
docker logs picpay_db
```

---

## Guia Completo de Testes - Detalhado

Este documento explica como testar o sistema de transferências do PicPay de diferentes formas.

### Índice Detalhado

1. Testes Automatizados
2. Testes Manuais da API
3. Testes de Integração
4. Verificação de Funcionalidades
5. Cenários de Teste

---

## Testes Automatizados Detalhados

### Pré-requisitos

Certifique-se de que os containers estão rodando:

```bash
docker-compose up -d
```

### Executar Todos os Testes

```bash
docker exec picpay_php_cli composer test
```

Ou manualmente:

```bash
docker exec picpay_php_cli vendor/bin/phpunit
```

### Executar Apenas Testes Unitários

```bash
docker exec picpay_php_cli vendor/bin/phpunit tests/Unit
```

### Executar Apenas Testes de Integração

```bash
docker exec picpay_php_cli vendor/bin/phpunit tests/Integration
```

### Executar um Teste Específico

```bash
docker exec picpay_php_cli vendor/bin/phpunit tests/Unit/Domain/Entity/WalletTest.php
```

### Ver Cobertura de Testes (se configurado)

```bash
docker exec picpay_php_cli vendor/bin/phpunit --coverage-text
```

---

## Testes Manuais da API Detalhados

### 1. Preparar o Ambiente

Primeiro, certifique-se de que tudo está configurado:

```bash
# Iniciar containers
docker-compose up -d

# Executar migrações
docker exec picpay_php_cli php database/migrations/run_migrations.php

# Popular com dados de teste
docker exec picpay_php_cli php database/seeds/seed_users.php
```

### 2. Verificar Usuários Criados

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, full_name, user_type, email FROM users;"
```

Você deve ver algo como:
```
 id |   full_name   | user_type |      email      
----+---------------+-----------+-----------------
  1 | João Silva    | common    | joao@example.com
  2 | Maria Santos  | common    | maria@example.com
  3 | Loja do Zé    | merchant  | loja@example.com
```

### 3. Verificar Saldos Iniciais

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, u.user_type, w.balance FROM users u JOIN wallets w ON u.id = w.user_id;"
```

---

## Testando o Endpoint POST /transfer - Detalhado

### Teste 1: Transferência Bem-Sucedida

**Cenário:** Usuário comum transfere R$ 100,00 para outro usuário comum

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.0,
    "payer": 1,
    "payee": 2
  }'
```

**Resultado Esperado:**
```json
{
  "transfer_id": 1,
  "status": "completed",
  "message": "Transfer completed successfully"
}
```

**Verificar Saldos Após Transferência:**
```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"
```

O usuário 1 deve ter R$ 900,00 e o usuário 2 deve ter R$ 600,00.

### Teste 2: Saldo Insuficiente

**Cenário:** Tentar transferir mais do que o saldo disponível

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 2000.0,
    "payer": 1,
    "payee": 2
  }'
```

**Resultado Esperado:**
```json
{
  "error": "Payer 1 has insufficient balance"
}
```

**Status HTTP:** 400 Bad Request

### Teste 3: Lojista Tentando Enviar Dinheiro

**Cenário:** Lojista (ID 3) tenta transferir dinheiro

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.0,
    "payer": 3,
    "payee": 1
  }'
```

**Resultado Esperado:**
```json
{
  "error": "User 3 is a merchant and cannot send money"
}
```

**Status HTTP:** 400 Bad Request

### Teste 4: Usuário Não Encontrado

**Cenário:** Tentar transferir usando um ID de usuário inexistente

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.0,
    "payer": 999,
    "payee": 1
  }'
```

**Resultado Esperado:**
```json
{
  "error": "Payer with ID 999 not found"
}
```

**Status HTTP:** 404 Not Found

### Teste 5: Campos Faltando

**Cenário:** Enviar requisição sem todos os campos obrigatórios

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 100.0
  }'
```

**Resultado Esperado:**
```json
{
  "error": "Missing required fields: payer, payee, value"
}
```

**Status HTTP:** 400 Bad Request

### Teste 6: Valor Zero ou Negativo

**Cenário:** Tentar transferir valor inválido

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 0,
    "payer": 1,
    "payee": 2
  }'
```

**Resultado Esperado:** Erro de validação

### Teste 7: Transferir para Lojista

**Cenário:** Usuário comum transfere para lojista (lojistas podem receber)

```bash
curl -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{
    "value": 50.0,
    "payer": 1,
    "payee": 3
  }'
```

**Resultado Esperado:** Transferência bem-sucedida

---

## Testes de Concorrência (Race Conditions)

### Teste de Múltiplas Transferências Simultâneas

Crie um script para testar múltiplas requisições ao mesmo tempo:

**Criar arquivo `test_concurrency.sh`:**
```bash
#!/bin/bash

# Função para fazer transferência
transfer() {
    curl -X POST http://localhost:8000/transfer \
      -H "Content-Type: application/json" \
      -d "{\"value\": 10.0, \"payer\": 1, \"payee\": 2}" \
      -w "\nHTTP Status: %{http_code}\n" \
      -s
}

# Executar 10 transferências em paralelo
for i in {1..10}; do
    transfer &
done

wait
echo "Todas as transferências concluídas"
```

**Executar:**
```bash
chmod +x test_concurrency.sh
./test_concurrency.sh
```

**Verificar Consistência:**
```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT COUNT(*) as total_transfers, SUM(value) as total_value FROM transfers WHERE payer_id = 1;"
```

O saldo final deve estar consistente, mesmo com múltiplas requisições simultâneas.

---

## Verificação de Dados no Banco - Detalhado

### Ver Todas as Transferências

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT id, payer_id, payee_id, value, status, created_at FROM transfers ORDER BY created_at DESC;"
```

### Ver Transferências por Status

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "SELECT status, COUNT(*) as count FROM transfers GROUP BY status;"
```

### Ver Histórico de uma Carteira

```bash
docker exec picpay_db psql -U picpay_user -d picpay_db -c "
SELECT 
    t.id,
    t.value,
    t.status,
    CASE 
        WHEN t.payer_id = 1 THEN 'Enviado para ' || u2.full_name
        WHEN t.payee_id = 1 THEN 'Recebido de ' || u1.full_name
    END as description,
    t.created_at
FROM transfers t
LEFT JOIN users u1 ON t.payer_id = u1.id
LEFT JOIN users u2 ON t.payee_id = u2.id
WHERE t.payer_id = 1 OR t.payee_id = 1
ORDER BY t.created_at DESC;
"
```

---

## Testes com Postman - Collection Completa

### Importar Collection

1. Abra o Postman
2. Clique em "Import"
3. Cole o JSON abaixo:

```json
{
  "info": {
    "name": "PicPay Transfer API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Transferência Bem-Sucedida",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"value\": 100.0,\n  \"payer\": 1,\n  \"payee\": 2\n}"
        },
        "url": {
          "raw": "http://localhost:8000/transfer",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["transfer"]
        }
      }
    },
    {
      "name": "Saldo Insuficiente",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"value\": 2000.0,\n  \"payer\": 1,\n  \"payee\": 2\n}"
        },
        "url": {
          "raw": "http://localhost:8000/transfer",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["transfer"]
        }
      }
    },
    {
      "name": "Lojista Tentando Enviar",
      "request": {
        "method": "POST",
        "header": [
          {
            "key": "Content-Type",
            "value": "application/json"
          }
        ],
        "body": {
          "mode": "raw",
          "raw": "{\n  \"value\": 100.0,\n  \"payer\": 3,\n  \"payee\": 1\n}"
        },
        "url": {
          "raw": "http://localhost:8000/transfer",
          "protocol": "http",
          "host": ["localhost"],
          "port": "8000",
          "path": ["transfer"]
        }
      }
    }
  ]
}
```

---

## Verificação de Logs Detalhada

### Ver Logs da Aplicação

```bash
docker logs -f picpay_php_cli
```

### Ver Logs de Erros

```bash
docker logs picpay_php_cli | grep ERROR
```

### Ver Logs de Transferências

```bash
docker logs picpay_php_cli | grep "transfer"
```

---


## Troubleshooting de Testes Detalhado

### Erro: "Connection refused"

**Problema:** A API não está respondendo

**Solução:**
```bash
# Verificar se os containers estão rodando
docker ps

# Reiniciar se necessário
docker-compose restart
```

### Erro: "User not found"

**Problema:** Usuários não foram criados

**Solução:**
```bash
# Executar seed novamente
docker exec picpay_php_cli php database/seeds/seed_users.php
```

### Erro: "Database connection failed"

**Problema:** Banco de dados não está acessível

**Solução:**
```bash
# Verificar se o banco está rodando
docker ps | grep picpay_db

# Ver logs do banco
docker logs picpay_db

# Reiniciar banco
docker-compose restart db
```

### Testes Falhando

**Problema:** Testes não estão passando

**Solução:**
```bash
# Limpar banco de dados de teste
docker exec picpay_db psql -U picpay_user -d picpay_db -c "TRUNCATE TABLE transfers, wallets, users RESTART IDENTITY CASCADE;"

# Executar testes novamente
docker exec picpay_php_cli composer test
```

---

## Exemplo de Script de Teste Completo

Crie um arquivo `test_all.sh`:

```bash
#!/bin/bash

echo "Iniciando testes do sistema PicPay"
echo ""

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Verificar se containers estão rodando
echo "1. Verificando containers..."
if ! docker ps | grep -q "picpay"; then
    echo -e "${RED}Containers não estão rodando. Execute: docker-compose up -d${NC}"
    exit 1
fi
echo -e "${GREEN}Containers rodando${NC}"
echo ""

# 2. Executar testes automatizados
echo "2. Executando testes automatizados..."
docker exec picpay_php_cli composer test
if [ $? -eq 0 ]; then
    echo -e "${GREEN}Testes automatizados passaram${NC}"
else
    echo -e "${RED}Testes automatizados falharam${NC}"
    exit 1
fi
echo ""

# 3. Testar API
echo "3. Testando API..."
RESPONSE=$(curl -s -X POST http://localhost:8000/transfer \
  -H "Content-Type: application/json" \
  -d '{"value": 100.0, "payer": 1, "payee": 2}' \
  -w "\n%{http_code}")

HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
BODY=$(echo "$RESPONSE" | head -n-1)

if [ "$HTTP_CODE" -eq 201 ]; then
    echo -e "${GREEN}API respondendo corretamente${NC}"
    echo "Response: $BODY"
else
    echo -e "${RED}API retornou código $HTTP_CODE${NC}"
    echo "Response: $BODY"
fi
echo ""

# 4. Verificar saldos
echo "4. Verificando saldos..."
docker exec picpay_db psql -U picpay_user -d picpay_db -t -c "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"
echo ""

echo -e "${GREEN}Todos os testes concluídos!${NC}"
```

Torne executável e execute:
```bash
chmod +x test_all.sh
./test_all.sh
```
