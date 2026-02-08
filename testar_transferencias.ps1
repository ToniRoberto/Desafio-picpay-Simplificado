# Script de Teste de Transferências
# Uso: .\testar_transferencias.ps1

$baseUrl = "http://localhost:8000/transfer"

Write-Host " Testando API de Transferências" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Função para fazer requisição
function Test-Transfer {
    param(
        [string]$Name,
        [string]$Body,
        [int]$ExpectedStatus = 201
    )
    
    Write-Host "Teste: $Name" -ForegroundColor Yellow
    
    try {
        $response = Invoke-RestMethod -Uri $baseUrl `
            -Method POST `
            -ContentType "application/json" `
            -Body $Body
        
        Write-Host " SUCESSO (Status esperado: $ExpectedStatus)" -ForegroundColor Green
        $response | ConvertTo-Json
    } catch {
        $statusCode = $_.Exception.Response.StatusCode.value__
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        
        if ($statusCode -eq $ExpectedStatus) {
            Write-Host " ERRO ESPERADO (Status: $statusCode)" -ForegroundColor Green
        } else {
            Write-Host " ERRO INESPERADO (Status: $statusCode, Esperado: $ExpectedStatus)" -ForegroundColor Red
        }
        
        Write-Host $responseBody
    }
    
    Write-Host ""
}

# Teste 1: Transferência bem-sucedida
Test-Transfer `
    -Name "Transferência Bem-Sucedida" `
    -Body '{"value": 100.0, "payer": 1, "payee": 2}' `
    -ExpectedStatus 201

# Teste 2: Saldo insuficiente
Test-Transfer `
    -Name "Saldo Insuficiente" `
    -Body '{"value": 2000.0, "payer": 1, "payee": 2}' `
    -ExpectedStatus 400

# Teste 3: Lojista tentando enviar
Test-Transfer `
    -Name "Lojista Tentando Enviar" `
    -Body '{"value": 100.0, "payer": 3, "payee": 1}' `
    -ExpectedStatus 400

# Teste 4: Usuário não encontrado
Test-Transfer `
    -Name "Usuário Não Encontrado" `
    -Body '{"value": 100.0, "payer": 999, "payee": 1}' `
    -ExpectedStatus 404

Write-Host "=================================" -ForegroundColor Cyan
Write-Host " Testes concluídos!" -ForegroundColor Green
Write-Host ""
Write-Host "Para verificar saldos:" -ForegroundColor Yellow
Write-Host "docker exec picpay_db psql -U picpay_user -d picpay_db -c `"SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;`"" -ForegroundColor Gray

