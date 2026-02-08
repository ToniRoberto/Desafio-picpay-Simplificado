#!/bin/bash

# Script de teste da API PicPay
# Uso: ./test_api.sh

BASE_URL="http://localhost:8000"
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "Testando API PicPay"
echo "======================"
echo ""

# Função para fazer requisição e exibir resultado
test_endpoint() {
    local name=$1
    local method=$2
    local data=$3
    local expected_status=$4
    
    echo "Teste: $name"
    echo "Request: $method $BASE_URL/transfer"
    if [ ! -z "$data" ]; then
        echo "Body: $data"
    fi
    
    if [ -z "$data" ]; then
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL/transfer")
    else
        response=$(curl -s -w "\n%{http_code}" -X $method "$BASE_URL/transfer" \
            -H "Content-Type: application/json" \
            -d "$data")
    fi
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)
    
    if [ "$http_code" -eq "$expected_status" ]; then
        echo -e "${GREEN} Status: $http_code (esperado: $expected_status)${NC}"
    else
        echo -e "${RED} Status: $http_code (esperado: $expected_status)${NC}"
    fi
    
    echo "Response: $body"
    echo ""
}

# Verificar se a API está respondendo
echo "Verificando se a API está online..."
if ! curl -s "$BASE_URL" > /dev/null 2>&1; then
    echo -e "${RED} API não está respondendo em $BASE_URL${NC}"
    echo "Certifique-se de que os containers estão rodando: docker-compose up -d"
    exit 1
fi
echo -e "${GREEN} API está online${NC}"
echo ""

# Teste 1: Transferência bem-sucedida
test_endpoint \
    "Transferência bem-sucedida" \
    "POST" \
    '{"value": 100.0, "payer": 1, "payee": 2}' \
    201

# Teste 2: Saldo insuficiente
test_endpoint \
    "Saldo insuficiente" \
    "POST" \
    '{"value": 2000.0, "payer": 1, "payee": 2}' \
    400

# Teste 3: Lojista tentando enviar
test_endpoint \
    "Lojista tentando enviar dinheiro" \
    "POST" \
    '{"value": 100.0, "payer": 3, "payee": 1}' \
    400

# Teste 4: Usuário não encontrado
test_endpoint \
    "Usuário não encontrado" \
    "POST" \
    '{"value": 100.0, "payer": 999, "payee": 1}' \
    404

# Teste 5: Campos faltando
test_endpoint \
    "Campos faltando" \
    "POST" \
    '{"value": 100.0}' \
    400

# Teste 6: Valor zero
test_endpoint \
    "Valor zero" \
    "POST" \
    '{"value": 0, "payer": 1, "payee": 2}' \
    400

echo "======================"
echo -e "${YELLOW} Verificando saldos no banco...${NC}"
echo ""

# Verificar saldos
docker-compose exec db psql -U picpay_user -d picpay_db -t -c \
    "SELECT u.id, u.full_name, w.balance FROM users u JOIN wallets w ON u.id = w.user_id ORDER BY u.id;" 2>/dev/null

echo ""
echo "======================"
echo -e "${YELLOW} Verificando transferências...${NC}"
echo ""

# Verificar transferências
docker-compose exec db psql -U picpay_user -d picpay_db -t -c \
    "SELECT id, payer_id, payee_id, value, status FROM transfers ORDER BY created_at DESC LIMIT 5;" 2>/dev/null

echo ""
echo -e "${GREEN} Testes concluídos!${NC}"


