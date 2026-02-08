# Script de teste da API PicPay (PowerShell)
# Uso: .\test_api.ps1

$BASE_URL = "http://localhost:8000"

Write-Host "Testando API PicPay" -ForegroundColor Cyan
Write-Host "======================" -ForegroundColor Cyan
Write-Host ""

# Função para fazer requisição e exibir resultado
function Test-Endpoint {
    param(
        [string]$Name,
        [string]$Method,
        [string]$Data,
        [int]$ExpectedStatus
    )
    
    Write-Host "Teste: $Name" -ForegroundColor Yellow
    Write-Host "Request: $Method $BASE_URL/transfer"
    if ($Data) {
        Write-Host "Body: $Data"
    }
    
    try {
        if ($Data) {
            $response = Invoke-RestMethod -Uri "$BASE_URL/transfer" -Method $Method `
                -ContentType "application/json" `
                -Body $Data `
                -ErrorAction Stop
            $httpCode = 200
        } else {
            $response = Invoke-WebRequest -Uri "$BASE_URL/transfer" -Method $Method `
                -ErrorAction Stop
            $httpCode = $response.StatusCode
            $response = $response.Content | ConvertFrom-Json
        }
        
        if ($httpCode -eq $ExpectedStatus) {
            Write-Host "✅ Status: $httpCode (esperado: $ExpectedStatus)" -ForegroundColor Green
        } else {
            Write-Host "❌ Status: $httpCode (esperado: $ExpectedStatus)" -ForegroundColor Red
        }
        
        Write-Host "Response: $($response | ConvertTo-Json -Compress)"
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        if ($statusCode -eq $ExpectedStatus) {
            Write-Host "✅ Status: $statusCode (esperado: $ExpectedStatus)" -ForegroundColor Green
        } else {
            Write-Host "❌ Status: $statusCode (esperado: $ExpectedStatus)" -ForegroundColor Red
        }
        
        try {
            $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
            $responseBody = $reader.ReadToEnd() | ConvertFrom-Json
            Write-Host "Response: $($responseBody | ConvertTo-Json -Compress)"
        } catch {
            Write-Host "Response: $($_.Exception.Message)"
        }
    }
    
    Write-Host ""
}

# Verificar se a API está respondendo
Write-Host "Verificando se a API está online..."
try {
    $test = Invoke-WebRequest -Uri "$BASE_URL" -Method GET -TimeoutSec 2 -ErrorAction Stop
    Write-Host "API está online" -ForegroundColor Green
} catch {
    Write-Host "API não está respondendo em $BASE_URL" -ForegroundColor Red
    Write-Host "Certifique-se de que os containers estão rodando: docker-compose up -d" -ForegroundColor Yellow
    exit 1
}
Write-Host ""

# Teste 1: Transferência bem-sucedida
Test-Endpoint `
    -Name "Transferência bem-sucedida" `
    -Method "POST" `
    -Data '{"value": 100.0, "payer": 1, "payee": 2}' `
    -ExpectedStatus 201

# Teste 2: Saldo insuficiente
Test-Endpoint `
    -Name "Saldo insuficiente" `
    -Method "POST" `
    -Data '{"value": 2000.0, "payer": 1, "payee": 2}' `
    -ExpectedStatus 400

# Teste 3: Lojista tentando enviar
Test-Endpoint `
    -Name "Lojista tentando enviar dinheiro" `
    -Method "POST" `
    -Data '{"value": 100.0, "payer": 3, "payee": 1}' `
    -ExpectedStatus 400

# Teste 4: Usuário não encontrado
Test-Endpoint `
    -Name "Usuário não encontrado" `
    -Method "POST" `
    -Data '{"value": 100.0, "payer": 999, "payee": 1}' `
    -ExpectedStatus 404

# Teste 5: Campos faltando
Test-Endpoint `
    -Name "Campos faltando" `
    -Method "POST" `
    -Data '{"value": 100.0}' `
    -ExpectedStatus 400

Write-Host "======================" -ForegroundColor Cyan
Write-Host "Verificando saldos no banco..." -ForegroundColor Yellow
Write-Host ""

# Verificar saldos
docker-compose exec db psql -U picpay_user -d picpay_db -t -c `
    "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;" 2>$null

Write-Host ""
Write-Host "======================" -ForegroundColor Cyan
Write-Host "Verificando transferências..." -ForegroundColor Yellow
Write-Host ""

# Verificar transferências
docker-compose exec db psql -U picpay_user -d picpay_db -t -c `
    "SELECT id, payer_id, payee_id, value, status FROM transfers ORDER BY created_at DESC LIMIT 5;" 2>$null

Write-Host ""
Write-Host "Testes concluídos!" -ForegroundColor Green


