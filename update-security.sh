#!/bin/bash

# Script para aplicar as atualizações de segurança

echo "Iniciando atualização do sistema de segurança..."

# Atualiza dependências do Composer se necessário
echo "Verificando dependências..."
composer update

# Executa o script de atualização de segurança
echo "Aplicando alterações no banco de dados..."
php update-security.php

echo "Atualização concluída com sucesso!"
echo "Por favor, teste o sistema acessando a página de login."
