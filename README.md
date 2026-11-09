# Achadinhos — Central de Agentes (fundação PHP)

Este pacote é a **primeira fundação executável** do painel administrativo + escritório virtual.
Ele não substitui nem altera o template público da loja. O ZIP original recebido está incluído
separadamente em `backup/cgi-bin-original.zip`, sem modificações.

## Compatibilidade e arquitetura

- PHP 8.4+ (validado nesta preparação com PHP 8.4.24).
- Apache/cPanel + HTTPS; sem Node.js, npm, processo na porta 3000 ou servidor frontend separado.
- MySQL 8 / MariaDB compatível, usando PDO MySQL.
- Interface HTML/CSS/JavaScript servida pelo próprio PHP.
- API no mesmo domínio: `/api/...`, sempre com JSON e status HTTP apropriado.
- Cron + PHP CLI para processar fila. Nenhum worker Node permanente.

> Você escreveu “PHP 8.8.4”. A versão disponível para validação neste ambiente foi PHP 8.4.24.
> Confirme no cPanel qual versão está selecionada para o domínio. O código evita recursos posteriores
> ao PHP 8.4.

## Instalação resumida

1. Faça upload da pasta `public/` para um subdiretório protegido do seu domínio (ex.: `painel/`).
2. Aponte o Document Root desse subdomínio para `public/`, se possível. Se não for possível,
   mantenha `app/`, `database/` e `config/` fora da pasta pública e ajuste `APP_ROOT` em `public/index.php`.
3. Crie um banco MySQL e um usuário no cPanel; conceda apenas os privilégios necessários nesse banco.
4. Copie `config/config.example.php` para `config/config.php` (fora da raiz pública) e preencha banco,
   URL e uma chave longa aleatória. Alternativamente, use o instalador web temporário descrito em `docs/INSTALACAO.md`.
5. Importe `database/schema.sql` no banco.
6. Configure `APP_CONFIG` no ambiente PHP apontando para `config/config.php`.
7. Acesse `/login` e entre com a senha administrativa definida no hash `ADMIN_PASSWORD_HASH`.
8. Configure Cron para executar `php /caminho/absoluto/cron/worker.php` a cada minuto.

O pacote não contém credenciais Shopee/Google e não inventa endpoints. A integração real depende
das credenciais e permissões oficiais que você fornecer/configurar.

## O que já funciona nesta fundação

- Login administrativo por sessão e proteção CSRF.
- API JSON consistente: `/api/health`, `/api/dashboard`, `/api/agents`, `/api/tasks`,
  `/api/approvals`, `/api/activity`.
- MySQL: agentes, tarefas, aprovações, atividades e mensagens.
- Criação de tarefa manual e aprovação/rejeição de propostas.
- Escritório virtual original em pixel art/isométrico, com parede amarela, avatares e estados
  vindos do banco; animações visuais não fingem que uma tarefa foi executada.
- Cron worker que reivindica tarefas com lock transacional e registra resultado/erro.
- Tela de diagnóstico da versão PHP, extensões PDO e conexão ao banco.

## O que ainda NÃO está conectado

- API de afiliados Shopee: requer credenciais, escopos e documentação/limites liberados para sua conta.
- Google Analytics 4, Search Console, Tag Manager e PageSpeed: requer OAuth e IDs das propriedades.
- Modelo de IA: requer escolha/configuração do provedor e limites.
- Importação em massa de catálogo: ainda não habilitada para produção. Primeiro é necessário mapear
  o esquema real do catálogo SQLite e a API Shopee, testar paginação, deduplicação, rate limits e
  idempotência.
- Conversas espontâneas entre agentes: por enquanto há estrutura de mensagens e atividades reais;
  não há diálogo autônomo fictício.
- WebSocket: não é necessário nesta hospedagem inicial; a interface atual atualiza por polling HTTP.

## Segurança importante

Não publique `config/`, `app/`, `database/`, `cron/`, logs, dumps ou backups dentro do Document Root.
Use HTTPS. Altere a senha inicial. Não coloque tokens em JavaScript. Não execute importações em massa
antes de validar limites e o catálogo em cópia. Toda alteração destrutiva ou publicação no site exige
aprovação explícita.
