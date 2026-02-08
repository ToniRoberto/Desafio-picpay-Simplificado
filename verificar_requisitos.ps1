# Script de Verificação de Requisitos
# Uso: .\verificar_requisitos.ps1

Write-Host "Verificando Requisitos do Sistema" -ForegroundColor Cyan
Write-Host "=================================" -ForegroundColor Cyan
Write-Host ""

# Verificar Docker
Write-Host "1. Docker:" -ForegroundColor Yellow
try {
    $dockerVersion = docker --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   $dockerVersion" -ForegroundColor Green
    } else {
        throw
    }
} catch {
    Write-Host "   Docker NÃO está instalado!" -ForegroundColor Red
    Write-Host "   Download: https://www.docker.com/products/docker-desktop/" -ForegroundColor Yellow
}

# Verificar Docker Compose
Write-Host "`n2. Docker Compose:" -ForegroundColor Yellow
try {
    $composeVersion = docker-compose --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   $composeVersion" -ForegroundColor Green
    } else {
        throw
    }
} catch {
    Write-Host "   Docker Compose NÃO está instalado!" -ForegroundColor Red
}

# Verificar se Docker está rodando
Write-Host "`n3. Status do Docker:" -ForegroundColor Yellow
try {
    $null = docker ps 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   Docker está RODANDO" -ForegroundColor Green
    } else {
        throw
    }
} catch {
    Write-Host "   Docker NÃO está rodando!" -ForegroundColor Red
    Write-Host "   Inicie o Docker Desktop" -ForegroundColor Yellow
}

# Verificar Git
Write-Host "`n4. Git:" -ForegroundColor Yellow
try {
    $gitVersion = git --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "   $gitVersion" -ForegroundColor Green
    } else {
        throw
    }
} catch {
    Write-Host "   Git NÃO está instalado (opcional)" -ForegroundColor Yellow
}

# Verificar PowerShell
Write-Host "`n5. PowerShell:" -ForegroundColor Yellow
$psVersion = $PSVersionTable.PSVersion
Write-Host "   Versão: $psVersion" -ForegroundColor Green

# Verificar portas
Write-Host "`n6. Portas Disponíveis:" -ForegroundColor Yellow
$port8000 = Get-NetTCPConnection -LocalPort 8000 -ErrorAction SilentlyContinue
$port5432 = Get-NetTCPConnection -LocalPort 5432 -ErrorAction SilentlyContinue

if ($port8000) {
    Write-Host "   Porta 8000: EM USO" -ForegroundColor Yellow
} else {
    Write-Host "   Porta 8000: Disponível" -ForegroundColor Green
}

if ($port5432) {
    Write-Host "   Porta 5432: EM USO" -ForegroundColor Yellow
} else {
    Write-Host "   Porta 5432: Disponível" -ForegroundColor Green
}

Write-Host "`n=================================" -ForegroundColor Cyan

# Resumo
$allOk = $true
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
    $allOk = $false
}

if ($allOk) {
    Write-Host "Status: PRONTO PARA USAR!" -ForegroundColor Green
    Write-Host "Você pode executar: docker-compose up -d" -ForegroundColor Cyan
} else {
    Write-Host "Status: FALTANDO REQUISITOS" -ForegroundColor Red
    Write-Host "Instale o Docker Desktop primeiro" -ForegroundColor Yellow
}

