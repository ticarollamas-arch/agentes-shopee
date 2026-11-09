# Instalação no cPanel (PHP + MySQL)

## 1. Confirmar versão e extensões
No cPanel, selecione PHP 8.4 ou superior para o subdomínio e habilite:
- `pdo`
- `pdo_mysql`
- `mbstring`
- `json`
- `openssl`
- `curl` (necessário nas futuras integrações HTTP)

A versão vista no ambiente de preparação foi PHP 8.4.24. A versão do seu domínio precisa ser confirmada no cPanel.

## 2. Banco MySQL/MariaDB
Crie banco e usuário no cPanel. Dê ao usuário permissões no banco. Importe `database/schema.sql` pelo phpMyAdmin.

## 3. Segredos
Copie `config/config.example.php` para `config/config.php`, em diretório fora do Document Root.
Gere chave:
```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```
Gere hash:
```bash
php -r "echo password_hash('SENHA_FORTE', PASSWORD_DEFAULT), PHP_EOL;"
```
Preencha `app.key`, `app.base_url`, banco e credenciais administrativas. Defina a variável de ambiente `APP_CONFIG` para o caminho absoluto desse arquivo. Não envie `config.php` ao GitHub.

## 4. Raiz pública
Aponte o Document Root do subdomínio para a pasta `public/`. Se o cPanel não permitir, não coloque `app/`, `config/`, `database/` ou `cron/` em pasta pública; solicite ajuste de estrutura antes de publicar.

## 5. Cron
Depois de configurar o ambiente PHP CLI e testar manualmente:
```bash
/usr/local/bin/php /CAMINHO/ABSOLUTO/cron/worker.php
```
Configure no Cron Jobs do cPanel para executar a cada minuto. O worker atual é deliberadamente conservador: registra tarefas e informa que handlers ainda precisam ser configurados. Não chama a Shopee nem altera o site.

## 6. Diagnóstico
Acesse `/api/health`. Esperado: JSON com `ok: true`, versão PHP e `database: connected`. Se receber HTML/DOCTYPE, confira o Document Root, `.htaccess`, versão PHP, logs e se a rota está chegando a `public/index.php`.

## 7. Integrações futuras
Shopee: inserir credenciais somente no arquivo de configuração fora da pasta pública. Confirmar API, assinatura, escopos, limites e termos vigentes para sua conta.
Google: OAuth com escopos mínimos e IDs de propriedades. GA4 mede eventos de clique afiliado; não presume venda concluída.
Antes de sincronização de catálogo, mapear a base SQLite atual, identificadores e regras de variantes. A importação deve ser idempotente e em lotes, com checkpoint e rate limit.
