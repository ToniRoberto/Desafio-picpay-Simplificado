# Script para Verificar Saldos no Banco de Dados
# Uso: .\verificar_saldos.ps1

Write-Host " Verificando Saldos das Carteiras" -ForegroundColor Cyan
Write-Host "====================================" -ForegroundColor Cyan
Write-Host ""

# Comando SQL para buscar saldos
$sqlQuery = "SELECT u.id, u.full_name, u.user_type, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;"

# Executar comando no container do banco
$result = docker exec picpay_db psql -U picpay_user -d picpay_db -c $sqlQuery

if ($LASTEXITCODE -eq 0) {
    Write-Host $result
} else {
    Write-Host " Erro ao conectar ao banco de dados" -ForegroundColor Red
    Write-Host "Verifique se o container está rodando: docker ps | grep picpay_db" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "====================================" -ForegroundColor Cyan

